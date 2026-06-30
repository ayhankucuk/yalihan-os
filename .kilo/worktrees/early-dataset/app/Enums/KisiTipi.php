<?php

namespace App\Enums;

/**
 * Kişi Tipi Enum
 *
 * Context7: Type-safe person type enumeration
 * Replaces string-based kisi_tipi field with enum
 */
enum KisiTipi: string
{
    case ALICI = 'alici';
    case KIRACI = 'kiraci';
    case SATICI = 'satici';
    case EV_SAHIBI = 'ev_sahibi';
    case YATIRIMCI = 'yatirimci';
    case ARACI = 'araci';
    case DANISMAN = 'danisman';
    case LEAD = 'lead'; // Aday Müşteri (Telegram Contact'tan gelen)

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::ALICI => 'Alıcı',
            self::KIRACI => 'Kiracı',
            self::SATICI => 'Satıcı',
            self::EV_SAHIBI => 'Ev Sahibi',
            self::YATIRIMCI => 'Yatırımcı',
            self::ARACI => 'Aracı',
            self::DANISMAN => 'Danışman',
            self::LEAD => 'Aday Müşteri',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match ($this) {
            self::ALICI => 'Gayrimenkul almak isteyen kişi',
            self::KIRACI => 'Gayrimenkul kiralamak isteyen kişi',
            self::SATICI => 'Gayrimenkul satan kişi',
            self::EV_SAHIBI => 'Gayrimenkul sahibi',
            self::YATIRIMCI => 'Yatırım amaçlı gayrimenkul arayan kişi',
            self::ARACI => 'Emlak aracısı',
            self::DANISMAN => 'Gayrimenkul danışmanı',
            self::LEAD => 'Aday müşteri (henüz kategorize edilmemiş)',
        };
    }

    /**
     * Get icon/emoji
     */
    public function icon(): string
    {
        return match ($this) {
            self::ALICI => '🏠',
            self::KIRACI => '🔑',
            self::SATICI => '💰',
            self::EV_SAHIBI => '👤',
            self::YATIRIMCI => '📈',
            self::ARACI => '🤝',
            self::DANISMAN => '👔',
            self::LEAD => '📋',
        };
    }

    /**
     * Get color for UI
     *
     * @return string Tailwind CSS color class
     */
    public function color(): string
    {
        return match ($this) {
            self::ALICI => 'blue',
            self::KIRACI => 'green',
            self::SATICI => 'orange',
            self::EV_SAHIBI => 'purple',
            self::YATIRIMCI => 'indigo',
            self::ARACI => 'yellow',
            self::DANISMAN => 'gray',
        };
    }

    /**
     * Check if this person type is a buyer
     */
    public function isBuyer(): bool
    {
        return in_array($this, [self::ALICI, self::YATIRIMCI]);
    }

    /**
     * Check if this person type is a renter
     */
    public function isRenter(): bool
    {
        return $this === self::KIRACI;
    }

    /**
     * Check if this person type is a seller
     */
    public function isSeller(): bool
    {
        return in_array($this, [self::SATICI, self::EV_SAHIBI]);
    }

    /**
     * Check if this person type is a professional
     */
    public function isProfessional(): bool
    {
        return in_array($this, [self::ARACI, self::DANISMAN]);
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
            ],
            self::cases()
        );
    }

    /**
     * Create from string (with fallback)
     * Note: PHP enum'larında tryFrom() built-in metodudur ve override edilemez.
     * Bu metod sadece dokümantasyon amaçlıdır.
     * Kullanım: KisiTipi::tryFrom($value) - PHP'nin built-in metodu otomatik null kontrolü yapar.
     *
     * @param  string|null  $value
     * @return self|null
     */
    // tryFrom() metodunu override etmeye gerek yok - PHP'nin built-in metodu kullanılmalı
}
