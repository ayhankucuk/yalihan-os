<?php

namespace App\Services\Wizard\Validation;

/**
 * ValidatorInterface — Wizard doğrulayıcıları için standart kontrat.
 */
interface ValidatorInterface
{
    /**
     * Verilen veriyi şemaya göre doğrular.
     *
     * @param array $fieldDefinitions Şema tanımları
     * @param array $data Doğrulanacak veri kümesi (payload)
     * @return array Hata dizisi: ['field_name' => ['error message']]
     */
    public function validate(array $fieldDefinitions, array $currentData): array;
}
