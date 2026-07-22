<?php

namespace App\Mail;

use App\Models\Certificate;
use App\Models\DocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CertificateOfAppearanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DocumentRequest $documentRequest,
        public Certificate $certificate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Certificate of Appearance — '.$this->documentRequest->tracking_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.certificate-of-appearance',
        );
    }

    public function attachments(): array
    {
        $path = $this->documentRequest->response_file_path;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return [];
        }

        return [
            Attachment::fromPath(Storage::disk('public')->path($path))
                ->as($this->documentRequest->response_file_name ?? 'Certificate-of-Appearance.pdf'),
        ];
    }
}
