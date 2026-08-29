<?php

namespace App\Http\Controllers;

use App\Mail\CertificateOfAppearanceMail;
use App\Mail\RequestCompletedMail;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\DocumentRequest;
use App\Models\RequestType;
use App\Rules\OasBarangayAddress;
use App\Services\CertificateIssuanceService;
use App\Services\CertificatePdfService;
use App\Services\CertificateSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RequestController extends Controller
{
    public function create()
    {
        return Inertia::render('Requests/Create', [
            'requestTypes' => RequestType::activeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requester_name'    => 'required|string|max:255',
            'requester_email'   => 'required|email|max:255',
            'requester_phone'   => 'nullable|string|max:30',
            'requester_address' => ['required', 'string', 'max:500', new OasBarangayAddress],
            'request_type_id'   => [
                'required',
                Rule::exists('request_types', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'details'           => 'nullable|string|max:2000',
        ]);

        $type = RequestType::query()
            ->where('id', $validated['request_type_id'])
            ->where('is_active', true)
            ->first();

        if (! $type) {
            return back()->withErrors([
                'request_type_id' => 'Select an available request type.',
            ]);
        }

        $year     = now()->year;
        $count    = DocumentRequest::whereYear('created_at', $year)->count() + 1;
        $tracking = 'REQ-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        DocumentRequest::create([
            'requester_name'    => $validated['requester_name'],
            'requester_email'   => $validated['requester_email'],
            'requester_phone'   => $validated['requester_phone'] ?? null,
            'requester_address' => $validated['requester_address'],
            'request_type_id'   => $type->id,
            'request_type'      => $type->name,
            'purpose'           => $type->purpose,
            'details'           => $validated['details'] ?? null,
            'tracking_number'   => $tracking,
            'status'            => 'pending',
        ]);

        return redirect()->route('requests.status', ['tracking' => $tracking]);
    }

    // ── Admin ──────────────────────────────────────────────────────────────

    public function adminIndex(CertificateSignatureService $signatures)
    {
        $requests = DocumentRequest::with(['processor', 'type'])
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id'                   => $r->id,
                'tracking'             => $r->tracking_number,
                'name'                 => $r->requester_name,
                'email'                => $r->requester_email ?? '—',
                'phone'                => $r->requester_phone ?? '—',
                'type'                 => $r->request_type,
                'purpose'              => $r->connectedPurpose(),
                'issues_certificate'   => $r->issuesCertificate(),
                'details'              => $r->details,
                'status'               => $r->status,
                'has_file'             => (bool) $r->response_file_path,
                'file_name'            => $r->response_file_name,
                'email_sent'           => (bool) $r->email_sent_at,
                'submitted_at'         => $r->created_at->format('M d, Y'),
                'processed_at'         => $r->processed_at?->format('M d, Y'),
                'processed_by'         => $r->processor?->name,
            ]);

        return Inertia::render('Requests/Admin', [
            'requests' => $requests,
            'signer'   => $signatures->payloadForUser(Auth::user()),
        ]);
    }

    public function adminUpdateStatus(Request $request, DocumentRequest $documentRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:under_review,rejected',
        ]);

        $documentRequest->update([
            'status'       => $validated['status'],
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        $label = match ($validated['status']) {
            'under_review' => 'marked as Under Review',
            'rejected'     => 'disapproved',
            default        => 'updated',
        };

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Request Status Updated',
            'description' => "Request {$documentRequest->tracking_number} {$label} by " . Auth::user()->name . '.',
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('requests.index')
            ->with('success', "Request {$documentRequest->tracking_number} {$label}.");
    }

    public function adminComplete(
        Request $request,
        DocumentRequest $documentRequest,
        CertificateIssuanceService $issuanceService,
        CertificateSignatureService $signatures
    ) {
        abort_if($documentRequest->status !== 'under_review', 403, 'Only requests under review can be completed.');

        $isCertificate = $documentRequest->issuesCertificate();

        if (! $documentRequest->requester_email) {
            return back()->withErrors([
                'response_file' => 'This request has no email address. The requester must provide an email before completion.',
            ]);
        }

        if ($isCertificate) {
            return $this->completeCertificateRequest($request, $documentRequest, $issuanceService, $signatures);
        }

        $request->validate([
            'response_file' => 'required|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
        ]);

        $file = $request->file('response_file');
        $path = $file->store('request-responses', 'public');
        $fileName = $file->getClientOriginalName();

        $documentRequest->update([
            'status'             => 'completed',
            'response_file_path' => $path,
            'response_file_name' => $fileName,
            'processed_by'       => Auth::id(),
            'processed_at'       => now(),
        ]);

        $emailSent = false;

        try {
            Mail::to($documentRequest->requester_email)->send(new RequestCompletedMail($documentRequest));
            $documentRequest->update(['email_sent_at' => now()]);
            $emailSent = true;
        } catch (\Throwable $e) {
            report($e);
        }

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Request Completed',
            'description' => "Request {$documentRequest->tracking_number} completed by ".Auth::user()->name
                .($emailSent ? ' and emailed to '.$documentRequest->requester_email.'.' : '. Email delivery failed.'),
            'ip_address'  => $request->ip(),
        ]);

        $message = "Request {$documentRequest->tracking_number} marked as completed.";
        if ($emailSent) {
            $message .= " The document was emailed to {$documentRequest->requester_email}.";
        } else {
            return redirect()->route('requests.index')
                ->with('success', $message)
                ->with('error', 'The document was saved, but the email could not be sent. Check your mail settings.');
        }

        return redirect()->route('requests.index')->with('success', $message);
    }

    private function completeCertificateRequest(
        Request $request,
        DocumentRequest $documentRequest,
        CertificateIssuanceService $issuanceService,
        CertificateSignatureService $signatures
    ) {
        $user = $request->user();
        $hasSaved = $signatures->hasSavedSignature($user);

        $signer = $request->validate([
            'signing_name'  => 'required|string|max:255',
            'signing_title' => 'required|string|max:255',
            'signature'     => [Rule::requiredIf(! $hasSaved), 'nullable', 'string'],
        ]);

        $certificate = $issuanceService->issueFromDocumentRequest(
            $documentRequest,
            $user->id,
            null,
            $signer
        );
        $stored = $issuanceService->storePdf($certificate);

        $documentRequest->update([
            'status'             => 'completed',
            'certificate_id'     => $certificate->id,
            'response_file_path' => $stored['path'],
            'response_file_name' => $stored['filename'],
            'processed_by'       => Auth::id(),
            'processed_at'       => now(),
        ]);

        $emailSent = false;

        try {
            Mail::to($documentRequest->requester_email)->send(
                new CertificateOfAppearanceMail($documentRequest->fresh(), $certificate)
            );
            $documentRequest->update(['email_sent_at' => now()]);
            $emailSent = true;
        } catch (\Throwable $e) {
            report($e);
        }

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Certificate Issued',
            'description' => "Certificate {$certificate->certificate_no} generated for request {$documentRequest->tracking_number}"
                .($emailSent ? ' and emailed to '.$documentRequest->requester_email.'.' : '. Email delivery failed.'),
            'ip_address'  => $request->ip(),
        ]);

        $message = "Request {$documentRequest->tracking_number} completed. Certificate {$certificate->certificate_no} was generated.";
        if ($emailSent) {
            $message .= " It was emailed to {$documentRequest->requester_email}.";
        } else {
            return redirect()->route('requests.index')
                ->with('success', $message)
                ->with('error', 'The certificate was generated, but the email could not be sent. Check your mail settings.');
        }

        return redirect()->route('requests.index')->with('success', $message);
    }

    public function downloadResponse(Request $request, DocumentRequest $documentRequest)
    {
        $tracking = strtoupper(trim($request->query('tracking', '')));

        abort_unless($tracking && $documentRequest->tracking_number === $tracking, 403);
        abort_unless($documentRequest->status === 'completed', 403);
        abort_unless($documentRequest->response_file_path, 404);
        abort_unless(Storage::disk('public')->exists($documentRequest->response_file_path), 404);

        return Storage::disk('public')->download(
            $documentRequest->response_file_path,
            $documentRequest->response_file_name ?? 'requested-document'
        );
    }

    // ── Guest ──────────────────────────────────────────────────────────────

    public function status(Request $request)
    {
        $tracking = $request->query('tracking');
        $result   = null;
        $notFound = false;

        if ($tracking) {
            $doc = DocumentRequest::with('type')->where('tracking_number', strtoupper(trim($tracking)))->first();

            if ($doc) {
                $result = [
                    'id'                => $doc->id,
                    'tracking_number'   => $doc->tracking_number,
                    'requester_name'    => $doc->requester_name,
                    'requester_phone'   => $doc->requester_phone,
                    'requester_address' => $doc->requester_address,
                    'request_type'      => $doc->request_type,
                    'purpose'           => $doc->connectedPurpose(),
                    'issues_certificate'=> $doc->issuesCertificate(),
                    'details'           => $doc->details,
                    'status'            => $doc->status,
                    'submitted_at'      => $doc->created_at->format('M d, Y'),
                    'processed_at'      => $doc->processed_at?->format('M d, Y'),
                    'has_file'          => (bool) $doc->response_file_path,
                    'file_name'         => $doc->response_file_name,
                    'download_url'      => $doc->response_file_path
                        ? route('requests.download', [
                            'documentRequest' => $doc->id,
                            'tracking'        => $doc->tracking_number,
                        ])
                        : null,
                    'has_certificate'   => (bool) $doc->certificate_id,
                    'certificate_url'   => $doc->certificate_id
                        ? route('guest.certificate.preview', $doc->certificate_id)
                        : null,
                ];
            } else {
                $notFound = true;
            }
        }

        return Inertia::render('Requests/Status', [
            'query'    => $tracking,
            'result'   => $result,
            'notFound' => $notFound,
        ]);
    }

    // ── Guest COA flow ─────────────────────────────────────────────────────

    public function showCertificateForm(DocumentRequest $documentRequest)
    {
        abort_unless(
            $documentRequest->issuesCertificate(),
            403,
            'A certificate can only be issued for completed certificate requests.'
        );

        abort_if(
            $documentRequest->status !== 'completed',
            403,
            'A certificate can only be issued for completed requests.'
        );

        if ($documentRequest->certificate_id) {
            return redirect()->route('guest.certificate.preview', $documentRequest->certificate_id);
        }

        return Inertia::render('Requests/CertificateForm', [
            'docRequest' => [
                'id'      => $documentRequest->id,
                'tracking'=> $documentRequest->tracking_number,
                'name'    => $documentRequest->requester_name,
                'phone'   => $documentRequest->requester_phone ?? '',
                'address' => $documentRequest->requester_address ?? '',
                'type'    => $documentRequest->request_type,
                'purpose' => $documentRequest->connectedPurpose(),
            ],
        ]);
    }

    public function issueGuestCertificate(
        Request $request,
        DocumentRequest $documentRequest,
        CertificateIssuanceService $issuanceService
    ) {
        abort_unless($documentRequest->issuesCertificate(), 403);
        abort_if($documentRequest->status !== 'completed', 403);

        if ($documentRequest->certificate_id) {
            return redirect()->route('guest.certificate.preview', $documentRequest->certificate_id);
        }

        $validated = $request->validate([
            'visitor_name'  => 'required|string|max:255',
            'visitor_phone' => 'nullable|string|max:30',
            'address'       => ['required', 'string', 'max:500', new OasBarangayAddress],
            'purpose'       => 'required|string|max:255',
        ]);

        $certificate = $issuanceService->issueFromDocumentRequest(
            $documentRequest,
            null,
            $validated
        );

        $stored = $issuanceService->storePdf($certificate);

        $documentRequest->update([
            'certificate_id'     => $certificate->id,
            'response_file_path' => $stored['path'],
            'response_file_name' => $stored['filename'],
        ]);

        return redirect()->route('guest.certificate.preview', $certificate->id);
    }

    public function guestCertificatePreview(Certificate $certificate)
    {
        $certificate->load('visitorLog');
        $issuedAt = $certificate->issued_at ?? $certificate->created_at;

        return Inertia::render('Requests/CertificatePreview', [
            'cert' => [
                'id'      => $certificate->id,
                'number'  => $certificate->certificate_no,
                'name'    => $certificate->visitorLog?->visitor_name ?? '—',
                'purpose' => $certificate->visitorLog?->purpose ?? '—',
                'issued'  => $issuedAt->format('F j, Y'),
            ],
            'preview_url'  => route('guest.certificate.download', ['certificate' => $certificate->id, 'inline' => 1]),
            'download_url' => route('guest.certificate.download', $certificate->id),
        ]);
    }

    public function guestCertificateDownload(Certificate $certificate, CertificatePdfService $pdfService)
    {
        $pdf = $pdfService->make($certificate);
        $filename = 'Certificate-' . $certificate->certificate_no . '.pdf';

        return request()->boolean('inline')
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }
}
