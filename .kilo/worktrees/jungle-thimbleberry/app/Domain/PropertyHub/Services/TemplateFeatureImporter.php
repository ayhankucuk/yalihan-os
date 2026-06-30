<?php

namespace App\Domain\PropertyHub\Services;

use App\Models\YayinTipiSablonu;

/**
 * TemplateFeatureImporter — Domain Service
 *
 * [SAB ENFORCEMENT]: AR Overgrowth Prevention — extracted from PropertyTypeConfiguration
 *
 * Sorumluluklar:
 * - Bulk feature import (loop + assignIndividual + display_order)
 * - Template feature clearing
 *
 * Transaction ve Event uretimi AR'da kalir.
 * Bu servis sadece data manipulation yapar.
 *
 * @see \App\Domain\PropertyHub\PropertyTypeConfiguration
 * @see \App\Domain\PropertyHub\Services\FeaturePackApplicator
 */
class TemplateFeatureImporter
{
    public function __construct(
        private FeaturePackApplicator $applicator,
    ) {}

    /**
     * Import features to a master template (bulk assign with display_order)
     *
     * Her feature icin assignIndividual cagirir, hata olursa toplar ve devam eder.
     * Transaction boundary bu method'da YOKTUR — AR yonetir.
     *
     * @param int $templateId YayinTipiSablonu ID
     * @param array $featureDataList [{feature_id: int, source_type: string, display_order: int}, ...]
     * @return array{added: int, skipped: int, errors: array}
     */
    public function import(int $templateId, array $featureDataList): array
    {
        $results = ['added' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($featureDataList as $idx => $featureData) {
            try {
                $assigned = $this->applicator->assignIndividual(
                    $templateId,
                    [$featureData['feature_id']],
                    $featureData['source_type'] ?? 'import',
                    YayinTipiSablonu::class
                );

                if (!empty($assigned)) {
                    if (isset($featureData['display_order'])) {
                        $assigned[0]->update(['display_order' => $featureData['display_order']]);
                    }
                    $results['added']++;
                } else {
                    $results['skipped']++;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('TemplateFeatureImporter: Error importing feature', [
                    'feature_index' => $idx,
                    'feature_id' => $featureData['feature_id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                throw $e; // SAB v3.1: Silent fail engellendi, fail-fast prensibiyle atomik işlem güvence altına alındı.
            }
        }

        return $results;
    }

    /**
     * Clear all features from a master template (YayinTipiSablonu scope)
     *
     * @param int $templateId YayinTipiSablonu ID
     * @return int Deleted count
     */
    public function clear(int $templateId): int
    {
        return $this->applicator->clearAll($templateId, YayinTipiSablonu::class);
    }
}
