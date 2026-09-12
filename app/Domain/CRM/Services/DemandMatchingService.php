<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\DTOs\DemandMatchResult;
use App\Domain\CRM\Policies\DemandMatchingPolicy;
use App\Models\Ilan;
use App\Models\Talep;

/**
 * DemandMatchingService — Pure Domain Service
 *
 * Evaluates listing vs. demand compatibility without I/O or database queries.
 * Driven strictly by DemandMatchingPolicy.
 */
class DemandMatchingService
{
    public function __construct(
        private readonly DemandMatchingPolicy $policy = new DemandMatchingPolicy()
    ) {}

    /**
     * Evaluate compatibility between a single Listing and a single Demand.
     */
    public function evaluateMatch(Ilan $ilan, Talep $talep): DemandMatchResult
    {
        $score = 0;
        $reasons = [];

        // 1. Lokasyon Uyumu (Max 40 puan)
        $locationScore = $this->calculateLocationScore($ilan, $talep, $reasons);
        $score += $locationScore;

        // 2. Bütçe Uyumu (Max 35 puan)
        $budgetScore = $this->calculateBudgetScore($ilan, $talep, $reasons);
        $score += $budgetScore;

        // 3. Gayrimenkul / İşlem Tipi Uyumu (Max 25 puan)
        $typeScore = $this->calculateTypeScore($ilan, $talep, $reasons);
        $score += $typeScore;

        $finalScore = (float) min(100, max(0, $score));
        $matchLevel = $this->policy->classifyLevel((int) $finalScore);

        $urgencyLevel = 'NORMAL';
        if ($finalScore >= 90 || $talep->oncelik === 'Acil' || $talep->oncelik === 'Yuksek' || $talep->one_cikan) {
            $urgencyLevel = $finalScore >= 95 ? 'CRITICAL' : 'HIGH';
        }

        $kisiAd = $talep->kisi ? ($talep->kisi->tam_ad ?? ($talep->kisi->ad . ' ' . $talep->kisi->soyad)) : null;

        return new DemandMatchResult(
            talepId: $talep->id,
            talepBaslik: $talep->baslik ?? "Talep #{$talep->id}",
            kisiId: $talep->kisi_id,
            kisiAdSoyad: $kisiAd,
            danismanId: $talep->danisman_id,
            ilanId: $ilan->id,
            score: $finalScore,
            matchLevel: $matchLevel,
            urgencyLevel: $urgencyLevel,
            matchReasons: $reasons,
            metadata: [
                'location_score' => $locationScore,
                'budget_score'   => $budgetScore,
                'type_score'     => $typeScore,
            ]
        );
    }

    private function calculateLocationScore(Ilan $ilan, Talep $talep, array &$reasons): float
    {
        $maxWeight = (float) $this->policy->locationWeight;

        if (!empty($talep->mahalle_id) && $talep->mahalle_id === $ilan->mahalle_id) {
            $reasons[] = 'il_match';
            $reasons[] = 'ilce_match';
            $reasons[] = 'mahalle_match';
            $reasons[] = 'Mahalle düzeyinde tam lokasyon uyumu';
            return $maxWeight; // 40
        }

        if (!empty($talep->ilce_id) && $talep->ilce_id === $ilan->ilce_id) {
            $reasons[] = 'il_match';
            $reasons[] = 'ilce_match';
            $reasons[] = 'İlçe düzeyinde lokasyon uyumu';
            return $maxWeight * 0.75; // 30
        }

        if (!empty($talep->il_id) && $talep->il_id === $ilan->il_id) {
            $reasons[] = 'il_match';
            $reasons[] = 'İl düzeyinde genel lokasyon uyumu';
            return $maxWeight * 0.40; // 16
        }

        return 0;
    }

