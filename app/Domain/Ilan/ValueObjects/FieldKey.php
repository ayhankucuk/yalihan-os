<?php

declare(strict_types=1);

namespace App\Domain\Ilan\ValueObjects;

use InvalidArgumentException;

/**
 * FieldKey — Value Object for Canonical Form Field Identifiers
 *
 * Enforces Context7 snake_case standards and normalizes legacy kebab-case aliases.
 */
final class FieldKey
{
    private const LEGACY_ALIASES = [
        'oda-sayisi'       => 'oda_sayisi',
        'banyo-sayisi'     => 'banyo_sayisi',
        'brut-metrekare'   => 'brut_m2',
        'net-metrekare'    => 'net_m2',
        'bina-yasi'        => 'bina_yasi',
        'tapu-durumu'      => 'tapu_durumu',
        'kredi-uygunlugu'  => 'krediye_uygunluk',
        'site-icerisinde'  => 'site_icerisinde',
        'denize-mesafe'    => 'denize_mesafe_m',
        'aidat'            => 'aidat_tutari',
        'esyali'           => 'esyali_durumu',
        'imar-durumu'      => 'imar_durumu',
        'alan-m2'          => 'net_m2',
        'kat'              => 'bulundugu_kat',
        'isitma'           => 'isitma_tipi',
        'balkon'           => 'balkon_durumu',
    ];

    private string $value;

    private function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        // Normalize legacy kebab-case alias if present
        if (isset(self::LEGACY_ALIASES[$normalized])) {
            $normalized = self::LEGACY_ALIASES[$normalized];
        }

        // Must be strict snake_case
        if (!preg_match('/^[a-z0-9_]+$/', $normalized)) {
            throw new InvalidArgumentException("Invalid field key '{$value}'. Must be alphanumeric snake_case.");
        }

        $this->value = $normalized;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
