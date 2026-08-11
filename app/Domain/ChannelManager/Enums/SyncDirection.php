<?php

namespace App\Domain\ChannelManager\Enums;

/**
 * SyncDirection — Canonical sync direction identifiers.
 *
 * CHANNEL_MANAGER_PROVIDER Wave 1 — ADR-006
 *
 * - EXPORT: Yalihan → external channel (push)
 * - IMPORT: external channel → Yalihan (pull)
 */
enum SyncDirection: string
{
    case EXPORT = 'export';
    case IMPORT = 'import';

    public function label(): string
    {
        return match ($this) {
            self::EXPORT => 'Gönderim (Yalihan → Kanal)',
            self::IMPORT => 'Alım (Kanal → Yalihan)',
        };
    }
}
