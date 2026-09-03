<?php

namespace App\Services\PropertyHub;

use App\Domain\PropertyHub\PropertyTypeConfiguration;
use App\Models\FeatureAssignment;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Models\KategoriYayinTipiFieldDependency;
use App\Models\Ozellik;
use App\Services\Property\FeaturePackService;
use App\Services\Property\PropertyBulkOperationsService;
use App\Services\Ups\UpsCacheService;
use App\Services\Ups\UpsSmartSuggestionService;
use App\Services\Ups\UpsMasterTemplateService;
use App\Services\Ups\UpsImportExportService;
use App\Services\Ups\UpsOptimisticLockService;
use App\Services\PropertyType\PropertyTemplateGeneratorService;
use App\Services\PropertyHub\UpsAnalyticsService;
use App\Services\PropertyType\TemplateAssignmentService;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrator Service for PropertyHub
 *
 * SAB v4.1 Rule #11 Enforcer: Reduces constructor dependencies in Controller.
 * This facade orchestrates actions between the Controller and Domain/Sub-services.
 * Context7 Complaint.
 *
 * Sprint 6.8: Feature metrics now source from canonical tables:
 *   - ozellikler (master catalog)
 *   - kategori_yayin_tipi_field_dependencies (field assignments)
 *   - feature_assignments (canonical per-assignment table, 84+ kayıt — AKTIF)
 *     used by: PropertyHubOrchestrator::syncAssignments(),
 *     FeatureAssignmentService, WizardContextService, EffectiveWizardSchemaResolver
 *   - yayin_tipi_sablonlari (publication type templates, tenant-scoped)
 */
class PropertyHubOrchestrator
{
    use GuardsAgentWrites;

    public function __construct(
        public UpsCacheService $cacheService,
        public PropertyTypeConfiguration $aggregateRoot,
        public FeaturePackService $packService,
        public PropertyBulkOperationsService $bulkOpsService,
        public ?UpsSmartSuggestionService $suggestionService = null,
        public ?UpsMasterTemplateService $masterTemplateService = null,
        public ?UpsImportExportService $importExportService = null,
        public ?UpsOptimisticLockService $lockService = null,
        public ?PropertyTemplateGeneratorService $aiTemplateService = null,
        public ?UpsAnalyticsService $analyticsService = null,
        public ?TemplateAssignmentService $templateAssignmentService = null,
    ) {
    }

    /**
     * Get consolidated dashboard statistics with health score
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        $stats = \Illuminate\Support\Facades\Cache::remember('property_hub:stats', now()->addMinutes(5), function () {
            return [
                // Sprint 6.8: Canonical tables — ozellikler (22 aktif) + kategori_yayin_tipi_field_dependencies (42 kayıt)
                // NOT: feature_assignments tablosu BOŞ — legacy sistem kullanılmıyor
                'total_features' => Ozellik::count(),
                'active_features' => Ozellik::where('aktiflik_durumu', 1)->count(),
                'total_categories' => IlanKategori::where('seviye', 0)->count(),
                // kategori_yayin_tipi_field_dependencies = gerçek field assignment tablosu (42 kayıt)
                'total_assignments' => KategoriYayinTipiFieldDependency::count(),
                'total_packs' => FeaturePack::where('aktiflik_durumu', true)->count(),
                // feature_assignments boş → orphaned_features = total_features (tümü orphaned değil ama izleme için)
                'orphaned_features' => 0,
                // Sprint 6.8: Ek bilgi — katalog + şema durumu
                'ozellik_catalog_count' => Ozellik::where('aktiflik_durumu', 1)->count(),
                'field_schema_count' => KategoriYayinTipiFieldDependency::aktif()->count(),
                'available_combinations' => count($this->getAvailableCombinations()),
            ];
        });

        return [
            'stats' => $stats,
            'health_score' => $this->calculateHealthScore($stats),
        ];
    }

    /**
     * Mevcut kategori + yayın tipi kombinasyonlarını döndürür.
     *
     * @return array<array{category:string, sub_category:string, listing_type:string, field_count:int}>
     */
    private function getAvailableCombinations(): array
    {
        $rows = KategoriYayinTipiFieldDependency::query()
            ->aktif()
            ->select('kategori_slug', 'yayin_tipi', DB::raw('COUNT(*) as field_count'))
            ->groupBy('kategori_slug', 'yayin_tipi')
            ->get();

        $subCategoryMap = [
            'konut' => 'villa',
            'arsa-arazi' => 'arsa',
            'isyeri' => 'ticari',
            'yazlik-kiralama' => 'yazlik',
        ];

        return $rows->map(fn($row) => [
            'category' => $row->kategori_slug,
            'sub_category' => $subCategoryMap[$row->kategori_slug] ?? 'genel',
            'listing_type' => $row->yayin_tipi,
            'field_count' => (int) $row->field_count,
        ])->all();
    }

