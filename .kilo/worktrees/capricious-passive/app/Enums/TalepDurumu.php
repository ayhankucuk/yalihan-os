<?php

namespace App\Enums;

use App\Enums\IlanDurumu;

/**
 * Talep Durumu Enum
 *
 * Context7: Type-safe talep durum enumeration
 * Master Vision 2025: Rich UI metadata and decision support
 */
enum TalepDurumu: string
{
    case AKTIF = IlanDurumu::YAYINDA->value;
    case BEKLEMEDE = 'Beklemede';
    case TASLAK = 'Taslak';
    case IPTAL = 'İptal';
    case KARSIILANDI = 'Tamamlandı'; // Alias for completed
    case ACIL = 'Acil';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::AKTIF => IlanDurumu::YAYINDA->value,
            self::BEKLEMEDE => 'Beklemede',
            self::TASLAK => 'Taslak',
            self::IPTAL => 'İptal Edildi',
            self::KARSIILANDI => 'Karşılandı',
            self::ACIL => 'Acil (Öncelikli)',
        };
    }

    /**
     * Get description for decision support
     */
    public function description(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif olarak mülk arayışında olan talep',
            self::BEKLEMEDE => 'Geçici olarak dondurulmuş veya takip bekleyen talep',
            self::TASLAK => 'Henüz tamamlanmamış taslak talep',
            self::IPTAL => 'Kullanıcı tarafından vazgeçilmiş veya geçersiz talep',
            self::KARSIILANDI => 'Aranan mülk bulunmuş ve işlem tamamlanmış',
            self::ACIL => 'Kritik öneme sahip, hızlı aksiyon gerektiren talep',
        };
    }

    /**
     * Get color for UI (HEX)
     */
    public function color(): string
    {
        return match ($this) {
            self::AKTIF => '#10B981',    // Emerald-500
            self::BEKLEMEDE => '#F59E0B', // Amber-500
            self::TASLAK => '#6B7280',    // Gray-500
            self::IPTAL => '#EF4444',    // Red-500
            self::KARSIILANDI => '#8B5CF6', // Violet-500
            self::ACIL => '#F97316',     // Orange-500
        };
    }

    /**
     * Get icon
     */
    public function icon(): string
    {
        return match ($this) {
            self::AKTIF => '🎯',
            self::BEKLEMEDE => '⏳',
            self::TASLAK => '📝',
            self::IPTAL => '❌',
            self::KARSIILANDI => '🤝',
            self::ACIL => '⚡',
        };
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    /**
     * Get options for select dropdown
     */
    public static function options(): array
    {
        return array_map(
            fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
                'color' => $case->color(),
                'description' => $case->description(),
            ],
            self::cases()
        );
    }
}
