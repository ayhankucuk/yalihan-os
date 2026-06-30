<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Domain\PropertyHub\PropertyTypeConfiguration;
use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\TemplateChangeLog;
use App\Services\Response\ResponseService;
use App\Services\Ups\UpsPreviewService;
use App\Services\Logging\LogService;
use App\Actions\Admin\Ups\UpdateFeatureAssignmentAction;
use Illuminate\Http\Request;

/**
 * UPS Template Manager Controller
 *
 * Bu controller YayinTipiSablonu (master template) scope'unda çalışır.
 * AR scope (AltKategoriYayinTipi) ile karıştırılmamalıdır.
 *
 * Tüm write operasyonları Aggregate Root üzerinden yapılır.
 * Controller sadece HTTP layer (validation, routing, response) içerir.
 *
 * Context7 Compliance: Template assignment management
 */
class UpsTemplateManagerController extends Controller
{
    public function __construct(
        private UpsPreviewService $previewService,
        private PropertyTypeConfiguration $aggregate,
    ) {}

    public function index()
    {
        $kategoriler = IlanKategori::where('seviye', 0)
            ->ordered() // context7-ignore
            ->paginate(15);

        return view('admin.ups.templates.index', compact('kategoriler'));
    }

    public function edit(Request $request)
    {
        $kategoriId = $request->integer('kategori_id');
        $yayinTipiId = $request->integer('yayin_tipi_id');

        if ($kategoriId > 0 && $yayinTipiId <= 0) {
            $template = YayinTipiSablonu::first();
            if ($template) {
                return redirect()->route('admin.ups.templates.edit', [
                    'kategori_id' => $kategoriId,
                    'yayin_tipi_id' => $template->id
                ]);
            }
        }

        $data = $this->getAssignments($kategoriId, $yayinTipiId);
        $availableFeatures = Feature::where('aktiflik_durumu', true)->assignable()->ordered()->get(); // context7-ignore

        $kategoriler = IlanKategori::where('seviye', 0)->ordered()->get(); // context7-ignore
        $yayinTipleri = YayinTipiSablonu::all();
        $packs = FeaturePack::enabled()->ordered()->withCount('features')->get(); // context7-ignore

        return view('admin.ups.templates.edit', [
            'kategori_id' => $kategoriId,
            'yayin_tipi_id' => $yayinTipiId,
            'assignments' => $data['assignments'],
            'grouped_assignments' => $data['grouped_assignments'],
            'stats' => $data['stats'],
            'parent_id' => $data['parent_id'],
            'available_features' => $availableFeatures,
            'availableFeatures' => $availableFeatures,
            'kategoriler' => $kategoriler,
            'yayinTipleri' => $yayinTipleri,
            'packs' => $packs,
        ]);
    }

