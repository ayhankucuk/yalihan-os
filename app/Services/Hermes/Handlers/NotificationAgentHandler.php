<?php

namespace App\Services\Hermes\Handlers;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use Illuminate\Support\Facades\Log;

/**
 * NotificationAgentHandler
 *
 * Placeholder handler for notification events.
 * Logs that a notification would be sent but does NOT make real API calls.
 *
 * This handler implements the SAB write authority pattern:
 * - No direct financial mutations
 * - No tenant isolation violations
 * - No external API calls (yet)
 */
class NotificationAgentHandler implements HermesHandlerContract
{
    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            'portfolio.created',
            'ilan.created',
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(HermesEventContract $event): array
    {
        $payload = $event->toPayload();

        // Log notification would be sent (no real API call)
        Log::info("[NotificationAgentHandler] Notification would be sent", [
            'event' => $event->eventName(),
            'tenant_id' => $event->tenantId(),
            'payload' => $payload,
            'message' => $this->buildNotificationMessage($event),
        ]);

        return [
            'handler' => self::class,
            'notification_type' => 'portfolio_created',
            'would_send' => true,
            'message' => $this->buildNotificationMessage($event),
            'recipient' => $this->getRecipient($event),
        ];
    }

    /**
     * @inheritDoc
     */
    public function isAsync(): bool
    {
        return false;
    }

    /**
     * Build notification message from event
     */
    private function buildNotificationMessage(HermesEventContract $event): string
    {
        $payload = $event->toPayload();
        $ilanBaslik = $payload['ilan_baslik'] ?? 'Bilinmeyen İlan';
        $ilanId = $payload['ilan_id'] ?? 'N/A';

        return sprintf(
            "Yeni portföy oluşturuldu: %s (ID: %s)",
            $ilanBaslik,
            $ilanId
        );
    }

    /**
     * Get notification recipient (placeholder)
     */
    private function getRecipient(HermesEventContract $event): ?string
    {
        $tenantId = $event->tenantId();

        // In future, this would look up the tenant's notification preferences
        // For now, return null (no real recipient)
        return null;
    }
}
