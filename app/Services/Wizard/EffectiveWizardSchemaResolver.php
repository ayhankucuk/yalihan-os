<?php

namespace App\Services\Wizard;

use Illuminate\Support\Str;

/**
 * EffectiveWizardSchemaResolver — Schema-driven field resolution for Wizard Step 2.
 *
 * Produces a typed schema contract from scoped feature_assignments + features tables.
 * The frontend renders fields dynamically based on this schema — no hardcoded if/else.
 *
 * Schema Contract:
 * {
 *   template_id: int,
 *   template_name: string,
 *   fields: [{ feature_id, slug, label, type, required, group, source, sort_order, options }],
 *   meta: { field_count, required_count }
 * }
 *
 * Delegates to: FeatureTemplateResolver (Wizard-scoped; system SSOT = Ups\FeatureTemplateResolver),
 *               DependencyRuleEvaluator (conditional field logic).
 * Used by: IlanWizardController@schema, DynamicFieldValueMapper, Step 2 dynamic rendering.
 *
 * ID CONVENTION:
 *   $yayinTipiId may be either:
 *     - A yayin_tipi_sablonlari.id (sablon/template/junction ID, e.g. 22=Satilik, 24=Gunluk)
 *     - A yayin_tipleri.id (publication type ID, e.g. 1=Satilik, 5=Gunluk)
 *   This resolver auto-detects: if a sablon ID is passed, it is resolved to the
 *   underlying yayin_tipi_id before querying feature_assignments.
 */
class EffectiveWizardSchemaResolver
{
    public function __construct(
        private readonly FeatureTemplateResolver $featureResolver,
        private readonly DependencyRuleEvaluator $dependencyEvaluator = new DependencyRuleEvaluator(),
    ) {}

    /**
     * Resolve full schema contract for a category + publication type.
     *
     * @param int $kategoriId Category ID — when a sablon ID is passed for yayin_tipi_id,
     *                              this is treated as the sub-category ID (NOT the parent main category).
     * @param int $yayinTipiId Sablon ID (yayin_tipi_sablonlari.id) OR yayin_tipi ID (yayin_tipleri.id)
     *                              Auto-detected: sablon IDs are resolved to publication type IDs
     *                              and their kategori_id becomes the sub-category.
     * @return array Schema contract array
     */
    public function resolve(int $kategoriId, int $yayinTipiId): array
    {
        // IlanWizardController@schema passes:
        //   - kategori_id = sub-category (Villa=8) when sablon is used
        //   - yayin_tipi_id = sablon_id (e.g. 22=Satilik, 24=Gunluk)
        //
        // WizardFeatureController passes:
        //   - ana_kategori_id = main category (Konut=11)
        //   - alt_kategori_id = sub-category (Villa=8)
        //   - yayin_tipi_id = yayin_tipi_id (1=Satilik, 5=Gunluk)
        //
        // This method handles both conventions by detecting sablon IDs.

        $sablon = \App\Models\YayinTipiSablonu::find($yayinTipiId);
        $isSablonId = $sablon && !empty($sablon->yayin_tipi_id);

        if ($isSablonId) {
            // Sablon ID path (IlanWizardController convention):
            // kategori_id IS the sub-category ID (e.g. 8 for Villa)
            // yayin_tipi_id IS the sablon ID (e.g. 24 for Villa Gunluk)
            $publicationTypeId = (int) $sablon->yayin_tipi_id;
            $subCategoryId = $kategoriId; // kategori_id IS the sub-category here
            $mainCategoryId = (int) $sablon->kategori_id; // sablon's kategori_id = main category
        } else {
            // Raw yayin_tipi_id path (WizardFeatureController convention)
            $publicationTypeId = $yayinTipiId;
            $subCategoryId = null;
            $mainCategoryId = $kategoriId;
        }

        $features = $this->featureResolver->resolveFeatures($mainCategoryId, $subCategoryId, $publicationTypeId);

        $fields = $features->map(function (array $feature) {
            return [
                'feature_id' => $feature['feature_id'],
                'slug' => $feature['slug'],
                'label' => $feature['label'],
                'type' => $feature['type'] ?? 'text', // context7-ignore
                'required' => (bool) ($feature['required'] ?? false),
                'group' => $feature['group'] ?? 'Genel',
                'group_slug' => $feature['group_slug'] ?? 'genel',
                'source' => $feature['source_type'] ?? 'feature_assignment',
                'sort_order' => (int) ($feature['display_order'] ?? 999),
                'unit' => $feature['unit'] ?? null,
                'description' => $feature['description'] ?? null,
                'options' => $this->normalizeOptions($feature['options'] ?? null, $feature['type'] ?? 'text'), // context7-ignore
                'visible_if' => $feature['visible_if'] ?? null,
                'required_if' => $feature['required_if'] ?? null,
                'enabled_if' => $feature['enabled_if'] ?? null,
            ];
        })
            ->sortBy('sort_order')
            ->values()
            ->toArray();

        $requiredCount = collect($fields)->where('required', true)->count();

        return [
            'template_id' => $yayinTipiId,
            'template_name' => $this->resolveTemplateName($yayinTipiId),
            'fields' => $fields,
            'meta' => [
                'field_count' => count($fields),
                'required_count' => $requiredCount,
            ],
        ];
    }

