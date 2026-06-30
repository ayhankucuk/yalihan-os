<?php

namespace App\Services\Wizard\Validation\Validators;

use App\Services\Wizard\FieldEngine\DependencyEvaluator;
use App\Services\Wizard\Validation\ValidatorInterface;
use Illuminate\Support\Arr;

/**
 * DependencyRuleValidator — Bağımlılık (Dependency) kurallarını denetler.
 * 
 * Bir alanın görünür olduğu halde zorunlu (required) olup olmadığını 
 * veya görünür olmaması gerektiği halde dolu gelip gelmediğini kontrol eder.
 */
class DependencyRuleValidator implements ValidatorInterface
{
    public function __construct(
        protected DependencyEvaluator $dependencyEvaluator
    ) {}

    /**
     * @param array $fieldDefinitions
     * @param array $currentData
     * @return array
     */
    public function validate(array $fieldDefinitions, array $currentData): array
    {
        $errors = [];

        foreach ($fieldDefinitions as $field) {
            $slug = $field['slug'] ?? $field['name'] ?? null;
            if (!$slug) continue;

            $isVisible = $this->dependencyEvaluator->isVisible($field, $currentData);
            $isRequired = $this->dependencyEvaluator->isRequired($field, $currentData);
            $value = Arr::get($currentData, $slug);

            // 1. ZORUNLU ALAN KONTROLÜ
            // Alan görünürse ve bağımlılığa göre zorunluysa, değeri boş olamaz.
            if ($isVisible && $isRequired && $this->isEmpty($value)) {
                $errors[$slug] = ["{$field['name']} alanı bu seçim için zorunludur."];
            }

            // 2. YASAKLI ALAN KONTROLÜ (Safety Net)
            // Eğer alan görünür olmaması gerekiyorsa ama dolu geldiyse hata ver.
            // (Note: DraftService normalde bunu cleanup ile temizler ama validasyon sızmalara karşı durur)
            if (!$isVisible && !$this->isEmpty($value)) {
                $errors[$slug] = ["{$field['name']} alanı bu kapsama dahil değildir."];
            }
        }

        return $errors;
    }

    /**
     * Değerin boş olup olmadığını kontrol eder.
     */
    protected function isEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return empty($value);
        }
        return $value === null || $value === '' || $value === false;
    }
}
