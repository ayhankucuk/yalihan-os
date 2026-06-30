<?php

namespace App\Services\Wizard;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Ups\PropertyPublicationPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * EffectiveListingTypeResolver — SINGLE SOURCE OF TRUTH for wizard yayın tipi resolution.
 *
 * Cascade logic:
 * 1. Try alt_kategori (seviye=1) → most specific
 * 2. Fallback to ana_kategori (seviye=0)
 * 3. Empty = category has no allowed publication types
 *
 * Used by: Wizard Step 1, backend validation, API endpoint.
 * Delegates to: PropertyPublicationPolicy (the underlying policy engine).
 */
class EffectiveListingTypeResolver
{
    public function __construct(
        private PropertyPublicationPolicy $policy
    ) {}

    /**
     * Resolve allowed publication types for a category pair.
     *
     * @param int $mainCategoryId  Ana kategori ID (seviye=0)
     * @param int|null $subCategoryId  Alt kategori ID (seviye=1), optional
     * @return Collection Collection of YayinTipiSablonu or IlanKategori (seviye=2) models
     */
    public function resolve(int $mainCategoryId, ?int $subCategoryId = null): Collection
    {
        // 1. Try sub-category first (most specific)
        if ($subCategoryId) {
            $types = $this->policy->getAllowedTypes($subCategoryId);
            if ($types->isNotEmpty()) {
                return $types;
            }
        }

        // 2. Fallback to main category
        $types = $this->policy->getAllowedTypes($mainCategoryId);

        if ($types->isEmpty()) {
            Log::warning('EffectiveListingTypeResolver: No publication types found', [
                'ana_kategori_id' => $mainCategoryId,
                'alt_kategori_id' => $subCategoryId,
            ]);
        }

        return $types;
    }

    /**
     * Resolve and return IDs only.
     *
     * @return array<int>
     */
    public function resolveIds(int $mainCategoryId, ?int $subCategoryId = null): array
    {
        // 1. Try sub-category first
        if ($subCategoryId) {
            $ids = $this->policy->allowedForCategory($subCategoryId);
            if (!empty($ids)) {
                return $ids;
            }
        }

        // 2. Fallback to main category
        return $this->policy->allowedForCategory($mainCategoryId);
    }

    /**
     * Validate that a specific yayın tipi is allowed for the category pair.
     *
     * @throws \InvalidArgumentException if not allowed
     */
    public function validate(int $mainCategoryId, ?int $subCategoryId, int $yayinTipiId): void
    {
        $targetCategoryId = $subCategoryId ?? $mainCategoryId;
        $this->policy->validate($targetCategoryId, $yayinTipiId);
    }

    /**
     * Boolean check: is yayın tipi allowed for this category pair?
     */
    public function isAllowed(int $mainCategoryId, ?int $subCategoryId, int $yayinTipiId): bool
    {
        // Check sub-category first
        if ($subCategoryId) {
            if ($this->policy->isAllowed($subCategoryId, $yayinTipiId)) {
                return true;
            }
        }

        // Fallback to main category
        return $this->policy->isAllowed($mainCategoryId, $yayinTipiId);
    }

    /**
     * Get debug info for the resolution chain.
     *
     * @return array{main_category: array, sub_category: ?array, resolved_types: array, resolution_source: string}
     */
    public function debug(int $mainCategoryId, ?int $subCategoryId = null): array
    {
        $mainCat = IlanKategori::find($mainCategoryId);
        $subCat = $subCategoryId ? IlanKategori::find($subCategoryId) : null;

        $subResult = $subCategoryId ? $this->policy->allowedForCategory($subCategoryId) : [];
        $mainResult = $this->policy->allowedForCategory($mainCategoryId);

        $resolvedIds = !empty($subResult) ? $subResult : $mainResult;
        $source = !empty($subResult) ? 'sub_category' : 'main_category';

        $ytMap = YayinTipiSablonu::whereIn('id', $resolvedIds)->pluck('ad', 'id')->toArray();

        return [
            'main_category' => [
                'id' => $mainCategoryId,
                'name' => $mainCat?->name,
                'slug' => $mainCat?->slug,
                'policy_ids' => $mainResult,
            ],
            'sub_category' => $subCat ? [
                'id' => $subCategoryId,
                'name' => $subCat->name,
                'slug' => $subCat->slug,
                'policy_ids' => $subResult,
            ] : null,
            'resolved_types' => array_map(
                fn($id) => ['id' => $id, 'name' => $ytMap[$id] ?? "?{$id}"],
                $resolvedIds
            ),
            'resolution_source' => $source,
            'has_explicit_policy' => $this->policy->hasExplicitPolicy($subCategoryId ?? $mainCategoryId),
        ];
    }
}
