<?php

namespace App\Services\Wizard\Validation;

use Illuminate\Support\Arr;

/**
 * ValidationPipeline — Çoklu doğrulama adımlarını yürüten orkestra şefi.
 * 
 * Bu sınıf, veriyi bir dizi ValidatorInterface uygulayan sınıftan geçirir 
 * ve tüm hataları standardize ederek toplar.
 */
class ValidationPipeline
{
    /** @var ValidatorInterface[] */
    protected array $validators = [];

    /**
     * Pipeline'a yeni bir validator ekler.
     */
    public function addValidator(ValidatorInterface $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    /**
     * Tüm validatorleri sırayla çalıştırır.
     *
     * @param array $fieldDefinitions
     * @param array $data
     * @return array Toplanmış hata kümesi
     */
    public function validate(array $fieldDefinitions, array $data): array
    {
        $allErrors = [];

        foreach ($this->validators as $validator) {
            $errors = $validator->validate($fieldDefinitions, $data);
            
            // Hataları birleştir (Ayandaki hataları ezmeden ekle)
            foreach ($errors as $field => $messages) {
                if (!isset($allErrors[$field])) {
                    $allErrors[$field] = (array) $messages;
                } else {
                    $allErrors[$field] = array_merge($allErrors[$field], (array) $messages);
                }
            }
        }

        return $allErrors;
    }

    /**
     * Hataları Laravel ValidationException formatına uygun hale getirir veya 
     * direkt olarak true/false dönebilir.
     */
    public function isValid(array $fieldDefinitions, array $data): bool
    {
        return empty($this->validate($fieldDefinitions, $data));
    }
}
