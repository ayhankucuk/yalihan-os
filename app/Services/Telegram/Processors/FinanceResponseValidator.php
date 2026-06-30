<?php

declare(strict_types=1);

namespace App\Services\Telegram\Processors;

use Illuminate\Support\Facades\Log;

/**
 * FinanceResponseValidator - R08 FinanceProcessor AI Safety
 *
 * Validates AI-generated financial responses with two layers:
 * 1. Schema validation (structure, required fields, types)
 * 2. Business rule validation (values, ranges, consistency)
 *
 * Never trusts AI output directly. Every response must pass both layers.
 */
class FinanceResponseValidator
{
    private const VALID_CURRENCIES = ['TRY', 'USD', 'EUR', 'GBP'];

    private const VALID_TYPES = ['gelir', 'gider', 'komisyon', 'masraf', 'odeme'];

    /** Maximum safe amount in TRY (100 billion) */
    private const MAX_AMOUNT = 100_000_000_000;

    /** Minimum meaningful amount */
    private const MIN_AMOUNT = 0.01;

    /**
     * Validate AI finance response against schema + business rules.
     *
     * @param mixed $data Raw AI response (may be null, string, array)
     * @return FinanceValidationResult
     */
    public function validate(mixed $data): FinanceValidationResult
    {
        // Layer 1: Schema validation
        $schemaResult = $this->validateSchema($data);
        if (!$schemaResult->isValid) {
            return $schemaResult;
        }

            // Layer 2: Business rule validation
            return $this->validateBusinessRules($schemaResult->data ?? []);
    }

    /**
     * Layer 1: Schema validation
     */
    private function validateSchema(mixed $data): FinanceValidationResult
    {
        // Not an array at all
        if (!is_array($data)) {
            return FinanceValidationResult::invalid(
                'SCHEMA_ERROR',
                'AI response is not valid JSON or not an object.',
                null
            );
        }

        // Required field: miktar
        if (!isset($data['miktar'])) {
            return FinanceValidationResult::invalid(
                'SCHEMA_MISSING_MIKTAR',
                'Required field "miktar" is missing from AI response.',
                null
            );
        }

        // miktar must be numeric
        if (!is_numeric($data['miktar'])) {
            return FinanceValidationResult::invalid(
                'SCHEMA_INVALID_MIKTAR_TYPE',
                'Field "miktar" must be a number.',
                null
            );
        }

        // Required field: para_birimi
        if (!isset($data['para_birimi'])) {
            return FinanceValidationResult::invalid(
                'SCHEMA_MISSING_PARA_BIRIMI',
                'Required field "para_birimi" is missing from AI response.',
                null
            );
        }

        // Required field: islem_tipi
        if (!isset($data['islem_tipi'])) {
            return FinanceValidationResult::invalid(
                'SCHEMA_MISSING_ISLEM_TIPI',
                'Required field "islem_tipi" is missing from AI response.',
                null
            );
        }

        // Optional but normalize
        if (!isset($data['aciklama'])) {
            $data['aciklama'] = 'Telegram üzerinden eklendi';
        }

        return FinanceValidationResult::valid($data);
    }

    /**
     * Layer 2: Business rule validation
     */
    private function validateBusinessRules(array $data): FinanceValidationResult
    {
        $miktar = (float) $data['miktar'];
        $paraBirimi = strtoupper((string) ($data['para_birimi'] ?? 'TRY'));
        $islemTipi = strtolower((string) ($data['islem_tipi'] ?? ''));
        $aciklama = trim((string) ($data['aciklama'] ?? ''));

        // Rule 1: Amount must be positive and within safe range
        if ($miktar <= 0) {
            return FinanceValidationResult::invalid(
                'BUSINESS_NEGATIVE_AMOUNT',
                "Amount must be positive. Got: {$miktar}",
                null
            );
        }

        if ($miktar > self::MAX_AMOUNT) {
            return FinanceValidationResult::invalid(
                'BUSINESS_EXCESSIVE_AMOUNT',
                "Amount exceeds maximum safe value of " . self::MAX_AMOUNT . ". Got: {$miktar}",
                null
            );
        }

        if ($miktar < self::MIN_AMOUNT) {
            return FinanceValidationResult::invalid(
                'BUSINESS_TOO_SMALL_AMOUNT',
                "Amount is below minimum meaningful value of " . self::MIN_AMOUNT . ". Got: {$miktar}",
                null
            );
        }

        // Rule 2: Currency must be valid
        if (!in_array($paraBirimi, self::VALID_CURRENCIES, true)) {
            return FinanceValidationResult::invalid(
                'BUSINESS_INVALID_CURRENCY',
                "Unsupported currency: {$paraBirimi}. Valid: " . implode(', ', self::VALID_CURRENCIES),
                null
            );
        }

        // Rule 3: Transaction type must be valid
        if (!in_array($islemTipi, self::VALID_TYPES, true)) {
            return FinanceValidationResult::invalid(
                'BUSINESS_INVALID_TYPE',
                "Unsupported transaction type: {$islemTipi}. Valid: " . implode(', ', self::VALID_TYPES),
                null
            );
        }

        // Rule 4: Description must not be empty or suspiciously generic
        if (strlen($aciklama) < 2) {
            return FinanceValidationResult::invalid(
                'BUSINESS_EMPTY_DESCRIPTION',
                'Description is too short or empty.',
                null
            );
        }

        // Rule 5: AI-only auto-approval signal detected — flag for review
        // Match keyword stems (partial words allowed since Turkish has suffixes)
        $autoApprovalKeywords = [
            'tahmin',
            'öner', 'oneri',
            'otomatik',
            'ai',
            'tavsiye',
            'karar',
        ];

        $lower = mb_strtolower($aciklama);
        foreach ($autoApprovalKeywords as $keyword) {
            if (mb_strpos($lower, $keyword) !== false) {
                if (app()->bound('log')) {
                    Log::warning('FinanceResponseValidator: AI auto-approval signal detected', [
                        'keyword' => $keyword,
                        'aciklama' => $aciklama,
                    ]);
                }
                return FinanceValidationResult::validWithReviewFlag(
                    $this->normalizeData($data),
                    'AI_AUTO_APPROVAL_SIGNAL',
                    "Description contains auto-approval signal: '{$keyword}'"
                );
            }
        }

        return FinanceValidationResult::valid($this->normalizeData($data));
    }

    /**
     * Normalize and type-cast validated data
     */
    private function normalizeData(array $data): array
    {
        return [
            'miktar' => round((float) $data['miktar'], 2),
            'para_birimi' => strtoupper((string) ($data['para_birimi'] ?? 'TRY')),
            'islem_tipi' => strtolower((string) ($data['islem_tipi'] ?? 'gider')),
            'aciklama' => trim((string) ($data['aciklama'] ?? 'Telegram üzerinden eklendi')),
        ];
    }
}
