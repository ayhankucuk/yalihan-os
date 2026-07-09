<?php

namespace App\Services\Vision;

use App\DTOs\Vision\VisionAnalysisDTO;
use App\DTOs\Vision\VisionObjectDTO;
use App\Services\Media\RoomDetectionService;
use App\Models\IlanFotografi;

/**
 * Vision Fusion Engine — Sprint 6.4
 *
 * Confidence Fusion Strategy:
 *   Rule Engine (RoomDetectionService)
 *           +
 *   Vision Provider (AI)
 *           ↓
 *   Confidence Weighted Average
 *           ↓
 *   Final Classification
 *
 * Fusion Rules:
 *   1. AI confidence > 0.85 → trust AI
 *   2. Rule confidence > 85 → trust rule
 *   3. Both moderate (0.5–0.85) → weighted average
 *   4. One very weak (<0.3) → trust the stronger one
 *   5. Both weak → "other" with low confidence
 */
class VisionFusionEngine
{
    private const AI_HIGH_THRESHOLD = 0.85;
    private const RULE_HIGH_THRESHOLD = 85; // rule engine uses 0–100
    private const AI_MODERATE_THRESHOLD = 0.5;
    private const RULE_WEAK_THRESHOLD = 30;

    public function __construct(
        private readonly RoomDetectionService $ruleEngine,
    ) {}

    /**
     * AI Vision sonucunu rule engine ile birleştirerek final classification üretir.
     *
     * @param  IlanFotografi  $fotograf
     * @param  VisionAnalysisDTO|null  $aiResult   AI Vision sonucu (null = AI başarısız)
     * @return array{
     *     oda_turu: string,
     *     label: string,
     *     guven_skoru: int,          // 0–100
     *     ai_confidence: float,       // 0.0–1.0
     *     rule_confidence: int,       // 0–100
     *     fusion_confidence: float,    // 0.0–1.0
     *     provider: string,
     *     reason: string,
     *     fused: bool,
     * }
     */
    public function fuse(IlanFotografi $fotograf, ?VisionAnalysisDTO $aiResult): array
    {
        // Step 1: Rule engine result
        $ruleResult = $this->ruleEngine->detect($fotograf);
        $ruleConfidence = $ruleResult['guven_skoru'];

        // Step 2: AI result
        if ($aiResult === null || $aiResult->hasError()) {
            // AI başarısız → sadece rule'e güven
            return $this->buildFallbackResult($ruleResult, 'rule_only');
        }

        $aiTopRoom = $aiResult->topRoom();
        if ($aiTopRoom === null) {
            // AI oda tespit edemedi → rule'e güven
            return $this->buildFallbackResult($ruleResult, 'rule_only');
        }

        $aiConfidence = $aiTopRoom->confidence;
        $aiRoomType   = $aiTopRoom->label;

        // Step 3: Apply fusion strategy
        $aiRule    = $this->normalizeRuleConfidence($ruleConfidence);
        $fused     = $this->shouldFuse($aiConfidence, $ruleConfidence);

        if (!$fused) {
            // Bir kaynağa güven → o kaynağı kullan
            if ($aiConfidence >= self::AI_HIGH_THRESHOLD) {
                return $this->buildAIRuledResult($aiResult, 'ai_only');
            }
            if ($ruleConfidence >= self::RULE_HIGH_THRESHOLD) {
                return $this->buildRuleAIRuledResult($ruleResult, 'rule_only');
            }
        }

        // Step 4: Weighted fusion
        return $this->computeWeightedFusion($ruleResult, $aiResult, $ruleConfidence, $aiConfidence);
    }

