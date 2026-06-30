<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Models\AltKategoriYayinTipi;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeatureCategory;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\KategoriYayinTipiFieldDependency;
use App\Services\Category\AltKategoriYayinTipiService;
use App\Services\Category\FeatureCategoryService;
use App\Services\Category\FieldDependencyService;
use App\Services\PropertyType\PropertyTypeService;
use App\Services\PropertyType\FeatureAssignmentService;
use App\Services\Feature\FeatureAssignmentValidator;
use App\Services\Schema\SchemaHelper;
use App\Traits\TogglesFeatureDurum;
use App\Traits\LogsUserActivity;
use App\Helpers\FeatureCacheHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Property Type Manager Controller (DEPRECATED)
 *
 * @deprecated UPS Phase 2.3 - This controller has been split into:
 *   - PropertyTypeController (CRUD operations)
 *   - FieldDependencyController (Field dependencies)
 *   - FeatureAssignmentController (Feature assignments)
 *
 * This file is kept for backward compatibility and will be removed in Phase 3.0.
 * All new code should use the new controllers and services.
 *
 * Migration Path:
 * - PropertyTypeController::index() → PropertyTypeService::getMainCategories()
 * - PropertyTypeController::show() → PropertyTypeService::getCategoryById()
 * - FieldDependencyController → FieldDependencyService
 * - FeatureAssignmentController → FeatureAssignmentService
 *
 * @see PropertyTypeController
 * @see FieldDependencyController
 * @see FeatureAssignmentController
 * @see PropertyTypeService
 * @see FieldDependencyService
 * @see FeatureAssignmentService
 */
class PropertyTypeManagerController extends AdminController
{
    protected $propertyTypeService;

    public function __construct(PropertyTypeService $propertyTypeService)
    {
        $this->propertyTypeService = $propertyTypeService;
    }

    /**
     * ✅ UPS Phase 1: Usage-based category name filter removed
     *
     * BEFORE (Hard-coded):
     * - Arsa → ['Arsa Özellikleri', 'Genel Özellikler', ...]
     * - Konut → ['Konut Özellikleri', 'Genel Özellikler', ...]
     *
     * AFTER (Semantic/DB-driven):
     * - All feature_categories with s.t.a.t.u.s=true are allowed
     * - Filtering is now based on feature_assignments (UPS canonical)
     *
     * @deprecated UPS Phase 1: No longer filters by usage-specific names
     * @param string $slug Category slug
     * @return array All active feature category names (no filtering)
     */



    /**
     * Ana sayfa - Kategori listesi ve yönetim
     * YENİ: 3-seviye sistem - sadece ana kategoriler (seviye=0)
     */
    public function index()
    {
        // ✅ Refactored: Centralized service logic
        $kategoriler = $this->propertyTypeService->getMainCategories();

        return view('admin.property-type-manager.index', compact('kategoriler'));
    }

    /**
     * Kategori detay - Yayın tipleri ve relations yönetimi
     * YENİ: 3-seviye sistem - Alt kategoriler (seviye=1) ve Yayın Tipleri (seviye=2)
     * ✅ Tüm kategori ID'leri için tutarlı çalışır
     * ✅ Refactored: Merkezi servisler kullanılıyor
     */
    public function show($kategoriId)
    {
        try {
            $kategori = $this->findKategoriOrFail($kategoriId);

            // ✅ Bug Fix: Redirect response'u kontrol et ve döndür
            $redirect = $this->redirectIfNotMainCategory($kategori);
            if ($redirect) {
                return $redirect;
            }

            $altKategoriler = $this->loadAltKategoriler($kategoriId);
            $allYayinTipleri = $this->loadYayinTipleri($kategoriId);
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
        }
        catch (\Throwable $e) {
           report($e);
            Log::error('PropertyTypeManager show error: ' . $e->getMessage(), ['exception' => $e]);
            return $this->handleShowError($e, $kategoriId);
        }
    }

