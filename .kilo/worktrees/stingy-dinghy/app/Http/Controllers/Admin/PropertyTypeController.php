<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Admin\Traits\ManagesPropertyTypes;
use App\Http\Controllers\Admin\Traits\UPSHelperTrait;
use App\Services\PropertyType\PropertyTypeService;
use App\Traits\TogglesFeatureDurum;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


/**
 * Property Type CRUD Controller
 *
 * Context7: Ana kategori yönetimi (CRUD + Toggle operations)
 * Refactored from PropertyTypeManagerController (Phase 2.2)
 * Phase 2.3+: UPSHelperTrait integration - Standardized responses, cache, validators
 *
 * Methods:
 * - index(): Ana dashboard - Kategori listesi
 * - show(): Kategori detay sayfası
 * - createYayinTipi(): Yayın tipi oluştur
 * - destroyYayinTipi(): Yayın tipi sil
 * - destroyAltKategori(): Alt kategori sil
 * - toggleYayinTipi(): Yayın tipi aktif/pasif
 */
class PropertyTypeController extends AdminController
{
    use ManagesPropertyTypes, UPSHelperTrait;
    use TogglesFeatureDurum;

    protected PropertyTypeService $service;

    public function __construct(PropertyTypeService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-settings');
    }

    /**
     * Ana sayfa - Kategori listesi ve yönetim
     * YENİ: 3-seviye sistem - sadece ana kategoriler (seviye=0)
     */
    public function index()
    {
        $kategoriler = $this->service->getMainCategories();

        return view('admin.property-type-manager.index', compact('kategoriler'));
    }

