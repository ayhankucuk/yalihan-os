<?php

namespace App\Enums;

/**
 * Yatırımcı Profili Enum
 *
 * Context7: Type-safe investor profile enumeration
 * Used for AI scoring and property matching
 */
enum YatirimciProfili: string
{
    case KONSERVATIF = 'konservatif';
    case AGRESIF = 'agresif';
    case FIRSATCI = 'firsatci';
    case DENGE = 'denge';
    case YENI_BASLAYAN = 'yeni_baslayan';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::KONSERVATIF => 'Konservatif',
            self::AGRESIF => 'Agresif',
            self::FIRSATCI => 'Fırsatçı',
            self::DENGE => 'Dengeli',
            self::YENI_BASLAYAN => 'Yeni Başlayan',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match ($this) {
            self::KONSERVATIF => 'Düşük risk, sabit getiri tercih eden yatırımcı',
            self::AGRESIF => 'Yüksek risk alabilen, yüksek getiri arayan yatırımcı',
            self::FIRSATCI => 'Fırsatları hızlı değerlendiren, esnek yatırımcı',
            self::DENGE => 'Risk ve getiri dengesini kuran yatırımcı',
            self::YENI_BASLAYAN => 'Yatırım deneyimi sınırlı, öğrenme aşamasında',
        };
    }

    /**
     * Get icon/emoji
     */
    public function icon(): string
    {
        return match ($this) {
            self::KONSERVATIF => '🛡️',
            self::AGRESIF => '⚡',
            self::FIRSATCI => '🎯',
            self::DENGE => '⚖️',
            self::YENI_BASLAYAN => '🌱',
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
            self::KONSERVATIF => 'blue',
            self::AGRESIF => 'red',
            self::FIRSATCI => 'orange',
            self::DENGE => 'green',
            self::YENI_BASLAYAN => 'yellow',
        };
    }

    /**
     * Get risk tolerance score (0-100)
     */
    public function riskScore(): int
    {
        return match ($this) {
            self::KONSERVATIF => 20,
            self::AGRESIF => 90,
            self::FIRSATCI => 70,
            self::DENGE => 50,
            self::YENI_BASLAYAN => 30,
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
                'risk_score' => $case->riskScore(),
            ],
            self::cases()
        );
    }

    /**
     * Safe database cast - PHP 8.4 compatibility
     * Returns null for invalid/null values instead of throwing exception
     *
     * @param string|null $value Database value
     * @return self|null
     */
    public static function tryFromDatabase(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return self::tryFrom($value);
    }

    /**
     * Get default value for new records
     */
    public static function default(): self
    {
        return self::YENI_BASLAYAN;
    }
}