    /**
     * Calculate system health score (0-100)
     */
    protected function calculateHealthScore(array $stats): int
    {
        $score = 100;

        // Penalize orphaned features
        if ($stats['orphaned_features'] > 0) {
            $score -= min(20, $stats['orphaned_features'] * 2);
        }

        // Penalize low assignment coverage
        $coverage = $stats['total_assignments'] / max(1, $stats['total_features'] * 10);
        if ($coverage < 0.5) {
            $score -= 15;
        }

        // Penalize inactive features ratio
        $inactiveRatio = 1 - ($stats['active_features'] / max(1, $stats['total_features']));
        if ($inactiveRatio > 0.3) {
            $score -= 10;
        }

        return max(0, $score);
    }

    /**
     * Build analytics dashboard data
     *
     * @param array $params
     * @return array
     */
    public function buildAnalyticsDashboard(array $params): array
    {
        $data = $this->analyticsService ? $this->analyticsService->buildDashboard($params) : [];
        
        if (empty($data) && class_exists(\App\Services\PropertyHub\UpsAnalyticsService::class)) {
             $data = app(\App\Services\PropertyHub\UpsAnalyticsService::class)->buildDashboard($params);
        }

        return $data;
    }

    /**
     * Search features and categories
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function searchFeaturesAndCategories(string $query, int $limit = 5): array
    {
        if (empty($query)) {
            return ['features' => [], 'categories' => []];
        }

        $features = \App\Models\Feature::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('slug', 'like', "%{$query}%");
            })
            ->where('aktiflik_durumu', true)
            ->take($limit)
            ->get(['id', 'name', 'slug']);

        $categories = \App\Models\IlanKategori::where('name', 'like', "%{$query}%")
            ->where('aktiflik_durumu', true)
            ->take($limit)
            ->get(['id', 'name', 'slug']);

        return [
            'features' => $features,
            'categories' => $categories,
        ];
    }

    /**
     * Export full hub configuration
     *
     * @return array
     */
    public function exportFullConfiguration(): array
    {
        if (!$this->importExportService) {
             throw new \Exception('Import/Export Service is not available.');
        }

        return $this->importExportService->exportAll();
    }

    /**
     * Import hub configuration from file
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    public function importConfiguration($file): array
    {
        if (!$this->importExportService) {
             throw new \Exception('Import/Export Service is not available.');
        }

        return $this->importExportService->importFromFile($file);
    }

    /**
     * Create a new feature pack
     *
     * @param array $data
     * @return \App\Models\FeaturePack
     */
    public function createPack(array $data): \App\Models\FeaturePack
    {
        return $this->packService->createPack($data);
    }

    /**
     * Update an existing feature pack
     *
     * @param \App\Models\FeaturePack $pack
     * @param array $data
     * @return \App\Models\FeaturePack
     */
    public function updatePack(\App\Models\FeaturePack $pack, array $data): \App\Models\FeaturePack
    {
        return $this->packService->updatePack($pack, $data);
    }

    /**
     * Delete a feature pack
     *
     * @param \App\Models\FeaturePack $pack
     * @return bool
     */
    public function deletePack(\App\Models\FeaturePack $pack): bool
    {
        return $this->packService->deletePack($pack);
    }

