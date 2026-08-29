<?php

namespace App\Support;

use Carbon\Carbon;

class ReportPeriod
{
    /**
     * @return array{period: string, start: Carbon, end: Carbon, label: string}
     */
    public static function resolve(?string $period): array
    {
        $period = $period === 'weekly' ? 'weekly' : 'monthly';

        if ($period === 'weekly') {
            $start = now()->startOfWeek();
            $end   = now()->endOfWeek();
            $label = $start->format('M d').' – '.$end->format('M d, Y');
        } else {
            $start = now()->startOfMonth();
            $end   = now()->endOfMonth();
            $label = $start->format('F Y');
        }

        return compact('period', 'start', 'end', 'label');
    }
}
