<?php

namespace App\Services\MarketIntelligence;

use App\DTOs\MarketIntelligence\ActionModeDTO;
use App\DTOs\MarketIntelligence\LocationInsightDTO;
use App\DTOs\MarketIntelligence\PricingInsightDTO;
use App\Enums\MarketIntelligence\PricingPosition;

/**
 * Action Mode Engine — MIE v5 Decision Platform
 *
 * Insight'ı aksiyona çevirir.
 * "Bu arsa 57 çünkü…" → "Bu arsayı ne yapmalısın?"
 *
 * Tamamen deterministik. ML yok, AI yok, rand() yok.
 * Mevcut MIE skorlarını composite decision'a dönüştürür.
 *
 * Composite Score = Location(×0.30) + Opportunity(×0.30) + Pricing(×0.20) + Demand(×0.20)
 *
 * Decision Levels:
 *   80+ → hot       → "🔥 Yüksek Potansiyel — Kaçırma"
 *   60+ → balanced  → "⚖️ Dengeli — Detaylı İncele"
 *   40+ → risky     → "⚠️ Riskli — Dikkatli Yaklaş"
 *   <40 → avoid     → "🚫 Düşük Potansiyel — Uzak Dur"
 *
 * CTA Actions:
 *   BUY opportunity + composite ≥ 65 → "Al & Değerlendir"
 *   WAIT opportunity OR composite 40-64 → "İzle"
 *   SELL opportunity OR composite < 40 → "Kaçın"
 */
class ActionModeEngine
{
    // Composite weight distribution
    private const W_LOCATION = 0.30;
    private const W_OPPORTUNITY = 0.30;
    private const W_PRICING = 0.20;
    private const W_DEMAND = 0.20;

    /**
     * Tüm MIE sinyallerini birleştirerek ActionModeDTO üret.
     */
    public function evaluate(
        ?LocationInsightDTO $locationInsight,
        ?PricingInsightDTO $pricingInsight,
    ): ActionModeDTO {
        $hasLocation = $locationInsight && $locationInsight->data_status === 'ok';
        $hasPricing = $pricingInsight && ! $pricingInsight->insufficient_data;

        // Extract raw scores
        $locationScore = $hasLocation ? ($locationInsight->location_signal_score ?? 0) : 0;
        $pricingScore = $hasPricing ? $pricingInsight->pricing_score : 0;
        $demandScore = $hasPricing ? $pricingInsight->demand_score : 0;
        $opportunityScore = $hasPricing ? $pricingInsight->opportunity_score : 0;
        $confidenceScore = $hasPricing ? $pricingInsight->confidence_score : 0;
        $pricingPosition = $hasPricing ? $pricingInsight->pricing_position->value : 'INSUFFICIENT_DATA';
        $opportunityAction = $hasPricing ? $pricingInsight->opportunity_action : 'INSUFFICIENT_DATA';

        // Composite score — weighted blend
        $compositeScore = $this->calculateComposite(
            $locationScore,
            $opportunityScore,
            $pricingScore,
            $demandScore,
            $hasLocation,
            $hasPricing,
        );

        // Decision label + level
        [$decisionLabel, $decisionLevel] = $this->resolveDecision($compositeScore);

        // CTA
        [$ctaAction, $ctaLabel, $ctaIcon] = $this->resolveCta($compositeScore, $opportunityAction);

        // Investment angles
        $investmentAngles = $this->buildInvestmentAngles(
            $locationInsight,
            $pricingInsight,
            $compositeScore,
            $hasLocation,
            $hasPricing,
        );

        // Opportunities & Risks — composite from both signals
        $opportunities = $this->buildOpportunities($locationInsight, $pricingInsight, $hasLocation, $hasPricing);
        $risks = $this->buildRisks($locationInsight, $pricingInsight, $hasLocation, $hasPricing);

        // Advisor narrative — deterministic composite text
        $narrative = $this->buildNarrative(
            $compositeScore,
            $decisionLevel,
            $locationScore,
            $pricingPosition,
            $demandScore,
            $opportunityAction,
            $opportunities,
            $risks,
            $hasLocation,
            $hasPricing,
        );

        return new ActionModeDTO(
            composite_score: $compositeScore,
            decision_label: $decisionLabel,
            decision_level: $decisionLevel,
            cta_action: $ctaAction,
            cta_label: $ctaLabel,
            cta_icon: $ctaIcon,
            investment_angles: $investmentAngles,
            opportunities: $opportunities,
            risks: $risks,
            advisor_narrative: $narrative,
            opportunity_action: $opportunityAction,
            location_score: $locationScore,
            pricing_score: $pricingScore,
            demand_score: $demandScore,
            opportunity_score: $opportunityScore,
            confidence_score: $confidenceScore,
            pricing_position: $pricingPosition,
            has_pricing_data: $hasPricing,
            has_location_data: $hasLocation,
        );
    }

