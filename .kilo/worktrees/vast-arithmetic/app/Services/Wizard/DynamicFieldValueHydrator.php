<?php

namespace App\Services\Wizard;

/**
 * DynamicFieldValueHydrator — READ layer for Wizard Step 2 edit mode.
 *
 * Loads existing feature values from ilan_feature table and merges
 * them into the schema contract so the frontend can pre-populate fields.
 *
 * Flow: DB (ilan_feature) → schema fields enrichment → API response → Alpine.js pre-fill
 * Counterpart: DynamicFieldValueMapper handles WRITE (form → DB)
 */
class DynamicFieldValueHydrator
{
    public function __construct(
        private readonly DynamicFieldValueMapper $fieldMapper,
    ) {}

    /**
     * Hydrate schema fields with existing DB values for a listing.
     *
     * Adds `value` and `has_value` properties to each field in the schema.
     * Type-casts stored string values to appropriate frontend types.
     *
     * @param int $ilanId Listing ID
     * @param array $schema Schema from EffectiveWizardSchemaResolver::resolve()
     * @return array Schema with values merged into fields
     */
    public function hydrate(int $ilanId, array $schema): array
    {
        $existingValues = $this->fieldMapper->loadValues($ilanId);

        $schema['fields'] = array_map(function (array $field) use ($existingValues) {
            $slug = $field['slug'];

            if ($existingValues->has($slug)) {
                $field['value'] = $this->castValue(
                    $existingValues->get($slug),
                    $field['type'] ?? 'text' // context7-ignore
                );
                $field['has_value'] = true;
            } else {
                $field['value'] = null;
                $field['has_value'] = false;
            }

            return $field;
        }, $schema['fields']);

        return $schema;
    }

    /**
     * Cast stored string value to appropriate type for frontend consumption.
     *
     * ilan_feature.value is always stored as string. This method casts
     * back to the native type the frontend expects for x-model binding.
     *
     * @param string|null $value Stored string value
     * @param string $type Field type from schema (number, boolean, select, multiselect, text)
     * @return mixed Typed value for JSON response
     */
    private function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'number' => is_numeric($value) ? (float) $value : $value,
            'boolean' => in_array($value, ['1', 'true', 'yes'], true),
            'multiselect' => json_decode($value, true) ?? [],
            default => $value,
        };
    }
}
