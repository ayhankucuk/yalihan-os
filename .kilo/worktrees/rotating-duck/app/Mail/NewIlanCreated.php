<?php

namespace App\Mail;

use App\Models\Ilan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewIlanCreated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Ilan $ilan
    ) {}

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("🏠 Yeni İlan: {$this->ilan->baslik}")
                    ->view('emails.ilan-created');
    }
}
