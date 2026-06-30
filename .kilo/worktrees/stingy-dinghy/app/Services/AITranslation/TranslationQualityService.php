<?php

namespace App\Services\AITranslation;

/**
 * 🛡️ Translation Quality Service
 *
 * Responsibility: Validates the integrity of AI-generated translations.
 */
class TranslationQualityService
{
    /**
     * Checks if the translated content is high quality and matches source constraints.
     */
    public function validate(string $source, string $translated): array
    {
        $vDurum = 'passed';
        $warnings = [];

        if (empty($translated) || $translated === $source) {
            return ['islem_durumu' => 'failed', 'score' => 0, 'warnings' => ['Empty or original content returned']];
        }

        // 1. Number Integrity (Prices, m2, etc.)
        preg_match_all('/\d+/', $source, $sourceNumbers);
        preg_match_all('/\d+/', $translated, $targetNumbers);

        $diff = array_diff($sourceNumbers[0] ?? [], $targetNumbers[0] ?? []);

        if (!empty($diff)) {
            $vDurum = 'warning';
            $warnings[] = "Missing numbers: " . implode(', ', $diff);
        }

        // 2. Length check
        $sourceLen = mb_strlen($source);
        $targetLen = mb_strlen($translated);

        if ($targetLen < ($sourceLen * 0.4)) {
            $vDurum = 'failed';
            $warnings[] = "Translation is suspiciously short.";
        }

        return [
            'islem_durumu' => $vDurum,
            'score' => $vDurum === 'passed' ? 1.0 : 0.5,
            'warnings' => $warnings
        ];
    }
}
