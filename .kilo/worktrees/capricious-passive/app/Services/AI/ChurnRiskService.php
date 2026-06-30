<?php

namespace App\Services\AI;

use App\Models\Ilan;
use Carbon\Carbon;

/**
 * Churn Risk Service (Simplified Version)
 * 
 * Context7 Standard: C7-CHURN-RISK-2026-01-06
 * 
 * Müşteri kaybı riskini hesaplar.
 * Gelecekte ML modeli ile geliştirilebilir.
 */
class ChurnRiskService
{
    /**
     * İlan için churn risk skoru hesapla (0-100)
     * 
     * Faktörler:
     * - İlan yaşı (eski ilanlar daha riskli)
     * - Görüntülenme sayısı (az görüntülenen riskli)
     * - Son güncelleme (uzun süre güncellenmemiş riskli)
     * - Fiyat değişikliği (değişmemiş fiyat riskli)
     * 
     * @param Ilan $ilan
     * @return int
     */
    public function calculateChurnRisk(Ilan $ilan): int
    {
        $risk = 0;

        // 1. İlan yaşı (max 40 puan)
        $daysSinceCreated = $ilan->created_at ? $ilan->created_at->diffInDays(now()) : 0;
        
        if ($daysSinceCreated > 90) {
            $risk += 40; // 90+ gün çok riskli
        } elseif ($daysSinceCreated > 60) {
            $risk += 30;
        } elseif ($daysSinceCreated > 30) {
            $risk += 20;
        }

        // 2. Görüntülenme performansı (max 30 puan)
        $viewsPerDay = $daysSinceCreated > 0 ? ($ilan->views_count ?? 0) / $daysSinceCreated : 0;
        
        if ($viewsPerDay < 1) {
            $risk += 30; // Günde 1'den az görüntülenme
        } elseif ($viewsPerDay < 3) {
            $risk += 20;
        } elseif ($viewsPerDay < 5) {
            $risk += 10;
        }

        // 3. Son güncelleme (max 30 puan)
        $daysSinceUpdate = $ilan->updated_at ? $ilan->updated_at->diffInDays(now()) : 0;
        
        if ($daysSinceUpdate > 30) {
            $risk += 30; // 30+ gün güncellenmemiş
        } elseif ($daysSinceUpdate > 15) {
            $risk += 20;
        } elseif ($daysSinceUpdate > 7) {
            $risk += 10;
        }

        // 4. Bonus: Yetki belgesi süresi (varsa)
        if (isset($ilan->yetki_belgesi_bitis) && $ilan->yetki_belgesi_bitis) {
            $daysUntilExpiry = Carbon::parse($ilan->yetki_belgesi_bitis)->diffInDays(now(), false);
            
            if ($daysUntilExpiry < 7 && $daysUntilExpiry >= 0) {
                $risk += 20; // Yakında bitiyor!
            } elseif ($daysUntilExpiry < 0) {
                $risk += 30; // Zaten bitmiş!
            }
        }

        return min(100, max(0, $risk));
    }

    /**
     * Yüksek riskli ilanları getir
     * 
     * @param int|null $userId Danışman ID
     * @param int $minRisk Minimum risk skoru
     * @return \Illuminate\Support\Collection
     */
    public function getHighRiskListings(?int $userId = null, int $minRisk = 70)
    {
        $query = Ilan::where('yayin_durumu', 'aktif');

        if ($userId) {
            $query->where('danisman_id', $userId);
        }

        $ilanlar = $query->with(['ilanSahibi'])->get();

        return $ilanlar->map(function ($ilan) {
            return [
                'ilan' => $ilan,
                'risk_score' => $this->calculateChurnRisk($ilan),
            ];
        })
        ->filter(fn($item) => $item['risk_score'] >= $minRisk)
        ->sortByDesc('risk_score')
        ->values();
    }
}
