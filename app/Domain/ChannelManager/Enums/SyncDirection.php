<?php

namespace App\Domain\ChannelManager\Enums;

/**
 * SyncDirection — Operation type
 *
 * CHANNEL_MANAGER Wave 1: Foundation
 *
 * Distinguishes between export (YALIHAN → External) and import (External → YALIHAN).
 */
enum SyncDirection: string
{
    case EXPORT = 'export'; // Internal canonical → External channel
    case IMPORT = 'import'; // External channel → Internal canonical

    /**
     * Get display name
     */
    public function label(): string
    {
        return match ($this) {
            self::EXPORT => 'Export',
            self::IMPORT => 'Import',
        };
    }

    /**
     * Get the opposite direction
     */
    public function opposite(): self
    {
        return match ($this) {
            self::EXPORT => self::IMPORT,
            self::IMPORT => self::EXPORT,
        };
    }
}
