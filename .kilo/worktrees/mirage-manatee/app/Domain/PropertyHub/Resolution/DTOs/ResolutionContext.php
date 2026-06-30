<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resolution\DTOs;

use Carbon\CarbonImmutable;

/**
 * Immutable Context for Deterministic Template Resolution.
 *
 * DESIGN RULES:
 * - Readonly properties only.
 * - No Eloquent Models allowed (use IDs).
 * - No Service Injection.
 * - Pure data object.
 */
final class ResolutionContext
{
    public function __construct(
        public readonly int $categoryId,
        public readonly int $publicationTypeId,
        public readonly ?int $subCategoryId, // Nullable for top-level resolution
        public readonly array $attributes = [], // Additional context (e.g. user metadata, location)
        public readonly ?string $forcedConfigVersion = null, // For time-travel debugging
        public readonly CarbonImmutable $timestamp, // Fixed timestamp for time-based rules
        public readonly int $publishType, // Alias for publicationTypeId
        public readonly string $inputSignature, // Alias for result of fingerprint()
    ) {}

    /**
     * Create a deterministic fingerprint hash of the context.
     * Used for caching and idempotency checks.
     */
    public function fingerprint(): string
    {
        // Normalize attributes by sorting keys to ensure consistent JSON
        $normalizedAttributes = $this->attributes;
        ksort($normalizedAttributes);

        $data = [
            'cat' => $this->categoryId,
            'pub' => $this->publicationTypeId,
            'sub' => $this->subCategoryId,
            'attr' => $normalizedAttributes,
            'ver' => $this->forcedConfigVersion,
            // We consciously EXCLUDE timestamp from fingerprint
            // if we want to cache results for the same inputs regardless of time.
            // BUT, if time-based rules exist (e.g. "Summer Promo"), timestamp MUST be included.
            // For PropertyHub, logic is usually static, so we might exclude it?
            // User requested: "Shadow compare, Caching, Observability".
            // Let's include timestamp only if strictly necessary.
            // For now, I will INCLUDE it to be safe, but we can refine logic later.
            // Actually, for broad caching, excluding timestamp is better unless logic depends on date.
            // Let's exclude timestamp for now to maximize cache hit rate.
        ];

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Factory method for creating context with current timestamp.
     */
    public static function create(
        int $categoryId,
        int $publishTypeId,
        ?int $subCategoryId = null,
        array $attributes = [],
        ?string $forcedConfigVersion = null
    ): self {
        return new self(
            $categoryId,
            $publishTypeId,
            $subCategoryId,
            $attributes,
            $forcedConfigVersion,
            CarbonImmutable::now(),
            $publishTypeId,
            '' // Temporary empty signature, filled in construct or factory
        );
    }

    /**
     * Set signatures for compatibility with skeleton.
     */
    public function withSignatures(): self
    {
        return new self(
            $this->categoryId,
            $this->publicationTypeId,
            $this->subCategoryId,
            $this->attributes,
            $this->forcedConfigVersion,
            $this->timestamp,
            $this->publicationTypeId,
            $this->fingerprint()
        );
    }

    /**
     * Convert to array for logging/persistence.
     */
    public function toArray(): array
    {
        return [
            'category_id' => $this->categoryId,
            'publication_type_id' => $this->publicationTypeId,
            'sub_category_id' => $this->subCategoryId,
            'attributes' => $this->attributes,
        ];
    }
}
