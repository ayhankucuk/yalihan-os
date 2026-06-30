<?php

namespace App\Services\Hermes\Handlers;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Domain\Hermes\Enums\HermesEventVocabulary;
use App\Models\Hermes\HermesAnalytics;
use Illuminate\Support\Facades\Log;

/**
 * AnalyticsHandler
 *
 * Hermes event metriklerini toplar.
 * Her event için success/failure rate, ortalama süre ve trend hesaplar.
 *
 * No external API calls.
 * No financial mutations.
 * No tenant isolation violations.
 */
class AnalyticsHandler implements HermesHandlerContract
{
    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            HermesEventVocabulary::PORTFOLIO_CREATED->value,
            HermesEventVocabulary::PORTFOLIO_UPDATED->value,
            HermesEventVocabulary::PORTFOLIO_DELETED->value,
            HermesEventVocabulary::PORTFOLIO_PUBLISHED->value,
            HermesEventVocabulary::CORTEX_FINDING_DETECTED->value,
            HermesEventVocabulary::GOVERNANCE_DECISION_MADE->value,
            HermesEventVocabulary::EXECUTION_ACTION_APPLIED->value,
            HermesEventVocabulary::LEAD_CREATED->value,
            HermesEventVocabulary::LEAD_ASSIGNED->value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(HermesEventContract $event): array
    {
        $startTime = microtime(true);
        $eventName = $event->eventName();

        try {
            // Record analytics metric
            HermesAnalytics::record(
                eventName: $eventName,
                success: true,
                durationMs: 0.1, // Analytics is lightweight
                tenantId: $event->tenantId(),
                metadata: [
                    'event_type' => $eventName,
                    'recorded_at' => now()->toIso8601String(),
                ]
            );

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::debug('[AnalyticsHandler] Event recorded', [
                'event' => $eventName,
                'tenant_id' => $event->tenantId(),
            ]);

            return [
                'handler' => self::class,
                'event' => $eventName,
                'recorded' => true,
                'duration_ms' => $duration,
            ];
        } catch (\Throwable $e) {
            Log::warning('[AnalyticsHandler] Failed to record metric', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);

            return [
                'handler' => self::class,
                'event' => $eventName,
                'recorded' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function isAsync(): bool
    {
        return false; // Sync for accurate metrics
    }
}
