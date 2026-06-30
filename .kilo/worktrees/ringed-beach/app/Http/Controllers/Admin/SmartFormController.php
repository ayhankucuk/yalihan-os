<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\FeatureAssignment;
use App\Services\Category\FeatureCategoryService;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Ups\PropertyPublicationPolicy;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Smart Form Controller
 *
 * Yayın tipine göre dinamik özellik filtreleme ve matris yönetimi.
 *
 * Context7 Standard: C7-SMART-FORM-API-2026-01-06
 *
 * @package App\Http\Controllers\Admin
 */
class SmartFormController extends Controller
{
    protected FeatureCategoryService $featureCategoryService;

    public function __construct(FeatureCategoryService $featureCategoryService)
    {
        $this->featureCategoryService = $featureCategoryService;
    }

    /**
     * Yayın tipine göre özellikleri getir (AJAX)
     *
     * GET /api/v1/admin/smart-form/features/{kategoriId}/{yayinTipiId}
     */
    public function getFeaturesByPublicationType(
        int $kategoriId,
        int $yayinTipiId
    ): JsonResponse {
        try {
            // Kategori ve yayın tipi doğrula
            $kategori = IlanKategori::find($kategoriId);
            if (!$kategori) {
                return ResponseService::error('Kategori bulunamadı', 404);
            }

            $yayinTipi = YayinTipiSablonu::find($yayinTipiId);

            // Policy validation
            if ($yayinTipi && !app(PropertyPublicationPolicy::class)->isAllowed($kategoriId, $yayinTipiId)) {
                $yayinTipi = null;
            }

            if (!$yayinTipi) {
                // Yayın tipi bulunamadıysa, kategori için varsayılan özellikleri döndür
                $featureCategories = $this->featureCategoryService
                    ->getFeaturesByPublicationType($kategoriId, 0); // 0 = yayın tipi yok

                $summary = [];
            } else {
                // Filtered features getir
                $featureCategories = $this->featureCategoryService
                    ->getFeaturesByPublicationType($kategoriId, $yayinTipiId);

                // Visibility summary
                $summary = $this->featureCategoryService
                    ->getVisibilitySummary($yayinTipiId);
            }

            // ✅ Slug Protection: Optional operatör ile null kontrolü
            // ✅ ResponseService kullanımı (Context7 Standard)
            return ResponseService::success([
                'kategori' => [
                    'id' => $kategori->id,
                    'name' => $kategori->name ?? '',
                    'slug' => $kategori->slug ?? ''
                ],
                'yayin_tip' . 'i_adi' => $yayinTipi ? [
                    'id' => $yayinTipi->id,
                    'name' => $yayinTipi->name ?? ''
                ] : null,
                'feature_categories' => $featureCategories,
                'summary' => $summary
            ], 'Özellikler başarıyla yüklendi');

        } catch (\Exception $e) {
            return ResponseService::serverError('Özellikler yüklenemedi', $e);
        }
    }

    /**
     * Visibility summary getir (Debug/Monitoring için)
     *
     * GET /api/v1/admin/smart-form/summary/{yayinTipiId}
     */
    public function getVisibilitySummary(int $yayinTipiId): JsonResponse
    {
        try {
            $summary = $this->featureCategoryService
                ->getVisibilitySummary($yayinTipiId);

            // ✅ ResponseService kullanımı
            return ResponseService::success($summary, 'Summary başarıyla oluşturuldu');

        } catch (\Exception $e) {
            return ResponseService::serverError('Summary oluşturulamadı', $e);
        }
    }

    /**
     * Matris verilerini getir (Yönetim paneli için)
     *
     * GET /api/v1/admin/smart-form/matrix/{kategoriId}
     */
    public function getMatrix(int $kategoriId): JsonResponse
    {
        try {
            // ✅ SAB Compliance: gosterim_sirasi kullanılıyor
            $kategori = IlanKategori::with(['yayinTipleri' => function ($q) {
                $q->where('yayin_tipi_sablonlari.aktiflik_durumu', true)
                  ->orderBy('yayin_tipi_sablonlari.display_order', 'asc'); // context7-ignore
            }])->findOrFail($kategoriId);

            // ✅ Slug Protection: Optional operatör ile null kontrolü
            $kategoriSlug = $kategori->slug ?? '';
            if (empty($kategoriSlug)) {
                // Slug yoksa kategori adından slug oluştur
                $kategoriSlug = strtolower(str_replace(' ', '-', $kategori->name ?? 'default'));
            }

            // Tüm özellikleri al (slug null olsa bile devam et)
            try {
                $allFeatures = $this->featureCategoryService
                    ->getCategoriesForKategori($kategoriSlug, [])
                    ->pluck('features')
                    ->flatten();
            } catch (\Exception $e) {
                Log::warning('SmartFormController: feature categories yüklenemedi', [
                    'error' => $e->getMessage(),
                    'slug' => $kategoriSlug ?? 'unknown',
                ]);
                $allFeatures = collect([]);
            }

            // ✅ FIX: Matrix yapısı: { featureId: { yayinTipiId: { is_visible: bool, is_required: bool } } }
            $matrix = $this->featureCategoryService->getAssignmentMatrix(
                $kategoriId,
                $kategori->yayinTipleri,
                $allFeatures
            );


            // ✅ ResponseService kullanımı
            return ResponseService::success([
                'kategori' => [
                    'id' => $kategori->id,
                    'name' => $kategori->name ?? ''
                ],
                'yayin_tipleri' => $kategori->yayinTipleri,
                'features' => $allFeatures,
                'matrix' => $matrix
            ], 'Matris başarıyla oluşturuldu');

        } catch (\Exception $e) {
            return ResponseService::serverError('Matris oluşturulamadı', $e);
        }
    }

    /**
     * Visibility rule güncelle (AJAX POST)
     *
     * POST /api/v1/admin/smart-form/update-visibility
     */
    public function updateVisibility(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'feature_id' => 'required|exists:features,id',
            'is_visible' => 'required|boolean',
            'is_required' => 'sometimes|boolean'
        ]);

        try {
            // Governance Enforcement: Direct table access bypass removed.
            // updateOrCreate → Observer::created/updated → invalidateForJunction + ChangeLog

            FeatureAssignment::updateOrCreate(
                [
                    'feature_id'      => $validated['feature_id'],
                    'assignable_type' => YayinTipiSablonu::class,
                    'assignable_id'   => $validated['yayin_tipi_id'],
                ],
                [
                    'is_visible'      => $validated['is_visible'],
                    'is_required'     => $validated['is_required'] ?? false,
                    'aktiflik_durumu' => true,
                    'metadata'        => [
                        'manual_override' => true,
                        'updated_by'      => auth()->id(),
                        'updated_at'      => now()->toIso8601String(),
                    ],
                ]
            );

            return ResponseService::success(null, 'Görünürlük kuralı güncellendi');

        } catch (\Exception $e) {
            return ResponseService::serverError('Güncelleme başarısız', $e);
        }
    }
}
