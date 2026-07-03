<?php

namespace App\Services\Hermes\Handlers\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Domain\Hermes\Enums\HermesWorkforceEventVocabulary;
use App\Models\Hermes\WorkforceExecutionLog;
use Illuminate\Support\Facades\Log;

/**
 * DescriptionAgent — AI Workforce Sprint 4.3
 *
 * Triggered by: workforce.description_analysis_requested
 * Role: Analyzes and improves listing descriptions
 *
 * No external API calls (vertical slice — rule-based).
 * Production: would call AIOrchestrator.generateListing() or description service.
 */
class DescriptionAgent implements HermesHandlerContract
{
    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            HermesWorkforceEventVocabulary::WORKFORCE_DESCRIPTION_ANALYSIS_REQUESTED->value,
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
            $ilanBaslik = $portfolioAnalysis['ilan_baslik'] ?? $payload['ilan_baslik'] ?? '';
            $tier = $portfolioAnalysis['tier'] ?? 'standard';
            $priority = $portfolioAnalysis['priority'] ?? 'normal';

            // Rule-based description analysis (production: AI service)
            $result = [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'current_title' => $ilanBaslik,
                'title_score' => $this->scoreTitle($ilanBaslik),
                'suggestions' => $this->generateDescriptionSuggestions($ilanBaslik, $tier, $priority),
                'improved_title' => $this->generateImprovedTitle($ilanBaslik, $tier),
                'keywords' => $this->extractKeywords($ilanBaslik),
                'market_positioning' => $this->classifyMarketPositioning($ilanBaslik, $tier),
                'language_quality' => $this->assessLanguageQuality($ilanBaslik),
                'analyzed_at' => now()->toIso8601String(),
            ];

            $execLog->markCompleted($result);

            Log::info('[DescriptionAgent] Description analysis complete', [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'title_score' => $result['title_score'],
                'suggestions_count' => count($result['suggestions']),
            ]);

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'title_score' => $result['title_score'],
                'suggestions' => $result['suggestions'],
                'improved_title' => $result['improved_title'],
                'keywords' => $result['keywords'],
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $execLog->markFailed($e->getMessage());

            Log::error('[DescriptionAgent] Description analysis failed', [
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

    private function scoreTitle(string $baslik): float
    {
        if (empty($baslik)) {
            return 0.0;
        }

        $score = 0.4;
        $lower = mb_strtolower($baslik);

        // Length scoring
        $len = mb_strlen($baslik);
        if ($len >= 30 && $len <= 60) {
            $score += 0.2;
        } elseif ($len >= 20 && $len <= 80) {
            $score += 0.1;
        }

        // Specificity scoring
        $specificityKeywords = ['satılık', 'kiralık', 'dükkan', 'villa', 'daire', 'arazi', 'bahçe'];
        foreach ($specificityKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $score += 0.05;
            }
        }

        // Value keywords
        $valueKeywords = ['lüks', 'deniz', 'havuz', 'site', 'prestij', 'ultra'];
        foreach ($valueKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $score += 0.05;
            }
        }

        // Urgency keywords
        $urgencyKeywords = ['acil', 'fırsat', 'hemen', 'sahibinden'];
        foreach ($urgencyKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $score += 0.1;
            }
        }

