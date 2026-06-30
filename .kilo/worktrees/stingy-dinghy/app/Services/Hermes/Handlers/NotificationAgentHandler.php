<?php

namespace App\Services\Hermes\Handlers;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Domain\Hermes\Enums\HermesEventVocabulary;
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
            HermesEventVocabulary::PORTFOLIO_CREATED->value,
            HermesEventVocabulary::LEAD_CREATED->value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(HermesEventContract $event): array
    {
        $payload = $event->toPayload();
        $eventName = $event->eventName();

        // Determine notification type from vocabulary
        $notificationType = $this->getNotificationType($eventName);

        // Log notification would be sent (no real API call)
        Log::info("[NotificationAgentHandler] Notification would be sent", [
            'event' => $eventName,
            'notification_type' => $notificationType,
            'tenant_id' => $event->tenantId(),
            'payload' => $payload,
            'message' => $this->buildNotificationMessage($event),
        ]);

        return [
            'handler' => self::class,
            'notification_type' => $notificationType,
            'would_send' => true,
            'message' => $this->buildNotificationMessage($event),
            'recipient' => $this->getRecipient($event),
        ];
    }

    /**
     * Determine notification type from event vocabulary
     */
    private function getNotificationType(string $eventName): string
    {
        return match ($eventName) {
            HermesEventVocabulary::PORTFOLIO_CREATED->value => 'portfolio_created',
            HermesEventVocabulary::PORTFOLIO_UPDATED->value => 'portfolio_updated',
            HermesEventVocabulary::LEAD_CREATED->value => 'lead_created',
            HermesEventVocabulary::LEAD_ASSIGNED->value => 'lead_assigned',
            HermesEventVocabulary::GOVERNANCE_DECISION_MADE->value => 'governance_decision',
            HermesEventVocabulary::EXECUTION_ACTION_APPLIED->value => 'execution_result',
            default => 'generic_notification',
        };
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
