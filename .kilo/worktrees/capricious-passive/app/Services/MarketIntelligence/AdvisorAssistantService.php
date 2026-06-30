<?php

namespace App\Services\MarketIntelligence;

use App\DTOs\MarketIntelligence\AdvisorInsightDTO;
use App\Services\AIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Logging\LogService;

/**
 * Advisor Assistant Service — MIE v3
 *
 * V1/V2 deterministik sinyallerini AI ile insana anlatır.
 *
 * ❌ AI fiyat belirlemez
 * ❌ AI skor üretmez
 * ❌ AI decision override etmez
 * ❌ AI yeni sinyal üretmez
 *
 * ✅ Sadece açıklar, gerekçe sunar, aksiyon önerir
 *
 * AI çağrısı başarısız olursa → deterministic fallback üretir.
 */
class AdvisorAssistantService
{
    private const VALID_URGENCY_LEVELS = ['LOW', 'MEDIUM', 'HIGH'];

    private const REQUIRED_OUTPUT_FIELDS = [
        'summary',
        'reasoning',
        'recommended_action',
        'urgency',
        'risk_note',
    ];

    /** Cache TTL: 6 saat (PricingInsight ile aynı) */
    private const CACHE_TTL_SECONDS = 21600;

    private AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * MIE intelligence payload'ından danışman yorumu üret.
     *
     * @param array{
     *   pricing_position?: string,
     *   pricing_score?: int,
     *   confidence_label?: string,
     *   confidence_score?: int,
     *   demand_label?: string,
     *   opportunity_action?: string,
     *   priority_label?: string,
     *   queue_type?: string,
     *   days_on_market?: float|null,
     *   price?: float|null,
     *   benchmark_price?: float|null,
     * } $payload
     */
    public function generate(array $payload): AdvisorInsightDTO
    {
        $sanitized = $this->sanitizePayload($payload);

        // Cache: aynı sinyal seti için tekrar AI çağrısı yapma
        $cacheKey = $this->buildCacheKey($sanitized);
        $cached = $this->getFromCache($cacheKey);
        if ($cached instanceof AdvisorInsightDTO) {
            return $cached;
        }

        try {
            $prompt = $this->buildFullPrompt($sanitized);

            $raw = $this->aiService->generate($prompt, [
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]);

            $parsed = $this->parseResponse($raw);

            if ($this->validateOutput($parsed)) {
                $dto = $this->buildDTO($parsed);
                $this->putToCache($cacheKey, $dto);
                return $dto;
            }

            $this->logWarning('MIE V3: AI output validation failed, falling back to deterministic', [
                'parsed' => $parsed,
            ]);
        } catch (\Throwable $e) {
            LogService::error('MIE V3: AI call failed', [], $e);
            $this->logWarning('MIE V3: AI call failed, falling back to deterministic', [
                'error' => $e->getMessage(),
            ]);
        }

        $fallback = $this->buildFallback($sanitized);
        $this->putToCache($cacheKey, $fallback);
        return $fallback;
    }

    private function logWarning(string $message, array $context = []): void
    {
        // Facade erişilemezse (örn. saf unit test), error_log'a düş — sessiz yutma yok.
        try {
            Log::warning($message, $context);
        } catch (\Throwable) {
            error_log("MIE_V3_LOG_FALLBACK: {$message} " . json_encode($context));
        }
    }

    /**
     * Payload'ı sanitize et — sadece izin verilen alanlar geçer.
     */
    public function sanitizePayload(array $payload): array
    {
        // current_price → price mapping (PricingInsightDTO uses current_price)
        if (isset($payload['current_price']) && ! isset($payload['price'])) {
            $payload['price'] = $payload['current_price'];
        }

        $allowed = [
            'pricing_position', 'pricing_score',
            'confidence_label', 'confidence_score',
            'demand_label',
            'opportunity_action', 'priority_label',
            'queue_type',
            'days_on_market', 'price', 'benchmark_price',
            // MIE v4: Location Intelligence
            'location_signal_score', 'location_confidence_label',
            'location_demand_modifier', 'location_top_groups',
        ];

        return array_intersect_key($payload, array_flip($allowed));
    }

    /**
     * System + user prompt'u birleştir.
     */
    public function buildFullPrompt(array $sanitized): string
    {
        return $this->buildSystemPrompt() . "\n\n" . $this->buildUserPrompt($sanitized);
    }