    /**
     * Birden fazla fotoğraf için fusion sonuçları üretir.
     *
     * @param  IlanFotografi[]  $fotograflar
     * @param  array<int, VisionAnalysisDTO|null>  $aiResults  key = fotograf_id
     * @return array<int, array{...}>
     */
    public function fuseBatch(array $fotograflar, array $aiResults): array
    {
        $results = [];

        foreach ($fotograflar as $fotograf) {
            $aiResult = $aiResults[$fotograf->id] ?? null;
            $results[$fotograf->id] = $this->fuse($fotograf, $aiResult);
        }

        return $results;
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    private function shouldFuse(float $aiConfidence, int $ruleConfidence): bool
    {
        $aiNorm     = $aiConfidence;
        $ruleNorm   = $this->normalizeRuleConfidence($ruleConfidence);

        // Her ikisi de yüksek → ayrık, fusion gereksiz
        if ($aiNorm >= self::AI_HIGH_THRESHOLD && $ruleNorm >= self::RULE_HIGH_THRESHOLD / 100) {
            return false;
        }

        // Her ikisi de zayıf → fusion kaotik olabilir
        if ($aiNorm < 0.3 && $ruleNorm < 0.3) {
            return false;
        }

        return true;
    }

    private function computeWeightedFusion(
        array $ruleResult,
        VisionAnalysisDTO $aiResult,
        int $ruleConfidence,
        float $aiConfidence,
    ): array {
        $ruleNorm    = $this->normalizeRuleConfidence($ruleConfidence);
        $aiNorm      = $aiConfidence;
        $totalWeight = $ruleNorm + $aiNorm;

        // Ağırlıklı ortalama (0–1 arası)
        $fusionConf = ($ruleNorm * $ruleConfidence / 100 + $aiNorm * $aiConfidence) / ($ruleNorm + $aiNorm);

        // AI daha güvenilir → AI'yı seç
        if ($aiNorm > $ruleNorm) {
            $aiTop = $aiResult->topRoom();
            return [
                'oda_turu'         => $this->mapLabelToKey($aiTop->label),
                'label'            => $aiTop->label,
                'guven_skoru'      => (int) round($fusionConf * 100),
                'ai_confidence'    => $aiConfidence,
                'rule_confidence'  => $ruleConfidence,
                'fusion_confidence' => round($fusionConf, 3),
                'provider'         => 'fusion',
                'reason'           => "AI ({$this->pct($aiConfidence)}) + Rule ({$this->pct($this->normalizeRuleConfidence($ruleConfidence))}) — AI daha güvenilir",
                'fused'            => true,
            ];
        }

        // Rule daha güvenilir → Rule'u seç
        return [
            'oda_turu'         => $ruleResult['oda_turu'],
            'label'            => $ruleResult['label'],
            'guven_skoru'      => (int) round($fusionConf * 100),
            'ai_confidence'    => $aiConfidence,
            'rule_confidence'  => $ruleConfidence,
            'fusion_confidence' => round($fusionConf, 3),
            'provider'         => 'fusion',
            'reason'           => "AI ({$this->pct($aiConfidence)}) + Rule ({$this->pct($this->normalizeRuleConfidence($ruleConfidence))}) — Rule daha güvenilir",
            'fused'            => true,
        ];
    }

    private function buildFallbackResult(array $ruleResult, string $mode): array
    {
        return [
            'oda_turu'         => $ruleResult['oda_turu'],
            'label'            => $ruleResult['label'],
            'guven_skoru'      => $ruleResult['guven_skoru'],
            'ai_confidence'    => 0.0,
            'rule_confidence'  => $ruleResult['guven_skoru'],
            'fusion_confidence' => round($this->normalizeRuleConfidence($ruleResult['guven_skoru']), 3),
            'provider'         => 'rule',
            'reason'           => 'AI başarısız veya mevcut değil — rule engine sonucu kullanıldı.',
            'fused'            => false,
        ];
    }

    private function buildAIRuledResult(VisionAnalysisDTO $aiResult, string $mode): array
    {
        $top = $aiResult->topRoom();

        return [
            'oda_turu'         => $this->mapLabelToKey($top->label),
            'label'            => $top->label,
            'guven_skoru'      => (int) round($aiResult->overall_confidence * 100),
            'ai_confidence'    => $aiResult->overall_confidence,
            'rule_confidence'  => 0,
            'fusion_confidence' => $aiResult->overall_confidence,
            'provider'         => 'ai',
            'reason'           => 'AI yüksek güvenilirlikle oda tespit etti.',
            'fused'            => false,
        ];
    }

    private function buildRuleAIRuledResult(array $ruleResult, string $mode): array
    {
        return [
            'oda_turu'         => $ruleResult['oda_turu'],
            'label'            => $ruleResult['label'],
            'guven_skoru'      => $ruleResult['guven_skoru'],
            'ai_confidence'    => 0.0,
            'rule_confidence'  => $ruleResult['guven_skoru'],
            'fusion_confidence' => round($this->normalizeRuleConfidence($ruleResult['guven_skoru']), 3),
            'provider'         => 'rule',
            'reason'           => 'Rule engine yüksek güvenilirlikle oda tespit etti.',
            'fused'            => false,
        ];
    }

    /**
     * Rule confidence (0–100) → AI scale (0.0–1.0)
     */
    private function normalizeRuleConfidence(int $ruleScore): float
    {
        return round($ruleScore / 100, 3);
    }

    /**
     * AI room label'ı → oda_turu key'e çevir.
     */
    private function mapLabelToKey(string $label): string
    {
        $map = [
            'Salon'        => 'living_room',
            'Mutfak'      => 'kitchen',
            'Yatak Odası' => 'bedroom',
            'Banyo'       => 'bathroom',
            'Yemek Odası' => 'dining_room',
            'Havuz'       => 'pool',
            'Bahçe'       => 'garden',
            'Teras'       => 'terrace',
            'Dış Cephe'   => 'exterior',
            'Manzara'     => 'view',
            'Diğer'       => 'other',
        ];

        return $map[$label] ?? 'other';
    }

    private function pct(float $v): string
    {
        return round($v * 100) . '%';
    }
}
