<?php

namespace App\Enums;

enum FindingSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Düşük',
            self::MEDIUM => 'Orta',
            self::HIGH => 'Yüksek',
            self::CRITICAL => 'Kritik',
        };
    }

    public function isAutoRunnable(): bool
    {
        return $this === self::LOW;
    }

    public function requiresReview(): bool
    {
        return $this === self::MEDIUM;
    }

    public function isBlocked(): bool
    {
        return $this === self::HIGH || $this === self::CRITICAL;
    }
}
