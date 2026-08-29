<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Document;
use App\Models\VisitorLog;
use App\Support\DocumentStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SectionReportService
{
    /**
     * @return array{period: string, label: string, stats: list<array{label: string, value: int|string}>, breakdown: list<array{label: string, value: int}>}
     */
    public function documents(Carbon $start, Carbon $end, string $period): array
    {
        $submitted   = Document::whereBetween('created_at', [$start, $end])->count();
        $approved    = Document::whereBetween('approved_at', [$start, $end])->count();
        $returned    = Document::whereBetween('returned_at', [$start, $end])->count();
        $disapproved = Document::where('status', 'rejected')->whereBetween('updated_at', [$start, $end])->count();
        $archived    = Document::whereBetween('archived_at', [$start, $end])->count();
        $released    = Document::whereBetween('released_at', [$start, $end])->count();

        $byCategory = Document::with('category.parent')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('approved_at', [$start, $end])
                    ->orWhereBetween('returned_at', [$start, $end])
                    ->orWhereBetween('archived_at', [$start, $end])
                    ->orWhereBetween('released_at', [$start, $end]);
            })
            ->get()
            ->groupBy(fn (Document $doc) => $doc->category?->label() ?? 'Uncategorized')
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc();

        return [
            'period'    => $period,
            'label'     => $period === 'weekly' ? 'Weekly document movement' : 'Monthly document movement',
            'stats'     => [
                ['label' => 'Submitted',   'value' => $submitted],
                ['label' => 'Approved',    'value' => $approved],
                ['label' => 'Returned',    'value' => $returned],
                ['label' => 'Disapproved', 'value' => $disapproved],
                ['label' => 'Archived',    'value' => $archived],
                ['label' => 'Released',    'value' => $released],
            ],
            'breakdown' => $byCategory->map(fn ($value, $label) => [
                'label' => $label,
                'value' => $value,
            ])->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function documentRows(Carbon $start, Carbon $end): array
    {
        $rows = [];

        $push = function (Document $doc, string $movement, ?Carbon $at) use (&$rows) {
            if (! $at) {
                return;
            }

            $rows[] = [
                'date'      => $at->format('Y-m-d H:i'),
                'movement'  => $movement,
                'tracking'  => $doc->tracking_number,
                'title'     => $doc->title,
                'category'  => $doc->category?->label() ?? '—',
                'status'    => DocumentStatus::label($doc->status),
                'owner'     => $doc->submitter?->name ?? '—',
            ];
        };

        $documents = Document::with(['category.parent', 'submitter'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('approved_at', [$start, $end])
                    ->orWhereBetween('returned_at', [$start, $end])
                    ->orWhereBetween('archived_at', [$start, $end])
                    ->orWhereBetween('released_at', [$start, $end])
                    ->orWhere(function ($rejected) use ($start, $end) {
                        $rejected->where('status', 'rejected')
                            ->whereBetween('updated_at', [$start, $end]);
                    });
            })
            ->get();

        foreach ($documents as $doc) {
            if ($doc->created_at && $doc->created_at->between($start, $end)) {
                $push($doc, 'Submitted', $doc->created_at);
            }
            if ($doc->approved_at && $doc->approved_at->between($start, $end)) {
                $push($doc, 'Approved', $doc->approved_at);
            }
            if ($doc->returned_at && $doc->returned_at->between($start, $end)) {
                $push($doc, 'Returned', $doc->returned_at);
            }
            if ($doc->archived_at && $doc->archived_at->between($start, $end)) {
                $push($doc, 'Archived', $doc->archived_at);
            }
            if ($doc->released_at && $doc->released_at->between($start, $end)) {
                $push($doc, 'Released', $doc->released_at);
            }
            if ($doc->status === 'rejected' && $doc->updated_at?->between($start, $end)) {
                $push($doc, 'Disapproved', $doc->updated_at);
            }
        }

        usort($rows, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return $rows;
    }

    /**
     * @return array{period: string, label: string, stats: list<array{label: string, value: int|string}>, breakdown: list<array{label: string, value: int}>}
     */
    public function visitors(Carbon $start, Carbon $end, string $period): array
    {
        $query = VisitorLog::whereBetween('time_in', [$start, $end]);
        $total = (clone $query)->count();

        $byPurpose = (clone $query)
            ->selectRaw('purpose, COUNT(*) as aggregate')
            ->groupBy('purpose')
            ->orderByDesc('aggregate')
            ->pluck('aggregate', 'purpose');

        return [
            'period'    => $period,
            'label'     => $period === 'weekly' ? 'Weekly visitor summary' : 'Monthly visitor summary',
            'stats'     => [
                ['label' => 'Total visitors', 'value' => $total],
                ['label' => 'Unique purposes', 'value' => $byPurpose->count()],
            ],
            'breakdown' => $byPurpose->map(fn ($value, $label) => [
                'label' => $label ?: 'Unspecified',
                'value' => (int) $value,
            ])->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function visitorRows(Carbon $start, Carbon $end): array
    {
        return VisitorLog::with('recorder')
            ->whereBetween('time_in', [$start, $end])
            ->orderBy('time_in')
            ->get()
            ->map(fn (VisitorLog $visitor) => [
                'date'     => $visitor->time_in?->format('Y-m-d H:i') ?? '—',
                'name'     => $visitor->visitor_name,
                'address'  => $visitor->address ?? '—',
                'purpose'  => $visitor->purpose,
                'contact'  => $visitor->visitor_phone ?? '—',
                'recorded' => $visitor->recorder?->name ?? '—',
            ])
            ->all();
    }

    /**
     * @return array{period: string, label: string, stats: list<array{label: string, value: int|string}>, breakdown: list<array{label: string, value: int}>}
     */
    public function certificates(Carbon $start, Carbon $end, string $period): array
    {
        $query = Certificate::query()
            ->where(function ($builder) use ($start, $end) {
                $builder->whereBetween('issued_at', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end) {
                        $fallback->whereNull('issued_at')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            });

        $total = (clone $query)->count();

        $byPurpose = Certificate::with('visitorLog')
            ->where(function ($builder) use ($start, $end) {
                $builder->whereBetween('issued_at', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end) {
                        $fallback->whereNull('issued_at')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->get()
            ->groupBy(fn (Certificate $cert) => $cert->visitorLog?->purpose ?: 'Unspecified')
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc();

        return [
            'period'    => $period,
            'label'     => $period === 'weekly' ? 'Weekly certificate issuance' : 'Monthly certificate issuance',
            'stats'     => [
                ['label' => 'Certificates issued', 'value' => $total],
            ],
            'breakdown' => $byPurpose->map(fn ($value, $label) => [
                'label' => $label,
                'value' => $value,
            ])->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function certificateRows(Carbon $start, Carbon $end): array
    {
        return Certificate::with(['visitorLog', 'issuer'])
            ->where(function ($builder) use ($start, $end) {
                $builder->whereBetween('issued_at', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end) {
                        $fallback->whereNull('issued_at')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->orderByRaw('COALESCE(issued_at, created_at)')
            ->get()
            ->map(fn (Certificate $cert) => [
                'date'    => ($cert->issued_at ?? $cert->created_at)?->format('Y-m-d H:i') ?? '—',
                'number'  => $cert->certificate_no,
                'visitor' => $cert->visitorLog?->visitor_name ?? '—',
                'address' => $cert->visitorLog?->address ?? '—',
                'purpose' => $cert->visitorLog?->purpose ?? '—',
                'issuer'  => $cert->signer_name ?: ($cert->issuer?->name ?? '—'),
            ])
            ->all();
    }
}
