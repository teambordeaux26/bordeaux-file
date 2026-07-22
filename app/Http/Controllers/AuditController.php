<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with('user')
            ->latest()
            ->take(200)
            ->get()
            ->map(fn($l) => [
                'id'     => $l->id,
                'action' => $l->action,
                'detail' => $l->description,
                'user'   => $l->user?->name ?? 'System',
                'time'   => $l->created_at->diffForHumans(),
            ]);

        return Inertia::render('Audit/Index', compact('logs'));
    }
}
