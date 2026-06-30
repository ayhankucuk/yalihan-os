<?php

namespace App\Services\Wizard\FieldEngine;

use Illuminate\Support\Arr;

/**
 * DependencyEvaluator — Alanlar arası bağımlılıkları (Visibility/Required) yönetir.
 * 
 * Bir alanın görünür olup olmadığını, zorunlu olup olmadığını kontrol eder 
 * ve gizlenen alanların verilerini otomatik temizler.
 */
class DependencyEvaluator
{
    /**
     * Alanın mevcut form durumuna göre görünür olup olmadığını kontrol eder.
     *
     * @param array $fieldDefinition (name, dependencies vb.)
     * @param array $currentData
     * @return bool
     */
    public function isVisible(array $fieldDefinition, array $currentData): bool
    {
        $dependencies = $fieldDefinition['dependencies'] ?? [];

        $visibleRules = array_values(array_filter(
            $dependencies,
            fn ($rule) => ($rule['effect'] ?? 'visible') === 'visible'
        ));

        if (empty($visibleRules)) {
            return true;
        }

        return $this->evaluateRules($visibleRules, $currentData);
    }

    /**
     * Alanın mevcut form durumuna göre zorunlu olup olmadığını kontrol eder.
     *
     * @param array $fieldDefinition
     * @param array $currentData
     * @return bool
     */
    public function isRequired(array $fieldDefinition, array $currentData): bool
    {
        $baseRequired = (bool) ($fieldDefinition['is_required'] ?? false);
        $dependencies = $fieldDefinition['dependencies'] ?? [];

        $requiredRules = array_values(array_filter(
            $dependencies,
            fn ($rule) => ($rule['effect'] ?? null) === 'required'
        ));

        if (empty($requiredRules)) {
            return $baseRequired;
        }

        return $this->evaluateRules($requiredRules, $currentData);
    }

    /**
     * Görünürlük kurallarına uymayan (gizli kalması gereken) alanların verilerini temizler.
     *
     * @param array $fieldDefinitions
     * @param array $currentData
     * @return array Temizlenmiş veri kümesi
     */
    public function applyVisibilityCleanup(array $fieldDefinitions, array $currentData): array
    {
        foreach ($fieldDefinitions as $field) {
            $name = $field['name'] ?? null;

            if (!$name) {
                continue;
            }

            if (!$this->isVisible($field, $currentData)) {
                // Eğer alan görünür değilse verisini temizle (Normalizer)
                Arr::forget($currentData, $name);
            }
        }

        return $currentData;
    }

    /**
     * Kurallar setini ana veri kümesine karşı değerlendirir.
     */
    protected function evaluateRules(array $rules, array $currentData): bool
    {
        foreach ($rules as $rule) {
            $field    = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? '=';
            $expected = $rule['value'] ?? null;

            $actual = Arr::get($currentData, $field);

            if (!$this->compare($actual, $operator, $expected)) {
                return false;
            }
        }

        return true;
    }

    /**
     * İki değeri verilen operatöre göre karşılaştırır.
     */
    protected function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            '=', '=='  => $actual == $expected,
            '!=', '<>'  => $actual != $expected,
            '>'        => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            '<'        => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            '>='       => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            '<='       => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            'in'       => is_array($expected) && in_array($actual, $expected, true),
            'not_in'   => is_array($expected) && !in_array($actual, $expected, true),
            'filled'   => !blank($actual),
            'empty'    => blank($actual),
            'truthy'   => !empty($actual) && $actual !== '0' && $actual !== 'false' && $actual !== false,
            'falsy'    => empty($actual) || $actual === '0' || $actual === 'false' || $actual === false,
            default    => false,
        };
    }
}