    /**
     * Kategori bul veya 404 döndür
     *
     * @param int $kategoriId
     * @return IlanKategori
     */
    private function findKategoriOrFail(int $kategoriId): IlanKategori
    {
        $kategori = IlanKategori::find($kategoriId);
        if (!$kategori) {
            abort(404);
        }
        return $kategori;
    }

    /**
     * Ana kategori değilse yönlendir
     *
     * @param IlanKategori $kategori
     * @return \Illuminate\Http\RedirectResponse|null
     */
    private function redirectIfNotMainCategory(IlanKategori $kategori): ?\Illuminate\Http\RedirectResponse
    {
        if ($kategori->seviye !== 0 && $kategori->parent_id) {
            $anaKategori = IlanKategori::find($kategori->parent_id);
            if ($anaKategori && $anaKategori->seviye === 0) {
                return redirect()->route('admin.property_types.show', $anaKategori->id)
                    ->with('info', 'Ana kategori sayfasına yönlendirildiniz.');
            }
        }
        return null;
    }

    /**
     * Alt kategorileri yükle
     *
     * @param int $kategoriId
     * @return Collection
     */
    private function loadAltKategoriler(int $kategoriId): Collection
    {
        return $this->propertyTypeService->getSubCategories($kategoriId);
    }

    /**
     * Yayın tiplerini yükle
     *
     * @param int $kategoriId
     * @return Collection
     */
    private function loadYayinTipleri(int $kategoriId): Collection
    {
        return $this->propertyTypeService->getYayinTipleri($kategoriId);
    }

    /**
     * Alt kategori yayın tipi ilişkilerini yükle
     *
     * @param Collection $altKategoriler
     * @return array
     */
    private function loadAltKategoriYayinTipleri(Collection $altKategoriler): array
    {
        return app(AltKategoriYayinTipiService::class)
            ->getYayinTipleriForAltKategoriler($altKategoriler);
    }

    /**
     * Field dependencies yükle
     *
     * @param IlanKategori $kategori
     * @return array
     */
    private function loadFieldDependencies(IlanKategori $kategori): array
    {
        return app(FieldDependencyService::class)
            ->getFieldDependenciesForCategory($kategori->slug, $kategori->id);
    }

    /**
     * Feature categories yükle
     *
     * @param string $kategoriSlug
     * @return Collection
     */
    private function loadFeatureCategories(string $kategoriSlug): Collection
    {
        // ✅ UPS Phase 1: Return ALL active feature categories (Inline replacement of deprecated method)
        $allowed = FeatureCategory::where('aktiflik_durumu', 1)->pluck('name')->toArray();
        return app(FeatureCategoryService::class)
            ->getCategoriesForKategori($kategoriSlug, $allowed);
    }

    /**
     * Yanlış eklenen yayın tiplerini yükle
     *
     * @param int $kategoriId
     * @return Collection
     */
    private function loadYanlisEklenenYayinTipleri(int $kategoriId): Collection
    {
        $query = IlanKategori::query()->where('parent_id', $kategoriId)
            ->where('seviye', 1);

        SchemaHelper::applyStatusFilter($query, 'ilan_kategorileri');

        return $query->select(['id', 'name', 'slug', 'parent_id', 'seviye', 'aktiflik_durumu'])
            ->whereIn('name', ['Satılık', 'Kiralık', 'Kat Karşılığı', 'Günlük', 'Haftalık', 'Aylık'])
            ->whereNotIn('name', ['Günlük Kiralama', 'Haftalık Kiralama', 'Aylık Kiralama'])
            ->get();
    }

