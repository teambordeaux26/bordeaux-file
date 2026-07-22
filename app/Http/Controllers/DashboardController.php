<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\VisitorLog;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $year  = now()->year;
        $month = now()->month;

        $stats = [
            [
                'label' => 'Total Documents',
                'value' => Document::count(),
                'note'  => 'Across all categories and offices.',
            ],
            [
                'label' => 'Pending Review',
                'value' => Document::whereIn('status', ['pending', 'under_review'])->count(),
                'note'  => 'Awaiting admin approval.',
            ],
            [
                'label' => 'Approved This Month',
                'value' => Document::where('status', 'approved')
                    ->whereYear('approved_at', $year)
                    ->whereMonth('approved_at', $month)
                    ->count(),
                'note'  => 'Ready for archiving or release.',
            ],
            [
                'label' => 'Visitors Logged Today',
                'value' => VisitorLog::whereDate('time_in', today())->count(),
                'note'  => 'Captured in the digital log.',
            ],
        ];

        $approvals = Document::with(['category', 'submitter'])
            ->whereIn('status', ['pending', 'under_review'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($d) => [
                'id'          => $d->id,
                'tracking'    => $d->tracking_number,
                'title'       => $d->title,
                'submittedBy' => $d->submitter?->name ?? '—',
                'age'         => $d->created_at->diffForHumans(),
            ]);

        $activity = AuditLog::with('user')
            ->latest()
            ->take(8)
            ->get()
            ->map(fn($l) => [
                'summary' => $l->action,
                'detail'  => $l->description,
                'time'    => $l->created_at->diffForHumans(),
            ]);

        return Inertia::render('Dashboard', compact('stats', 'approvals', 'activity'));
    }
}
