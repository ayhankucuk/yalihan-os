<?php

namespace App\Enums;

/**
 * CategoryType Enum
 * 🛡️ SAB §9 & §12: Eliminating Magic Numbers
 */
enum CategoryType: int
{
    case RESIDENTIAL = 1;
    case COMMERCIAL  = 2;
    case LAND         = 3;
    case TOURISM      = 4;

    public function isCommercial(): bool
    {
        return $this === self::COMMERCIAL;
    }
}
