<?php

namespace App\Enums;

/**
 * TemsilYetkiTipi Enum
 *
 * Sprint 12D — Authorized representative authority type.
 */
enum TemsilYetkiTipi: string
{
    case FULL = 'FULL';
    case FINANCIAL = 'FINANCIAL';
    case OPERATIONAL = 'OPERATIONAL';
    case LEGAL = 'LEGAL';

    public function label(): string
    {
        return match ($this) {
            self::FULL => 'Tam Yetkili',
            self::FINANCIAL => 'Finansal Yetki',
            self::OPERATIONAL => 'Operasyonel Yetki',
            self::LEGAL => 'Hukuki Yetki',
        };
    }
}
