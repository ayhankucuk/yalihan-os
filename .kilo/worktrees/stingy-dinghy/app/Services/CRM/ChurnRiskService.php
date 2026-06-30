<?php

namespace App\Services\CRM;

use App\Models\Ilan;
use Carbon\Carbon;

/**
 * Müşteri Kayıp Riski Hesaplama Servisi
 *
 * Context7 Standard: C7-CHURN-ENGINE-2026-01-06
 *
 * @governance PUBLIC_CORPUS — global_avg_views cache key tenant-agnostic kasıtlı (tüm tenantlar için ortalama)
 */
class ChurnRiskService
{
    /**
     * İlan için kayıp riskini hesaplar.
     *
     * @param Ilan $ilan
     * @param float $averageViewCount Bölge/Kategori ortalama görüntülenme sayısı
     * @return array
     */
    public function calculate(Ilan $ilan, float $averageViewCount = 100): array
    {
        $score = 0;
        $reasons = [];

        // 1. Zaman Aşımı Riski (> 90 gün)
        // Eğer ilan çok uzun süredir yayındaysa, mülk sahibi umudunu kaybetmiş olabilir.
        if ($ilan->created_at) {
            $daysOnMarket = $ilan->created_at->diffInDays(now());
            if ($daysOnMarket > 90) {
                $score += 30;
                $reasons[] = "90+ gündür yayında ($daysOnMarket gün)";
            }
        }

        // 2. Fiyat Durgunluğu Riski (> 60 gün)
        // Fiyat uzun süredir güncellenmediyse, piyasa rekabetini kaybetmiş olabilir.
        // Fiyat güncelleme tarihi yoksa updated_at kullanılır.
        // İdeal senaryoda IlanPriceHistory tablosuna bakılır ama performans için updated_at kullanıyoruz.
        if ($ilan->updated_at) {
            $daysSinceLastUpdate = $ilan->updated_at->diffInDays(now());
            if ($daysSinceLastUpdate > 60) {
                $score += 25;
                $reasons[] = "Fiyat/İlan 60+ gündür güncellenmedi";
            }
        }

        // 3. İlgi Düşüklüğü Riski
        // Görüntülenme ortalamanın altındaysa risk artar.
        $views = $ilan->goruntulenme ?? 0;
        if ($views < ($averageViewCount * 0.5)) { // Ortalamanın yarısından azsa
            $score += 20;
            $reasons[] = "Düşük etkileşim (Bölge ortalamasının altında)";
        }

        // 4. Sözleşme Bitiş Riski (< 15 gün) - KRİTİK
        // Yetki belgesi bitiyorsa müşteri kesinlikle kaybedilmek üzeredir.
        // Not: Field veritabanında olmayabilir, null check yapıyoruz.
        if (isset($ilan->yetki_belgesi_bitis_tarihi) && $ilan->yetki_belgesi_bitis_tarihi) {
            $contractEndDate = Carbon::parse($ilan->yetki_belgesi_bitis_tarihi);
            $daysLeft = now()->diffInDays($contractEndDate, false);

            if ($daysLeft <= 15 && $daysLeft >= 0) {
                $score = 100; // Direkt Kritik
                $reasons[] = "Yetki belgesi bitimine $daysLeft gün kaldı!";
            } elseif ($daysLeft < 0) {
                $score = 100;
                $reasons[] = "Yetki belgesi süresi dolmuş!";
            }
        }

        // Skoru 100 ile sınırla
        $score = min(100, max(0, $score));

        // Seviye Belirleme
        $level = match (true) {
            $score >= 71 => 'critical',
            $score >= 41 => 'warning',
            default => 'low'
        };

        return [
            'score' => $score,
            'level' => $level, // low, warning, critical
            'reasons' => $reasons,
            'primary_reason' => $reasons[0] ?? null
        ];
    }

    /**
     * İlan listesine (Paginator/Collection) risk verilerini iliştirir.
     *
     * @param mixed $listings
     * @return mixed
     */
    public function attachRiskToPaginator($listings)
    {
        // ✅ Global ortalama görüntülenme (Cache Authority)
        $avgViews = \Illuminate\Support\Facades\Cache::remember('global_avg_views', 3600, function () {
            return \App\Models\Ilan::avg('goruntulenme') ?? 100;
        });

        // Loop through items and attach calculation results
        $listings->getCollection()->transform(function ($ilan) use ($avgViews) {
            $ilan->churn_risk = $this->calculate($ilan, $avgViews);
            return $ilan;
        });

        return $listings;
    }
}
