<?php

namespace App\Services\Wizard\FieldEngine;

/**
 * SchemaValidationRuleGenerator — DB şemasından Laravel validasyon kuralları üretir.
 * 
 * Sadece tip kontrolü değil, aynı zamanda anlık form durumuna göre 
 * dinamik (required_if vb. karşılığı) kurallar oluşturur.
 */
class SchemaValidationRuleGenerator
{
    public function __construct(
        protected DependencyEvaluator $dependencyEvaluator
    ) {}

    /**
     * Tüm şemayı gezer ve geçerli veriye göre kuralları üretir.
     *
     * @param array $fieldDefinitions
     * @param array $currentData
     * @return array Laravel validation rules
     */
    public function generate(array $fieldDefinitions, array $currentData = []): array
    {
        $rules = [];

        foreach ($fieldDefinitions as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'text'; // context7-ignore

            if (!$name) {
                continue;
            }

            $fieldRules = [];

            // 1. Dinamik Required Kontrolü
            $isRequired = $this->dependencyEvaluator->isRequired($field, $currentData);
            $isVisible  = $this->dependencyEvaluator->isVisible($field, $currentData);

            // Eğer alan görünür değilse validasyona sokma veya nullable yap
            if (!$isVisible) {
                $rules[$name] = ['nullable'];
                continue;
            }

            $fieldRules[] = $isRequired ? 'required' : 'nullable';

            // 2. Tip Bazlı Kuralların Eklenmesi
            $fieldRules = array_merge($fieldRules, $this->resolveTypeRules($type, $field));

            $rules[$name] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Alan tipine ve opsiyonlarına göre spesifik kuralları çözer.
     */
    protected function resolveTypeRules(string $type, array $field): array
    {
        $options = $field['options'] ?? [];

        return match ($type) {
            'text', 'textarea' => $this->textRules($options),
            'number'           => $this->numberRules($options),
            'boolean', 'toggle' => ['boolean'],
            'select'           => $this->selectRules($options),
            'multiselect'      => ['array'],
            'email'            => ['email'],
            'date'             => ['date'],
            'url'              => ['url'],
            default            => ['string'],
        };
    }

    protected function textRules(array $options): array
    {
        $rules = ['string'];

        if (isset($options['min_length'])) {
            $rules[] = 'min:' . (int) $options['min_length'];
        }

        if (isset($options['max_length'])) {
            $rules[] = 'max:' . (int) $options['max_length'];
        }

        return $rules;
    }

    protected function numberRules(array $options): array
    {
        $rules = ['numeric'];

        if (isset($options['min'])) {
            $rules[] = 'min:' . $options['min'];
        }

        if (isset($options['max'])) {
            $rules[] = 'max:' . $options['max'];
        }

        return $rules;
    }

    protected function selectRules(array $options): array
    {
        $rules = ['string'];

        // Eğer 'items' (basit liste) veya 'choices' (value/label objesi) varsa 'in' kuralı ekle
        $allowed = [];

        if (!empty($options['items']) && is_array($options['items'])) {
            $allowed = $options['items'];
        } elseif (!empty($options['choices']) && is_array($options['choices'])) {
            $allowed = array_map(fn ($item) => is_array($item) ? ($item['value'] ?? null) : $item, $options['choices']);
            $allowed = array_filter($allowed, fn ($v) => $v !== null);
        }

        if (!empty($allowed)) {
            $rules[] = 'in:' . implode(',', $allowed);
        }

        return $rules;
    }
}
