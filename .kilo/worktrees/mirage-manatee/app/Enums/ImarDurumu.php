<?php

namespace App\Enums;

/**
 * İmar Durumu Enum (SSOT)
 *
 * Context7: Type-safe imar durumu enumeration
 * TKGM'den gelen ham veriler bu enum üzerinden normalize edilir
 *
 * [PROSES_MÜHRÜ: YALIHAN_AI_0206]
 */
enum ImarDurumu: string
{
    case IMARLI = 'imarlı';
    case IMARSIZ = 'imarsiz';
    case TARLA = 'tarla';
    case VILLA_IMARLI = 'villa_imarli';
    case KONUT_IMARLI = 'konut_imarli';
    case TICARI_IMARLI = 'ticari_imarli';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::IMARLI => 'İmarlı',
            self::IMARSIZ => 'İmarsız',
            self::TARLA => 'Tarla',
            self::VILLA_IMARLI => 'Villa İmarlı',
            self::KONUT_IMARLI => 'Konut İmarlı',
            self::TICARI_IMARLI => 'Ticari İmarlı',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match ($this) {
            self::IMARLI => 'İmar planında belirtilen imar durumuna sahip arsa',
            self::IMARSIZ => 'İmar planı dışında kalan arsa',
            self::TARLA => 'Tarım arazisi statüsündeki arsa',
            self::VILLA_IMARLI => 'Villa inşaatı için özel imar durumuna sahip arsa',
            self::KONUT_IMARLI => 'Konut yapımı için imar durumuna sahip arsa',
            self::TICARI_IMARLI => 'Ticari yapı inşaatı için imar durumuna sahip arsa',
        };
    }

    /**
     * SSOT: Normalize ham TKGM/n8n verisini canonical ImarDurumu'na çevir
     *
     * Yasaklı kelimeler: status, type, island, parcel, area
     * Mühürlü karşılıklar: imar_durumu, ada_parsel_bilgisi
     *
     * @param mixed $rawValue Ham değer (string/int/English)
     * @return self|null Canonical ImarDurumu veya null
     */
    public static function normalize($rawValue): ?self
    {
        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        $normalized = is_string($rawValue) ? strtolower(trim($rawValue)) : (string) $rawValue;

        $map = [
            'imarlı' => self::IMARLI,
            'imarlı' => self::IMARLI,
            'imarli' => self::IMARLI,
            'imarsiz' => self::IMARSIZ,
            'imarsız' => self::IMARSIZ,
            'imar_dışı' => self::IMARSIZ,
            'imar_disi' => self::IMARSIZ,
            'tarla' => self::TARLA,
            'villa_imarli' => self::VILLA_IMARLI,
            'villa imarlı' => self::VILLA_IMARLI,
            'konut_imarli' => self::KONUT_IMARLI,
            'konut imarlı' => self::KONUT_IMARLI,
            'konut' => self::KONUT_IMARLI,
            'ticari_imarli' => self::TICARI_IMARLI,
            'ticari imarlı' => self::TICARI_IMARLI,
            'ticari' => self::TICARI_IMARLI,
            'zoning' => self::IMARLI,
            'zoned' => self::IMARLI,
            'unzoned' => self::IMARSIZ,
            'agricultural' => self::TARLA,
            'residential' => self::KONUT_IMARLI,
            'commercial' => self::TICARI_IMARLI,
        ];

        return $map[$normalized] ?? null;
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
                'description' => $case->description(),
            ],
            self::cases()
        );
    }
}
