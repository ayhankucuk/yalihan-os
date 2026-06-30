<?php

namespace App\Services\Ilan;

use App\Enums\IlanDurumu;

use App\Models\IlanKategori;
use App\Models\FeatureAssignment;
use App\Services\Logging\LogService;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class IlanKategoriService
{
    use GuardsAgentWrites;
    /**
     * Create a new category.
     *
     * @param array $data Validated input data
     * @param bool $isApi Call originated from API/JSON
     * @return IlanKategori
     * @throws Exception
     */
    public function createCategory(array $data, bool $isApi = false): IlanKategori
    {
        $this->blockAgentWrite('createCategory');

        $seviye = (int) ($data['seviye'] ?? 0);
        $parentId = $data['parent_id'] ?? null;

        if (!$isApi) {
            if (($seviye == 1 || $seviye == 2) && !$parentId) {
                throw new Exception('Alt kategori veya Yayın Tipi için Üst Kategori seçmelisiniz.');
            }

            if ($seviye == 0 && $parentId) {
                throw new Exception('Ana kategorinin üst kategorisi olamaz.');
            }
        }

        $baseSlug = Str::slug($data['slug'] ?? $data['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (IlanKategori::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $displayOrder = $data['display_order'] ?? 0;
        if ($isApi && !isset($data['display_order'])) {
            $displayOrder = (IlanKategori::where('seviye', 0)->max('display_order') ?? 0) + 1;
        }

        return IlanKategori::create([
            'name' => $data['name'],
            'slug' => $slug,
            'seviye' => $seviye,
            'parent_id' => $parentId,
            'aktiflik_durumu' => $data['aktiflik_durumu'] ?? true,
            'display_order' => $displayOrder,
            'aciklama' => $data['aciklama'] ?? '',
            'icon' => $data['icon'] ?? '🏠',
        ]);
    }

    /**
     * Update an existing category.
     *
     * @param IlanKategori $kategori
     * @param array $data Validated input data
     * @return IlanKategori
     * @throws Exception
     */
    public function updateCategory(IlanKategori $kategori, array $data): IlanKategori
    {
        $this->blockAgentWrite('updateCategory');

        $seviye = (int) $data['seviye'];
        $parentId = $data['parent_id'] ?? null;

        if (($seviye == 1 || $seviye == 2) && !$parentId) {
            throw new Exception('Alt kategori veya Yayın Tipi için Üst Kategori seçmelisiniz.');
        }

        if ($seviye == 0 && $parentId) {
            throw new Exception('Ana kategorinin üst kategorisi olamaz.');
        }

        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (IlanKategori::where('slug', $slug)->where('id', '!=', $kategori->id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $kategori->update([
            'name' => $data['name'],
            'slug' => $slug,
            'seviye' => $seviye,
            'parent_id' => $parentId,
            'aktiflik_durumu' => $data['aktiflik_durumu'] ?? true,
            'display_order' => $data['display_order'] ?? 0,
            'aciklama' => $data['aciklama'] ?? '',
        ]);

        return $kategori;
    }

    /**
     * Modify a single field using inline update logic.
     *
     * @param IlanKategori $kategori
     * @param string $field
     * @param mixed $value
     * @return IlanKategori
     */
    public function inlineUpdateCategory(IlanKategori $kategori, string $field, mixed $value): IlanKategori
    {
        $this->blockAgentWrite('inlineUpdateCategory');

        switch ($field) {
            case 'aktiflik_durumu':
                $kategori->aktiflik_durumu = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'sira':
            case 'display_order':
            case 'sort_order':
                $kategori->display_order = (int) $value;
                break;
            case 'name':
                $kategori->name = $value;
                $kategori->slug = Str::slug($value);
                break;
            default:
                $kategori->{$field} = $value;
        }

        $kategori->save();

        return $kategori;
    }

    /**
     * Delete a category, checking for children and listings.
     *
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function deleteCategory(int $id): void
    {
        $this->blockAgentWrite('deleteCategory');

        $kategori = IlanKategori::withCount(['children', 'ilanlar'])->findOrFail($id);

        if ($kategori->children_count > 0) {
            throw new Exception('Bu kategorinin alt kategorileri var. Önce alt kategorileri silin.');
        }

        if ($kategori->ilanlar_count > 0) {
            throw new Exception('Bu kategoride ilanlar var. Kategori silinemez.');
        }

        $kategori->delete();
    }

    /**
     * Perform bulk operations (activate, deactivate, delete) with TX safely inside Service Layer.
     *
     * @param string $action
     * @param array $ids
     * @return int processed count
     * @throws Exception
     */
    public function bulkAction(string $action, array $ids): int
    {
        $this->blockAgentWrite('bulkAction');

        return DB::transaction(function () use ($action, $ids) {
            $count = 0;

            switch ($action) {
                case 'activate':
                    $count = IlanKategori::whereIn('id', $ids)->update(['aktiflik_durumu' => true]);
                    break;

                case 'deactivate':
                    $count = IlanKategori::whereIn('id', $ids)->update(['aktiflik_durumu' => false]);
                    break;

                case 'delete':
                    $kategoriler = IlanKategori::with(['children:id,parent_id', 'ilanlar:id,ana_kategori_id,alt_kategori_id,yayin_tipi_id'])
                        ->whereIn('id', $ids)
                        ->get();

                    foreach ($kategoriler as $kategori) {
                        if ($kategori->children->isEmpty() && $kategori->ilanlar->isEmpty()) {
                            $kategori->delete();
                            $count++;
                        }
                    }
                    break;
            }

            return $count;
        });
    }

    /**
     * Update the display sequences for multiple categories inside a TX boundary.
     *
     * @param array $items
     * @return void
     */
    public function updateSequence(array $items): void
    {
        $this->blockAgentWrite('updateSequence');

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                DB::table('ilan_kategorileri')
                    ->where('id', $item['id'])
                    ->update(['display_order' => $item['display_order']]);
            }
        });
    }

    /**
     * Override an inherited feature, creating a local FeatureAssignment.
     *
     * @param IlanKategori $kategori
     * @param int $featureId
     * @return FeatureAssignment
     * @throws Exception
     */
    public function overrideFeature(IlanKategori $kategori, int $featureId): FeatureAssignment
    {
        $this->blockAgentWrite('overrideFeature');

        $existing = FeatureAssignment::where('assignable_type', IlanKategori::class)
            ->where('assignable_id', $kategori->id)
            ->where('feature_id', $featureId)
            ->first();

        if ($existing) {
            throw new Exception('Bu özellik zaten bu kategoriye yerel olarak atanmış.');
        }

        $assignment = FeatureAssignment::create([
            'feature_id' => $featureId,
            'assignable_type' => IlanKategori::class,
            'assignable_id' => $kategori->id,
            'is_required' => false,
            'is_visible' => true,
            'display_order' => 0,
        ]);

        LogService::debug('Feature Override Created', [
            'kategori_id' => $kategori->id,
            'kategori_name' => $kategori->name,
            'feature_id' => $featureId,
        ]);

        return $assignment;
    }

    /**
     * Toggle the inherit_from_parent flag on a category.
     *
     * @param IlanKategori $kategori
     * @return bool the new inheritance state
     */
    public function toggleInheritance(IlanKategori $kategori): bool
    {
        $this->blockAgentWrite('toggleInheritance');

        $currentFlag = $kategori->getAttribute('inherit_from_parent');
        $newFlag = $currentFlag === null || $currentFlag === true ? false : true;

        $kategori->{'inherit_from_parent'} = $newFlag;
        $kategori->save();

        LogService::debug('Inheritance Toggle', [
            'kategori_id' => $kategori->id,
            'kategori_name' => $kategori->name,
            'old_flag' => $currentFlag,
            'new_flag' => $newFlag,
        ]);

        return $newFlag;
    }

    /**
     * Attach a feature from the global pool to the category.
     *
     * @param IlanKategori $kategori
     * @param int $featureId
     * @return FeatureAssignment
     * @throws Exception
     */
    public function attachFeature(IlanKategori $kategori, int $featureId): FeatureAssignment
    {
        $this->blockAgentWrite('attachFeature');

        $existing = FeatureAssignment::where('assignable_type', IlanKategori::class)
            ->where('assignable_id', $kategori->id)
            ->where('feature_id', $featureId)
            ->first();

        if ($existing) {
            throw new Exception('Bu özellik zaten bu kategoriye atanmış.');
        }

        $assignment = FeatureAssignment::create([
            'feature_id' => $featureId,
            'assignable_type' => IlanKategori::class,
            'assignable_id' => $kategori->id,
            'is_required' => false,
            'is_visible' => true,
            'display_order' => 0,
        ]);

        LogService::debug('Feature Attached From Global Pool', [
            'kategori_id' => $kategori->id,
            'kategori_name' => $kategori->name,
            'feature_id' => $featureId,
        ]);

        return $assignment;
    }

    /**
     * Get all data required for the category dashboard.
     *
     * @param array $filters
     * @return array
     */
    public function getDashboardData(array $filters = []): array
    {
        // 1. Base Query
        $query = DB::table('ilan_kategorileri');

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['aktiflik_durumu'])) {
            $query->where('aktiflik_durumu', $filters['aktiflik_durumu']);
        }

        // 2. Parents Data
        $parentsData = IlanKategori::whereNull('parent_id')
            ->orderBy('display_order') // context7-ignore
            ->get();

        // 3. Children Data
        $childrenData = IlanKategori::with('parent')
            ->whereNotNull('parent_id')
            ->where('seviye', 1)
            ->orderBy('display_order') // context7-ignore
            ->get();

        // 4. Counts (Ilanlar)
        $anaKategoriCounts = \App\Models\Ilan::query()
            ->withoutGlobalScopes()
            ->reorder()
            ->select('ana_kategori_id')
            ->selectRaw('count(*) as total')
            ->groupBy('ana_kategori_id')
            ->pluck('total', 'ana_kategori_id')
            ->toArray();

        $altKategoriCounts = \App\Models\Ilan::query()
            ->withoutGlobalScopes()
            ->reorder()
            ->select('alt_kategori_id')
            ->selectRaw('count(*) as total')
            ->groupBy('alt_kategori_id')
            ->pluck('total', 'alt_kategori_id')
            ->toArray();

        $yayinTipiCounts = \App\Models\Ilan::query()
            ->withoutGlobalScopes()
            ->reorder()
            ->select('yayin_tipi_id')
            ->selectRaw('count(*) as total')
            ->groupBy('yayin_tipi_id')
            ->pluck('total', 'yayin_tipi_id')
            ->toArray();

        // 5. Statistics
        $stats = [
            'toplam' => DB::table('ilan_kategorileri')->count(),
            'ana_kategoriler' => DB::table('ilan_kategorileri')->whereNull('parent_id')->count(),
            'alt_kategoriler' => DB::table('ilan_kategorileri')->whereNotNull('parent_id')->count(),
            'aktif' => DB::table('ilan_kategorileri')->where('aktiflik_durumu', true)->count(),
            'pasif' => DB::table('ilan_kategorileri')->where('aktiflik_durumu', false)->count(),
            'bugun_eklenen' => DB::table('ilan_kategorileri')->whereDate('created_at', today())->count(),
        ];

        // 6. Root Categories for Selects
        $ustKategoriler = DB::table('ilan_kategorileri')
            ->whereNull('parent_id')
            ->select('id', 'name')
            ->orderBy('name') // context7-ignore
            ->get();

        return [
            'parents' => $parentsData,
            'children' => $childrenData,
            'stats' => $stats,
            'ana_kategori_counts' => $anaKategoriCounts,
            'alt_kategori_counts' => $altKategoriCounts,
            'yayin_tipi_counts' => $yayinTipiCounts,
            'ust_kategoriler' => $ustKategoriler,
        ];
    }

    /**
     * Get detailed information for a specific category.
     *
     * @param int|string $kategoriIdOrSlug
     * @return array
     */
    public function getCategoryDetail(int|string $kategoriIdOrSlug): array
    {
        $query = IlanKategori::with([
            'parent:id,name,slug',
            'children:id,name,slug,parent_id,aktiflik_durumu',
            'ilanlar' => function ($query) {
                $query->select([
                    'id', 'baslik', 'fiyat', 'para_birimi', 'yayin_durumu',
                    'ana_kategori_id', 'alt_kategori_id', 'yayin_tipi_id',
                    'created_at', 'danisman_id',
                ]);
            }
        ]);

        if (is_numeric($kategoriIdOrSlug)) {
            $kategori = $query->findOrFail((int) $kategoriIdOrSlug);
        } else {
            $kategori = $query->where('slug', $kategoriIdOrSlug)->firstOrFail();
        }

        $stats = [
            'toplam_ilan' => $kategori->ilanlar->count(),
            'aktif_ilan' => $kategori->ilanlar->filter(fn($i) => $i->yayin_durumu === \App\Enums\IlanDurumu::YAYINDA->value)->count(),
            'son_30_gun' => $kategori->ilanlar->filter(fn($i) => $i->created_at >= now()->subDays(30))->count(),
            'alt_kategoriler' => $kategori->children->count(),
        ];

        $son_ilanlar = $kategori->ilanlar->sortByDesc('created_at')->take(10)->values();

        return [
            'kategori' => $kategori,
            'stats' => $stats,
            'son_ilanlar' => $son_ilanlar,
        ];
    }

    /**
     * Get data for categories export.
     *
     * @return array
     */
    public function getExportData(): array
    {
        $kategoriler = IlanKategori::with(['parent:id,name', 'children:id,name,parent_id'])
            ->orderBy('display_order', 'asc') // context7-ignore
            ->orderBy('name', 'asc') // context7-ignore
            ->get();

        $data = [
            ['Kategoriler - Excel Raporu'],
            ['Tarih', now()->format('d.m.Y H:i')],
            ['Toplam Kategori', $kategoriler->count()],
            [''],
            ['ID', 'Ad', 'Slug', 'Seviye', 'Üst Kategori', 'Alt Kategori Sayısı', 'Aktiflik Durumu', 'Sıra', 'Oluşturulma Tarihi'],
        ];

        foreach ($kategoriler as $kategori) {
            $data[] = [
                $kategori->id,
                $kategori->name,
                $kategori->slug,
                $kategori->seviye === 0 ? 'Ana' : ($kategori->seviye === 1 ? 'Alt' : 'Yayın Tipi'),
                $kategori->parent?->name ?? '-',
                $kategori->children->count(),
                $kategori->aktiflik_durumu ? IlanDurumu::YAYINDA->value : 'Pasif',
                $kategori->display_order ?? 0,
                $kategori->created_at?->format('d.m.Y H:i') ?? '-',
            ];
        }

        return $data;
    }

    /**
     * Get active root categories, optionally excluding some IDs.
     */
    public function getActiveRootCategories(array $exceptIds = [])
    {
        $query = IlanKategori::whereNull('parent_id')
            ->where('aktiflik_durumu', true);

        if (!empty($exceptIds)) {
            $query->whereNotIn('id', $exceptIds);
        }

        return $query->orderBy('name')->get(); // context7-ignore
    }

    /**
     * Search categories by name exact match (for AI suggestions).
     */
    public function searchCategoriesByName(array $titleWords)
    {
        return IlanKategori::whereIn('name', $titleWords)->get();
    }

    /**
     * Get active features for a category by ID.
     */
    public function getActiveFeaturesByCategory(int $kategoriId)
    {
        return \App\Models\Ozellik::where('kategori_id', $kategoriId)
            ->where('aktiflik_durumu', 1)
            ->orderBy('display_order', 'asc') // context7-ignore
            ->orderBy('name', 'asc') // context7-ignore
            ->select(['id', 'name', 'slug', 'veri_tipi', 'veri_secenekleri', 'birim', 'zorunlu', 'aciklama'])
            ->get();
    }

    /**
     * Get active yayin tipleri for a category by ID.
     */
    public function getActiveYayinTipleriByCategory(int $kategoriId)
    {
        return \App\Models\YayinTipiSablonu::select(['id', 'yayin_tipi as name'])
            ->where('kategori_id', $kategoriId)
            ->where(function ($query) {
                $query->where('aktiflik_durumu', '=', 1)
                    ->orWhere('aktiflik_durumu', '=', true)
                    ->orWhere('aktiflik_durumu', '=', IlanDurumu::YAYINDA->value);
            })
            ->orderBy('display_order', 'asc') // context7-ignore
            ->get();
    }

    /**
     * Calculate popularity metrics for a category.
     */
    public function getCategoryPerformanceMetrics(int $categoryId): int
    {
        return \App\Models\Ilan::where('kategori_id', $categoryId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
    }

    /**
     * Calculate growth percentage for a category.
     */
    public function getCategoryGrowthMetrics(int $categoryId): float
    {
        $thisMonth = \App\Models\Ilan::where('kategori_id', $categoryId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $lastMonth = \App\Models\Ilan::where('kategori_id', $categoryId)
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->count();

        return $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;
    }

    /**
     * Get SEO score for a category.
     */
    public function getCategorySeoScore(int $categoryId): int
    {
        $category = IlanKategori::find($categoryId);
        if (!$category) return 0;

        $score = 0;
        if ($category->meta_description) $score += 30;
        if ($category->meta_keywords) $score += 20;
        if (strlen($category->name) >= 3 && strlen($category->name) <= 60) $score += 25;
        if ($category->description && strlen($category->description) >= 50) $score += 25;

        return $score;
    }

    /**
     * Get popular categories data.
     */
    public function getPopularCategoriesData()
    {
        return IlanKategori::select('name')
            ->selectRaw('COUNT(ilanlar.id) as ilan_count')
            ->join('ilanlar', function ($join) {
                $join->on('ilan_kategorileri.id', '=', 'ilanlar.ana_kategori_id')
                    ->orOn('ilan_kategorileri.id', '=', 'ilanlar.alt_kategori_id')
                    ->orOn('ilan_kategorileri.id', '=', 'ilanlar.yayin_tipi_id');
            })
            ->where('ilanlar.created_at', '>=', now()->subDays(30))
            ->groupBy('ilan_kategorileri.id', 'name')
            ->orderBy('ilan_count', 'desc') // context7-ignore
            ->limit(10)
            ->get();
    }

    /**
     * Get growth trends data for last 6 months.
     */
    public function getGrowthTrendsData(): array
    {
        $trends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = \App\Models\Ilan::where('created_at', '>=', $month->startOfMonth())
                ->where('created_at', '<=', $month->endOfMonth())
                ->count();

            $trends[] = [
                'month' => $month->format('Y-m'),
                'count' => $count,
            ];
        }
        return $trends;
    }

    /**
     * Get seasonal data.
     */
    public function getSeasonalIlanData(): array
    {
        return [
            'spring' => \App\Models\Ilan::whereMonth('created_at', '>=', 3)
                ->whereMonth('created_at', '<=', 5)
                ->count(),
            'summer' => \App\Models\Ilan::whereMonth('created_at', '>=', 6)
                ->whereMonth('created_at', '<=', 8)
                ->count(),
            'autumn' => \App\Models\Ilan::whereMonth('created_at', '>=', 9)
                ->whereMonth('created_at', '<=', 11)
                ->count(),
            'winter' => \App\Models\Ilan::where(function ($query) {
                $query->whereMonth('created_at', 12)
                    ->orWhereMonth('created_at', 1)
                    ->orWhereMonth('created_at', 2);
            })->count(),
        ];
    }

    /**
     * Get category health metrics.
     */
    public function getCategoryHealthMetrics(): array
    {
        return [
            'healthy_categories' => IlanKategori::whereHas('ilanlar', function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            })->count(),
            'stale_categories' => IlanKategori::whereDoesntHave('ilanlar')->count(),
            'total_categories' => IlanKategori::count(),
        ];
    }

    /**
     * Get category optimization suggestions.
     */
    public function getCategoryOptimizationSuggestions(): array
    {
        return [
            'empty_categories' => IlanKategori::whereDoesntHave('ilanlar')
                ->pluck('name')
                ->toArray(),
            'categories_without_description' => IlanKategori::whereNull('description')
                ->orWhere('description', '')
                ->pluck('name')
                ->toArray(),
            'categories_without_meta' => IlanKategori::whereNull('meta_description')
                ->orWhere('meta_description', '')
                ->pluck('name')
                ->toArray(),
        ];
    }

    /**
     * Get performance insights.
     */
    public function getCategoryPerformanceInsights(): array
    {
        return [
            'most_active_category' => IlanKategori::select('name')
                ->selectRaw('COUNT(ilanlar.id) as count')
                ->join('ilanlar', function ($join) {
                    $join->on('ilan_kategorileri.id', '=', 'ilanlar.ana_kategori_id')
                        ->orOn('ilan_kategorileri.id', '=', 'ilanlar.alt_kategori_id')
                        ->orOn('ilan_kategorileri.id', '=', 'ilanlar.yayin_tipi_id');
                })
                ->groupBy('ilan_kategorileri.id', 'name')
                ->orderBy('count', 'desc') // context7-ignore
                ->first(),
            'least_active_category' => IlanKategori::select('name')
                ->selectRaw('COUNT(ilanlar.id) as count')
                ->leftJoin('ilanlar', function ($join) {
                    $join->on('ilan_kategorileri.id', '=', 'ilanlar.ana_kategori_id')
                        ->orOn('ilan_kategorileri.id', '=', 'ilanlar.alt_kategori_id')
                        ->orOn('ilan_kategorileri.id', '=', 'ilanlar.yayin_tipi_id');
                })
                ->groupBy('ilan_kategorileri.id', 'name')
                ->orderBy('count', 'asc') // context7-ignore
                ->first(),
        ];
    }

    /**
     * Get child categories for a specific parent.
     */
    public function getAltKategoriler(int $parentId)
    {
        return IlanKategori::where('parent_id', $parentId)
            ->where('seviye', 1)
            ->where('aktiflik_durumu', true)
            ->where('seviye', '!=', 2)
            ->select(['id', 'name', 'slug'])
            ->orderBy('name', 'asc') // context7-ignore
            ->get();
    }

    /**
     * Get all active features based on specified category names
     */
    public function getActiveFeaturesByCategoryNames(array $allowedCategoryNames)
    {
        return \App\Models\Feature::where('aktiflik_durumu', true)
            ->whereIn('feature_category_id', function($query) use ($allowedCategoryNames) {
                $query->select('id')->from('feature_categories')->whereIn('name', $allowedCategoryNames);
            })
            ->orderBy('name') // context7-ignore
            ->get();
    }

    /**
     * Get direct feature assignments for a specific assignable type.
     */
    public function getDirectFeatureAssignments(string $class)
    {
        return FeatureAssignment::where('assignable_type', $class)
            ->where('is_required', true)
            ->where('aktiflik_durumu', true)
            ->orderBy('display_order', 'asc') // context7-ignore
            ->get();
    }
}
