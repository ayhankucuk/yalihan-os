<?php

namespace App\Services\Hermes\Handlers\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Domain\Hermes\Enums\HermesWorkforceEventVocabulary;
use App\Models\Hermes\HermesEventLog;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Services\Hermes\HermesService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PortfolioAgent — AI Workforce Sprint 4.3
 *
 * Triggered by: portfolio.created
 * Chain: PortfolioAgent → PhotoAgent → DescriptionAgent → NotificationAgent
 *
 * Responsibilities:
 * - Receive portfolio.created event
 * - Coordinate the AI workforce chain
 * - Record execution metrics
 * - Emit chain events for downstream agents
 */
class PortfolioAgent implements HermesHandlerContract
{
    private ?string $currentChainId = null;

    public function __construct(
        private HermesService $hermesService,
    ) {}

    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            'portfolio.created',
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

        // Generate unique chain ID for this workforce execution
        $chainId = $payload['chain_id'] ?? (string) Str::uuid();

        Log::info('[PortfolioAgent] Starting AI workforce chain', [
            'ilan_id' => $ilanId,
            'tenant_id' => $tenantId,
            'chain_id' => $chainId,
        ]);

        try {
            // ── Step 1: Analyze Portfolio ──────────────────────────────
            $analysisResult = $this->analyzePortfolio($ilanId, $tenantId, $chainId, $event);

            // ── Step 2: Emit PhotoAnalysisRequested ───────────────────
            $this->emitChainEvent(
                eventName: HermesWorkforceEventVocabulary::WORKFORCE_PHOTO_ANALYSIS_REQUESTED->value,
                ilanId: $ilanId,
                tenantId: $tenantId,
                chainId: $chainId,
                payload: array_merge($payload, [
                    'portfolio_analysis' => $analysisResult,
                    'chain_step' => 1,
                ]),
            );

            // ── Step 3: Emit DescriptionAnalysisRequested ─────────────
            $this->emitChainEvent(
                eventName: HermesWorkforceEventVocabulary::WORKFORCE_DESCRIPTION_ANALYSIS_REQUESTED->value,
                ilanId: $ilanId,
                tenantId: $tenantId,
                chainId: $chainId,
                payload: array_merge($payload, [
                    'portfolio_analysis' => $analysisResult,
                    'chain_step' => 2,
                ]),
            );

            // ── Step 4: Emit NotificationRequested ────────────────────
            $this->emitChainEvent(
                eventName: HermesWorkforceEventVocabulary::WORKFORCE_NOTIFICATION_REQUESTED->value,
                ilanId: $ilanId,
                tenantId: $tenantId,
                chainId: $chainId,
                payload: array_merge($payload, [
                    'portfolio_analysis' => $analysisResult,
                    'chain_step' => 3,
                    'all_agents_triggered' => true,
                ]),
            );

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('[PortfolioAgent] AI workforce chain initiated', [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'duration_ms' => $duration,
                'agents_triggered' => ['photo', 'description', 'notification'],
            ]);

            return [
                'handler' => self::class,
                'chain_id' => $chainId,
                'ilan_id' => $ilanId,
                'agents_triggered' => ['photo', 'description', 'notification'],
                'portfolio_analysis' => $analysisResult,
                'chain_initiated_at' => now()->toIso8601String(),
                'duration_ms' => $duration,
            ];
        } catch (\Throwable $e) {
            Log::error('[PortfolioAgent] Workforce chain failed', [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'error' => $e->getMessage(),
            ]);

            return [
                'handler' => self::class,
                'chain_id' => $chainId,
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * Analyze portfolio data (rule-based for vertical slice)
     *
     * @return array<string, mixed>
     */
    private function analyzePortfolio(?int $ilanId, ?int $tenantId, string $chainId, HermesEventContract $event): array
    {
        $payload = $event->toPayload();
        $ilanBaslik = $payload['ilan_baslik'] ?? 'Bilinmeyen İlan';
        $ilanFiyat = $payload['ilan_fiyat'] ?? null;

        // Record execution log
        $execLog = WorkforceExecutionLog::create([
            'ilan_id' => $ilanId,
            'tenant_id' => $tenantId,
            'chain_id' => $chainId,
            'agent_name' => 'portfolio_agent',
            'agent_class' => self::class,
            'event_received' => $event->eventName(),
            'event_chain_step' => 0,
            'input_payload' => $payload,
            'output_payload' => [],
            'status' => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            // Rule-based analysis (production would call AI service)
            $analysis = [
                'ilan_id' => $ilanId,
                'ilan_baslik' => $ilanBaslik,
                'ilan_fiyat' => $ilanFiyat,
                'tier' => $this->classifyTier($ilanFiyat),
                'priority' => $this->classifyPriority($ilanBaslik),
                'features_detected' => $this->detectFeatures($ilanBaslik),
                'completeness_score' => $this->calculateCompleteness($payload),
                'chain_status' => 'analysis_complete',
                'analyzed_at' => now()->toIso8601String(),
            ];

            $execLog->markCompleted($analysis);

            return $analysis;
        } catch (\Throwable $e) {
            $execLog->markFailed($e->getMessage());
            throw $e;
        }
    }

    private function classifyTier(?float $fiyat): string
    {
        if ($fiyat === null) {
            return 'unpriced';
        }
        return match (true) {
            $fiyat < 5_000_000 => 'budget',
            $fiyat < 20_000_000 => 'standard',
            $fiyat < 100_000_000 => 'premium',
            default => 'luxury',
        };
    }

    private function classifyPriority(string $baslik): string
    {
        $lower = mb_strtolower($baslik);
        if (str_contains($lower, 'acil') || str_contains($lower, 'fırsat')) {
            return 'high';
        }
        if (str_contains($lower, 'yatırımlık') || str_contains($lower, 'invest')) {
            return 'medium';
        }
        return 'normal';
    }

    private function detectFeatures(string $baslik): array
    {
        $lower = mb_strtolower($baslik);
        $features = [];

        $keywordMap = [
            'deniz' => 'sea_view',
            'havuz' => 'pool',
            'bahçe' => 'garden',
            'garaj' => 'garage',
            'asansör' => 'elevator',
            'klim' => 'air_conditioning',
            'jakuzi' => 'jacuzzi',
            'teras' => 'terrace',
            'dublex' => 'duplex',
            'müstakil' => 'detached',
        ];

        foreach ($keywordMap as $keyword => $feature) {
            if (str_contains($lower, $keyword)) {
                $features[] = $feature;
            }
        }

        return $features;
    }

    private function calculateCompleteness(array $payload): float
    {
        $fields = ['ilan_id', 'ilan_baslik', 'ilan_fiyat'];
        $present = count(array_filter($fields, fn ($f) => !empty($payload[$f])));
        return round($present / count($fields), 2);
    }

    /**
     * Emit a chain event through Hermes
     */
    private function emitChainEvent(
        string $eventName,
        ?int $ilanId,
        ?int $tenantId,
        string $chainId,
        array $payload,
    ): void {
        // Build anonymous event that implements HermesEventContract
        $event = new class($eventName, $ilanId, $tenantId, $chainId, $payload) implements HermesEventContract
        {
            private \DateTimeImmutable $occurredAt;

            public function __construct(
                private string $eventName,
                private ?int $ilanId,
                private ?int $tenantId,
                private string $chainId,
                private array $payload,
            ) {
                $this->occurredAt = new \DateTimeImmutable();
            }

            public function eventName(): string
            {
                return $this->eventName;
            }

            public function tenantId(): ?int
            {
                return $this->tenantId;
            }

            public function toPayload(): array
            {
                return array_merge($this->payload, [
                    'ilan_id' => $this->ilanId,
                    'tenant_id' => $this->tenantId,
                    'chain_id' => $this->chainId,
                    '_emitted_by' => 'portfolio_agent',
                    '_emitted_at' => now()->toIso8601String(),
                ]);
            }

            public function occurredAt(): \DateTimeImmutable
            {
                return $this->occurredAt;
            }
        };

        $this->hermesService->receive($event);
    }

    /**
     * @inheritDoc
     */
    public function isAsync(): bool
    {
        return false; // Sync: coordinates the chain before returning
    }
}