    /**
     * Composite score — weighted average with fallback.
     *
     * Eğer bir sinyal yoksa, o sinyal ağırlığı diğerlerine dağıtılır.
     */
    private function calculateComposite(
        int $locationScore,
        int $opportunityScore,
        int $pricingScore,
        int $demandScore,
        bool $hasLocation,
        bool $hasPricing,
    ): int {
        $weights = [];
        $scores = [];

        if ($hasLocation) {
            $weights[] = self::W_LOCATION;
            $scores[] = $locationScore;
        }
        if ($hasPricing) {
            $weights[] = self::W_OPPORTUNITY;
            $scores[] = $opportunityScore;

            $weights[] = self::W_PRICING;
            $scores[] = $pricingScore;

            $weights[] = self::W_DEMAND;
            $scores[] = $demandScore;
        }

        // Hiç veri yoksa
        if (empty($weights)) {
            return 0;
        }

        // Normalize weights to sum 1.0
        $totalWeight = array_sum($weights);
        $composite = 0;
        foreach ($scores as $i => $score) {
            $composite += $score * ($weights[$i] / $totalWeight);
        }

        return (int) min(100, max(0, round($composite)));
    }

    /**
     * Decision label + level from composite score.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveDecision(int $composite): array
    {
        return match (true) {
            $composite >= 80 => ['🔥 Yüksek Potansiyel — Kaçırma', 'hot'],
            $composite >= 60 => ['⚖️ Dengeli — Detaylı İncele', 'balanced'],
            $composite >= 40 => ['⚠️ Riskli — Dikkatli Yaklaş', 'risky'],
            default => ['🚫 Düşük Potansiyel — Uzak Dur', 'avoid'],
        };
    }

    /**
     * CTA action + label + icon.
     *
     * OpportunityService'in BUY/WAIT/SELL kararını composite ile birleştirir.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolveCta(int $composite, string $opportunityAction): array
    {
        // BUY: OpportunityScore BUY diyor ve composite yeterli
        if ($opportunityAction === 'BUY' && $composite >= 55) {
            return ['buy', '✔ Al & Değerlendir', '✔'];
        }

        // SELL: OpportunityScore SELL diyor veya composite çok düşük
        if ($opportunityAction === 'SELL' || $composite < 35) {
            return ['avoid', '❌ Kaçın', '❌'];
        }

        // Composite yüksekse ama pricing data yok — yine de pozitif sinyal
        if ($composite >= 65 && $opportunityAction === 'INSUFFICIENT_DATA') {
            return ['buy', '✔ Al & Değerlendir', '✔'];
        }

        // Default: WAIT
        return ['watch', '⚖️ İzle', '⚖️'];
    }

    /**
     * Investment angles — "Bu lokasyon kim için uygun?"
     *
     * Konum + fiyat + talep sinyallerinden profil çıkarır.
     */
    private function buildInvestmentAngles(
        ?LocationInsightDTO $location,
        ?PricingInsightDTO $pricing,
        int $composite,
        bool $hasLocation,
        bool $hasPricing,
    ): array {
        $angles = [];
        $presentGroups = [];

        if ($hasLocation) {
            $presentGroups = collect($location->top_nearby_groups)->pluck('group')->toArray();
        }

        // ── Yatırımcı Profili ──
        // Yüksek composite + ulaşım erişimi + (underpriced VEYA aktif talep)
        $investorFit = 0;
        $investorReasons = [];

        if ($composite >= 55) {
            $investorFit += 30;
            $investorReasons[] = 'Bileşik skoru güçlü';
        }
        if (in_array('transport', $presentGroups)) {
            $investorFit += 20;
            $investorReasons[] = 'ulaşım erişimi var';
        }
        if ($hasPricing && $pricing->pricing_position === PricingPosition::UNDERPRICED) {
            $investorFit += 30;
            $investorReasons[] = 'piyasa altı fiyat';
        }
        if ($hasPricing && $pricing->demand_score >= 50) {
            $investorFit += 20;
            $investorReasons[] = 'aktif talep';
        }

        if ($investorFit >= 40) {
            $angles[] = [
                'icon' => '📈',
                'label' => 'Yatırımcı',
                'reason' => implode(', ', array_slice($investorReasons, 0, 3)),
                'fit_score' => min(100, $investorFit),
            ];
        }

        // ── Oturum Profili ──
        // Eğitim + sağlık + günlük ihtiyaç erişimi + makul fiyat
        $oturumFit = 0;
        $oturumReasons = [];

        $livingGroups = array_intersect(['education', 'health', 'daily_need'], $presentGroups);
        if (count($livingGroups) >= 2) {
            $oturumFit += 40;
            $oturumReasons[] = 'temel hizmet erişimi güçlü';
        }
        if (in_array('green_leisure', $presentGroups)) {
            $oturumFit += 15;
            $oturumReasons[] = 'yeşil alan yakın';
        }
        if ($hasPricing && in_array($pricing->pricing_position->value, [
            PricingPosition::UNDERPRICED->value,
            PricingPosition::FAIR->value,
        ])) {
            $oturumFit += 25;
            $oturumReasons[] = 'fiyat uygun';
        }

        if ($oturumFit >= 40) {
            $angles[] = [
                'icon' => '🏠',
                'label' => 'Oturum',
                'reason' => implode(', ', array_slice($oturumReasons, 0, 3)),
                'fit_score' => min(100, $oturumFit),
            ];
        }

        // ── Ticari Profil ──
        // Alışveriş/yeme-içme yoğunluğu + ulaşım + talep
        $ticariFit = 0;
        $ticariReasons = [];

        if (in_array('shopping', $presentGroups) || in_array('food_social', $presentGroups)) {
            $ticariFit += 30;
            $ticariReasons[] = 'ticari aktivite mevcut';
        }
        if (in_array('transport', $presentGroups)) {
            $ticariFit += 20;
            $ticariReasons[] = 'ulaşım aksı üzerinde';
        }
        if ($hasLocation && ($location->poi_density_score ?? 0) >= 15) {
            $ticariFit += 25;
            $ticariReasons[] = 'yoğun çevre';
        }
        if ($hasPricing && $pricing->demand_score >= 50) {
            $ticariFit += 15;
            $ticariReasons[] = 'piyasa talebi aktif';
        }

        if ($ticariFit >= 40) {
            $angles[] = [
                'icon' => '🏪',
                'label' => 'Ticari',
                'reason' => implode(', ', array_slice($ticariReasons, 0, 3)),
                'fit_score' => min(100, $ticariFit),
            ];
        }

        // ── Turizm Profili ── (Bodrum context)
        // Yeşil alan + yeme-içme + ulaşım = turizm potansiyeli
        $turizmFit = 0;
        $turizmReasons = [];

        if (in_array('green_leisure', $presentGroups)) {
            $turizmFit += 25;
            $turizmReasons[] = 'doğa/deniz yakınlığı';
        }
        if (in_array('food_social', $presentGroups)) {
            $turizmFit += 25;
            $turizmReasons[] = 'yeme-içme altyapısı var';
        }
        if (in_array('shopping', $presentGroups)) {
            $turizmFit += 15;
            $turizmReasons[] = 'alışveriş erişimi';
        }
        if ($composite >= 45) {
            $turizmFit += 15;
            $turizmReasons[] = 'lokasyon konforu';
        }

        if ($turizmFit >= 40) {
            $angles[] = [
                'icon' => '🏖️',
                'label' => 'Turizm / Kiralık',
                'reason' => implode(', ', array_slice($turizmReasons, 0, 3)),
                'fit_score' => min(100, $turizmFit),
            ];
        }

        // ── Geliştirme (fallback) ──
        if (empty($angles) && $composite >= 25) {
            $angles[] = [
                'icon' => '🏗️',
                'label' => 'Geliştirme',
                'reason' => 'Altyapı geliştirme potansiyeli, uzun vadeli değerlendirme',
                'fit_score' => min(50, $composite),
            ];
        }

        // Sort by fit_score descending
        usort($angles, fn($a, $b) => ($b['fit_score'] ?? 0) <=> ($a['fit_score'] ?? 0));

        return $angles;
    }

