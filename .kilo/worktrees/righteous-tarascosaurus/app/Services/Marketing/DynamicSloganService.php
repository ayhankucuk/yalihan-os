<?php

namespace App\Services\Marketing;

/**
 * @sab-ignore-catch
 */

use App\Models\Ilan;
use App\Services\AI\YalihanCortex;
use App\Services\Ilan\IlanService;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Cache;

/**
 * Dynamic Slogan Service
 *
 * Phase 8.0: Pazarlama ve Sosyal Medya Motoru
 * Context7 Standardı: C7-DYNAMIC-SLOGAN-SERVICE-2025-12-23
 *
 * Veriye dayalı akıllı reklam metinleri üretimi.
 * - ROI-based slogans
 * - Location-based slogans
 * - Price-based slogans
 * - Feature-based slogans
 * - Multi-language support
 */
class DynamicSloganService
{
    /**
     * Cache TTL (24 hours)
     */
    private const CACHE_TTL = 86400;

    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'slogan';

    /**
     * IlanService instance
     */
    private IlanService $ilanService;

    /**
     * YalihanCortex instance
     */
    private YalihanCortex $cortex;

    /**
     * Slogan templates by category
     */
    private array $sloganTemplates = [
        'high_roi' => [
            '{{roi}}% ROI ile yatırım fırsatı!',
            '{{roi}}% getiri garantisi!',
            'Yüksek ROI: {{roi}}%',
        ],
        'good_deal' => [
            'Piyasa ortalamasının altında!',
            'Fırsat fiyatı!',
            'Özel indirimli fiyat!',
        ],
        'premium_location' => [
            '{{location}} merkezinde premium konum!',
            '{{location}} en iyi lokasyon!',
            '{{location}} kalbi!',
        ],
        'luxury' => [
            'Lüks yaşamın adresi!',
            'Prestijli yaşam alanı!',
            'Seçkinlerin tercihi!',
        ],
        'investment' => [
            'Yatırım için ideal!',
            'Gelir getiren yatırım!',
            'Güvenli yatırım fırsatı!',
        ],
        'family' => [
            'Aile için ideal!',
            'Çocuklu aileler için mükemmel!',
            'Güvenli aile ortamı!',
        ],
    ];

    public function __construct(IlanService $ilanService, YalihanCortex $cortex)
    {
        $this->ilanService = $ilanService;
        $this->cortex = $cortex;
    }

    /**
     * Generate dynamic slogan for listing
     *
     * @param Ilan $ilan
     * @param string $type Slogan type: 'short', 'medium', 'long', 'hashtag'
     * @param array $options Additional options
     * @return array
     */
    public function generateSlogan(Ilan $ilan, string $type = 'medium', array $options = []): array
    {
        $startTime = LogService::startTimer('dynamic_slogan_generation');

        try {
            $cacheKey = self::CACHE_PREFIX . ':ilan:' . $ilan->id . ':type:' . $type;

            $result = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ilan, $type, $options) {
                // Get social media metadata
                $metadata = $this->ilanService->getSocialMediaMetadata($ilan);

                // Generate slogan based on type
                $slogan = match ($type) {
                    'short' => $this->generateShortSlogan($ilan, $metadata),
                    'medium' => $this->generateMediumSlogan($ilan, $metadata),
                    'long' => $this->generateLongSlogan($ilan, $metadata),
                    'hashtag' => $this->generateHashtagSlogan($ilan, $metadata),
                    default => $this->generateMediumSlogan($ilan, $metadata),
                };

                // Generate hashtags
                $hashtags = $this->generateHashtags($ilan, $metadata);

                // Generate call-to-action
                $cta = $this->generateCTA($ilan, $metadata);

                return [
                    'slogan' => $slogan,
                    'hashtags' => $hashtags,
                    'cta' => $cta,
                    'metadata' => [
                        'type' => $type, // context7-ignore
                        'roi' => $metadata['roi']['roi_percentage'] ?? null,
                        'badge' => $metadata['badge']['primary_badge']['type'] ?? null, // context7-ignore
                        'location' => $metadata['location']['il'] ?? null,
                    ],
                ];
            });

            $durationMs = LogService::stopTimer($startTime);
            $result['duration_ms'] = $durationMs;

            LogService::action('slogan_generated', 'marketing', $ilan->id, [
                'ilan_id' => $ilan->id,
                'type' => $type, // context7-ignore
                'slogan' => $result['slogan'],
                'duration_ms' => $durationMs,
            ]);

