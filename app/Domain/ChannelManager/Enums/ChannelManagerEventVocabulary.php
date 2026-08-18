<?php

namespace App\Domain\ChannelManager\Enums;

/**
 * ChannelManagerEventVocabulary — Canonical Event Names
 *
 * Team Hermes — Sprint 13 Epic: Channel Manager Domain Foundation
 *
 * Naming Convention: {domain}.{action}
 * - action: geçmiş zaman (created, updated, deleted, synced, conflicted...)
 */
enum ChannelManagerEventVocabulary: string
{
    // ─── Availability Events ──────────────────────────────────────────
    case AVAILABILITY_SYNCED = 'channel.availability_synced';
    case AVAILABILITY_PUSHED = 'channel.availability_pushed';
    case AVAILABILITY_PULLED = 'channel.availability_pulled';
    case AVAILABILITY_CONFLICTED = 'channel.availability_conflicted';
    case AVAILABILITY_CONFLICT_RESOLVED = 'channel.availability_conflict_resolved';

    // ─── Reservation Events ───────────────────────────────────────────
    case RESERVATION_SYNCED = 'channel.reservation_synced';
    case RESERVATION_CREATED = 'channel.reservation_created';
    case RESERVATION_CANCELLED = 'channel.reservation_cancelled';
    case RESERVATION_UPDATED = 'channel.reservation_updated';

    // ─── Channel Events ──────────────────────────────────────────────
    case CHANNEL_CONNECTED = 'channel.connected';
    case CHANNEL_DISCONNECTED = 'channel.disconnected';
    case CHANNEL_STATUS_FETCHED = 'channel.status_fetched';
    case CHANNEL_SYNC_FAILED = 'channel.sync_failed';

    // ─── Sync Job Events ──────────────────────────────────────────────
    case SYNC_JOB_STARTED = 'channel.sync_job_started';
    case SYNC_JOB_COMPLETED = 'channel.sync_job_completed';
    case SYNC_JOB_FAILED = 'channel.sync_job_failed';

    /**
     * Get all event names as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if a string is a valid vocabulary event
     */
    public static function isValid(string $eventName): bool
    {
        return in_array($eventName, self::values(), true);
    }

    /**
     * Get domain prefix from event name
     */
    public static function domain(string $eventName): ?string
    {
        $parts = explode('.', $eventName, 2);
        return $parts[0] ?? null;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::AVAILABILITY_SYNCED => 'Uygunluk Senkronize Edildi',
            self::AVAILABILITY_PUSHED => 'Uygunluk Gönderildi',
            self::AVAILABILITY_PULLED => 'Uygunluk Alındı',
            self::AVAILABILITY_CONFLICTED => 'Uygunluk Çakışması Tespit Edildi',
            self::AVAILABILITY_CONFLICT_RESOLVED => 'Uygunluk Çakışması Çözüldü',
            self::RESERVATION_SYNCED => 'Rezervasyon Senkronize Edildi',
            self::RESERVATION_CREATED => 'Rezervasyon Oluşturuldu',
            self::RESERVATION_CANCELLED => 'Rezervasyon İptal Edildi',
            self::RESERVATION_UPDATED => 'Rezervasyon Güncellendi',
            self::CHANNEL_CONNECTED => 'Kanal Bağlandı',
            self::CHANNEL_DISCONNECTED => 'Kanal Bağlantısı Kesildi',
            self::CHANNEL_STATUS_FETCHED => 'Kanal Durumu Alındı',
            self::CHANNEL_SYNC_FAILED => 'Kanal Senkronizasyonu Başarısız',
            self::SYNC_JOB_STARTED => 'Senkronizasyon İşi Başladı',
            self::SYNC_JOB_COMPLETED => 'Senkronizasyon İşi Tamamlandı',
            self::SYNC_JOB_FAILED => 'Senkronizasyon İşi Başarısız',
        };
    }
}