    /**
     * Fırsat paneli — konum güçlü yönleri + fiyat fırsatları.
     */
    private function buildOpportunities(
        ?LocationInsightDTO $location,
        ?PricingInsightDTO $pricing,
        bool $hasLocation,
        bool $hasPricing,
    ): array {
        $opps = [];

        // Location güçlü yönleri
        if ($hasLocation) {
            $groupLabels = [
                'education' => 'Eğitim', 'health' => 'Sağlık', 'shopping' => 'Alışveriş',
                'transport' => 'Ulaşım', 'food_social' => 'Yeme-İçme', 'green_leisure' => 'Yeşil Alan',
                'daily_need' => 'Günlük İhtiyaç',
            ];
            $groupIcons = [
                'education' => '🎓', 'health' => '🏥', 'shopping' => '🛍️',
                'transport' => '🚌', 'food_social' => '🍽️', 'green_leisure' => '🌳',
                'daily_need' => '🏦',
            ];

            foreach ($location->top_nearby_groups as $g) {
                $m = $g['closest_m'] ?? 9999;
                $label = $groupLabels[$g['group']] ?? $g['group'];
                $icon = $groupIcons[$g['group']] ?? '📌';

                if ($m <= 500) {
                    $opps[] = [
                        'text' => "{$icon} {$label} erişimi güçlü ({$m}m)",
                        'source' => 'location',
                        'strength' => 'strong',
                    ];
                } elseif ($m <= 1500) {
                    $opps[] = [
                        'text' => "{$icon} {$label} erişimi uygun ({$m}m)",
                        'source' => 'location',
                        'strength' => 'moderate',
                    ];
                }
            }

            // Turizm potansiyeli
            $presentGroups = collect($location->top_nearby_groups)->pluck('group')->toArray();
            if (in_array('green_leisure', $presentGroups) && in_array('food_social', $presentGroups)) {
                $opps[] = [
                    'text' => '🏖️ Turizm potansiyeli var',
                    'source' => 'composite',
                    'strength' => 'moderate',
                ];
            }
        }

        // Pricing fırsatları
        if ($hasPricing) {
            if ($pricing->pricing_position === PricingPosition::UNDERPRICED) {
                $delta = abs($pricing->price_delta_percent ?? 0);
                $opps[] = [
                    'text' => "💰 Piyasa altı fiyat (-%{$delta})",
                    'source' => 'pricing',
                    'strength' => 'strong',
                ];
            }
            if ($pricing->pricing_position === PricingPosition::FAIR) {
                $opps[] = [
                    'text' => '💰 Piyasa uyumlu fiyat',
                    'source' => 'pricing',
                    'strength' => 'moderate',
                ];
            }
            if ($pricing->demand_score >= 75) {
                $opps[] = [
                    'text' => '🔥 Yüksek piyasa talebi',
                    'source' => 'demand',
                    'strength' => 'strong',
                ];
            } elseif ($pricing->demand_score >= 50) {
                $opps[] = [
                    'text' => '📊 Aktif piyasa talebi',
                    'source' => 'demand',
                    'strength' => 'moderate',
                ];
            }
        }

        // Sort: strong first
        usort($opps, fn($a, $b) => ($a['strength'] === 'strong' ? 0 : 1) <=> ($b['strength'] === 'strong' ? 0 : 1));

        return $opps;
    }

