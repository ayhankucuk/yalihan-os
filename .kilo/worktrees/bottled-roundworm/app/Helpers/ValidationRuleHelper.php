<?php

/**
 * Validation Rule Helper - Merkezi Validation Rules Yönetimi
 *
 * Context7 Standard: C7-VALIDATION-RULE-HELPER-2025-12-06
 *
 * Merkezi validation rules config'den rules alır ve kullanır.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

namespace App\Helpers;

class ValidationRuleHelper
{
    /**
     * Validation rules'ı al
     *
     * @param string $path Dot notation path (örn: 'user.store')
     * @param array $replacements Placeholder replacements (örn: ['{id}' => $userId])
     * @return array Validation rules
     */
    public static function get(string $path, array $replacements = []): array
    {
        $rules = config("validation-rules.{$path}", []);

        if (empty($rules)) {
            return [];
        }

        // Placeholder replacements
        if (!empty($replacements)) {
            $rules = self::replacePlaceholders($rules, $replacements);
        }

        return $rules;
    }

    /**
     * Common validation rules'ı al
     *
     * @param string $ruleName Common rule ismi (örn: 'email')
     * @return string|array Validation rule
     */
    public static function getCommon(string $ruleName)
    {
        return config("validation-rules.common.{$ruleName}", '');
    }

    /**
     * Frontend validation hints al
     *
     * @param string $fieldName Field ismi (örn: 'email')
     * @return array|null Validation hints
     */
    public static function getHints(string $fieldName): ?array
    {
        return config("validation-rules.hints.{$fieldName}", null);
    }

    /**
     * Validation rules'ı merge et
     *
     * @param array $baseRules Base rules
     * @param array $additionalRules Additional rules
     * @return array Merged rules
     */
    public static function merge(array $baseRules, array $additionalRules): array
    {
        return array_merge($baseRules, $additionalRules);
    }

    /**
     * Placeholder'ları replace et
     *
     * @param array $rules Validation rules
     * @param array $replacements Replacements
     * @return array Rules with replacements
     */
    protected static function replacePlaceholders(array $rules, array $replacements): array
    {
        foreach ($rules as $key => $value) {
            if (is_string($value)) {
                $rules[$key] = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $value
                );
            } elseif (is_array($value)) {
                $rules[$key] = self::replacePlaceholders($value, $replacements);
            }
        }

        return $rules;
    }

    /**
     * Validation rules'ı Laravel formatına çevir
     *
     * @param string $path Dot notation path
     * @param array $replacements Placeholder replacements
     * @return array Laravel validation rules
     */
    public static function toLaravel(string $path, array $replacements = []): array
    {
        $rules = self::get($path, $replacements);

        // Laravel validation format'ına çevir
        $laravelRules = [];
        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                $laravelRules[$field] = $rule;
            } elseif (is_array($rule)) {
                $laravelRules[$field] = implode('|', $rule);
            }
        }

        return $laravelRules;
    }

    /**
     * Validation rules'ı JSON formatında al (API için)
     *
     * @param string $path Dot notation path
     * @param array $replacements Placeholder replacements
     * @return array JSON formatında validation rules
     */
    public static function toJson(string $path, array $replacements = []): array
    {
        $rules = self::get($path, $replacements);
        $hints = [];

        // Frontend hints ekle
        foreach ($rules as $field => $rule) {
            $hint = self::getHints($field);
            if ($hint) {
                $hints[$field] = $hint;
            }
        }

        return [
            'rules' => $rules,
            'hints' => $hints,
        ];
    }
}
