<?php

declare(strict_types=1);

namespace App\Services\Telegram\Processors;

/**
 * FinanceValidationResult — R08 value object
 *
 * Immutable result of FinanceResponseValidator::validate().
 * Encapsulates validation outcome, data, and review requirements.
 */
final class FinanceValidationResult
{
    private function __construct(
        public readonly bool $isValid,
        public readonly bool $aiReviewRequired,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
        public readonly ?array $data,
        public readonly ?string $reviewReason,
    ) {}

    /**
     * Create a valid result
     */
    public static function valid(array $data): self
    {
        return new self(
            isValid: true,
            aiReviewRequired: false,
            errorCode: null,
            errorMessage: null,
            data: $data,
            reviewReason: null,
        );
    }

    /**
     * Create a valid result that still requires human review
     */
    public static function validWithReviewFlag(array $data, string $reviewCode, string $reviewReason): self
    {
        return new self(
            isValid: true,
            aiReviewRequired: true,
            errorCode: null,
            errorMessage: null,
            data: $data,
            reviewReason: "{$reviewCode}: {$reviewReason}",
        );
    }

    /**
     * Create an invalid result (validation failed)
     */
    public static function invalid(string $errorCode, string $errorMessage, ?array $data): self
    {
        return new self(
            isValid: false,
            aiReviewRequired: true,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            data: $data,
            reviewReason: "VALIDATION_FAILED: {$errorCode}",
        );
    }
}
