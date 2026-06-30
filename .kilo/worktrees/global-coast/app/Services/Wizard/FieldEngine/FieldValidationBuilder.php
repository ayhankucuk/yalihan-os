<?php

namespace App\Services\Wizard\FieldEngine;

use App\Services\Wizard\DependencyRuleEvaluator;

/**
 * FieldValidationBuilder — FieldDefinition[] → Laravel validation rules.
 *
 * Schema-driven validasyon kural üreticisi.
 * Field type'a göre otomatik kural oluşturur.
 * Dependency-aware: visible_if false olan field'lar skip edilir.
 *
 * Kullanım:
 *   $builder = app(FieldValidationBuilder::class);
 *   $rules = $builder->build($fields, $formValues);
 *   // Returns: ['features.oda_sayisi' => 'required|in:1+0,1+1,...', ...]
 */
class FieldValidationBuilder
{
    public function __construct(
        private readonly DependencyRuleEvaluator $dependencyEvaluator,
    ) {}

    /**
     * Build Laravel validation rules from field definitions.
     *
     * @param FieldDefinition[] $fields
     * @param array $formValues Current form data (for dependency-aware rules)
     * @return array<string, string> Laravel validation rules keyed by "features.{slug}"
     */
    public function build(array $fields, array $formValues = []): array
    {
        $rules = [];
        $knownSlugs = array_map(fn (FieldDefinition $f) => $f->slug, $fields);

        foreach ($fields as $field) {
            $fieldArray = $field->toArray();

            // Skip invisible fields
            if (!empty($formValues)) {
                if (!$this->dependencyEvaluator->isVisible($fieldArray, $formValues, $knownSlugs)) {
                    continue;
                }
                if (!$this->dependencyEvaluator->isEnabled($fieldArray, $formValues, $knownSlugs)) {
                    continue;
                }
            }

            // Determine required state
            $isRequired = $field->required;
            if (!$isRequired && !empty($formValues)) {
                $isRequired = $this->dependencyEvaluator->isRequired($fieldArray, $formValues, $knownSlugs);
            }

            $fieldRules = $this->buildFieldRules($field, $isRequired);

            $rules['features.' . $field->slug] = implode('|', $fieldRules);
        }

        return $rules;
    }

    /**
     * Build rules for a single field.
     */
    private function buildFieldRules(FieldDefinition $field, bool $isRequired): array
    {
        $rules = [];

        // Required / nullable
        $rules[] = $isRequired ? 'required' : 'nullable';

        // Type-based rules
        switch ($field->type) {
            case 'number':
            case 'integer':
            case 'decimal':
            case 'float':
                $rules[] = 'numeric';
                if ($field->min !== null) {
                    $rules[] = 'min:' . $field->min;
                }
                if ($field->max !== null) {
                    $rules[] = 'max:' . $field->max;
                }
                break;

            case 'boolean':
            case 'toggle':
                $rules[] = 'boolean';
                break;

            case 'select':
            case 'dropdown':
                if (!empty($field->options)) {
                    $allowedValues = array_column($field->options, 'value');
                    if (!empty($allowedValues)) {
                        $rules[] = 'in:' . implode(',', $allowedValues);
                    }
                }
                break;

            case 'multiselect':
            case 'tags':
                $rules[] = 'array';
                break;

            case 'textarea':
            case 'longtext':
                $rules[] = 'string';
                if ($field->max !== null) {
                    $rules[] = 'max:' . (int) $field->max;
                } else {
                    $rules[] = 'max:5000';
                }
                break;

            case 'text':
            case 'string':
            default:
                $rules[] = 'string';
                if ($field->max !== null) {
                    $rules[] = 'max:' . (int) $field->max;
                } else {
                    $rules[] = 'max:500';
                }
                break;
        }

        return $rules;
    }
}
