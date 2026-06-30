<?php

namespace App\Services\Wizard\Validation\Validators;

use App\Services\Wizard\FieldEngine\SchemaValidationRuleGenerator;
use App\Services\Wizard\Validation\ValidatorInterface;
use Illuminate\Support\Facades\Validator;

/**
 * TypeConstraintValidator — Veri tipi ve temel kısıtlamaları denetler.
 * 
 * Existing SchemaValidationRuleGenerator'ı kullanarak Laravel 
 * Validator üzerinden tip, min/max, email vb. kontrollerini yapar.
 */
class TypeConstraintValidator implements ValidatorInterface
{
    public function __construct(
        protected SchemaValidationRuleGenerator $ruleGenerator
    ) {}

    /**
     * @param array $fieldDefinitions
     * @param array $currentData
     * @return array
     */
    public function validate(array $fieldDefinitions, array $currentData): array
    {
        // 1. Şemaya göre kuralları üret
        $rules = $this->ruleGenerator->generate($fieldDefinitions, $currentData);

        // 2. Laravel Validator'ı çalıştır
        $validator = Validator::make($currentData, $rules);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return [];
    }
}
