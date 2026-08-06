<?php

namespace App\Domains\GuestCommunication\Services;

use App\Domains\GuestCommunication\Adapters\AirbnbDeliveryAdapter;
use App\Domains\GuestCommunication\Contracts\DeliveryResult;
use App\Domains\GuestCommunication\Contracts\DeliveryStatus;
use App\Domains\GuestCommunication\Models\GuestWelcomeNotification;
use App\Models\PropertyReservation;
use Illuminate\Support\Facades\Log;

/**
 * GuestDeliveryAuditService
 *
 * EX-001 WAVE 2 — Delivery Audit
 *
 * Her gönderim denemesini (başarılı veya başarısız) audit eder.
 */
class GuestDeliveryAuditService
{
    public function __construct(
        private readonly ?AirbnbDeliveryAdapter $adapter = null,
    ) {
        $this->adapter = $adapter ?? new AirbnbDeliveryAdapter();
    }

    /**
     * Record a delivery attempt
     */
    public function recordDeliveryAttempt(
        DeliveryResult $result,
        GuestWelcomeNotification $notification,
        string $idempotencyKey,
        ?string $correlationId = null,
    ): void {
        $data = [
            'type' => 'welcome',
            'tenant_id' => $notification->getTenantId(),
            'reservation_id' => $notification->getReservationId(),
            'property_id' => $notification->getPropertyId(),
            'channel' => $notification->getChannel(),
            'message_type' => 'welcome',
            'language' => $notification->getLanguage(),
            'idempotency_key' => $idempotencyKey,
            'correlation_id' => $correlationId ?? $this->generateCorrelationId(),
            'status' => $result->status->value,
            'is_success' => $result->status->isSuccess(),
            'external_message_id' => $result->externalId,
            'error_message' => $result->errorMessage,
            'is_retryable' => $result->retryable,
            'attempt_count' => 1,
            'sent_at' => now()->toIso8601String(),
        ];

        Log::channel('guest_communication')->info('Delivery Audit', $data);

        // @todo: Persist to database in WAVE 2+
        // GuestDeliveryAudit::create($data);
    }

    /**
     * Record a successful delivery
     */
    public function recordSuccess(
        DeliveryResult $result,
        GuestWelcomeNotification $notification,
        string $idempotencyKey,
    ): void {
        $this->recordDeliveryAttempt($result, $notification, $idempotencyKey);
    }

    /**
     * Record a failed delivery
     */
    public function recordFailure(
        DeliveryResult $result,
        GuestWelcomeNotification $notification,
        string $idempotencyKey,
        int $attemptCount = 1,
    ): void {
        $data = [
            'type' => 'welcome',
            'tenant_id' => $notification->getTenantId(),
            'reservation_id' => $notification->getReservationId(),
            'property_id' => $notification->getPropertyId(),
            'channel' => $notification->getChannel(),
            'message_type' => 'welcome',
            'language' => $notification->getLanguage(),
            'idempotency_key' => $idempotencyKey,
            'correlation_id' => $this->generateCorrelationId(),
            'status' => $result->status->value,
            'is_success' => false,
            'external_message_id' => null,
            'error_message' => $result->errorMessage,
            'is_retryable' => $result->retryable,
            'attempt_count' => $attemptCount,
            'sent_at' => null,
        ];

        Log::channel('guest_communication')->warning('Delivery Audit - Failure', $data);
    }

    /**
     * Record a duplicate delivery
     */
    public function recordDuplicate(
        string $existingMessageId,
        GuestWelcomeNotification $notification,
        string $idempotencyKey,
    ): void {
        $data = [
            'type' => 'welcome',
            'tenant_id' => $notification->getTenantId(),
            'reservation_id' => $notification->getReservationId(),
            'property_id' => $notification->getPropertyId(),
            'channel' => $notification->getChannel(),
            'message_type' => 'welcome',
            'language' => $notification->getLanguage(),
            'idempotency_key' => $idempotencyKey,
            'correlation_id' => $this->generateCorrelationId(),
            'status' => DeliveryStatus::DUPLICATE->value,
            'is_success' => true, // Duplicate is OK
            'external_message_id' => $existingMessageId,
            'error_message' => null,
            'is_retryable' => false,
            'attempt_count' => 0,
            'sent_at' => null,
        ];

        Log::channel('guest_communication')->info('Delivery Audit - Duplicate', $data);
    }

    /**
     * Generate correlation ID for tracing
     */
    private function generateCorrelationId(): string
    {
        return sprintf(
            'gc_%s_%s',
            now()->format('YmdHis'),
            bin2hex(random_bytes(6))
        );
    }
}
