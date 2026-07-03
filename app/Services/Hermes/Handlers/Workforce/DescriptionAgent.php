<?php

namespace App\Services\Hermes\Handlers\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Events\Workforce\DescriptionCompleted;
use App\Events\Workforce\PhotoAnalysisCompleted;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Hermes\HermesService;
use Illuminate\Support\Facades\Log;

/**
 * DescriptionAgent — AI Workforce Sprint 4.5
 *
 * Subscribes to:
 * - workforce.photo_analysis.completed (triggers after PhotoAgent)
 *
 * Emits:
 * - workforce.description.completed (downstream: PropertyScoreAgent)
 *
 * Role: Analyzes and improves listing descriptions.
 * Updates workspace lifecycle: MEDIA_READY → DESCRIPTION_READY
 *
 * No external API calls (vertical slice — rule-based).
 */
class DescriptionAgent implements HermesHandlerContract
{
    public function __construct(
        private HermesService $hermesService,
    ) {}

    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            'workforce.photo_analysis.completed',
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

        // Load workspace
        $workspace = $this->loadWorkspace($payload);

        // Record execution
        $execLog = WorkforceExecutionLog::create([
            'ilan_id' => $ilanId,
            'tenant_id' => $tenantId,
            'chain_id' => $chainId,
            'agent_name' => 'description_agent',
            'agent_class' => self::class,
            'event_received' => $event->eventName(),
            'event_chain_step' => 2,
            'input_payload' => $payload,
            'output_payload' => [],
            'status' => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $ilanBaslik = $payload['ilan_baslik'] ?? $workspace?->root_folder_name ?? '';
            $tier = $this->classifyTier($ilanBaslik);

            $analysisResult = [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'current_title' => $ilanBaslik,
                'title_score' => $this->scoreTitle($ilanBaslik),
                'suggestions' => $this->generateDescriptionSuggestions($ilanBaslik, $tier),
                'improved_title' => $this->generateImprovedTitle($ilanBaslik, $tier),
                'keywords' => $this->extractKeywords($ilanBaslik),
                'market_positioning' => $this->classifyMarketPositioning($ilanBaslik, $tier),
                'language_quality' => $this->assessLanguageQuality($ilanBaslik),
                'analyzed_at' => now()->toIso8601String(),
            ];

            // Update workspace
            if ($workspace) {
                $workspace->markAiAgentComplete('description_agent', $analysisResult);
            }

            $execLog->markCompleted($analysisResult);

            Log::info('[DescriptionAgent] Description analysis complete', [
                'ilan_id' => $ilanId,
                'workspace_id' => $workspace?->getKey(),
                'title_score' => $analysisResult['title_score'],
                'lifecycle_state' => $workspace?->lifecycle_state?->value,
            ]);

            // Emit DescriptionCompleted event
            if ($workspace) {
                $this->emitDescriptionCompleted($workspace, $analysisResult, [
                    'ilan_id' => $ilanId,
                    'chain_id' => $chainId,
                ]);
            }

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'workspace_id' => $workspace?->getKey(),
                'title_score' => $analysisResult['title_score'],
                'suggestions' => $analysisResult['suggestions'],
                'improved_title' => $analysisResult['improved_title'],
                'keywords' => $analysisResult['keywords'],
                'lifecycle_state' => $workspace?->lifecycle_state?->value,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $execLog->markFailed($e->getMessage());

            Log::error('[DescriptionAgent] Description analysis failed', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
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

    private function loadWorkspace(array $payload): ?PortfolioDriveWorkspace
    {
        $workspaceId = $payload['workspace_id'] ?? null;
        $ilanId = $payload['ilan_id'] ?? null;

        if ($workspaceId) {
            return PortfolioDriveWorkspace::find($workspaceId);
        }
        if ($ilanId) {
            return PortfolioDriveWorkspace::forPortfolio($ilanId)->first();
        }
        return null;
    }

    private function emitDescriptionCompleted(
        PortfolioDriveWorkspace $workspace,
        array $analysisResult,
        array $metadata,
    ): void {
        $event = new DescriptionCompleted($workspace, $analysisResult, $metadata);
        $this->hermesService->receive($event);
    }

    private function classifyTier(string $baslik): string
    {
        $lower = mb_strtolower($baslik);
        if (str_contains($lower, 'lüks') || str_contains($lower, 'luxury')) {
            return 'luxury';
        }
        if (str_contains($lower, 'premium')) {
            return 'premium';
        }
        return 'standard';
    }

    private function scoreTitle(string $baslik): float
    {
        if (empty($baslik)) {
            return 0.0;
        }

        $score = 0.4;
        $lower = mb_strtolower($baslik);
        $len = mb_strlen($baslik);

        if ($len >= 30 && $len <= 60) {
            $score += 0.2;
        } elseif ($len >= 20 && $len <= 80) {
            $score += 0.1;
        }

        $specificityKeywords = ['satılık', 'kiralık', 'dükkan', 'villa', 'daire', 'arazi', 'bahçe'];
        foreach ($specificityKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $score += 0.05;
            }
        }

        $valueKeywords = ['lüks', 'deniz', 'havuz', 'site', 'prestij'];
        foreach ($valueKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $score += 0.05;
            }
        }

        $urgencyKeywords = ['acil', 'fırsat', 'hemen'];
        foreach ($urgencyKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $score += 0.1;
            }
        }

        return round(min($score, 1.0), 2);
    }

