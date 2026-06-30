<?php

namespace App\Listeners;

use App\Events\IlanCreated;
use App\Mail\NewIlanCreated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Send email notification when new ilan is created
 *
 * Event-driven listener for IlanCreated event
 * Sends email to admin with ilan details
 */
class SendEmailOnIlanCreated
{
    /**
     * Handle the event.
     */
    public function handle(IlanCreated $event): void
    {
        try {
            // Get admin email from config
            $adminEmail = config('mail.admin_email', 'admin@yalihan.com');

            // Send email
            Mail::to($adminEmail)->send(new NewIlanCreated($event->ilan));

            Log::info('New ilan email notification sent', [
                'ilan_id' => $event->ilan->id,
                'ilan_baslik' => $event->ilan->baslik,
                'recipient' => $adminEmail,
            ]);

        } catch (\Exception $e) {
            // Don't fail the request if email fails
            Log::error('Failed to send ilan notification email', [
                'ilan_id' => $event->ilan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