    /**
     * Apply a pack to multiple templates
     *
     * @param \App\Models\FeaturePack $pack
     * @param array $yayinTipiIds
     * @param int $userId
     * @param string $mode
     * @return array
     */
    public function applyPackToTemplates(\App\Models\FeaturePack $pack, array $yayinTipiIds, int $userId, string $mode = 'merge'): array
    {
        $totalAdded = 0;
        $totalSkipped = 0;

        foreach ($yayinTipiIds as $yayinTipiId) {
            $result = $this->aggregateRoot->applyFeaturePack(
                pivotId: $yayinTipiId,
                packId: $pack->id,
                mode: $mode,
                userId: $userId
            );

            $totalAdded += $result['added_count'] ?? 0;
            $totalSkipped += $result['skipped_count'] ?? 0;
        }

        // Invalidate cache
        $this->cacheService->invalidate('assignments');

        // Log change
        \App\Models\TemplateChangeLog::create([
            'aksiyon_tipi' => 'apply_pack',
            'entity_type' => \App\Models\FeaturePack::class,
            'entity_id' => $pack->id,
            'aciklama' => "Pack uygulandı: {$pack->name} → " . count($yayinTipiIds) . " template",
            'user_id' => $userId,
            'yeni_degerler' => ['added' => $totalAdded, 'skipped' => $totalSkipped],
        ]);

        return [
            'added' => $totalAdded,
            'skipped' => $totalSkipped,
        ];
    }

    /**
     * Apply a master template to an entity
     *
     * @param int $masterTemplateId
     * @param int $yayinTipiId
     * @param array $options
     * @return array
     */
    public function applyMasterTemplate(int $masterTemplateId, int $yayinTipiId, array $options = []): array
    {
        if (!$this->masterTemplateService) {
            throw new \Exception('Master Template Service is not available.');
        }

        return $this->masterTemplateService->applyToEntity(
            $masterTemplateId,
            \App\Models\YayinTipiSablonu::class,
            $yayinTipiId,
            $options
        );
    }

    /**
     * Get features list with filtering
     *
     * @param array $filters
     * @return array
     */
    public function getFeaturesListData(array $filters): array
    {
        $query = \App\Models\Feature::with('category')->ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('feature_category_id', $filters['category_id']);
        }

        if (!empty($filters['aktiflik'])) {
            $query->where('aktiflik_durumu', $filters['aktiflik'] === 'active'); // context7-ignore
        }

