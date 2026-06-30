<?php

namespace App\Domain\Core\Cache;

/**
 * Interface CacheIsolationContextContract
 * @package App\Domain\Core\Cache
 * @description Phase 18: Çoklu kiracı önbellek katmanında anahtar kanonizasyonu ve yalıtım sınırlarını belirleyen anayasal kontrat.
 */
interface CacheIsolationContextContract
{
    /**
     * İlgili kiracı kimliğine ve jenerik anahtara göre kriptografik ve izole bir önbellek anahtarı (Cache Key) üretir.
     * Context7 Kanonik Kelime Seti ve SAB Madde 20 kurallarına tabidir.
     *
     * @param int $tenantId
     * @param string $jenerikAnahtar Örn: 'ilan_detay_501'
     * @return string Kanonik Önbellek Anahtarı (Örn: 'tenant:1:hub:ilan_detay_501')
     */
    public function generateIsolatedKey(int $tenantId, string $jenerikAnahtar): string;

    /**
     * Önbellek geçersiz kılma (Invalidation) esnasında "Cache Stampede" blokajı için erken yenileme (XFetch) olasılığını hesaplar.
     *
     * @param string $isolatedKey
     * @param int $ttlSeconds Orijinal yaşam süresi
     * @param float $beta Olasılık çarpanı (SAB Standart: 1.0)
     * @return bool Erken yenileme tetiklenmeli mi?
     */
    public function shouldEarlyRefresh(string $isolatedKey, int $ttlSeconds, float $beta = 1.0): bool;
}
