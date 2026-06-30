<?php

namespace App\Domain\PropertyHub;

use App\Models\AltKategoriYayinTipi;
use App\Models\FeaturePack;
use App\Models\YayinTipiSablonu;
use App\Domain\PropertyHub\Services\FeaturePackApplicator;
use App\Domain\PropertyHub\Services\TemplateFeatureImporter;
use App\Domain\PropertyHub\Services\TemplateSealingPolicy;
use App\Domain\PropertyHub\Events\TemplateSealedEvent;
use App\Domain\PropertyHub\Events\FeatureAssignedEvent;
use App\Exceptions\PropertyHub\TemplateResolutionException;
use Illuminate\Support\Facades\DB;

/**
 * PropertyTypeConfiguration — Aggregate Root
 *
 * [SAB ENFORCEMENT]: Domain Consolidation + Aggregate Overgrowth Prevention
 * Bu sinif, bir mulk tipinin yapilandirilmasi ile ilgili
 * TUM islemlerin tek giris noktasidir (Single Entry Point).
 *
 * Sorumluluklar (ince katman):
 * - Invariant kontrolu
 * - Transaction siniri
 * - Domain Event uretimi
 *
 * Tum feature assignment method'lari polymorphic:
 * - Default scope: AltKategoriYayinTipi (pivot-level)
 * - Template scope: YayinTipiSablonu (master template scope icin assignableType pass edin)
 *
 * Agir algoritmalar Domain Service'lere delege edilir:
 * - TemplateSealingPolicy: canonical json + hash/seal policy
 * - FeaturePackApplicator: pack -> assignment algoritmasi (polymorphic)
 * - TemplateFeatureImporter: bulk template import + clear
 *
 * @see \App\Domain\PropertyHub\Services\TemplateSealingPolicy
 * @see \App\Domain\PropertyHub\Services\FeaturePackApplicator
 * @see \App\Domain\PropertyHub\Services\TemplateFeatureImporter
 */
class PropertyTypeConfiguration
{
    public function __construct(
        private TemplateSealingPolicy $sealingPolicy,
        private FeaturePackApplicator $applicator,
        private TemplateFeatureImporter $importer,
    ) {}

    /**
     * Resolve template for a category + publication type combination
     *
     * @throws TemplateResolutionException
     */
    public function resolveTemplate(int $kategoriId, ?int $yayinTipiId = null): ?AltKategoriYayinTipi
    {
        $query = AltKategoriYayinTipi::where('alt_kategori_id', $kategoriId)
            ->active();

        if ($yayinTipiId) {
            $query->where('yayin_tipi_id', $yayinTipiId);
        }

        $pivot = $query->orderBy('id')->first(); // P1-E: deterministic tie-break

        if (!$pivot) {
            throw new TemplateResolutionException(
                "Kategori ({$kategoriId}) ve yayin tipi ({$yayinTipiId}) icin aktif pivot bulunamadi."
            );
        }

        return $pivot;
    }

