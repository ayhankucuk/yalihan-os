<?php

namespace App\Services\AITranslation;

use App\Models\Ilan;
use App\Models\ListingTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🎼 Listing Translation Service
 *
 * Responsibility: Orchestrates the end-to-end translation lifecycle for a property listing.
 */
class ListingTranslationService
{
    protected AITranslationService $aiTranslator;
    protected TranslationQualityService $qualityChecker;

    public function __construct(
        AITranslationService $aiTranslator,
        TranslationQualityService $qualityChecker
    ) {
        $this->aiTranslator = $aiTranslator;
        $this->qualityChecker = $qualityChecker;
    }

    /**
     * Resolves the target locales for translation dynamically, preventing self-translation.
     */
    public function resolveTargetLocales(Ilan $ilan): array
    {
        $activeLanguages = app(\App\Services\LocaleControlService::class)->getActiveLanguages();
        $targetLocales = $activeLanguages->pluck('code')->toArray();
        $sourceLocale = $ilan->source_locale ?? 'tr';
        $finalTargets = [];

        foreach ($targetLocales as $locale) {
            if ($locale === $sourceLocale) continue;
            // Additional safety to prevent TR->TR translation if source content is TR master
            if ($sourceLocale === 'tr' && $locale === 'tr') continue;

            $finalTargets[] = $locale;
        }

        return $finalTargets;
    }

    /**
     * Translates a single listing into all supported languages.
     */
    public function translateAll(Ilan $ilan): array
    {
        $targetLocales = $this->resolveTargetLocales($ilan);
        $sourceLocale = $ilan->source_locale ?? 'tr';
        $results = [];

        foreach ($targetLocales as $locale) {
            $results[$locale] = $this->translateToLocale($ilan, $locale, $sourceLocale);
        }

        return $results;
    }

    /**
     * Translates a listing to a specific target locale.
     */
    public function translateToLocale(Ilan $ilan, string $targetLocale, string $sourceLocale): bool
    {
        $startTime = microtime(true);

        // 1. Combine content for translation
        $contentToTranslate = "TITLE: {$ilan->baslik}\n\nDESCRIPTION: {$ilan->aciklama}";

        // 2. Execute AI Translation
        $translationResult = $this->aiTranslator->translate($contentToTranslate, $targetLocale, $sourceLocale);

        // 3. Quality Check
        $quality = $this->qualityChecker->validate($contentToTranslate, $translationResult['translated']);

        // 4. Persistence
        return DB::transaction(function () use ($ilan, $targetLocale, $translationResult, $quality, $startTime, $sourceLocale) {

            // Extract title and description from translated output (simplified for mock-up)
            // In real world, we might request JSON from LLM
            $translatedTitle = $ilan->baslik; // Need proper extraction logic
            $translatedDescription = $translationResult['translated'];

            ListingTranslation::updateOrCreate(
                ['listing_id' => $ilan->id, 'locale' => $targetLocale],
                [
                    'translated_title' => $translatedTitle,
                    'translated_description' => $translatedDescription,
                    'cevirme_durumu' => $translationResult['islem_durumu'] === 'success' ? 'translated' : 'failed',
                    'translated_by' => 'ai',
                    'review_required' => ($quality['islem_durumu'] !== 'passed' || $translationResult['islem_durumu'] !== 'success'),
                    'last_translated_at' => now(),
                    'metadata' => [
                        'quality_score' => $quality['score'],
                        'warnings' => $quality['warnings']
                    ]
                ]
            );

            // 5. Telemetry
            DB::table('ai_translation_logs')->insert([
                'listing_id' => $ilan->id,
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'islem_durumu' => $translationResult['islem_durumu'],
                'review_required' => ($quality['islem_durumu'] !== 'passed'),
                'execution_time' => microtime(true) - $startTime,
                'provider' => $translationResult['provider'] ?? 'unknown',
                'quality_score' => $quality['score'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $translationResult['islem_durumu'] === 'success';
        });
    }
}
