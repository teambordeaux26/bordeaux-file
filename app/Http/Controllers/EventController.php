<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\OfficeEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'type'        => ['required', Rule::in(['meeting', 'deadline', 'other'])],
            'starts_at'   => 'required|date',
            'ends_at'     => 'nullable|date|after_or_equal:starts_at',
            'description' => 'nullable|string|max:2000',
        ]);

        $event = OfficeEvent::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Calendar Event Created',
            'description' => "Scheduled {$event->typeLabel()} “{$event->title}” on {$event->starts_at->format('M d, Y g:i A')}.",
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', 'Event added to the calendar.');
    }

    public function destroy(OfficeEvent $event)
    {
        $user = Auth::user();

        if ($user->role !== 'admin' && $event->created_by !== $user->id) {
            abort(403, 'You can only remove events you created.');
        }

        $title = $event->title;
        $event->delete();

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Calendar Event Removed',
            'description' => "Removed calendar event “{$title}”.",
            'ip_address'  => request()->ip(),
        ]);

        return back()->with('success', 'Event removed from the calendar.');
    }
}
