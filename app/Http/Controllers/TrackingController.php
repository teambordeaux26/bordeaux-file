<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Inertia\Inertia;

class TrackingController extends Controller
{
    public function index()
    {
        $trackingItems = Document::with(['category', 'submitter'])
            ->whereNotIn('status', ['archived'])
            ->latest()
            ->get()
            ->map(fn($d) => [
                'id'       => $d->id,
                'tracking' => $d->tracking_number,
                'title'    => $d->title,
                'status'   => ucfirst(str_replace('_', ' ', $d->status)),
                'owner'    => $d->submitter?->name ?? '—',
                'updated'  => $d->updated_at->diffForHumans(),
            ]);

        return Inertia::render('Tracking/Index', compact('trackingItems'));
    }
}