    /**
     * Normalize options array for select/multiselect fields.
     *
     * Options from the resolver may be simple strings or already {value, label} pairs.
     * Ensures consistent {value: slug, label: text} format.
     *
     * @param array|null $options Raw options from resolver
     * @param string $type Field type
     * @return array|null Normalized options or null for non-select types
     */
    private function normalizeOptions(?array $options, string $type): ?array
    {
        if (!in_array($type, ['select', 'multiselect'])) {
            return null;
        }

        if (empty($options)) {
            return null;
        }

        return collect($options)->map(function ($option) {
            if (is_array($option) && isset($option['value'], $option['label'])) {
                return $option;
            }

            // Simple string options: "Müstakil Tapu" → {value: "mustakil-tapu", label: "Müstakil Tapu"}
            $label = is_string($option) ? $option : (string) $option;

            return [
                'value' => Str::slug($label),
                'label' => $label,
            ];
        })->values()->toArray();
    }

    /**
     * Resolve template name from yayin_tipi_id.
     *
     * @param int $yayinTipiId Publication Type Template ID
     * @return string Template name
     */
    private function resolveTemplateName(int $yayinTipiId): string
    {
        $template = \App\Models\YayinTipiSablonu::find($yayinTipiId);

        return $template->ad ?? ('Şablon #' . $yayinTipiId);
    }

    /**
     * Get only required field slugs for validation rule building.
     *
     * @param int $kategoriId Category ID
     * @param int $yayinTipiId Publication Type ID
     * @return array List of required field slugs
     */
    public function getRequiredFieldSlugs(int $kategoriId, int $yayinTipiId): array
    {
        $schema = $this->resolve($kategoriId, $yayinTipiId);

        return collect($schema['fields'])
            ->where('required', true)
            ->pluck('slug')
            ->toArray();
    }

    /**
     * Get allowed field slugs (schema whitelist).
     *
     * Only fields in this list may be saved to DB.
     *
     * @param int $kategoriId Category ID
     * @param int $yayinTipiId Publication Type ID
     * @return array List of allowed field slugs
     */
    public function getAllowedFieldSlugs(int $kategoriId, int $yayinTipiId): array
    {
        $schema = $this->resolve($kategoriId, $yayinTipiId);

        return collect($schema['fields'])
            ->pluck('slug')
            ->toArray();
    }

