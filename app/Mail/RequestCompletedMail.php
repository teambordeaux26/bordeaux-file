<?php

namespace App\Mail;

use App\Models\DocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class RequestCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DocumentRequest $documentRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Request Has Been Completed — '.$this->documentRequest->tracking_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.request-completed',
        );
    }

    public function attachments(): array
    {
        if (! $this->documentRequest->response_file_path) {
            return [];
        }

        $path = Storage::disk('public')->path($this->documentRequest->response_file_path);
        $name = $this->documentRequest->response_file_name ?? 'requested-document';

        return [
            Attachment::fromPath($path)->as($name),
        ];
    }
}
