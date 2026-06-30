<?php

namespace App\Services\AIMatch;

use Illuminate\Support\Facades\App;

/**
 * ️ SAB SEALED
 * Buyer Match Formatter Service
 *
 * Generates natural language explanations for match scores across multiple locales.
 * Supported: TR, EN, RU, AR (RTL), DE, FR.
 */
class BuyerMatchFormatterService
{
    /**
     * Format match reasons into a cohesive string based on current locale.
     */
    public function formatReasons(array $breakdown): string
    {
        $locale = App::getLocale();
        $topFactors = $this->getTopFactors($breakdown);

        return $this->generateExplanation($topFactors, $locale);
    }

    /**
     * Identify the 3 strongest factors contributing to the match.
     */
    private function getTopFactors(array $breakdown): array
    {
        arsort($breakdown);
        return array_slice($breakdown, 0, 3, true);
    }

    /**
     * Generate localized explanation string.
     */
    private function generateExplanation(array $factors, string $locale): string
    {
        $templates = $this->getTemplates($locale);
        $parts = [];

        foreach ($factors as $factor => $score) {
            if ($score > 0 && isset($templates[$factor])) {
                $parts[] = $templates[$factor];
            }
        }

        if (empty($parts)) {
            return $templates['fallback'] ?? 'Compatible match identified.';
        }

        $glue = ($locale === 'ar') ? ' و ' : ', ';
        $content = implode($glue, $parts);

        return str_replace('{content}', $content, $templates['wrapper']);
    }

    private function getTemplates(string $locale): array
    {
        $allTemplates = [
            'tr' => [
                'wrapper' => 'Bu eşleşme özellikle {content} nedeniyle önerilmektedir.',
                'price' => 'bütçe uyumu',
                'location' => 'konum yakınlığı',
                'features' => 'istenen özellikler',
                'rooms' => 'oda sayısı tercihi',
                'type' => 'mülk tipi uyumu', // context7-ignore
                'intent' => 'yüksek alım niyeti',
                'action' => 'acil aksiyon skoru',
                'fallback' => 'Genel kriterlere uygun eşleşme.',
            ],
            'en' => [
                'wrapper' => 'This match is recommended primarily due to {content}.',
                'price' => 'budget compatibility',
                'location' => 'location proximity',
                'features' => 'desired features',
                'rooms' => 'room count preference',
                'type' => 'property type alignment', // context7-ignore
                'intent' => 'high purchase intent',
                'action' => 'urgent action score',
                'fallback' => 'Match aligned with general criteria.',
            ],
            'ru' => [
                'wrapper' => 'Это совпадение рекомендуется в первую очередь из-за {content}.',
                'price' => 'соответствия бюджету',
                'location' => 'близости расположения',
                'features' => 'желаемых характеристик',
                'rooms' => 'предпочтения по количеству комнат',
                'type' => 'соответствия типу недвижимости', // context7-ignore
                'intent' => 'высокого намерения к покупке',
                'action' => 'высокого показателя срочности',
                'fallback' => 'Совпадение по общим критериям.',
            ],
            'ar' => [
                'wrapper' => 'يوصى بهذا التطابق بشكل أساسي بسبب {content}.',
                'price' => 'توافق الميزانية',
                'location' => 'قرب الموقع',
                'features' => 'الميزات المطلوبة',
                'rooms' => 'تفضيل عدد الغرف',
                'type' => 'توافق نوع العقار', // context7-ignore
                'intent' => 'نية شراء عالية',
                'action' => 'درجة إجراء عاجلة',
                'fallback' => 'تطابق متوافق مع المعايير العامة.',
            ],
            'de' => [
                'wrapper' => 'Diese Übereinstimmung wird primär aufgrund von {content} empfohlen.',
                'price' => 'Budgetkompatibilität',
                'location' => 'Standortnähe',
                'features' => 'gewünschter Merkmale',
                'rooms' => 'Zimmeranzahl-Präferenz',
                'type' => 'Objekttyp-Ausrichtung', // context7-ignore
                'intent' => 'hoher Kaufabsicht',
                'action' => 'dringender Handlungsbedarf',
                'fallback' => 'Übereinstimmung mit allgemeinen Kriterien.',
            ],
            'fr' => [
                'wrapper' => 'Cette correspondance est recommandée principalement en raison de {content}.',
                'price' => 'la compatibilité budgétaire',
                'location' => 'la proximité du lieu',
                'features' => 'des caractéristiques souhaitées',
                'rooms' => 'la préférence du nombre de pièces',
                'type' => 'l\'alignement du type de bien', // context7-ignore
                'intent' => 'la forte intention d\'achat',
                'action' => 'le score d\'action urgent',
                'fallback' => 'Correspondance alignée avec les critères généraux.',
            ],
        ];

        return $allTemplates[$locale] ?? $allTemplates['en'];
    }
}
