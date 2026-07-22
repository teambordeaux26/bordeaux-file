<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;

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

    public static function formatRetention(int $days): string
    {
        return match ($days) {
            30      => '30 Days',
            90      => '90 Days',
            180     => '6 Months',
            365     => '1 Year',
            730     => '2 Years',
            1825    => '5 Years',
            default => "{$days} Days",
        };
    }
}
