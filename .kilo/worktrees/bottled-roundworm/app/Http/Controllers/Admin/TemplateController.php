<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-service
 */

use App\Http\Controllers\Controller;
use App\Models\YayinTipiSablonu;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 🎨 ADMIN TEMPLATE EDITOR (V2)
 *
 * Manage master templates and their feature assignments.
 * Context7 Standard: display_order, aktiflik_durumu.
 * @sab-ignore-thin
 */
class TemplateController extends Controller
{
    public function __construct(
        private readonly \App\Services\PropertyHub\PropertyHubOrchestrator $orchestratorService,
    ) {}

    public function index()
    {
        $templates = YayinTipiSablonu::withCount('featureAssignments')
            ->orderBy('display_order') // context7-ignore
            ->get();

        $features = \App\Models\Feature::aktif()->get();

        $kategoriler = \App\Models\IlanKategori::where('aktiflik_durumu', true)
            ->where('seviye', 0) // Only top level categories for the modal
            ->get();

        $stats = [
            'total_templates' => $templates->count(),
            'total_assignments' => FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)->count(),
            'total_features' => $features->count(),
        ];

        return view('admin.property-hub.templates.index', compact('templates', 'features', 'stats', 'kategoriler'));
    }

    /**
     * Backward compatibility for legacy edit route
     */
    public function showFromQuery(Request $request)
    {
        $yayinTipiId = $request->integer('yayin_tipi_id');
        if (!$yayinTipiId) {
            return redirect()->route('admin.property-hub.templates.index')
                ->with('error', 'Geçersiz şablon ID.');
        }

        $template = YayinTipiSablonu::findOrFail($yayinTipiId);
        return $this->show($template, $request->integer('kategori_id'));
    }


    public function show(YayinTipiSablonu $template, ?int $kategoriId = null)
    {
        $template->load(['featureAssignments.feature.category']);

        // Fetch category for the view context (Legacy support)
        $kategori = $kategoriId ? \App\Models\IlanKategori::find($kategoriId) : $template->kategori;
        if (!$kategori) {
            $kategori = \App\Models\IlanKategori::where('seviye', 0)->first(); // Fallback to first root category
        }

        // Group assigned features by category
        $groupedAssignments = $template->featureAssignments
            ->filter(fn($a) => $a->feature !== null)
            ->sortBy('display_order')
            ->groupBy(fn($a) => $a->feature?->category?->name ?? 'Genel');

        // Get features not yet assigned
        $assignedFeatureIds = $template->featureAssignments
            ->filter(fn($a) => $a->feature !== null)
            ->pluck('feature_id')->toArray();
        $availableFeatures = Feature::aktif()
            ->whereNotIn('id', $assignedFeatureIds)
            ->with('category')
            ->get();

        $sablon = $template; // backward compat
        $yayinTipi = $template;
        $assignments = $template->featureAssignments;
        $masterTemplates = YayinTipiSablonu::where('aktiflik_durumu', true)->where('display_order', '<', 10)->get(); // Example master filter
        $activeSubCategories = $template->altKategoriler()
            ->where('alt_kategori_yayin_tipi.aktiflik_durumu', true)
            ->get();
        $allCategories = \App\Models\IlanKategori::where('seviye', 0)->get();

        return view('admin.property-hub.templates.edit', compact(
            'yayinTipi',
            'sablon',
            'kategori',
            'assignments',
            'groupedAssignments',
            'availableFeatures',
            'masterTemplates',
            'activeSubCategories',
            'allCategories'
        ));
    }

    public function update(Request $request, YayinTipiSablonu $template)
    {
        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'aktiflik_durumu' => 'required|boolean',
            'display_order' => 'required|integer',
        ]);

        $this->orchestratorService->updateTemplate($template, $validated);

        return redirect()->route('admin.property-hub.yayin-tipi-sablonlari.index')
            ->with('success', 'Şablon başarıyla güncellendi.');
    }

    /**
     * AJAX: Assign a feature to a template
     */
    public function assignFeature(Request $request)
    {
        $validated = $request->validate([
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'feature_id' => 'required|exists:features,id',
            'is_required' => 'boolean',
            'display_order' => 'integer'
        ]);

        $result = $this->orchestratorService->assignFeature($validated['yayin_tipi_id'], $validated['feature_id'], $validated);

        return response()->json($result);
    }

    /**
     * AJAX: Remove a feature from a template
     */
    public function removeFeature(Request $request)
    {
        if ($request->has('assignment_id')) {
            // 🛡️ SAB Refactor: Move direct DB write to orchestrator
            $success = $this->orchestratorService->deleteAssignment($request->input('assignment_id'));
            return response()->json(['success' => $success]);
        }

        $validated = $request->validate([
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'feature_id' => 'required|exists:features,id'
        ]);

        $success = $this->orchestratorService->unassignFeature($validated['yayin_tipi_id'], $validated['feature_id']);

        return response()->json(['success' => $success]);
    }

    /**
     * AJAX/API: Sync all feature assignments
     */
    public function syncFeatures(Request $request, YayinTipiSablonu $template)
    {
        $features = $request->input('features', []); // array of {id, is_required, is_visible, display_order}

        $this->orchestratorService->syncTemplateFeatures($template, $features);

        return response()->json([
            'success' => true,
            'message' => 'Özellikler başarıyla güncellendi.',
            'count' => count($features)
        ]);
    }

    /**
     * AJAX: Bulk assign features to a template
     */
    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'feature_ids' => 'required|array',
            'feature_ids.*' => 'exists:features,id'
        ]);

        $result = $this->orchestratorService->bulkAssignFeatures($validated['yayin_tipi_id'], $validated['feature_ids']);

        return response()->json($result);
    }
}
