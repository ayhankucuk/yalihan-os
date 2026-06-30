<?php

namespace App\DTOs\MarketIntelligence;

/**
 * Action Mode DTO — MIE v5 Decision Engine
 *
 * Insight → Action dönüşümü.
 * Konum + Fiyat + Talep + Güven sinyallerini birleştirerek
 * kullanıcıya deterministik karar önerisi sunar.
 *
 * "Bu arsayı ne yapmalısın?"
 */
final class ActionModeDTO
{
    /**
     * @param int         $composite_score       Bileşik karar skoru (0–100)
     * @param string      $decision_label        Karar etiketi (Yüksek Potansiyel / Dengeli / Riskli / Düşük Potansiyel)
     * @param string      $decision_level        hot | balanced | risky | avoid
     * @param string      $cta_action            buy | watch | avoid
     * @param string      $cta_label             CTA metin (Al & Değerlendir / İzle / Kaçın)
     * @param string      $cta_icon              CTA ikon
     * @param array       $investment_angles     Kim için uygun? [{icon, label, reason, fit_score}]
     * @param array       $opportunities         Fırsat listesi [{text, source, strength}]
     * @param array       $risks                 Risk listesi [{text, source, severity}]
     * @param string      $advisor_narrative     Bütünleşik danışman metni
     * @param string      $opportunity_action    OpportunityScore aksiyon: BUY/WAIT/SELL/INSUFFICIENT_DATA
     * @param int         $location_score        Konum skoru (0–100)
     * @param int         $pricing_score         Fiyat skoru (0–100)
     * @param int         $demand_score          Talep skoru (0–100)
     * @param int         $opportunity_score     Fırsat skoru (0–100)
     * @param int         $confidence_score      Güven skoru (0–100)
     * @param string      $pricing_position      Fiyat pozisyonu
     * @param bool        $has_pricing_data      Fiyat verisi var mı
     * @param bool        $has_location_data     Konum verisi var mı
     */
    public function __construct(
        public readonly int $composite_score,
        public readonly string $decision_label,
        public readonly string $decision_level,
        public readonly string $cta_action,
        public readonly string $cta_label,
        public readonly string $cta_icon,
        public readonly array $investment_angles,
        public readonly array $opportunities,
        public readonly array $risks,
        public readonly string $advisor_narrative,
        public readonly string $opportunity_action,
        public readonly int $location_score,
        public readonly int $pricing_score,
        public readonly int $demand_score,
        public readonly int $opportunity_score,
        public readonly int $confidence_score,
        public readonly string $pricing_position,
        public readonly bool $has_pricing_data,
        public readonly bool $has_location_data,
    ) {}

    public function toArray(): array
    {
        return [
            'composite_score' => $this->composite_score,
            'decision_label' => $this->decision_label,
            'decision_level' => $this->decision_level,
            'cta_action' => $this->cta_action,
            'cta_label' => $this->cta_label,
            'cta_icon' => $this->cta_icon,
            'investment_angles' => $this->investment_angles,
            'opportunities' => $this->opportunities,
            'risks' => $this->risks,
            'advisor_narrative' => $this->advisor_narrative,
            'opportunity_action' => $this->opportunity_action,
            'location_score' => $this->location_score,
            'pricing_score' => $this->pricing_score,
            'demand_score' => $this->demand_score,
            'opportunity_score' => $this->opportunity_score,
            'confidence_score' => $this->confidence_score,
            'pricing_position' => $this->pricing_position,
            'has_pricing_data' => $this->has_pricing_data,
            'has_location_data' => $this->has_location_data,
        ];
    }

