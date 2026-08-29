<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\VisitorLog;
use App\Rules\OasBarangayAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VisitorController extends Controller
{
    use ExportsSectionReports;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'report_period' => 'nullable|in:weekly,monthly',
        ]);

        $visitors = VisitorLog::with('recorder')
            ->whereDate('time_in', today())
            ->latest('time_in')
            ->get()
            ->map(fn ($v) => [
                'id'      => $v->id,
                'name'    => $v->visitor_name,
                'contact' => $v->visitor_phone ?? '—',
                'address' => $v->address ?? '—',
                'purpose' => $v->purpose,
                'timeIn'  => $v->time_in->format('H:i'),
                'timeOut' => $v->time_out?->format('H:i'),
            ]);

        $period = $this->reportPeriod($filters['report_period'] ?? 'monthly');

        return Inertia::render('Visitors/Index', [
            'visitors'    => $visitors,
            'report'      => $this->reports()->visitors($period['start'], $period['end'], $period['period']),
            'reportRange' => $period['label'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_name'  => 'required|string|max:255',
            'visitor_phone' => 'nullable|string|max:30',
            'address'       => ['required', 'string', 'max:500', new OasBarangayAddress],
            'purpose'       => 'required|string|max:255',
        ]);

        VisitorLog::create([
            ...$validated,
            'time_in'     => now(),
            'recorded_by' => Auth::id(),
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Visitor Logged',
            'description' => "Visitor '{$validated['visitor_name']}' recorded for: {$validated['purpose']}",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('visitors.index')
            ->with('success', "Visitor '{$validated['visitor_name']}' logged successfully.");
    }

    public function exportReport(Request $request)
    {
        $data = $request->validate([
            'period' => 'nullable|in:weekly,monthly',
            'format' => 'nullable|in:csv,pdf',
        ]);

        $period = $this->reportPeriod($data['period'] ?? 'monthly');
        $rows   = $this->reports()->visitorRows($period['start'], $period['end']);
        $report = $this->reports()->visitors($period['start'], $period['end'], $period['period']);
        $format = $data['format'] ?? 'csv';
        $stamp  = $period['period'].'-'.now()->format('Ymd');

        if ($format === 'pdf') {
            return $this->downloadReportPdf('pdf.reports.visitors', "visitor-log-{$stamp}.pdf", [
                'title'       => "Visitor's Log Report",
                'office'      => 'Office of the Vice Mayor — Oas, Albay',
                'range'       => $period['label'],
                'generatedAt' => now()->format('F j, Y g:i A'),
                'report'      => $report,
                'rows'        => $rows,
            ]);
        }

        return $this->streamCsv(
            "visitor-log-{$stamp}.csv",
            ['Date', 'Visitor', 'Address', 'Purpose', 'Contact', 'Recorded By'],
            $rows
        );
    }
}
