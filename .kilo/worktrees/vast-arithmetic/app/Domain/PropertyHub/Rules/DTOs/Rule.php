<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Rules\DTOs;

/**
 * Domain Rule DTO
 *
 * Represents a single rule loaded from the database.
 */
final class Rule
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $ruleType,
        public readonly array $config, // Decoded JSON
        public readonly int $priority,
        public readonly bool $isActive,
    ) {}

    /**
     * Get conditions from rule config.
     */
    public function getConditions(): array
    {
        return $this->config['conditions'] ?? [];
    }

    /**
     * Get actions from rule config.
     */
    public function getActions(): array
    {
        return $this->config['actions'] ?? [];
    }
}
