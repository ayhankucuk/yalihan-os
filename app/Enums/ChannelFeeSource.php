<?php

namespace App\Enums;

/**
 * ChannelFeeSource — C4.1: Provenance of channel fee data
 *
 * Priority chain for source-of-truth:
 *   1. PROVIDER_REPORTED  — captured from OTA payout report / API (most reliable)
 *   2. PROPERTY_CONFIG    — channel-specific configuration on the property record
 *   3. EXPLICIT_RULE     — admin-configured channel rule snapshot at booking time
 *   4. UNKNOWN           — no reliable source; payout readiness BLOCKED
 *
 * C4.1 Invariant 2:
 *   UNKNOWN source → system does NOT guess → payout readiness blocked
 *   This is NOT the same as a C5 settlement operation.
 *   This is the completeness gate that prevents premature payout.
 */
enum ChannelFeeSource: string
{
    case PROVIDER_REPORTED = 'PROVIDER_REPORTED';
    case PROPERTY_CONFIG   = 'PROPERTY_CONFIG';
    case EXPLICIT_RULE     = 'EXPLICIT_RULE';
    case UNKNOWN           = 'UNKNOWN';

    /**
     * Whether this source level is sufficient for payout readiness.
     * Only confirmed sources (PROVIDER_REPORTED) reach payout readiness.
     * PROPERTY_CONFIG and EXPLICIT_RULE are estimates until confirmed.
     */
    public function isSufficientForPayoutReadiness(): bool
    {
        return match ($this) {
            // PROVIDER_REPORTED: directly from OTA payout — confirmed
            self::PROVIDER_REPORTED => true,
            // PROPERTY_CONFIG: configured rate — may differ from actual
            // EXPLICIT_RULE: admin snapshot — may differ from actual
            // Both need C5 reconciliation before payout
            self::PROPERTY_CONFIG,
            self::EXPLICIT_RULE,
            self::UNKNOWN           => false,
        };
    }

    /**
     * Whether this source should be treated as a hard estimate vs. soft estimate.
     * Hard estimates should be flagged for reconciliation.
     */
    public function requiresReconciliation(): bool
    {
        return match ($this) {
            self::PROPERTY_CONFIG,
            self::EXPLICIT_RULE     => true,
            self::PROVIDER_REPORTED,
            self::UNKNOWN           => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PROVIDER_REPORTED => 'OTA Raporundan Bildirildi',
            self::PROPERTY_CONFIG   => 'Mülk Konfigürasyonu',
            self::EXPLICIT_RULE     => 'Açık Kanal Kuralı',
            self::UNKNOWN           => 'Bilinmiyor',
        };
    }
}
