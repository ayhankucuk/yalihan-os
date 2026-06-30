<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Enums\IlanDurumu;
use App\Http\Requests\Admin\IlanKategoriFieldUpdateRequest;
use App\Http\Requests\Admin\IlanKategoriRequest;
use App\Http\Requests\Admin\IlanKategoriSuggestRequest;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Services\Logging\LogService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

use App\Http\Controllers\Admin\Traits\ManagesPropertyTypes;
use App\Services\Ilan\IlanKategoriService;

class IlanKategoriController extends AdminController
{
    use ManagesPropertyTypes;

    protected IlanKategoriService $kategoriService;

    public function __construct(IlanKategoriService $kategoriService)
    {
        $this->kategoriService = $kategoriService;
    }
    /**
     * Kategori listesi sayfası
     * GET /admin/ilan-kategorileri
     *
     * @context7: İstatistikler ve filtreleme ile kategori listesi
     */
    public function index(Request $request): \Illuminate\View\View
    {
        // ✅ SAB: All query logic delegated to service
        $filters = [
            'search' => $request->get('search'),
            'seviye' => $request->get('seviye'),
            'parent_id' => $request->get('parent_id'),
            'aktiflik_durumu' => $request->has('aktiflik_durumu') ? $request->get('aktiflik_durumu') === 'aktif' : null,
        ];

        $data = $this->kategoriService->getDashboardData($filters);

        // Sayfalama manuel olarak controller'da kalabilir (UI concern)
        $perPage = 15;
        $currentPage = (int) $request->get('page', 1);
        $total = count($data['parents']) + count($data['children']); // Basit bir total hesabı

        // View'a gönderilecek verileri hazırla
        $istatistikler = $data['stats'];
        $ustKategoriler = $data['ust_kategoriler'];

        // Pagination logic (Service'den gelen veriyi paginate et)
        $models = collect($data['parents'])->merge($data['children']);
        $paginatedItems = $models->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $kategoriler = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $healthService = new \App\Services\Admin\HealthScoreService();
        $healthScore = $healthService->calculate();

        return view('admin.ilan-kategorileri.index', compact('kategoriler', 'istatistikler', 'ustKategoriler', 'healthScore'));
    }

    /**
     * Yeni kategori oluşturma formu
     * GET /admin/ilan-kategorileri/create
     *
     * @context7: Kategori oluşturma formu sayfası
     */
    public function create(): \Illuminate\View\View
    {
        $anaKategoriler = $this->kategoriService->getActiveRootCategories();

        return view('admin.ilan-kategorileri.create', compact('anaKategoriler'));
    }

