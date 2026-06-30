<?php

namespace App\Services\Hermes\Handlers;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Domain\Hermes\Enums\HermesEventVocabulary;
use Illuminate\Support\Facades\Log;

/**
 * GovernanceNotificationHandler
 *
 * Governance kararlarını log/bildirim seviyesinde kaydeder.
 * Karar verilerini analiz eder ve önemli kararları vurgular.
 *
 * No external API calls (stub for future webhook/email).
 * No financial mutations.
 * No tenant isolation violations.
 */
class GovernanceNotificationHandler implements HermesHandlerContract
{
    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            HermesEventVocabulary::GOVERNANCE_DECISION_MADE->value,
            HermesEventVocabulary::GOVERNANCE_FINDING_SUPPRESSED->value,
            HermesEventVocabulary::GOVERNANCE_ROLLBACK_EXECUTED->value,
            HermesEventVocabulary::GOVERNANCE_OVERRIDE_APPLIED->value,
            HermesEventVocabulary::GOVERNANCE_ACTION_FAILED->value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(HermesEventContract $event): array
    {
        $eventName = $event->eventName();
        $payload = $event->toPayload();

        // Determine severity and type
        [$severity, $type] = $this->classifyEvent($eventName, $payload);

        // Log based on severity
        $this->logBySeverity($severity, $eventName, $payload);

        return [
            'handler' => self::class,
            'event' => $eventName,
            'severity' => $severity,
            'type' => $type,
            'logged' => true,
            'tenant_id' => $event->tenantId(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function isAsync(): bool
    {
        return false; // Sync for governance visibility
    }

    /**
     * Classify governance event by type and severity
     */
    private function classifyEvent(string $eventName, array $payload): array
    {
        return match ($eventName) {
            HermesEventVocabulary::GOVERNANCE_ACTION_FAILED->value => ['critical', 'action_failed'],
            HermesEventVocabulary::GOVERNANCE_ROLLBACK_EXECUTED->value => ['high', 'rollback'],
            HermesEventVocabulary::GOVERNANCE_OVERRIDE_APPLIED->value => ['high', 'override'],
            HermesEventVocabulary::GOVERNANCE_DECISION_MADE->value => $this->classifyDecision($payload),
            HermesEventVocabulary::GOVERNANCE_FINDING_SUPPRESSED->value => ['medium', 'suppression'],
            default => ['low', 'unknown'],
        };
    }

    /**
     * Classify decision event by bucket
     */
    private function classifyDecision(array $payload): array
    {
        $bucket = $payload['metadata']['bucket'] ?? 'unknown';

        return match ($bucket) {
            'blocked' => ['high', 'decision_blocked'],
            'needs_review' => ['medium', 'decision_review'],
            'auto_run' => ['low', 'decision_auto'],
            default => ['low', 'decision_unknown'],
        };
    }

    /**
     * Log event based on severity level
     */
    private function logBySeverity(string $severity, string $eventName, array $payload): void
    {
        $logContext = [
            'event' => $eventName,
            'tenant_id' => $payload['tenant_id'] ?? null,
            'payload' => $payload,
        ];

        match ($severity) {
            'critical' => Log::critical("[Governance] {$this->getMessage($eventName)}", $logContext),
            'high' => Log::error("[Governance] {$this->getMessage($eventName)}", $logContext),
            'medium' => Log::warning("[Governance] {$this->getMessage($eventName)}", $logContext),
            default => Log::info("[Governance] {$this->getMessage($eventName)}", $logContext),
        };
    }

    /**
     * Get human-readable message for event
     */
    private function getMessage(string $eventName): string
    {
        return match ($eventName) {
            HermesEventVocabulary::GOVERNANCE_DECISION_MADE->value => 'Governance kararı verildi',
            HermesEventVocabulary::GOVERNANCE_FINDING_SUPPRESSED->value => 'Governance: Bulgı bastırıldı',
            HermesEventVocabulary::GOVERNANCE_ROLLBACK_EXECUTED->value => 'Governance: Geri alma çalıştı',
            HermesEventVocabulary::GOVERNANCE_OVERRIDE_APPLIED->value => 'Governance: Override uygulandı',
            HermesEventVocabulary::GOVERNANCE_ACTION_FAILED->value => 'Governance: Eylem başarısız',
            default => 'Governance event',
        };
    }
}