    /**
     * Build Laravel validation rules from schema.
     *
     * @param int $kategoriId Category ID
     * @param int $yayinTipiId Publication Type ID
     * @return array Laravel validation rules keyed by "features.{slug}"
     */
    public function buildValidationRules(int $kategoriId, int $yayinTipiId): array
    {
        $schema = $this->resolve($kategoriId, $yayinTipiId);
        return $this->buildRulesFromFields($schema['fields'], []);
    }

    /**
     * Build dependency-aware validation rules.
     *
     * Evaluates visible_if / required_if / enabled_if against current payload.
     * If a field is invisible (visible_if evaluates false), it is excluded.
     * If required_if evaluates true, field becomes required even if base is nullable.
     *
     * @param int $kategoriId Category ID
     * @param int $yayinTipiId Publication Type ID
     * @param array $payload Current request features payload {slug => value}
     * @return array Laravel validation rules keyed by "features.{slug}"
     */
    public function buildDependencyAwareRules(int $kategoriId, int $yayinTipiId, array $payload): array
    {
        $schema = $this->resolve($kategoriId, $yayinTipiId);
        return $this->buildRulesFromFields($schema['fields'], $payload);
    }

    /**
     * Build validation rules from field definitions, optionally evaluating dependency rules.
     */
    private function buildRulesFromFields(array $fields, array $payload): array
    {
        $rules = [];
        $knownSlugs = array_column($fields, 'slug');

        foreach ($fields as $field) {
            // If payload provided, evaluate visibility — skip invisible fields
            if (!empty($payload)) {
                if (!$this->dependencyEvaluator->isVisible($field, $payload, $knownSlugs)) {
                    continue;
                }

                if (!$this->dependencyEvaluator->isEnabled($field, $payload, $knownSlugs)) {
                    continue;
                }
            }

            $fieldRules = [];

            // Determine required state (base + required_if)
            $isRequired = $field['required'] ?? false;
            if (!$isRequired && !empty($payload) && !empty($field['required_if'])) {
                $isRequired = $this->dependencyEvaluator->evaluate($field['required_if'], $payload, $knownSlugs);
            }

            $fieldRules[] = $isRequired ? 'required' : 'nullable';

            // Type-based rules
            switch ($field['type']) { // context7-ignore
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'boolean':
                    $fieldRules[] = 'boolean';
                    break;
                case 'select':
                    $allowedValues = collect($field['options'] ?? [])
                        ->pluck('value')
                        ->toArray();
                    if (!empty($allowedValues)) {
                        $fieldRules[] = 'in:' . implode(',', $allowedValues);
                    }
                    break;
                case 'multiselect':
                    $fieldRules[] = 'array';
                    break;
                case 'text':
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:500';
                    break;
            }

            $rules['features.' . $field['slug']] = implode('|', $fieldRules);
        }

        return $rules;
    }

    /**
     * Get the dependency rule evaluator instance.
     *
     * Exposed for use by other services that need the same evaluation logic
     * (e.g., DynamicFieldValueMapper for dependency-aware persist).
     */
    public function getDependencyEvaluator(): DependencyRuleEvaluator
    {
        return $this->dependencyEvaluator;
    }

    /**
     * Resolve a sablon/junction ID to the underlying yayin_tipi_id.
     *
     * IlanWizardController@schema passes sablon IDs (22, 24) which are IDs in the
     * yayin_tipi_sablonlari table. The resolver must detect these and convert them
     * to the actual publication type ID (1, 5) before querying feature_assignments.
     *
     * Detection: if a row exists in yayin_tipi_sablonlari with this ID,
     * use its yayin_tipi_id. Otherwise treat as a raw yayin_tipi_id.
     *
     * @param int $id Sablon ID (yayin_tipi_sablonlari.id) OR yayin_tipi ID (yayin_tipleri.id)
     * @return int Actual yayin_tipi_id
     */
    private function resolvePublicationTypeId(int $id): int
    {
        $sablon = \App\Models\YayinTipiSablonu::find($id);

        return $sablon && !empty($sablon->yayin_tipi_id)
            ? (int) $sablon->yayin_tipi_id
            : $id;
    }
}