    /**
     * Kategori detay - Yayın tipleri ve relations yönetimi
     * YENİ: 3-seviye sistem - Alt kategoriler (seviye=1) ve Yayın Tipleri (seviye=2)
     */
    public function show($kategoriId)
    {
        try {
            $kategori = $this->service->getCategoryById((int) $kategoriId);

            // Ana kategori kontrolü ve redirect
            if (!$this->service->isMainCategory($kategori)) {
                $mainCategoryId = $this->service->getMainCategoryId($kategori);
                if ($mainCategoryId) {
                    return redirect()->route('admin.property_types.show', $mainCategoryId)
                        ->with('info', 'Ana kategori sayfasına yönlendirildiniz.');
                }
            }

            // Service'den veri çek
            $altKategoriler = $this->service->getSubCategories((int) $kategoriId);
            $allYayinTipleri = $this->service->getYayinTipleri((int) $kategoriId);

            // Trait metodları (view için gerekli helper'lar - formatlama ve ilişkiler)
            $altKategoriYayinTipleri = $this->loadAltKategoriYayinTipleri($altKategoriler);
            $fieldDependencies = $this->loadFieldDependencies($kategori);
            $featureCategories = $this->loadFeatureCategories($kategori->slug);
            $yanlisEklenenYayinTipleri = $this->loadYanlisEklenenYayinTipleri($kategoriId);

            return view('admin.property-type-manager.show', [
                'kategori' => $kategori,
                'kategoriId' => (int) $kategoriId,
                'altKategoriler' => $altKategoriler,
                'allYayinTipleri' => $allYayinTipleri,
                'altKategoriYayinTipleri' => $altKategoriYayinTipleri,
                'fieldDependencies' => $fieldDependencies ?: collect(),
                'featureCategories' => $featureCategories,
                'yanlisEklenenYayinTipleri' => $yanlisEklenenYayinTipleri,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('admin.property_types.index')
                ->with('error', 'Kategori bulunamadı. Lütfen geçerli bir kategori seçin.');
        } catch (\Throwable $e) {
            Log::error('PropertyTypeController@show error', [
                'kategoriId' => $kategoriId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.property_types.index')
                ->with('error', 'Sayfa yüklenirken hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Yayın tipi oluştur
     *
     * POST /admin/property-types/{kategoriId}/yayin-tipi
     */
    public function createYayinTipi(Request $request, $kategoriId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $yayinTipi = $this->service->createYayinTipi((int) $kategoriId, $validated['name']);

            return response()->json([
                'success' => true,
                'message' => 'Yayın tipi başarıyla eklendi!',
                'data' => [
                    'id' => $yayinTipi->id,
                    'yayin_tipi_id' => $yayinTipi->id,
                    'name' => $yayinTipi->name,
                    'aktiflik_durumu' => $yayinTipi->aktiflik_durumu,
                    'display_order' => $yayinTipi->display_order,
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori bulunamadı!',
            ], 404);
        }
    }

    /**
     * Yayın tipi sil
     *
     * DELETE /admin/property-types/{kategoriId}/yayin-tipi/{yayinTipiId}?force=1
     */
    public function destroyYayinTipi($kategoriId, $yayinTipiId)
    {
        try {
            // ✅ ZERO-GAP FIX: Support force delete via query parameter
            $force = request()->boolean('force', false);

            $this->service->deleteYayinTipi((int) $yayinTipiId, (int) $kategoriId, $force);

            return $this->sendUPSSuccess('✅ Yayın tipi başarıyla silindi!');
        } catch (\RuntimeException $e) {
            // ✅ ZERO-GAP FIX: Detect field dependency OR subcategory relation error and suggest force delete
            if (str_contains($e->getMessage(), 'alan ilişkisi') || str_contains($e->getMessage(), 'alt kategori ilişkisi')) {
                return $this->sendUPSError(
                    '❌ ' . $e->getMessage() . ' Force delete için ?force=1 parametresini ekleyin.',
                    ['can_force_delete' => true],
                    422
                );
            }

            return $this->sendUPSError('❌ ' . $e->getMessage(), [], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendUPSError('❌ Yayın tipi bulunamadı!', [], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Yayın tipi silinirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alt kategori sil
     *
     * DELETE /admin/property-types/{kategoriId}/alt-kategori/{altKategoriId}
     */
    public function destroyAltKategori($kategoriId, $altKategoriId)
    {
        try {
            $this->service->deleteAltKategori((int) $altKategoriId, (int) $kategoriId);

            return response()->json([
                'success' => true,
                'message' => 'Alt kategori başarıyla silindi!',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Alt kategori bulunamadı!',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Alt kategori silinirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Yayın tipi toggle (aktif/pasif)
     *
     * POST /admin/property-types/{kategoriId}/toggle-yayin-tipi
     */
    public function toggleYayinTipi($kategoriId, Request $request)
    {
        $request->validate([
            'alt_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'aktiflik_durumu' => 'required|boolean',
            'cascade' => 'nullable|boolean',
        ]);

        try {
            $cascade = $request->boolean('cascade');
            $targetKategoriId = (int) $request->alt_kategori_id;
            $yayinTipiId = (int) $request->yayin_tipi_id;
            $aktiflikDurumu = $request->boolean('aktiflik_durumu');

            // ✅ Cascade Logic: If target is a parent and cascade=true, apply to all children
            if ($cascade) {
                $category = \App\Models\IlanKategori::find($targetKategoriId);

                // Check if it's a parent category (seviye=0 or has children)
                if ($category && ($category->seviye === 0 || $category->children()->exists())) {
                    $children = $category->children;

                    if ($children->count() > 0) {
                        $count = $this->service->cascadeToggleAltKategoriYayinTipi(
                            $category,
                            $yayinTipiId,
                            $aktiflikDurumu
                        );

                        return response()->json([
                            'success' => true,
                            'message' => "Yayın tipi {$count} alt kategoriye uygulandı."
                        ]);
                    }
                }
            }

            // Normal single toggle
            $this->service->toggleAltKategoriYayinTipi(
                $targetKategoriId,
                $yayinTipiId,
                $aktiflikDurumu
            );

            return response()->json([
                'success' => true,
                'message' => 'Yayın tipi ilişkisi güncellendi'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İşlem sırasında hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Yayın Tipi Sıralamasını Güncelle
     *
     * @param int $kategoriId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateYayinTipiSequence($kategoriId, Request $request)
    {
        $items = $request->input('items') ?? [];
        if (empty($items)) {
            return response()->json(['success' => true, 'message' => 'Sıralama güncellendi (No items)'], 200);
        }

        try {
            $this->service->updateYayinTipiSequence((int) $kategoriId, $items);
            return response()->json(['success' => true, 'message' => 'Yayın tipi sıralaması güncellendi']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Sıralama hatası: ' . $e->getMessage()], 500);
        }
    }

    public function getYayinTipleri($kategoriId)
    {
        try {
            $yayinTipleri = $this->service->getYayinTipleri((int) $kategoriId);

            return response()->json([
                'success' => true,
                'data' => $yayinTipleri,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Yayın tipleri alınamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

}
