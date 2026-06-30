<?php

namespace App\Services\Integrations;

use App\Enums\IlanDurumu;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * İmar Planı Servisi
 *
 * Belediye açık veri portallarından imar planı bilgilerini çeker
 *
 * Context7: İmar planı verileri entegrasyonu
 */
class ImarPlanService
{
    protected BelediyeOpenDataService $belediyeService;

    public function __construct(BelediyeOpenDataService $belediyeService)
    {
        $this->belediyeService = $belediyeService;
    }

    /**
     * İmar planı bilgisi getir (İstanbul BB)
     *
     * @param string $mahalleId Mahalle ID veya adı
     * @return array
     */
    public function getImarPlani(string $mahalleId): array
    {
        // Demo için mock data döndür
        // Gerçek entegrasyon için İstanbul BB resource_id gerekli

        return Cache::remember("imar_plani.{$mahalleId}", 86400, function () use ($mahalleId) {
            // Mock data - gerçek API entegrasyonu için resource_id gerekli
            return [
                'success' => true,
                'mahalle_id' => $mahalleId,
                'imar_durumu' => 'Yapılaşmaya açık',
                'kaks' => 0.4, // Kat Alanı Katsayısı
                'taks' => 0.3, // Taban Alanı Katsayısı
                'gabari' => 12.5, // Metre
                'yapilasma_yogunlugu' => 'Orta',
                'plan_turu' => '1/1000 İmar Planı',
                'onay_tarihi' => '2023-01-15',
                'gecerlilik_statusu' => IlanDurumu::YAYINDA->value,
                'source' => 'mock_data', // Gerçekte 'istanbul_bb' olacak
            ];
        });
    }

    /**
     * Koordinat bazlı imar planı sorgusu
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return array
     */
    public function getImarPlaniByCoordinates(float $lat, float $lng): array
    {
        $cacheKey = "imar_plani.coords." . md5("{$lat}_{$lng}");

        return Cache::remember($cacheKey, 86400, function () use ($lat, $lng) {
            // Mock data
            return [
                'success' => true,
                'coordinates' => ['lat' => $lat, 'lng' => $lng],
                'imar_durumu' => 'Yapılaşmaya açık',
                'kaks' => 0.4,
                'taks' => 0.3,
                'gabari' => 12.5,
                'yapilasma_yogunlugu' => 'Orta',
                'plan_turu' => '1/1000 İmar Planı',
                'source' => 'mock_data',
            ];
        });
    }

    /**
     * İmar planı uygunluk kontrolü
     *
     * @param array $arsaData Arsa bilgileri
     * @return array
     */
    public function checkImarUygunlugu(array $arsaData): array
    {
        $imarPlani = $this->getImarPlani($arsaData['mahalle_id'] ?? '');

        if (!$imarPlani['success']) {
            return [
                'success' => false,
                'uygun' => false,
                'mesaj' => 'İmar planı bilgisi alınamadı',
            ];
        }

        $uygun = true;
        $mesajlar = [];

        // KAKS kontrolü
        if (isset($arsaData['kaks']) && $arsaData['kaks'] > $imarPlani['kaks']) {
            $uygun = false;
            $mesajlar[] = "KAKS değeri izin verilen değeri aşıyor (İzin: {$imarPlani['kaks']}, Girilen: {$arsaData['kaks']})";
        }

        // TAKS kontrolü
        if (isset($arsaData['taks']) && $arsaData['taks'] > $imarPlani['taks']) {
            $uygun = false;
            $mesajlar[] = "TAKS değeri izin verilen değeri aşıyor (İzin: {$imarPlani['taks']}, Girilen: {$arsaData['taks']})";
        }

        // Gabari kontrolü
        if (isset($arsaData['gabari']) && $arsaData['gabari'] > $imarPlani['gabari']) {
            $uygun = false;
            $mesajlar[] = "Gabari değeri izin verilen değeri aşıyor (İzin: {$imarPlani['gabari']}m, Girilen: {$arsaData['gabari']}m)";
        }

        return [
            'success' => true,
            'uygun' => $uygun,
            'imar_plani' => $imarPlani,
            'mesajlar' => $mesajlar,
            'oneri' => $uygun ? 'İmar planına uygun' : 'İmar planına uygun değil',
        ];
    }
}
