<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerLoginLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $token,
        public readonly User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yalıhan - Mülk Sahibi Paneli Giriş Bağlantısı',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.owner.login-link',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
