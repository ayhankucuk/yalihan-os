<?php

namespace App\Enums\MarketIntelligence;

/**
 * Fiyat Pozisyon Sınıflandırması
 *
 * Bir ilanın benchmark medyanına göre konumunu belirler.
 * Sapma eşikleri BenchmarkService tarafından uygulanır.
 */
enum PricingPosition: string
{
    case UNDERPRICED = 'underpriced';
    case FAIR = 'fair';
    case OVERPRICED = 'overpriced';
    case AGGRESSIVELY_OVERPRICED = 'aggressively_overpriced';
    case INSUFFICIENT_DATA = 'insufficient_data';

    public function label(): string
    {
        return match ($this) {
            self::UNDERPRICED => 'Piyasa Altı',
            self::FAIR => 'Piyasa Uyumlu',
            self::OVERPRICED => 'Piyasa Üstü',
            self::AGGRESSIVELY_OVERPRICED => 'Belirgin Yüksek',
            self::INSUFFICIENT_DATA => 'Veri Yetersiz',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::UNDERPRICED => 'green',
            self::FAIR => 'blue',
            self::OVERPRICED => 'amber',
            self::AGGRESSIVELY_OVERPRICED => 'red',
            self::INSUFFICIENT_DATA => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::UNDERPRICED => '↓',
            self::FAIR => '≈',
            self::OVERPRICED => '↑',
            self::AGGRESSIVELY_OVERPRICED => '⬆',
            self::INSUFFICIENT_DATA => '?',
        };
    }
}
