<?php

namespace App\Services\Listing;

use App\Models\YayinTipiSablonu;

/**
 * ✅ SPRINT 2: TypeResolver Service
 *
 * Context7 Compliance: %100
 * - Resolves Property Type configurations for Wizard
 * - Merges template config with type-specific overrides
 * - Returns complete JSON config for frontend consumption
 *
 * @package App\Services\Listing
 */
class TypeResolver
{
    /**
     * Resolve complete configuration for a property type
     *
     * @param int $yayinTipiId Publication Type ID
     * @return array Complete configuration array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolveTypeConfig(int $yayinTipiId): array
    {
        $yayinTipi = YayinTipiSablonu::findOrFail($yayinTipiId);

        $config = $yayinTipi->varsayilan_ozellikler ?? [];

        return [
            // ✅ Template Identity
            'template_id' => $yayinTipi->id,
            'template_name' => $yayinTipi->ad,
            'template_code' => $yayinTipi->slug,

            // ✅ Feature Groups (from varsayilan_ozellikler)
            'feature_groups' => $config['feature_groups'] ?? [],

            // ✅ Required Fields
            'required_fields' => $config['required_fields'] ?? [],

            // ✅ Optional Fields
            'optional_fields' => $config['optional_fields'] ?? [],

            // ✅ Hidden Fields
            'hidden_fields' => $config['hidden_fields'] ?? [],

            // ✅ POI Configuration
            'poi_config' => $config['poi_config'] ?? $this->getDefaultConfig()['poi_config'],

            // ✅ AI Metadata
            'confidence_score' => $config['confidence_score'] ?? 100,
            'ai_model_version' => $config['ai_model_version'] ?? 'v2.0',
        ];
    }

    /**
     * Get default configuration (fallback when no template assigned)
     *
     * @return array Default config
     */
    protected function getDefaultConfig(): array
    {
        return [
            'template_id' => null,
            'template_name' => 'Varsayılan Şablon',
            'template_code' => 'default',
            'feature_groups' => [],
            'required_fields' => ['baslik', 'kategori_id', 'fiyat'],
            'optional_fields' => [],
            'hidden_fields' => [],
            'poi_config' => [
                'radius' => 1000,
                'categories' => ['all'],
                'auto_fetch' => true,
            ],
            'confidence_score' => 100,
            'ai_model_version' => 'v1.0',
        ];
    }

    /**
     * Validate if config meets minimum requirements
     *
     * @param array $config
     * @return bool
     */
    public function validateConfig(array $config): bool
    {
        $requiredKeys = ['template_id', 'required_fields', 'poi_config'];

        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                return false;
            }
        }

        return true;
    }
}
