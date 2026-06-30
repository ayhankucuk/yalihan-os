<?php

namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Admin\Traits\HandlesCategoryOperations;
use App\Models\Feature;
use App\Models\FeatureCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * @sab-ignore-thin
 */
class OzellikKategoriController extends AdminController
{
    use HandlesCategoryOperations;

    public function __construct(
        private readonly \App\Services\Category\FeatureCategoryBulkService $bulkService,
    ) {}

    public function index(Request $request)
    {
        $query = FeatureCategory::query();

        if ($request->filled('q')) {
            $q = (string) $request->get('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($request->filled('aktiflik_durumu')) {
            $aktiflik = (bool) $request->input('aktiflik_durumu');
            if (Schema::hasColumn('feature_categories', 'aktiflik_durumu')) {
                $query->where('aktiflik_durumu', $aktiflik);
            }
        }

        $kategoriler = $query->withCount('features')
            ->orderBy('display_order') // context7-ignore
            ->orderBy('name') // context7-ignore
            ->paginate(20)
            ->appends(request()->query());

        $aktiflik = $request->get('aktiflik_durumu');

        return view('admin.ozellikler.kategoriler.index', compact('kategoriler', 'aktiflik'));
    }

    public function create()
    {
        $ilanKategorileri = \App\Models\IlanKategori::where('aktiflik_durumu', true)
            ->where('seviye', 0)
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'slug', 'seviye']);

        return view('admin.ozellikler.kategoriler.create', compact('ilanKategorileri'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:64'],
            'applies_to' => ['nullable', 'array'],
            'aktiflik_durumu' => ['required', 'boolean'],
            'display_order' => ['nullable', 'integer'],
        ]);

        if (! empty($data['applies_to'])) {
            if (is_array($data['applies_to'])) {
                $data['applies_to'] = array_values(array_filter($data['applies_to']));
            } elseif (is_string($data['applies_to'])) {
                $applies = explode(',', $data['applies_to']);
                $data['applies_to'] = array_map('trim', $applies);
            }
        } else {
            $data['applies_to'] = null;
        }

        $this->initializeCategoryOperations();
        $data['slug'] = $this->generateSlug($data['slug'] ?? $data['name'], FeatureCategory::class);
        if (! array_key_exists('display_order', $data) || is_null($data['display_order'])) {
            $data['display_order'] = (int) (FeatureCategory::max('display_order') + 1);
        }

        $this->bulkService->createCategory($data);

        \App\Helpers\FeatureCacheHelper::clearCategoryCache();
        $this->clearCache('feature_categories', 'stats');
        $this->forgetWithQuery('feature_categories', 'index');

