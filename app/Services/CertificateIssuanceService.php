<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CertificateIssuanceService
{
    public function __construct(
        private CertificatePdfService $pdfService,
        private CertificateSignatureService $signatures,
    ) {}

    public function issueFromDocumentRequest(
        DocumentRequest $documentRequest,
        ?int $issuedBy = null,
        ?array $overrides = null,
        ?array $signer = null
    ): Certificate {
        if ($documentRequest->certificate_id) {
            return Certificate::query()->findOrFail($documentRequest->certificate_id);
        }

        return DB::transaction(function () use ($documentRequest, $issuedBy, $overrides, $signer) {
            $visitor = VisitorLog::create([
                'visitor_name'  => $overrides['visitor_name'] ?? $documentRequest->requester_name,
                'visitor_phone' => $overrides['visitor_phone'] ?? $documentRequest->requester_phone,
                'address'       => $overrides['address'] ?? ($documentRequest->requester_address ?: 'Not specified'),
                'purpose'       => $overrides['purpose']
                    ?? $documentRequest->connectedPurpose(),
                'time_in'       => now(),
            ]);

            $certNo = 'COA-'.now()->year.'-'.str_pad(Certificate::count() + 1, 3, '0', STR_PAD_LEFT);

            $certificate = Certificate::create([
                'visitor_log_id' => $visitor->id,
                'certificate_no' => $certNo,
                'issued_by'      => $issuedBy,
                'issued_at'      => now(),
            ]);

            if ($issuedBy) {
                $this->signatures->captureForCertificate(
                    $certificate,
                    User::query()->findOrFail($issuedBy),
                    $signer['signature'] ?? null,
                    $signer['signing_name'] ?? null,
                    $signer['signing_title'] ?? null,
                    true
                );
            } else {
                $this->signatures->snapshotOntoCertificate($certificate, null, false);
            }

            return $certificate->fresh();
        });
    }

    /**
     * @return array{path: string, filename: string}
     */
    public function storePdf(Certificate $certificate): array
    {
        $pdf = $this->pdfService->make($certificate);
        $filename = 'Certificate-'.$certificate->certificate_no.'.pdf';
        $path = 'request-responses/'.$filename;

        Storage::disk('public')->put($path, $pdf->output());

        $certificate->update(['file_path' => $path]);

        return [
            'path'     => $path,
            'filename' => $filename,
        ];
    }
}