    private function generateDescriptionSuggestions(string $baslik, string $tier): array // @sab-ignore-context7
    {
        $suggestions = []; // @sab-ignore-context7
        $lower = mb_strtolower($baslik);
        $len = mb_strlen($baslik);

        if ($len < 20) {
            $suggestions[] = [
                'type' => 'length',
                'message' => 'Başlık çok kısa. En az 30-60 karakter arası ideal uzunluktadır.',
                'priority' => 'high',
            ];
        } elseif ($len > 80) {
            $suggestions[] = [
                'type' => 'length',
                'message' => 'Başlık çok uzun. Kısaltılarak özet bilgi ön plana çıkarılmalı.',
                'priority' => 'medium',
            ];
        }

        if ($tier === 'luxury') {
            $suggestions[] = [
                'type' => 'tone',
                'message' => 'Lüks segment için premium dil kullanılmalı: "benzersiz", "özel".',
                'priority' => 'medium',
            ];
        }

        if (!str_contains($lower, 'satılık') && !str_contains($lower, 'kiralık')) {
            $suggestions[] = [
                'type' => 'clarity',
                'message' => 'İlan tipi (Satılık/Kiralık) başlıkta belirtilmemiş.',
                'priority' => 'high',
            ];
        }

        return $suggestions;
    }

    private function generateImprovedTitle(string $baslik, string $tier): string
    {
        $lower = mb_strtolower($baslik);

        if ($this->scoreTitle($baslik) >= 0.8) {
            return $baslik;
        }

        $improvements = [];

        if ($tier === 'luxury' && !str_contains($lower, 'lüks')) {
            $improvements[] = 'Lüks';
        }

        $propertyTypes = ['villa', 'daire', 'dükkan', 'arazi', 'büro'];
        $hasType = false;
        foreach ($propertyTypes as $pt) {
            if (str_contains($lower, $pt)) {
                $hasType = true;
                break;
            }
        }
        if (!$hasType) {
            $improvements[] = 'Gayrimenkul';
        }

        return trim(implode(' ', $improvements) . ' ' . $baslik);
    }

    private function extractKeywords(string $baslik): array
    {
        $lower = mb_strtolower($baslik);
        $keywordMap = [
            'satılık', 'kiralık', 'villa', 'daire', 'dükkan', 'arazi',
            'bodrum', 'antalya', 'istanbul', 'deniz', 'havuz',
            'bahçe', 'dublex', 'site', 'lüks', 'acil', 'fırsat',
        ];

        $found = [];
        foreach ($keywordMap as $kw) {
            if (str_contains($lower, $kw)) {
                $found[] = ucfirst($kw);
            }
        }

        return array_unique($found);
    }

    private function classifyMarketPositioning(string $baslik, string $tier): string
    {
        return match ($tier) {
            'luxury' => 'premium_market',
            'premium' => 'upper_mid_market',
            default => 'mass_market',
        };
    }

    private function assessLanguageQuality(string $baslik): array
    {
        $score = 0.8;

        if (empty($baslik)) {
            return ['score' => 0.0, 'issues' => ['Boş başlık']];
        }

        $issues = [];

        if (!preg_match('/[çğıöşü]/u', $baslik) && preg_match('/[cgiou]/u', $baslik)) {
            $issues[] = 'Türkçe karakter eksikliği (olası yabancı dil kullanımı)';
        }

        if (preg_match('/[A-ZÇĞİÖŞÜ]{5,}/u', $baslik)) {
            $issues[] = 'Aşırı büyük harf kullanımı';
        }

        if (str_contains($baslik, 'http') || str_contains($baslik, 'www')) {
            $issues[] = 'URL içeriği algılandı';
            $score -= 0.3;
        }

        return [
            'score' => round(max($score - count($issues) * 0.1, 0.0), 2),
            'issues' => $issues,
        ];
    }
}