    /**
     * Seal (store) a new UPS Template version
     *
     * @return array{template: \App\Models\UpsTemplate, is_duplicate: bool}
     * @throws \Exception
     */
    public function sealTemplate(int $junctionId, array $upsJson, bool $shouldSeal = true, ?int $userId = null): array
    {
        DB::beginTransaction();

        try {
            $result = $this->sealingPolicy->seal($junctionId, $upsJson, $shouldSeal, $userId);

            DB::commit();

            if (!$result['is_duplicate']) {
                $junction = $result['template']->junction;
                event(new TemplateSealedEvent($result['template'], $junction, $userId));
            }

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign features (polymorphic: pivot veya template scope)
     *
     * @param string|null $assignableType Scope tipi (default: AltKategoriYayinTipi)
     * @param array $metadata Ekstra atama verileri (is_required, is_visible vb.)
     */
    public function assignFeatures(
        int $pivotId,
        array $featureIds,
        string $sourceType = 'manual',
        ?int $userId = null,
        ?string $assignableType = null,
        array $metadata = []
    ): array
    {
        DB::beginTransaction();
        try {
            $assigned = $this->applicator->assignIndividual(
                $pivotId,
                $featureIds,
                $sourceType,
                $assignableType,
                $metadata
            );

            DB::commit();

            if (!empty($assigned)) {
                event(new FeatureAssignedEvent($pivotId, $assigned, $userId));
            }

            return $assigned;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove (unassign) features (polymorphic: pivot veya template scope)
     *
     * @param string|null $assignableType Scope tipi (default: AltKategoriYayinTipi)
     */
    public function unassignFeatures(int $pivotId, array $featureIds, ?string $assignableType = null): int
    {
        $deleted = $this->applicator->unassign($pivotId, $featureIds, $assignableType);

        if ($deleted > 0) {
            event(new FeatureAssignedEvent($pivotId, [], null));
        }

        return $deleted;
    }

    /**
     * Apply a Feature Pack (polymorphic: pivot veya template scope)
     *
     * @param string $mode 'merge' veya 'replace'
     * @param string|null $assignableType Scope tipi (default: AltKategoriYayinTipi)
     * @return array{added_count: int, skipped_count: int}
     */
    public function applyFeaturePack(
        int $pivotId,
        int $packId,
        string $mode = 'merge',
        ?int $userId = null,
        ?string $assignableType = null
    ): array
    {
        $pack = FeaturePack::with('features')->findOrFail($packId);

        DB::beginTransaction();
        try {
            $result = $this->applicator->apply($pivotId, $pack, $mode, $assignableType);

            DB::commit();

            if ($result['added_count'] > 0) {
                event(new FeatureAssignedEvent($pivotId, [], $userId));
            }

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sync features for a pivot (diff-based)
     *
     * @param string|null $assignableType Scope tipi (default: AltKategoriYayinTipi)
     * @return array{added: int, removed: int}
     */
    public function syncFeatures(
        int $pivotId,
        array $featureIds,
        ?int $userId = null,
        ?string $assignableType = null
    ): array
    {
        DB::beginTransaction();
        try {
            $result = $this->applicator->sync($pivotId, $featureIds, $assignableType);

            DB::commit();

            if ($result['added'] > 0 || $result['removed'] > 0) {
                event(new FeatureAssignedEvent($pivotId, [], $userId));
            }

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update assignment metadata (e.g. is_required, is_visible)
     *
     * @param int $assignmentId
     * @param array $metadata
     * @return \App\Models\FeatureAssignment
     */
    public function updateAssignmentMetadata(int $assignmentId, array $metadata, ?int $userId = null)
    {
        DB::beginTransaction();
        try {
            $assignment = \App\Models\FeatureAssignment::findOrFail($assignmentId);
            $assignment->update($metadata);

            DB::commit();

            // Sadece cache silebilmek icin pivotId ve empty features ile event tetiklenir
            if ($assignment->assignable_type === 'App\\Models\\AltKategoriYayinTipi' ||
                $assignment->assignable_type === 'App\\Models\\YayinTipiSablonu') {
                event(new FeatureAssignedEvent($assignment->assignable_id, [], $userId));
            }

            return $assignment;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reorder features (polymorphic: pivot veya template scope)
     *
     * @param array $featureOrders [{feature_id: int, display_order: int}, ...]
     * @param string|null $assignableType Scope tipi (default: AltKategoriYayinTipi)
     * @return int Updated count
     */
    public function reorderFeatures(
        int $pivotId,
        array $featureOrders,
        ?int $userId = null,
        ?string $assignableType = null
    ): int
    {
        DB::beginTransaction();
        try {
            $updated = $this->applicator->reorder($pivotId, $featureOrders, $assignableType);

            DB::commit();

            if ($updated > 0) {
                event(new FeatureAssignedEvent($pivotId, [], $userId));
            }

            return $updated;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─── TEMPLATE-SPECIFIC OPERATIONS (unique logic, no pivot mirror) ───

    /**
     * Import features to a master template (bulk assign with tx boundary)
     *
     * @param array $featureDataList [{feature_id: int, source_type: string, display_order: int}, ...]
     * @return array{added: int, skipped: int, errors: array}
     */
    public function importTemplateFeatures(int $templateId, array $featureDataList, ?int $userId = null): array
    {
        DB::beginTransaction();
        try {
            $results = $this->importer->import($templateId, $featureDataList);

            DB::commit();

            if ($results['added'] > 0) {
                event(new FeatureAssignedEvent($templateId, [], $userId));
            }

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Clear all features from a master template (YayinTipiSablonu scope)
     *
     * @return int Deleted count
     */
    public function clearTemplateFeatures(int $templateId): int
    {
        return $this->importer->clear($templateId);
    }
}
