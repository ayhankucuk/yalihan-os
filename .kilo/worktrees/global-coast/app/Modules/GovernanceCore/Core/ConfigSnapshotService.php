<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Core;

use App\Models\UpsTemplate;
use App\Models\YayinTipiSablonu;
use App\Models\Feature;
use App\Models\RuleDefinition;
use Illuminate\Support\Facades\Log;

/**
 * Service: ConfigSnapshotService
 *
 * Captures the entire state of templates, rules, and assignments into a
 * deterministic JSON structure for the PropertyConfigVersion model.
 */
class ConfigSnapshotService
{
    /**
     * Capture the current database state as a snapshot array.
     *
     * @return array The full configuration snapshot
     */
    public function capture(): array
    {
        return [
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version_schema' => '1.0',
            ],
            'rules' => $this->captureRules(),
            'templates' => $this->captureTemplates(),
            'master_templates' => $this->captureMasterTemplates(),
            'features' => $this->captureFeatures(),
        ];
    }

    /**
     * Capture all UpsTemplate records with their relationships.
     */
    protected function captureTemplates(): array
    {
        // Deterministic sorting IS CRITICAL for consistency
        return UpsTemplate::with(['kategori', 'masterTemplate'])
            ->orderBy('id')
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'kategori_id' => $template->kategori_id,
                    'yayin_tipi_sablonu_id' => $template->yayin_tipi_sablonu_id,
                    'yayin_tipi_id' => $template->yayin_tipi_id,
                    'aktiflik_durumu' => $template->aktiflik_durumu,
                    'created_at' => $template->created_at->toIso8601String(),
                    'updated_at' => $template->updated_at->toIso8601String(),
                ];
            })
            ->toArray();
    }

    /**
     * Capture all RuleDefinition records.
     */
    protected function captureRules(): array
    {
        return RuleDefinition::orderBy('priority')
            ->orderBy('id')
            ->get()
            ->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'rule_type' => $rule->rule_type,
                    'rule_config' => $rule->rule_config,
                    'priority' => $rule->priority,
                    'aktif' => $rule->aktif,
                ];
            })
            ->toArray();
    }

    /**
     * Capture all YayinTipiSablonu records (Master Templates).
     */
    protected function captureMasterTemplates(): array
    {
        return YayinTipiSablonu::orderBy('id')
            ->get()
            ->map(function ($master) {
                return [
                    'id' => $master->id,
                    'ad' => $master->ad,
                    'kategori_id' => $master->kategori_id,
                    'yayin_tipi_id' => $master->yayin_tipi_id,
                    'ozellikler' => $master->ozellikler, // JSON field
                    'aktiflik_durumu' => $master->aktiflik_durumu,
                    'display_order' => $master->display_order,
                ];
            })
            ->toArray();
    }

    /**
     * Capture all Feature records (Snapshot-Only Data Schema).
     */
    protected function captureFeatures(): array
    {
        // 🚨 Context7: Snapshot-Only Rule - Capture all active features for AI context
        return Feature::orderBy('id')
            ->get()
            ->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'slug' => $feature->slug,
                    'type' => $feature->type,
                    'options' => $feature->options,
                    'feature_category_id' => $feature->feature_category_id,
                    'aktiflik_durumu' => $feature->aktiflik_durumu,
                    'display_order' => $feature->display_order,
                ];
            })
            ->toArray();
    }

    public function calculateSignature(array $snapshot): string
    {
        return self::computeSignature($snapshot);
    }

    /**
     * static Compute Signature (SSOT for Validation)
     */
    /**
     * static Compute Signature (SSOT for Validation)
     */
    public static function computeSignature(array $data): string
    {
        $normalized = self::normalizeRecursive($data);

        // Final roundtrip to ensure types match Eloquent decode/encode behavior
        $json = json_encode($normalized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        $finalData = json_decode($json, true);
        $finalJson = json_encode($finalData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

        return hash('sha256', $finalJson);
    }

    private static function normalizeRecursive(array $data): array
    {
        ksort($data);
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = self::normalizeRecursive($value);
            }
        }
        return $data;
    }
}
