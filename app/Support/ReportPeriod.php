<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportPeriod
{
    /**
     * @return array{period: string, start: Carbon, end: Carbon, label: string, from: string, to: string, ready: bool}
     */
    public static function fromRequest(Request $request): array
    {
        $period = $request->input('report_period') ?? $request->input('period');
        $from   = $request->input('report_from') ?? $request->input('from');
        $to     = $request->input('report_to') ?? $request->input('to');

        return static::resolve(is_string($period) ? $period : null, is_string($from) ? $from : null, is_string($to) ? $to : null);
    }

    /**
     * @return array<string, mixed>
     */
    public static function requestRules(string $periodKey = 'report_period', string $fromKey = 'report_from', string $toKey = 'report_to'): array
    {
        return [
            $periodKey => ['nullable', 'regex:/^(weekly|monthly|custom|month-\d{4}-\d{2})$/'],
            $fromKey   => "nullable|required_if:{$periodKey},custom|date",
            $toKey     => "nullable|required_if:{$periodKey},custom|date|after_or_equal:{$fromKey}",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function exportRules(): array
    {
        return [
            'period' => ['nullable', 'regex:/^(weekly|monthly|custom|month-\d{4}-\d{2})$/'],
            'from'   => 'required|date',
            'to'     => 'required|date|after_or_equal:from',
            'format' => 'nullable|in:csv,pdf',
        ];
    }

    /**
     * @return array{period: string, start: Carbon, end: Carbon, label: string, from: string, to: string, ready: bool}
     */
    public static function resolve(?string $period, ?string $from = null, ?string $to = null): array
    {
        $period = static::normalizePeriod($period);

        if (filled($from) && filled($to)) {
            $period = $period !== '' ? $period : 'custom';
            $start  = Carbon::parse($from)->startOfDay();
            $end    = Carbon::parse($to)->endOfDay();

            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            $label = static::labelFor($period, $start, $end);
        } elseif ($period === 'weekly') {
            $start = Carbon::now()->startOfWeek();
            $end   = Carbon::now()->endOfWeek();
            $label = $start->format('M d').' – '.$end->format('M d, Y');
        } elseif ($period === 'monthly') {
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfMonth();
            $label = $start->format('F Y');
        } elseif ($namedMonth = static::namedMonth($period)) {
            $start = $namedMonth->copy()->startOfMonth();
            $end   = $namedMonth->copy()->endOfMonth();
            $label = $start->format('F Y');
        } else {
            $now = Carbon::now();

            return [
                'period' => '',
                'start'  => $now->copy()->startOfDay(),
                'end'    => $now->copy()->endOfDay(),
                'label'  => '',
                'from'   => '',
                'to'     => '',
                'ready'  => false,
            ];
        }

        return [
            'period' => $period,
            'start'  => $start,
            'end'    => $end,
            'label'  => $label,
            'from'   => $start->toDateString(),
            'to'     => $end->toDateString(),
            'ready'  => true,
        ];
    }

    public static function normalizePeriod(?string $period): string
    {
        if (in_array($period, ['weekly', 'monthly', 'custom'], true)) {
            return $period;
        }

        return static::namedMonth($period) ? $period : '';
    }

    public static function namedMonth(?string $period): ?Carbon
    {
        if (! is_string($period) || ! preg_match('/^month-(\d{4})-(\d{2})$/', $period, $matches)) {
            return null;
        }

        $month = (int) $matches[2];

        if ($month < 1 || $month > 12) {
            return null;
        }

        return Carbon::createFromDate((int) $matches[1], $month, 1)->startOfMonth();
    }

    private static function labelFor(string $period, Carbon $start, Carbon $end): string
    {
        if ($period === 'monthly' || static::namedMonth($period)) {
            return $start->format('F Y');
        }

        return $start->format('M d, Y').' – '.$end->format('M d, Y');
    }
}