    /**
     * Risk paneli — konum zayıf yönleri + fiyat riskleri.
     */
    private function buildRisks(
        ?LocationInsightDTO $location,
        ?PricingInsightDTO $pricing,
        bool $hasLocation,
        bool $hasPricing,
    ): array {
        $risks = [];

        // Location zayıf yönleri
        if ($hasLocation) {
            $criticalGroups = ['education', 'health', 'daily_need', 'transport'];
            $presentGroups = collect($location->top_nearby_groups)->pluck('group')->toArray();
            $groupLabels = [
                'education' => 'Eğitim', 'health' => 'Sağlık',
                'daily_need' => 'Günlük İhtiyaç', 'transport' => 'Ulaşım',
            ];
            $groupIcons = [
                'education' => '🎓', 'health' => '🏥',
                'daily_need' => '🏦', 'transport' => '🚌',
            ];

            // Missing critical groups
            foreach ($criticalGroups as $cg) {
                if (! in_array($cg, $presentGroups)) {
                    $label = $groupLabels[$cg] ?? $cg;
                    $icon = $groupIcons[$cg] ?? '📌';
                    $risks[] = [
                        'text' => "{$icon} {$label} erişimi yok",
                        'source' => 'location',
                        'severity' => 'high',
                    ];
                }
            }

            // Distant services
            foreach ($location->top_nearby_groups as $g) {
                $m = $g['closest_m'] ?? 9999;
                if ($m > 1500 && in_array($g['group'], $criticalGroups)) {
                    $label = $groupLabels[$g['group']] ?? $g['group'];
                    $icon = $groupIcons[$g['group']] ?? '📌';
                    $risks[] = [
                        'text' => "{$icon} {$label} mesafesi yüksek ({$m}m)",
                        'source' => 'location',
                        'severity' => 'medium',
                    ];
                }
            }
        }

        // Pricing riskleri
        if ($hasPricing) {
            if ($pricing->pricing_position === PricingPosition::AGGRESSIVELY_OVERPRICED) {
                $delta = abs($pricing->price_delta_percent ?? 0);
                $risks[] = [
                    'text' => "💸 Aşırı fiyatlandırılmış (+%{$delta})",
                    'source' => 'pricing',
                    'severity' => 'high',
                ];
            } elseif ($pricing->pricing_position === PricingPosition::OVERPRICED) {
                $delta = abs($pricing->price_delta_percent ?? 0);
                $risks[] = [
                    'text' => "💸 Piyasa üstü fiyat (+%{$delta})",
                    'source' => 'pricing',
                    'severity' => 'medium',
                ];
            }

            if ($pricing->demand_score <= 24) {
                $risks[] = [
                    'text' => '📉 Zayıf piyasa talebi',
                    'source' => 'demand',
                    'severity' => 'high',
                ];
            } elseif ($pricing->demand_score <= 49) {
                $risks[] = [
                    'text' => '📉 Yavaş piyasa talebi',
                    'source' => 'demand',
                    'severity' => 'medium',
                ];
            }

            if ($pricing->confidence_score < 20) {
                $risks[] = [
                    'text' => '⚠️ Veri güveni çok düşük',
                    'source' => 'confidence',
                    'severity' => 'medium',
                ];
            }
        }

        // Veri eksikliği riskleri
        if (! $hasLocation) {
            $risks[] = [
                'text' => '📍 Konum verisi yetersiz',
                'source' => 'data',
                'severity' => 'medium',
            ];
        }
        if (! $hasPricing) {
            $risks[] = [
                'text' => '💰 Fiyat karşılaştırma verisi yetersiz',
                'source' => 'data',
                'severity' => 'medium',
            ];
        }

        // Sort: high severity first
        usort($risks, fn($a, $b) => ($a['severity'] === 'high' ? 0 : 1) <=> ($b['severity'] === 'high' ? 0 : 1));

        return $risks;
    }

