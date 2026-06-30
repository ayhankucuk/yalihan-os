<?php

namespace App\Services\Ups;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Logging\LogService;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;

/**
 * UPS Feature Pack Service
 *
 * Context7 Compliance: Feature pack CRUD + apply operations
 * - Idempotent: operations skip duplicates
 * - Logging: NO content_type key
 * - SSOT: writes to FeatureAssignment only
 */
class UpsFeaturePackService
{
    use GuardsAgentWrites;
    /**
     * Apply pack to templates (kategori + yayin tipi)
     *
     * @param FeaturePack $pack
     * @param int $kategoriId
     * @param array $yayinTipiIds
     * @param string $mode 'merge' | 'replace'
     * @return array Applied report
     */
    public function applyPack(
        FeaturePack $pack,
        int $kategoriId,
        array $yayinTipiIds,
        string $mode = 'merge'
    ): array {
        $this->blockAgentWrite('applyPack');

        if (!in_array($mode, ['merge', 'replace'])) {
            throw new \InvalidArgumentException("Invalid mode: {$mode}. Must be 'merge' or 'replace'.");
        }

        $report = [
            'pack_slug' => $pack->slug,
            'kategori_id' => $kategoriId,
            'yayin_tipi_ids' => $yayinTipiIds,
            'mode' => $mode,
            'created' => 0,
            'skipped' => 0,
            'inactive_skipped' => 0,
            'removed' => 0,
            'templates_affected' => 0,
        ];

        $currentKategori = IlanKategori::findOrFail($kategoriId);

        DB::beginTransaction();
        try {
            $packFeatures = $pack->features()->get()->keyBy('id');

            foreach ($yayinTipiIds as $yayinTipiId) {
                // Find template (Global Template)
                $template = YayinTipiSablonu::find($yayinTipiId);

                if (!$template) {
                    continue; // Template doesn't exist, skip
                }

                // Replace mode: remove assignments not in pack
                if ($mode === 'replace') {
                    $removed = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                        ->where('assignable_id', $template->id)
                        ->whereNotIn('feature_id', $packFeatures->keys()->all())
                        ->delete();

                    $report['removed'] += $removed;
                }

                // Add pack features (idempotent)
                foreach ($packFeatures as $featureId => $feature) {
                    $existing = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                        ->where('assignable_id', $template->id)
                        ->where('feature_id', $featureId)
                        ->first();

                    if ($existing) {
                        $report['skipped']++;
                    } else {
                        if (!$this->isFeatureActive($feature)) {
                            $report['inactive_skipped']++;
                            continue;
                        }

                        $inheritance = $this->computeInheritance($feature, $currentKategori);

                        FeatureAssignment::create([
                            'feature_id' => $featureId,
                            'assignable_type' => YayinTipiSablonu::class,
                            'assignable_id' => $template->id,
                            'is_required' => false,
                            'is_visible' => true,
                            'is_inherited' => $inheritance['is_inherited'],
                            'origin_category_name' => $inheritance['origin_category_name'],
                            'display_order' => $feature->pivot->display_order ?? $feature->display_order ?? 0,
                        ]);
                        $report['created']++;
                    }
                }

                $report['templates_affected']++;
            }

            // Log operation
            LogService::info('UPS Feature Pack applied', [
                'pack_id' => $pack->id,
                'pack_slug' => $pack->slug,
                'kategori_id' => $kategoriId,
                'yayin_tipi_ids' => $yayinTipiIds,
                'apply_mode' => $mode,
                'created_count' => $report['created'],
                'skipped_count' => $report['skipped'],
                'inactive_skipped' => $report['inactive_skipped'],
                'removed_count' => $report['removed'],
                'templates_affected' => $report['templates_affected'],
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return $report;
        } catch (\Exception $e) {
            DB::rollBack();

            LogService::error('UPS Feature Pack apply failed', [
                'pack_id' => $pack->id,
                'kategori_id' => $kategoriId,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }

    /**
     * Add feature to pack (idempotent)
     */
    public function addFeatureToPack(FeaturePack $pack, Feature $feature, int $displayOrder = 0): bool
    {
        $added = $pack->addFeature($feature, $displayOrder);

        LogService::info('UPS Feature added to pack', [
            'pack_id' => $pack->id,
            'feature_id' => $feature->id,
            'display_order' => $displayOrder,
            'created' => $added,
            'user_id' => auth()->id(),
        ]);

        return $added;
    }

    /**
     * Remove feature from pack
     */
    public function removeFeatureFromPack(FeaturePack $pack, Feature $feature): bool
    {
        $removed = $pack->removeFeature($feature);

        LogService::info('UPS Feature removed from pack', [
            'pack_id' => $pack->id,
            'feature_id' => $feature->id,
            'removed' => $removed,
            'user_id' => auth()->id(),
        ]);

        return $removed;
    }

    /**
     * Create new pack
     */
    public function createPack(array $data): FeaturePack
    {
        $pack = FeaturePack::create($data);

        LogService::info('UPS Feature Pack created', [
            'pack_id' => $pack->id,
            'pack_slug' => $pack->slug,
            'user_id' => auth()->id(),
        ]);

        return $pack;
    }

    /**
     * Update pack
     */
    public function updatePack(FeaturePack $pack, array $data): FeaturePack
    {
        $pack->update($data);

        LogService::info('UPS Feature Pack updated', [
            'pack_id' => $pack->id,
            'changes' => array_keys($data),
            'user_id' => auth()->id(),
        ]);

        return $pack->fresh();
    }

    /**
     * Delete pack
     */
    public function deletePack(FeaturePack $pack): bool
    {
        $packId = $pack->id;
        $packSlug = $pack->slug;

        $deleted = $pack->delete();

        if ($deleted) {
            LogService::info('UPS Feature Pack deleted', [
                'pack_id' => $packId,
                'pack_slug' => $packSlug,
                'user_id' => auth()->id(),
            ]);
        }

        return (bool) $deleted;
    }

    /**
     * Toggle pack aktiflik durumu
     */
    public function toggleAktiflikDurumu(FeaturePack $pack): FeaturePack
    {
        $pack->aktiflik_durumu = !$pack->aktiflik_durumu;
        $pack->save();

        LogService::info('UPS Feature Pack aktiflik_durumu toggled', [
            'pack_id' => $pack->id,
            'new_aktiflik_durumu' => $pack->aktiflik_durumu,
            'user_id' => auth()->id(),
        ]);

        return $pack;
    }

    /**
     * Aktiflik kontrolü: yeni isim (aktiflik_durumu) + geriye dönük uyum
     */
    private function isFeatureActive(Feature $feature): bool
    {
        if (isset($feature->aktiflik_durumu)) {
            return (bool) $feature->aktiflik_durumu;
        }

        if (isset($feature->aktif_mi)) {
            return (bool) $feature->aktif_mi;
        }

        if (isset($feature->aktiflik_durumu)) {
            return (bool) $feature->aktiflik_durumu;
        }

        if (isset($feature->aktif_mi)) {
            return (bool) $feature->aktif_mi;
        }

        return false;

        return false;
    }

    /**
     * Kalıtım bilgisini hesapla
     */
    private function computeInheritance(Feature $feature, IlanKategori $currentKategori): array
    {
        $originCategory = null;
        $originName = null;
        $isInherited = false;

        if ($feature->feature_category_id) {
            $originCategory = IlanKategori::find($feature->feature_category_id);
            $originName = $originCategory?->name;
            $isInherited = $feature->feature_category_id !== $currentKategori->id;
        }

        return [
            'is_inherited' => $isInherited,
            'origin_category_name' => $originName,
        ];
    }
}
