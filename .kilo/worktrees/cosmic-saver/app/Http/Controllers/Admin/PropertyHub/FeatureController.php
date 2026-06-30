<?php

namespace App\Http\Controllers\Admin\PropertyHub;

use App\Actions\PropertyHub\ArchiveFeatureAction;
use App\Actions\PropertyHub\CreateFeatureAction;
use App\Actions\PropertyHub\DeleteFeatureAction;
use App\Actions\PropertyHub\ToggleFeatureAction;
use App\Actions\PropertyHub\UpdateFeatureAction;
use App\Http\Controllers\Api\Concerns\ApiResponds;
use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Models\IlanKategori;
use App\Services\AI\YalihanCortex;
use App\Services\PropertyHub\PropertyHubOrchestrator;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * PropertyHub Feature Controller
 *
 * Handles feature CRUD operations and AI-powered feature analysis.
 * Part of PropertyHub modular refactoring (Sprint 2).
 */
class FeatureController extends Controller
{
    use ApiResponds;

    public function __construct(
        private PropertyHubOrchestrator $hub,
        private YalihanCortex $cortex
    ) {}

    /**
     * Feature list
     */
    public function index(Request $request)
    {
        $data = $this->hub->getFeaturesListData($request->all());

        return view('admin.property-hub.features.index', $data);
    }

    /**
     * Create feature form
     */
    public function create()
    {
        $categories = FeatureCategory::ordered()->get();

        return view('admin.property-hub.features.create', compact('categories'));
    }