    /**
     * Bütünleşik danışman metni — tek paragraf, deterministik.
     *
     * "Bu arsa yatırım için orta riskli, ancak doğru fiyatla fırsata dönüşebilir."
     */
    private function buildNarrative(
        int $composite,
        string $level,
        int $locationScore,
        string $pricingPosition,
        int $demandScore,
        string $opportunityAction,
        array $opportunities,
        array $risks,
        bool $hasLocation,
        bool $hasPricing,
    ): string {
        // Risk seviyesi
        $riskLabel = match ($level) {
            'hot' => 'düşük riskli',
            'balanced' => 'orta riskli',
            'risky' => 'yüksek riskli',
            'avoid' => 'çok yüksek riskli',
            default => 'belirsiz riskli',
        };

        // Opening statement
        $text = "Bu arsa bileşik değerlendirmeye göre {$riskLabel} bir yatırım (skor: {$composite}/100).";

        // Location context
        if ($hasLocation && $locationScore > 0) {
            $locQuality = $locationScore >= 65 ? 'güçlü' : ($locationScore >= 35 ? 'orta' : 'zayıf');
            $text .= " Konum kalitesi {$locQuality} ({$locationScore}/100).";
        }

        // Pricing context
        if ($hasPricing) {
            $posLabel = match ($pricingPosition) {
                PricingPosition::UNDERPRICED->value => 'piyasa altında — değer fırsatı',
                PricingPosition::FAIR->value => 'piyasa uyumlu',
                PricingPosition::OVERPRICED->value => 'piyasa üstünde — fiyat revizyonu gerekli',
                PricingPosition::AGGRESSIVELY_OVERPRICED->value => 'aşırı fiyatlı — ciddi revizyon gerekli',
                default => 'fiyat pozisyonu belirlenemedi',
            };
            $text .= " Fiyat: {$posLabel}.";
        }

        // Demand context
        if ($hasPricing && $demandScore > 0) {
            $demandQ = $demandScore >= 75 ? 'yüksek' : ($demandScore >= 50 ? 'aktif' : ($demandScore >= 25 ? 'yavaş' : 'zayıf'));
            $text .= " Piyasa talebi {$demandQ}.";
        }

        // Strength/weakness count
        $strongCount = count(array_filter($opportunities, fn($o) => ($o['strength'] ?? '') === 'strong'));
        $highRiskCount = count(array_filter($risks, fn($r) => ($r['severity'] ?? '') === 'high'));

        if ($strongCount > 0 && $highRiskCount === 0) {
            $text .= ' Güçlü yönleri değer artışını destekleyebilir.';
        } elseif ($strongCount > 0 && $highRiskCount > 0) {
            $text .= ' Güçlü yönleri var ancak kritik eksikler risk oluşturuyor — dikkatli değerlendirme önerilir.';
        } elseif ($highRiskCount > 0) {
            $text .= ' Belirgin riskler mevcut — doğru fiyatla fırsata dönüşebilir.';
        }

        // Final recommendation based on opportunity action
        $text .= match ($opportunityAction) {
            'BUY' => ' Fırsat — agresif aksiyon alınabilir.',
            'SELL' => ' Mevcut koşullarda fiyat/pozisyon revizyonu önerilir.',
            'WAIT' => ' Bekle — piyasa veri izlenmeye devam edilmeli.',
            default => '',
        };

        return $text;
    }
}
