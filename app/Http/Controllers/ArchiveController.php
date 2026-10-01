<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\DocumentRetentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ArchiveController extends Controller
{
    public function index(Request $request, DocumentRetentionService $retentionService)
    {
        $retentionService->archiveExpired();

        $filters = $request->validate([
            'q' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:document_categories,id',
            'retention_days' => 'nullable|integer|in:7,30,90,180,365,730,1825',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $base = Document::query()
            ->visibleTo(Auth::user())
            ->where('status', 'archived');

        $years = (clone $base)
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->pluck('archived_at')
            ->map(fn ($date) => (int) $date->year)
            ->unique()
            ->values()
            ->all();

        $query = (clone $base)->with(['category.parent', 'submitter', 'handler']);

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function ($builder) use ($term) {
                $builder->where('title', 'like', "%{$term}%")
                    ->orWhere('tracking_number', 'like', "%{$term}%")
                    ->orWhere('reference_no', 'like', "%{$term}%")
                    ->orWhereHas('submitter', fn ($rel) => $rel->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('category', fn ($rel) => $rel->where('name', 'like', "%{$term}%"));
            });
        }

        if (! empty($filters['category_id'])) {
            $query->whereIn('category_id', DocumentCategory::idsForFilter((int) $filters['category_id']));
        }

        if (! empty($filters['retention_days'])) {
            $query->where('retention_days', (int) $filters['retention_days']);
        }

        if (! empty($filters['year'])) {
            $query->whereYear('archived_at', (int) $filters['year']);
        }

        $counts = (clone $base)
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id')
            ->all();

        $archives = $query
            ->latest('archived_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($d) => [
                'id'       => $d->id,
                'tracking' => $d->tracking_number,
                'title'    => $d->title,
                'category' => $d->category?->label() ?? '—',
                'owner'    => $d->submitter?->name ?? '—',
                'handler'  => $d->handler?->name ?? '—',
                'retention' => $d->retention_days
                    ? DocumentRetentionService::formatRetention($d->retention_days)
                    : '—',
                'archived' => $d->archived_at?->format('M d, Y') ?? $d->updated_at->format('M d, Y'),
                'view_url' => route('documents.show', $d->id),
                'download_url' => $d->file_path
                    ? route('documents.file', $d->id)
                    : null,
            ]);

        return Inertia::render('Archive/Index', [
            'archives' => $archives,
            'totalArchives' => (clone $base)->count(),
            'categoryTree' => DocumentCategory::treeWithCounts($counts),
            'retentionOptions' => collect($this->retentionOptions())
                ->map(fn ($label, $days) => ['value' => $days, 'label' => $label])
                ->values(),
            'years' => $years,
            'filters' => [
                'q' => $filters['q'] ?? '',
                'category_id' => isset($filters['category_id']) ? (string) $filters['category_id'] : '',
                'retention_days' => isset($filters['retention_days']) ? (string) $filters['retention_days'] : '',
                'year' => isset($filters['year']) ? (string) $filters['year'] : '',
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function retentionOptions(): array
    {
        return [
            7 => '7 Days',
            30 => '30 Days',
            90 => '90 Days',
            180 => '6 Months',
            365 => '1 Year',
            730 => '2 Years',
            1825 => '5 Years',
        ];
    }

    public function restore(Document $document)
    {
        $document->update([
            'status'      => 'approved',
            'archived_at' => null,
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Document Restored',
            'description' => "Document {$document->tracking_number} restored from archive.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect()->back()
            ->with('success', "Document {$document->tracking_number} restored successfully.");
    }
}
