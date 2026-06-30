<?php

namespace App\Services\Property;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use App\Models\TemplateChangeLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\GuardsAgentWrites;

/**
 * PropertyBulkOperationsService
 *
 * Context7 Compliance:
 * - Uses aktiflik_durumu (yasakli kelime engellendi)
 * - Uses display_order (NOT sort_order)
 * - Guards against forbidden fields
 *
 * Handles feature assignment/unassignment operations
 */
class PropertyBulkOperationsService
{
    use GuardsAgentWrites;
    /**
     * Context7 Forbidden Fields — reads from SSOT config
     */
    private static ?array $forbiddenFieldsCache = null;

    private static function getForbiddenFields(): array
    {
        if (self::$forbiddenFieldsCache === null) {
            self::$forbiddenFieldsCache = config('context7.forbidden_fields.runtime_guard', []);
        }
        return self::$forbiddenFieldsCache;
    }

    /**
     * Assign a single feature to yayin tipi
     *
     * @param int $yayinTipiId
     * @param int $featureId
     * @param array $options Additional options (display_order, is_required, etc.)
     * @return array ['success' => bool, 'message' => string, 'assignment' => ?FeatureAssignment]
     */
    public function assignFeature(int $yayinTipiId, int $featureId, array $options = []): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        $this->guardAgainstForbiddenFields($options);