    public function addFeature(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer',
            'yayin_tipi_id' => 'required|integer',
            'feature_id' => 'required|exists:features,id',
            'display_order' => 'integer|min:0',
        ]);

        $template = YayinTipiSablonu::findOrFail($validated['yayin_tipi_id']);
        $feature = Feature::findOrFail($validated['feature_id']);

        if (!($feature->aktiflik_durumu ?? true)) {
            throw new \Exception("Feature '{$feature->slug}' is inactive and cannot be assigned.");
        }

        // Delegate to Aggregate Root (tx boundary + idempotency inside applicator)
        $assigned = $this->aggregate->assignFeatures(
            $template->id,
            [$feature->id],
            'manual',
            auth()->id(),
            YayinTipiSablonu::class
        );

        if (empty($assigned)) {
            // Already existed (idempotent)
            $existing = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $template->id)
                ->where('feature_id', $feature->id)
                ->first();

            return ResponseService::success(['created' => false, 'assignment_id' => $existing?->id]);
        }

        // Update display_order + enrichment fields if needed
        $assignment = $assigned[0];
        $currentKategori = IlanKategori::find($validated['kategori_id']);
        $inheritance = $currentKategori
            ? $this->computeInheritance($feature, $currentKategori)
            : ['is_inherited' => false, 'origin_category_name' => 'Sistem'];
        $groupName = $feature->category ? $feature->category->name : 'Genel Özellikler';

        app(UpdateFeatureAssignmentAction::class)->handle($assignment, [
            'is_required' => false,
            'is_visible' => true,
            'is_inherited' => $inheritance['is_inherited'],
            'origin_category_name' => $inheritance['origin_category_name'],
            'group_name' => $groupName,
            'display_order' => $validated['display_order'] ?? $this->getNextDisplayOrder($template->id),
        ]);

        $this->logChange($template->id, 'feature_added', $feature->id, [
            'slug' => $feature->slug,
            'source' => 'manual',
            'group' => $groupName
        ]);

        return ResponseService::success(['created' => true, 'assignment_id' => $assignment->id]);
    }

    public function removeFeature(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer',
            'yayin_tipi_id' => 'required|integer',
            'feature_id' => 'required|integer',
        ]);

        $template = YayinTipiSablonu::findOrFail($validated['yayin_tipi_id']);
        $feature = Feature::find($validated['feature_id']);

        // Delegate to Aggregate Root
        $deleted = $this->aggregate->unassignFeatures(
            $template->id,
            [$validated['feature_id']],
            YayinTipiSablonu::class
        );

        if ($deleted && $feature) {
            $this->logChange($template->id, 'feature_removed', $validated['feature_id'], [
                'feature_slug' => $feature->slug,
                'feature_name' => $feature->name,
            ]);
        }

        LogService::info('UPS Template remove feature', [
            'kategori_id' => $validated['kategori_id'],
            'yayin_tipi_id' => $validated['yayin_tipi_id'],
            'feature_id' => $validated['feature_id'],
            'deleted_count' => $deleted,
            'user_id' => auth()->id(),
        ]);

        return ResponseService::success(['removed' => $deleted > 0]);
    }

    public function applyPack(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer',
            'yayin_tipi_id' => 'required|integer',
            'pack_id' => 'required|exists:ups_feature_packs,id',
            'mode' => 'string|in:merge,replace',
        ]);

        $template = YayinTipiSablonu::findOrFail($validated['yayin_tipi_id']);
        $mode = $validated['mode'] ?? 'merge';

        // Delegate to Aggregate Root (tx boundary + pack logic inside domain service)
        $result = $this->aggregate->applyFeaturePack(
            $template->id,
            $validated['pack_id'],
            $mode,
            auth()->id(),
            YayinTipiSablonu::class
        );

        LogService::info('UPS Pack assigned', [
            'pack_id' => $validated['pack_id'],
            'kategori_id' => $validated['kategori_id'],
            'yayin_tipi_id' => $validated['yayin_tipi_id'],
            'added_count' => $result['added_count'],
            'skipped_count' => $result['skipped_count'],
            'user_id' => auth()->id(),
        ]);

        return ResponseService::success($result, 'Paket başarıyla uygulandı');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer',
            'yayin_tipi_id' => 'required|integer',
            'feature_orders' => 'required|array',
            'feature_orders.*.feature_id' => 'required|integer',
            'feature_orders.*.display_order' => 'required|integer|min:0',
        ]);

        $template = YayinTipiSablonu::findOrFail($validated['yayin_tipi_id']);

        // Delegate to Aggregate Root
        $updated = $this->aggregate->reorderFeatures(
            $template->id,
            $validated['feature_orders'],
            auth()->id(),
            YayinTipiSablonu::class
        );

        $this->logChange($template->id, 'feature_reordered', null, $validated['feature_orders']);

        LogService::info('UPS Template reorder features', [
            'kategori_id' => $validated['kategori_id'],
            'yayin_tipi_id' => $validated['yayin_tipi_id'],
            'updated_count' => $updated,
            'user_id' => auth()->id(),
        ]);

        return ResponseService::success(['updated_count' => $updated]);
    }

    public function syncFromParent(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required|integer',
            'yayin_tipi_id' => 'required|integer',
        ]);

        // V2 Global Template sisteminde senkronizasyon gerekmez
        return ResponseService::success([
            'synced' => 0,
            'message' => 'V2 Global Template sisteminde ebeveyn senkronizasyonu gerekmez.'
        ]);
    }

    public function aiSuggestions(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer',
        ]);

        $kategori = IlanKategori::findOrFail($validated['kategori_id']);
        $rootId = $kategori->parent_id ?: $kategori->id;

        $suggestions = Feature::whereHas('category', function($q) use ($rootId) {
                // Placeholder: categories that apply to this root
            })
            ->limit(5)
            ->get()
            ->toArray();

        return ResponseService::success(['suggestions' => $suggestions]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer',
            'yayin_tipi_id' => 'required|integer',
            'planned_features' => 'required|array',
            'planned_features.*' => 'string',
        ]);

        $preview = $this->previewService->previewTemplateChanges(
            $validated['kategori_id'],
            $validated['yayin_tipi_id'],
            $validated['planned_features']
        );

        return ResponseService::success($preview, 'Preview generated');
    }

    public function importExport()
    {
        $kategoriler = IlanKategori::where('seviye', 0)->ordered()->get(); // context7-ignore
        return view('admin.ups.templates.import-export', compact('kategoriler'));
    }

    public function export(Request $request)
    {
        try {
            $validated = $request->validate([
                'kategori_id' => 'required|integer|exists:ilan_kategorileri,id',
                'yayin_tipi_id' => 'required|integer|exists:yayin_tipi_sablonlari,id',
            ]);

            $kategoriId = $validated['kategori_id'];
            $yayinTipiId = $validated['yayin_tipi_id'];

            $template = YayinTipiSablonu::findOrFail($yayinTipiId);
            $data = $this->getAssignments($kategoriId, $yayinTipiId);
            $exportData = $this->formatExportData($data);

            TemplateChangeLog::create([
                // @context7-ignore-next-line
                'yayin_tipi_sablonu_id' => $template->id,
                'user_id' => auth()->id(),
                'aksiyon_tipi' => 'template_exported',
                'yeni_degerler' => [
                    'feature_count' => count($data['features'] ?? []),
                    'exported_at' => now()->toIso8601String(),
                ],
                // @context7-ignore-next-line
                'versiyon_numarasi' => TemplateChangeLog::where('yayin_tipi_sablonu_id', $template->id)->max('versiyon_numarasi') ?? 0,
            ]);

            $filename = "ups_template_{$kategoriId}_{$yayinTipiId}_" . date('Y-m-d_H-i') . ".json";

            return response()->json($exportData, 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Throwable $e) {
            return ResponseService::error('Export failed: ' . $e->getMessage(), 422);
        }
    }

    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:json|max:10240',
            ]);

            $file = $request->file('file');
            $data = json_decode(file_get_contents($file->getRealPath()), true);

            if (!$data || !is_array($data)) {
                return back()->with('error', '❌ Invalid JSON format. File is not valid JSON.');
            }

            // Validate export schema
            try {
                $this->validateExportSchema($data);
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', '❌ Invalid export format: ' . $e->getMessage());
            }

            $yayinTipiId = $data['template']['yayin_tipi_id'] ?? null;
            $template = YayinTipiSablonu::findOrFail($yayinTipiId);

            // Validate features before delegating to AR
            $featureDataList = [];
            foreach ($data['features'] ?? [] as $featureData) {
                $feature = Feature::find($featureData['feature_id'] ?? null);
                if (!$feature || !($feature->aktiflik_durumu ?? true)) {
                    continue; // Skip inactive or missing features
                }
                $featureDataList[] = [
                    'feature_id' => $feature->id,
                    'source_type' => 'import',
                    'display_order' => $featureData['display_order'] ?? 0,
                ];
            }

            // Delegate to Aggregate Root (tx boundary inside AR)
            $results = $this->aggregate->importTemplateFeatures(
                $template->id,
                $featureDataList,
                auth()->id()
            );

            TemplateChangeLog::create([
                // @context7-ignore-next-line
                'yayin_tipi_sablonu_id' => $template->id,
                'user_id' => auth()->id(),
                'aksiyon_tipi' => 'template_imported',
                'yeni_degerler' => [
                    'features_added' => $results['added'],
                    'features_skipped' => $results['skipped'],
                    'errors_count' => count($results['errors']),
                    'imported_at' => now()->toIso8601String(),
                ],
                // @context7-ignore-next-line
                'versiyon_numarasi' => TemplateChangeLog::where('yayin_tipi_sablonu_id', $template->id)->max('versiyon_numarasi') ?? 0,
            ]);

            if ($results['skipped'] > 0 && count($results['errors']) > 0) {
                return back()->with('warning', "⚠️ Import completed with issues: {$results['added']} added, {$results['skipped']} skipped. " . json_encode($results['errors']));
            }

            return back()->with('success', "✅ Template imported successfully! {$results['added']} features added.");

        } catch (\Throwable $e) {
            return back()->with('error', '❌ Import error: ' . $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        $kategoriId = $request->get('kategori_id');
        $yayinTipiId = $request->get('yayin_tipi_id');

        if (!$kategoriId || !$yayinTipiId) {
            return back()->with('error', 'Template seçimi zorunludur');
        }

        $template = YayinTipiSablonu::where('id', $yayinTipiId)->firstOrFail();

        $changelog = TemplateChangeLog::forTemplate($template->id)->paginate(20);

        return view('admin.ups.templates.history', [
            'template' => $template,
            'changelog' => $changelog,
            'aksiyon_options' => [
                'feature_added' => '✅ Özellik Eklendi',
                'feature_removed' => '❌ Özellik Kaldırıldı',
                'feature_reordered' => '🔄 Özellik Sıralandı',
                'template_exported' => '📥 Template İndirme',
                'template_imported' => '📤 Template Yükleme',
            ],
        ]);
    }

    public function advancedHistory()
    {
        return view('admin.ups.templates.history');
    }

    // ─── Private Helpers (read-only / view-layer enrichment) ───

    private function getAssignments(int $kategoriId, int $yayinTipiId): array
    {
        $template = YayinTipiSablonu::findOrFail($yayinTipiId);
        $currentKategori = IlanKategori::with('parent')->findOrFail($kategoriId);

        $assignments = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', $template->id)
            ->with(['feature' => function($q) {
                $q->with('category')->active(); // context7-ignore
            }])
            ->ordered() // context7-ignore
            ->get()
            ->map(function ($assignment) use ($currentKategori) {
                if ($assignment->feature) {
                    $inheritance = $this->computeInheritance($assignment->feature, $currentKategori);
                    $assignment->is_inherited = $inheritance['is_inherited'];
                    $assignment->origin_category_name = $inheritance['origin_category_name'] ?? 'Bilinmiyor';
                }
                return $assignment;
            });

        $grouped = $assignments->groupBy(function($a) {
            return $a->feature?->category?->name ?? 'Genel';
        });

        return [
            'template_id' => $template->id,
            'kategori_id' => $kategoriId,
            'yayin_tipi_id' => $yayinTipiId,
            'kategori_name' => $currentKategori->name,
            'parent_id' => $currentKategori->parent_id,
            'assignments' => $assignments,
            'grouped_assignments' => $grouped,
            'stats' => [
                'total' => $assignments->count(),
                'inherited' => $assignments->where('is_inherited', true)->count(),
                'manual' => $assignments->where('source_type', 'manual')->count(),
                'ai' => $assignments->where('source_type', 'ai')->count(),
            ]
        ];
    }

    private function computeInheritance(Feature $feature, IlanKategori $currentKategori): array
    {
        $originName = 'Sistem';
        $isInherited = false;

        if ($currentKategori->parent_id) {
            $parent = $currentKategori->parent ?? IlanKategori::find($currentKategori->parent_id);
            if ($parent) {
                $originName = $parent->name;
                $isInherited = true;
            }
        }

        return [
            'is_inherited' => $isInherited,
            'origin_category_name' => $originName,
        ];
    }

    private function getNextDisplayOrder(int $templateId): int
    {
        return (FeatureAssignment::where('assignable_id', $templateId)->max('display_order') ?? 0) + 10;
    }

    private function logChange(int $templateId, string $action, ?int $featureId = null, array $values = []): void
    {
        $version = TemplateChangeLog::where('yayin_tipi_sablonu_id', $templateId)
            ->max('versiyon_numarasi') ?? 0;

        TemplateChangeLog::create([
            'yayin_tipi_sablonu_id' => $templateId,
            'user_id' => auth()->id(),
            'aksiyon_tipi' => $action,
            'feature_id' => $featureId,
            'yeni_degerler' => $values,
            'versiyon_numarasi' => $version + 1,
        ]);
    }

    private function formatExportData(array $data): array
    {
        return [
            'export_version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'template' => [
                'kategori_id' => $data['kategori_id'],
                'kategori_name' => $data['kategori_name'],
                'yayin_tipi_id' => $data['yayin_tipi_id'],
            ],
            'features' => $data['assignments']->map(function ($assignment) {
                return [
                    'feature_id' => $assignment->feature_id,
                    'feature_slug' => $assignment->feature->slug,
                    'feature_name' => $assignment->feature->name,
                    'feature_type' => $assignment->feature->type, // context7-ignore
                    'display_order' => $assignment->display_order,
                    'is_required' => $assignment->is_required,
                    'is_visible' => $assignment->is_visible,
                ];
            })->values()->all(),
            'statistics' => [
                'total_features' => $data['assignments']->count(),
                'required_count' => $data['assignments']->where('is_required', true)->count(),
                'visible_count' => $data['assignments']->where('is_visible', true)->count(),
            ]
        ];
    }

    private function validateExportSchema(array $data): bool
    {
        if (!isset($data['export_version'])) {
            throw new \InvalidArgumentException('Missing export_version field');
        }
        if (!isset($data['template']) || !is_array($data['template'])) {
            throw new \InvalidArgumentException('Missing or invalid template field');
        }
        if (!isset($data['template']['kategori_id']) || !is_int($data['template']['kategori_id'])) {
            throw new \InvalidArgumentException('Missing or invalid template.kategori_id');
        }
        if (!isset($data['template']['yayin_tipi_id']) || !is_int($data['template']['yayin_tipi_id'])) {
            throw new \InvalidArgumentException('Missing or invalid template.yayin_tipi_id');
        }
        if (!isset($data['features']) || !is_array($data['features'])) {
            throw new \InvalidArgumentException('Missing or invalid features field');
        }

        foreach ($data['features'] as $idx => $feature) {
            if (!isset($feature['feature_id']) || !is_int($feature['feature_id'])) {
                throw new \InvalidArgumentException("features[{$idx}]: Missing or invalid feature_id");
            }
            if (!isset($feature['display_order']) || !is_int($feature['display_order'])) {
                throw new \InvalidArgumentException("features[{$idx}]: Missing or invalid display_order");
            }
        }

        return true;
    }
}
