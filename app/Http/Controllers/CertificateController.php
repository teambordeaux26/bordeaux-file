<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\VisitorLog;
use App\Services\CertificatePdfService;
use App\Services\CertificateSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CertificateController extends Controller
{
    use ExportsSectionReports;

    public function index(Request $request, CertificateSignatureService $signatures)
    {
        $selectedDate = $request->validate([
            'date'          => 'nullable|date',
            'report_period' => 'nullable|in:weekly,monthly',
        ]);

        $selectedDate = $selectedDate['date'] ?? today()->toDateString();
        $period       = $this->reportPeriod($request->query('report_period', 'monthly'));

        $certificates = Certificate::with(['visitorLog', 'issuer'])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($c) => [
                'id'      => $c->id,
                'number'  => $c->certificate_no,
                'name'    => $c->visitorLog?->visitor_name ?? '—',
                'purpose' => $c->visitorLog?->purpose ?? '—',
                'issued'  => $c->issued_at?->format('M d, Y H:i') ?? $c->created_at->format('M d, Y H:i'),
                'signer'  => $c->signer_name ?: ($c->issuer?->name ?? '—'),
                'download_url' => route('certificates.download', $c->id),
            ]);

        $visitors = VisitorLog::whereDoesntHave('certificate')
            ->whereDate('time_in', $selectedDate)
            ->orderBy('time_in')
            ->get(['id', 'visitor_name', 'purpose', 'time_in'])
            ->map(fn($v) => [
                'id'      => $v->id,
                'name'    => $v->visitor_name,
                'purpose' => $v->purpose,
                'time_in' => $v->time_in?->format('g:i A') ?? '—',
            ]);

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
            'visitors'     => $visitors,
            'selectedDate' => $selectedDate,
            'report'       => $this->reports()->certificates($period['start'], $period['end'], $period['period']),
            'reportRange'  => $period['label'],
            'signer'       => $signatures->payloadForUser(Auth::user()),
        ]);
    }

    public function exportReport(Request $request)
    {
        $data = $request->validate([
            'period' => 'nullable|in:weekly,monthly',
            'format' => 'nullable|in:csv,pdf',
        ]);

        $period = $this->reportPeriod($data['period'] ?? 'monthly');
        $rows   = $this->reports()->certificateRows($period['start'], $period['end']);
        $report = $this->reports()->certificates($period['start'], $period['end'], $period['period']);
        $format = $data['format'] ?? 'csv';
        $stamp  = $period['period'].'-'.now()->format('Ymd');

        if ($format === 'pdf') {
            return $this->downloadReportPdf('pdf.reports.certificates', "certificates-{$stamp}.pdf", [
                'title'       => 'Certificate Issuance Report',
                'office'      => 'Office of the Vice Mayor — Oas, Albay',
                'range'       => $period['label'],
                'generatedAt' => now()->format('F j, Y g:i A'),
                'report'      => $report,
                'rows'        => $rows,
            ]);
        }

        return $this->streamCsv(
            "certificates-{$stamp}.csv",
            ['Date', 'Certificate No.', 'Visitor', 'Address', 'Purpose', 'Issued By'],
            $rows
        );
    }

    public function generate(Request $request, CertificateSignatureService $signatures)
    {
        $user = $request->user();
        $hasSaved = $signatures->hasSavedSignature($user);

        $validated = $request->validate([
            'visitor_log_id' => 'required|exists:visitor_logs,id',
            'signing_name'   => 'required|string|max:255',
            'signing_title'  => 'required|string|max:255',
            'signature'      => [Rule::requiredIf(! $hasSaved), 'nullable', 'string'],
        ]);

        $certNo = 'COA-' . now()->year . '-' . str_pad(Certificate::count() + 1, 3, '0', STR_PAD_LEFT);

        $cert = DB::transaction(function () use ($validated, $user, $signatures, $certNo) {
            $cert = Certificate::create([
                'visitor_log_id' => $validated['visitor_log_id'],
                'certificate_no' => $certNo,
                'issued_by'      => $user->id,
                'issued_at'      => now(),
            ]);

            $signatures->captureForCertificate(
                $cert,
                $user,
                $validated['signature'] ?? null,
                $validated['signing_name'],
                $validated['signing_title'],
                true
            );

            return $cert;
        });

        $visitor = VisitorLog::find($validated['visitor_log_id']);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Certificate Generated',
            'description' => "Certificate {$certNo} issued for {$visitor->visitor_name}.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('certificates.index', [
                'date' => $visitor->time_in?->toDateString(),
            ])
            ->with('success', "Certificate {$certNo} generated successfully.");
    }

    public function download(Certificate $certificate, CertificatePdfService $pdfService)
    {
        $pdf = $pdfService->make($certificate);
        $filename = 'Certificate-' . $certificate->certificate_no . '.pdf';

        return $pdf->download($filename);
    }

    public function verify(string $token, CertificateSignatureService $signatures)
    {
        $certificate = Certificate::with(['visitorLog', 'issuer'])
            ->where('verification_token', $token)
            ->firstOrFail();

        $issuedAt = $certificate->issued_at ?? $certificate->created_at;
        $signer = $signatures->signerPayload($certificate);

        return Inertia::render('Certificates/Authenticate', [
            'certificate' => [
                'number'  => $certificate->certificate_no,
                'name'    => $certificate->visitorLog?->visitor_name ?? '—',
                'address' => $certificate->visitorLog?->address ?? '—',
                'purpose' => $certificate->visitorLog?->purpose ?? '—',
                'issued'  => $issuedAt->format('F j, Y g:i A'),
            ],
            'issuer' => [
                'name'          => $signer['name'],
                'position'      => $signer['title'],
                'department'    => "Vice Mayor's Office — Oas, Albay",
                'signature_url' => $signer['signature_url'],
            ],
        ]);
    }
}
