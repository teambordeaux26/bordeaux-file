<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DocumentCategory;
use App\Support\OfficeTime;
use App\Support\ReportPeriod;
use App\Support\TextSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditController extends Controller
{
    use ExportsSectionReports;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255',
            'action' => 'nullable|string|max:120',
            'category_id' => 'nullable|integer|exists:document_categories,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'preset' => 'nullable|in:week,month',
            ...ReportPeriod::requestRules(),
        ]);

        [$start, $end] = $this->range($filters);

        $query = AuditLog::with('user')->latest();

        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function ($builder) use ($term) {
                TextSearch::contains($builder, 'action', $term);
                TextSearch::contains($builder, 'description', $term, true);
                $builder->orWhereHas('user', fn ($rel) => TextSearch::contains($rel, 'name', $term));
            });
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['category_id'])) {
            $ids = DocumentCategory::idsForFilter((int) $filters['category_id']);
            $names = DocumentCategory::query()->whereIn('id', $ids)->pluck('name');
            $query->where(function ($builder) use ($ids, $names) {
                foreach ($ids as $id) {
                    $builder->orWhere('meta->category_id', $id);
                }
                foreach ($names as $name) {
                    TextSearch::contains($builder, 'description', $name, true);
                }
            });
        }

        $logs = $query
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'detail' => $log->description,
                'user' => $log->user?->name ?? 'System',
                'time' => OfficeTime::exact($log->created_at),
            ]);

        $period = $this->reportPeriodFrom($request);

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'categories' => DocumentCategory::treeForForms(),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'action' => $filters['action'] ?? '',
                'category_id' => isset($filters['category_id']) ? (string) $filters['category_id'] : '',
                'from' => $start?->toDateString() ?? '',
                'to' => $end?->toDateString() ?? '',
                'preset' => $filters['preset'] ?? '',
            ],
            'report' => $period['ready']
                ? $this->reports()->audit($period['start'], $period['end'], $period['period'])
                : $this->emptyReport(),
            'reportRange' => $period['label'],
        ]);
    }

    public function exportReport(Request $request)
    {
        $data = $request->validate(ReportPeriod::exportRules());
        $period = $this->reportPeriodFrom($request);
        $rows = $this->reports()->auditRows($period['start'], $period['end']);
        $report = $this->reports()->audit($period['start'], $period['end'], $period['period']);
        $format = $data['format'] ?? 'csv';
        $stamp = $period['from'].'-to-'.$period['to'];

        if ($format === 'pdf') {
            return $this->downloadReportPdf('pdf.reports.simple', "audit-trail-{$stamp}.pdf", [
                'title' => 'Audit Trail Report',
                'office' => 'Office of the Vice Mayor — Oas, Albay',
                'range' => $period['label'],
                'generatedAt' => now()->format('F j, Y g:i A'),
                'report' => $report,
                'columns' => ['When', 'Action', 'Detail', 'User', 'IP Address'],
                'rows' => $rows,
            ]);
        }

        return $this->streamCsv(
            "audit-trail-{$stamp}.csv",
            ['When', 'Action', 'Detail', 'User', 'IP Address'],
            $rows
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: ?\Carbon\Carbon, 1: ?\Carbon\Carbon}
     */
    private function range(array $filters): array
    {
        if (($filters['preset'] ?? '') === 'week') {
            return [now()->startOfWeek(), now()->endOfWeek()];
        }

        if (($filters['preset'] ?? '') === 'month') {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        if (! empty($filters['from']) && ! empty($filters['to'])) {
            return [
                \Carbon\Carbon::parse($filters['from'])->startOfDay(),
                \Carbon\Carbon::parse($filters['to'])->endOfDay(),
            ];
        }

        return [null, null];
    }
}
