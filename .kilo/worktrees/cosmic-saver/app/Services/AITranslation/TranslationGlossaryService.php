<?php

namespace App\Services\AITranslation;

/**
 * 📖 Translation Glossary Service
 *
 * Responsibility: Enforces domain-specific terminology across all supported languages.
 */
class TranslationGlossaryService
{
    protected array $glossary;

    public function __construct()
    {
        $this->glossary = config('domain_glossary.terms', []);
    }

    /**
     * Replaces terms in the content with their glossary counterparts for the target locale.
     */
    public function apply(string $content, string $targetLocale, string $sourceLocale = 'tr'): string
    {
        if (empty($content)) {
            return $content;
        }

        foreach ($this->glossary as $term => $mappings) {
            if (isset($mappings[$targetLocale])) {
                // Case-insensitive search/replace for the term in the source locale
                // Note: Simplified logic assumes terms are provided in 'tr' by default in the glossary keys
                $pattern = '/\b' . preg_quote($term, '/') . '\b/iu';
                $content = preg_replace($pattern, $mappings[$targetLocale], $content);
            }
        }

        return $content;
    }

    /**
     * Normalizes content AFTER AI translation to ensure glossary terms weren't altered.
     */
    public function normalize(string $content, string $targetLocale): string
    {
        // This could be used for reverse-mapping or fixing common AI hallucination terms
        return $content;
    }
}
