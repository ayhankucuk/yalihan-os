<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Enums;

/**
 * Finding risk level — mechanical classification per ADR H7.
 *
 * HIGH   : runtime authority / request contract / write-path / prod behavior impact
 * MEDIUM : architectural truth drift / legacy surface with active caller potential
 * LOW    : doc drift / clearly inactive surface
 * SKIP   : quarantined / commented / no-caller safe ignore
 */
enum RiskLevel: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case SKIP = 'skip';

    public function rank(): int
    {
        return match ($this) {
            self::HIGH => 3,
            self::MEDIUM => 2,
            self::LOW => 1,
            self::SKIP => 0,
        };
    }
}
