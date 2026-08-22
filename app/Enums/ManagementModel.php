<?php

namespace App\Enums;

/**
 * ManagementModel — Yalıhan Property Management Agreement Types
 *
 * C3.1: Rental Management Financial Contract
 *
 * Represents the commercial agreement between Yalıhan and the property owner
 * for how Yalıhan manages the property (full service vs check-in/out only).
 *
 * These are the canonical values used for financial commission calculations.
 * Rates are stored as DECIMAL(5,4) = 0.1500 (fraction) internally.
 * Examples: 0.1500 = 15%, 0.1000 = 10%, 0.0000 = 0%
 *
 * FULL_MANAGEMENT  → Yalıhan operates all aspects (cleaning, guest comms, channel mgmt)
 * CHECKIN_CHECKOUT → Yalıhan handles only check-in and check-out
 * NONE            → No Yalıhan commission (owner self-manages)
 * CUSTOM          → Custom negotiated rate stored in custom_commission_rate
 *
 * Validation rules:
 *   - FULL_MANAGEMENT  → rate = 0.1500 (canonical)
 *   - CHECKIN_CHECKOUT → rate = 0.1000 (canonical)
 *   - NONE            → rate = 0.0000 (canonical)
 *   - CUSTOM          → custom_commission_rate must be set and > 0
 */
enum ManagementModel: string
{
    case FULL_MANAGEMENT  = 'FULL_MANAGEMENT';
    case CHECKIN_CHECKOUT = 'CHECKIN_CHECKOUT';
    case NONE            = 'NONE';
    case CUSTOM          = 'CUSTOM';

    /**
     * Canonical commission rates — fraction form matching repository standard.
     * Used when no custom rate is configured.
     */
    public const CANONICAL_RATES = [
        self::FULL_MANAGEMENT->value  => 0.1500,
        self::CHECKIN_CHECKOUT->value => 0.1000,
        self::NONE->value            => 0.0000,
    ];

    /**
     * Get the canonical rate for this model.
     * Returns null for CUSTOM (must use custom_commission_rate).
     */
    public function canonicalRate(): ?float
    {
        return self::CANONICAL_RATES[$this->value] ?? null;
    }

    /**
     * Whether this model requires a custom rate.
     * Only CUSTOM requires custom_commission_rate to be set.
     */
    public function requiresCustomRate(): bool
    {
        return $this === self::CUSTOM;
    }

    /**
     * Get rate as percentage string for display (e.g. "15%").
     */
    public function rateLabel(): string
    {
        $rate = $this->canonicalRate();
        if ($rate !== null) {
            return number_format($rate * 100, 0) . '%';
        }
        return 'Custom';
    }
}