    /**
     * Trust Breakdown payload — Karar Dağılımı UI bileşeni için.
     */
    public function toTrustBreakdown(): array
    {
        $confidenceLabel = match (true) {
            $this->confidence_score >= 75 => 'HIGH',
            $this->confidence_score >= 50 => 'MEDIUM',
            $this->confidence_score >= 25 => 'LOW',
            default => 'UNKNOWN',
        };

        $locationContribution = round($this->location_score * 0.30, 1);
        $opportunityContribution = round($this->opportunity_score * 0.30, 1);
        $pricingContribution = round($this->pricing_score * 0.20, 1);
        $demandContribution = round($this->demand_score * 0.20, 1);

        $locationConfidence = $this->has_location_data ? ($this->location_score >= 60 ? 'HIGH' : 'MEDIUM') : 'LOW';
        $pricingConfidence = $this->has_pricing_data ? ($this->pricing_score >= 60 ? 'HIGH' : 'MEDIUM') : 'LOW';
        $demandConfidence = $this->has_pricing_data ? ($this->demand_score >= 60 ? 'HIGH' : 'MEDIUM') : 'LOW';
        $opportunityConfidence = $confidenceLabel;

        // Impact: supports / neutral / weakens
        $resolveImpact = fn(int $score): string => match (true) {
            $score >= 60 => 'supports',
            $score >= 40 => 'neutral',
            default => 'weakens',
        };

        $components = [
            [
                'key' => 'location',
                'label' => 'Location',
                'raw_score' => $this->location_score,
                'weight' => 30,
                'contribution' => $locationContribution,
                'confidence' => $locationConfidence,
                'impact' => $this->has_location_data ? $resolveImpact($this->location_score) : 'weakens',
                'reason' => $this->buildLocationReason(),
                'color' => 'emerald',
                'source_hint' => 'POI dataset',
            ],
            [
                'key' => 'opportunity',
                'label' => 'Opportunity',
                'raw_score' => $this->opportunity_score,
                'weight' => 30,
                'contribution' => $opportunityContribution,
                'confidence' => $opportunityConfidence,
                'impact' => $resolveImpact($this->opportunity_score),
                'reason' => $this->buildOpportunityReason(),
                'color' => 'blue',
                'source_hint' => 'Composite decision engine',
            ],
            [
                'key' => 'pricing',
                'label' => 'Pricing',
                'raw_score' => $this->pricing_score,
                'weight' => 20,
                'contribution' => $pricingContribution,
                'confidence' => $pricingConfidence,
                'impact' => $this->has_pricing_data ? $resolveImpact($this->pricing_score) : 'weakens',
                'reason' => $this->buildPricingReason(),
                'color' => 'amber',
                'source_hint' => 'Benchmark pricing',
            ],
            [
                'key' => 'demand',
                'label' => 'Demand',
                'raw_score' => $this->demand_score,
                'weight' => 20,
                'contribution' => $demandContribution,
                'confidence' => $demandConfidence,
                'impact' => $this->has_pricing_data ? $resolveImpact($this->demand_score) : 'weakens',
                'reason' => $this->buildDemandReason(),
                'color' => 'violet',
                'source_hint' => 'Demand model',
            ],
        ];

        // Strongest / weakest by contribution
        $sorted = collect($components)->sortByDesc('contribution');
        $strongest = $sorted->first();
        $weakest = $sorted->last();

        // Decision narrative — "Bu karar en çok X ve Y tarafından taşınıyor"
        $supporters = collect($components)
            ->filter(fn($c) => $c['impact'] === 'supports')
            ->sortByDesc('contribution')
            ->values();

        $decisionNarrative = $this->buildDecisionNarrative($supporters, $components);

        // Confidence narrative
        $confidenceNarrative = match ($confidenceLabel) {
            'HIGH' => 'Bu karar güçlü veri ve tutarlı sinyaller tarafından destekleniyor.',
            'MEDIUM' => 'Bu karar destekleniyor ancak bazı bileşenlerde veri sınırlı.',
            'LOW' => 'Bu karar yüksek belirsizlik içerir. Ek doğrulama önerilir.',
            default => 'Güven seviyesi belirlenemedi. Veri yetersiz.',
        };

        $riskNotes = [];
        if (! $this->has_pricing_data) {
            $riskNotes[] = 'Fiyat verisi sınırlı olabilir';
        }
        $riskNotes[] = 'Talep tahmini model bazlıdır';
        if ($confidenceLabel === 'LOW' || $confidenceLabel === 'UNKNOWN') {
            $riskNotes[] = 'Düşük güvenli bileşenler kararı zayıflatabilir';
        }

        return [
            'composite_score' => $this->composite_score,
            'decision_label' => $this->decision_label,
            'confidence_label' => $confidenceLabel,
            'confidence_narrative' => $confidenceNarrative,
            'decision_narrative' => $decisionNarrative,
            'summary' => 'Karar; lokasyon, fırsat, fiyat ve talep sinyallerinin bileşiminden üretildi.',
            'strongest_signal' => $strongest['label'] ?? null,
            'weakest_signal' => $weakest['label'] ?? null,
            'components' => $components,
            'risk_notes' => $riskNotes,
        ];
    }

