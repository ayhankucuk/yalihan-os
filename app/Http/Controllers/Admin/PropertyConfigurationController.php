<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\PropertyConfigurationContract;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Property Configuration Controller — Sprint 6.8
 *
 * Konut → Alt Kategori → Yayın Tipi kombinasyonu için
 * dinamik alan şeması sağlar.
 *
 * API Endpoints:
 *   GET /admin/property-config                     — Tüm kombinasyonlar
 *   GET /admin/property-config/{category}         — Kategori kombinasyonları
 *   GET /admin/property-config/{cat}/{subCat}/{type} — Spesifik alan şeması
 *
 * @sab-ignore-service
 * SAAB Certified: Sprint 6.8
 */
class PropertyConfigurationController extends Controller
{
    public function __construct(
        private readonly PropertyConfigurationContract $configService,
    ) {}

    /**
     * Tüm mevcut kategori + yayın tipi kombinasyonlarını listeler.
     *
     * GET /admin/property-config
     */
    public function index(): JsonResponse
    {
        $combinations = $this->configService->getAvailableCombinations();

        return response()->json([
            'success' => true,
            'data' => $combinations,
            'meta' => [
                'total_combinations' => count($combinations),
                'total_fields' => array_sum(array_column($combinations, 'field_count')),
            ],
        ]);
    }

    /**
     * Spesifik kategori + alt kategori + yayın tipi kombinasyonunun
     * alan şemasını döndürür.
     *
     * GET /admin/property-config/konut/villa/satilik
     *
     * @throws InvalidArgumentException Geçersiz kombinasyon
     */
    public function show(string $category, string $subCategory, string $listingType): JsonResponse
    {
        $dto = $this->configService->getConfiguration(
            category: $category,
            subCategory: $subCategory,
            listingType: $listingType,
        );

        return response()->json([
            'success' => true,
            'data' => $dto->toArray(),
            'meta' => [
                'field_count' => $dto->metadata['field_count'] ?? 0,
                'required_count' => $dto->metadata['required_count'] ?? 0,
                'optional_count' => $dto->metadata['optional_count'] ?? 0,
                'ai_auto_fill_count' => $dto->metadata['ai_auto_fill_count'] ?? 0,
                'source_table' => $dto->metadata['source_table'] ?? 'unknown',
            ],
        ]);
    }

    /**
     * Sadece field listesini döndürür (kanal konfigürasyonu olmadan).
     * Wizard için optimize edilmiş endpoint.
     *
     * GET /admin/property-config/fields/konut/villa/satilik
     */
    public function fields(string $category, string $subCategory, string $listingType): JsonResponse
    {
        $fields = $this->configService->getFields(
            category: $category,
            subCategory: $subCategory,
            listingType: $listingType,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'kategori' => $category,
                'alt_kategori' => $subCategory,
                'yayin_tipi' => $listingType,
                'fields' => array_map(fn($f) => $f->toArray(), $fields),
            ],
            'meta' => [
                'field_count' => count($fields),
                'required_count' => count(array_filter($fields, fn($f) => $f->isRequired)),
                'optional_count' => count(array_filter($fields, fn($f) => !$f->isRequired)),
            ],
        ]);
    }
}