        return round(min($score, 1.0), 2);
    }

    private function generateDescriptionSuggestions(string $baslik, string $tier, string $priority): array // @sab-ignore-context7
    {
        $suggestions = []; // @sab-ignore-context7
        $lower = mb_strtolower($baslik);

        // Length suggestions
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

        // Tier-specific suggestions
        if ($tier === 'luxury') {
            $suggestions[] = [
                'type' => 'tone',
                'message' => 'Lüks segment için premium dil kullanılmalı: "benzersiz", "özel", "rahatlık".',
                'priority' => 'medium',
            ];
            $suggestions[] = [
                'type' => 'features',
                'message' => 'Lüks alıcılar için yaşam tarzı vurgusu ekleyin.',
                'priority' => 'medium',
            ];
        }

        if ($tier === 'budget') {
            $suggestions[] = [
                'type' => 'value',
                'message' => 'Fiyat avantajı ve lokasyon bilgisi ön plana çıkarılmalı.',
                'priority' => 'medium',
            ];
        }

        // Missing element suggestions
        if (!str_contains($lower, 'satılık') && !str_contains($lower, 'kiralık')) {
            $suggestions[] = [
                'type' => 'clarity',
                'message' => 'İlan tipi (Satılık/Kiralık) başlıkta belirtilmemiş.',
                'priority' => 'high',
            ];
        }

        if (!str_contains($lower, 'bodrum') && !str_contains($lower, 'antalya') && !str_contains($lower, 'istanbul')) {
            $suggestions[] = [
                'type' => 'location',
                'message' => 'Lokasyon bilgisi başlıkta belirtilmemiş.',
                'priority' => 'medium',
            ];
        }

        // Priority suggestions
        if ($priority === 'high') {
            $suggestions[] = [
                'type' => 'urgency',
                'message' => 'Acil/fırsat vurgusu varsa, detaylı açıklama ile desteklenmeli.',
                'priority' => 'high',
            ];
        }

        return $suggestions;
    }

    private function generateImprovedTitle(string $baslik, string $tier): string
    {
        $lower = mb_strtolower($baslik);

        // If title already good, return as-is
        if ($this->scoreTitle($baslik) >= 0.8) {
            return $baslik;
        }

        $improvements = [];

        // Add tier indicator
        if ($tier === 'luxury' && !str_contains($lower, 'lüks')) {
            $improvements[] = 'Lüks';
        }

        // Add property type if missing
        $propertyTypes = ['villa', 'daire', 'dükkan', 'arazi', 'büro'];
        $hasType = false;
        foreach ($propertyTypes as $type) {
            if (str_contains($lower, $type)) {
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
            'satılık' => 'Satılık',
            'kiralık' => 'Kiralık',
            'villa' => 'Villa',
            'daire' => 'Daire',
            'dükkan' => 'Dükkan',
            'arazi' => 'Arazi',
            'büro' => 'Ofis',
            'bodrum' => 'Bodrum',
            'antalya' => 'Antalya',
            'istanbul' => 'İstanbul',
            'muğla' => 'Muğla',
            'deniz' => 'Deniz',
            'havuz' => 'Havuz',
            'bahçe' => 'Bahçe',
            'dublex' => 'Dublex',
            'site' => 'Site',
            'lüks' => 'Lüks',
            'acil' => 'Acil',
            'fırsat' => 'Fırsat',
        ];

        $found = [];
        foreach ($keywordMap as $keyword => $label) {
            if (str_contains($lower, $keyword)) {
                $found[] = $label;
            }
        }

        return array_unique($found);
    }

    private function classifyMarketPositioning(string $baslik, string $tier): string
    {
        return match ($tier) {
            'luxury' => 'premium_market',
            'premium' => 'upper_mid_market',
            'standard' => 'mass_market',
            'budget' => 'value_market',
            default => 'standard_market',
        };
    }

    private function assessLanguageQuality(string $baslik): array
    {
        $score = 0.8; // Default reasonable quality

        if (empty($baslik)) {
            return ['score' => 0.0, 'issues' => ['Boş başlık']];
        }

        $issues = [];

        // Check for Turkish characters
        if (!preg_match('/[çğıöşü]/u', $baslik) && preg_match('/[cgiou]/u', $baslik)) {
            $issues[] = 'Türkçe karakter eksikliği (olası yabancı dil kullanımı)';
        }

        // Check for excessive caps
        if (preg_match('/[A-ZÇĞİÖŞÜ]{5,}/u', $baslik)) {
            $issues[] = 'Aşırı büyük harf kullanımı';
        }

        // Check for URL-like content
        if (str_contains($baslik, 'http') || str_contains($baslik, 'www')) {
            $issues[] = 'URL içeriği algılandı';
            $score -= 0.3;
        }

        return [
            'score' => round(max($score - (count($issues) * 0.1), 0.0), 2),
            'issues' => $issues,
        ];
    }
}
