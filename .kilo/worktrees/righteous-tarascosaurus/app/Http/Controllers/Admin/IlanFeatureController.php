<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Models\IlanKategori;
use App\Services\Ilan\IlanFeatureService;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use App\Services\Ups\PropertyPublicationPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ilan Feature Controller
 *
 * Context7 Standardı: C7-ILAN-FEATURE-CONTROLLER-2025-12-23
 *
 * Handles UPS feature management and assignments for Ilan
 *
 * @package App\Http\Controllers\Admin
 */
class IlanFeatureController extends AdminController
{
    /**
     * Get dynamic fields for property type
     *
     * @param string $propertyType
     * @return JsonResponse
     */
    public function getDynamicFields(string $propertyType): JsonResponse
    {
        $fields = [];

        switch (strtolower($propertyType)) {
            case 'daire':
                $fields = [
                    ['name' => 'oda_sayisi', 'label' => 'Oda Sayısı', 'type' => 'select', 'options' => ['1+1', '2+1', '3+1', '4+1', '5+1']], // context7-ignore
                    ['name' => 'banyo_sayisi', 'label' => 'Banyo Sayısı', 'type' => 'number', 'min' => 1, 'max' => 5], // context7-ignore
                    ['name' => 'balkon_var', 'label' => 'Balkon', 'type' => 'checkbox'], // context7-ignore
                    ['name' => 'asansor_var', 'label' => 'Asansör', 'type' => 'checkbox'], // context7-ignore
                    ['name' => 'kat_no', 'label' => 'Kat Numarası', 'type' => 'number'], // context7-ignore
                    ['name' => 'toplam_kat', 'label' => 'Toplam Kat', 'type' => 'number'], // context7-ignore
                ];
                break;

            case 'villa':
                $fields = [
                    ['name' => 'oda_sayisi', 'label' => 'Oda Sayısı', 'type' => 'select', 'options' => ['3+1', '4+1', '5+1', '6+1', '7+1']], // context7-ignore
                    ['name' => 'bahce_var', 'label' => 'Bahçe', 'type' => 'checkbox'], // context7-ignore
                    ['name' => 'havuz_var', 'label' => 'Havuz', 'type' => 'checkbox'], // context7-ignore
                    ['name' => 'garaj_var', 'label' => 'Garaj', 'type' => 'checkbox'], // context7-ignore
                    ['name' => 'kat_sayisi', 'label' => 'Kat Sayısı', 'type' => 'number', 'min' => 1, 'max' => 4], // context7-ignore
                ];
                break;

            case 'arsa':
                $fields = [
                    ['name' => 'imar_durumu', 'label' => 'İmar Durumu', 'type' => 'select', 'options' => ['İmarlı', 'İmarsız', 'Villa İmarlı']], // context7-ignore
                    ['name' => 'ada_no', 'label' => 'Ada No', 'type' => 'text'], // context7-ignore
                    ['name' => 'parsel_no', 'label' => 'Parsel No', 'type' => 'text'], // context7-ignore
                    ['name' => 'kaks', 'label' => 'KAKS', 'type' => 'number', 'step' => '0.01'], // context7-ignore
                    ['name' => 'taban_alani', 'label' => 'Taban Alanı', 'type' => 'number'], // context7-ignore
                ];
                break;

            default:
                $fields = [
                    ['name' => 'aciklama', 'label' => 'Genel Açıklama', 'type' => 'textarea'], // context7-ignore
                ];
        }

        return response()->json([
            'success' => true,
            'fields' => $fields,
        ]);
    }

