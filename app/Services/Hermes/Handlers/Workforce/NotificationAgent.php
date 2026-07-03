<?php

namespace App\Services\Hermes\Handlers\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Domain\Hermes\Enums\HermesWorkforceEventVocabulary;
use App\Models\Hermes\WorkforceExecutionLog;
use Illuminate\Support\Facades\Log;

/**
 * NotificationAgent — AI Workforce Sprint 4.3
 *
 * Triggered by: workforce.notification_requested
 * Role: Sends final notification when workforce chain completes
 *
 * No external API calls (vertical slice — stub implementation).
 * Production: would call TelegramNotificationHandler or real notification service.
 */
class NotificationAgent implements HermesHandlerContract
{
    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            HermesWorkforceEventVocabulary::WORKFORCE_NOTIFICATION_REQUESTED->value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(HermesEventContract $event): array
    {
        $startTime = microtime(true);
        $payload = $event->toPayload();
        $ilanId = $payload['ilan_id'] ?? null;
        $tenantId = $event->tenantId();
        $chainId = $payload['chain_id'] ?? null;
        $portfolioAnalysis = $payload['portfolio_analysis'] ?? [];

        // Record execution
        $execLog = WorkforceExecutionLog::create([
            'ilan_id' => $ilanId,
            'tenant_id' => $tenantId,
            'chain_id' => $chainId,
            'agent_name' => 'notification_agent',
            'agent_class' => self::class,
            'event_received' => $event->eventName(),
            'event_chain_step' => 3,
            'input_payload' => $payload,
            'output_payload' => [],
            'status' => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $ilanBaslik = $portfolioAnalysis['ilan_baslik'] ?? $payload['ilan_baslik'] ?? 'Bilinmeyen İlan';
            $tier = $portfolioAnalysis['tier'] ?? 'standard';
            $agentsTriggered = $payload['all_agents_triggered'] ?? false;

            // Build notification message
            $notification = $this->buildWorkforceNotification(
                ilanId: $ilanId,
                ilanBaslik: $ilanBaslik,
                tier: $tier,
                chainId: $chainId,
                agentsTriggered: $agentsTriggered,
            );

            // Log notification (stub — no real API call in vertical slice)
            Log::info('[NotificationAgent] Workforce chain notification', [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'message' => $notification['body'],
                'tier' => $tier,
                'channel' => 'log', // Stub channel
            ]);

            $result = [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'notification' => $notification,
                'sent_at' => now()->toIso8601String(),
                'channel' => 'log', // Stub
                'status' => 'sent',
            ];

            $execLog->markCompleted($result);

            // Check if chain is complete
            $chainComplete = WorkforceExecutionLog::isChainComplete($chainId ?? '');

            if ($chainComplete) {
                Log::info('[NotificationAgent] AI workforce chain COMPLETE', [
                    'chain_id' => $chainId,
                    'ilan_id' => $ilanId,
                ]);
            }

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'notification' => $notification,
                'chain_complete' => $chainComplete,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $execLog->markFailed($e->getMessage());

            Log::error('[NotificationAgent] Notification failed', [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'error' => $e->getMessage(),
            ]);

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function isAsync(): bool
    {
        return false;
    }

    /**
     * @return array{title: string, body: string, priority: string, channels: array<string>}
     */
    private function buildWorkforceNotification(
        ?int $ilanId,
        string $ilanBaslik,
        string $tier,
        ?string $chainId,
        bool $agentsTriggered,
    ): array {
        $emoji = match ($tier) {
            'luxury' => '🏛️',
            'premium' => '✨',
            'standard' => '📋',
            'budget' => '🏠',
            default => '📋',
        };

        $title = "{$emoji} AI Zincir Tamamlandı — {$ilanBaslik}";
        $body = implode("\n", array_filter([
            "Portföy ID: {$ilanId}",
            "Segment: " . ucfirst($tier),
            "Zincir ID: {$chainId}",
            $agentsTriggered ? 'Tüm ajanlar tetiklendi: Fotoğraf, Açıklama, Bildirim' : null,
            '',
            'AI Workforce Sprint 4.3 Vertical Slice — Tamamlandı ✅',
        ]));

        return [
            'title' => $title,
            'body' => $body,
            'priority' => $tier === 'luxury' || $tier === 'premium' ? 'high' : 'normal',
            'channels' => ['log', 'telegram_stub'],
            'tier' => $tier,
        ];
    }
}