        try {
            // Check if yayin tipi exists
            $yayinTipi = YayinTipiSablonu::findOrFail($yayinTipiId);

            // Check if feature exists
            $feature = Feature::findOrFail($featureId);

            // Check for duplicate
            $existing = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $yayinTipiId)
                ->where('feature_id', $featureId)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "Özellik zaten atanmış: {$feature->name}",
                    'assignment' => $existing,
                ];
            }

            // Create assignment
            $assignment = FeatureAssignment::create([
                'feature_id' => $featureId,
                'assignable_type' => YayinTipiSablonu::class,
                'assignable_id' => $yayinTipiId,
                'source_type' => $options['source_type'] ?? 'manual',
                'display_order' => $options['display_order'] ?? 0,
                'is_required' => $options['is_required'] ?? false,
                'is_visible' => $options['is_visible'] ?? true,
                'aktiflik_durumu' => true,
                'metadata' => $options['metadata'] ?? null,
            ]);

            // Log change
            $this->logChange(
                'assign_feature',
                $assignment->id,
                "Özellik atandı: {$feature->name} -> {$yayinTipi->ad}"
            );

            return [
                'success' => true,
                'message' => "Özellik başarıyla atandı: {$feature->name}",
                'assignment' => $assignment,
            ];
        } catch (\Exception $e) {
            Log::error('Feature assignment failed', [
                'yayin_tipi_id' => $yayinTipiId,
                'feature_id' => $featureId,
                'error' => $e->getMessage(),
            ]);
            throw new \DomainException("Özellik atama hatası: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Unassign a feature from yayin tipi
     *
     * @param int $yayinTipiId
     * @param int $featureId
     * @return array ['success' => bool, 'message' => string]
     */
    public function unassignFeature(int $yayinTipiId, int $featureId): array
    {
        try {
            $assignment = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $yayinTipiId)
                ->where('feature_id', $featureId)
                ->first();

            if (!$assignment) {
                return [
                    'success' => false,
                    'message' => 'Özellik ataması bulunamadı',
                ];
            }

            return $this->deleteAssignment($assignment->id);
        } catch (\Exception $e) {
            Log::error('Feature unassignment failed', [
                'yayin_tipi_id' => $yayinTipiId,
                'feature_id' => $featureId,
                'error' => $e->getMessage(),
            ]);
            throw new \DomainException("Özellik kaldırma hatası: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Delete an assignment by ID (SAB Phase 1A)
     *
     * @param int $assignmentId
     * @return array
     */
    public function deleteAssignment(int $assignmentId): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        try {
            $assignment = FeatureAssignment::findOrFail($assignmentId);
            $feature = Feature::find($assignment->feature_id);

            // Log before deletion
            $this->logChange(
                'delete_assignment',
                $assignment->id,
                "Atama silindi: " . ($feature?->name ?? 'Bilinmeyen Özellik')
            );

            $success = $assignment->delete();

            return [
                'success' => $success,
                'message' => $success ? 'Atama başarıyla silindi' : 'Atama silinemedi',
            ];
        } catch (\Exception $e) {
            Log::error('Assignment deletion failed', [
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage(),
            ]);
            throw new \DomainException("Atama silme hatası: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Bulk assign multiple features to yayin tipi
     *
     * @param int $yayinTipiId
     * @param array $featureIds
     * @return array ['success' => bool, 'added' => int, 'skipped' => int, 'message' => string]
     */
    public function bulkAssign(int $yayinTipiId, array $featureIds): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        DB::beginTransaction();

        try {
            $yayinTipi = YayinTipiSablonu::findOrFail($yayinTipiId);

            $added = 0;
            $skipped = 0;

            foreach ($featureIds as $featureId) {
                // Check if already assigned
                $exists = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                    ->where('assignable_id', $yayinTipiId)
                    ->where('feature_id', $featureId)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Create assignment
                FeatureAssignment::create([
                    'feature_id' => $featureId,
                    'assignable_type' => YayinTipiSablonu::class,
                    'assignable_id' => $yayinTipiId,
                    'source_type' => 'bulk_assign',
                    'aktiflik_durumu' => true,
                ]);

                $added++;
            }

            // Log change
            $this->logChange(
                'bulk_assign',
                $yayinTipiId,
                "Toplu atama: {$added} özellik eklendi, {$skipped} atlandı -> {$yayinTipi->ad}"
            );

            DB::commit();

            return [
                'success' => true,
                'added' => $added,
                'skipped' => $skipped,
                'message' => "{$added} özellik eklendi, {$skipped} zaten atanmıştı",
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk assignment failed', [
                'yayin_tipi_id' => $yayinTipiId,
                'feature_ids' => $featureIds,
                'error' => $e->getMessage(),
            ]);
            throw new \DomainException("Toplu atama hatası: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Bulk unassign multiple features from yayin tipi
     *
     * @param int $yayinTipiId
     * @param array $featureIds
     * @return array ['success' => bool, 'removed' => int, 'message' => string]
     */
    public function bulkUnassign(int $yayinTipiId, array $featureIds): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        DB::beginTransaction();

        try {
            $removed = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $yayinTipiId)
                ->whereIn('feature_id', $featureIds)
                ->delete();

            $yayinTipi = YayinTipiSablonu::find($yayinTipiId);

            // Log change
            $this->logChange(
                'bulk_unassign',
                $yayinTipiId,
                "Toplu kaldırma: {$removed} özellik kaldırıldı <- {$yayinTipi?->ad}"
            );

            DB::commit();

            return [
                'success' => true,
                'removed' => $removed,
                'message' => "{$removed} özellik ataması kaldırıldı",
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk unassignment failed', [
                'yayin_tipi_id' => $yayinTipiId,
                'feature_ids' => $featureIds,
                'error' => $e->getMessage(),
            ]);
            throw new \DomainException("Toplu kaldırma hatası: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Guard against forbidden Context7 fields
     *
     * @param array $data
     * @return void
     * @throws \InvalidArgumentException
     */
    private function guardAgainstForbiddenFields(array $data): void
    {
        foreach (self::getForbiddenFields() as $forbiddenField) {
            if (array_key_exists($forbiddenField, $data)) {
                throw new \InvalidArgumentException(
                    "Forbidden field: {$forbiddenField}. Use Context7 canonical fields instead."
                );
            }
        }
    }

    /**
     * Log template changes
     *
     * @param string $action
     * @param int $entityId
     * @param string $description
     * @return void
     */
    private function logChange(string $action, int $entityId, string $description): void
    {
        try {
            TemplateChangeLog::create([
                'aksiyon_tipi' => $action,
                'entity_type' => 'feature_assignment',
                'entity_id' => $entityId,
                'aciklama' => $description,
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log template change', [
                'action' => $action,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
