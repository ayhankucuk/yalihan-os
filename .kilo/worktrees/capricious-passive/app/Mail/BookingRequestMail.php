<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Booking Request Mail
 *
 * Context7 Standardı: C7-BOOKING-MAIL-2025-11-05
 *
 * Rezervasyon talebi için email gönderir
 */
class BookingRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $bookingData;

    public $villa;

    /**
     * Create a new message instance.
     */
    public function __construct($villa, array $bookingData)
    {
        $this->villa = $villa;
        $this->bookingData = $bookingData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yeni Rezervasyon Talebi - '.($this->villa->baslik ?? 'Villa'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-request',
            with: [
                'villa' => $this->villa,
                'booking' => $this->bookingData,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
