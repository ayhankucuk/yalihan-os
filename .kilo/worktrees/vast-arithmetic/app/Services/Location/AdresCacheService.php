<?php

namespace App\Services\Location;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\Ulke;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * 🛡️ SAB Sprint 1 — Controller Cache Extraction
 * AdresYonetimiController'daki tüm Cache:: çağrıları bu service'e taşındı.
 *
 * Authority: .sab/authority.json §RULE-C2 (Cache mutation in controller forbidden)
 * Baseline ref: controller_cache.count 65 → hedef ≤10
 *
 * TTL sabitleri:
 *   ULKE_TTL = 7200s (2 saat) — nadiren değişir
 *   IL_TTL   = 7200s (2 saat) — nadiren değişir
 *   ILCE_TTL = 3600s (1 saat) — orta sıklık
 *   MAHALLE_TTL = 3600s (1 saat) — orta sıklık
 */
class AdresCacheService
{
    private const ULKE_KEY = 'adres_yonetimi_ulkeler';

    private const IL_KEY = 'adres_yonetimi_iller';

    private const TUM_ILCE_KEY = 'adres_yonetimi_all_ilceler';

    private const ULKE_TTL = 7200;

    private const IL_TTL = 7200;

    private const ILCE_TTL = 3600;

    private const MAHALLE_TTL = 3600;

    // ─── READ ────────────────────────────────────────────────────────────────

    /**
     * Tüm ülkeleri cache'li döndür.
     */
    public function ulkeler(): Collection
    {
        return Cache::remember(self::ULKE_KEY, self::ULKE_TTL, function () {
            return Ulke::select(['id', 'ulke_adi'])
                ->orderBy('ulke_adi') // context7-ignore
                ->get();
        });
    }

    /**
     * Tüm illeri cache'li döndür.
     */
    public function iller(): Collection
    {
        return Cache::remember(self::IL_KEY, self::IL_TTL, function () {
            return Il::select(['id', 'il_adi'])
                ->orderBy('il_adi') // context7-ignore
                ->get();
        });
    }

    /**
     * Tüm ilçeleri (il bilgisiyle birlikte) cache'li döndür.
     * create/edit formları için parentOptions olarak kullanılır.
     */
    public function tumIlceler(): Collection
    {
        return Cache::remember(self::TUM_ILCE_KEY, self::ILCE_TTL, function () {
            return Ilce::select(['id', 'il_id', 'ilce_adi'])
                ->with('il:id,il_adi')
                ->orderBy('ilce_adi') // context7-ignore
                ->get();
        });
    }

    /**
     * Belirli bir ile ait ilçeleri cache'li döndür.
     */
    public function ilcelerByIl(int $ilId): Collection
    {
        return Cache::remember("adres_yonetimi_ilceler_il_{$ilId}", self::ILCE_TTL, function () use ($ilId) {
            return Ilce::select(['id', 'il_id', 'ilce_adi'])
                ->where('il_id', $ilId)
                ->orderBy('ilce_adi') // context7-ignore
                ->get();
        });
    }

    /**
     * Belirli bir ilçeye ait mahalleleri cache'li döndür.
     */
    public function mahallelerByIlce(int $ilceId): Collection
    {
        return Cache::remember("adres_yonetimi_mahalleler_ilce_{$ilceId}", self::MAHALLE_TTL, function () use ($ilceId) {
            return Mahalle::select(['id', 'ilce_id', 'mahalle_adi'])
                ->where('ilce_id', $ilceId)
                ->orderBy('mahalle_adi') // context7-ignore
                ->get();
        });
    }

    // ─── INVALIDATE ──────────────────────────────────────────────────────────

    public function invalidateUlkeler(): void
    {
        Cache::forget(self::ULKE_KEY);
    }

    public function invalidateIller(): void
    {
        Cache::forget(self::IL_KEY);
    }

    public function invalidateTumIlceler(): void
    {
        Cache::forget(self::TUM_ILCE_KEY);
    }

    public function invalidateIlcelerByIl(int $ilId): void
    {
        Cache::forget("adres_yonetimi_ilceler_il_{$ilId}");
    }

    public function invalidateMahallelerByIlce(int $ilceId): void
    {
        Cache::forget("adres_yonetimi_mahalleler_ilce_{$ilceId}");
    }

    /**
     * İlçe mutasyonu sonrası toplu invalidate.
     * store/update/destroy/bulkDelete tarafından kullanılır.
     *
     * @param  int[]  $ilIds  Etkilenen il ID'leri
     */
    public function invalidateIlceGroup(array $ilIds): void
    {
        $this->invalidateTumIlceler();
        foreach ($ilIds as $ilId) {
            $this->invalidateIlcelerByIl((int) $ilId);
        }
    }

    /**
     * Mahalle mutasyonu sonrası toplu invalidate.
     *
     * @param  int[]  $ilceIds  Etkilenen ilçe ID'leri
     */
    public function invalidateMahalleGroup(array $ilceIds): void
    {
        foreach ($ilceIds as $ilceId) {
            $this->invalidateMahallelerByIlce((int) $ilceId);
        }
    }
}
