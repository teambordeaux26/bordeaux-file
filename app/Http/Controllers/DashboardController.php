<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\OfficeEvent;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year  = now()->year;
        $month = now()->month;

        $stats = [
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

        $approvals = Document::with(['category.parent', 'submitter'])
            ->whereIn('status', ['pending', 'under_review'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($d) => [
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
            ->map(fn ($l) => [
                'summary' => $l->action,
                'detail'  => $l->description,
                'time'    => $l->created_at->diffForHumans(),
            ]);

        $cursor = $this->calendarCursor($request->query('calendar'));

        return Inertia::render('Dashboard', [
            'stats'      => $stats,
            'approvals'  => $approvals,
            'activity'   => $activity,
            'calendar'   => $this->calendarPayload($cursor),
            'reminders'  => $this->reminders(),
        ]);
    }

    protected function calendarCursor(?string $value): Carbon
    {
        if ($value && preg_match('/^\d{4}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value.'-01')->startOfMonth();
        }

        return now()->startOfMonth();
    }

    protected function calendarPayload(Carbon $cursor): array
    {
        $start = $cursor->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end   = $cursor->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $events = OfficeEvent::query()
            ->whereBetween('starts_at', [$start, $end])
            ->orderBy('starts_at')
            ->get();

        $eventsByDay = $events->groupBy(fn (OfficeEvent $event) => $event->starts_at->toDateString());

        $days = [];
        $day  = $start->copy();
        while ($day->lte($end)) {
            $key = $day->toDateString();
            $days[] = [
                'date'    => $key,
                'day'     => $day->day,
                'inMonth' => $day->month === $cursor->month,
                'isToday' => $day->isToday(),
                'events'  => ($eventsByDay[$key] ?? collect())->map(fn (OfficeEvent $event) => $this->eventPayload($event))->values()->all(),
            ];
            $day->addDay();
        }

        return [
            'month'     => $cursor->format('Y-m'),
            'label'     => $cursor->format('F Y'),
            'prev'      => $cursor->copy()->subMonth()->format('Y-m'),
            'next'      => $cursor->copy()->addMonth()->format('Y-m'),
            'weekdays'  => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'days'      => $days,
        ];
    }

    protected function reminders(): array
    {
        $from = now()->startOfDay();
        $to   = now()->addDays(14)->endOfDay();

        $events = OfficeEvent::query()
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (OfficeEvent $event) => [
                ...$this->eventPayload($event),
                'source'  => 'event',
                'sort_at' => $event->starts_at->timestamp,
            ]);

        $deadlines = Document::with('category.parent')
            ->whereNotNull('expires_at')
            ->where('status', '!=', 'archived')
            ->whereBetween('expires_at', [$from, $to])
            ->orderBy('expires_at')
            ->get()
            ->map(fn (Document $doc) => [
                'id'         => 'doc-'.$doc->id,
                'title'      => $doc->title,
                'type'       => 'deadline',
                'typeLabel'  => 'Document deadline',
                'when'       => $doc->expires_at->format('M d, Y'),
                'time'       => null,
                'detail'     => $doc->tracking_number.' · '.($doc->category?->label() ?? 'Uncategorized'),
                'source'     => 'document',
                'url'        => route('documents.show', $doc->id),
                'can_delete' => false,
                'sort_at'    => $doc->expires_at->timestamp,
            ]);

        return $events->concat($deadlines)
            ->sortBy('sort_at')
            ->values()
            ->all();
    }

    protected function eventPayload(OfficeEvent $event): array
    {
        $user = request()->user();

        return [
            'id'         => $event->id,
            'title'      => $event->title,
            'type'       => $event->type,
            'typeLabel'  => $event->typeLabel(),
            'when'       => $event->starts_at->format('M d, Y'),
            'time'       => $event->starts_at->format('g:i A'),
            'detail'     => $event->description ?: $event->typeLabel(),
            'starts_at'  => $event->starts_at->toIso8601String(),
            'can_delete' => $user && ($user->role === 'admin' || $event->created_by === $user->id),
        ];
    }
}
