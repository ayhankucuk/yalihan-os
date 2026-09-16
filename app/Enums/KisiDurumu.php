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

    // CRM Süreç Aşama Values
    case YENI = 'yeni';
    case GORUSME = 'gorusme';
    case TAKIP = 'takip';
    case TAMAMLANDI = 'tamamlandi';
    case KAYBEDILDI = 'kaybedildi';

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
            self::YENI => 'Yeni Lead',
            self::GORUSME => 'Görüşme Aşamasında',
            self::TAKIP => 'Takip Ediliyor',
            self::TAMAMLANDI => 'Kazanıldı (Tamamlandı)',
            self::KAYBEDILDI => 'Kaybedildi',
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
            self::YENI => 'Sisteme yeni girmiş lead/potansiyel',
            self::GORUSME => 'Danışman ile aktif görüşme aşamasında',
            self::TAKIP => 'Teklif verilmiş veya takip sürecinde',
            self::TAMAMLANDI => 'Satış veya kiralama başarıyla sonuçlanmış',
            self::KAYBEDILDI => 'İşlem olumsuz sonuçlanmış veya vazgeçilmiş',
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
            self::YENI => '✨',
            self::GORUSME => '💬',
            self::TAKIP => '🔄',
            self::TAMAMLANDI => '🏆',
            self::KAYBEDILDI => '❌',
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
            self::YENI => 'blue',
            self::GORUSME => 'amber',
            self::TAKIP => 'indigo',
            self::TAMAMLANDI => 'emerald',
            self::KAYBEDILDI => 'rose',
        };
    }

    /**
     * Safely parse value from database string (PHP 8.4 safe)
     */
    public static function tryFromDatabase(?string $value): ?self
    {
        if (empty($value)) {
            return null;
        }

        $val = strtolower(trim($value));

        if ($val === 'musteri') {
            return self::ISLEMYAPMIS;
        }

        return self::tryFrom($val);
    }

    /**
     * Check if durum requires immediate attention
     */
    public function isUrgent(): bool
    {
        return in_array($this, [self::SICAK, self::ILGILI, self::YENI, self::GORUSME]);
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
