<?php

namespace App\Support;

use Carbon\CarbonInterface;

class OfficeTime
{
    public static function exact(?CarbonInterface $date): string
    {
        return $date?->format('F d, Y, \a\t g:i A') ?? '—';
    }
}
