<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Services\DocumentRetentionService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ArchiveController extends Controller
{
    public function index(DocumentRetentionService $retentionService)
    {
        $retentionService->archiveExpired();

        $archives = Document::with(['category.parent', 'submitter', 'handler'])
            ->visibleTo(Auth::user())
            ->where('status', 'archived')
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

        return Inertia::render('Archive/Index', compact('archives'));
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

        return redirect()->route('archive.index')
            ->with('success', "Document {$document->tracking_number} restored successfully.");
    }
}
