<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagerDeletionAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $itemType,
        public string $label,
        public string $deletedBy,
        public string $deletedAt,
        public string $recoverableUntil,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'muSharp deletion alert: '.$this->label,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.manager-deletion-alert',
        );
    }
}
