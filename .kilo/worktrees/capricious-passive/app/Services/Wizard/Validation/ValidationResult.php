<?php

namespace App\Services\Wizard\Validation;

/**
 * ValidationResult — Doğrulama işleminin sonucunu taşıyan DTO.
 */
class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $fieldStatus = []
    ) {}

    /**
     * Dizi formatına dönüştürür (JSON response için).
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'field_status' => $this->fieldStatus,
            'updated_at' => now()->toIso8601String()
        ];
    }
}