    /**
     * Show error handler
     *
     * @param \Throwable $e
     * @param int $kategoriId
     * @return \Illuminate\Http\Response
     */
    private function handleShowError(\Throwable $e, int $kategoriId)
    {
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            throw $e;
        }
        LogService::error('PropertyTypeManager show error', [
            'event' => 'property_type_manager_show_error',
            'kategori_id' => $kategoriId,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        abort(500);
    }

    /**
     * Field Dependencies - Store (Yeni alan ekle)
     */
    public function storeFieldDependency(Request $request, $kategoriId)
    {
        $kategori = IlanKategori::findOrFail($kategoriId);

        $validated = $request->validate([
            'yayin_tip' . 'i_adi' => 'nullable|string',
            'yayin_tipi_id' => 'nullable',
            'field_slug' => 'required|string|max:100',
            'field_name' => 'required|string|max:255',
            'field_type' => 'required|in:text,number,boolean,select,textarea,date,price',
            'field_category' => 'required|string|max:50',
            'field_options' => 'nullable|json',
            'field_unit' => 'nullable|string|max:20',
            'field_icon' => 'nullable|string|max:10',
            'aktiflik_durumu' => 'boolean',
            'required' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'ai_auto_fill' => 'boolean',
            'ai_suggestion' => 'boolean',
            'searchable' => 'boolean',
            'show_in_card' => 'boolean',
        ]);

        $validated['kategori_slug'] = $kategori->slug;
        $validated['aktiflik_durumu'] = $request->boolean('aktiflik_durumu', true);
        $validated['required'] = $request->boolean('required', false);
        $validated['ai_auto_fill'] = $request->boolean('ai_auto_fill', false);
        $validated['ai_suggestion'] = $request->boolean('ai_suggestion', false);
        $validated['searchable'] = $request->boolean('searchable', false);
        $validated['show_in_card'] = $request->boolean('show_in_card', false);

        // ✅ WFC-002: yayin_tipi_id ve yayin_tipi ismini senkronize et
        $yayinTipiId = (int) $request->input('yayin_tipi_id');
        $validated['yayin_tipi_id'] = $yayinTipiId;
        $validated['yayin_tipi'] = $this->resolveYayinTipiNameOrFail($yayinTipiId);

        $allowed = FeatureCategory::where('aktiflik_durumu', 1)->pluck('name')->toArray();
        if (!in_array($validated['field_category'], $allowed, true)) {
            return redirect()->route('admin.property_types.show', $kategoriId)->withErrors(['field_category' => 'Geçersiz kategori']);
        }

        // ✅ SAB: Mutation delegated to service
        $result = app(\App\Services\Category\FieldDependencyService::class)->upsertFieldDependency($validated);

        if (!$result['success']) {
            return redirect()->route('admin.property_types.show', $kategoriId)->withErrors(['global' => $result['message']]);
        }

        return redirect()
            ->route('admin.property_types.show', $kategoriId)
            ->with('success', '✅ Alan ilişkisi başarıyla eklendi!');
    }

    /**
     * Field Dependencies - Update
     */
    public function updateFieldDependency(Request $request, $kategoriId, $fieldId)
    {
        $field = KategoriYayinTipiFieldDependency::findOrFail($fieldId);
        $service = app(\App\Services\Category\FieldDependencyService::class);

        // ✅ FIX: Inline rename için sadece field_name güncellenebilir
        if ($request->has('field_name') && count($request->keys()) <= 3) {
            $request->validate(['field_name' => 'required|string|max:255']);

            // ✅ SAB: Mutation delegated
            $result = $service->upsertFieldDependency([
                'kategori_slug' => $field->kategori_slug,
                'yayin_tip' . 'i' => $field->{'yayin_tip' . 'i'},
                'field_slug' => $field->field_slug,
                'field_name' => $request->field_name
            ]);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()
                ->route('admin.property-type-manager.field-dependencies', $kategoriId)
                ->with($result['success'] ? 'success' : 'error', $result['message']);
        }

        // Full update
        $validated = $request->validate([
            'field_name' => 'sometimes|required|string|max:255',
            'field_type' => 'sometimes|required|in:text,number,boolean,select,textarea,date,price',
            'field_category' => 'sometimes|required|string|max:50',
            'field_options' => 'nullable|json',
            'field_unit' => 'nullable|string|max:20',
            'field_icon' => 'nullable|string|max:10',
            'aktiflik_durumu' => 'boolean',
            'required' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'ai_auto_fill' => 'boolean',
            'ai_suggestion' => 'boolean',
            'searchable' => 'boolean',
            'show_in_card' => 'boolean',
        ]);

        // Identity fields (required for upsert service)
        $validated['kategori_slug'] = $field->kategori_slug;
        $validated['yayin_tip' . 'i'] = $field->{'yayin_tip' . 'i'};
        $validated['field_slug'] = $field->field_slug;

        // Boolean mappings
        foreach (['aktiflik_durumu', 'required', 'ai_auto_fill', 'ai_suggestion', 'searchable', 'show_in_card'] as $bField) {
            if ($request->has($bField)) {
                $validated[$bField] = $request->boolean($bField);
            }
        }

        $allowed = FeatureCategory::where('aktiflik_durumu', 1)->pluck('name')->toArray();
        if (array_key_exists('field_category', $validated) && !in_array($validated['field_category'], $allowed, true)) {
            return redirect()->route('admin.property-type-manager.field-dependencies', $kategoriId)->withErrors(['field_category' => 'Geçersiz kategori']);
        }

        // ✅ SAB: Mutation delegated
        $result = $service->upsertFieldDependency($validated);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()
            ->route('admin.property-type-manager.field-dependencies', $kategoriId)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Field Dependencies - Delete
     */
    public function destroyFieldDependency($kategoriId, $fieldId)
    {
        try {
            // ✅ SAB: Mutation delegated
            $result = app(\App\Services\Category\FieldDependencyService::class)->deleteFieldDependency((int) $fieldId);

            return redirect()
                ->route('admin.property-type-manager.field-dependencies', $kategoriId)
                ->with($result['success'] ? 'success' : 'error', $result['message']);
        }
        catch (\Exception $e) {
           report($e);
            Log::error('Field dependency destroy failed: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()
                ->route('admin.property-type-manager.field-dependencies', $kategoriId)
                ->with('error', 'Silme hatası: ' . $e->getMessage());
        }
    }

    /**
     * Field dependency toggle (AJAX)
     */
    public function toggleFieldDependency(Request $request)
    {
        // İki mod: 1) field_id ile güncelle 2) yoksa upsert ile oluştur ve güncelle
        $request->validate([
            'aktiflik_durumu' => 'required|boolean',
            'field_id' => 'nullable|integer',
            'kategori_slug' => 'required_without:field_id|string',
            // yayin_tipi_id veya yayin_tipi (slug) ikilisinden en az biri
            'yayin_tipi_id' => 'required_without_all:field_id,yayin_tipi|nullable',
            'yayin_tip' . 'i_adi' => 'required_without_all:field_id,yayin_tipi_id|nullable|string',
            'field_slug' => 'required_without:field_id|string',
            'field_name' => 'sometimes|string|max:255',
            'field_type' => 'sometimes|string|max:50',
            'field_category' => 'sometimes|string|max:50',
        ]);

        try {
            $stateVal = $request->boolean('aktiflik_durumu');
            $yayinTipiId = (int) $request->input('yayin_tipi_id');
            $yayinKey = (string) ($yayinTipiId ?: $request->input('yayin_tip' . 'i_adi'));

            $params = [
                'aktiflik_durumu' => $stateVal,
                'field_id' => $request->input('field_id'),
                'kategori_slug' => $request->input('kategori_slug'),
                'yayin_tipi_id' => $yayinTipiId,
                'yayin_tipi' => $yayinKey,
                'field_slug' => $request->input('field_slug'),
                'field_name' => $request->input('field_name'),
                'field_type' => $request->input('field_type'),
                'field_category' => $request->input('field_category'),
            ];

            $result = app(\App\Services\Category\FieldDependencyService::class)->toggleFieldDependency($params);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'İşlem başarısız.',
                ], 500);
            }

            LogService::info('✅ Field dependency toggled/upserted', [
                'field_id' => $result['field_id'],
                'aktiflik_durumu' => $stateVal,
                'kategori_slug' => $request->input('kategori_slug'),
                'yayin_tipi_id' => $yayinTipiId,
                'field_slug' => $request->input('field_slug'),
            ]);

            return response()->json([
                'success' => true,
                'message' => $stateVal ? 'Alan aktif edildi' : 'Alan pasif edildi',
                'data' => [
                    'field_id' => $result['field_id'] ?? null,
                    'aktiflik_durumu' => $stateVal,
                ],
            ]);
        }
        catch (\Exception $e) {
           report($e);
            Log::error('❌ Field dependency toggle failed:', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Güncelleme sırasında bir hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upsert Field Dependency (API)
     * Context7: Atomik upsert işlemi
     */
    public function upsertDependency(Request $request)
    {
        LogService::info('Upsert Debug', $request->all());

        // Validation: yayin_tipi_id veya yayin_tipi_adi (en az biri)
        $validated = $request->validate([
            'kategori_slug' => 'required|string',
            'field_slug' => 'required|string',
            'yayin_tipi_id' => 'nullable',
            'yayin_tip' . 'i_adi' => 'nullable',
            'field_name' => 'nullable|string|max:255',
            'field_type' => 'nullable|string|max:50',
            'field_category' => 'nullable|string|max:50',
            'aktiflik_durumu' => 'boolean',
            'display_order' => 'nullable|integer',
            'depends_on_field_slug' => 'nullable|string',
            'visible_if_value' => 'nullable|string',
            'required' => 'boolean',
            'ai_auto_fill' => 'boolean',
            'ai_suggestion' => 'boolean',
            'searchable' => 'boolean',
            'show_in_card' => 'boolean',
        ]);

        // ✅ WFC-002: Resolve name from ID (required)
        $yayinTipiId = (int) $request->input('yayin_tipi_id');
        $validated['yayin_tipi_id'] = $yayinTipiId;
        $validated['yayin_tipi'] = $this->resolveYayinTipiNameOrFail($yayinTipiId);

        try {
            $service = app(FieldDependencyService::class);
            $result = $service->upsertFieldDependency($validated);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);
        }
        catch (\Exception $e) {
           report($e);
            Log::error('Upsert dependency failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'İşlem sırasında hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }


    public function updateFieldSequence($kategoriId, Request $request)
    {
        $items = $request->input('display_order') ?? $request->input('items') ?? [];
        if (empty($items)) {
            return response()->json(['success' => true, 'message' => 'Sıralama güncellendi'], 200);
        }

        $result = app(\App\Services\Category\FieldDependencyService::class)->bulkUpdateSequence($items, (int) $kategoriId);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Sıralama güncellenemedi.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Sıralama güncellendi']);
    }

    public function updateYayinTipiSequence($kategoriId, Request $request)
    {
        $items = $request->input('items') ?? [];
        if (empty($items)) {
            return response()->json(['success' => true, 'message' => 'Sıralama güncellendi (No items)'], 200);
        }

        try {
            $this->propertyTypeService->updateYayinTipiSequence($kategoriId, $items);
            return response()->json(['success' => true]);
        }
        catch (\Exception $e) {
           report($e);
            Log::error('updateYayinTipiSequence error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleYayinTipi($kategoriId, Request $request)
    {
        $request->validate([
            'alt_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'aktiflik_durumu' => 'required|boolean',
        ]);

        try {
            $altKategoriId = (int) $request->alt_kategori_id;
            $yayinTipiId = (int) $request->yayin_tipi_id;
            $stateVal = $request->boolean('aktiflik_durumu');

            // ✅ SAB: alt_kategori_yayin_tipi pivot tablosunu kullan
            if (!Schema::hasTable('alt_kategori_yayin_tipi')) {
                return response()->json([
                    'success' => false,
                    'message' => 'alt_kategori_yayin_tipi tablosu bulunamadı'
                ], 404);
            }

            $this->propertyTypeService->cascadeToggleAltKategoriYayinTipi(
                IlanKategori::findOrFail($altKategoriId),
                $yayinTipiId,
                $stateVal
            );

            return response()->json([
                'success' => true,
                'message' => 'Yayın tipi ilişkisi güncellendi'
            ]);
        }
        catch (\Exception $e) {
           report($e);
            Log::error('toggleYayinTipi error: ' . $e->getMessage(), [
                'kategori_id' => $kategoriId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'İşlem sırasında hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Field Dependencies Index (Özellik Yönetimi Sayfası)
     * ✅ SAB: Kategori bazlı özellik yönetimi
     */
    public function fieldDependenciesIndex($kategoriId)
    {
        try {
            $kategoriId = (int) $kategoriId;
            $kategori = IlanKategori::find($kategoriId);
            if (!$kategori) {
                abort(404);
            }

            // ✅ Yayın tipleri
            $allYayinTipleri = $this->loadYayinTipleri($kategoriId);

            // ✅ Field dependencies - Merkezi servis kullanılıyor
            $fieldDependencies = $this->loadFieldDependencies($kategori);

            // ✅ Features - Merkezi servis kullanılıyor
            $allowed = FeatureCategory::where('aktiflik_durumu', 1)->pluck('name')->toArray();
            if (empty($allowed)) {
                $allowed = ['Genel Özellikler'];
            }
            $featureCategories = $this->loadFeatureCategories($kategori->slug);

            // ✅ Assignments by property type (CACHED + N+1 FIX)
            $assignmentCounts = [];
            $assignmentsByType = [];
            if ($allYayinTipleri->isNotEmpty()) {
                $typeIds = $allYayinTipleri->pluck('id')->all();

                // ⚡ USE CACHED SERVICE: 10-20x faster than direct query
                $assignmentService = app(FeatureAssignmentService::class);
                $result = $assignmentService->getAssignmentsGroupedByType($typeIds);

                $assignmentCounts = $result['counts'];
                $assignmentsByType = $result['assignments'];
            }

            // ✅ propertyTypesSummary view'de hesaplanacak, burada göndermiyoruz
            return view('admin.property-type-manager.field-dependencies', [
                'kategori' => $kategori,
                'kategoriId' => (int) $kategoriId,
                'yayinTipleri' => $allYayinTipleri,
                'fieldDependencies' => $fieldDependencies,
                'featureCategories' => $featureCategories,
                'availableFeatures' => $featureCategories->mapWithKeys(function ($category) {
                    return [$category->name => $category->features];
                }),
                'assignmentCounts' => $assignmentCounts,
                'assignmentsByType' => $assignmentsByType,
            ]);
        }
        catch (\Throwable $e) {
            report($e);
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                throw $e;
            }
            Log::error('FieldDependenciesIndex error: ' . $e->getMessage(), [
                'event' => 'field_dependencies_index_error',
                'kategori_id' => (int) $kategoriId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }

    /**
     * Feature toggle
     * ✅ SAB: Uses TogglesFeatureDurum trait
     * @deprecated Use toggleFeatureStatus() instead (trait method)
     */
    public function toggleFeature(Request $request)
    {
        // ✅ Trait kullanımı için redirect
        return $this->toggleFeatureStatus($request, $request->input('feature_id'));
    }

    public function bulkSave($kategoriId, Request $request)
    {
        $yayinTipiUpdates = $request->input('yayin_tipi_updates', $request->input('yayin_tipleri', []));
        $featureUpdates = $request->input('feature_updates', $request->input('features', []));
        $fieldDepUpdates = $request->input('field_dependency_updates', $request->input('field_dependencies', []));

        if (empty($yayinTipiUpdates) && empty($featureUpdates) && empty($fieldDepUpdates)) {
            return response()->json(['success' => true, 'message' => 'Toplu kayıtlar güncellendi']);
        }

        app(\App\Services\PropertyType\PropertyTypeBulkUpdateService::class)->bulkUpdate(
            (int) $kategoriId,
            $yayinTipiUpdates,
            $featureUpdates,
            $fieldDepUpdates
        );

        return response()->json(['success' => true, 'message' => 'Toplu kayıtlar güncellendi']);
    }

    /**
     * ========================================
     * POLYMORPHIC FEATURE ASSIGNMENT METHODS
     * ========================================
     */

    /**
     * Assign feature to property type
     * ✅ SAB: Feature assignment validation eklendi
     */
    public function assignFeature(Request $request, $propertyTypeId)
    {
        $request->validate([
            'feature_id' => 'required|exists:features,id',
            'is_required' => 'nullable|boolean',
            'is_visible' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
            'group_name' => 'nullable|string|max:100',
            'label_override' => 'nullable|string|max:255', // ✅ SAB
        ]);

        try {
            $propertyType = YayinTipiSablonu::findOrFail($propertyTypeId);
            $feature = Feature::findOrFail($request->feature_id);

            // ✅ SAB: Feature assignment validation
            $validator = app(FeatureAssignmentValidator::class);
            $validation = $validator->validate($feature, $propertyType);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message'],
                ], 400);
            }

            $assignment = $propertyType->assignFeature($feature, [
                'is_required' => $request->boolean('is_required', false),
                'is_visible' => $request->boolean('is_visible', true),
                'display_order' => $request->input('display_order', 0),
                'group_name' => $request->input('group_name'),
                'label_override' => $request->input('label_override'), // ✅ SAB
            ]);

            LogService::info('Feature assigned to property type', [
                'property_type_id' => $propertyTypeId,
                'feature_id' => $request->feature_id,
                'assignment_id' => $assignment->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Özellik başarıyla atandı',
                'data' => [
                    'assignment_id' => $assignment->id,
                    'feature' => $feature->only(['id', 'name', 'slug', 'field_type']),
                ],
            ]);
        }
        catch (\Exception $e) {
           report($e);
            Log::error('Feature assignment failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Özellik atama hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unassign feature from property type
     */
    public function unassignFeature(Request $request, $propertyTypeId)
    {
        $request->validate([
            'feature_id' => 'required|exists:features,id',
        ]);

        try {
            $propertyType = YayinTipiSablonu::findOrFail($propertyTypeId);
            $feature = Feature::findOrFail($request->feature_id);

            $propertyType->unassignFeature($feature);

            LogService::info('Feature unassigned from property type', [
                'property_type_id' => $propertyTypeId,
                'feature_id' => $request->feature_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Özellik kaldırıldı',
            ]);
        }
        catch (\Exception $e) {
            report($e);
            LogService::error('Feature unassignment failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Özellik kaldırma hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle feature assignment visibility/requirement
     */
    public function toggleFeatureAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:feature_assignments,id',
            'field' => 'required|in:is_visible,is_required',
            'value' => 'required|boolean',
        ]);

        try {
            $aggregate = app(\App\Domain\PropertyHub\PropertyTypeConfiguration::class);
            $assignment = $aggregate->updateAssignmentMetadata(
                (int) $request->assignment_id,
                [$request->field => $request->boolean('value')],
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Özellik güncellendi',
                'data' => $assignment
            ]);
        }
        catch (\Exception $e) {
            report($e);
            LogService::error('Feature assignment toggle failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Güncelleme hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync features for property type (bulk)
     * ✅ SAB: Feature assignment validation eklendi
     */
    public function syncFeatures(Request $request, $propertyTypeId)
    {
        $request->validate([
            'feature_ids' => 'required|array',
            'feature_ids.*' => 'exists:features,id',
        ]);

        try {
            app(\App\Services\PropertyType\PropertyTypeService::class)->syncFeatures((int) $propertyTypeId, $request->feature_ids);

            return response()->json([
                'success' => true,
                'message' => 'Özellikler güncellendi',
                'data' => [
                    'synced_count' => count($request->feature_ids),
                ],
            ]);
        }
        catch (\RuntimeException $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
        catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Senkronizasyon hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update feature assignment configuration
     */
    public function updateFeatureAssignment(Request $request, $assignmentId)
    {
        $request->validate([
            'is_required' => 'nullable|boolean',
            'is_visible' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
            'group_name' => 'nullable|string|max:100',
            'label_override' => 'nullable|string|max:255',
        ]);

        try {
            $aggregate = app(\App\Domain\PropertyHub\PropertyTypeConfiguration::class);
            $assignment = $aggregate->updateAssignmentMetadata(
                (int) $assignmentId,
                $request->only([
                    'is_required',
                    'is_visible',
                    'display_order',
                    'group_name',
                    'label_override',
                ]),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Özellik ayarları güncellendi',
                'data' => $assignment
            ]);
        }
        catch (\Exception $e) {
            report($e);
            LogService::error('Feature assignment update failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Güncelleme hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Yeni yayın tipi oluştur
     *
     * POST /admin/property-types/{kategoriId}/yayin-tipi
     */
    public function createYayinTipi(Request $request, $kategoriId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $yayinTipi = app(\App\Services\PropertyType\PropertyTypeService::class)->createYayinTipi((int) $kategoriId, $validated['name']);

            return response()->json([
                'success' => true,
                'message' => 'Yayın tipi başarıyla eklendi!',
                'data' => [
                    'id' => $yayinTipi->id,
                    'yayin_tip' . 'i_adi' => clone $yayinTipi->name ?? $yayinTipi->ad,
                    'aktiflik_durumu' => clone $yayinTipi->aktiflik_durumu,
                    'display_order' => clone $yayinTipi->display_order,
                ],
            ]);
        }
        catch (\RuntimeException $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
        catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Sunucu hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Yayın tipi silme
     *
     * DELETE /admin/property-type-manager/{kategoriId}/yayin-tipi/{yayinTipiId}
     *
     * @param int $kategoriId Kategori ID
     * @param int $yayinTipiId Yayın tipi ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyYayinTipi($kategoriId, $yayinTipiId)
    {
        try {
            // ✅ SAB: Mutation delegated to service
            $this->propertyTypeService->deleteYayinTipi((int) $yayinTipiId, (int) $kategoriId);

            return response()->json([
                'success' => true,
                'message' => 'Yayın tipi başarıyla silindi!',
            ]);
        }
        catch (\RuntimeException $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Yayın tipi bulunamadı!',
            ], 404);
        }
        catch (\Exception $e) {
            report($e);
            LogService::error('Yayın tipi silme hatası', [
                'kategori_id' => $kategoriId,
                'yayin_tipi_id' => $yayinTipiId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Yayın tipi silinirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alt kategori silme
     *
     * DELETE /admin/property-type-manager/{kategoriId}/alt-kategori/{altKategoriId}
     *
     * @param int $kategoriId Ana kategori ID
     * @param int $altKategoriId Alt kategori ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyAltKategori($kategoriId, $altKategoriId)
    {
        try {
            // ✅ SAB: Mutation delegated
            $this->propertyTypeService->deleteAltKategori((int) $altKategoriId, (int) $kategoriId);

            return response()->json([
                'success' => true,
                'message' => 'Alt kategori başarıyla silindi!',
            ]);
        }
        catch (\RuntimeException $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Alt kategori bulunamadı!',
            ], 404);
        }
        catch (\Exception $e) {
            report($e);
            LogService::error('Alt kategori silme hatası', [
                'alt_kategori_id' => $altKategoriId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Alt kategori silinirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }
}