        return redirect()->route('admin.ozellikler.kategoriler.index')->with('success', 'Kategori oluşturuldu.');
    }

    public function show(int $id)
    {
        $kategori = FeatureCategory::with('features')->findOrFail($id);

        return view('admin.ozellikler.kategoriler.ozellikler', compact('kategori'));
    }

    public function edit(int $id)
    {
        $kategori = FeatureCategory::with('features')->findOrFail($id);

        $ilanKategorileri = \App\Models\IlanKategori::where('aktiflik_durumu', true)
            ->where('seviye', 0)
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'slug', 'seviye']);

        return view('admin.ozellikler.kategoriler.edit', compact('kategori', 'ilanKategorileri'));
    }

    public function update(Request $request, int $id)
    {
        $kategori = FeatureCategory::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:64'],
            'applies_to' => ['nullable', 'array'],
            'aktiflik_durumu' => ['required', 'boolean'],
            'display_order' => ['nullable', 'integer'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
        ]);

        if (! empty($data['applies_to'])) {
            if (is_array($data['applies_to'])) {
                $data['applies_to'] = array_values(array_filter($data['applies_to']));
            } elseif (is_string($data['applies_to'])) {
                $applies = explode(',', $data['applies_to']);
                $data['applies_to'] = array_map('trim', $applies);
            }
        } else {
            $data['applies_to'] = null;
        }

        $this->initializeCategoryOperations();
        $data['slug'] = $this->generateSlug($data['slug'] ?? $data['name'], FeatureCategory::class, $kategori->id);

        $this->bulkService->updateCategory($kategori, $data);

        $this->clearCache('feature_categories', 'stats');
        $this->forgetWithQuery('feature_categories', 'index');

        return redirect()->route('admin.ozellikler.kategoriler.index')->with('success', 'Kategori güncellendi.');
    }

    public function destroy(int $id)
    {
        $kategori = FeatureCategory::findOrFail($id);
        $this->bulkService->deleteCategory($kategori);

        $this->clearCache('feature_categories', 'stats');
        $this->forgetWithQuery('feature_categories', 'index');

        return redirect()->route('admin.ozellikler.kategoriler.index')->with('success', 'Kategori silindi.');
    }

    public function kategorisizOzellikler()
    {
        $ozellikler = Feature::whereNull('feature_category_id')->orderBy('name')->paginate(50); // context7-ignore

        return view('admin.ozellikler.kategoriler.kategorisiz-ozellikler', compact('ozellikler'));
    }

    public function toggleDurum(int $kategori)
    {
        $model = FeatureCategory::findOrFail($kategori);
        $this->bulkService->toggleDurum($model);

        \App\Helpers\FeatureCacheHelper::clearCategoryCache();
        $this->clearCache('feature_categories', 'stats');

        return response()->json(['success' => true, 'aktiflik_durumu' => $model->aktiflik_durumu]);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:feature_categories,id'],
            'items.*.display_order' => ['required', 'integer'],
        ]);

        $this->bulkService->reorder($data['items']);

        \App\Helpers\FeatureCacheHelper::clearCategoryCache();
        $this->clearCache('feature_categories', 'stats');

        return response()->json(['success' => true]);
    }

    public function checkSlug(Request $request)
    {
        // ✅ SAB: FeatureCategory kullan (OzellikKategori yasak)
        $slug = (string) $request->get('slug');
        $excludeId = $request->integer('exclude_id');
        $query = FeatureCategory::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        $exists = $query->exists();

        return response()->json(['unique' => ! $exists]);
    }

    public function ozellikler(int $id)
    {
        $kategori = FeatureCategory::with('features')->findOrFail($id);
        $ozellikler = $kategori->features()->orderBy('display_order')->orderBy('name')->paginate(20); // context7-ignore

        return view('admin.ozellikler.kategoriler.ozellikler', compact('kategori', 'ozellikler'));
    }

    public function quickUpdate(Request $request, int $id)
    {
        // ✅ SAB: FeatureCategory kullan (OzellikKategori yasak)
        $kategori = FeatureCategory::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'aktiflik_durumu' => ['sometimes', 'boolean'],
            'display_order' => ['sometimes', 'integer'], // Context7: display_order alanı
        ]);
        $this->bulkService->quickUpdate($kategori, $data);

        // ✅ REFACTORED: Merkezi Cache Helper kullan
        \App\Helpers\FeatureCacheHelper::clearCategoryCache();
        // Legacy cache manager (backward compatibility)
        $this->clearCache('feature_categories', 'stats');

        return response()->json(['success' => true]);
    }

    public function duplicate(int $id)
    {
        // ✅ SAB: FeatureCategory kullan (OzellikKategori yasak)
        $kategori = FeatureCategory::findOrFail($id);
        $yeni = $this->bulkService->duplicate($kategori);

        // ✅ REFACTORED: Merkezi Cache Helper kullan
        \App\Helpers\FeatureCacheHelper::clearCategoryCache();
        // Legacy cache manager (backward compatibility)
        $this->clearCache('feature_categories', 'stats');

        return response()->json(['success' => true, 'id' => $yeni->id]);
    }

    public function bulkToggleDurum(Request $request)
    {
        // ✅ SAB: FeatureCategory kullan (OzellikKategori yasak)
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:feature_categories,id'],
            'aktiflik_durumu' => ['required', 'boolean'],
        ]);
        $this->bulkService->bulkToggleDurum($data['ids'], $data['aktiflik_durumu']);

        // ✅ REFACTORED: Merkezi Cache Helper kullan
        \App\Helpers\FeatureCacheHelper::clearCategoryCache();
        // Legacy cache manager (backward compatibility)
        $this->clearCache('feature_categories', 'stats');

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        // ✅ SAB: FeatureCategory kullan (OzellikKategori yasak)
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:feature_categories,id'],
        ]);
        $this->bulkService->bulkDelete($data['ids']);

        // ✅ REFACTORED: Merkezi Cache Helper kullan
        \App\Helpers\FeatureCacheHelper::clearCategoryCache();
        // Legacy cache manager (backward compatibility)
        $this->clearCache('feature_categories', 'stats');

        return response()->json(['success' => true]);
    }

    public function stats()
    {
        // ✅ REFACTORED: Merkezi Cache Manager kullan
        // ✅ Bug Fix: Protected helper method kullan - doğrudan property erişimi yerine
        $stats = $this->rememberCache('feature_categories', 'stats', function () {
            return [
                'toplam' => FeatureCategory::count(),
                'active' => FeatureCategory::where('aktiflik_durumu', true)->count(), // context7-ignore
                'pasif' => FeatureCategory::where('aktiflik_durumu', false)->count(),
            ];
        });

        return response()->json($stats);
    }

    // ✅ REFACTORED: generateUniqueSlug() ve clearIndexCache() metodları kaldırıldı
    // Artık HandlesCategoryOperations trait'i ve merkezi servisler kullanılıyor
}
