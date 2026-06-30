<?php

namespace App\Enums;

/**
 * AI Description Draft Status
 *
 * Pipeline Flow:
 *  uretildi → bekliyor → onayli → uygulandi
 *                ↓
 *           reddedildi
 *                ↓
 *           duzeltildi → onayli
 *
 * Status Definitions:
 * 1. URETILDI       - AI draft üretildi, owner'a gönderilmedi
 * 2. BEKLEMEDE_ONAY - Owner'a gönderildi, review bekliyor
 * 3. ONAYLI         - Owner onayladı, uygulanmayı bekliyor
 * 4. UYGULANDI      - aciklama alanına yazıldı
 * 5. REDDEDILDI     - Owner reddetti
 * 6. DUZELTILDI     - Owner tarafından düzeltildi, tekrar onay bekliyor
 */
enum AIDescriptionStatus: string
{
    case URETILDI = 'uretildi';
    case BEKLEMEDE_ONAY = 'bekliyor';
    case ONAYLI = 'onayli';
    case UYGULANDI = 'uygulandi';
    case REDDEDILDI = 'reddedildi';
    case DUZELTILDI = 'duzeltildi';

    /**
     * Human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::URETILDI => 'Üretildi',
            self::BEKLEMEDE_ONAY => 'Onay Bekliyor',
            self::ONAYLI => 'Onaylandı',
            self::UYGULANDI => 'Uygulandı',
            self::REDDEDILDI => 'Reddedildi',
            self::DUZELTILDI => 'Düzeltildi',
        };
    }

    /**
     * Status color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::URETILDI => 'gray',
            self::BEKLEMEDE_ONAY => 'yellow',
            self::ONAYLI => 'green',
            self::UYGULANDI => 'blue',
            self::REDDEDILDI => 'red',
            self::DUZELTILDI => 'orange',
        };
    }

    /**
     * Pipeline step index (for progress display)
     */
    public function step(): int
    {
        return match ($this) {
            self::URETILDI => 1,
            self::BEKLEMEDE_ONAY => 2,
            self::ONAYLI => 3,
            self::UYGULANDI => 4,
            self::REDDEDILDI => 5,
            self::DUZELTILDI => 6,
        };
    }

    /**
     * Can be edited by owner
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::URETILDI, self::BEKLEMEDE_ONAY, self::DUZELTILDI]);
    }

    /**
     * Can be approved
     */
    public function canApprove(): bool
    {
        return in_array($this, [self::URETILDI, self::BEKLEMEDE_ONAY, self::DUZELTILDI]);
    }

    /**
     * Can be rejected
     */
    public function canReject(): bool
    {
        return in_array($this, [self::URETILDI, self::BEKLEMEDE_ONAY, self::DUZELTILDI]);
    }

    /**
     * Is in terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::UYGULANDI, self::REDDEDILDI]);
    }
}