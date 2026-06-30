<?php

namespace App\Services\Wizard;

use App\Models\IlanKategori;
use App\Models\YayinTipleri;
use App\Models\YayinTipiSablonu;
use App\Contracts\TemplateResolverInterface;
use App\Services\Template\TemplateService;
use App\Services\Ups\FeatureTemplateResolver;
use Illuminate\Support\Facades\Log;

/**
 * 🛰️ WizardContext SSOT Resolver Service
 *
 * Sorumluluk: İlan sihirbazı için kategori, yayın tipi, template ve özellikleri
 * tek bir deterministik kaynak olarak resolve eder.
 *
 * V2 Update: Template System with TemplateResolver (kategori_id + yayin_tipi STRING)
 * Legacy: FeatureTemplateResolver (kategori_id + yayin_tipi_id)
 *
 * @see docs/technical/TEMPLATE_SYSTEM_ARCHITECTURE.md
 */
class WizardContextService
{
    /**
     * Feature flag: Use Template System V2
     */
    private bool $useTemplateV2 = true;

    public function __construct(
        private TemplateService $templateService,
        private FeatureTemplateResolver $featureResolver,
        private TemplateResolverInterface $templateResolver
    ) {}

    /**
     * Resolve comprehensive wizard context
     */
    public function resolve(int $kategoriId, int $yayinTipiId, array $options = []): array
    {
        try {
            // 1. Load Core Models
            $kategori = IlanKategori::findOrFail($kategoriId);

            // V2: Resolve YayinTipiSablonu directly from ID
            // In V2, $yayinTipiId passed from wizard is interpreted as YayinTipiSablonu ID (Global Template ID)
            $yayinTipiTemplate = YayinTipiSablonu::find($yayinTipiId);

            if (!$yayinTipiTemplate) {
                 // Fallback: If it's not a direct template ID, maybe it's the old pivot?
                 // But pivot table is gone. So we assume it MUST be a valid template ID or we fail.
                 throw new \Exception("Yayin Tipi Sablonu not found for ID: {$yayinTipiId}");
            }

            // We treat the "pivot" concept as the template itself in V2 context
            // To maintain some compatibility with code below that expects $pivot->yayin_tipi
            // We can wrap it or just use the template.

            // Construct a mock pivot object structure if needed, or update downstream logic.
            // Let's update logic to use $yayinTipiTemplate directly.

            $targetKategoriId = $kategoriId; // Simplified in V2 (Global Templates don't depend on parent category logic usually)

            // Log selection
            Log::info('WizardContext: Resolved Template', [
                'kategori_id' => $kategoriId,
                'template_id' => $yayinTipiTemplate->id,
                'template_name' => $yayinTipiTemplate->ad
            ]);

            // 2. Resolve Template & Features
            // V2: Use Template System with TemplateResolver (kategori_id + yayin_tipi STRING)
            // Legacy: Use TemplateService + FeatureTemplateResolver (kategori_id + yayin_tipi_id)

            $groupedFeatures = [];
            $template = null;

            if ($this->useTemplateV2) {
                 // NEW: Template System V2 (Standard Master Templates)
                 Log::info('WizardContext: Using Template System V2', [
                     'kategori_id' => $targetKategoriId,
                     'yayin_tipi' => $yayinTipiTemplate->ad
                 ]);

                 try {
                     $resolvedTemplate = $this->templateResolver->resolve($targetKategoriId, $yayinTipiTemplate->slug);

                     if (!$resolvedTemplate) {
                         // Fallback to the master template itself if no specific override
                         $resolvedTemplate = $yayinTipiTemplate;
                     }

                     // Load features from feature_assignments
                     $groupedFeatures = $this->loadFeaturesFromAssignments($resolvedTemplate);

                     // For V2, we might not have 'template_json' on the YayinTipiSablonu directly unless migrated.
                     // The migration shows: varsayilan_ozellikler (JSON) and fiyat_ayarlari (JSON).
                     // It does NOT show template_json directly?
                     // Wait, feature_assignments is the source of truth for features.

                     // Let's assume standard structure.

                     $template = [
                         'id' => $resolvedTemplate->id,
                         'name' => "{$kategori->name} - {$yayinTipiTemplate->ad}",
                         'required' => [], // [Phase 5] feature_assignments required=true filtresi
                         'optional' => [],
                         'hidden' => [],
                         'fields' => [],
                         'validation' => [
                             'rules' => [],
                             'messages' => []
                         ],
                         'source' => 'template_v2'
                     ];

                 } catch (\Exception $e) {
                      Log::warning('Template V2 resolution failed', [
                         'error' => $e->getMessage()
                     ]);
                     // No fallback available since legacy is gone.
                     throw $e;
                 }
            }

            // 4. Transform to SSOT Context Structure
            return [
                'success' => true,
                'context' => [
                    'category' => [
                        'id' => $kategori->id,
                        'slug' => $kategori->slug,
                        'name' => $kategori->name,
                    ],
                    'yayin_tipi' => [
                        'id' => $yayinTipiTemplate->id,
                        'name' => $yayinTipiTemplate->ad, // 'ad' is the column name in Context7
                        'slug' => $yayinTipiTemplate->slug,
                    ],
                    'template' => $this->formatTemplate($template),
                    'features' => $this->formatFeatures($groupedFeatures),
                    'fallback' => false
                ]
            ];
        } catch (\Exception $e) {
            Log::error('WizardContext Resolution Failure', [
                'kategori_id'   => $kategoriId,
                'yayin_tipi_id' => $yayinTipiId,
                'hata_mesaji'   => $e->getMessage(),
            ]);

            // Governance Enforcement: silent fallback KALDIRILDI.
            // Caller 500 alır, hata yutulmaz.
            // @see docs/adr/2026-02-21-governance-enforcement-layer.md
            throw $e;
        }
    }

