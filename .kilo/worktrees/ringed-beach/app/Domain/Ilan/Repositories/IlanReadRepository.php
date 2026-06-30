<?php

namespace App\Domain\Ilan\Repositories;

use App\Domain\Core\Cache\CacheIsolationContextContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class IlanReadRepository
 * @package App\Domain\Ilan\Repositories
 * @description CQRS Okuma Katmanı: İlanlar için önbellek zırhlı, ultra-hafif, tenant-izole veri deposu.
 */
final class IlanReadRepository
{
    private string $table = 'ilanlar_read_model';
    private int $cacheTtl = 3600; // 1 Saat (Projeksiyon handler flush edene kadar yaşar)

    /**
     * @param int $tenantId SAB Madde 1: Mutlak Kiracı İzolasyonu
     * @param CacheIsolationContextContract $cacheContext Phase 18 XFetch ve Anahtar Muhafızı
     */
    public function __construct(
        private readonly int $tenantId,
        private readonly CacheIsolationContextContract $cacheContext
    ) {}

    /**
     * Belirli bir ilanın flat detayını mikro saniye seviyesinde önbellekten getirir.
     *
     * @param int $ilanId
     * @return object|null
     */
    public function findById(int $ilanId): ?object
    {
        $jenerikAnahtar = "ilan_detay_{$ilanId}";
        $isolatedKey = $this->cacheContext->generateIsolatedKey($this->tenantId, $jenerikAnahtar);

        return Cache::tags(["tenant:{$this->tenantId}:ilanlar", "ilan:{$ilanId}"])
            ->remember($isolatedKey, $this->cacheTtl, function () use ($ilanId) {
                return DB::table($this->table)
                    ->where('tenant_id', $this->tenantId)
                    ->where('ilan_id', $ilanId)
                    ->where('aktiflik_durumu', '!=', 0)
                    ->first();
            });
    }

    /**
     * Gelişmiş filtrelerle sayfalama yapar ve etiketli önbelleğe alır.
     *
     * @param array<string, mixed> $filtreler
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(array $filtreler = [], int $perPage = 15): LengthAwarePaginator
    {
        $filtreHash = md5(json_encode($filtreler) . $perPage . request('page', 1));
        $jenerikAnahtar = "ilan_liste_{$filtreHash}";
        $isolatedKey = $this->cacheContext->generateIsolatedKey($this->tenantId, $jenerikAnahtar);

        return Cache::tags(["tenant:{$this->tenantId}:ilanlar", "ilan_listeleri"])
            ->remember($isolatedKey, $this->cacheTtl, function () use ($filtreler, $perPage) {
                $query = DB::table($this->table)
                    ->where('tenant_id', $this->tenantId)
                    ->where('aktiflik_durumu', 1);

                if (!empty($filtreler['denetim_tipi'])) {
                    $query->where('denetim_tipi', $filtreler['denetim_tipi']);
                }

                if (!empty($filtreler['min_fiyat'])) {
                    $query->where('fiyat', '>=', (float)$filtreler['min_fiyat']);
                }
                if (!empty($filtreler['max_fiyat'])) {
                    $query->where('fiyat', '<=', (float)$filtreler['max_fiyat']);
                }

                return $query->orderBy('updated_at', 'desc')->paginate($perPage);
            });
    }
}