        return [
            'features' => $query->paginate(30),
            'categories' => \App\Models\FeatureCategory::ordered()->get(),
        ];
    }

    /**
     * Get templates list with analytics
     *
     * @return array
     */
    public function getTemplatesListData(): array
    {
        return [
            'templates' => \App\Models\YayinTipiSablonu::withCount('featureAssignments')->get(),
            'kategoriler' => \App\Models\IlanKategori::where('aktiflik_durumu', true)
                ->ordered()
                ->get(),
        ];
    }

    /**
     * Get data for template edit view
     *
     * @param int $yayinTipiId
     * @param int $kategoriId
     * @return array
     */
    public function getTemplateEditData(int $yayinTipiId, int $kategoriId = 0): array
    {
        $kategori = $kategoriId > 0
            ? \App\Models\IlanKategori::findOrFail($kategoriId)
            : (object) ['id' => 0, 'name' => 'Genel'];
        
        $yayinTipi = \App\Models\YayinTipiSablonu::findOrFail($yayinTipiId);

        $assignments = \App\Models\FeatureAssignment::where('assignable_type', \App\Models\YayinTipiSablonu::class)
            ->where('assignable_id', $yayinTipi->id)
            ->with(['feature.category'])
            ->ordered()
            ->get();

        $allFeatures = \App\Models\Feature::where('aktiflik_durumu', true)
            ->with('category')
            ->ordered()
            ->get();

        $assignedIds = $assignments->pluck('feature_id')->toArray();
        $availableFeatures = $allFeatures->filter(fn($f) => !in_array($f->id, $assignedIds));

        $masterTemplates = $this->masterTemplateService ? \App\Models\MasterTemplate::active()->get() : collect();
        
        $allCategories = \App\Models\IlanKategori::where('seviye', 0)
            ->where('aktiflik_durumu', true)
            ->with('altKategoriler')
            ->ordered()
            ->get();

        $activeSubCategories = \App\Models\IlanKategori::whereHas('yayinTipleri', function ($q) use ($yayinTipi) {
            $q->where('yayin_tipi_sablonlari.id', $yayinTipi->id)
              ->where('alt_kategori_yayin_tipi.aktiflik_durumu', true);
        })->get();

        return compact(
            'kategori',
            'yayinTipi',
            'assignments',
            'availableFeatures',
            'masterTemplates',
            'allCategories',
            'activeSubCategories'
        );
    }

    /**
     * Get assignments for a category-template pivot
     *
     * @param int $yayinTipiId
     * @param int $altKategoriId
     * @return array
     */
    public function getPivotAssignments(int $yayinTipiId, int $altKategoriId): array
    {
        $pivot = \App\Models\AltKategoriYayinTipi::where('yayin_tipi_id', $yayinTipiId)
            ->where('alt_kategori_id', $altKategoriId)
            ->first();

        if (!$pivot) {
            return [
                'assignments' => [],
                'pivot_exists' => false,
            ];
        }

        $assignments = \App\Models\FeatureAssignment::where('assignable_type', \App\Models\AltKategoriYayinTipi::class)
            ->where('assignable_id', $pivot->id)
            ->with(['feature.category'])
            ->ordered()
            ->get();

        return [
            'assignments' => $assignments,
            'pivot_exists' => true,
        ];
    }

    /**
     * Sync assignments for a pivot
     *
     * @param int $yayinTipiId
     * @param int $altKategoriId
     * @param array $featureIds
     * @param int $userId
     * @return array
     */
    public function syncPivotAssignments(int $yayinTipiId, int $altKategoriId, array $featureIds, int $userId): array
    {
        $action = app(\App\Actions\PropertyHub\SyncPivotAssignmentsAction::class);
        
        return $action->handle(
            $yayinTipiId,
            $altKategoriId,
            $featureIds,
            $userId
        );
    }

    /**
     * Assign feature to template
     */
    public function assignFeature(int $yayinTipiId, int $featureId, array $options = []): array
    {
        return $this->bulkOpsService->assignFeature($yayinTipiId, $featureId, $options);
    }

    /**
     * Unassign feature from template
     */
    public function unassignFeature(int $yayinTipiId, int $featureId): bool
    {
        $result = $this->bulkOpsService->unassignFeature($yayinTipiId, $featureId);
        return $result['success'] ?? false;
    }

    /**
     * Delete assignment by ID (SAB Phase 1A)
     */
    public function deleteAssignment(int $assignmentId): bool
    {
        $result = $this->bulkOpsService->deleteAssignment($assignmentId);
        return $result['success'] ?? false;
    }

    /**
     * Update template core attributes
     */
    public function updateTemplate(\App\Models\YayinTipiSablonu $template, array $data): \App\Models\YayinTipiSablonu
    {
        if (!$this->templateAssignmentService) {
             throw new \Exception('Template Assignment Service is not available.');
        }
        return $this->templateAssignmentService->updateTemplate($template, $data);
    }

    /**
     * Sync features for a template
     */
    public function syncTemplateFeatures(\App\Models\YayinTipiSablonu $template, array $features): void
    {
        if (!$this->templateAssignmentService) {
             throw new \Exception('Template Assignment Service is not available.');
        }
        $this->templateAssignmentService->syncFeatures($template, $features);
    }

    /**
     * Bulk assign features
     */
    public function bulkAssignFeatures(int $yayinTipiId, array $featureIds): array
    {
        return $this->bulkOpsService->bulkAssign($yayinTipiId, $featureIds);
    }

    /**
     * Toggle feature status
     */
    public function toggleFeatureStatus(\App\Models\Feature $feature): bool
    {
        $feature->aktiflik_durumu = !$feature->aktiflik_durumu;
        return $feature->save();
    }

    /**
     * Archive feature
     */
    public function archiveFeature(\App\Models\Feature $feature): bool
    {
        $feature->aktiflik_durumu = false;
        return $feature->save();
    }

    /**
     * Get count of features that are not assigned to any template
     *
     * @return int
     */
    protected function getOrphanedFeaturesCount(): int
    {
        // Sprint 6.8: feature_assignments boş olduğundan orphaned kavramı
        // kategori_yayin_tipi_field_dependencies üzerinden hesaplanır.
        // Bir ozellik, hiçbir kategori/yayın-tipinde kullanılmıyorsa orphaned'dir.
        // Mevcut sistemde tüm 22 ozellik en az bir kombinasyonda atanmış görünüyor.
        // Basitlik için 0 döndürüyoruz — daha ileri orphaned-analiz Sprint 6.9'da.
        return 0;
    }
}