    /**
     * Format template data to comply with SSOT requirements
     */
    private function formatTemplate(?array $template): array
    {
        if (!$template) {
            return [
                'id' => null,
                'name' => 'Fallback Template',
                'required' => ['baslik', 'fiyat', 'aciklama'],
                'optional' => [],
                'hidden' => [],
                'field_visibility' => [
                    'step2' => ['baslik', 'fiyat', 'aciklama', 'para_birimi', 'alan_m2'],
                    'step3' => ['fotograflar', 'video_url', 'sanal_tur_url'],
                    'step4' => ['il_id', 'ilce_id', 'mahalle_id', 'adres']
                ],
                'validation_rules' => [
                    'rules' => [
                        'baslik' => 'required|min:5',
                        'fiyat' => 'required|numeric',
                        'aciklama' => 'required|min:50'
                    ],
                    'messages' => []
                ]
            ];
        }

        // SSOT Mapping: Use keys from the template table
        $required = $template['required'] ?? [];
        $optional = $template['optional'] ?? [];

        return [
            'id' => $template['id'] ?? null,
            'name' => $template['name'] ?? 'Custom Template',
            'required' => $required,
            'optional' => $optional,
            'hidden' => $template['hidden'] ?? [],
            'field_visibility' => $template['field_visibility'] ?? [
                'step2' => array_merge(['baslik', 'fiyat', 'aciklama', 'para_birimi', 'alan_m2'], $required, $optional),
                'step3' => ['fotograflar', 'video_url', 'sanal_tur_url'],
                'step4' => ['il_id', 'ilce_id', 'mahalle_id', 'adres']
            ],
            'validation_rules' => $template['validation'] ?? [
                'rules' => [],
                'messages' => []
            ],
            'fields' => $template['fields'] ?? []
        ];
    }

    /**
     * Format features to comply with SSOT requirements
     */
    private function formatFeatures($groupedFeatures): array
    {
        $featureGroups = [];
        $featureSchema = [];
        $upsBindings = [];

        foreach ($groupedFeatures as $groupName => $features) {
            $featureGroups[] = [
                'name' => $groupName,
                'slug' => str()->slug($groupName),
                'count' => count($features)
            ];

            foreach ($features as $feature) {
                $featureSchema[$feature['slug']] = [
                    'id' => $feature['id'],
                    'name' => $feature['name'],
                    'tip' => $feature['type'], // context7-ignore
                    'options' => $feature['options'],
                    'unit' => $feature['unit'],
                    'required' => $feature['required']
                ];

                $upsBindings[$feature['slug']] = [
                    'feature_id' => $feature['id'],
                    'group' => $groupName
                ];
            }
        }

        return [
            'feature_groups' => $featureGroups,
            'feature_schema' => $featureSchema,
            'ups_bindings' => $upsBindings
        ];
    }

    /**
     * Load features from feature_assignments (Template System V2)
     *
     * @param \App\Models\YayinTipiSablonu $template
     * @return array Grouped features
     */
    private function loadFeaturesFromAssignments(YayinTipiSablonu $template): array
    {
        // Load feature assignments with features
        $assignments = $template->featureAssignments()
            ->with('feature')
            ->where('is_visible', true)
            ->whereHas('feature', function ($query) {
                $query->where('aktiflik_durumu', true);
            })
            ->orderBy('display_order') // context7-ignore
            ->get();

        // Group features by domain (based on feature slug prefix)
        $grouped = [];

        foreach ($assignments as $assignment) {
            $feature = $assignment->feature;

            // Determine group based on slug prefix or use 'Genel' as default
            $group = 'Genel Özellikler';
            if (str_starts_with($feature->slug, 'konut_')) {
                $group = 'Konut Özellikleri';
            } elseif (str_starts_with($feature->slug, 'arsa_')) {
                $group = 'Arsa Özellikleri';
            } elseif (str_starts_with($feature->slug, 'ticari_')) {
                $group = 'Ticari Özellikler';
            }

            $grouped[$group][] = [
                'id' => $feature->id,
                'slug' => $feature->slug,
                'name' => $feature->name,
                'tip' => $feature->type, // context7-ignore
                'unit' => $feature->unit,
                'options' => $feature->options,
                'required' => (bool) $assignment->is_required,
                'visible' => (bool) $assignment->is_visible,
                'display_order' => $assignment->display_order,
            ];
        }

        return $grouped;
    }
}
