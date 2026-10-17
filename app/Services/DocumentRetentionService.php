<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\SystemSetting;

class DocumentRetentionService
{
    public function archiveExpired(): int
    {
        $expired = Document::query()
            ->where('status', '!=', 'archived')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $document) {
            $document->update([
                'status'      => 'archived',
                'archived_at' => now(),
            ]);

            AuditLog::create([
                'user_id'     => null,
                'action'      => 'Document Auto-Archived',
                'description' => "Document {$document->tracking_number} archived after reaching its retention period.",
                'ip_address'  => request()?->ip() ?? '127.0.0.1',
            ]);
        }

        return $expired->count();
    }

    /**
     * @return list<array{amount: int, unit: string}>
     */
    public static function defaultPeriods(): array
    {
        return [
            ['amount' => 7, 'unit' => 'days'],
            ['amount' => 30, 'unit' => 'days'],
            ['amount' => 3, 'unit' => 'months'],
            ['amount' => 6, 'unit' => 'months'],
            ['amount' => 12, 'unit' => 'months'],
            ['amount' => 24, 'unit' => 'months'],
            ['amount' => 60, 'unit' => 'months'],
        ];
    }

    public static function toDays(int $amount, string $unit): int
    {
        $amount = max(1, $amount);

        if ($unit !== 'months') {
            return $amount;
        }

        if ($amount % 12 === 0) {
            return intdiv($amount, 12) * 365;
        }

        return $amount * 30;
    }

    public static function labelFor(int $amount, string $unit): string
    {
        $unit = $unit === 'months' ? 'months' : 'days';
        $noun = $unit === 'months'
            ? ($amount === 1 ? 'Month' : 'Months')
            : ($amount === 1 ? 'Day' : 'Days');

        return "{$amount} {$noun}";
    }

    /**
     * @param  array<int, mixed>|null  $periods
     * @return list<array{amount: int, unit: string, days: int, label: string}>
     */
    public static function normalizePeriods(?array $periods): array
    {
        $normalized = [];
        $seen = [];

        foreach ($periods ?? [] as $period) {
            if (! is_array($period)) {
                continue;
            }

            $unit = ($period['unit'] ?? 'days') === 'months' ? 'months' : 'days';
            $amount = (int) ($period['amount'] ?? 0);

            if ($amount < 1) {
                continue;
            }

            $days = static::toDays($amount, $unit);

            if (isset($seen[$days])) {
                continue;
            }

            $seen[$days] = true;
            $normalized[] = [
                'amount' => $amount,
                'unit'   => $unit,
                'days'   => $days,
                'label'  => static::labelFor($amount, $unit),
            ];
        }

        if ($normalized === []) {
            return static::normalizePeriods(static::defaultPeriods());
        }

        return $normalized;
    }

    /**
     * @return list<array{amount: int, unit: string, days: int, label: string}>
     */
    public static function periods(): array
    {
        return static::normalizePeriods(SystemSetting::current()->retention_periods);
    }

    /**
     * @return list<array{amount: int, unit: string}>
     */
    public static function persistable(array $periods): array
    {
        return array_map(
            fn (array $period) => [
                'amount' => $period['amount'],
                'unit'   => $period['unit'],
            ],
            static::normalizePeriods($periods)
        );
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public static function options(?int $currentDays = null): array
    {
        $options = array_map(
            fn (array $period) => [
                'value' => $period['days'],
                'label' => $period['label'],
            ],
            static::periods()
        );

        if ($currentDays && ! in_array($currentDays, array_column($options, 'value'), true)) {
            array_unshift($options, [
                'value' => $currentDays,
                'label' => static::formatRetention($currentDays),
            ]);
        }

        return $options;
    }

    /**
     * @return list<int>
     */
    public static function allowedDays(?int $currentDays = null): array
    {
        $days = array_column(static::periods(), 'days');

        if ($currentDays) {
            $days[] = $currentDays;
        }

        return array_values(array_unique($days));
    }

    public static function formatRetention(int $days): string
    {
        foreach (static::periods() as $period) {
            if ($period['days'] === $days) {
                return $period['label'];
            }
        }

        return match ($days) {
            7    => '7 Days',
            30   => '30 Days',
            90   => '90 Days',
            180  => '6 Months',
            365  => '1 Year',
            730  => '2 Years',
            1825 => '5 Years',
            default => "{$days} Days",
        };
    }
}
