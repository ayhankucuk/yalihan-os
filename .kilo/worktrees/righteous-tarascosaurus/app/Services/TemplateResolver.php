<?php

namespace App\Services;

/**
 * @sab-ignore-catch
 */

use App\Contracts\TemplateResolverInterface;
use App\Exceptions\TemplateCategoryMismatchException;
use App\Exceptions\TemplateNotFoundException;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Ups\UpsCacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * 🎨 TEMPLATE RESOLVER (V2)
 *
 * Sorumluluk: Kategori ve yayın tipi bazlı şablon çözümleme.
 * V2 sisteminde yayın tipi bazlı Master Template (YayinTipiSablonu) kullanılır.
 *
 * Cache: UpsCacheService üzerinden (registry takipli, invalidation garantili).
 * Race condition: Cache::remember atomik çağrısı ile giderildi.
 */
class TemplateResolver implements TemplateResolverInterface
{
    protected const CACHE_TTL = 3600;

    public function __construct(
        private UpsCacheService $cacheService
    ) {}

    /**
     * PRIMARY: Junction-first template cozumleme.
     *
     * SAB Kural 2: Birincil SoT = junction_id (YayinTipiSablonu.id).
     * SAB Kural 3: requestKategoriId verilmisse junction.kategori_id ile eslesme zorunlu.
     *             Eslesme yoksa: TemplateCategoryMismatchException (FAIL-FAST, fallback yasak).
     * SAB Kural 4: Deterministic — orderBy('id') explicit, belirsiz first() yasak.
     */
    public function resolveByJunction(int $junctionId, ?int $requestKategoriId = null): YayinTipiSablonu
    {
        if ($junctionId <= 0) {
            throw new InvalidArgumentException('junction_id must be positive, got: '.$junctionId);
        }

        // Collision-proof cache key: junction_id + opsiyonel kategori guard
        $cacheKey = $this->cacheService->buildJunctionKey($junctionId, $requestKategoriId);

        $template = $this->cacheService->rememberJunction($cacheKey, self::CACHE_TTL, function () use ($junctionId) {
            return YayinTipiSablonu::active()
                ->where('id', $junctionId)
                ->orderBy('id') // deterministic — tek satir beklenilen ama explicit zorunlu // context7-ignore
                ->first();
        });

        if (! $template) {
            Log::error('template_junction_not_found', [
                'junction_id'          => $junctionId,
                'request_kategori_id'  => $requestKategoriId,
            ]);
            throw new TemplateNotFoundException("Template not found for junction_id: {$junctionId}");
        }

        // Category consistency guard (SAB Kural 3)
        if ($requestKategoriId !== null && (int) $template->kategori_id !== $requestKategoriId) {
            Log::error('template_category_mismatch', [
                'junction_id'          => $junctionId,
                'request_kategori_id'  => $requestKategoriId,
                'junction_kategori_id' => $template->kategori_id,
            ]);
            throw new TemplateCategoryMismatchException(
                junctionId: $junctionId,
                requestKategoriId: $requestKategoriId,
                junctionKategoriId: $template->kategori_id,
            );
        }

        return $template;
    }

    /**
     * Junction cache'ini temizle.
     */
    public function clearJunctionCache(int $junctionId, ?int $kategoriId = null): void
    {
        $this->cacheService->forgetJunctionKey($junctionId, $kategoriId);
    }

    /**
     * Resolve a template based on category and publication type string.
     *
     * @deprecated Yeni kodda resolveByJunction() kullanin.
     *
     * Resolve Master Template (YayinTipiSablonu) based on slugified publication type.
     *
     * @param int $kategoriId
     * @param string $yayinTipi Slug or Name
     * @return YayinTipiSablonu
     * @throws TemplateNotFoundException
     */
    public function resolve(int $kategoriId, string $yayinTipi): ?YayinTipiSablonu
    {
        if ($kategoriId <= 0) {
            throw new InvalidArgumentException("kategori_id must be positive");
        }

        if (empty(trim($yayinTipi))) {
            throw new InvalidArgumentException("yayin_tipi cannot be empty");
        }

        // 1. Get Category Context for Slug Generation
        $kategori = IlanKategori::find($kategoriId);
        $kategoriName = $kategori?->name ?? '';
        $typeSlug = Str::slug($yayinTipi);
        $categoryPrefixedSlug = $kategoriName ? Str::slug($kategoriName . '-' . $yayinTipi) : null;

        // Cache::remember — race condition-free (atomik), UpsCacheService registry takipli
        $cacheKey = $this->cacheService->buildResolverKey($kategoriId, $typeSlug);

        $template = $this->cacheService->rememberResolver($cacheKey, self::CACHE_TTL, function () use (
            $categoryPrefixedSlug, $typeSlug, $yayinTipi
        ) {
            $query = YayinTipiSablonu::active();

            if ($categoryPrefixedSlug) {
                $query->where(function ($q) use ($categoryPrefixedSlug, $typeSlug, $yayinTipi) {
                    $q->where('slug', $categoryPrefixedSlug)
                      ->orWhere('slug', $typeSlug)
                      ->orWhere('ad', $yayinTipi);
                });
            } else {
                $query->where(function ($q) use ($typeSlug, $yayinTipi) {
                    $q->where('slug', $typeSlug)
                      ->orWhere('ad', $yayinTipi);
                });
            }

            return $query->orderByRaw("CASE
                    WHEN slug = ? THEN 2
                    WHEN slug = ? THEN 1
                    ELSE 0
                END DESC", [$categoryPrefixedSlug, $typeSlug]) // context7-ignore
                ->orderBy('id') // context7-ignore
                ->first();
        });

        if (!$template) {
            Log::error("Template resolution failed for V2", [
                'kategori_id' => $kategoriId,
                'yayin_tipi' => $yayinTipi,
                'category_slug' => $categoryPrefixedSlug,
                'type_slug' => $typeSlug // context7-ignore
            ]);
            throw new TemplateNotFoundException("Template not found for publication type: {$yayinTipi}");
        }

        return $template;
    }

    /**
     * Check if a template exists.
     */
    public function exists(int $kategoriId, string $yayinTipi): bool
    {
        try {
            $this->resolve($kategoriId, $yayinTipi);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all active templates for a given category.
     */
    public function getTemplatesForCategory(int $kategoriId): Collection
    {
        return YayinTipiSablonu::active()->where('kategori_id', $kategoriId)->siralı()->get();
    }

    /**
     * Clear cache for specific template.
     */
    public function clearCache(int $kategoriId, string $yayinTipi): void
    {
        $slug = Str::slug($yayinTipi);
        $this->cacheService->forgetResolverKey($kategoriId, $slug);
    }
}
