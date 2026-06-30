<?php

namespace App\Services\Wizard;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DynamicFieldValueMapper — Maps and persists wizard Step 2 dynamic field values.
 *
 * Responsibilities:
 * 1. Normalize values by field type (number, boolean, text, select, multiselect)
 * 2. Enforce schema whitelist (only schema-allowed fields written to DB)
 * 3. Enforce option whitelist for select/multiselect fields
 * 4. Persist to ilan_feature pivot table
 *
 * Used by: IlanWizardController (save step 2), StoreIlanRequest (validate step 2).
 */
class DynamicFieldValueMapper
{
    public function __construct(
        private readonly EffectiveWizardSchemaResolver $schemaResolver,
        private readonly DependencyRuleEvaluator $dependencyEvaluator = new DependencyRuleEvaluator(),
    ) {}

    /**
     * Map, validate, and persist feature values for a listing.
     *
     * @param int $ilanId Listing ID
     * @param array $submittedFeatures Key-value pairs: {slug: value}
     * @param int $kategoriId Category ID
     * @param int $yayinTipiId Publication Type ID
     * @return array Summary of mapped values
     */
    public function mapAndSave(int $ilanId, array $submittedFeatures, int $kategoriId, int $yayinTipiId): array
    {
        $schema = $this->schemaResolver->resolve($kategoriId, $yayinTipiId);
        $allowedFields = collect($schema['fields'])->keyBy('slug');

        // Dependency-aware: determine which fields are active given current payload
        $activeSlugs = $this->dependencyEvaluator->getActiveSlugs(
            $schema['fields'],
            $submittedFeatures
        );

        $mappedValues = [];
        $skipped = [];

        foreach ($submittedFeatures as $slug => $rawValue) {
            // Schema whitelist enforcement: skip unknown fields
            if (!$allowedFields->has($slug)) {
                $skipped[] = $slug;
                continue;
            }

            // Dependency-aware skip: invisible/disabled fields not persisted
            if (!in_array($slug, $activeSlugs, true)) {
                $skipped[] = $slug;
                continue;
            }

            $fieldDef = $allowedFields->get($slug);
            $normalizedValue = $this->normalizeValue($rawValue, $fieldDef);

            // Option whitelist enforcement for select/multiselect
            if (in_array($fieldDef['type'], ['select', 'multiselect'])) { // context7-ignore
                if (!$this->isValidOption($normalizedValue, $fieldDef)) {
                    $skipped[] = $slug;
                    continue;
                }
            }

            $mappedValues[] = [
                'feature_id' => $fieldDef['feature_id'],
                'slug' => $slug,
                'value' => $normalizedValue,
            ];
        }

        // Persist to DB
        $this->persist($ilanId, $mappedValues);

        if (!empty($skipped)) {
            Log::info('DynamicFieldValueMapper: Skipped non-schema fields', [
                'ilan_id' => $ilanId,
                'skipped' => $skipped,
            ]);
        }

        return [
            'saved_count' => count($mappedValues),
            'skipped_count' => count($skipped),
            'skipped_fields' => $skipped,
        ];
    }

    /**
     * Normalize a raw value based on field type.
     *
     * @param mixed $rawValue Raw input value
     * @param array $fieldDef Field definition from schema
     * @return string|null Normalized string value for storage
     */
    public function normalizeValue(mixed $rawValue, array $fieldDef): ?string
    {
        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        $type = $fieldDef['type'] ?? 'text'; // context7-ignore

        return match ($type) {
            'number' => $this->normalizeNumber($rawValue),
            'boolean' => $this->normalizeBoolean($rawValue),
            'select' => $this->normalizeSelect($rawValue),
            'multiselect' => $this->normalizeMultiselect($rawValue),
            default => $this->normalizeText($rawValue),
        };
    }

    /**
     * Normalize numeric value.
     */
    private function normalizeNumber(mixed $value): ?string
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Normalize boolean value.
     */
    private function normalizeBoolean(mixed $value): string
    {
        $truthy = ['1', 'true', 'yes', 'evet', 'on'];

        return in_array(strtolower((string) $value), $truthy, true) ? '1' : '0';
    }

