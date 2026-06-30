<?php

namespace App\Services\Wizard;

/**
 * DependencyRuleEvaluator — Standalone dependency rule evaluation service.
 *
 * Evaluates single-level dependency rules of the form:
 *   {"field": "slug", "operator": "!=", "value": "yok"}
 *
 * Supported operators: =, !=, in, not_in, truthy, falsy
 *
 * Used by: EffectiveWizardSchemaResolver, DynamicFieldValueMapper.
 * Shared logic for both frontend (JS) and backend (PHP) evaluation symmetry.
 */
class DependencyRuleEvaluator
{
    /**
     * Evaluate a single dependency rule against form state.
     *
     * Returns true if:
     *  - rule is null/empty (no condition = always active)
     *  - rule is malformed (fail-safe = active)
     *  - source field not in schema (fail-safe = active)
     *  - condition is met
     *
     * @param array|null $rule {field, operator, value?}
     * @param array $formState {slug => value} current form data
     * @param array $knownSlugs List of valid field slugs in schema (optional)
     * @return bool True if condition is met (field should be visible/required/enabled)
     */
    public function evaluate(?array $rule, array $formState, array $knownSlugs = []): bool
    {
        if (empty($rule)) {
            return true;
        }

        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? null;

        if (!$field || !$operator) {
            return true; // Malformed rule = inactive (fail-safe)
        }

        // If known slugs provided and source field isn't in schema, treat as inactive
        if (!empty($knownSlugs) && !in_array($field, $knownSlugs, true)) {
            return true;
        }

        $currentValue = $formState[$field] ?? null;

        return match ($operator) {
            '=' => (string) ($currentValue ?? '') === (string) ($rule['value'] ?? ''),
            '!=' => (string) ($currentValue ?? '') !== (string) ($rule['value'] ?? ''),
            'in' => is_array($rule['value'] ?? null)
                && in_array((string) ($currentValue ?? ''), array_map('strval', $rule['value']), true),
            'not_in' => !is_array($rule['value'] ?? null)
                || !in_array((string) ($currentValue ?? ''), array_map('strval', $rule['value']), true),
            'truthy' => !empty($currentValue) && $currentValue !== '0' && $currentValue !== 'false',
            'falsy' => empty($currentValue) || $currentValue === '0' || $currentValue === 'false',
            default => true, // Unknown operator = inactive (fail-safe)
        };
    }

    /**
     * Check if a field should be visible based on its visible_if rule.
     *
     * @param array $field Field definition with optional 'visible_if' key
     * @param array $formState Current form data
     * @param array $knownSlugs Available field slugs in schema
     * @return bool True if field should be visible
     */
    public function isVisible(array $field, array $formState, array $knownSlugs = []): bool
    {
        return $this->evaluate($field['visible_if'] ?? null, $formState, $knownSlugs);
    }

    /**
     * Check if a field should be required based on its required_if rule.
     *
     * Note: This evaluates only the dynamic required_if condition.
     * The base 'required' flag must be checked separately.
     *
     * @param array $field Field definition with optional 'required_if' key
     * @param array $formState Current form data
     * @param array $knownSlugs Available field slugs in schema
     * @return bool True if field's required_if condition is met
     */
    public function isRequired(array $field, array $formState, array $knownSlugs = []): bool
    {
        $baseRequired = $field['required'] ?? false;
        if ($baseRequired) {
            return true;
        }

        return $this->evaluate($field['required_if'] ?? null, $formState, $knownSlugs);
    }

    /**
     * Check if a field should be enabled based on its enabled_if rule.
     *
     * @param array $field Field definition with optional 'enabled_if' key
     * @param array $formState Current form data
     * @param array $knownSlugs Available field slugs in schema
     * @return bool True if field should be enabled
     */
    public function isEnabled(array $field, array $formState, array $knownSlugs = []): bool
    {
        return $this->evaluate($field['enabled_if'] ?? null, $formState, $knownSlugs);
    }

    /**
     * Filter fields: return only those visible AND enabled for current form state.
     *
     * @param array $fields All schema fields
     * @param array $formState Current form data
     * @return array Active (visible + enabled) field slugs
     */
    public function getActiveSlugs(array $fields, array $formState): array
    {
        $knownSlugs = array_column($fields, 'slug');

        return collect($fields)
            ->filter(fn (array $field) =>
                $this->isVisible($field, $formState, $knownSlugs) &&
                $this->isEnabled($field, $formState, $knownSlugs)
            )
            ->pluck('slug')
            ->toArray();
    }
}
