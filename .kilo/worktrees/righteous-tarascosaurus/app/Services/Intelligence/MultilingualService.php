<?php

namespace App\Services\Intelligence;

use App\Models\Ilan;
use App\Services\AIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Multilingual Service
 * Context7: Çok Dilli Lokalizasyon (Multilingual Localization) için içerik üretimi servisi
 *
 * Yabancı dil ilan açıklaması üretirken kültürel lokalizasyon yapar.
 */
class MultilingualService
{
    public function __construct(
        private AIService $aiService,
        private \App\Services\LocaleControlService $localeService
    ) {}

    /**
     * Desteklenen dilleri getir (LocaleControlService üzerinden)
     */
    private function getSupportedLanguages(): array
    {
        return $this->localeService->getActiveLanguages()->pluck('code')->toArray();
    }


    /**
     * İlan için çok dilli açıklama üret
     *
     * @param Ilan $ilan
     * @param string $targetLanguage Hedef dil (en, ar, ru, de, fr)
     * @param array $options Ek seçenekler
     * @return array
     */
    public function generateLocalizedDescription(Ilan $ilan, string $targetLanguage = 'en', array $options = []): array
    {
        if (!in_array($targetLanguage, $this->getSupportedLanguages())) {
            return [
                'success' => false,
                'error' => "Desteklenmeyen dil: {$targetLanguage}",
            ];
        }

        $cacheKey = "multilingual:ilan:{$ilan->id}:lang:{$targetLanguage}";

        return Cache::remember($cacheKey, 3600 * 24 * 7, function () use ($ilan, $targetLanguage, $options) {
            try {
                $prompt = $this->buildLocalizationPrompt($ilan, $targetLanguage, $options);
                $aiResponse = $this->aiService->generate($prompt, [
                    'type' => 'multilingual_localization', // context7-ignore
                    'max_tokens' => 1000,
                    'language' => $targetLanguage,
                ]);

                // AI yanıtını string'e çevir
                $description = is_array($aiResponse) ? ($aiResponse['content'] ?? json_encode($aiResponse)) : (string) $aiResponse;

                $seoKeywords = $this->generateSEOKeywords($ilan, $targetLanguage);
                $culturalNotes = $this->getCulturalNotes($targetLanguage);

                return [
                    'success' => true,
                    'ilan_id' => $ilan->id,
                    'language' => $targetLanguage,
                    'description' => $description,
                    'seo_keywords' => $seoKeywords,
                    'cultural_notes' => $culturalNotes,
                    'generated_at' => now(),
                ];
            } catch (\Exception $e) {
                Log::error('Multilingual generation error', [
                    'ilan_id' => $ilan->id,
                    'language' => $targetLanguage,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Lokalizasyon prompt'u oluştur
     */
    private function buildLocalizationPrompt(Ilan $ilan, string $targetLanguage, array $options): string
    {
        $languageNames = [
            'en' => 'English',
            'ar' => 'Arabic',
            'ru' => 'Russian',
            'de' => 'German',
            'fr' => 'French',
        ];

        $languageName = $languageNames[$targetLanguage] ?? $targetLanguage;

        $culturalContext = $this->getCulturalContext($targetLanguage);

        return sprintf(
            "Generate a professional real estate listing description in %s for the following property:\n\n" .
                "Title: %s\n" .
                "Description: %s\n" .
                "Price: ₺%s\n" .
                "Location: %s\n" .
                "Features: %s\n\n" .
                "%s\n\n" .
                "Requirements:\n" .
                "- Use %s language naturally\n" .
                "- Include cultural context: %s\n" .
                "- Highlight features that appeal to %s buyers\n" .
                "- Use SEO-friendly keywords\n" .
                "- Keep it professional and engaging\n" .
                "- Length: 200-400 words",
            $languageName,
            $ilan->baslik ?? 'Property',
            Str::limit($ilan->aciklama ?? '', 500),
            number_format($ilan->fiyat ?? 0, 0, ',', '.'),
            $this->getLocationString($ilan),
            $this->getFeaturesString($ilan),
            $culturalContext,
            $languageName,
            $culturalContext,
            $this->getTargetAudience($targetLanguage)
        );
    }

    /**
     * Kültürel bağlam
     */
    private function getCulturalContext(string $language): string
    {
        return match ($language) {
            'ar' => 'Arabic buyers value privacy, family-friendly spaces, and proximity to mosques. Emphasize security and community.',
            'ru' => 'Russian buyers appreciate quality construction, sea views, and investment potential. Highlight luxury and ROI.',
            'de' => 'German buyers value efficiency, quality, and sustainability. Emphasize energy efficiency and build quality.',
            'fr' => 'French buyers appreciate elegance, culture, and lifestyle. Highlight charm and cultural proximity.',
            default => 'International buyers value quality, location, and investment potential.',
        };
    }

    /**
     * Hedef kitle
     */
    private function getTargetAudience(string $language): string
    {
        return match ($language) {
            'ar' => 'Arabic-speaking',
            'ru' => 'Russian-speaking',
            'de' => 'German-speaking',
            'fr' => 'French-speaking',
            default => 'international',
        };
    }

    /**
     * SEO keywords üret
     */
    private function generateSEOKeywords(Ilan $ilan, string $targetLanguage): array
    {
        $baseKeywords = [
            'real estate',
            'property',
            'for sale',
            $ilan->il ? $ilan->il->adi : '',
            $ilan->ilce ? $ilan->ilce->adi : '',
        ];

        $languageKeywords = match ($targetLanguage) {
            'ar' => ['عقار', 'للبيع', 'تركيا', 'استثمار'],
            'ru' => ['недвижимость', 'продажа', 'Турция', 'инвестиции'],
            'de' => ['Immobilie', 'Verkauf', 'Türkei', 'Investition'],
            'fr' => ['immobilier', 'vente', 'Turquie', 'investissement'],
            default => $baseKeywords,
        };

        return array_filter(array_merge($baseKeywords, $languageKeywords));
    }

    /**
     * Kültürel notlar
     */
    private function getCulturalNotes(string $language): array
    {
        return match ($language) {
            'ar' => [
                'RTL (right-to-left) text support may be needed',
                'Consider Islamic finance options',
                'Privacy and security are important',
            ],
            'ru' => [
                'Cyrillic script support required',
                'Emphasize investment returns',
                'Sea view is highly valued',
            ],
            'de' => [
                'Emphasize quality and efficiency',
                'Energy certificates important',
                'Precision and detail valued',
            ],
            'fr' => [
                'Emphasize lifestyle and culture',
                'Elegance and charm important',
                'Cultural proximity valued',
            ],
            default => [],
        };
    }

    /**
     * Lokasyon string'i
     */
    private function getLocationString(Ilan $ilan): string
    {
        $parts = [];
        if ($ilan->il) {
            $parts[] = $ilan->il->adi;
        }
        if ($ilan->ilce) {
            $parts[] = $ilan->ilce->adi;
        }
        if ($ilan->mahalle) {
            $parts[] = $ilan->mahalle->adi;
        }
        return implode(', ', $parts) ?: 'Location not specified';
    }

    /**
     * Özellikler string'i
     */
    private function getFeaturesString(Ilan $ilan): string
    {
        $features = [];
        if ($ilan->oda_sayisi) {
            $features[] = "{$ilan->oda_sayisi} rooms";
        }
        if ($ilan->metrekare) {
            $features[] = "{$ilan->metrekare} m²";
        }
        if ($ilan->yas) {
            $features[] = "Built in {$ilan->yas}";
        }
        return implode(', ', $features) ?: 'Features not specified';
    }

    /**
     * Cache'i temizle
     */
    public function clearCache(int $ilanId, ?string $language = null): void
    {
        if ($language) {
            Cache::forget("multilingual:ilan:{$ilanId}:lang:{$language}");
        } else {
            foreach ($this->getSupportedLanguages() as $lang) {
                Cache::forget("multilingual:ilan:{$ilanId}:lang:{$lang}");
            }
        }
    }
}