    /**
     * Normalize select value.
     */
    private function normalizeSelect(mixed $value): ?string
    {
        if (is_array($value)) {
            return null; // Select must be scalar
        }

        return trim((string) $value);
    }

    /**
     * Normalize multiselect value (stored as JSON).
     */
    private function normalizeMultiselect(mixed $value): ?string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return json_encode($decoded);
            }
            return json_encode([$value]);
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return null;
    }

    /**
     * Normalize text value.
     */
    private function normalizeText(mixed $value): string
    {
        return trim((string) $value);
    }

    /**
     * Check if a value is within the allowed options for select/multiselect.
     *
     * @param string|null $normalizedValue Normalized value
     * @param array $fieldDef Field definition with options
     * @return bool True if value is valid
     */
    private function isValidOption(?string $normalizedValue, array $fieldDef): bool
    {
        if ($normalizedValue === null) {
            return !($fieldDef['required'] ?? false);
        }

        $allowedValues = collect($fieldDef['options'] ?? [])
            ->pluck('value')
            ->toArray();

        if (empty($allowedValues)) {
            return true; // No options defined = no restriction
        }

        $type = $fieldDef['type'] ?? 'select'; // context7-ignore

        if ($type === 'multiselect') {
            $decoded = json_decode($normalizedValue, true);
            if (!is_array($decoded)) {
                return false;
            }
            return empty(array_diff($decoded, $allowedValues));
        }

        return in_array($normalizedValue, $allowedValues, true);
    }

    /**
     * Persist mapped values to ilan_feature table.
     *
     * Uses upsert pattern: delete existing + insert new.
     *
     * @param int $ilanId Listing ID
     * @param array $mappedValues Array of [feature_id, slug, value]
     */
    private function persist(int $ilanId, array $mappedValues): void
    {
        if (empty($mappedValues)) {
            return;
        }

        DB::transaction(function () use ($ilanId, $mappedValues) {
            $featureIds = collect($mappedValues)->pluck('feature_id')->toArray();

            // Delete existing values for these features
            DB::table('ilan_feature')
                ->where('ilan_id', $ilanId)
                ->whereIn('feature_id', $featureIds)
                ->delete();

            // Insert new values
            $rows = collect($mappedValues)
                ->filter(fn ($v) => $v['value'] !== null)
                ->map(fn ($v) => [
                    'ilan_id' => $ilanId,
                    'feature_id' => $v['feature_id'],
                    'value' => $v['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->toArray();

            if (!empty($rows)) {
                DB::table('ilan_feature')->insert($rows);
            }
        });
    }

    /**
     * Load existing feature values for a listing (raw strings).
     *
     * @param int $ilanId Listing ID
     * @return Collection Keyed by feature slug: {slug => value}
     */
    public function loadValues(int $ilanId): Collection
    {
        return DB::table('ilan_feature')
            ->join('features', 'features.id', '=', 'ilan_feature.feature_id')
            ->where('ilan_feature.ilan_id', $ilanId)
            ->select('features.slug', 'ilan_feature.value')
            ->get()
            ->pluck('value', 'slug');
    }

    /**
     * Load existing feature values with type-aware casting.
     *
     * Joins with features table to get the type, then casts the stored
     * string value to the appropriate PHP/JSON type for frontend consumption.
     *
     * @param int $ilanId Listing ID
     * @return array Associative array: {slug => castValue}
     */
    public function loadCastValues(int $ilanId): array
    {
        return DB::table('ilan_feature')
            ->join('features', 'features.id', '=', 'ilan_feature.feature_id')
            ->where('ilan_feature.ilan_id', $ilanId)
            ->select('features.slug', 'features.type', 'ilan_feature.value')
            ->get()
            ->mapWithKeys(function ($row) {
                return [$row->slug => $this->castValue($row->value, $row->type)];
            })
            ->toArray();
    }

    /**
     * Cast stored string value to appropriate type for frontend consumption.
     *
     * ilan_feature.value is always stored as string. This casts back to the
     * native type the frontend expects for x-model binding.
     *
     * @param string|null $value Stored string value
     * @param string $type Field type from features table
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
