<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\PropertyHub\PropertyHubOrchestrator;
use App\Actions\PropertyHub\CreateFeatureAction;
use App\Actions\PropertyHub\UpdateFeatureAction;
use App\Actions\PropertyHub\ToggleFeatureAction;
use App\Actions\PropertyHub\ArchiveFeatureAction;
use App\Actions\PropertyHub\DeleteFeatureAction;
use App\Actions\PropertyHub\ApplyPackAction;
use App\Actions\PropertyHub\SyncPivotAssignmentsAction;
use App\Services\AI\YalihanCortex;
use App\Services\Response\ResponseService;
use App\Http\Controllers\Api\Concerns\ApiResponds;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class PropertyHubController extends Controller
{
    use ApiResponds;

    public function __construct(
        private PropertyHubOrchestrator $hub,
        private YalihanCortex $cortex
    ) {}

    /**
     * Main hub dashboard
     */
    public function index()
    {
        $dashboard = $this->hub->getDashboardStats();

        $stats = $dashboard['stats'];
        $healthScore = $dashboard['health_score'];

        $recentChanges = \App\Models\TemplateChangeLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.property-hub.index', compact(
            'stats',
            'recentChanges',
            'healthScore'
        ));
    }


    /**
     * Feature list
     */
    public function features(Request $request)
    {
        $data = $this->hub->getFeaturesListData($request->all());

        return view('admin.property-hub.features.index', $data);
    }

    /**
     * Create feature form
     */
    public function createFeature()
    {
        $categories = FeatureCategory::ordered()->get();

        return view('admin.property-hub.features.create', compact('categories'));
    }

    /**
     * Store new feature
     */
    public function storeFeature(Request $request, CreateFeatureAction $action)
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
    public function editFeature(Feature $feature)
    {
        $categories = FeatureCategory::ordered()->get();

        return view('admin.property-hub.features.edit', compact('feature', 'categories'));
    }

    /**
     * Update feature
     */
    public function updateFeature(Request $request, Feature $feature, UpdateFeatureAction $action)
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
    public function toggleFeature(\App\Models\Feature $feature, ToggleFeatureAction $action): JsonResponse
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
    public function archiveFeature(\App\Models\Feature $feature, ArchiveFeatureAction $action): JsonResponse
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
    public function destroyFeature(Feature $feature, DeleteFeatureAction $action)
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
     * Feature packs list
     */
    public function packs()
    {
        $packs = FeaturePack::with(['features'])
            ->withCount('features')
            ->ordered() // context7-ignore
            ->paginate(20);

        // Features for pack creation modal
        $features = Feature::where('aktiflik_durumu', true)
            ->with('category')
            ->ordered() // context7-ignore
            ->get();

        // Templates for apply modal
        $templates = YayinTipiSablonu::all();

        $kategoriler = IlanKategori::where('aktiflik_durumu', true)
            ->ordered() // context7-ignore
            ->get();

        return view('admin.property-hub.packs.index', compact('packs', 'features', 'templates', 'kategoriler'));
    }

    /**
     * Store new pack
     */
    /**
     * Store a new feature pack
     */
    public function storePack(Request $request): JsonResponse
    {
        try {
            $pack = $this->hub->createPack($request->all());
            return ResponseService::success($pack, 'Feature Pack başarıyla oluşturuldu');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseService::serverError('İşlem başarısız: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Update feature pack
     */
    public function updatePack(Request $request, \App\Models\FeaturePack $pack): JsonResponse
    {
        try {
            $updated = $this->hub->updatePack($pack, $request->all());
            return ResponseService::success($updated, 'Feature Pack başarıyla güncellendi');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseService::serverError('İşlem başarısız: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Delete feature pack
     */
    public function destroyPack(\App\Models\FeaturePack $pack): JsonResponse
    {
        try {
            $this->hub->deletePack($pack);
            return ResponseService::success(null, 'Feature Pack başarıyla silindi');
        } catch (\Exception $e) {
            return ResponseService::serverError('İşlem başarısız: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Apply pack to templates
     */
    public function applyPack(Request $request, \App\Models\FeaturePack $pack, ApplyPackAction $action): JsonResponse
    {
        $validated = $request->validate([
            'yayin_tipi_ids' => 'required|array|min:1',
            'yayin_tipi_ids.*' => 'exists:yayin_tipi_sablonlari,id',
            'mode' => 'nullable|in:merge,replace',
        ]);

        try {
            $result = $this->hub->applyPackToTemplates(
                $pack,
                $validated['yayin_tipi_ids'],
                auth()->id(),
                $request->mode ?? 'merge'
            );

            return ResponseService::success($result, "Paket başarıyla uygulandı");
        } catch (\Exception $e) {
            return ResponseService::serverError('İşlem başarısız: ' . $e->getMessage(), $e);
        }
    }


    /**
     * Store AI Generated Template Structure (UPS Standard V1)
     *
     * [SAB ENFORCEMENT]: Domain Consolidation
     * Aggregate Root uzerinden sealTemplate() cagirilir.
     */
    public function storeTemplateStructure(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'junction_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'ups_json' => 'required|array',
            'should_seal' => 'nullable|boolean'
        ]);

        try {
            $result = $this->hub->aggregateRoot->sealTemplate(
                junctionId: (int) $validated['junction_id'],
                upsJson: $validated['ups_json'],
                shouldSeal: $validated['should_seal'] ?? true,
                userId: auth()->id()
            );

            if ($result['is_duplicate']) {
                return ResponseService::success(
                    $result['template'],
                    'Bu sablon zaten guncel surum olarak kayitli.'
                );
            }

            return ResponseService::success(
                $result['template'],
                "AI Sablonu basariyla muhurlendi (v{$result['template']->template_version}) 🛡️"
            );

        } catch (\Exception $e) {
            return ResponseService::error('Sablon muhurleme hatasi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get features assigned to a specific category-template pivot
     */
    public function getPivotAssignments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'alt_kategori_id' => 'required|exists:ilan_kategorileri,id',
        ]);

        $data = $this->hub->getPivotAssignments(
            (int) $validated['yayin_tipi_id'],
            (int) $validated['alt_kategori_id']
        );

        return response()->json($data);
    }

    /**
     * Save feature assignments for a specific category-template pivot
     */
    public function savePivotAssignments(Request $request, SyncPivotAssignmentsAction $action): JsonResponse
    {
        $validated = $request->validate([
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'alt_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'feature_ids' => 'present|array',
            'feature_ids.*' => 'exists:features,id',
        ]);

        try {
            $this->hub->syncPivotAssignments(
                (int) $validated['yayin_tipi_id'],
                (int) $validated['alt_kategori_id'],
                $validated['feature_ids'],
                auth()->id()
            );

            return ResponseService::success(null, 'Kategoriye özel özellikler güncellendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Kayıt başarısız: ' . $e->getMessage(), $e);
        }
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
     * AI: Suggest template structure from category + optional description.
     */
    public function aiSuggestTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $startedAt = microtime(true);
        $traceId = (string) Str::uuid();

        try {
            $aiData = $this->cortex->generateTemplateSuggestions(
                $validated['category_name'],
                (string) ($validated['description'] ?? '')
            );

            $httpCode = 200;
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            return response()->json([
                'success' => true,
                'data' => $aiData,
                'mapped_features' => $this->mapSuggestedFeatures($aiData),
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
                'message' => 'AI şablon önerisi başarısız: ' . $e->getMessage(),
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

    private function mapSuggestedFeatures(array $aiData): array
    {
        return collect($aiData['groups'] ?? [])
            ->flatMap(fn (array $group) => collect($group['features'] ?? [])
                ->map(fn (array|string $feature) => is_array($feature)
                    ? ($feature['slug'] ?? $feature['name'] ?? null)
                    : $feature))
            ->filter(fn ($slug) => is_string($slug) && $slug !== '')
            ->values()
            ->all();
    }

    // [SAB]: normalizeArrayRecursive() kaldırıldı → SealedTemplateJson Value Object'e taşındı
    public function featureCategories()
    {
        return redirect()->route('admin.ozellikler.kategoriler.index');
    }

    /**
     * Analytics dashboard
     */
    public function analytics(Request $request)
    {
        $data = $this->hub->buildAnalyticsDashboard($request->all());

        return view('admin.property-hub.analytics.index', $data);
    }

    /**
     * Export templates
     */
    public function export(Request $request)
    {
        try {
            $export = $this->hub->exportFullConfiguration();
            $filename = 'ups-templates-' . now()->format('Y-m-d-His') . '.json';

            return response()->json($export)
                ->header('Content-Disposition', "attachment; filename={$filename}")
                ->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Import templates
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);

        try {
            $result = $this->hub->importConfiguration($request->file('file'));

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import başarısız: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Quick search endpoint for command palette
     */
    public function search(Request $request): JsonResponse
    {
        $results = $this->hub->searchFeaturesAndCategories($request->input('q', ''));

        return response()->json([
            'results' => $results,
        ]);
    }

    /**
     * Apply a master template (blueprint) to a specific yayin tipi şablonu
     */
    public function applyMasterTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'master_template_id' => 'required|exists:ups_master_templates,id',
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'mode' => 'nullable|in:merge,replace',
        ]);

        try {
            $result = $this->hub->applyMasterTemplate(
                (int) $request->master_template_id,
                (int) $request->yayin_tipi_id,
                ['mode' => $request->mode ?? 'merge']
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
}