    public function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a real estate advisor assistant for a Turkish property platform.

You DO NOT generate scores.
You DO NOT override system decisions.
You ONLY explain existing signals and suggest human actions.

Be concise, factual, deterministic-aware.
Never hallucinate missing data.
Respond in Turkish.

Return ONLY valid JSON with exactly these fields:
{
  "summary": "1-2 sentence situation summary",
  "reasoning": "why this decision was made, referencing the signals",
  "recommended_action": "specific action the human should take",
  "urgency": "LOW or MEDIUM or HIGH",
  "risk_note": "any risks or caveats, empty string if none"
}
PROMPT;
    }

    public function buildUserPrompt(array $sanitized): string
    {
        $pricingPosition = $sanitized['pricing_position'] ?? 'insufficient_data';
        $pricingScore = $sanitized['pricing_score'] ?? 0;
        $confidenceLabel = $sanitized['confidence_label'] ?? 'VERY_LOW';
        $confidenceScore = $sanitized['confidence_score'] ?? 0;
        $demandLabel = $sanitized['demand_label'] ?? 'WEAK';
        $opportunityAction = $sanitized['opportunity_action'] ?? 'INSUFFICIENT_DATA';
        $priorityLabel = $sanitized['priority_label'] ?? 'LOW';
        $queueType = $sanitized['queue_type'] ?? 'NO_ACTION';
        $daysOnMarket = $sanitized['days_on_market'] ?? 'N/A';
        $price = $sanitized['price'] ?? 'N/A';
        $benchmarkPrice = $sanitized['benchmark_price'] ?? 'N/A';

        // MIE v4: Location Intelligence
        $locationScore = $sanitized['location_signal_score'] ?? 'N/A';
        $locationConfidence = $sanitized['location_confidence_label'] ?? 'N/A';
        $locationModifier = $sanitized['location_demand_modifier'] ?? 0;
        $locationGroups = isset($sanitized['location_top_groups'])
            ? implode(', ', (array) $sanitized['location_top_groups'])
            : 'N/A';

        return <<<PROMPT
Analyze the following listing intelligence:

Pricing Position: {$pricingPosition}
Pricing Score: {$pricingScore}
Confidence: {$confidenceLabel} ({$confidenceScore})
Demand: {$demandLabel}
Opportunity: {$opportunityAction}
Priority: {$priorityLabel}
Queue: {$queueType}
Days on Market: {$daysOnMarket}
Price: {$price}
Benchmark: {$benchmarkPrice}

Location Signal: {$locationScore}/100 (Confidence: {$locationConfidence})
Location Modifier: {$locationModifier}
Nearby Services: {$locationGroups}

Explain:
- What is happening
- Why
- What should be done (consider location context)
- Urgency
- Risks

Return JSON only.
PROMPT;
    }

    /**
     * AI'dan gelen raw yanıtı parse et.
     */
    public function parseResponse(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw)) {
            return [];
        }

        $raw = trim($raw);

        // Markdown code fence temizle
        if (str_starts_with($raw, '```')) {
            $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $raw = preg_replace('/\s*```\s*$/', '', $raw);
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * AI output'u validate et — hallucination guard.
     */
    public function validateOutput(array $parsed): bool
    {
        // Gerekli alanlar var mı?
        foreach (self::REQUIRED_OUTPUT_FIELDS as $field) {
            if (! array_key_exists($field, $parsed)) {
                return false;
            }
            if (! is_string($parsed[$field])) {
                return false;
            }
        }

        // Boş summary / reasoning kabul edilmez
        if (empty(trim($parsed['summary'])) || empty(trim($parsed['reasoning']))) {
            return false;
        }

        // Urgency sadece belirli değerler
        if (! in_array($parsed['urgency'], self::VALID_URGENCY_LEVELS, true)) {
            return false;
        }

        // Hallucinated field kontrolü — fazla alan varsa reddet
        $extraFields = array_diff(array_keys($parsed), self::REQUIRED_OUTPUT_FIELDS);
        if (count($extraFields) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Parse edilmiş AI çıktısından DTO oluştur.
     */
    private function buildDTO(array $parsed): AdvisorInsightDTO
    {
        return new AdvisorInsightDTO(
            summary: trim($parsed['summary']),
            reasoning: trim($parsed['reasoning']),
            recommended_action: trim($parsed['recommended_action']),
            urgency: $parsed['urgency'],
            risk_note: trim($parsed['risk_note']),
        );
    }

    /**
     * AI başarısız olduğunda deterministic fallback üret.
     * V1/V2 sinyallerini template'e yerleştirir — AI sıfır.
     */
    public function buildFallback(array $sanitized): AdvisorInsightDTO
    {
        $action = $sanitized['opportunity_action'] ?? 'INSUFFICIENT_DATA';
        $position = $sanitized['pricing_position'] ?? 'insufficient_data';
        $confidence = $sanitized['confidence_label'] ?? 'VERY_LOW';
        $demand = $sanitized['demand_label'] ?? 'WEAK';
        $priority = $sanitized['priority_label'] ?? 'LOW';
        $daysOnMarket = $sanitized['days_on_market'] ?? null;

        $positionText = match ($position) {
            'underpriced' => 'Mevcut fiyat benchmark ortalamasının altında',
            'fair' => 'Fiyat piyasa ile uyumlu',
            'overpriced' => 'Fiyat benchmark ortalamasının üzerinde',
            'aggressively_overpriced' => 'Fiyat benchmark ortalamasının belirgin üzerinde',
            default => 'Fiyat pozisyonu belirlenemiyor',
        };

        $demandText = match ($demand) {
            'HOT' => 'yüksek talep var',
            'ACTIVE' => 'aktif talep mevcut',
            'SLOW' => 'talep yavaş',
            default => 'talep zayıf',
        };

        $summary = "{$positionText}, {$demandText}.";

        $reasoning = match ($action) {
            'BUY' => "İlan benchmark altında fiyatlanmış ve talep koşulları destekleyici. Güven seviyesi: {$confidence}.",
            'SELL' => "İlan benchmark üzerinde fiyatlanmış, fiyat revizyonu düşünülebilir. Güven seviyesi: {$confidence}.",
            'WAIT' => "Mevcut koşullar izleme gerektirir, henüz net aksiyon gerekmez. Güven seviyesi: {$confidence}.",
            default => "Veri yetersiz, karar için daha fazla karşılaştırılabilir ilan gerekiyor.",
        };

        $recommendedAction = match ($action) {
            'BUY' => 'Bu ilan takip listesine alınmalı, fırsat değerlendirmesi yapılmalı.',
            'SELL' => 'Fiyat revizyonu düşünülmeli, piyasa benchmark ile karşılaştırma yapılmalı.',
            'WAIT' => 'İlan izlemeye alınsın, talep ve fiyat trendi takip edilsin.',
            default => 'Manuel inceleme gerekli, veri yeterli olunca yeniden değerlendirilsin.',
        };

        $urgency = match ($priority) {
            'CRITICAL' => 'HIGH',
            'HIGH' => 'HIGH',
            'MEDIUM' => 'MEDIUM',
            default => 'LOW',
        };

        // MIE v4: Location context to fallback reasoning
        $locationScore = $sanitized['location_signal_score'] ?? null;
        $locationGroups = $sanitized['location_top_groups'] ?? [];
        if ($locationScore !== null && $locationScore >= 50 && !empty($locationGroups)) {
            $groupsStr = implode(', ', array_slice((array) $locationGroups, 0, 3));
            $reasoning .= " Çevresel erişim güçlü ({$groupsStr}).";
        } elseif ($locationScore !== null && $locationScore < 30) {
            $reasoning .= ' Çevresel hizmet erişimi sınırlı.';
        }

        $riskParts = [];
        if (in_array($confidence, ['VERY_LOW', 'LOW'], true)) {
            $riskParts[] = 'Güven seviyesi düşük, karşılaştırılabilir veri sınırlı';
        }
        if ($daysOnMarket !== null && $daysOnMarket > 90) {
            $riskParts[] = 'İlan uzun süredir piyasada (' . (int) $daysOnMarket . ' gün)';
        }
        $riskNote = implode('. ', $riskParts);

        return new AdvisorInsightDTO(
            summary: $summary,
            reasoning: $reasoning,
            recommended_action: $recommendedAction,
            urgency: $urgency,
            risk_note: $riskNote,
        );
    }

    /**
     * Deterministik cache key — aynı sinyaller → aynı key.
     */
    public function buildCacheKey(array $sanitized): string
    {
        ksort($sanitized);

        return 'mie_advisor_' . md5(json_encode($sanitized));
    }

    private function getFromCache(string $key): ?AdvisorInsightDTO
    {
        try {
            $cached = Cache::get($key);

            return $cached instanceof AdvisorInsightDTO ? $cached : null;
        } catch (\Throwable $e) {
            LogService::error('MIE V3: Cache get error', ['key' => $key], $e);
            return null;
        }
    }

    private function putToCache(string $key, AdvisorInsightDTO $dto): void
    {
        try {
            Cache::put($key, $dto, self::CACHE_TTL_SECONDS);
        } catch (\Throwable $e) {
            LogService::error('MIE V3: Cache put error', ['key' => $key], $e);
        }
    }
}
