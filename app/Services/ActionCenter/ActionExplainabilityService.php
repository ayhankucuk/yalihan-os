<?php

namespace App\Services\ActionCenter;

use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Support\Facades\Log;

/**
 * ActionExplainabilityService — Sprint 16 Phase 1.
 *
 * Provides explainability for AI-generated Gorev records by reconstructing
 * the provenance chain: which AI module generated the recommendation,
 * what signals were used, and what the confidence score is based on.
 *
 * Design rules:
 * - Read-only: does not mutate Gorev records
 * - Returns structured JSON for API consumption
 * - Falls back gracefully when source data is unavailable
 *
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §Phase 4
 */
class ActionExplainabilityService
{
    /** Human-readable AI module labels */
    private const MODULE_LABELS = [
        'deal_radar'          => 'Deal Radar',
        'opportunity_engine'  => 'Fırsat Motoru',
        'portfolio_doctor'    => 'Portföy Doktoru',
        'buyer_match'        => 'Alıcı Eşleştirme',
        'ActionCenter'        => 'Action Center',
    ];

    /**
     * Return a full explainability payload for a Gorev record.
     *
     * @return array{
     *   gorev_id: int,
     *   ai_generated: bool,
     *   module_label: string,
     *   confidence: float|null,
     *   reasoning: string|null,
     *   provenance_chain: array,
     *   recommendation: string,
     *   caveats: array
     * }
     */
    public function explain(Gorev $gorev): array
    {
        $isAI = $this->isAIRecord($gorev);

        $result = [
            'gorev_id'        => $gorev->id,
            'ai_generated'    => $isAI,
            'module_label'    => self::MODULE_LABELS[$gorev->source_module] ?? ($gorev->source_module ?? 'Bilinmiyor'),
            'source_event'    => $gorev->source_event,
            'source_module'   => $gorev->source_module,
            'ai_model_version' => $gorev->ai_model_version,
        ];

        if (!$isAI) {
            $result = array_merge($result, [
                'confidence'         => null,
                'reasoning'         => null,
                'provenance_chain'   => [],
                'recommendation'    => $this->buildNonAIRecommendation($gorev),
                'caveats'           => ['Bu görev AI tarafından oluşturulmamıştır.'],
            ]);
            return $result;
        }

        $confidence = $gorev->ai_confidence_score;
        $reasoning  = $gorev->ai_reasoning;
        $provenance = $this->buildProvenanceChain($gorev);

        return array_merge($result, [
            'confidence'       => $confidence,
            'confidence_label' => $this->labelConfidence($confidence),
            'reasoning'        => $reasoning,
            'provenance_chain'  => $provenance,
            'recommendation'   => $this->buildAIRecommendation($gorev, $provenance),
            'caveats'          => $this->buildCaveats($gorev),
        ]);
    }

