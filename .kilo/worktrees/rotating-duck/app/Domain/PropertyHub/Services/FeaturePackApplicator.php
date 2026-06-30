<?php

namespace App\Domain\PropertyHub\Services;

use App\Models\FeatureAssignment;
use App\Models\FeaturePack;
use App\Models\AltKategoriYayinTipi;

/**
 * FeaturePackApplicator — Domain Service
 *
 * [SAB ENFORCEMENT]: Aggregate Overgrowth Prevention
 * Pack -> Assignment algoritmasini kapsulleyen domain service.
 * Aggregate Root bu servisi kullanir, ama logic burada yasar.
 *
 * Sorumluluklar:
 * - Feature Pack'i pivot'a uygulama (merge/replace)
 * - Diff-based feature sync (add/remove/re-sequence)
 *
 * Polymorphic: assignableType parametresi ile farkli scope'larda calisir.
 * Default scope: AltKategoriYayinTipi (pivot-level assignments).
 */
class FeaturePackApplicator
{
    /**
     * Apply a Feature Pack to a pivot
     *
     * @param string $mode 'merge' (ekle, var olanlari atla) veya 'replace' (sil ve yeniden ata)
     * @param string|null $assignableType Polymorphic type (default: AltKategoriYayinTipi)
     * @return array{added_count: int, skipped_count: int}
     */
    public function apply(int $pivotId, FeaturePack $pack, string $mode = 'merge', ?string $assignableType = null): array
    {
        $type = $assignableType ?? AltKategoriYayinTipi::class;
        $addedCount = 0;
        $skippedCount = 0;

        // Replace mode: once temizle, sonra ata
        if ($mode === 'replace') {
            FeatureAssignment::where('assignable_type', $type)
                ->where('assignable_id', $pivotId)
                ->delete();
        }

        // Batch pre-fetch: mevcut atamaları tek sorguda al (N+1 önleme)
        $existingFeatureIds = FeatureAssignment::where('assignable_type', $type)
            ->where('assignable_id', $pivotId)
            ->whereIn('feature_id', $pack->features->pluck('id'))
            ->pluck('feature_id')
            ->toArray();

        foreach ($pack->features as $feature) {
            if (!$feature->aktiflik_durumu) {
                $skippedCount++;
                continue;
            }

            if (in_array($feature->id, $existingFeatureIds)) {
                $skippedCount++;
                continue;
            }

            FeatureAssignment::create([
                'assignable_type' => $type,
                'assignable_id' => $pivotId,
                'feature_id' => $feature->id,
                'source_type' => 'pack',
                'aktiflik_durumu' => 1,
            ]);

            $addedCount++;
        }

        return [
            'added_count' => $addedCount,
            'skipped_count' => $skippedCount,
        ];
    }

    /**
     * Sync features for a pivot (diff-based: add missing, remove extra, re-sequence)
     *
     * @param string|null $assignableType Polymorphic type (default: AltKategoriYayinTipi)
     * @return array{added: int, removed: int}
     */
    public function sync(int $pivotId, array $featureIds, ?string $assignableType = null): array
    {
        $type = $assignableType ?? AltKategoriYayinTipi::class;
        $addedCount = 0;
        $removedCount = 0;

        // Mevcut atamalari al
        $existingAssignments = FeatureAssignment::where('assignable_type', $type)
            ->where('assignable_id', $pivotId)
            ->get();

        $existingFeatureIds = $existingAssignments->pluck('feature_id')->toArray();

        // Diff hesapla
        $toAdd = array_diff($featureIds, $existingFeatureIds);
        $toRemove = array_diff($existingFeatureIds, $featureIds);

        // Kaldirilacaklari sil
        if (!empty($toRemove)) {
            $removedCount = FeatureAssignment::where('assignable_type', $type)
                ->where('assignable_id', $pivotId)
                ->whereIn('feature_id', $toRemove)
                ->delete();
        }

        // Yenilerini ekle
        foreach ($toAdd as $featureId) {
            $sortIndex = array_search($featureId, $featureIds);

            FeatureAssignment::create([
                'feature_id' => $featureId,
                'assignable_type' => $type,
                'assignable_id' => $pivotId,
                'source_type' => 'manual_pivot',
                'aktiflik_durumu' => 1,
                'display_order' => $sortIndex !== false ? $sortIndex : 999,
            ]);

            $addedCount++;
        }

        // Siralamayı guncelle (tum feature'lar icin)
        foreach ($featureIds as $index => $featureId) {
            FeatureAssignment::where('assignable_type', $type)
                ->where('assignable_id', $pivotId)
                ->where('feature_id', $featureId)
                ->update(['display_order' => $index]);
        }

        return [
            'added' => $addedCount,
            'removed' => $removedCount,
        ];
    }

