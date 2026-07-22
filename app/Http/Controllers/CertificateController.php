<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\VisitorLog;
use App\Services\CertificatePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->validate([
            'date' => 'nullable|date',
        ])['date'] ?? today()->toDateString();

        $certificates = Certificate::with(['visitorLog', 'issuer'])
            ->latest()
            ->get()
            ->map(fn($c) => [
                'id'      => $c->id,
                'number'  => $c->certificate_no,
                'name'    => $c->visitorLog?->visitor_name ?? '—',
                'purpose' => $c->visitorLog?->purpose ?? '—',
                'issued'  => $c->issued_at?->format('M d, Y H:i') ?? $c->created_at->format('M d, Y H:i'),
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
            'certificates'   => $certificates,
            'visitors'       => $visitors,
            'selectedDate'   => $selectedDate,
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'visitor_log_id' => 'required|exists:visitor_logs,id',
        ]);

        $certNo = 'COA-' . now()->year . '-' . str_pad(Certificate::count() + 1, 3, '0', STR_PAD_LEFT);

        $cert = Certificate::create([
            'visitor_log_id' => $request->visitor_log_id,
            'certificate_no' => $certNo,
            'issued_by'      => Auth::id(),
            'issued_at'      => now(),
        ]);

        $visitor = VisitorLog::find($request->visitor_log_id);

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

    public function verify(string $token)
    {
        $certificate = Certificate::with(['visitorLog', 'issuer'])
            ->where('verification_token', $token)
            ->firstOrFail();

        $issuedAt = $certificate->issued_at ?? $certificate->created_at;
        $issuer = $certificate->issuer;

        return Inertia::render('Certificates/Authenticate', [
            'certificate' => [
                'number'  => $certificate->certificate_no,
                'name'    => $certificate->visitorLog?->visitor_name ?? '—',
                'address' => $certificate->visitorLog?->address ?? '—',
                'purpose' => $certificate->visitorLog?->purpose ?? '—',
                'issued'  => $issuedAt->format('F j, Y g:i A'),
            ],
            'issuer' => [
                'name'       => $issuer?->name ?? 'Office of the Vice Mayor',
                'position'   => $issuer?->position ?? 'Certificate Issuing Office',
                'department' => $issuer?->department ?? "Vice Mayor's Office — Oas, Albay",
                'email'      => $issuer?->email,
            ],
        ]);
    }
}