    private function calculateBudgetScore(Ilan $ilan, Talep $talep, array &$reasons): float
    {
        $maxWeight = (float) $this->policy->budgetWeight;
        $fiyat = (float) ($ilan->fiyat ?? 0);

        if ($fiyat <= 0) {
            return 0;
        }

        $minFiyat = (float) ($talep->min_fiyat ?? 0);
        $maxFiyat = (float) ($talep->max_fiyat ?? 0);

        if ($minFiyat > 0 && $maxFiyat > 0) {
            if ($fiyat >= $minFiyat && $fiyat <= $maxFiyat) {
                $reasons[] = 'budget_match';
                $reasons[] = 'Bütçe aralığı tam uyumlu';
                return $maxWeight; // 35
            }

            // %15 tolerans payı
            $toleratedMin = $minFiyat * 0.85;
            $toleratedMax = $maxFiyat * 1.15;
            if ($fiyat >= $toleratedMin && $fiyat <= $toleratedMax) {
                $reasons[] = 'budget_tolerance_match';
                $reasons[] = 'Bütçe aralığına yakın (%15 tolerans)';
                return $maxWeight * 0.60; // 21
            }
        } elseif ($maxFiyat > 0) {
            if ($fiyat <= $maxFiyat) {
                $reasons[] = 'budget_match';
                $reasons[] = 'Maksimum bütçe dahilinde';
                return $maxWeight; // 35
            }
            if ($fiyat <= ($maxFiyat * 1.15)) {
                $reasons[] = 'budget_tolerance_match';
                $reasons[] = 'Maksimum bütçeye yakın (%15 tolerans)';
                return $maxWeight * 0.60; // 21
            }
        } elseif ($minFiyat > 0 && $fiyat >= $minFiyat) {
            $reasons[] = 'budget_match';
            $reasons[] = 'Minimum bütçe şartı karşılandı';
            return $maxWeight * 0.80; // 28
        }

        return 0;
    }

    private function calculateTypeScore(Ilan $ilan, Talep $talep, array &$reasons): float
    {
        $maxWeight = (float) $this->policy->propertyTypeWeight;
        $score = 0;

        // Kategori / Alt Kategori Uyumu
        if (!empty($talep->alt_kategori_id) && $talep->alt_kategori_id === $ilan->alt_kategori_id) {
            $score += $maxWeight * 0.50; // 12.5
            $reasons[] = 'category_match';
            $reasons[] = 'Gayrimenkul alt kategorisi eşleşti';
        } elseif (!empty($talep->kategori_id) && $talep->kategori_id === $ilan->kategori_id) {
            $score += $maxWeight * 0.30; // 7.5
            $reasons[] = 'category_match';
            $reasons[] = 'Gayrimenkul ana kategorisi eşleşti';
        }

        // Satılık / Kiralık / İşlem Tipi Uyumu
        $talepTip = mb_strtolower(trim((string) ($talep->talep_tipi ?? $talep->tip ?? '')));
        $ilanTip = mb_strtolower(trim((string) ($ilan->tip ?? $ilan->yayin_tipi_id ?? '')));

        if ($talepTip !== '' && ($talepTip === $ilanTip || $this->isTransactionTypeEquivalent($talepTip, $ilanTip))) {
            $score += $maxWeight * 0.50; // 12.5
            $reasons[] = 'type_match';
            $reasons[] = 'İşlem türü (Satılık/Kiralık) tam uyumlu';
        }

        return $score;
    }

    private function isTransactionTypeEquivalent(string $type1, string $type2): bool
    {
        $map = [
            'satilik' => 'satilik',
            'satılık' => 'satilik',
            '1'       => 'satilik',
            'kiralik' => 'kiralik',
            'kiralık' => 'kiralik',
            '2'       => 'kiralik',
            'gunluk'  => 'gunluk',
            'günlük'  => 'gunluk',
            '3'       => 'gunluk',
            'devren'  => 'devren',
            '4'       => 'devren',
        ];

        $norm1 = $map[$type1] ?? $type1;
        $norm2 = $map[$type2] ?? $type2;

        return $norm1 === $norm2;
    }
}