    /**
     * Assign individual features to a pivot
     *
     * @param string|null $assignableType Polymorphic type (default: AltKategoriYayinTipi)
     * @param array $metadata Ekstra atama verileri (is_required, display_order vb.)
     * @return FeatureAssignment[]
     */
    public function assignIndividual(
        int $pivotId,
        array $featureIds,
        string $sourceType = 'manual',
        ?string $assignableType = null,
        array $metadata = []
    ): array
    {
        $type = $assignableType ?? AltKategoriYayinTipi::class;
        $assigned = [];

        // N+1 Optimization: Get existing assignments in one query
        $existingFeatureIds = FeatureAssignment::where('assignable_type', $type)
            ->where('assignable_id', $pivotId)
            ->whereIn('feature_id', $featureIds)
            ->pluck('feature_id')
            ->toArray();

        foreach ($featureIds as $featureId) {
            if (in_array($featureId, $existingFeatureIds)) continue;

            $data = array_merge([
                'assignable_type' => $type,
                'assignable_id' => $pivotId,
                'feature_id' => $featureId,
                'source_type' => $sourceType,
                'aktiflik_durumu' => 1,
            ], $metadata);

            $assignment = FeatureAssignment::create($data);

            $assigned[] = $assignment;
        }

        return $assigned;
    }

    /**
     * Remove features from a pivot
     *
     * @param string|null $assignableType Polymorphic type (default: AltKategoriYayinTipi)
     */
    public function unassign(int $pivotId, array $featureIds, ?string $assignableType = null): int
    {
        $type = $assignableType ?? AltKategoriYayinTipi::class;

        return FeatureAssignment::where('assignable_type', $type)
            ->where('assignable_id', $pivotId)
            ->whereIn('feature_id', $featureIds)
            ->delete();
    }

    /**
     * Reorder features for a pivot
     *
     * @param array $featureOrders [{feature_id: int, display_order: int}, ...]
     * @param string|null $assignableType Polymorphic type (default: AltKategoriYayinTipi)
     * @return int Updated count
     */
    public function reorder(int $pivotId, array $featureOrders, ?string $assignableType = null): int
    {
        $type = $assignableType ?? AltKategoriYayinTipi::class;
        $updated = 0;

        foreach ($featureOrders as $item) {
            $count = FeatureAssignment::where('assignable_type', $type)
                ->where('assignable_id', $pivotId)
                ->where('feature_id', $item['feature_id'])
                ->update(['display_order' => $item['display_order']]);

            $updated += $count;
        }

        return $updated;
    }

    /**
     * Clear all feature assignments for a given assignable
     *
     * @param string|null $assignableType Polymorphic type (default: AltKategoriYayinTipi)
     * @return int Deleted count
     */
    public function clearAll(int $assignableId, ?string $assignableType = null): int
    {
        $type = $assignableType ?? AltKategoriYayinTipi::class;

        return FeatureAssignment::where('assignable_type', $type)
            ->where('assignable_id', $assignableId)
            ->delete();
    }
}
