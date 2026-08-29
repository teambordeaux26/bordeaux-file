<?php

namespace App\Http\Controllers;

use App\Services\SectionReportService;
use App\Support\ReportPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsSectionReports
{
    protected function reportPeriod(?string $period): array
    {
        return ReportPeriod::resolve($period);
    }

    protected function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function downloadReportPdf(
        string $view,
        string $filename,
        array $payload
    ) {
        return Pdf::loadView($view, $payload)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    protected function reports(): SectionReportService
    {
        return app(SectionReportService::class);
    }
}
