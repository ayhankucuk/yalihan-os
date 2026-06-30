<?php

namespace App\Http\Controllers\Admin\Traits;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * UPS Helper Trait
 *
 * Context7 Standardı: C7-UPS-HELPER-TRAIT-2025-12-23
 * Phase: 2.3 - Service Layer Refactoring
 *
 * Universal Property System (UPS) için ortak helper metodları.
 * Cache management, response formatting, data transformation, validation.
 *
 * Kullanım:
 * ```php
 * use App\Http\Controllers\Admin\Traits\UPSHelperTrait;
 *
 * class FieldDependencyController extends AdminController
 * {
 *     use UPSHelperTrait;
 *
 *     public function store(Request $request) {
 *         $result = $this->service->upsertFieldDependency($data);
 *         return $this->sendUPSResponse($result);
 *     }
 * }
 * ```
 *
 * @package App\Http\Controllers\Admin\Traits
 */
trait UPSHelperTrait
{
    // ========================================
    // 🔄 CACHE MANAGEMENT
    // ========================================

    /**
     * Redis cache prefix for UPS field dependencies
     */
    private const UPS_CACHE_PREFIX = 'field_deps';

    /**
     * Cache TTL (1 hour)
     */
    private const UPS_CACHE_TTL = 3600;

    /**
     * 🧹 Clear UPS cache for specific category
     *
     * Atomik cache temizleme - Sadece ilgili kategori cache'ini invalidate eder
     *
     * @param string $categorySlug Kategori slug (örn: "konut", "arsa")
     * @param bool $recursive Alt kategorileri de temizle
     * @return void
     */
    protected function clearUPSCache(string $categorySlug, bool $recursive = false): void
    {
        try {
            // Primary cache key
            $primaryKey = self::UPS_CACHE_PREFIX . ':' . $categorySlug;
            Cache::forget($primaryKey);

            // Variant cache keys (yayin_tipi bazlı)
            $variantKeys = [
                $primaryKey . ':satilik',
                $primaryKey . ':kiralik',
                $primaryKey . ':gunluk',
            ];

            foreach ($variantKeys as $key) {
                Cache::forget($key);
            }

            // Recursive sub-category cache clear (opsiyonel)
            if ($recursive) {
                $pattern = self::UPS_CACHE_PREFIX . ':' . $categorySlug . ':*';
                // Context7: Driver-bağımsız wildcard pattern ile tüm alt cache'leri temizle
                $cacheService = app(\App\Services\Cache\CacheService::class);
                $cacheService->flushByPrefix(self::UPS_CACHE_PREFIX . ':' . $categorySlug);
            }

            Log::debug('✅ UPS Cache cleared', [
                'category_slug' => $categorySlug,
                'recursive' => $recursive,
                'keys_cleared' => count($variantKeys) + 1,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ UPS Cache clear failed', [
                'category_slug' => $categorySlug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🔍 Get UPS cache key for category
     *
     * @param string $categorySlug
     * @param string|null $yayinTipi
     * @return string
     */
    protected function getUPSCacheKey(string $categorySlug, $yayinTipi = null): string
    {
        $key = self::UPS_CACHE_PREFIX . ':' . $categorySlug;

        if ($yayinTipi) {
            $key .= ':' . $yayinTipi;
        }

        return $key;
    }

    // ========================================
    // 📤 RESPONSE FORMATTERS
    // ========================================

    /**
     * ✅ Standardized success response
     *
     * Tüm UPS controller'lar için tek tip başarı response'u
     *
     * @param string $message Başarı mesajı
     * @param array $data Opsiyonel veri (frontend için)
     * @param int $code HTTP durum kodu (default: 200)
     * @return JsonResponse
     */
    protected function sendUPSSuccess(string $message, array $data = [], int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }

    /**
     * ❌ Standardized error response
     *
     * Tüm UPS controller'lar için tek tip hata response'u
     *
     * @param string $message Hata mesajı
     * @param array $errors Detaylı hatalar (validation vb.)
     * @param int $code HTTP durum kodu (default: 400)
     * @return JsonResponse
     */
    protected function sendUPSError(string $message, array $errors = [], int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * 🔄 Unified response handler for service results
     *
     * Service layer'dan gelen result'u otomatik olarak format'layıp response'a çevirir
     *
     * Örnek kullanım:
     * ```php
     * $result = $this->service->upsertFieldDependency($data);
     * return $this->sendUPSResponse($result);
     * ```
     *
     * @param array $result Service method result
     * @param int $successCode HTTP code for success (default: 200)
     * @param int $errorCode HTTP code for error (default: 400)
     * @return JsonResponse
     */
    protected function sendUPSResponse(array $result, int $successCode = 200, int $errorCode = 400): JsonResponse
    {
        if ($result['success'] ?? false) {
            return $this->sendUPSSuccess(
                $result['message'] ?? 'İşlem başarılı',
                $result['data'] ?? [],
                $successCode
            );
        }

        return $this->sendUPSError(
            $result['message'] ?? 'İşlem başarısız',
            $result['errors'] ?? [],
            $errorCode
        );
    }

    // ========================================
    // 🔄 DATA TRANSFORMERS
    // ========================================

    /**
     * 🏗️ Format field dependency for frontend
     *
     * Veritabanından gelen ham field dependency verisini frontend'in beklediği formata çevirir
     *
     * @param object|\Illuminate\Database\Eloquent\Model $field Field dependency model instance
     * @return array Frontend-friendly format
     */
    protected function formatFieldDependency($field): array
    {
        return [
            'id' => $field->id,
            'field_slug' => $field->field_slug,
            'field_name' => $field->field_name,
            'field_type' => $field->field_type,
            'field_category' => $field->field_category ?? 'genel',
            'field_icon' => $field->field_icon ?? '📋',
            'field_unit' => $field->field_unit,
            'yayin_tipi_id' => $field->yayin_tipi_id,
            'yayin_tipi' => $field->yayin_tipi,
            'aktiflik_durumu' => (bool) $field->aktiflik_durumu,
            'required' => (bool) $field->required,
            'display_order' => (int) $field->display_order,
            'options' => $field->field_options ? (is_array($field->field_options) ? $field->field_options : json_decode($field->field_options, true)) : [],
            'ai_features' => [
                'auto_fill' => (bool) ($field->ai_auto_fill ?? false),
                'suggestion' => (bool) ($field->ai_suggestion ?? false),
            ],
            'display_options' => [
                'searchable' => (bool) ($field->searchable ?? false),
                'show_in_card' => (bool) ($field->show_in_card ?? false),
            ],
            'metadata' => [
                'created_at' => $field->created_at?->toIso8601String(),
                'updated_at' => $field->updated_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * 📊 Format bulk operation result
     *
     * Toplu işlem sonuçlarını frontend'e uygun formata çevirir
     *
     * @param array $result Bulk operation result from service
     * @return array Frontend-friendly bulk result
     */
    protected function formatBulkResult(array $result): array
    {
        return [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? '',
            'statistics' => [
                'total' => $result['total'] ?? 0,
                'updated' => $result['updated_count'] ?? 0,
                'failed' => $result['failed_count'] ?? 0,
                'skipped' => $result['skipped_count'] ?? 0,
            ],
            'errors' => $result['errors'] ?? [],
            'duration_ms' => $result['duration_ms'] ?? null,
        ];
    }

    /**
     * 🔄 Format circular dependency error
     *
     * Circular dependency hatalarını kullanıcı dostu formata çevirir
     *
     * @param array $circularResult detectCircularDependency() sonucu
     * @return array Frontend-friendly error format
     */
    protected function formatCircularDependencyError(array $circularResult): array
    {
        return [
            'error_type' => 'circular_dependency',
            'cycle_detected' => $circularResult['cycle_detected'] ?? false,
            'message' => $circularResult['message'] ?? 'Döngüsel bağımlılık tespit edildi',
            'cycle_chain' => $circularResult['chain'] ?? [],
            'visualization' => implode(' → ', $circularResult['chain'] ?? []),
            'suggestion' => 'Lütfen farklı bir alan seçin veya mevcut bağımlılıkları kontrol edin.',
        ];
    }

    // ========================================
    // ✅ VALIDATION HELPERS
    // ========================================

    /**
     * 🔐 Validate UPS field data
     *
     * Field dependency verilerinin temel validation'ı
     *
     * @param array $data Field data
     * @return array ['valid' => bool, 'errors' => array]
     */
    protected function validateUPSFieldData(array $data): array
    {
        $errors = [];

        // Required fields
        $requiredFields = ['kategori_slug', 'field_slug', 'field_name', 'field_type'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "Alan zorunludur: {$field}";
            }
        }

        // Field type validation
        $allowedTypes = ['text', 'number', 'boolean', 'select', 'textarea', 'date', 'price'];
        if (isset($data['field_type']) && !in_array($data['field_type'], $allowedTypes, true)) {
            $errors['field_type'] = 'Geçersiz field_type. İzin verilenler: ' . implode(', ', $allowedTypes);
        }

        // Field slug format (lowercase, hyphen, underscore only)
        if (isset($data['field_slug']) && !preg_match('/^[a-z0-9_-]+$/', $data['field_slug'])) {
            $errors['field_slug'] = 'field_slug sadece küçük harf, rakam, tire ve alt çizgi içerebilir';
        }

        // Sıralama (display_order) pozitif olmalı
        if (isset($data['display_order']) && $data['display_order'] < 0) {
            $errors['display_order'] = 'display_order negatif olamaz';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 🔍 Check if category slug is valid
     *
     * @param string $categorySlug
     * @return bool
     */
    protected function isValidUPSCategory(string $categorySlug): bool
    {
        $allowedCategories = [
            'konut',
            'arsa',
            'isyeri',
            'turizm',
            'bina',
            'proje',
        ];

        return in_array($categorySlug, $allowedCategories, true);
    }

    /**
     * 📋 Get allowed feature categories for kategori
     *
     * Hangi kategoride hangi feature category'lerin kullanılabileceğini döner
     * ManagesPropertyTypes trait'ten yeniden kullanılabilir versiyon
     *
     * @param string $kategoriSlug
     * @return array Allowed feature category names
     */
    protected function getAllowedFeatureCategories(string $kategoriSlug): array
    {
        $mapping = [
            'konut' => ['genel', 'konum', 'ozellikler', 'diger'],
            'arsa' => ['genel', 'konum', 'imar', 'diger'],
            'isyeri' => ['genel', 'konum', 'ozellikler', 'diger'],
            'turizm' => ['genel', 'konum', 'ozellikler', 'tesis', 'diger'],
            'bina' => ['genel', 'konum', 'ozellikler', 'diger'],
            'proje' => ['genel', 'konum', 'proje_detay', 'diger'],
        ];

        return $mapping[$kategoriSlug] ?? ['genel', 'diger'];
    }

    // ========================================
    // 🛠️ UTILITY METHODS
    // ========================================

    /**
     * 📊 Get UPS statistics summary
     *
     * Kategori bazlı istatistik özeti
     *
     * @param string $categorySlug
     * @return array Statistics
     */
    protected function getUPSStatistics(string $categorySlug): array
    {
        try {
            $cacheKey = $this->getUPSCacheKey($categorySlug) . ':stats';

            return Cache::remember($cacheKey, self::UPS_CACHE_TTL, function () use ($categorySlug) {
                return [
                    'total_fields' => \App\Models\KategoriYayinTipiFieldDependency::where('kategori_slug', $categorySlug)->count(),
                    'active_fields' => \App\Models\KategoriYayinTipiFieldDependency::where('kategori_slug', $categorySlug)->where('aktiflik_durumu', true)->count(), // context7-ignore
                    'required_fields' => \App\Models\KategoriYayinTipiFieldDependency::where('kategori_slug', $categorySlug)->where('required', true)->count(),
                    'ai_status_fields' => \App\Models\KategoriYayinTipiFieldDependency::where('kategori_slug', $categorySlug)->where('ai_auto_fill', true)->count(),
                    'last_update' => \App\Models\KategoriYayinTipiFieldDependency::where('kategori_slug', $categorySlug)->max('updated_at'),
                ];
            });
        } catch (\Exception $e) {
            Log::error('❌ UPS Statistics failed', [
                'category_slug' => $categorySlug,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * 🧪 Validate and sanitize field slug
     *
     * @param string $fieldSlug
     * @return string Sanitized slug
     */
    protected function sanitizeFieldSlug(string $fieldSlug): string
    {
        // Convert to lowercase
        $slug = strtolower($fieldSlug);

        // Remove spaces, replace with underscore
        $slug = str_replace(' ', '_', $slug);

        // Remove invalid characters (only allow a-z, 0-9, _, -)
        $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);

        // Remove consecutive underscores/hyphens
        $slug = preg_replace('/[_-]+/', '_', $slug);

        // Trim underscores/hyphens from start and end
        $slug = trim($slug, '_-');

        return $slug;
    }
}
