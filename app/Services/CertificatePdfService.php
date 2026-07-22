<?php

namespace App\Services;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificatePdfService
{
    public function make(Certificate $certificate): DomPdf
    {
        $certificate->load(['visitorLog', 'issuer']);
        $certificate->ensureVerificationToken();

        $issuedAt = $certificate->issued_at ?? $certificate->created_at;
        $verifyUrl = URL::route('certificates.verify', $certificate->verification_token);

        $qrCode = base64_encode(
            QrCode::format('svg')
                ->size(140)
                ->margin(1)
                ->generate($verifyUrl)
        );

        return Pdf::loadView('pdf.certificate_of_appearance', [
            'visitor_name'   => $certificate->visitorLog?->visitor_name ?? '_______________',
            'address'        => $certificate->visitorLog?->address ?? '',
            'purpose'        => $certificate->visitorLog?->purpose ?? '_______________',
            'day'            => $issuedAt->format('j'),
            'month_year'     => $issuedAt->format('F Y'),
            'certificate_no' => $certificate->certificate_no,
            'qr_code'        => $qrCode,
        ])->setOption([
            'defaultFont'             => 'serif',
            'isRemoteEnabled'         => true,
            'defaultPaperSize'        => 'a4',
            'defaultPaperOrientation' => 'landscape',
        ])->setPaper([0, 0, 841.89, 595.28]);
    }
}
