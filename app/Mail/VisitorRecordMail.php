<?php

namespace App\Mail;

use App\Models\VisitorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VisitorRecordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public VisitorLog $visitor) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your visit record — Office of the Vice Mayor, Oas',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.visitor-record',
        );
    }
}
