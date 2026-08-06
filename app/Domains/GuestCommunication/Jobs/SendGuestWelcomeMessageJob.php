<?php

namespace App\Domains\GuestCommunication\Jobs;

use App\Domains\GuestCommunication\Adapters\AirbnbDeliveryAdapter;
use App\Domains\GuestCommunication\Contracts\DeliveryResult;
use App\Domains\GuestCommunication\Contracts\DeliveryStatus;
use App\Domains\GuestCommunication\Models\GuestWelcomeNotification;
use App\Domains\GuestCommunication\Services\GuestCommunicationFeatureFlags;
use App\Domains\GuestCommunication\Services\GuestDeliveryAuditService;
use App\Models\Notification\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendGuestWelcomeMessageJob
 *
 * EX-001 WAVE 2 — Airbnb Delivery Integration
 *
 * Welcome mesajını Airbnb API üzerinden gönderir.
 * Feature flag, idempotency, retry ve audit ile.
 */
class SendGuestWelcomeMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 30;

    private ?AirbnbDeliveryAdapter $adapter = null;

    private ?GuestDeliveryAuditService $auditService = null;

    private ?GuestCommunicationFeatureFlags $featureFlags = null;

    public function __construct(
        private readonly GuestWelcomeNotification $notification,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Feature flags
            $this->checkFeatureFlags();

            // Create idempotency key
            $idempotencyKey = $this->createIdempotencyKey();

            // Get template
            $template = $this->getTemplate();

            // Render message
            $messageData = $this->renderMessage($template);

            // Send via adapter
            $result = $this->sendViaAdapter($messageData, $idempotencyKey);

            // Handle result
            $this->handleResult($result, $idempotencyKey);

        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Check feature flags
     */
    private function checkFeatureFlags(): void
    {
        $flags = $this->getFeatureFlags();

        // Global check
        if (!$flags->isEnabled()) {
            Log::channel('guest_communication')->info('GuestCommunication: Disabled by feature flag', [
                'reservation_id' => $this->notification->getReservationId(),
            ]);
            $this->markAsSkipped('Feature flag disabled');
            return;
        }

        // Channel check
        if (!$flags->isAirbnbEnabled()) {
            Log::channel('guest_communication')->info('GuestCommunication: Airbnb channel disabled', [
                'reservation_id' => $this->notification->getReservationId(),
            ]);
            $this->markAsSkipped('Airbnb channel disabled');
            return;
        }

        // Welcome message check
        if (!$flags->isWelcomeEnabled()) {
            Log::channel('guest_communication')->info('GuestCommunication: Welcome messages disabled', [
                'reservation_id' => $this->notification->getReservationId(),
            ]);
            $this->markAsSkipped('Welcome messages disabled');
            return;
        }

        // Pilot check
        if (!$flags->isPilotEnabled(
            $this->notification->getTenantId(),
            $this->notification->getPropertyId()
        )) {
            Log::channel('guest_communication')->info('GuestCommunication: Not in pilot scope', [
                'reservation_id' => $this->notification->getReservationId(),
                'tenant_id' => $this->notification->getTenantId(),
                'property_id' => $this->notification->getPropertyId(),
            ]);
            $this->markAsSkipped('Not in pilot scope');
            return;
        }
    }

    /**
     * Create idempotency key
     */
    private function createIdempotencyKey(): string
    {
        $adapter = $this->getAdapter();

        return $adapter->createIdempotencyKey(
            $this->notification->getReservationId(),
            'welcome'
        );
    }

    /**
     * Get template
     */
    private function getTemplate(): NotificationTemplate
    {
        $template = NotificationTemplate::where('key', $this->notification->getTemplateKey())
            ->where('aktiflik_durumu', 1)
            ->first();

        if (!$template) {
            throw new \RuntimeException(
                "Template not found: {$this->notification->getTemplateKey()}"
            );
        }

        return $template;
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
            ...$data,
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
     * Send via adapter
     */
    private function sendViaAdapter(array $messageData, string $idempotencyKey): DeliveryResult
    {
        $adapter = $this->getAdapter();

        return $adapter->sendWelcomeMessage($messageData);
    }

    /**
     * Handle delivery result
     */
    private function handleResult(DeliveryResult $result, string $idempotencyKey): void
    {
        $audit = $this->getAuditService();

        match ($result->status) {
            DeliveryStatus::SENT => $this->handleSent($result, $idempotencyKey, $audit),
            DeliveryStatus::DUPLICATE => $this->handleDuplicate($result, $idempotencyKey, $audit),
            DeliveryStatus::RATE_LIMITED => $this->handleRateLimited($result, $idempotencyKey, $audit),
            DeliveryStatus::INVALID_CREDENTIALS => $this->handleInvalidCredentials($result, $audit),
            DeliveryStatus::FAILED => $this->handleFailed($result, $audit),
        };
    }

    /**
     * Handle successful delivery
     */
    private function handleSent(
        DeliveryResult $result,
        string $idempotencyKey,
        GuestDeliveryAuditService $audit
    ): void {
        $audit->recordSuccess($result, $this->notification, $idempotencyKey);

        Log::channel('guest_communication')->info('GuestCommunication: Message sent', [
            'reservation_id' => $this->notification->getReservationId(),
            'external_id' => $result->externalId,
        ]);
    }

    /**
     * Handle duplicate delivery
     */
    private function handleDuplicate(
        DeliveryResult $result,
        string $idempotencyKey,
        GuestDeliveryAuditService $audit
    ): void {
        $audit->recordDuplicate(
            $result->externalId ?? $idempotencyKey,
            $this->notification,
            $idempotencyKey
        );

        Log::channel('guest_communication')->info('GuestCommunication: Duplicate skipped', [
            'reservation_id' => $this->notification->getReservationId(),
            'existing_id' => $result->externalId,
        ]);
    }

    /**
     * Handle rate limited
     */
    private function handleRateLimited(
        DeliveryResult $result,
        string $idempotencyKey,
        GuestDeliveryAuditService $audit
    ): void {
        $audit->recordFailure($result, $this->notification, $idempotencyKey, $this->attempts());

        // Rate limited = retryable
        if ($this->attempts() < $this->tries) {
            $this->release($this->calculateBackoff());

            Log::channel('guest_communication')->warning('GuestCommunication: Rate limited, retrying', [
                'reservation_id' => $this->notification->getReservationId(),
                'attempt' => $this->attempts(),
                'backoff' => $this->calculateBackoff(),
            ]);
        }
    }

    /**
     * Handle invalid credentials
     */
    private function handleInvalidCredentials(
        DeliveryResult $result,
        GuestDeliveryAuditService $audit
    ): void {
        $audit->recordFailure($result, $this->notification, '', $this->attempts());

        Log::channel('guest_communication')->error('GuestCommunication: Invalid credentials', [
            'reservation_id' => $this->notification->getReservationId(),
            'tenant_id' => $this->notification->getTenantId(),
            'error' => $result->errorMessage,
        ]);

        // Invalid credentials = not retryable
        $this->fail($result->errorMessage ?? 'Invalid credentials');
    }

    /**
     * Handle failed delivery
     */
    private function handleFailed(
        DeliveryResult $result,
        GuestDeliveryAuditService $audit
    ): void {
        $audit->recordFailure($result, $this->notification, '', $this->attempts());

        // Check retryable
        if ($result->retryable && $this->attempts() < $this->tries) {
            $this->release($this->calculateBackoff());

            Log::channel('guest_communication')->warning('GuestCommunication: Failed, retrying', [
                'reservation_id' => $this->notification->getReservationId(),
                'attempt' => $this->attempts(),
                'error' => $result->errorMessage,
            ]);
        } else {
            Log::channel('guest_communication')->error('GuestCommunication: Failed permanently', [
                'reservation_id' => $this->notification->getReservationId(),
                'error' => $result->errorMessage,
                'attempts' => $this->attempts(),
            ]);

            // Max retries reached or not retryable
            $this->fail($result->errorMessage ?? 'Delivery failed');
        }
    }

    /**
     * Handle exception
     */
    private function handleException(\Throwable $e): void
    {
        $idempotencyKey = $this->createIdempotencyKey();

        $result = DeliveryResult::failed($e->getMessage(), true);
        $audit = $this->getAuditService();
        $audit->recordFailure($result, $this->notification, $idempotencyKey, $this->attempts());

        Log::channel('guest_communication')->error('GuestCommunication: Exception', [
            'reservation_id' => $this->notification->getReservationId(),
            'error' => $e->getMessage(),
            'attempt' => $this->attempts(),
        ]);

        // Re-throw for retry
        throw $e;
    }

    /**
     * Calculate backoff based on attempt
     */
    private function calculateBackoff(): int
    {
        $flags = $this->getFeatureFlags();

        return $flags->getRetryBackoff() * $this->attempts();
    }

    /**
     * Mark job as skipped
     */
    private function markAsSkipped(string $reason): void
    {
        Log::channel('guest_communication')->info('GuestCommunication: Skipped', [
            'reservation_id' => $this->notification->getReservationId(),
            'reason' => $reason,
        ]);

        // Stop job without error
        $this->delete();
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $idempotencyKey = $this->createIdempotencyKey();

        $result = DeliveryResult::failed($exception->getMessage(), false);
        $audit = $this->getAuditService();
        $audit->recordFailure($result, $this->notification, $idempotencyKey, $this->attempts());

        Log::channel('guest_communication')->error('GuestCommunication: Job failed permanently', [
            'reservation_id' => $this->notification->getReservationId(),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    // ========================================================================
    // Dependency Injection
    // ========================================================================

    private function getAdapter(): AirbnbDeliveryAdapter
    {
        $this->adapter ??= new AirbnbDeliveryAdapter();

        return $this->adapter;
    }

    private function getAuditService(): GuestDeliveryAuditService
    {
        $this->auditService ??= new GuestDeliveryAuditService();

        return $this->auditService;
    }

    private function getFeatureFlags(): GuestCommunicationFeatureFlags
    {
        $this->featureFlags ??= new GuestCommunicationFeatureFlags();

        return $this->featureFlags;
    }
}
