<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentStatusUpdate;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\DocumentRetentionService;
use App\Support\DocumentStatus;
use App\Support\OfficeTime;
use App\Support\ReportPeriod;
use App\Support\TextSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            ...ReportPeriod::requestRules(),
        ]);

        $query = Document::with(['category.parent', 'submitter', 'handler'])
            ->visibleTo(Auth::user())
            ->where('status', '!=', 'archived');

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                TextSearch::contains($builder, 'title', $q);
                TextSearch::contains($builder, 'tracking_number', $q, true);
                TextSearch::contains($builder, 'reference_no', $q, true);
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
            ->visibleTo(Auth::user())
            ->where('status', '!=', 'archived')
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id')
            ->all();

        $period = $this->reportPeriodFrom($request);
        $categoryIds = $this->reportCategoryIds($filters['category_id'] ?? null);
        $report = $period['ready']
            ? $this->reports()->documents($period['start'], $period['end'], $period['period'], $categoryIds, Auth::user())
            : $this->emptyReport();

        return Inertia::render('Documents/Index', [
            'documents'     => $documents,
            'categoryTree'  => DocumentCategory::treeWithCounts($counts),
            'filters'       => [
                'q'           => $filters['q'] ?? '',
                'category_id' => $filters['category_id'] ?? '',
                'status'      => $filters['status'] ?? '',
            ],
            'report'        => $report,
            'reportRange'   => $period['label'],
        ]);
    }

    public function exportReport(Request $request)
    {
        $data = $request->validate([
            ...ReportPeriod::exportRules(),
            'category_id' => 'nullable|integer|exists:document_categories,id',
        ]);

        $period = $this->reportPeriodFrom($request);
        $categoryIds = $this->reportCategoryIds($data['category_id'] ?? null);
        $rows   = $this->reports()->documentRows($period['start'], $period['end'], $categoryIds, Auth::user());
        $report = $this->reports()->documents($period['start'], $period['end'], $period['period'], $categoryIds, Auth::user());
        $format = $data['format'] ?? 'csv';
        $stamp  = $period['from'].'-to-'.$period['to'];

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

    public function uploadForm(Request $request)
    {
        $selected = $request->integer('category_id');

        return Inertia::render('Documents/Upload', [
            'categories'       => DocumentCategory::treeForForms(),
            'refNumber'        => $this->generateReferenceNumber(),
            'retentionOptions' => DocumentRetentionService::options(),
            'staff'            => User::staffOptions(Auth::id()),
            'accessRoles'      => User::documentAccessRoles(),
            'currentUserId'    => Auth::id(),
            'selectedCategoryId' => $selected > 0 ? $selected : null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => 'required|exists:document_categories,id',
            'priority'       => 'required|in:Standard,Priority,Urgent',
            'description'    => 'nullable|string|max:2000',
            'retention_days' => ['required', 'integer', Rule::in(DocumentRetentionService::allowedDays())],
            'file'           => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'access_roles'      => 'nullable|array',
            'access_roles.*'    => 'in:'.implode(',', array_keys(User::documentAccessRoles())),
            'handled_by'        => ['nullable', 'integer', $this->staffUserRule()],
        ]);

        $this->assertLeafCategory((int) $validated['category_id']);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('documents', 'public');
        }

        $trackingNumber = $this->generateTrackingNumber();
        $refNumber      = $this->generateReferenceNumber();

        $document = Document::create([
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
            'handled_by'      => $validated['handled_by'] ?? null,
            'submitted_at'    => now(),
        ]);

        $document->syncAccessRoles($validated['access_roles'] ?? []);
        $document->load('handler');

        DocumentStatusUpdate::create([
            'document_id' => $document->id,
            'status'      => 'pending',
            'remarks'     => $document->handler
                ? 'Submitted and assigned to '.$document->handler->name.'.'
                : 'Submitted for review.',
            'updated_by'  => Auth::id(),
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
        $this->authorizeDocumentAccess($document);

        $document->load([
            'category.parent',
            'submitter',
            'handler',
            'versions.uploader',
            'statusUpdates' => fn ($query) => $query->with('updater')->orderBy('id'),
        ]);

        $previewable = $document->file_path && $this->isPreviewable($document->file_path);

        return Inertia::render('Documents/Show', [
            'document' => [
                'id'          => $document->id,
                'tracking'    => $document->tracking_number,
                'reference'   => $document->reference_no,
                'title'       => $document->title,
                'description' => $document->description ?? '—',
                'owner_remarks' => $document->owner_remarks,
                'category'    => $document->category?->label() ?? '—',
                'status'      => DocumentStatus::label($document->status),
                'priority'    => $document->priority,
                'owner'       => $document->submitter?->name ?? '—',
                'owner_id'    => $document->submitted_by,
                'handler'     => $document->handler ? [
                    'id'       => $document->handler->id,
                    'name'     => $document->handler->name,
                    'position' => $document->handler->position ?: 'Staff',
                ] : null,
                'access'      => [
                    'restricted'  => $document->isRestricted(),
                    'roles'       => $document->accessRoleList(),
                    'role_labels' => collect($document->accessRoleList())
                        ->map(fn (string $role) => User::documentAccessRoles()[$role] ?? ucfirst($role))
                        ->values()
                        ->all(),
                    'can_edit'    => Auth::user()->role === 'admin' || $document->submitted_by === Auth::id(),
                ],
                'trail'       => $document->statusUpdates->map(fn (DocumentStatusUpdate $update) => [
                    'id'      => $update->id,
                    'status'  => DocumentStatus::label($update->status),
                    'remarks' => $update->remarks,
                    'by'      => $update->updater?->name ?? '—',
                    'at'      => $update->created_at?->format('M d, Y g:i A'),
                ])->values()->all(),
                'submitted'   => $document->created_at->format('M d, Y H:i'),
                'updated'     => $document->updated_at->format('M d, Y H:i'),
                'retention'   => $document->retention_days
                    ? DocumentRetentionService::formatRetention($document->retention_days)
                    : '—',
                'expires_at'  => $document->expires_at?->format('M d, Y') ?? '—',
                'has_file'    => (bool) $document->file_path,
                'previewable' => $previewable,
                'versions'    => $document->versionPayload(),
            ],
            'preview_url'  => $previewable
                ? route('documents.file', ['document' => $document->id, 'inline' => 1])
                : null,
            'download_url' => $document->file_path
                ? route('documents.file', $document->id)
                : null,
            'staff'        => User::staffOptions(),
            'accessRoles'  => User::documentAccessRoles(),
        ]);
    }

    public function updateSharing(Request $request, Document $document)
    {
        $this->authorizeDocumentAccess($document);

        $user = Auth::user();
        if ($user->role !== 'admin' && $document->submitted_by !== $user->id) {
            abort(403, 'Only the owner or an admin can change document access.');
        }

        $validated = $request->validate([
            'access_roles'   => 'nullable|array',
            'access_roles.*' => 'in:'.implode(',', array_keys(User::documentAccessRoles())),
            'handled_by'     => ['nullable', 'integer', $this->staffUserRule()],
        ]);

        $document->update([
            'handled_by' => $validated['handled_by'] ?? null,
        ]);
        $document->syncAccessRoles($validated['access_roles'] ?? []);

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'Document Access Updated',
            'description' => "Updated access and handler for {$document->tracking_number}.",
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', "Access and handler for {$document->tracking_number} saved.");
    }

    /**
     * List documents that were returned to the current user for revision.
     * Admins see every returned document.
     */
    public function returnedIndex()
    {
        $user  = Auth::user();
        $query = Document::with(['category.parent', 'submitter', 'versions.uploader'])
            ->where('status', 'returned')
            ->latest('returned_at');

        if ($user->role !== 'admin') {
            $query->where('submitted_by', $user->id);
        }

        $documents = $query->paginate(10)->withQueryString()->through(function (Document $doc) {
            $context = $this->returnContext($doc);

            return [
                'id'             => $doc->id,
                'tracking'       => $doc->tracking_number,
                'reference'      => $doc->reference_no,
                'title'          => $doc->title,
                'description'    => $doc->description,
                'category'       => $doc->category?->label() ?? '—',
                'category_id'    => $doc->category_id,
                'priority'       => $doc->priority,
                'retention_days' => (int) $doc->retention_days,
                'owner'          => $doc->submitter?->name ?? '—',
                'returned_at'    => OfficeTime::exact($doc->returned_at),
                'returned_from'  => $context['from_label'],
                'returned_by'    => $context['by'],
                'remarks'        => $context['remarks'],
                'file_name'      => $doc->file_path ? basename($doc->file_path) : null,
                'file_url'       => $doc->file_path
                    ? route('documents.file', ['document' => $doc->id, 'inline' => 1])
                    : null,
                'versions'       => $doc->versionPayload(),
                'view_url'       => route('documents.show', $doc->id),
            ];
        });

        return Inertia::render('Documents/Returned', [
            'documents'        => $documents,
            'categories'       => DocumentCategory::treeForForms(),
            'retentionOptions' => DocumentRetentionService::options(),
            'editingId'        => request()->integer('edit') ?: null,
        ]);
    }

    /**
     * Show the edit form for a returned document.
     * Only the owner (or admin) may open this — and only while status = returned.
     */
    public function edit(Document $document)
    {
        $this->authorizeEdit($document);
        $this->authorizeDocumentAccess($document);

        return redirect()->route('documents.returned', ['edit' => $document->id]);
    }

    /**
     * Save the revisions and (optionally) resubmit the document for review.
     */
    public function update(Request $request, Document $document)
    {
        $this->authorizeEdit($document);

        $resubmit = $request->boolean('resubmit');

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => 'required|exists:document_categories,id',
            'priority'       => 'required|in:Standard,Priority,Urgent',
            'description'    => 'nullable|string|max:2000',
            'owner_remarks'  => [$resubmit ? 'required' : 'nullable', 'string', 'max:2000'],
            'retention_days' => ['required', 'integer', Rule::in(DocumentRetentionService::allowedDays((int) $document->retention_days))],
            'file'           => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'resubmit'       => 'sometimes|boolean',
        ], [
            'owner_remarks.required' => 'Please add remarks for the reviewer.',
        ]);

        $this->assertLeafCategory((int) $validated['category_id']);

        $updates = [
            'title'          => $validated['title'],
            'category_id'    => $validated['category_id'],
            'priority'       => $validated['priority'],
            'description'    => $validated['description'] ?? null,
            'owner_remarks'  => trim((string) ($validated['owner_remarks'] ?? '')) ?: null,
            'retention_days' => (int) $validated['retention_days'],
            'expires_at'     => now()->addDays((int) $validated['retention_days']),
        ];

        $replacedFile = false;

        if ($request->hasFile('file')) {
            $stored = $request->file('file')->store('documents', 'public');
            $document->keepFileAsRevision(
                $stored,
                $request->file('file')->getClientOriginalName(),
                Auth::id(),
            );
            $updates['file_path'] = $stored;
            $replacedFile = true;
        }

        if ($resubmit) {
            $updates['status']       = 'pending';
            $updates['submitted_at'] = now();
            $updates['returned_at']  = null;
            $updates['handled_by']   = null;
        }

        $document->update($updates);

        $ownerRemarks = trim((string) ($validated['owner_remarks'] ?? ''));
        $systemNote = $replacedFile
            ? 'Previous file kept as the old version. The new file is the revised version.'
            : ($resubmit
                ? 'Revised and resubmitted by owner.'
                : 'Owner saved revisions (still in Returned).');

        DocumentStatusUpdate::create([
            'document_id' => $document->id,
            'status'      => $resubmit ? 'pending' : 'returned',
            'remarks'     => $ownerRemarks !== ''
                ? "Remarks from owner:\n{$ownerRemarks}\n\n{$systemNote}"
                : $systemNote,
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
        $this->authorizeDocumentAccess($document);

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

    public function versionFile(Document $document, DocumentVersion $version)
    {
        $this->authorizeDocumentAccess($document);
        abort_unless($version->document_id === $document->id, 404);

        if (! Storage::disk('public')->exists($version->file_path)) {
            abort(404);
        }

        $filename = str_replace(['"', '\\'], '', $version->displayName());

        if (request()->boolean('inline')) {
            return Storage::disk('public')->response(
                $version->file_path,
                $filename,
                ['Content-Disposition' => 'inline; filename="' . $filename . '"']
            );
        }

        return Storage::disk('public')->download($version->file_path, $filename);
    }

    protected function authorizeDocumentAccess(Document $document): void
    {
        abort_unless($document->canBeAccessedBy(Auth::user()), 403, 'You do not have access to this document.');
    }

    private function staffUserRule(): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('users', 'id')->where(function ($query) {
            $query->whereIn('role', ['admin', 'employee'])->where('status', 'active');
        });
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
            'handler'       => $doc->handler?->name ?? 'Unassigned',
            'restricted'    => $doc->isRestricted(),
            'updated'       => OfficeTime::exact($doc->updated_at),
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

    /**
     * @return list<int>|null
     */
    private function reportCategoryIds(mixed $categoryId): ?array
    {
        $id = (int) $categoryId;

        return $id > 0 ? DocumentCategory::idsForFilter($id) : null;
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
}