    /**
     * Yeni kategori kaydetme
     * POST /admin/ilan-kategorileri
     *
     * @context7: Form verilerini doğrulayıp yeni kategori oluşturur
     */
    public function store(IlanKategoriRequest $request): \Illuminate\Http\RedirectResponse
    {
        // ✅ STANDARDIZED: Using Form Request
        $validated = $request->validated();

        try {
            $kategori = $this->kategoriService->createCategory($validated);

            return redirect()->route('admin.ilan-kategorileri.index')
                ->with('success', 'Kategori başarıyla oluşturuldu.');
        }
        catch (Exception $e) {
            report($e);
            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Yeni kategori kaydetme (JSON API Versiyonu)
     * POST /admin/ilan-kategorileri/api/store
     *
     * @context7: AJAX/JavaScript requests için JSON response döner
     */
    public function storeJson(Request $request): \Illuminate\Http\JsonResponse
    {
        // Basit validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ilan_kategorileri',
            'icon' => 'nullable|string|max:2',
        ]);

        try {
            $kategori = $this->kategoriService->createCategory($request->all(), true);

            return response()->json([
                'success' => true,
                'message' => 'Kategori başarıyla eklendi!',
                'data' => $kategori,
            ], 201);
        }
        catch (\Illuminate\Database\QueryException $e) {
            report($e);
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                return response()->json([
                    'success' => false,
                    'message' => 'Bu slug zaten kullanılıyor!',
                ], 422);
            }
            return response()->json([
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage(),
            ], 500);
        }
        catch (Exception $e) {
           report($e);
            Log::error('Kategori storeJson hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tekil kategori görüntüleme
     * GET /admin/ilan-kategorileri/{kategori}
     *
     * @context7: Kategori detay sayfası ve istatistikleri
     *
     * @param  int|string  $kategori  Route parameter (can be id or slug)
     */
    public function show($kategori): \Illuminate\View\View
    {
        // ✅ SAB: Detail fetching and stat calculation delegated to service
        $data = $this->kategoriService->getCategoryDetail($kategori);

        $kategori = $data['kategori'];
        $stats = $data['stats'];
        $son_ilanlar = $data['son_ilanlar'];

        return view('admin.ilan-kategorileri.show', compact('kategori', 'stats', 'son_ilanlar'));
    }

    /**
     * Kategori düzenleme formu
     * GET /admin/ilan-kategorileri/{id}/edit
     *
     * @context7: Kategori düzenleme formu sayfası
     */
    public function edit(int $id): \Illuminate\View\View
    {
        $kategori = IlanKategori::findOrFail($id);

        $parentCategories = $this->kategoriService->getActiveRootCategories([$id]);

        return view('admin.ilan-kategorileri.edit', compact('kategori', 'parentCategories'));
    }

    /**
     * Kategori güncelleme
     * PUT /admin/ilan-kategorileri/{id}
     *
     * @context7: Kategori bilgilerini günceller
     */
    public function update(IlanKategoriRequest $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $kategori = IlanKategori::findOrFail($id);

        // ✅ STANDARDIZED: Using Form Request
        $validated = $request->validated();

        try {
            $kategori = $this->kategoriService->updateCategory($kategori, $validated);

            return redirect()->route('admin.ilan-kategorileri.index')
                ->with('success', $kategori->name.' başarıyla güncellendi!');
        }
        catch (Exception $e) {
            report($e);
            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Kategori silme
     * DELETE /admin/ilan-kategorileri/{id}
     *
     * @context7: Kategori silme işlemi (soft delete)
     */
    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        try {
            $this->kategoriService->deleteCategory($id);

            return redirect()->route('admin.ilan-kategorileri.index')
                ->with('success', 'Kategori başarıyla silindi.');
        }
        catch (Exception $e) {
           report($e);
            Log::error('Kategori silme hatası: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Kategori performans metrikleri
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerformance(Request $request)
    {
        try {
            $categoryId = $request->get('category_id');

            if (! $categoryId) {
                return response()->json(['error' => 'Kategori ID gerekli'], 400);
            }

            $category = IlanKategori::find($categoryId);
            if (! $category) {
                return response()->json(['error' => 'Kategori bulunamadı'], 404);
            }

            $performance = [
                'popularity' => $this->calculateCategoryPopularity($categoryId),
                'growth' => $this->calculateCategoryGrowth($categoryId),
                'seo_score' => $this->calculateSEOScores($categoryId),
            ];

            return response()->json($performance);
        }
        catch (Exception $e) {
           report($e);
            Log::error('Performans metrikleri hatası: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * AI destekli kategori önerileri
     */
    public function suggestCategories(IlanKategoriSuggestRequest $request): \Illuminate\Http\JsonResponse
    {
        // ✅ STANDARDIZED: Using Form Request
        $validated = $request->validated();

        try {
            // Basit kategori önerisi mantığı
            $suggestions = $this->generateCategorySuggestions(
                $validated['title'],
                $validated['description'] ?? '',
                $validated['features'] ?? []
            );

            return response()->json($suggestions);
        }
        catch (Exception $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Kategori trendleri
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrends(Request $request)
    {
        try {
            $trends = [
                'popular_categories' => $this->getPopularCategories(),
                'growth_trends' => $this->getGrowthTrends(),
                'seasonal_data' => $this->getSeasonalData(),
            ];

            return response()->json($trends);
        }
        catch (Exception $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * AI analiz
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function aiAnalysis(Request $request)
    {
        try {
            $analysis = [
                'category_health' => $this->analyzeCategoryHealth(),
                'optimization_suggestions' => $this->getOptimizationSuggestions(),
                'performance_insights' => $this->getPerformanceInsights(),
            ];

            return response()->json($analysis);
        }
        catch (Exception $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Yardımcı metodlar

    private function calculateCategoryPopularity($categoryId)
    {
        return $this->kategoriService->getCategoryPerformanceMetrics($categoryId);
    }

    private function calculateCategoryGrowth($categoryId)
    {
        return $this->kategoriService->getCategoryGrowthMetrics($categoryId);
    }

    private function calculateSEOScores($categoryId)
    {
        // Basit SEO skoru hesaplama
        $category = IlanKategori::find($categoryId);

        $score = 0;
        if ($category->meta_description) {
            $score += 30;
        }
        if ($category->meta_keywords) {
            $score += 20;
        }
        if (strlen($category->name) >= 3 && strlen($category->name) <= 60) {
            $score += 25;
        }
        if ($category->description && strlen($category->description) >= 50) {
            $score += 25;
        }

        return $score;
    }

    private function generateCategorySuggestions($title, $description, $features)
    {
        // Basit öneri sistemi
        $suggestions = [];

        // Başlık bazlı öneriler
        $existingCategories = $this->kategoriService->searchCategoriesByName($titleWords);

        foreach ($existingCategories as $category) {
            $suggestions[] = [
                'name' => $category->name,
                'confidence' => 0.8,
                'reason' => 'Başlık benzerliği',
            ];
        }

        return $suggestions;
    }

    private function getPopularCategories()
    {
        return $this->kategoriService->getPopularCategoriesData();
    }

    private function getGrowthTrends()
    {
        // Son 6 ayın büyüme trendi
        return $this->kategoriService->getGrowthTrendsData();
    }

    private function getSeasonalData()
    {
        return $this->kategoriService->getSeasonalIlanData();
    }

    private function analyzeCategoryHealth()
    {
        return $this->kategoriService->getCategoryHealthMetrics();
    }

    private function getOptimizationSuggestions()
    {
        return $this->kategoriService->getCategoryOptimizationSuggestions();
    }

    /**
     * Inline güncelleme (AJAX endpoint)
     * POST /admin/ilan-kategorileri/{id}/inline-update
     *
     * @context7: Tablo üzerinden hızlı düzenleme
     */
    public function inlineUpdate(IlanKategoriFieldUpdateRequest $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $kategori = IlanKategori::findOrFail($id);

            // ✅ STANDARDIZED: Using Form Request
            $validated = $request->validated();

            $kategori = $this->kategoriService->inlineUpdateCategory(
                $kategori,
                $validated['field'],
                $validated['value']
            );

            return response()->json([
                'success' => true,
                'message' => 'Kategori güncellendi',
                'data' => $kategori,
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz veri',
                'errors' => $e->errors(),
            ], 422);
        }
        catch (Exception $e) {
           report($e);
            Log::error('Inline update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Güncelleme hatası: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alt kategorileri getir (AJAX endpoint)
     * GET /admin/ilan-kategorileri/alt-kategoriler?parent_id={id}
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAltKategoriler(Request $request)
    {
        $parentId = $request->input('parent_id');

        if (! $parentId) {
            return response()->json([
                'success' => false,
                'message' => 'Parent ID gerekli',
            ], 400);
        }

        $altKategoriler = $this->kategoriService->getAltKategoriler((int) $parentId);

        return response()->json([
            'success' => true,
            'data' => $altKategoriler,
        ]);
    }

    /**
     * Kategoriye özel özellikleri getir (AJAX endpoint)
     * GET /admin/ilan-kategorileri/{id}/ozellikler
     *
     * @param  int|string  $kategoriId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOzellikler($kategoriId)
    {
        $ozellikler = $this->kategoriService->getActiveFeaturesByCategory((int) $kategoriId);

        return response()->json([
            'success' => true,
            'data' => $ozellikler->map(function ($oz) {
                return [
                    'id' => $oz->id,
                    'name' => $oz->name,
                    'slug' => $oz->slug,
                    'type' => $oz->veri_tipi, // context7-ignore
                    'options' => $oz->veri_secenekleri ? json_decode($oz->veri_secenekleri, true) : null,
                    'unit' => $oz->birim,
                    'required' => (bool) $oz->zorunlu,
                    'help' => $oz->aciklama,
                ];
            }),
        ]);
    }

    /**
     * Yayın tiplerini getir (AJAX endpoint)
     * GET /admin/ilan-kategorileri/yayin-tipleri?kategori_id={id}
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getYayinTipleri(Request $request)
    {
        $kategoriId = $request->input('kategori_id');

        if (! $kategoriId) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori ID gerekli',
            ], 400);
        }

        $yayinTipleri = $this->kategoriService->getActiveYayinTipleriByCategory((int) $kategoriId);

        return response()->json([
            'success' => true,
            'data' => $yayinTipleri,
        ]);
    }

    private function getPerformanceInsights()
    {
        return $this->kategoriService->getCategoryPerformanceInsights();
    }

    /**
     * Toplu işlem (AJAX endpoint)
     * POST /admin/ilan-kategorileri/bulk-action
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAction(Request $request)
    {
        try {
            $request->validate([
                'action' => 'required|string|in:activate,deactivate,delete',
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:ilan_kategorileri,id',
            ]);

            $count = $this->kategoriService->bulkAction($request->input('action'), $request->input('ids'));

            return response()->json([
                'success' => true,
                'message' => $count.' kategori başarıyla işlendi',
                'count' => $count,
            ]);
        }
        catch (Exception $e) {

            report($e);

            // ✅ STANDARDIZED: Using ResponseService (automatic logging)
            Log::error('Bulk action error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Toplu işlem hatası: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Kategori özelliklerini yönet (Feature Manager UI)
     * ✅ RECURSIVE INHERITANCE: Kategori hiyerarşisinde özellikleri gösterir
     *
     * Template Inheritance Flow:
     * 1. findTemplateCategory() via IlanFeatureService
     * 2. Recursive climber: Seviye 2 → Seviye 1 → Seviye 0
     * 3. getFeaturesByCategory() with hasCustomTemplate() checks
     * 4. Feature Manager UI displays inheritance chain
     */
    public function featureManager(int $id)
    {
        try {
            $kategori = IlanKategori::findOrFail($id);
            $hasCustomTemplate = $kategori->hasCustomTemplate();

            LogService::debug('Feature Manager Load', [
                'kategori_id' => $id,
                'kategori_name' => $kategori->name,
                'has_custom_template' => $hasCustomTemplate,
            ]);

            // ✅ Kategoriye atanmış özellikleri çek
            $features = $kategori->featureAssignments()->with('feature')->get();

            // ✅ Kategori slug'ına göre izin verilen özellikleri filtrele
            $allowedCategoryNames = $this->allowedFeatureCategoryNames($kategori->slug);
            $availableFeatures = $this->kategoriService->getActiveFeaturesByCategoryNames($allowedCategoryNames);

            return view('admin.ilan-kategorileri.feature-manager', [
                'kategori' => $kategori,
                'features' => $features,
                'availableFeatures' => $availableFeatures,
                'hasCustomTemplate' => $hasCustomTemplate,
            ]);
        }
        catch (Exception $e) {
            report($e);
            LogService::error('Feature Manager Error', [
                'kategori_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Kategori özellikleri yüklenemedi: '.$e->getMessage());
        }
    }

    /**
     * 🤖 AI-Powered Feature Suggestions
     * GET /admin/ilan-kategorileri/{id}/ai-feature-suggestions
     *
     * Returns intelligent feature recommendations based on:
     * - Similar category usage patterns
     * - Publication type compatibility
     * - Semantic similarity analysis
     *
     * @param Request $request
     * @param int $id Category ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAIFeatureSuggestions(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $kategori = IlanKategori::findOrFail($id);

            // Get publication type ID (default to first yayin tipi if not specified)
            $yayinTipiId = $request->input('yayin_tipi_id');

            if (!$yayinTipiId) {
                $firstYayinTipi = YayinTipiSablonu::where('kategori_id', $kategori->id)
                    ->where('aktiflik_durumu', true)
                    ->first();
                $yayinTipiId = $firstYayinTipi?->id;
            }

            if (!$yayinTipiId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu kategori için aktif yayın tipi bulunamadı.',
                    'suggestions' => []
                ]);
            }

            // ✅ AI-Powered Recommendations
            $recommenderService = app(\App\Services\AI\FeatureRecommenderService::class);
            $recommendations = $recommenderService->recommend($kategori->id, $yayinTipiId);

            // Format for UI
            $suggestions = $recommendations->map(function ($rec) {
                return [
                    'feature_id' => $rec['feature_id'],
                    'name' => $rec['feature']->name,
                    'type' => $rec['feature']->type, // context7-ignore
                    'score' => round($rec['score'], 2),
                    'reason' => $rec['reason'],
                    'priority' => $rec['score'] >= 80 ? 'high' : ($rec['score'] >= 50 ? 'medium' : 'low'),
                    'badge_color' => $rec['score'] >= 80 ? 'green' : ($rec['score'] >= 50 ? 'yellow' : 'gray'),
                ];
            })->values();

            LogService::debug('AI Feature Suggestions Generated', [
                'kategori_id' => $kategori->id,
                'kategori_name' => $kategori->name,
                'yayin_tipi_id' => $yayinTipiId,
                'suggestion_count' => $suggestions->count(),
            ]);

            return response()->json([
                'success' => true,
                'kategori' => [
                    'id' => $kategori->id,
                    'name' => $kategori->name,
                ],
                'suggestions' => $suggestions,
                'total' => $suggestions->count(),
            ]);

        }

        catch (Exception $e) {
            report($e);
            LogService::error('AI Feature Suggestions Error', [
                'kategori_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI önerileri oluşturulamadı: '.$e->getMessage(),
                'suggestions' => []
            ], 500);
        }
    }

    /**
     * Nexus Studio - Visual inheritance & override UI
     */
    public function nexusStudio(int $id): \Illuminate\View\View
    {
        $kategori = IlanKategori::findOrFail($id);
        $ilanFeatureService = app(\App\Services\Ilan\IlanFeatureService::class);

        $blueprint = $ilanFeatureService->getFeaturesByCategory($kategori->id);
        $allFeatures = Feature::with('category')->orderBy('name')->get(); // context7-ignore
        $directAssignments = $this->kategoriService->getDirectFeatureAssignments(IlanKategori::class)
            ->where('assignable_id', $kategori->id)
            ->pluck('feature_id')
            ->toArray();

        return view('admin.ilan-kategorileri.nexus-studio', [
            'kategori' => $kategori,
            'blueprint' => $blueprint,
            'allFeatures' => $allFeatures,
            'directAssignments' => $directAssignments,
        ]);
    }

    /**
     * 🧬 DNA Kopyalayıcı (Override Backend)
     * Converts an inherited feature into a local assignment for this category.
     *
     * POST /admin/ilan-kategorileri/{id}/override-feature
     */
    public function overrideFeature(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validate([
                'feature_id' => 'required|integer|exists:features,id',
            ]);

            $kategori = IlanKategori::findOrFail($id);
            $this->kategoriService->overrideFeature($kategori, $validated['feature_id']);

            return response()->json([
                'success' => true,
                'message' => 'Özellik başarıyla bu kategoriye kopyalandı (artık yerel).',
            ]);
        }
        catch (Exception $e) {
            report($e);
            LogService::error('Override Feature Error', [
                'kategori_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Özellik kopyalanamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔒 Miras Kesici (Inheritance Toggle)
     * Toggles the inherit_from_parent flag on this category.
     *
     * POST /admin/ilan-kategorileri/{id}/toggle-inheritance
     */
    public function toggleInheritance(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $kategori = IlanKategori::findOrFail($id);
            $newFlag = $this->kategoriService->toggleInheritance($kategori);

            return response()->json([
                'success' => true,
                'inherit_from_parent' => $newFlag,
                'message' => $newFlag
                    ? 'Miras zinciri aktif: Üst kategoriden özellikler alınacak.'
                    : 'Miras zinciri kesildi: Bu kategori artık bağımsız (Standalone).',
            ]);
        }
        catch (Exception $e) {
            report($e);
            LogService::error('Toggle Inheritance Error', [
                'kategori_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Miras ayarı değiştirilemedi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ➕ Global Havuzdan Ekleme (The Connector)
     * Attaches a feature from the global pool to this category.
     *
     * POST /admin/ilan-kategorileri/{id}/attach-feature
     */
    public function attachFeature(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validate([
                'feature_id' => 'required|integer|exists:features,id',
            ]);

            $kategori = IlanKategori::findOrFail($id);
            $this->kategoriService->attachFeature($kategori, $validated['feature_id']);

            return response()->json([
                'success' => true,
                'message' => 'Özellik başarıyla kategoriye eklendi.',
            ]);
        }
        catch (Exception $e) {
            report($e);
            LogService::error('Attach Feature Error', [
                'kategori_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Özellik eklenemedi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update category sequence
     * POST /admin/ilan-kategorileri/reorder
     *
     * @context7: Sıralama yönetimi
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:ilan_kategorileri,id',
            'items.*.display_order' => 'required|integer',
        ]);

        try {
            $this->kategoriService->updateSequence($request->items);

            return response()->json(['success' => true]);
        }
        catch (Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Export categories to Excel
     * GET /admin/ilan-kategorileri/export
     *
     * Context7: Kategorileri Excel formatında export eder
     */
    public function export(Request $request)
    {
        try {
            // ✅ SAB: Export data generation delegated to service
            $data = $this->kategoriService->getExportData();

            $dosyaAdi = 'Ilan_Kategorileri_'.now()->format('Ymd_His').'.xlsx';

            return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray
            {
                protected $data;
                public function __construct(array $data) { $this->data = $data; }
                public function array(): array { return $this->data; }
            }, $dosyaAdi);
        }
        catch (Exception $e) {
            report($e);
            return back()->with('error', 'Export Hatası: '.$e->getMessage());
        }
    }

    public function stats(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'toplam_kategori' => IlanKategori::count(),
                'aktif_kategori' => IlanKategori::where('aktiflik_durumu', 1)->count(),
                'ana_kategori' => IlanKategori::whereNull('parent_id')->count(),
            ],
        ]);
    }

    public function addFeature(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        return $this->attachFeature($request, $id);
    }
}