    private function buildDecisionNarrative(mixed $supporters, array $components): string
    {
        $label = $this->decision_label;

        if ($supporters->isEmpty()) {
            return "Bu ilan \"{$label}\" olarak işaretlendi ancak hiçbir bileşen güçlü destek sağlamıyor.";
        }

        $parts = $supporters->take(3)->map(function ($c) {
            $impactLabel = match (true) {
                $c['contribution'] >= 20 => 'çok güçlü',
                $c['contribution'] >= 15 => 'güçlü',
                $c['contribution'] >= 10 => 'destekleyici',
                default => 'kısmen destekleyici',
            };
            return "{$c['label']} {$impactLabel} (+{$c['contribution']} katkı)";
        })->toArray();

        $narrative = "Bu ilan \"{$label}\" olarak işaretlendi çünkü: " . implode(', ', $parts) . '.';

        // Weakeners
        $weakeners = collect($components)->filter(fn($c) => $c['impact'] === 'weakens');
        if ($weakeners->isNotEmpty()) {
            $weakNames = $weakeners->pluck('label')->implode(', ');
            $narrative .= " {$weakNames} kararı zayıflatıyor.";
        }

        return $narrative;
    }

    private function buildLocationReason(): string
    {
        if (! $this->has_location_data) {
            return 'Konum verisi mevcut değil';
        }

        return match (true) {
            $this->location_score >= 70 => 'Erişim güçlü, çeşitlilik iyi, konum avantajlı',
            $this->location_score >= 50 => 'Erişim orta, çeşitlilik kısmen yeterli',
            $this->location_score >= 30 => 'Erişim zayıf, konum dezavantajlı',
            default => 'Konum sinyali çok düşük',
        };
    }

    private function buildOpportunityReason(): string
    {
        return match (true) {
            $this->opportunity_score >= 70 => 'Fırsat sinyali güçlü',
            $this->opportunity_score >= 50 => 'Fırsat sinyali dengeli',
            $this->opportunity_score >= 30 => 'Fırsat sinyali zayıf',
            default => 'Fırsat sinyali çok düşük',
        };
    }

    private function buildPricingReason(): string
    {
        if (! $this->has_pricing_data) {
            return 'Fiyat verisi yetersiz';
        }

        return match (true) {
            $this->pricing_score >= 70 => 'Fiyat avantajı belirgin',
            $this->pricing_score >= 50 => 'Fiyat piyasaya yakın',
            $this->pricing_score >= 30 => 'Fiyat dezavantajı var',
            default => 'Fiyat çok yüksek veya veri yetersiz',
        };
    }

    private function buildDemandReason(): string
    {
        if (! $this->has_pricing_data) {
            return 'Talep verisi yetersiz';
        }

        return match (true) {
            $this->demand_score >= 70 => 'Talep güçlü, ilgi yüksek',
            $this->demand_score >= 50 => 'Talep dengeli',
            $this->demand_score >= 30 => 'Talep zayıf',
            default => 'Talep çok düşük',
        };
    }
}
