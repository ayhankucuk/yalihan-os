<?php

namespace App\Services\AITranslation;

use App\Models\Ilan;
use Illuminate\Support\Facades\App;

/**
 * 🔄 Translation Fallback Service
 *
 * Responsibility: Manages the lookup and fallback hierarchy for localized listing content.
 */
class TranslationFallbackService
{
    /**
     * Gets the most appropriate translation for a listing's field.
     */
    public function getLocalized(Ilan $ilan, string $field, ?string $requestLocale = null): string
    {
        $locale = $requestLocale ?? App::getLocale();
        $sourceLocale = $ilan->source_locale ?? 'tr';

        // 1. Primary: Requested Locale
        if ($locale === $sourceLocale) {
            return $this->getOriginalField($ilan, $field);
        }

        $translation = $ilan->translations()->where('locale', $locale)->first();
        if ($translation && !empty($this->getTranslationField($translation, $field))) {
            return $this->getTranslationField($translation, $field);
        }

        // 2. Fallback: English
        if ($locale !== 'en' && $sourceLocale !== 'en') {
            $enTranslation = $ilan->translations()->where('locale', 'en')->first();
            if ($enTranslation && !empty($this->getTranslationField($enTranslation, $field))) {
                return $this->getTranslationField($enTranslation, $field);
            }
        }

        // 3. Final Fallback: Source Locale (Original)
        return $this->getOriginalField($ilan, $field);
    }

    protected function getOriginalField(Ilan $ilan, string $field): string
    {
        $map = [
            'title' => 'baslik',
            'description' => 'aciklama',
            'summary' => 'metadata->summary', // logic for nested json if needed
        ];

        $dbField = $map[$field] ?? $field;
        return (string) $ilan->{$dbField};
    }

    protected function getTranslationField($translation, string $field): ?string
    {
        $map = [
            'title' => 'translated_title',
            'description' => 'translated_description',
            'summary' => 'translated_summary',
        ];

        return $translation->{$map[$field] ?? $field};
    }
}