    /**
     * Lightweight explain for list views — returns just the key fields.
     *
     * @return array{
     *   ai_generated: bool,
     *   module_label: string,
     *   confidence_label: string|null,
     *   reasoning_preview: string|null
     * }
     */
    public function explainSummary(Gorev $gorev): array
    {
        $isAI = $this->isAIRecord($gorev);

        if (!$isAI) {
            return [
                'ai_generated'       => false,
                'module_label'       => 'Manual',
                'confidence_label'   => null,
                'reasoning_preview'  => null,
            ];
        }

        $reasoning = $gorev->ai_reasoning ?? '';
        return [
            'ai_generated'       => true,
            'module_label'       => self::MODULE_LABELS[$gorev->source_module] ?? ($gorev->source_module ?? 'AI'),
            'confidence_label'   => $this->labelConfidence($gorev->ai_confidence_score),
            'reasoning_preview'  => mb_strlen($reasoning) > 80
                ? mb_substr($reasoning, 0, 80) . '…'
                : $reasoning,
        ];
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function isAIRecord(Gorev $gorev): bool
    {
        return $gorev->source_module !== null
            && in_array($gorev->source_module, [
                'deal_radar', 'opportunity_engine',
                'portfolio_doctor', 'buyer_match',
            ], true);
    }

    private function labelConfidence(?float $score): string
    {
        if ($score === null) {
            return 'Bilinmiyor';
        }
        return match (true) {
            $score >= 0.90 => 'Çok Yüksek',
            $score >= 0.75 => 'Yüksek',
            $score >= 0.55 => 'Orta',
            $score >= 0.35 => 'Düşük',
            default        => 'Çok Düşük',
        };
    }

    private function buildProvenanceChain(Gorev $gorev): array
    {
        $chain = [];

        $chain[] = [
            'step'     => 1,
            'agent'    => 'Yalihan AI',
            'module'   => $gorev->source_module ?? 'unknown',
            'action'   => 'Veri analizi ve sinyal tespiti',
            'evidence' => $gorev->source_event ?? 'N/A',
        ];

        $confidence = $gorev->ai_confidence_score;
        $chain[] = [
            'step'     => 2,
            'agent'    => 'Yalihan AI',
            'module'   => $gorev->source_module ?? 'unknown',
            'action'   => 'Öneri üretimi ve skor hesaplama',
            'evidence' => $confidence !== null
                ? "confidence_score={$confidence}"
                : 'confidence_score=null',
        ];

        $chain[] = [
            'step'     => 3,
            'agent'    => 'Action Center',
            'module'   => 'AIRecommendationRecorder',
            'action'   => 'Gorev kaydı oluşturma (onay_bekliyor)',
            'evidence' => "model_version={$gorev->ai_model_version}",
        ];

        if ($gorev->gorev_durumu !== 'onay_bekliyor') {
            $chain[] = [
                'step'     => 4,
                'agent'    => 'İnsan Operatör',
                'module'   => 'Human-in-the-Loop',
                'action'   => 'Gorev durumu: ' . $gorev->gorev_durumu,
                'evidence' => 'onaylanmış veya reddedilmiş',
            ];
        }

        return $chain;
    }

    private function buildAIRecommendation(Gorev $gorev, array $provenance): string
    {
        $moduleLabel = self::MODULE_LABELS[$gorev->source_module] ?? $gorev->source_module;
        $baslik = $gorev->baslik ?? 'İsimlendirilmemiş görev';
        $confidence = $gorev->ai_confidence_score;
        $confidenceLabel = $this->labelConfidence($confidence);

        return "{$moduleLabel}, \"{$baslik}\" görevini önermektedir. "
            . "Güven skoru: {$confidenceLabel} (" . ($confidence !== null ? round($confidence * 100) . '%' : 'hesaplanamadı') . "). "
            . "Bu öneri, Yalihan AI sistemlerinin analiz verilerine dayanmaktadır ve insan tarafından onaylanması gerekmektedir.";
    }

    private function buildNonAIRecommendation(Gorev $gorev): string
    {
        $baslik = $gorev->baslik ?? 'İsimlendirilmemiş görev';
        return "\"{$baslik}\" görevi manuel olarak oluşturulmuştur ve AI kaynağı içermemektedir.";
    }

    private function buildCaveats(Gorev $gorev): array
    {
        $caveats = [];

        $confidence = $gorev->ai_confidence_score;
        if ($confidence !== null && $confidence < 0.55) {
            $caveats[] = 'Düşük güven skoru: Bu öneri ek insan doğrulaması gerektirebilir.';
        }

        if ($gorev->gorev_durumu === 'onay_bekliyor') {
            $caveats[] = 'Bu öneri henüz insan tarafından onaylanmamıştır.';
        }

        if ($gorev->source_module === 'buyer_match') {
            $caveats[] = 'Alıcı eşleştirme önerileri, güncel piyasa koşullarına ve tarafların mevcut durumlarına bağlıdır.';
        }

        if ($gorev->source_module === 'deal_radar') {
            $caveats[] = 'Deal Radar sinyalleri geçmiş verilere dayanır; piyasa koşulları değişmiş olabilir.';
        }

        return $caveats;
    }
}
