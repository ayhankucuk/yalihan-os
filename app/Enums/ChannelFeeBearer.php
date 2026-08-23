<?php

namespace App\Enums;

/**
 * ChannelFeeBearer — C4.1: Who bears the OTA/channel commission fee
 *
 * SAAB Decision: C4_POLICY_LOCKED_OWNER_BORNE
 *
 * - OWNER_BORNE:      OTA fee deducted from owner gross before Yalihan commission.
 *                      Formula: owner_payable = gross - channel_fee - yalihan_commission
 * - YALIHAN_BORNE:    Yalihan absorbs OTA fee as part of its margin.
 *                      Formula: owner_payable = gross - yalihan_commission (Yalihan pays OTA fee from its cut)
 * - COMMISSION_SHARE: OTA fee is shared between owner and Yalihan per negotiated terms.
 *                      Formula: configurable split; requires custom terms.
 *
 * Default for Yalıhan OS: OWNER_BORNE
 */
enum ChannelFeeBearer: string
{
    case OWNER_BORNE     = 'OWNER_BORNE';
    case YALIHAN_BORNE   = 'YALIHAN_BORNE';
    case COMMISSION_SHARE = 'COMMISSION_SHARE';

    /**
     * Whether this bearer model requires channel fee to be known before payout.
     * OWNER_BORNE: YES — channel fee reduces gross before owner payable.
     * YALIHAN_BORNE: NO — channel fee is Yalihan's problem, owner gets gross - commission.
     * COMMISSION_SHARE: YES — channel fee affects the split calculation.
     */
    public function requiresChannelFeeKnown(): bool
    {
        return match ($this) {
            self::OWNER_BORNE     => true,
            self::YALIHAN_BORNE   => false,
            self::COMMISSION_SHARE => true,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OWNER_BORNE     => ' Sahip Tarafından Karşılanır',
            self::YALIHAN_BORNE   => ' Yalıhan Tarafından Karşılanır',
            self::COMMISSION_SHARE => ' Komisyon Paylaşımı',
        };
    }
}