            return $result;
        } catch (\Exception $e) {
            LogService::error('Slogan generation failed', [
                'ilan_id' => $ilan->id,
                'type' => $type, // context7-ignore
                'error' => $e->getMessage(),
            ]);

            // Return fallback slogan
            return [
                'slogan' => $ilan->baslik,
                'hashtags' => ['#emlak', '#gayrimenkul'],
                'cta' => 'Detaylar için iletişime geçin!',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate short slogan (max 50 chars)
     *
     * @param Ilan $ilan
     * @param array $metadata
     * @return string
     */
    private function generateShortSlogan(Ilan $ilan, array $metadata): string
    {
        $badge = $metadata['badge']['primary_badge'] ?? null;
        $roi = $metadata['roi']['roi_percentage'] ?? null;

        if ($roi && $roi > 15) {
            return "{$roi}% ROI - {$ilan->baslik}";
        }

        if ($badge) {
            $badgeLabels = [
                'high_roi' => 'Yüksek ROI',
                'good_deal' => 'Fırsat',
                'premium_location' => 'Premium',
            ];

            $badgeLabel = $badgeLabels[$badge['type']] ?? ''; // context7-ignore
            if ($badgeLabel) {
                return "{$badgeLabel} - {$ilan->baslik}";
            }
        }

        $location = $metadata['location']['il'] ?? '';
        if ($location) {
            return "{$location} - {$ilan->baslik}";
        }

        return $ilan->baslik;
    }

    /**
     * Generate medium slogan (50-100 chars)
     *
     * @param Ilan $ilan
     * @param array $metadata
     * @return string
     */
    private function generateMediumSlogan(Ilan $ilan, array $metadata): string
    {
        $badge = $metadata['badge']['primary_badge'] ?? null;
        $roi = $metadata['roi']['roi_percentage'] ?? null;
        $location = $metadata['location']['il'] ?? '';
        $ilce = $metadata['location']['ilce'] ?? '';

        $parts = [];

        // ROI-based
        if ($roi && $roi > 15) {
            $parts[] = "{$roi}% ROI ile yatırım fırsatı";
        }

        // Badge-based
        if ($badge) {
            $badgeMessages = [
                'high_roi' => 'Yüksek getiri garantisi',
                'good_deal' => 'Piyasa ortalamasının altında',
                'premium_location' => 'Premium lokasyon',
            ];

            $badgeMessage = $badgeMessages[$badge['type']] ?? ''; // context7-ignore
            if ($badgeMessage) {
                $parts[] = $badgeMessage;
            }
        }

        // Location-based
        if ($location && $ilce) {
            $parts[] = "{$location} {$ilce} merkezinde";
        } elseif ($location) {
            $parts[] = "{$location} merkezinde";
        }

        // Price-based
        $formattedPrice = $this->formatPrice($ilan->fiyat, $ilan->para_birimi);
        $parts[] = "Sadece {$formattedPrice}";

        // Combine parts
        if (empty($parts)) {
            return "{$ilan->baslik} - {$formattedPrice}";
        }

        return implode(' | ', $parts);
    }

    /**
     * Generate long slogan (100+ chars)
     *
     * @param Ilan $ilan
     * @param array $metadata
     * @return string
     */
    private function generateLongSlogan(Ilan $ilan, array $metadata): string
    {
        $roi = $metadata['roi']['roi_percentage'] ?? null;
        $badge = $metadata['badge']['primary_badge'] ?? null;
        $location = $metadata['location']['il'] ?? '';
        $ilce = $metadata['location']['ilce'] ?? '';
        $amortization = $metadata['amortization'] ?? null;

        $slogan = "{$ilan->baslik}";

        // Location
        if ($location && $ilce) {
            $slogan .= " - {$location} {$ilce} merkezinde konumlanmış";
        } elseif ($location) {
            $slogan .= " - {$location} merkezinde";
        }

        // ROI
        if ($roi && $roi > 15) {
            $slogan .= ", {$roi}% ROI ile yatırım fırsatı sunuyor";
        }

        // Badge
        if ($badge) {
            $badgeMessages = [
                'high_roat' => 'Yüksek getiri garantisi',
                'good_deal' => 'Piyasa ortalamasının altında özel fiyat',
                'premium_location' => 'Premium lokasyon avantajı',
            ];

            $badgeMessage = $badgeMessages[$badge['type']] ?? ''; // context7-ignore
            if ($badgeMessage) {
                $slogan .= ". {$badgeMessage}";
            }
        }

        // Amortization
        if ($amortization && isset($amortization['monthly_amortization'])) {
            $monthly = $this->formatPrice($amortization['monthly_amortization'], $amortization['currency']);
            $slogan .= ". Aylık amortisman: {$monthly}";
        }

        // Price
        $formattedPrice = $this->formatPrice($ilan->fiyat, $ilan->para_birimi);
        $slogan .= ". Fiyat: {$formattedPrice}";

        return $slogan;
    }

    /**
     * Generate hashtag slogan
     *
     * @param Ilan $ilan
     * @param array $metadata
     * @return string
     */
    private function generateHashtagSlogan(Ilan $ilan, array $metadata): string
    {
        $hashtags = $this->generateHashtags($ilan, $metadata);
        return implode(' ', $hashtags);
    }

    /**
     * Generate hashtags
     *
     * @param Ilan $ilan
     * @param array $metadata
     * @return array
     */
    private function generateHashtags(Ilan $ilan, array $metadata): array
    {
        $hashtags = [];

        // Category-based
        $kategoriSlug = $ilan->kategori?->slug ?? '';
        if ($kategoriSlug) {
            $hashtags[] = '#' . str_replace('-', '', $kategoriSlug);
        }

        // Location-based
        $location = $metadata['location']['il'] ?? '';
        if ($location) {
            $hashtags[] = '#' . strtolower(str_replace([' ', 'ı', 'İ'], ['', 'i', 'i'], $location));
        }

        // ROI-based
        $roi = $metadata['roi']['roi_percentage'] ?? null;
        if ($roi && $roi > 15) {
            $hashtags[] = '#YüksekROI';
            $hashtags[] = '#YatırımFırsatı';
        }

        // Badge-based
        $badge = $metadata['badge']['primary_badge'] ?? null;
        if ($badge) {
            $badgeHashtags = [
                'high_roi' => '#YüksekGetiri',
                'good_deal' => '#Fırsat',
                'premium_location' => '#PremiumLokasyon',
            ];

            $badgeHashtag = $badgeHashtags[$badge['type']] ?? ''; // context7-ignore
            if ($badgeHashtag) {
                $hashtags[] = $badgeHashtag;
            }
        }

        // General
        $hashtags[] = '#Emlak';
        $hashtags[] = '#Gayrimenkul';
        $hashtags[] = '#Yatırım';

        return array_unique($hashtags);
    }

    /**
     * Generate call-to-action
     *
     * @param Ilan $ilan
     * @param array $metadata
     * @return string
     */
    private function generateCTA(Ilan $ilan, array $metadata): string
    {
        $roi = $metadata['roi']['roi_percentage'] ?? null;
        $badge = $metadata['badge']['primary_badge'] ?? null;

        $ctas = [
            'Detaylar için iletişime geçin!',
            'Hemen inceleyin!',
            'Fırsatı kaçırmayın!',
            'Daha fazla bilgi için bize ulaşın!',
        ];

        if ($roi && $roi > 20) {
            return 'Bu yatırım fırsatını kaçırmayın! Hemen iletişime geçin!';
        }

        if ($badge && $badge['type'] === 'good_deal') { // context7-ignore
            return 'Özel fiyat avantajı! Hemen inceleyin!';
        }

        return $ctas[array_rand($ctas)];
    }

    /**
     * Format price
     *
     * @param float $price
     * @param string $currency
     * @return string
     */
    private function formatPrice(float $price, string $currency): string
    {
        $formatted = number_format($price, 0, ',', '.');

        $currencySymbols = [
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $currencySymbols[$currency] ?? $currency;

        return "{$symbol} {$formatted}";
    }

    /**
     * Generate multiple slogan variations
     *
     * @param Ilan $ilan
     * @param int $count Number of variations
     * @return array
     */
    public function generateVariations(Ilan $ilan, int $count = 3): array
    {
        $variations = [];

        for ($i = 0; $i < $count; $i++) {
            $type = match ($i % 3) {
                0 => 'short',
                1 => 'medium',
                default => 'long',
            };

            $variations[] = $this->generateSlogan($ilan, $type);
        }

        return $variations;
    }
}

