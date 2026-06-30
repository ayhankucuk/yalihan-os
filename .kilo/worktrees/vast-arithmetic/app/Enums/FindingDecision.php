<?php

namespace App\Enums;

enum FindingDecision: string
{
    case AUTO_RUN = 'auto_run';
    case NEEDS_REVIEW = 'needs_review';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::AUTO_RUN => 'Otomatik Çalıştır',
            self::NEEDS_REVIEW => 'İnceleme Gerekli',
            self::BLOCKED => 'Engellendi',
        };
    }
}
