<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ApprovalsController extends Controller
{
    public function index()
    {
        $documents = Document::with(['category', 'submitter'])
            ->whereIn('status', ['pending', 'under_review'])
            ->latest()
            ->get()
            ->map(fn($d) => [
                'id'       => $d->id,
                'tracking' => $d->tracking_number,
                'title'    => $d->title,
                'category' => $d->category?->name ?? '—',
                'owner'    => $d->submitter?->name ?? '—',
                'priority' => $d->priority,
                'age'      => $d->created_at->diffForHumans(),
            ]);

        return Inertia::render('Approvals/Index', compact('documents'));
    }

    public function approve(Document $document)
    {
        $document->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Document Approved',
            'description' => "Document {$document->tracking_number} approved.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect()->route('approvals.index')
            ->with('success', "Document {$document->tracking_number} approved successfully.");
    }

    public function returnDoc(Request $request, Document $document)
    {
        $document->update([
            'status'      => 'returned',
            'returned_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Document Returned',
            'description' => "Document {$document->tracking_number} returned for revision.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect()->route('approvals.index')
            ->with('success', "Document {$document->tracking_number} returned for revision.");
    }

    public function reject(Document $document)
    {
        $document->update([
            'status'      => 'rejected',
            'reviewed_by' => Auth::id(),
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Document Rejected',
            'description' => "Document {$document->tracking_number} rejected.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect()->route('approvals.index')
            ->with('error', "Document {$document->tracking_number} has been rejected.");
    }
}
