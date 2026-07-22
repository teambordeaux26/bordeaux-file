<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\DocumentRetentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index(DocumentRetentionService $retentionService)
    {
        $retentionService->archiveExpired();

        $documents = Document::with(['category', 'submitter'])
            ->where('status', '!=', 'archived')
            ->latest()
            ->get()
            ->map(fn($doc) => [
                'id'            => $doc->id,
                'tracking'      => $doc->tracking_number,
                'reference'     => $doc->reference_no,
                'title'         => $doc->title,
                'summary'       => $doc->description
                    ? Str::limit($doc->description, 60)
                    : '—',
                'category'      => $doc->category?->name ?? '—',
                'status'        => ucfirst($doc->status),
                'priority'      => $doc->priority,
                'owner'         => $doc->submitter?->name ?? '—',
                'updated'       => $doc->updated_at->diffForHumans(),
                'retention'     => $doc->retention_days
                    ? DocumentRetentionService::formatRetention($doc->retention_days)
                    : '—',
                'expires_at'    => $doc->expires_at?->format('M d, Y') ?? '—',
                'view_url'      => route('documents.show', $doc->id),
                'download_url'  => $doc->file_path
                    ? route('documents.file', $doc->id)
                    : null,
            ]);

        $categories = DocumentCategory::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Documents/Index', [
            'documents'  => $documents,
            'categories' => $categories,
        ]);
    }

    public function uploadForm()
    {
        $categories = DocumentCategory::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Documents/Upload', [
            'categories'       => $categories,
            'refNumber'        => $this->generateReferenceNumber(),
            'retentionOptions' => $this->retentionOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => 'nullable|exists:document_categories,id',
            'priority'       => 'required|in:Standard,Priority,Urgent',
            'description'    => 'nullable|string|max:2000',
            'retention_days' => 'required|integer|in:30,90,180,365,730,1825',
            'file'           => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('documents', 'public');
        }

        $trackingNumber = $this->generateTrackingNumber();
        $refNumber      = $this->generateReferenceNumber();

        Document::create([
            'tracking_number' => $trackingNumber,
            'reference_no'    => $refNumber,
            'title'           => $validated['title'],
            'category_id'     => $validated['category_id'] ?? null,
            'priority'        => $validated['priority'],
            'description'     => $validated['description'] ?? null,
            'file_path'       => $filePath,
            'retention_days'  => $validated['retention_days'],
            'expires_at'      => now()->addDays($validated['retention_days']),
            'status'          => 'pending',
            'submitted_by'    => Auth::id(),
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Document Uploaded',
            'description' => "Document '{$validated['title']}' submitted with tracking no. {$trackingNumber}.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('documents.index')
            ->with('success', "Document submitted for review. Tracking No: {$trackingNumber}");
    }

    public function show(Document $document)
    {
        $document->load(['category', 'submitter']);

        $previewable = $document->file_path && $this->isPreviewable($document->file_path);

        return Inertia::render('Documents/Show', [
            'document' => [
                'id'          => $document->id,
                'tracking'    => $document->tracking_number,
                'reference'   => $document->reference_no,
                'title'       => $document->title,
                'description' => $document->description ?? '—',
                'category'    => $document->category?->name ?? '—',
                'status'      => ucfirst($document->status),
                'priority'    => $document->priority,
                'owner'       => $document->submitter?->name ?? '—',
                'submitted'   => $document->created_at->format('M d, Y H:i'),
                'updated'     => $document->updated_at->format('M d, Y H:i'),
                'retention'   => $document->retention_days
                    ? DocumentRetentionService::formatRetention($document->retention_days)
                    : '—',
                'expires_at'  => $document->expires_at?->format('M d, Y') ?? '—',
                'has_file'    => (bool) $document->file_path,
                'previewable' => $previewable,
            ],
            'preview_url'  => $previewable
                ? route('documents.file', ['document' => $document->id, 'inline' => 1])
                : null,
            'download_url' => $document->file_path
                ? route('documents.file', $document->id)
                : null,
        ]);
    }

    public function file(Document $document)
    {
        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
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

    private function isPreviewable(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'], true);
    }

    private function generateTrackingNumber(): string
    {
        $year  = now()->year;
        $count = Document::whereYear('created_at', $year)->count() + 1;

        return 'DMS-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function generateReferenceNumber(): string
    {
        $year  = now()->year;
        $month = now()->format('m');
        $count = Document::whereYear('created_at', $year)
                         ->whereMonth('created_at', $month)
                         ->count() + 1;

        return 'REF-' . $year . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function retentionOptions(): array
    {
        return [
            ['value' => 30, 'label' => '30 Days'],
            ['value' => 90, 'label' => '90 Days'],
            ['value' => 180, 'label' => '6 Months'],
            ['value' => 365, 'label' => '1 Year'],
            ['value' => 730, 'label' => '2 Years'],
            ['value' => 1825, 'label' => '5 Years'],
        ];
    }
}
