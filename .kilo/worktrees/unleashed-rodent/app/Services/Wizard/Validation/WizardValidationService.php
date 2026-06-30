<?php

namespace App\Services\Wizard\Validation;

use App\Services\Wizard\Validation\Validators\DependencyRuleValidator;
use App\Services\Wizard\Validation\Validators\TypeConstraintValidator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * WizardValidationService — Doğrulama boru hattını inşa eder ve çalıştırır.
 */
class WizardValidationService
{
    public function __construct(
        protected DependencyRuleValidator $dependencyValidator,
        protected TypeConstraintValidator $typeValidator,
    ) {}

    /**
     * Boru hattını kurar ve veriyi doğrular (Exception fırlatır).
     * 
     * @throws ValidationException
     */
    public function validate(array $fieldDefinitions, array $data): void
    {
        $result = $this->check($fieldDefinitions, $data);

        if (!$result->isValid) {
            throw ValidationException::withMessages($result->errors);
        }
    }

    /**
     * Boru hattını kurar ve sonucu döner (Exception fırlatmaz).
     */
    public function check(array $fieldDefinitions, array $data): ValidationResult
    {
        $pipeline = new ValidationPipeline();
        
        $pipeline->addValidator($this->dependencyValidator);
        $pipeline->addValidator($this->typeValidator);

        $errors = $pipeline->validate($fieldDefinitions, $data);

        // Alan bazlı durumları (field_status) oluştur
        $fieldStatus = [];
        foreach ($fieldDefinitions as $field) {
            $slug = $field['slug'] ?? $field['name'] ?? null;
            if (!$slug) continue;

            $fieldErrors = $errors[$slug] ?? null;
            $fieldStatus[$slug] = [
                'valid' => empty($fieldErrors),
                'error' => $fieldErrors ? Arr::first($fieldErrors) : null
            ];
        }

        return new ValidationResult(
            isValid: empty($errors),
            errors: $errors,
            fieldStatus: $fieldStatus
        );
    }
}
