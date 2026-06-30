<?php

namespace App\Enums;

/**
 * Pipeline Step (adım) durumu.
 * Context7: adim_durumu field enum.
 */
enum PipelineAdimDurumu: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Bekliyor',
            self::RUNNING => 'Çalışıyor',
            self::COMPLETED => 'Tamamlandı',
            self::FAILED => 'Başarısız',
            self::SKIPPED => 'Atlandı',
            self::BLOCKED => 'Engellendi',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::SKIPPED, self::BLOCKED]);
    }
}
