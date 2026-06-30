<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Config Option Value Validation Rule
 *
 * Validates config option values based on option type
 * Context7: C7-CONFIG-OPTIONS-VALIDATION-2025-12-15
 */
class ConfigOptionValue implements Rule
{
    protected string $optionType;
    protected ?string $errorMessage = null;

    public function __construct(string $optionType)
    {
        $this->optionType = $optionType;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // JSON string ise decode et
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errorMessage = 'Geçersiz JSON formatı';
                return false;
            }
            $value = $decoded;
        }

        switch ($this->optionType) {
            case 'simple':
                return $this->validateSimple($value);
            case 'associative':
                return $this->validateAssociative($value);
            case 'object_array':
                return $this->validateObjectArray($value);
            case 'nested':
                return $this->validateNested($value);
            default:
                $this->errorMessage = 'Geçersiz option type';
                return false;
        }
    }

    /**
     * Validate simple array
     */
    protected function validateSimple($value): bool
    {
        if (!is_array($value)) {
            $this->errorMessage = 'Simple type için array bekleniyor';
            return false;
        }

        // Tüm değerler string veya numeric olmalı
        foreach ($value as $item) {
            if (!is_string($item) && !is_numeric($item)) {
                $this->errorMessage = 'Simple type için tüm değerler string veya numeric olmalı';
                return false;
            }
        }

        return true;
    }

    /**
     * Validate associative array
     */
    protected function validateAssociative($value): bool
    {
        if (!is_array($value)) {
            $this->errorMessage = 'Associative type için array bekleniyor';
            return false;
        }

        // Associative array kontrolü (numeric key'ler olmamalı)
        if (array_keys($value) !== range(0, count($value) - 1)) {
            // Bu bir associative array
            foreach ($value as $key => $val) {
                if (!is_string($key)) {
                    $this->errorMessage = 'Associative type için tüm key\'ler string olmalı';
                    return false;
                }
                if (!is_string($val) && !is_numeric($val)) {
                    $this->errorMessage = 'Associative type için tüm değerler string veya numeric olmalı';
                    return false;
                }
            }
            return true;
        }

        $this->errorMessage = 'Associative type için key-value çiftleri bekleniyor';
        return false;
    }

    /**
     * Validate object array
     */
    protected function validateObjectArray($value): bool
    {
        if (!is_array($value)) {
            $this->errorMessage = 'Object array type için array bekleniyor';
            return false;
        }

        // Her item bir object (associative array) olmalı
        foreach ($value as $index => $item) {
            if (!is_array($item)) {
                $this->errorMessage = "Object array item #{$index} bir object olmalı";
                return false;
            }

            // Numeric key kontrolü (associative array olmalı)
            if (array_keys($item) === range(0, count($item) - 1)) {
                $this->errorMessage = "Object array item #{$index} associative array olmalı";
                return false;
            }
        }

        return true;
    }

    /**
     * Validate nested structure
     */
    protected function validateNested($value): bool
    {
        if (!is_array($value)) {
            $this->errorMessage = 'Nested type için array bekleniyor';
            return false;
        }

        // Nested yapı için recursive validation
        return $this->validateNestedRecursive($value);
    }

    /**
     * Recursive nested validation
     */
    protected function validateNestedRecursive($value, $depth = 0): bool
    {
        if ($depth > 10) {
            $this->errorMessage = 'Nested yapı çok derin (max 10 seviye)';
            return false;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (!is_string($key) && !is_numeric($key)) {
                    $this->errorMessage = 'Nested yapıda geçersiz key tipi';
                    return false;
                }

                if (is_array($item)) {
                    if (!$this->validateNestedRecursive($item, $depth + 1)) {
                        return false;
                    }
                } elseif (!is_string($item) && !is_numeric($item) && !is_bool($item) && !is_null($item)) {
                    $this->errorMessage = 'Nested yapıda geçersiz değer tipi';
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->errorMessage ?? 'Geçersiz config option değeri';
    }
}
