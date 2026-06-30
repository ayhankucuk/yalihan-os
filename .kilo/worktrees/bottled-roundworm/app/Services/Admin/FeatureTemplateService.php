<?php

namespace App\Services\Admin;

/**
 * @sab-ignore-catch
 */

use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;

/**
 * Feature Template Service
 *
 * Managed template-based feature synchronization across category trees.
 * Context7 Standard: C7-FEATURE-TEMPLATE-SERVICE-2025-12-29
 */
class FeatureTemplateService
{
    /**
     * Synchronize features from a source category to all siblings in a parent tree.
     *
     * @param int $parentCategoryId The root parent ID (e.g., 23 for Yazlık)
     * @param int $sourceCategoryId The template category ID (e.g., 75 for Villa)
     * @param array $options Additional filters (e.g., specific publication types)
     * @return array Results of the synchronization
     */
    public function syncTreeFromSource(int $parentCategoryId, int $sourceCategoryId, array $options = []): array
    {
        $results = [
            'success' => true,
            'processed' => 0,
            'updated' => 0,
            'created' => 0,
            'errors' => []
        ];

        try {
            // 1. Get source features (Commonly assigned to the category itself)
            $sourceAssignments = FeatureAssignment::where('assignable_id', $sourceCategoryId)
                ->where('assignable_type', IlanKategori::class)
                ->get();

            if ($sourceAssignments->isEmpty()) {
                LogService::warning('FeatureTemplateService: Source category has no features.', ['source_id' => $sourceCategoryId]);
                return array_merge($results, ['success' => false, 'message' => 'Kaynak kategoride özellik bulunamadı.']);
            }

            // 2. Identify target categories (Siblings under the same parent)
            $targetCategories = IlanKategori::where('parent_id', $parentCategoryId)
                ->where('id', '!=', $sourceCategoryId)
                ->where('aktiflik_durumu', true)
                ->get();

            if ($targetCategories->isEmpty()) {
                LogService::info('FeatureTemplateService: No target subcategories found under parent.', ['parent_id' => $parentCategoryId]);
                return array_merge($results, ['message' => 'Eşleşen alt kategori bulunamadı.']);
            }

            DB::beginTransaction();

            foreach ($targetCategories as $category) {
                $results['processed']++;

                foreach ($sourceAssignments as $source) {
                    $assignment = FeatureAssignment::updateOrCreate([
                        'feature_id'      => $source->feature_id,
                        'assignable_id'   => $category->id,
                        'assignable_type' => IlanKategori::class,
                    ], [
                        'is_visible'    => true,
                        'is_required'   => $source->is_required,
                        'display_order' => $source->display_order,
                    ]);

                    if ($assignment->wasRecentlyCreated) {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }
                }
            }

            DB::commit();

            LogService::info('FeatureTemplateService: Tree sync successful.', [
                'parent' => $parentCategoryId,
                'source' => $sourceCategoryId,
                'created' => $results['created'],
                'updated' => $results['updated']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            LogService::error('FeatureTemplateService Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw new \DomainException(
                "Ağaç senkronizasyonu sırasında kritik hata oluştu: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return $results;
    }

    /**
     * Map specific features to a shared publication type (Level 2 categories)
     *
     * @param int $publicationTypeId The target publication type category ID
     * @param array $featureIds List of features to assign
     * @return bool
     */
    public function syncToPublicationType(int $publicationTypeId, array $featureIds): bool
    {
        try {
            foreach ($featureIds as $fId) {
                FeatureAssignment::updateOrCreate([
                    'feature_id'      => $fId,
                    'assignable_id'   => $publicationTypeId,
                    'assignable_type' => IlanKategori::class,
                ], [
                    'is_visible'    => true,
                    'is_required'   => false,
                    'display_order' => 10,
                ]);
            }
            return true;
        } catch (\Exception $e) {
            LogService::error('FeatureTemplateService: Sync to Publication Type failed.', [
                'id' => $publicationTypeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
