<?php

namespace App\Enums;

/**
 * AI Description Draft Status
 *
 * Pipeline: taslak → onayli → uygulandi
 *              ↓
 *          reddedildi
 *
 * Status Flow:
 * 1. TASLAK         - AI draft oluşturuldu, owner review bekliyor
 * 2. ONAYLI         - Owner onayladı, uygulanmayı bekliyor
 * 3. UYGULANDI      - aciklama alanına yazıldı
 * 4. REDDEDILDI     - Owner reddetti, original data korundu
 */
enum AIDescriptionStatus: string
{
    case TASLAK = 'taslak';
    case ONAYLI = 'onayli';
    case UYGULANDI = 'uygulandi';
    case REDDEDILDI = 'reddedildi';

    /**
     * Human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::TASLAK => 'Taslak',
            self::ONAYLI => 'Onaylandı',
            self::UYGULANDI => 'Uygulandı',
            self::REDDEDILDI => 'Reddedildi',
        };
    }

    /**
     * Status color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::TASLAK => 'yellow',
            self::ONAYLI => 'green',
            self::UYGULANDI => 'blue',
            self::REDDEDILDI => 'red',
        };
    }

    /**
     * Can be edited by owner
     */
    public function isEditable(): bool
    {
        return $this === self::TASLAK;
    }

    /**
     * Can be approved
     */
    public function canApprove(): bool
    {
        return $this === self::TASLAK;
    }

    /**
     * Can be rejected
     */
    public function canReject(): bool
    {
        return $this === self::TASLAK;
    }
}
