<?php

use App\Support\ReportPeriod;
use Carbon\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('resolves a custom from and to date range', function () {
    $result = ReportPeriod::resolve('custom', '2026-03-02', '2026-03-18');

    expect($result['period'])->toBe('custom')
        ->and($result['from'])->toBe('2026-03-02')
        ->and($result['to'])->toBe('2026-03-18')
        ->and($result['start']->toDateTimeString())->toBe('2026-03-02 00:00:00')
        ->and($result['end']->toDateTimeString())->toBe('2026-03-18 23:59:59')
        ->and($result['label'])->toBe('Mar 02, 2026 – Mar 18, 2026');
});

it('swaps custom dates when the end is before the start', function () {
    $result = ReportPeriod::resolve('custom', '2026-03-18', '2026-03-02');

    expect($result['from'])->toBe('2026-03-02')
        ->and($result['to'])->toBe('2026-03-18');
});

it('resolves this week when the period is weekly', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-11 10:00:00'));

    $result = ReportPeriod::resolve('weekly');

    expect($result['period'])->toBe('weekly')
        ->and($result['from'])->toBe(Carbon::now()->startOfWeek()->toDateString())
        ->and($result['to'])->toBe(Carbon::now()->endOfWeek()->toDateString());
});

it('resolves a named previous month', function () {
    $result = ReportPeriod::resolve('month-2026-08');

    expect($result['ready'])->toBeTrue()
        ->and($result['period'])->toBe('month-2026-08')
        ->and($result['from'])->toBe('2026-08-01')
        ->and($result['to'])->toBe('2026-08-31')
        ->and($result['label'])->toBe('August 2026');
});

it('does not select a range until a period or dates are provided', function () {
    $result = ReportPeriod::resolve(null);

    expect($result['ready'])->toBeFalse()
        ->and($result['period'])->toBe('')
        ->and($result['from'])->toBe('')
        ->and($result['to'])->toBe('');
});
