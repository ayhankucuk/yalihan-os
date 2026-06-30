<?php

namespace App\Enums;

enum TeklifDurumu: string
{
    case BEKLEMEDE = 'beklemede';
    case KABUL_EDILDI = 'kabul_edildi';
    case REDDEDILDI = 'reddedildi';
    case REVIZE_ISTENDI = 'revize_istendi';
    case IPTAL_EDILDI = 'iptal_edildi';

    public function label(): string
    {
        return match($this) {
            self::BEKLEMEDE => 'Beklemede',
            self::KABUL_EDILDI => 'Kabul Edildi',
            self::REDDEDILDI => 'Reddedildi',
            self::REVIZE_ISTENDI => 'Revize İstendi',
            self::IPTAL_EDILDI => 'İptal Edildi',
        };
    }
}
