<?php

namespace App\Domain\Core\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class CacheIsolationContext
 * @package App\Domain\Core\Cache
 * @description Phase 18: Çoklu kiracı önbellek katmanında anahtar kanonizasyonu ve XFetch Stampede bariyeri gerçekleştiren anayasal sınıf.
 */
class CacheIsolationContext implements CacheIsolationContextContract
{
    /**
     * İlgili kiracı kimliğine ve jenerik anahtara göre kriptografik ve izole bir önbellek anahtarı (Cache Key) üretir.
     * Context7 Kanonik Kelime Seti ve SAB Madde 20 kurallarına tabidir.
     *
     * @param int $tenantId
     * @param string $jenerikAnahtar Örn: 'ilan_detay_501'
     * @return string Kanonik Önbellek Anahtarı (Örn: '{tenant:1}:hub:ilan_detay_501')
     */
    public function generateIsolatedKey(int $tenantId, string $jenerikAnahtar): string
    {
        // SAB Enforced: Redis Cluster Slot-Pinning (Hash Tag) Format
        $prefix = "{tenant:{$tenantId}}";
        
        return "{$prefix}:hub:{$jenerikAnahtar}";
    }

    /**
     * Önbellek geçersiz kılma (Invalidation) esnasında "Cache Stampede" blokajı için erken yenileme (XFetch) olasılığını hesaplar.
     *
     * @param string $isolatedKey
     * @param int $ttlSeconds Orijinal yaşam süresi
     * @param float $beta Olasılık çarpanı (SAB Standart: 1.0)
     * @return bool Erken yenileme tetiklenmeli mi?
     */
    public function shouldEarlyRefresh(string $isolatedKey, int $ttlSeconds, float $beta = 1.0): bool
    {
        try {
            $cachedData = Cache::get($isolatedKey);

            // Eğer önbellekte veri yoksa veya format XFetch uyumlu değilse erken yenilemeye gerek yoktur (cache miss yollarından geçecektir)
            if (!is_array($cachedData) || !isset($cachedData['expires_at']) || !isset($cachedData['delta'])) {
                return false;
            }

            // Kalan TTL süresini saniye/milisaniye cinsinden hassasiyetle hesapla
            $remainingTtl = $cachedData['expires_at'] - microtime(true);
            $delta = $cachedData['delta']; // Veriyi hesaplama/çekme latansı (saniye cinsinden)

            // XFetch Algoritması: -beta * delta * ln(rand()) > remainingTtl
            // mt_rand() kullanarak [0, 1) exclusive rastgele sayı üret
            $rand = mt_rand(1, mt_getrandmax() - 1) / mt_getrandmax();
            $xFetchValue = - $beta * $delta * log($rand);

            $shouldRefresh = $xFetchValue > $remainingTtl;

            if ($shouldRefresh) {
                Log::debug("SAB CACHE STAMPEDE BARRIER: XFetch triggered early refresh.", [
                    'key' => $isolatedKey,
                    'remaining_ttl' => $remainingTtl,
                    'delta' => $delta,
                    'xfetch_value' => $xFetchValue
                ]);
            }

            return $shouldRefresh;
        } catch (\Exception $e) {
            Log::warning("SAB CACHE WARNING: XFetch calculation failed, falling back to standard expiration.", [
                'key' => $isolatedKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
