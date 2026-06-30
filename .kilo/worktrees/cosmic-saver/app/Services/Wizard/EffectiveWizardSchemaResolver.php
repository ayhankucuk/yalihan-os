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
     * @param int $kategoriId Category ID (main or sub — treated as main when called from legacy code)
     * @param int $yayinTipiId Publication Type ID
     * @return array Schema contract array
     */
    public function resolve(int $kategoriId, int $yayinTipiId): array
    {
        $features = $this->featureResolver->resolveFeatures($kategoriId, null, $yayinTipiId);

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
}
