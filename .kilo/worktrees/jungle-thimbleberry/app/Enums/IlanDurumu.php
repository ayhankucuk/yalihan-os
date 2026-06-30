<?php

namespace App\Enums;

/**
 * IlanDurumu — Canonical State Enum (SAB Lockdown)
 *
 * 5 durum, tümü küçük harf, DB'ye doğrudan yazılır.
 * PHP cast: protected $casts = ['yayin_durumu' => IlanDurumu::class];
 *
 * BREAKING CHANGE: Eski değerler (Aktif/Taslak/Pasif/Beklemede) data migration
 * ile bu enum'a map edilmiştir. Kaynak: 2026_02_28_canonical_state_migration.php
 */
enum IlanDurumu: string
{
    case TASLAK    = 'taslak';
    case BEKLEMEDE = 'beklemede';
    case YAYINDA   = 'yayinda';
    case ARSIV     = 'arsiv';
    case PASIF     = 'pasif';

    // ── Labels ───────────────────────────────────────────────────────────────

    public function label(): string
    {
        return match ($this) {
            self::TASLAK    => 'Taslak',
            self::BEKLEMEDE => 'Beklemede',
            self::YAYINDA   => 'Yayında',
            self::ARSIV     => 'Arşiv',
            self::PASIF     => 'Pasif',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TASLAK    => 'gray',
            self::BEKLEMEDE => 'blue',
            self::YAYINDA   => 'green',
            self::ARSIV     => 'slate',
            self::PASIF     => 'yellow',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TASLAK    => '📝',
            self::BEKLEMEDE => '⏳',
            self::YAYINDA   => '✅',
            self::ARSIV     => '📦',
            self::PASIF     => '⏸️',
        };
    }

    // ── State predicates ─────────────────────────────────────────────────────

    public function isActive(): bool  { return $this === self::YAYINDA; }
    public function isPublic(): bool  { return $this === self::YAYINDA; }
    public function isPending(): bool { return $this === self::BEKLEMEDE; }
    public function isDraft(): bool   { return $this === self::TASLAK; }
    public function isEditable(): bool
    {
        return ! in_array($this, [self::ARSIV]);
    }

    // ── Normalizer: Legacy string → Canonical ────────────────────────────────

    /**
     * Eski / karma format string'i canonical enum'a çevirir.
     * Bilinmeyen değer gelirse null döner — auto-correct yapılmaz (SAB §5.5).
     */
    public static function normalize(mixed $ham): ?self
    {
        if ($ham instanceof self) {
            return $ham;
        }

        $deger = is_string($ham) ? mb_strtolower(trim($ham)) : null;

        if ($deger === null || $deger === '') {
            return null;
        }

        return match ($deger) {
            'taslak', 'draft'                         => self::TASLAK,
            'beklemede', 'pending', 'onay_bekliyor',
            'incelemede', 'review'                     => self::BEKLEMEDE,
            'yayinda', 'aktif', 'active', 'yayınlandi',
            'approved', 'yayinlanabilir', 'live'       => self::YAYINDA,
            'arsiv', 'arşiv', 'archived', 'completed',
            'satisildi', 'kirasildi'                   => self::ARSIV,
            'pasif', 'passive', 'paused',
            'reddedildi', 'rejected'                   => self::PASIF,
            default => null,
        };
    }

    // ── UI helpers ───────────────────────────────────────────────────────────

    public static function options(): array
    {
        return array_map(
            fn ($case) => [
                'value'       => $case->value,
                'label'       => $case->label(),
                'icon'        => $case->icon(),
                'color'       => $case->color(),
            ],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function activeDurumlar(): array
    {
        return [self::YAYINDA];
    }
}