    /**
     * Store new feature
     */
    public function store(Request $request, CreateFeatureAction $action)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:features,slug',
            'type' => 'required|in:boolean,text,number,select', // context7-ignore
            'feature_category_id' => 'nullable|exists:feature_categories,id',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'options' => 'nullable|array',
            'display_order' => 'nullable|integer',
        ]);

        $feature = $action->handle($validated, auth()->id());

        if ($request->wantsJson()) {
            return ResponseService::success($feature, 'Feature başarıyla oluşturuldu');
        }

        return redirect()
            ->route('admin.property-hub.features.index')
            ->with('success', 'Feature başarıyla oluşturuldu');
    }

    /**
     * Edit feature form
     */
    public function edit(Feature $feature)
    {
        $categories = FeatureCategory::ordered()->get();

        return view('admin.property-hub.features.edit', compact('feature', 'categories'));
    }

    /**
     * Update feature
     */
    public function update(Request $request, Feature $feature, UpdateFeatureAction $action)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:features,slug,' . $feature->id,
            'type' => 'required|in:boolean,text,number,select', // context7-ignore
            'feature_category_id' => 'nullable|exists:feature_categories,id',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'options' => 'nullable|array',
            'display_order' => 'nullable|integer',
        ]);

        $feature = $action->handle($feature, $validated, auth()->id());

        if ($request->wantsJson()) {
            return ResponseService::success($feature, 'Feature başarıyla güncellendi');
        }

        return redirect()
            ->route('admin.property-hub.features.index')
            ->with('success', 'Feature başarıyla güncellendi');
    }

    /**
     * Toggle feature aktiflik_durumu
     */
    public function toggle(Feature $feature, ToggleFeatureAction $action): JsonResponse
    {
        try {
            $this->hub->toggleFeatureStatus($feature);
            return ResponseService::success(
                ['aktiflik_durumu' => $feature->aktiflik_durumu],
                $feature->aktiflik_durumu ? 'Feature aktifleştirildi' : 'Feature pasifleştirildi'
            );
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Archive feature
     */
    public function archive(Feature $feature, ArchiveFeatureAction $action): JsonResponse
    {
        try {
            $this->hub->archiveFeature($feature);
            return ResponseService::success(null, 'Feature arşivlendi');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete feature
     */
    public function destroy(Feature $feature, DeleteFeatureAction $action)
    {
        // Check if feature has assignments
        $assignmentCount = $feature->assignments()->count();

        if ($assignmentCount > 0) {
            return redirect()
                ->route('admin.property-hub.features.index')
                ->with('error', "Bu özellik {$assignmentCount} yerde kullanılıyor. Önce atamaları kaldırın.");
        }

        $action->handle($feature, auth()->id());

        return redirect()
            ->route('admin.property-hub.features.index')
            ->with('success', 'Feature başarıyla silindi');
    }

    /**
     * AI: Analyze feature gaps for a category.
     */
    public function aiAnalyzeGaps(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'current_features' => 'nullable|array',
        ]);

        $startedAt = microtime(true);
        $traceId = (string) Str::uuid();

        try {
            $result = $this->cortex->analyzePropertyGaps(
                $validated['category_name'],
                $validated['current_features'] ?? []
            );

            $httpCode = 200;
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            return response()->json([
                'success' => true,
                'data' => $result,
                'trace_id' => $traceId,
                'telemetry' => [
                    'basarili' => true,
                    'http_durum_kodu' => $httpCode,
                    'duration_ms' => $durationMs,
                    'istek_url' => $request->fullUrl(),
                    'trace_id' => $traceId,
                ],
            ], $httpCode);
        } catch (\Throwable $e) {
            $httpCode = 500;
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            return response()->json([
                'success' => false,
                'message' => 'AI gap analizi başarısız: ' . $e->getMessage(),
                'trace_id' => $traceId,
                'telemetry' => [
                    'basarili' => false,
                    'http_durum_kodu' => $httpCode,
                    'duration_ms' => $durationMs,
                    'istek_url' => $request->fullUrl(),
                    'trace_id' => $traceId,
                ],
            ], $httpCode);
        }
    }

    /**
     * AI: Extract structured features from free text.
     */
    public function aiExtractFeatures(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|min:3',
        ]);

        $startedAt = microtime(true);
        $traceId = (string) Str::uuid();

        try {
            $result = $this->cortex->extractFeaturesFromText($validated['text']);
            $httpCode = 200;
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            return response()->json([
                'success' => true,
                'data' => $result,
                'trace_id' => $traceId,
                'telemetry' => [
                    'basarili' => true,
                    'http_durum_kodu' => $httpCode,
                    'duration_ms' => $durationMs,
                    'istek_url' => $request->fullUrl(),
                    'trace_id' => $traceId,
                ],
            ], $httpCode);
        } catch (\Throwable $e) {
            $httpCode = 500;
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            return response()->json([
                'success' => false,
                'message' => 'AI özellik çıkarımı başarısız: ' . $e->getMessage(),
                'trace_id' => $traceId,
                'telemetry' => [
                    'basarili' => false,
                    'http_durum_kodu' => $httpCode,
                    'duration_ms' => $durationMs,
                    'istek_url' => $request->fullUrl(),
                    'trace_id' => $traceId,
                ],
            ], $httpCode);
        }
    }

    /**
     * 🤖 AI Suggestions for onboarding wizard
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');

        if (!$categoryId) {
            return response()->json([
                'success' => false,
                'message' => 'Category ID gerekli'
            ], 400);
        }

        try {
            // Get category-specific feature suggestions
            $category = IlanKategori::find($categoryId);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori bulunamadı'
                ], 404);
            }

            // Get popular features for this category
            $suggestions = Feature::query()
                ->whereHas('kategoriler', function ($q) use ($categoryId) {
                    $q->where('ilan_kategoriler.id', $categoryId);
                })
                ->where('aktiflik_durumu', 1)
                ->orderBy('display_order')
                ->limit(10)
                ->get()
                ->map(function ($feature) {
                    return [
                        'id' => $feature->id,
                        'name' => $feature->name,
                        'slug' => $feature->slug,
                        'category' => $feature->category->name ?? null
                    ];
                });

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
                'category' => $category->name
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Öneriler alınırken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Redirect to feature categories
     */
    public function categories()
    {
        return redirect()->route('admin.ozellikler.kategoriler.index');
    }
}
