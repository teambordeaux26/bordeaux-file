<?php

namespace App\Console\Commands;

use App\Services\DocumentRetentionService;
use Illuminate\Console\Command;

class ArchiveExpiredDocuments extends Command
{
    protected $signature = 'documents:archive-expired';

    protected $description = 'Move documents that have reached their retention period to the archive';

    public function handle(DocumentRetentionService $retentionService): int
    {
        $count = $retentionService->archiveExpired();

        if ($count === 0) {
            $this->info('No expired documents to archive.');

            return self::SUCCESS;
        }

        $this->info("Archived {$count} expired document(s).");

        return self::SUCCESS;
    }
}
