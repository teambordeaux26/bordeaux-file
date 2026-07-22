<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VisitorController extends Controller
{
    public function index()
    {
        $visitors = VisitorLog::with('recorder')
            ->whereDate('time_in', today())
            ->latest('time_in')
            ->get()
            ->map(fn($v) => [
                'id'      => $v->id,
                'name'    => $v->visitor_name,
                'contact' => $v->visitor_phone ?? '—',
                'address' => $v->address ?? '—',
                'purpose' => $v->purpose,
                'timeIn'  => $v->time_in->format('H:i'),
                'timeOut' => $v->time_out?->format('H:i'),
            ]);

        return Inertia::render('Visitors/Index', compact('visitors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_name'  => 'required|string|max:255',
            'visitor_phone' => 'nullable|string|max:30',
            'address'       => 'nullable|string|max:500',
            'purpose'       => 'required|string|max:255',
        ]);

        VisitorLog::create([
            ...$validated,
            'time_in'     => now(),
            'recorded_by' => Auth::id(),
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Visitor Logged',
            'description' => "Visitor '{$validated['visitor_name']}' recorded for: {$validated['purpose']}",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('visitors.index')
            ->with('success', "Visitor '{$validated['visitor_name']}' logged successfully.");
    }
}
