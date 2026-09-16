<?php

declare(strict_types=1);

namespace App\Domain\Ilan\ValueObjects;

use InvalidArgumentException;

/**
 * ValidationRule — Value Object for Field Validation Specifications
 *
 * Pure domain value object without DB or framework coupling.
 */
final class ValidationRule
{
    private const ALLOWED_TYPES = [
        'text',
        'number',
        'select',
        'boolean',
        'currency',
    ];

    private string $type;
    private bool $required;
    private array $options;
    private ?int $min;
    private ?int $max;
    private ?int $step;
    private ?string $unit;

    public function __construct(
        string $type,
        bool $required = false,
        array $options = [],
        ?int $min = null,
        ?int $max = null,
        ?int $step = null,
        ?string $unit = null
    ) {
        $type = strtolower(trim($type));
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported field validation type '{$type}'.");
        }

        $this->type = $type;
        $this->required = $required;
        $this->options = $options;
        $this->min = $min;
        $this->max = $max;
        $this->step = $step;
        $this->unit = $unit;
    }

    public static function create(
        string $type,
        bool $required = false,
        array $options = [],
        ?int $min = null,
        ?int $max = null,
        ?int $step = null,
        ?string $unit = null
    ): self {
        return new self($type, $required, $options, $min, $max, $step, $unit);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function min(): ?int
    {
        return $this->min;
    }

    public function max(): ?int
    {
        return $this->max;
    }

    public function step(): ?int
    {
        return $this->step;
    }

    public function unit(): ?string
    {
        return $this->unit;
    }

    /**
     * Validate a value against this rule.
     * Returns true if valid, false otherwise.
     */
    public function isValid(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return !$this->required;
        }

        return match ($this->type) {
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true),
            'number' => is_numeric($value)
                && ($this->min === null || (float)$value >= $this->min)
                && ($this->max === null || (float)$value <= $this->max),
            'currency' => is_numeric($value) && (float)$value >= 0,
            'select' => empty($this->options) || in_array((string)$value, array_map('strval', $this->options), true),
            'text' => is_string($value),
            default => true,
        };
    }

    public function toArray(): array
    {
        return array_filter([
            'type'     => $this->type,
            'required' => $this->required,
            'options'  => $this->options,
            'min'      => $this->min,
            'max'      => $this->max,
            'step'     => $this->step,
            'unit'     => $this->unit,
        ], fn($v) => $v !== null && $v !== []);
    }
}