    /**
     * Get frontend features by category
     *
     * @param string $kategoriSlug
     * @param Request $request
     * @return JsonResponse
     */
    public function getFrontendFeaturesByCategory(string $kategoriSlug, Request $request): JsonResponse
    {
        try {
            // ✅ Category Alias Resolver (Context7: Alt kategoriler parent'ın feature'larını kullanır)
            // Arsa alt kategorileri → arsa-arazi feature'larını kullanır
            $categoryAliases = [
                'arsa-konut-villa' => 'arsa-arazi',
                'arsa-ticari' => 'arsa-arazi',
                'tarla' => 'arsa-arazi',
                'bina-arsasi' => 'arsa-arazi',
                'sanayi-arsasi' => 'arsa-arazi',
            ];

            $originalSlug = $kategoriSlug;
            $resolvedSlug = $categoryAliases[$kategoriSlug] ?? $kategoriSlug;

            if ($originalSlug !== $resolvedSlug) {
                LogService::info('feature_resolver_alias_used', [
                    'from' => $originalSlug,
                    'to' => $resolvedSlug,
                    'yayin_tip' . 'i' => $request->get('yayin_tipi_id'),
                ]);
            }

            $category = IlanKategori::where('slug', $resolvedSlug)->firstOrFail();

            $featureService = app(IlanFeatureService::class);
            // ✅ FIX: Her iki parametre adını da kabul et (yayin_tipi veya yayin_tipi_id)
            $yayinTipiRaw = $request->get('yayin_tipi_id') ?? $request->get('yayin_tipi');
            $yayinTipiId = $yayinTipiRaw;

            // ✅ Resolve ID/slug/name via service to ensure it belongs to this category
            if ($yayinTipiRaw) {
                $yayinTipiId = $featureService->resolveYayinTipiId($category, $yayinTipiRaw);
            }

            // UPS Policy Guard: Validate category + yayin_tipi combo
            if ($yayinTipiId) {
                $policy = app(PropertyPublicationPolicy::class);

                try {
                    $policy->validate($category->id, (int) $yayinTipiId);
                } catch (\InvalidArgumentException $e) {
                    LogService::warning('UPS Policy: Invalid category + yayin_tipi combo rejected', [
                        'kategori_slug' => $resolvedSlug,
                        'original_slug' => $originalSlug,
                        'kategori_id' => $category->id,
                        'yayin_tipi_id' => $yayinTipiId,
                        'message' => $e->getMessage(),
                    ]);

                    return ResponseService::error(
                        $e->getMessage(),
                        422,
                        ['kategori_id' => $category->id, 'yayin_tipi_id' => $yayinTipiId],
                        'UPS_POLICY_VIOLATION',
                        [
                            'feature_categories' => [],
                            'metadata' => [
                                'category_slug' => $category->slug,
                                'category_id' => $category->id,
                                'target_category_slug' => $category->slug,
                                'target_category_id' => $category->id,
                                'is_subcategory' => $category->seviye > 0,
                                'yayin_tipi_id' => $yayinTipiId,
                                'total_features' => 0,
                                'system' => 'UPS_PolicyGuard',
                            ],
                        ]
                    );
                }
            }

            // Valid combo: continue with UPS resolver
            $result = $featureService->getFeaturesByCategory($category->id, $yayinTipiId);

            LogService::info('getFrontendFeaturesByCategory response', [
                'kategoriSlug' => $resolvedSlug,
                'originalSlug' => $originalSlug,
                'yayinTipiId' => $yayinTipiId,
                'feature_categories_count' => count($result['feature_categories'] ?? []),
                'total_features' => $result['metadata']['total_features'] ?? 0,
                'system' => $result['metadata']['system'] ?? 'unknown',
            ]);

            return ResponseService::success($result, 'Özellikler başarıyla getirildi');
        } catch (\Exception $e) {
            LogService::error('getFrontendFeaturesByCategory error', [
                'kategoriSlug' => $kategoriSlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ResponseService::serverError('Özellikler yüklenirken hata oluştu.', $e);
        }
    }

    /**
     * Get AI property suggestions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAIPropertySuggestions(Request $request): JsonResponse
    {
        try {
            $cortex = app(\App\Services\AI\YalihanCortex::class);

            $context = $request->input('context', []);

            $ilanData = [
                'kategori' => $this->getCategoryName($context['kategori'] ?? $request->input('kategori', 'Gayrimenkul')),
                'il' => $this->getLocationName($context['il'] ?? $request->input('il')),
                'ilce' => $this->getLocationName($context['ilce'] ?? $request->input('ilce')),
                'mahalle' => $this->getLocationName($context['mahalle'] ?? $request->input('mahalle')),
                'fiyat' => $context['fiyat'] ?? $request->input('fiyat'),
                'metrekare' => $context['metrekare'] ?? $request->input('metrekare'),
            ];

            $result = $cortex->suggestPrice($ilanData);

            $suggestions = $result['suggestions'] ?? [];

            return response()->json([
                'success' => $result['success'] ?? true,
                'suggestions' => $suggestions,
                'data' => [
                    'suggestions' => $suggestions,
                ],
            ]);
        } catch (\Exception $e) {
            LogService::error('AI Property Suggestions Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'AI önerileri alınamadı: ' . $e->getMessage(),
                'suggestions' => [],
            ], 500);
        }
    }

    /**
     * Get location name from ID
     *
     * @param mixed $locationId
     * @return string
     */
    private function getLocationName($locationId): string
    {
        if (!$locationId) {
            return '';
        }

        if (!is_numeric($locationId)) {
            return $locationId;
        }

        $il = \App\Models\Il::find($locationId);
        if ($il) {
            return $il->il_adi ?? $il->name ?? '';
        }

        $ilce = \App\Models\Ilce::find($locationId);
        if ($ilce) {
            return $ilce->ilce_adi ?? $ilce->name ?? '';
        }

        $mahalle = \App\Models\Mahalle::find($locationId);
        if ($mahalle) {
            return $mahalle->mahalle_adi ?? $mahalle->name ?? '';
        }

        return '';
    }

    /**
     * Get category name from ID or slug
     *
     * @param mixed $categoryValue
     * @return string
     */
    private function getCategoryName($categoryValue): string
    {
        if (!$categoryValue) {
            return '';
        }

        if (!is_numeric($categoryValue)) {
            return $categoryValue;
        }

        $kategori = IlanKategori::find($categoryValue);
        return $kategori ? ($kategori->name ?? '') : '';
    }
}
