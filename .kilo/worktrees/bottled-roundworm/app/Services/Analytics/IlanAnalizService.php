<?php

namespace App\Services\Analytics;

use App\Models\Ilan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\Cortex\CortexROIEngine;
use App\Services\Location\PoiService;

use App\Enums\IlanDurumu;

/**
 * IlanAnalizService
 *
 * İlan analitik verilerini toplar, ROI ve POI ile birleştirerek raporlar üretir.
 * Context7: Hayalet Metod yasak, Wildcard Cache zorunlu, isimlendirme mühürlü.
 */
class IlanAnalizService
{
    public function __construct(
        protected CortexROIEngine $roiEngine,
        protected PoiService $poiService
    ) {}

    /**
     * İlan için detaylı analiz raporu oluşturur.
     *
     * @param int $ilanId
     * @return array
     */
    public function getDetayliRapor(int $ilanId): array
    {
        $cacheKey = "ilan:analiz:detay:{$ilanId}";

        return Cache::remember($cacheKey, 3600, function () use ($ilanId) {
            $ilan = Ilan::with(['il', 'ilce', 'anaKategori', 'danisman'])->findOrFail($ilanId);

            // 1. Temel Metrikler
            $metrikler = [
                'goruntulenme_sayisi' => (int) ($ilan->goruntulenme ?? 0),
                'favori_sayisi' => (int) ($ilan->favorite_count ?? 0),
                'metrik_durumu' => $this->getMetrikDurumu($ilan),
                'siralama_sirasi' => (int) ($ilan->display_order ?? 0),
            ];

            // 2. ROI Analizi
            $roiData = $this->roiEngine->calculateROI($ilan);

            // 3. POI Analizi (Çevresel Değer)
            $poiHighlights = $this->poiService->getHighlights($ilan->lat, $ilan->lng);

            return [
                'ilan_id' => $ilan->id,
                'baslik' => $ilan->baslik,
                'metrikler' => $metrikler,
                'roi_analizi' => $roiData,
                'poi_analizi' => $poiHighlights,
                'olusturulma_tarihi' => now()->toIso8601String(),
                'analiz_durumu' => 'Guncel'
            ];
        });
    }

    /**
     * Dashboard için genel istatistikleri döndürür.
     */
    public function getGenelIstatistikler(): array
    {
        $cacheKey = "ilan:analiz:genel:dashboard";

        return Cache::remember($cacheKey, 1800, function () {
            return [
                'toplam_ilan' => Ilan::count(),
                'aktif_ilanlar' => Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->count(),
                'toplam_goruntulenme' => (int) Ilan::sum('goruntulenme'),
                'en_yuksek_roi' => $this->getEnYuksekROIIlanlar(5),
                'analiz_tarihi' => now()->toIso8601String(),
                'metrik_durumu' => 'Stabil'
            ];
        });
    }

    /**
     * En yüksek ROI skoruna sahip ilanları listeler.
     */
    protected function getEnYuksekROIIlanlar(int $limit = 5): array
    {
        return Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereNotNull('roi_skoru')
            ->orderBy('roi_skoru', 'desc') // context7-ignore
            ->limit($limit)
            ->get(['id', 'baslik', 'roi_skoru', 'fiyat', 'para_birimi'])
            ->toArray();
    }

    /**
     * İlanın metrik sağlığını belirler.
     */
    protected function getMetrikDurumu(Ilan $ilan): string
    {
        $views = (int) ($ilan->goruntulenme ?? 0);

        if ($views > 1000) return 'Popüler';
        if ($views > 500) return 'Yukselen';
        if ($views > 0) return 'Stabil';

        return 'Yeni';
    }

    /**
     * Analiz cache'ini temizler (Wildcard Pattern).
     */
    public function clearCache(?int $ilanId = null): void
    {
        if ($ilanId) {
            Cache::forget("ilan:analiz:detay:{$ilanId}");
        } else {
            // Not: Laravel default cache şoförü her zaman wildcard desteklemez.
            // Redis kullanılıyorsa flush yapılabilir veya mühürlü bir prefix ile yönetilir.
            Cache::forget("ilan:analiz:genel:dashboard");
        }
    }
}
