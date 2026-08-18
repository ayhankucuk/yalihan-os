<?php

namespace App\Domain\ChannelManager\Enums;

/**
 * ChannelManagerCapability — Canonical Capability Names
 *
 * Team Hermes — Sprint 13 Epic: Channel Manager Domain Foundation
 *
 * Naming Convention: {domain}.{action}
 */
enum ChannelManagerCapability: string
{
    // ─── Sync Capabilities ────────────────────────────────────────────
    case SYNC_AVAILABILITY = 'channel.sync_availability';
    case SYNC_AVAILABILITY_PUSH = 'channel.sync_availability_push';
    case SYNC_AVAILABILITY_PULL = 'channel.sync_availability_pull';
    case SYNC_RESERVATION = 'channel.sync_reservation';

    // ─── Conflict Detection ──────────────────────────────────────────
    case DETECT_CONFLICT = 'channel.detect_conflict';
    case RESOLVE_CONFLICT = 'channel.resolve_conflict';

    // ─── Channel Management ───────────────────────────────────────────
    case CONNECT_CHANNEL = 'channel.connect';
    case DISCONNECT_CHANNEL = 'channel.disconnect';
    case FETCH_CHANNEL_STATUS = 'channel.fetch_status';

    /**
     * Get all capability names as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if a string is a valid capability
     */
    public static function isValid(string $capability): bool
    {
        return in_array($capability, self::values(), true);
    }

    /**
     * Get domain prefix from capability
     */
    public static function domain(string $capability): ?string
    {
        $parts = explode('.', $capability, 2);
        return $parts[0] ?? null;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::SYNC_AVAILABILITY => 'Uygunluk Senkronizasyonu',
            self::SYNC_AVAILABILITY_PUSH => 'Uygunluk Push Senkronizasyonu',
            self::SYNC_AVAILABILITY_PULL => 'Uygunluk Pull Senkronizasyonu',
            self::SYNC_RESERVATION => 'Rezervasyon Senkronizasyonu',
            self::DETECT_CONFLICT => 'Çakışma Tespiti',
            self::RESOLVE_CONFLICT => 'Çakışma Çözümü',
            self::CONNECT_CHANNEL => 'Kanal Bağlantısı',
            self::DISCONNECT_CHANNEL => 'Kanal Bağlantısı Kesme',
            self::FETCH_CHANNEL_STATUS => 'Kanal Durumu Alma',
        };
    }
}
