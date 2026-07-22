<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PublicDocumentController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $q        = trim($validated['q'] ?? '');
        $searched = $q !== '';
        $results  = [];
        $settings = SystemSetting::current();

        if ($searched) {
            $query = Document::with('category')
                ->where('status', 'approved')
                ->whereNotNull('file_path');

            $allowedCategories = $settings->public_search_categories ?? [];
            if (! empty($allowedCategories)) {
                $query->whereIn('category_id', $allowedCategories);
            }

            $query->where(function ($builder) use ($q, $settings) {
                $this->applyPublicSearch($builder, $q, $settings);
            });

            $results = $query->latest()->get()->map(fn ($document) => [
                'id'           => $document->id,
                'tracking'     => $document->tracking_number,
                'title'        => $document->title,
                'category'     => $document->category?->name ?? '—',
                'summary'      => $document->description
                    ? Str::limit($document->description, 80)
                    : '—',
                'date'         => $document->approved_at?->format('M d, Y')
                    ?? $document->updated_at->format('M d, Y'),
                'view_url'     => route('get-documents.show', $document->id),
                'download_url' => route('get-documents.file', $document->id),
            ])->all();
        }

        return Inertia::render('Documents/GetDocuments', [
            'results'  => $results,
            'filters'  => compact('q'),
            'searched' => $searched,
        ]);
    }

    public function show(Document $document)
    {
        $this->ensurePublicDocument($document);
        $document->load('category');

        $previewable = $this->isPreviewable($document->file_path);

        return Inertia::render('Documents/GuestShow', [
            'document' => [
                'tracking'    => $document->tracking_number,
                'reference'   => $document->reference_no,
                'title'       => $document->title,
                'description' => $document->description ?? '—',
                'category'    => $document->category?->name ?? '—',
                'released'    => $document->approved_at?->format('M d, Y')
                    ?? $document->updated_at->format('M d, Y'),
                'previewable' => $previewable,
            ],
            'preview_url'  => $previewable
                ? route('get-documents.file', ['document' => $document->id, 'inline' => 1])
                : null,
            'download_url' => route('get-documents.file', $document->id),
        ]);
    }

    public function file(Document $document)
    {
        $this->ensurePublicDocument($document);

        if (! Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        $filename = basename($document->file_path);

        if (request()->boolean('inline')) {
            return Storage::disk('public')->response(
                $document->file_path,
                $filename,
                ['Content-Disposition' => 'inline; filename="' . $filename . '"']
            );
        }

        return Storage::disk('public')->download($document->file_path, $filename);
    }

    private function applyPublicSearch($builder, string $q, SystemSetting $settings): void
    {
        $fields = $settings->public_search_fields ?? SystemSetting::defaultPublicSearchFields();
        $first  = true;

        foreach ($fields as $field) {
            if ($field === 'category') {
                $method = $first ? 'whereHas' : 'orWhereHas';
                $builder->{$method}('category', fn ($categoryQuery) => $categoryQuery
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%"));
                $first = false;
                continue;
            }

            $method = $first ? 'where' : 'orWhere';
            $builder->{$method}($field, 'like', "%{$q}%");
            $first = false;
        }

        if ($first) {
            $builder->where('title', 'like', "%{$q}%");
        }
    }

    private function ensurePublicDocument(Document $document): void
    {
        if ($document->status !== 'approved' || ! $document->file_path) {
            abort(404);
        }

        $settings = SystemSetting::current();
        $allowedCategories = $settings->public_search_categories ?? [];

        if (! empty($allowedCategories) && ! in_array($document->category_id, $allowedCategories, true)) {
            abort(404);
        }
    }

    private function isPreviewable(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'], true);
    }
}
