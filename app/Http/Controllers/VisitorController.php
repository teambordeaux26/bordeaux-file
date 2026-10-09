<?php

namespace App\Http\Controllers;

use App\Mail\VisitorRecordMail;
use App\Models\AuditLog;
use App\Models\VisitorLog;
use App\Rules\OasBarangayAddress;
use App\Support\OfficeTime;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class VisitorController extends Controller
{
    use ExportsSectionReports;

    public function index(Request $request)
    {
        $request->validate(ReportPeriod::requestRules());

        $map = fn ($v) => [
            'id'      => $v->id,
            'name'    => $v->visitor_name,
            'contact' => $v->visitor_phone ?? '—',
            'email'   => $v->visitor_email ?? '—',
            'address' => $v->address ?? '—',
            'purpose' => $v->purpose,
            'timeIn'  => $v->time_in->format('g:i A'),
            'timeOut' => $v->time_out?->format('g:i A'),
            'when'    => OfficeTime::exact($v->time_in),
        ];

        $visitors = VisitorLog::with('recorder')
            ->whereDate('time_in', today())
            ->latest('time_in')
            ->get()
            ->map($map);

        $previous = VisitorLog::with('recorder')
            ->where('time_in', '<', today()->startOfDay())
            ->latest('time_in')
            ->paginate(10, ['*'], 'previous_page')
            ->withQueryString()
            ->through($map);

        $period = $this->reportPeriodFrom($request);
        $report = $period['ready']
            ? $this->reports()->visitors($period['start'], $period['end'], $period['period'])
            : $this->emptyReport();

        return Inertia::render('Visitors/Index', [
            'visitors'    => $visitors,
            'previous'    => $previous,
            'report'      => $report,
            'reportRange' => $period['label'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_name'  => 'required|string|max:255',
            'visitor_phone' => 'nullable|string|max:30',
            'visitor_email' => 'nullable|email|max:255',
            'address'       => ['required', 'string', 'max:500', new OasBarangayAddress],
            'purpose'       => 'required|string|max:255',
        ]);

        $visitor = VisitorLog::create([
            ...$validated,
            'visitor_email' => $validated['visitor_email'] ?? null,
            'time_in'       => now(),
            'recorded_by'   => Auth::id(),
        ]);

        $when = OfficeTime::exact($visitor->time_in);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Visitor Logged',
            'description' => "Visitor {$visitor->visitor_name} from {$visitor->address} was logged on {$when} by ".Auth::user()->name." for {$visitor->purpose}.",
            'ip_address'  => $request->ip(),
        ]);

        $message = "Visitor {$visitor->visitor_name} was logged on {$when}.";

        if (filled($visitor->visitor_email)) {
            try {
                Mail::mailer('smtp')->to($visitor->visitor_email)->send(new VisitorRecordMail($visitor));
                $message .= " A copy was sent to {$visitor->visitor_email}.";
            } catch (\Throwable $e) {
                report($e);
                $message .= ' The visit was saved, but the email copy could not be sent.';
            }
        }

        return redirect()->route('visitors.index')
            ->with('success', $message);
    }

    public function exportReport(Request $request)
    {
        $data = $request->validate(ReportPeriod::exportRules());

        $period = $this->reportPeriodFrom($request);
        $rows   = $this->reports()->visitorRows($period['start'], $period['end']);
        $report = $this->reports()->visitors($period['start'], $period['end'], $period['period']);
        $format = $data['format'] ?? 'csv';
        $stamp  = $period['from'].'-to-'.$period['to'];

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
