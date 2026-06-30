<?php

namespace App\Enums;

/**
 * Kişi CRM Durum Enum
 *
 * Context7: Type-safe CRM durum enumeration
 * Master Vision 2025: Human-centric terminology (Müşteri -> İşlem Yapmış)
 */
enum KisiDurumu: string
{
    case SICAK = 'sicak';
    case ILGILI = 'ilgili';
    case TAKIPTE = 'takipte';
    case SOGUK = 'soguk';
    case PASIF = 'pasif';
    case POTANSIYEL = 'potansiyel';
    case ISLEMYAPMIS = 'islemyapmis'; // Replaces 'musteri' for better clarity

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::SICAK => 'Sıcak (Öncelikli)',
            self::ILGILI => 'İlgili',
            self::TAKIPTE => 'Takipte',
            self::SOGUK => 'Soğuk',
            self::PASIF => 'Pasif',
            self::POTANSIYEL => 'Potansiyel',
            self::ISLEMYAPMIS => 'İşlem Yapmış',
        };
    }

    /**
     * Get description for CRM tracking
     */
    public function description(): string
    {
        return match ($this) {
            self::SICAK => 'Yüksek satış potansiyeli olan, aktif ilgilenen kişi',
            self::ILGILI => 'İlgileniyor, yakın takip edilmeli',
            self::TAKIPTE => 'Aktif takip ve görüşme sürecinde',
            self::SOGUK => 'Düşük ilgi gösteren, pasif kişi',
            self::PASIF => 'Pasif durumda, aktif takip edilmiyor',
            self::POTANSIYEL => 'Gelecek vaat eden kişi, henüz aktif değil',
            self::ISLEMYAPMIS => 'Daha önce başarıyla işlem/satış tamamlamış kişi',
        };
    }

    /**
     * Get icon/emoji
     */
    public function icon(): string
    {
        return match ($this) {
            self::SICAK => '🔥',
            self::ILGILI => '👀',
            self::TAKIPTE => '📞',
            self::SOGUK => '❄️',
            self::PASIF => '😴',
            self::POTANSIYEL => '💡',
            self::ISLEMYAPMIS => '🤝',
        };
    }

    /**
     * Get color for UI (Tailwind)
     */
    public function color(): string
    {
        return match ($this) {
            self::SICAK => 'red',
            self::ILGILI => 'orange',
            self::TAKIPTE => 'blue',
            self::SOGUK => 'gray',
            self::PASIF => 'slate',
            self::POTANSIYEL => 'yellow',
            self::ISLEMYAPMIS => 'green',
        };
    }

    /**
     * Check if durum requires immediate attention
     */
    public function isUrgent(): bool
    {
        return in_array($this, [self::SICAK, self::ILGILI]);
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
