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
     * @return array<string, string>
     */
    public static function requestRules(string $periodKey = 'report_period', string $fromKey = 'report_from', string $toKey = 'report_to'): array
    {
        return [
            $periodKey => 'nullable|in:weekly,monthly,custom',
            $fromKey   => "nullable|required_if:{$periodKey},custom|date",
            $toKey     => "nullable|required_if:{$periodKey},custom|date|after_or_equal:{$fromKey}",
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function exportRules(): array
    {
        return [
            'period' => 'nullable|in:weekly,monthly,custom',
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
        $period = in_array($period, ['weekly', 'monthly', 'custom'], true) ? $period : '';

        if (filled($from) && filled($to)) {
            $period = $period !== '' ? $period : 'custom';
            $start  = Carbon::parse($from)->startOfDay();
            $end    = Carbon::parse($to)->endOfDay();

            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            $label = $start->format('M d, Y').' – '.$end->format('M d, Y');
        } elseif ($period === 'weekly') {
            $start = Carbon::now()->startOfWeek();
            $end   = Carbon::now()->endOfWeek();
            $label = $start->format('M d').' – '.$end->format('M d, Y');
        } elseif ($period === 'monthly') {
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfMonth();
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
}
