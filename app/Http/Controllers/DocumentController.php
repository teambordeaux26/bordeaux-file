<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentStatusUpdate;
use App\Services\DocumentRetentionService;
use App\Support\DocumentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DocumentController extends Controller
{
    use ExportsSectionReports;

    public function index(Request $request, DocumentRetentionService $retentionService)
    {
        $retentionService->archiveExpired();

        $filters = $request->validate([
            'q'             => 'nullable|string|max:255',
            'category_id'   => 'nullable|integer|exists:document_categories,id',
            'status'        => 'nullable|string|max:50',
            'report_period' => 'nullable|in:weekly,monthly',
        ]);

        $query = Document::with(['category.parent', 'submitter'])
            ->where('status', '!=', 'archived');

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('tracking_number', 'like', "%{$q}%")
                    ->orWhere('reference_no', 'like', "%{$q}%");
            });
        }

        if (! empty($filters['category_id'])) {
            $query->whereIn('category_id', DocumentCategory::idsForFilter((int) $filters['category_id']));
        }

        if (! empty($filters['status'])) {
            $status = DocumentStatus::fromFilter($filters['status']);
            if ($status) {
                $query->where('status', $status);
            }
        }

        $documents = $query
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($doc) => $this->listPayload($doc));

        $counts = Document::query()
            ->where('status', '!=', 'archived')
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id')
            ->all();

        $period = $this->reportPeriod($filters['report_period'] ?? 'monthly');

        return Inertia::render('Documents/Index', [
            'documents'     => $documents,
            'categoryTree'  => DocumentCategory::treeWithCounts($counts),
            'filters'       => [
                'q'           => $filters['q'] ?? '',
                'category_id' => $filters['category_id'] ?? '',
                'status'      => $filters['status'] ?? '',
            ],
            'report'        => $this->reports()->documents($period['start'], $period['end'], $period['period']),
            'reportRange'   => $period['label'],
        ]);
    }

    public function exportReport(Request $request)
    {
        $data = $request->validate([
            'period' => 'nullable|in:weekly,monthly',
            'format' => 'nullable|in:csv,pdf',
        ]);

        $period = $this->reportPeriod($data['period'] ?? 'monthly');
        $rows   = $this->reports()->documentRows($period['start'], $period['end']);
        $report = $this->reports()->documents($period['start'], $period['end'], $period['period']);
        $format = $data['format'] ?? 'csv';
        $stamp  = $period['period'].'-'.now()->format('Ymd');

        if ($format === 'pdf') {
            return $this->downloadReportPdf('pdf.reports.documents', "document-movement-{$stamp}.pdf", [
                'title'       => 'Document Movement Overview',
                'office'      => 'Office of the Vice Mayor — Oas, Albay',
                'range'       => $period['label'],
                'generatedAt' => now()->format('F j, Y g:i A'),
                'report'      => $report,
                'rows'        => $rows,
            ]);
        }

        return $this->streamCsv(
            "document-movement-{$stamp}.csv",
            ['Date', 'Movement', 'Tracking No.', 'Title', 'Category', 'Status', 'Submitted By'],
            $rows
        );
    }

    public function uploadForm()
    {
        return Inertia::render('Documents/Upload', [
            'categories'       => DocumentCategory::treeForForms(),
            'refNumber'        => $this->generateReferenceNumber(),
            'retentionOptions' => $this->retentionOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => 'required|exists:document_categories,id',
            'priority'       => 'required|in:Standard,Priority,Urgent',
            'description'    => 'nullable|string|max:2000',
            'retention_days' => 'required|integer|in:7,30,90,180,365,730,1825',
            'file'           => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
        ]);

        $this->assertLeafCategory((int) $validated['category_id']);

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
            'category_id'     => $validated['category_id'],
            'priority'        => $validated['priority'],
            'description'     => $validated['description'] ?? null,
            'file_path'       => $filePath,
            'retention_days'  => (int) $validated['retention_days'],
            'expires_at'      => now()->addDays((int) $validated['retention_days']),
            'status'          => 'pending',
            'submitted_by'    => Auth::id(),
            'submitted_at'    => now(),
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
        $document->load(['category.parent', 'submitter']);

        $previewable = $document->file_path && $this->isPreviewable($document->file_path);

        return Inertia::render('Documents/Show', [
            'document' => [
                'id'          => $document->id,
                'tracking'    => $document->tracking_number,
                'reference'   => $document->reference_no,
                'title'       => $document->title,
                'description' => $document->description ?? '—',
                'category'    => $document->category?->label() ?? '—',
                'status'      => DocumentStatus::label($document->status),
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

    /**
     * List documents that were returned to the current user for revision.
     * Admins see every returned document.
     */
    public function returnedIndex()
    {
        $user  = Auth::user();
        $query = Document::with(['category.parent', 'submitter'])
            ->where('status', 'returned')
            ->latest('returned_at');

        if ($user->role !== 'admin') {
            $query->where('submitted_by', $user->id);
        }

        $documents = $query->get()->map(function (Document $doc) {
            $context = $this->returnContext($doc);

            return [
                'id'            => $doc->id,
                'tracking'      => $doc->tracking_number,
                'reference'     => $doc->reference_no,
                'title'         => $doc->title,
                'category'      => $doc->category?->label() ?? '—',
                'priority'      => $doc->priority,
                'owner'         => $doc->submitter?->name ?? '—',
                'returned_at'   => $doc->returned_at?->diffForHumans(),
                'returned_from' => $context['from_label'],
                'returned_by'   => $context['by'],
                'remarks'       => $context['remarks'],
                'edit_url'      => route('documents.edit', $doc->id),
                'view_url'      => route('documents.show', $doc->id),
            ];
        });

        return Inertia::render('Documents/Returned', [
            'documents' => $documents,
        ]);
    }

    /**
     * Show the edit form for a returned document.
     * Only the owner (or admin) may open this — and only while status = returned.
     */
    public function edit(Document $document)
    {
        $this->authorizeEdit($document);

        $document->load('category.parent');
        $context = $this->returnContext($document);

        return Inertia::render('Documents/Edit', [
            'document' => [
                'id'             => $document->id,
                'tracking'       => $document->tracking_number,
                'reference'      => $document->reference_no,
                'title'          => $document->title,
                'description'    => $document->description,
                'category_id'    => $document->category_id,
                'priority'       => $document->priority,
                'retention_days' => (int) $document->retention_days,
                'file_name'      => $document->file_path ? basename($document->file_path) : null,
                'view_url'       => route('documents.show', $document->id),
                'file_url'       => $document->file_path
                    ? route('documents.file', ['document' => $document->id, 'inline' => 1])
                    : null,
            ],
            'returnContext'    => $context,
            'categories'       => DocumentCategory::treeForForms(),
            'retentionOptions' => $this->retentionOptions(),
        ]);
    }

    /**
     * Save the revisions and (optionally) resubmit the document for review.
     */
    public function update(Request $request, Document $document)
    {
        $this->authorizeEdit($document);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => 'required|exists:document_categories,id',
            'priority'       => 'required|in:Standard,Priority,Urgent',
            'description'    => 'nullable|string|max:2000',
            'retention_days' => 'required|integer|in:7,30,90,180,365,730,1825',
            'file'           => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'resubmit'       => 'sometimes|boolean',
        ]);

        $this->assertLeafCategory((int) $validated['category_id']);

        $updates = [
            'title'          => $validated['title'],
            'category_id'    => $validated['category_id'],
            'priority'       => $validated['priority'],
            'description'    => $validated['description'] ?? null,
            'retention_days' => (int) $validated['retention_days'],
            'expires_at'     => now()->addDays((int) $validated['retention_days']),
        ];

        if ($request->hasFile('file')) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            $updates['file_path'] = $request->file('file')->store('documents', 'public');
        }

        $resubmit = (bool) ($validated['resubmit'] ?? false);

        if ($resubmit) {
            $updates['status']       = 'pending';
            $updates['submitted_at'] = now();
            $updates['returned_at']  = null;
        }

        $document->update($updates);

        DocumentStatusUpdate::create([
            'document_id' => $document->id,
            'status'      => $resubmit ? 'pending' : 'returned',
            'remarks'     => $resubmit
                ? 'Revised and resubmitted by owner.'
                : 'Owner saved revisions (still in Returned).',
            'updated_by'  => Auth::id(),
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $resubmit ? 'Document Resubmitted' : 'Document Revised',
            'description' => "Document {$document->tracking_number} "
                             . ($resubmit ? 'revised and resubmitted for review.' : 'revised (kept in Returned).'),
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('documents.returned')->with(
            'success',
            $resubmit
                ? "Document {$document->tracking_number} resubmitted for review."
                : "Revisions to {$document->tracking_number} saved."
        );
    }

    /**
     * Enforce edit permissions.
     */
    protected function authorizeEdit(Document $document): void
    {
        $user = Auth::user();

        if ($document->status !== 'returned') {
            abort(403, 'Only returned documents can be edited here.');
        }

        if ($user->role !== 'admin' && $document->submitted_by !== $user->id) {
            abort(403, 'You can only edit documents you submitted.');
        }
    }

    /**
     * Look up the return remarks and where the document was returned from.
     *
     * @return array{from:?string, from_label:string, by:?string, remarks:?string}
     */
    protected function returnContext(Document $document): array
    {
        $lastReturn = $document->statusUpdates()
            ->with('updater')
            ->where('status', 'returned')
            ->latest('id')
            ->first();

        $before = null;
        if ($lastReturn) {
            $before = $document->statusUpdates()
                ->where('id', '<', $lastReturn->id)
                ->latest('id')
                ->first();
        }

        $fromLabels = [
            'under_review' => 'Under Review (returned by reviewer)',
            'for_approval' => 'For Approval (returned by admin)',
        ];

        $fromStatus = $before?->status;

        return [
            'from'       => $fromStatus,
            'from_label' => $fromLabels[$fromStatus] ?? 'Review process',
            'by'         => $lastReturn?->updater?->name,
            'remarks'    => $lastReturn?->remarks,
        ];
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

    private function listPayload(Document $doc): array
    {
        return [
            'id'            => $doc->id,
            'tracking'      => $doc->tracking_number,
            'reference'     => $doc->reference_no,
            'title'         => $doc->title,
            'summary'       => $doc->description
                ? Str::limit($doc->description, 60)
                : '—',
            'category'      => $doc->category?->label() ?? '—',
            'category_id'   => $doc->category_id,
            'status'        => DocumentStatus::label($doc->status),
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
        ];
    }

    private function assertLeafCategory(int $categoryId): void
    {
        $category = DocumentCategory::query()->with('children')->find($categoryId);

        if ($category && $category->children->isNotEmpty()) {
            throw ValidationException::withMessages([
                'category_id' => 'Please select a sub-category for '.$category->name.'.',
            ]);
        }
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
            ['value' => 7, 'label' => '7 Days'],
            ['value' => 30, 'label' => '30 Days'],
            ['value' => 90, 'label' => '90 Days'],
            ['value' => 180, 'label' => '6 Months'],
            ['value' => 365, 'label' => '1 Year'],
            ['value' => 730, 'label' => '2 Years'],
            ['value' => 1825, 'label' => '5 Years'],
        ];
    }
}
