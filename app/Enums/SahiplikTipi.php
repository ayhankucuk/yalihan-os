<?php

namespace App\Enums;

/**
 * SahiplikTipi Enum
 *
 * Sprint 12D — Property ownership type.
 */
enum SahiplikTipi: string
{
    case OWNER = 'OWNER';
    case BENEFICIAL_OWNER = 'BENEFICIAL_OWNER';
    case JOINT_OWNER = 'JOINT_OWNER';
    case REPRESENTATIVE = 'REPRESENTATIVE';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Malik',
            self::BENEFICIAL_OWNER => 'Gerçek Faydalanıcı',
            self::JOINT_OWNER => 'Ortak Malik',
            self::REPRESENTATIVE => 'Temsilci',
        };
    }
}
