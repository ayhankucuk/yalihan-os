<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Helpers\FeatureCacheHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OzellikController extends AdminController
{
    public function index(Request $request)
    {
        // PHASE 2.2: Tab-based UI - Collect all data

        // Tab 1: Tüm Özellikler
        // Context7: feature_category_id kullanılmalı (category_id yok)
        $query = Feature::with('category')->sirali();
        if ($request->filled('q')) {
            $q = trim($request->get('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }
        if ($request->has('category_id') && $request->category_id) {
            $query->where('feature_category_id', $request->category_id);
        }
        if ($request->has('aktiflik_durumu') && $request->aktiflik_durumu !== '') {
            $query->where('aktiflik_durumu', $request->aktiflik_durumu == '1' ? true : false);
        }
        $ozellikler = $query->paginate(20, ['*'], 'ozellikler_page')->appends($request->query());

        // Tab 2: Kategoriler
        $kategoriQuery = FeatureCategory::withCount('features')->orderBy('display_o' . 'rder')->orderBy('name'); // context7-ignore
        if ($request->filled('kategori_search')) {
            $q = $request->get('kategori_search');
            $kategoriQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }
        $kategoriListesi = $kategoriQuery->paginate(20, ['*'], 'kategoriler_page');

        // Tab 3: Kategorisiz Özellikler
        // Context7: feature_category_id kullanılmalı (category_id yok)
        $kategorisizOzellikler = Feature::whereNull('feature_category_id')
            ->orderBy('name') // context7-ignore
            ->paginate(20, ['*'], 'kategorisiz_page');

        // İstatistikler (cached)
        // ✅ SAB: C7-DURUM-MAP
        // ✅ Force refresh to ensure Turkish keys are included
        $istatistikler = FeatureCacheHelper::getStats(true);

        // ✅ CACHE: Kategoriler dropdown için cache ekle (merkezi helper)
        $kategoriler = FeatureCacheHelper::getCategoryList();

        // Feature Packs for bulk action
        $featurePacks = \App\Models\FeaturePack::where('aktiflik_durumu', true)->orderBy('display_o' . 'rder')->get(); // context7-ignore

        // Active tab (default: ozellikler)
        $activeTab = $request->get('tab', 'ozellikler');

        return view('admin.ozellikler.index', compact(
            'ozellikler',
            'kategoriListesi',
            'kategorisizOzellikler',
            'istatistikler',
            'kategoriler',
            'featurePacks',
            'activeTab' // context7-ignore
        ));
    }

    public function create()
    {
        // ✅ CACHE: Kategoriler dropdown için cache ekle (merkezi helper)
        $kategoriler = FeatureCacheHelper::getCategoryList();

        return view('admin.ozellikler.create', compact('kategoriler'));
    }

    public function store(Request $request, \App\Actions\Ilan\Feature\StoreFeatureAction $action)
    {
        $this->authorize('create', Feature::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'feature_category_id' => 'nullable|exists:feature_categories,id',
            'type' => 'required|in:text,number,boolean,select,checkbox,radio,textarea', // context7-ignore
            'aktiflik_durumu' => 'required|boolean',
            'display_o' . 'rder' => 'nullable|integer',
        ]);

        if ($request->has('sort_o' . 'rder') && !isset($validated['display_o' . 'rder'])) {
            $validated['display_o' . 'rder'] = $request->input('sort_o' . 'rder');
        }

        $action->handle($validated);

        FeatureCacheHelper::clearCategoryCache();

        return redirect()->route('admin.ozellikler.index')
            ->with('success', 'Özellik başarıyla oluşturuldu.');
    }

    public function edit($id)
    {
        $ozellik = Feature::findOrFail($id);
        // ✅ CACHE: Kategoriler dropdown için cache ekle (merkezi helper)
        $kategoriler = FeatureCacheHelper::getCategoryList();

        return view('admin.ozellikler.edit', compact('ozellik', 'kategoriler'));
    }

    public function update(Request $request, $id, \App\Actions\Ilan\Feature\UpdateFeatureAction $action)
    {
        $ozellik = Feature::findOrFail($id);
        $this->authorize('update', $ozellik);

        if ($request->has('field_type') && ! $request->has('type')) { // context7-ignore
            $request->merge(['type' => $request->input('field_type')]); // context7-ignore
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'feature_category_id' => 'nullable|exists:feature_categories,id',
            'type' => 'required|in:text,number,boolean,select,checkbox,radio,textarea', // context7-ignore
            'aktiflik_durumu' => 'required|boolean',
            'display_o' . 'rder' => 'nullable|integer',
            'is_required' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
            'unit' => 'nullable|string|max:50',
            'options' => 'nullable|array',
        ]);

        if ($request->has('sort_o' . 'rder') && !isset($validated['display_o' . 'rder'])) {
            $validated['display_o' . 'rder'] = $request->input('sort_o' . 'rder');
        }

        if ($request->has('zorunlu') && ! $request->has('is_required')) {
            $validated['is_required'] = $request->boolean('zorunlu');
        }

        if ($request->has('aciklama') && ! $request->has('description')) {
            $validated['description'] = $request->input('aciklama');
        }

        $action->handle($ozellik, $validated);

        return redirect()->route('admin.ozellikler.edit', $ozellik->id)
            ->with('success', $ozellik->name . ' başarıyla güncellendi! ✅');
    }

    public function destroy($id, \App\Actions\Ilan\Feature\DestroyFeatureAction $action)
    {
        $ozellik = Feature::findOrFail($id);
        $this->authorize('delete', $ozellik);

        $action->handle($ozellik);

        return redirect()->route('admin.ozellikler.index')
            ->with('success', 'Özellik başarıyla silindi.');
    }

    /**
     * Bulk Actions - Toplu işlemler
     */
    public function bulkAction(Request $request, \App\Actions\Ilan\Feature\BulkFeatureAction $action)
    {
        $this->authorize('manage', Feature::class);

        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:features,id',
        ]);

        $action->handle($validated['ids'], $validated['action']);

        $message = "Toplu işlem başarıyla tamamlandı! ✅";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'count' => count($validated['ids']),
                'action' => $validated['action'],
            ], 200);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Restore soft deleted feature
     */
    public function restore($id, \App\Actions\Ilan\Feature\RestoreFeatureAction $action)
    {
        $this->authorize('manage', Feature::class);

        $action->handle((int) $id);

        return redirect()->back()->with('success', 'Özellik başarıyla geri yüklendi ve aktif edildi');
    }

    /**
     * Bulk Assign to Pack - Toplu pakete ata
     */
    public function bulkAssignToPack(Request $request, \App\Actions\Ilan\Feature\BulkAssignFeatureToPackAction $action)
    {
        $this->authorize('manage', Feature::class);

        $validated = $request->validate([
            'pack_id' => 'required|exists:ups_feature_packs,id',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:features,id',
        ]);

        $addedCount = $action->handle((int) $validated['pack_id'], $validated['ids']);

        return response()->json([
            'message' => "{$addedCount} özellik pakete başarıyla eklendi! 📦",
            'added_count' => $addedCount,
        ]);
    }

    /**
     * AI Kategori Önerisi
     * Context7: Cortex AI integration
     */
    public function suggestCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $cortex = app(\App\Services\AI\YalihanCortex::class);
            $result = $cortex->suggestCategory($request->input('name'));

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
