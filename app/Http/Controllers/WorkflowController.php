<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentStatusUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    /**
     * Ordered stages of the workflow.
     *
     * Each stage:
     *  - label:   human-friendly name
     *  - roles:   which roles can move a document OUT of this stage
     *  - actions: allowed transitions with rules & next status
     */
    public static function stages(): array
    {
        return [
            'draft' => [
                'label'   => 'Draft',
                'kicker'  => 'Owned by submitter',
                'color'   => 'slate',
            ],
            'pending' => [
                'label'   => 'Submitted',
                'kicker'  => 'Awaiting a reviewer',
                'color'   => 'amber',
            ],
            'under_review' => [
                'label'   => 'Under Review',
                'kicker'  => 'Reviewer verifying',
                'color'   => 'blue',
            ],
            'for_approval' => [
                'label'   => 'For Approval',
                'kicker'  => 'Awaiting admin decision',
                'color'   => 'indigo',
            ],
            'approved' => [
                'label'   => 'Approved',
                'kicker'  => 'Ready for release',
                'color'   => 'emerald',
            ],
            'released' => [
                'label'   => 'Released',
                'kicker'  => 'Available to the public',
                'color'   => 'green',
            ],
            'returned' => [
                'label'   => 'Returned',
                'kicker'  => 'Sent back to owner',
                'color'   => 'orange',
            ],
            'rejected' => [
                'label'   => 'Disapproved',
                'kicker'  => 'Closed',
                'color'   => 'rose',
            ],
        ];
    }

    public function index()
    {
        $user   = Auth::user();
        $stages = static::stages();

        $documents = Document::with(['category.parent', 'submitter', 'reviewer'])
            ->whereIn('status', array_keys($stages))
            ->orderBy('updated_at', 'desc')
            ->get();

        $grouped = [];
        foreach (array_keys($stages) as $stage) {
            $grouped[$stage] = [];
        }

        $owners     = [];
        $categories = [];

        foreach ($documents as $d) {
            $stage = $d->status;
            if (! isset($grouped[$stage])) {
                continue;
            }

            if ($d->submitter && ! isset($owners[$d->submitter->id])) {
                $owners[$d->submitter->id] = [
                    'id'   => $d->submitter->id,
                    'name' => $d->submitter->name,
                ];
            }

            if ($d->category && ! isset($categories[$d->category->id])) {
                $categories[$d->category->id] = [
                    'id'   => $d->category->id,
                    'name' => $d->category->label(),
                ];
            }

            $grouped[$stage][] = [
                'id'           => $d->id,
                'tracking'     => $d->tracking_number,
                'reference'    => $d->reference_no,
                'title'        => $d->title,
                'category'     => $d->category?->label() ?? '—',
                'category_id'  => $d->category_id,
                'category_parent_id' => $d->category?->parent_id,
                'owner'        => $d->submitter?->name ?? '—',
                'owner_id'     => $d->submitted_by,
                'reviewer'     => $d->reviewer?->name,
                'reviewer_id'  => $d->reviewed_by,
                'priority'     => $d->priority,
                'updated'      => optional($d->updated_at)->diffForHumans(),
                'updated_ts'   => optional($d->updated_at)->timestamp,
                'view_url'     => route('documents.show', $d->id),
                'has_file'     => (bool) $d->file_path,
                'file_url'     => $d->file_path
                    ? route('documents.file', ['document' => $d->id, 'inline' => 1])
                    : null,
                'download_url' => $d->file_path
                    ? route('documents.file', $d->id)
                    : null,
                'edit_url'     => $d->status === 'returned'
                        && ($d->submitted_by === $user->id || $user->role === 'admin')
                    ? route('documents.edit', $d->id)
                    : null,
                'actions'      => static::actionsFor($d, $user),
            ];
        }

        $stageMeta = [];
        foreach ($stages as $key => $meta) {
            $stageMeta[] = [
                'key'    => $key,
                'label'  => $meta['label'],
                'kicker' => $meta['kicker'],
                'color'  => $meta['color'],
                'count'  => count($grouped[$key] ?? []),
            ];
        }

        return Inertia::render('Workflow/Index', [
            'stages'     => $stageMeta,
            'documents'  => $grouped,
            'legend'     => static::legend(),
            'currentUser'=> [
                'id'   => $user->id,
                'role' => $user->role,
                'name' => $user->name,
            ],
            'filters'    => [
                'owners'     => array_values($owners),
                'categories' => array_values($categories),
                'priorities' => ['Urgent', 'Priority', 'Standard'],
            ],
        ]);
    }

    /**
     * Available actions for the given document + user pair.
     * Returns [ ['key' => 'submit', 'label' => 'Submit for Review'], ... ]
     */
    protected static function actionsFor(Document $d, $user): array
    {
        $actions = [];
        $isOwner = $d->submitted_by === $user->id;
        $isAdmin = $user->role === 'admin';

        switch ($d->status) {
            case 'draft':
                if ($isOwner) {
                    $actions[] = ['key' => 'submit', 'label' => 'Submit for Review', 'variant' => 'primary'];
                }
                break;

            case 'pending':
                if (! $isOwner) {
                    $actions[] = ['key' => 'start_review', 'label' => 'Start Review', 'variant' => 'primary'];
                }
                break;

            case 'under_review':
                if ($d->reviewed_by === $user->id || $isAdmin) {
                    $actions[] = ['key' => 'forward', 'label' => 'Forward to Approval', 'variant' => 'primary'];
                    $actions[] = ['key' => 'return', 'label' => 'Return to Owner', 'variant' => 'warn', 'needs_remarks' => true];
                }
                break;

            case 'for_approval':
                if ($isAdmin) {
                    $actions[] = ['key' => 'approve', 'label' => 'Approve', 'variant' => 'primary'];
                    $actions[] = ['key' => 'return', 'label' => 'Return', 'variant' => 'warn', 'needs_remarks' => true];
                    $actions[] = ['key' => 'reject', 'label' => 'Disapprove', 'variant' => 'danger', 'needs_remarks' => true];
                }
                break;

            case 'approved':
                if ($isAdmin) {
                    $actions[] = ['key' => 'release', 'label' => 'Release', 'variant' => 'primary'];
                }
                break;

            case 'returned':
                if ($isOwner) {
                    $actions[] = ['key' => 'resubmit', 'label' => 'Resubmit', 'variant' => 'primary'];
                }
                break;
        }

        return $actions;
    }

    public function advance(Request $request, Document $document)
    {
        $data = $request->validate([
            'action'  => 'required|string',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $user     = Auth::user();
        $action   = $data['action'];
        $remarks  = $data['remarks'] ?? null;

        $allowed = collect(static::actionsFor($document, $user))->pluck('key')->all();
        if (! in_array($action, $allowed, true)) {
            return back()->with('error', 'You are not allowed to perform this action.');
        }

        $before = $document->status;

        [$newStatus, $updates, $auditMsg] = match ($action) {
            'submit', 'resubmit' => [
                'pending',
                ['submitted_at' => now(), 'returned_at' => null],
                "submitted for review",
            ],
            'start_review' => [
                'under_review',
                ['reviewed_by' => $user->id],
                "picked up for review",
            ],
            'forward' => [
                'for_approval',
                [],
                "forwarded for approval",
            ],
            'approve' => [
                'approved',
                ['approved_at' => now()],
                "approved",
            ],
            'release' => [
                'released',
                ['released_at' => now()],
                "released",
            ],
            'return' => [
                'returned',
                ['returned_at' => now()],
                "returned to owner",
            ],
            'reject' => [
                'rejected',
                [],
                "disapproved",
            ],
            default => [null, [], null],
        };

        if (! $newStatus) {
            return back()->with('error', 'Unknown workflow action.');
        }

        $document->update(array_merge($updates, ['status' => $newStatus]));

        DocumentStatusUpdate::create([
            'document_id' => $document->id,
            'status'      => $newStatus,
            'remarks'     => $remarks,
            'updated_by'  => $user->id,
        ]);

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'Workflow: '.$auditMsg,
            'description' => "Document {$document->tracking_number} moved {$before} → {$newStatus}"
                             .($remarks ? " with remarks: {$remarks}" : ''),
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', "Document {$document->tracking_number} {$auditMsg}.");
    }

    protected static function legend(): array
    {
        return [
            [
                'role'  => 'Owner (submitter)',
                'does'  => 'Creates the draft, submits it, and resubmits when returned.',
            ],
            [
                'role'  => 'Reviewer (any employee who is not the owner)',
                'does'  => 'Picks up a submission, verifies it, then forwards to admin or returns to the owner.',
            ],
            [
                'role'  => 'Admin',
                'does'  => 'Approves and releases documents, or returns/disapproves them.',
            ],
        ];
    }
}
