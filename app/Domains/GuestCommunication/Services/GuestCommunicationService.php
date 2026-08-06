<?php

namespace App\Domains\GuestCommunication\Services;

use App\Domains\GuestCommunication\Models\GuestWelcomeNotification;
use App\Events\Reservation\ReservationConfirmedEvent;
use App\Models\Notification\NotificationTemplate;
use App\Models\PropertyReservation;
use Illuminate\Support\Facades\Log;

/**
 * GuestCommunicationService
 *
 * GuestCommunication WAVE 1
 *
 * Misafir iletişimini yöneten ana servis.
 * ReservationConfirmedEvent'i dinler ve welcome mesajı gönderir.
 */
class GuestCommunicationService
{
    public function __construct(
        private readonly LanguageResolver $languageResolver,
    ) {}

    /**
     * Handle ReservationConfirmed event - send welcome message
     */
    public function handleReservationConfirmed(ReservationConfirmedEvent $event): void
    {
        try {
            // Load reservation with relations
            $reservation = PropertyReservation::with('ilan')
                ->where('id', $event->reservationId)
                ->where('tenant_id', $event->tenantId)
                ->first();

            if (!$reservation) {
                Log::warning('GuestCommunication: Reservation not found', [
                    'reservation_id' => $event->reservationId,
                    'tenant_id' => $event->tenantId,
                ]);
                return;
            }

            // Check if guest communication is enabled for this property
            if (!$this->isGuestCommunicationEnabled($reservation)) {
                Log::info('GuestCommunication: Disabled for property', [
                    'property_id' => $event->propertyId,
                ]);
                return;
            }

            // Resolve language
            $language = $this->languageResolver->resolveFromReservation($reservation);

            // Create welcome notification
            $notification = GuestWelcomeNotification::fromReservation($reservation, $language);

            // Send via queue
            $this->sendWelcomeMessage($notification);

            Log::info('GuestCommunication: Welcome message queued', [
                'reservation_id' => $event->reservationId,
                'language' => $language,
                'channel' => $notification->getChannel(),
            ]);

        } catch (\Throwable $e) {
            Log::error('GuestCommunication: Failed to send welcome message', [
                'reservation_id' => $event->reservationId,
                'error' => $e->getMessage(),
            ]);

            // Re-throw for queue retry
            throw $e;
        }
    }

    /**
     * Check if guest communication is enabled for reservation
     */
    private function isGuestCommunicationEnabled(PropertyReservation $reservation): bool
    {
        // Check property/ilan settings
        if ($reservation->ilan) {
            return (bool) ($reservation->ilan->guest_communication_enabled ?? false);
        }

        // Default: enabled
        return true;
    }

    /**
     * Send welcome message via queue
     */
    private function sendWelcomeMessage(GuestWelcomeNotification $notification): void
    {
        // Log for audit trail
        $this->createDeliveryAudit($notification);

        // In WAVE 1: Log the notification
        // Actual sending happens via queue job (GuestMessageQueueJob)
        Log::info('GuestCommunication: Welcome message prepared', [
            'reservation_id' => $notification->getReservationId(),
            'property_id' => $notification->getPropertyId(),
            'tenant_id' => $notification->getTenantId(),
            'language' => $notification->getLanguage(),
            'channel' => $notification->getChannel(),
            'recipient' => $notification->getRecipient(),
            'template_key' => $notification->getTemplateKey(),
            'priority' => $notification->getPriority(),
            'async' => $notification->isAsync(),
            'queued_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Create delivery audit record
     */
    private function createDeliveryAudit(GuestWelcomeNotification $notification): void
    {
        // In WAVE 1: Log to application log
        // Future: Create database record in delivery_audit table
        Log::channel('guest_communication')->info('Delivery Audit', [
            'type' => 'welcome',
            'reservation_id' => $notification->getReservationId(),
            'property_id' => $notification->getPropertyId(),
            'tenant_id' => $notification->getTenantId(),
            'language' => $notification->getLanguage(),
            'channel' => $notification->getChannel(),
            'template_key' => $notification->getTemplateKey(),
            'status' => 'queued',
            'queued_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get welcome message for preview
     */
    public function previewWelcomeMessage(PropertyReservation $reservation, string $language = 'en'): array
    {
        $notification = GuestWelcomeNotification::fromReservation($reservation, $language);

        // Get template
        $template = NotificationTemplate::where('key', $notification->getTemplateKey())
            ->where('aktiflik_durumu', 1)
            ->first();

        if (!$template) {
            return [
                'success' => false,
                'error' => 'Template not found',
                'template_key' => $notification->getTemplateKey(),
            ];
        }

        // Render template with data
        $renderedContent = $this->renderTemplate($template->content, $notification->getData());
        $renderedSubject = $this->renderTemplate($template->subject, $notification->getData());

        return [
            'success' => true,
            'template_key' => $notification->getTemplateKey(),
            'language' => $language,
            'subject' => $renderedSubject,
            'content' => $renderedContent,
            'data' => $notification->getData(),
        ];
    }

    /**
     * Render template with data
     */
    private function renderTemplate(string $template, array $data): string
    {
        $rendered = $template;

        foreach ($data as $key => $value) {
            $rendered = str_replace("{{ {$key} }}", (string) $value, $rendered);
        }

        return $rendered;
    }
}
