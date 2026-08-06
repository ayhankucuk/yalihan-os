<?php

namespace App\Domains\GuestCommunication\Jobs;

use App\Domains\GuestCommunication\Models\GuestWelcomeNotification;
use App\Models\Notification\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SendGuestWelcomeMessageJob
 *
 * GuestCommunication WAVE 1
 *
 * Welcome mesajını Airbnb API üzerinden gönderir.
 * Queue-based with retry mechanism.
 */
class SendGuestWelcomeMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 30;

    public function __construct(
        private readonly GuestWelcomeNotification $notification,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get template
            $template = NotificationTemplate::where('key', $this->notification->getTemplateKey())
                ->where('aktiflik_durumu', 1)
                ->first();

            if (!$template) {
                Log::error('GuestCommunication: Template not found', [
                    'template_key' => $this->notification->getTemplateKey(),
                ]);
                return;
            }

            // Render message
            $message = $this->renderMessage($template);

            // Send via channel
            $this->sendViaChannel($message);

            // Update audit
            $this->updateDeliveryAudit('sent');

            Log::info('GuestCommunication: Welcome message sent', [
                'reservation_id' => $this->notification->getReservationId(),
                'language' => $this->notification->getLanguage(),
                'channel' => $this->notification->getChannel(),
            ]);

        } catch (\Throwable $e) {
            Log::error('GuestCommunication: Failed to send welcome message', [
                'reservation_id' => $this->notification->getReservationId(),
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e; // Re-throw for retry
        }
    }

    /**
     * Render message from template
     */
    private function renderMessage(NotificationTemplate $template): array
    {
        $data = $this->notification->getData();

        $subject = $this->replacePlaceholders($template->subject, $data);
        $content = $this->replacePlaceholders($template->content, $data);

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }

    /**
     * Replace {{ placeholder }} with data values
     */
    private function replacePlaceholders(string $text, array $data): string
    {
        $rendered = $text;

        foreach ($data as $key => $value) {
            $rendered = str_replace("{{ {$key} }}", (string) $value, $rendered);
        }

        return $rendered;
    }

    /**
     * Send message via appropriate channel
     */
    private function sendViaChannel(array $message): void
    {
        $channel = $this->notification->getChannel();

        match ($channel) {
            'airbnb' => $this->sendViaAirbnb($message),
            'whatsapp' => $this->sendViaWhatsApp($message),
            'email' => $this->sendViaEmail($message),
            default => Log::warning("GuestCommunication: Unknown channel {$channel}"),
        };
    }

    /**
     * Send via Airbnb API
     */
    private function sendViaAirbnb(array $message): void
    {
        // WAVE 1: Log only - actual API integration in future waves
        Log::channel('guest_communication')->info('Airbnb API: Message prepared', [
            'reservation_id' => $this->notification->getReservationId(),
            'recipient' => $this->notification->getRecipient(),
            'subject' => $message['subject'],
            'preview' => substr($message['content'], 0, 100) . '...',
        ]);

        // TODO (WAVE 2+): Airbnb API integration
        // $response = Http::withToken(config('services.airbnb.access_token'))
        //     ->post(config('services.airbnb.api_url') . '/messages', [
        //         'reservation_id' => $this->notification->getReservationId(),
        //         'message' => $message['content'],
        //     ]);
    }

    /**
     * Send via WhatsApp
     */
    private function sendViaWhatsApp(array $message): void
    {
        Log::channel('guest_communication')->info('WhatsApp: Message prepared', [
            'reservation_id' => $this->notification->getReservationId(),
            'recipient' => $this->notification->getRecipient(),
        ]);

        // TODO (WAVE 2+): WhatsApp API integration
    }

    /**
     * Send via Email
     */
    private function sendViaEmail(array $message): void
    {
        Log::channel('guest_communication')->info('Email: Message prepared', [
            'reservation_id' => $this->notification->getReservationId(),
            'recipient' => $this->notification->getRecipient(),
            'subject' => $message['subject'],
        ]);

        // TODO (WAVE 2+): Email integration
    }

    /**
     * Update delivery audit
     */
    private function updateDeliveryAudit(string $status): void
    {
        Log::channel('guest_communication')->info('Delivery Audit Updated', [
            'type' => 'welcome',
            'reservation_id' => $this->notification->getReservationId(),
            'property_id' => $this->notification->getPropertyId(),
            'tenant_id' => $this->notification->getTenantId(),
            'status' => $status,
            'sent_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GuestCommunication: Welcome message job failed after all retries', [
            'reservation_id' => $this->notification->getReservationId(),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        $this->updateDeliveryAudit('failed');
    }
}
