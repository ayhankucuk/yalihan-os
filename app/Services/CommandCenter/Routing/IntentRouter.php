<?php

declare(strict_types=1);

namespace App\Services\CommandCenter\Routing;

use App\DTOs\Command\NormalizedCommandInput;
use App\Enums\IlanDurumu;
use App\Services\CommandCenter\Handlers\PropertySearchIntentHandler;
use App\Services\CommandCenter\Handlers\TalepCreateIntentHandler;
use Illuminate\Support\Facades\Log;

/**
 * IntentRouter
 *
 * Context7 Standard: C7-INTENT-ROUTER-2026-09-19
 *
 * NormalizedCommandInput metnini analiz ederek uygun intent handler'a yönlendirir.
 */
class IntentRouter
{
    public function __construct(
        private PropertySearchIntentHandler $propertySearchHandler,
        private TalepCreateIntentHandler $talepCreateHandler
    ) {}

    /**
     * Gelen komut girdisini yönlendir ve yanıt oluştur
     */
    public function route(NormalizedCommandInput $input): string
    {
        $text = mb_strtolower(trim($input->rawText));

        Log::info('IntentRouter: Niyet analiz ediliyor', [
            'channel' => $input->channel,
            'raw_text' => $input->rawText,
        ]);

        // 1. Talep Create Intent Detection (önce — talep_create öncelikli)
        if ($this->isTalepCreateIntent($text)) {
            $params = $this->extractTalepCreateParameters($text);

            return $this->talepCreateHandler->handle($input, $params);
        }

        // 2. Property / Listing Search Intent Detection
        if ($this->isPropertySearchIntent($text)) {
            $params = $this->extractPropertySearchParameters($text);

            return $this->propertySearchHandler->handle($input, $params);
        }

        // 3. Fallback / Unknown Intent
        return "💡 *Yalıhan Command Center*\n\n".
               "Komut anlaşılamadı. Örnek sorgular:\n".
               "• \"Elimizde €1M'a ne var?\"\n".
               "• \"Yeni talep var. Ahmet Yılmaz, 0532 123 45 67, Bodrum'da villa.\"\n".
               '• "500.000 EUR altındaki ilanlar"';
    }

    /**
     * İlan / Portföy arama niyeti mi?
     */
    private function isPropertySearchIntent(string $text): bool
    {
        $keywords = [
            'elimizde', 'ne var', 'ilan', 'portföy', 'portfoy',
            'satılık', 'satilik', 'kiralık', 'kiralik', 'fiyat',
            'eur', 'euro', 'tl', 'usd', 'dolar', '€', '$', '₺',
        ];

        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Metinden arama parametrelerini çıkar (€1M, 1.000.000 EUR, 500k vs.)
     */
    private function extractPropertySearchParameters(string $text): array
    {
        $maxPrice = null;
        $currency = 'EUR';

        // Canonical supported currencies from config authority
        // (used for reference; explicit symbol detection below is authoritative)
        $supportedCurrencies = array_keys(config('currency.supported', [
            'TRY' => true, 'USD' => true, 'EUR' => true, 'GBP' => true,
        ]));

        // 1. €1M / 1M € / 1 milyon / milyon euro pattern — multiply by 1_000_000
        // Handles: €1M | 1M € | 1.5M | 1 milyon | milyon euro
        if (preg_match(
            '/(?:€|eur|euro)?\s*(\d+(?:[\.,]\d+)?)\s*(?:m|milyon)\s*(?:€|eur|euro)?/iu',
            $text, $matches
        )) {
            $val = (float) str_replace(',', '.', $matches[1]);
            $maxPrice = $val * 1_000_000;
        }
        // 2. €750k / 750K pattern — multiply by 1_000
        // Handles: €750k | 750K | €750K | 750k euro
        elseif (preg_match(
            '/(?:€|eur|euro)?\s*(\d+(?:[\.,]\d+)?)\s*(k)\b(?:\s*(?:€|eur|euro))?/iu',
            $text, $matches
        )) {
            $val = (float) str_replace(',', '.', $matches[1]);
            $maxPrice = $val * 1_000;
        }
        // 3. €750 bin / 750 bin euro pattern — multiply by 1_000 (Turkish "bin"=thousand)
        // Handles: €750 bin | 750 bin | 750 bin euro
        elseif (preg_match(
            '/(?:€|eur|euro)?\s*(\d+(?:[\.,]\d+)?)\s*bin\b(?:\s*(?:€|eur|euro))?/ui',
            $text, $matches
        )) {
            $val = (float) str_replace(',', '.', $matches[1]);
            $maxPrice = $val * 1_000;
        }
        // 4. Plain numeric: 1.000.000 / 500000 pattern
        elseif (preg_match('/(\d{1,3}(?:\.\d{3})+|\d{4,9})/u', $text, $matches)) {
            $valStr = str_replace('.', '', $matches[1]);
            $maxPrice = (float) $valStr;
        }

        // Currency detection — TRY before USD (₺/tl/try), then USD ($/usd/dolar), then EUR (€/eur/euro)
        if (str_contains($text, 'tl') || str_contains($text, '₺') || str_contains($text, 'try')) {
            $currency = 'TRY';
        } elseif (str_contains($text, '$') || str_contains($text, 'usd') || str_contains($text, 'dolar')) {
            $currency = 'USD';
        } elseif (str_contains($text, '€') || str_contains($text, 'eur') || str_contains($text, 'euro')) {
            $currency = 'EUR';
        }

        return [
            'maxFiyat' => $maxPrice,
            'paraBirimi' => $currency,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ];
    }

    /**
     * Talep oluşturma niyeti mi?
     */
    private function isTalepCreateIntent(string $text): bool
    {
        $keywords = [
            'yeni talep', 'yeni talep var', 'talep ekle', 'talep oluştur',
            'yeni müşteri talep', 'talep aç', 'müşteri ekle',
            'talep var', 'yeni demand',
        ];

        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Talep oluşturma parametrelerini çıkar.
     *
     * Bu metod IntentRouter'da tanımlanır, handler'a sadece ham metin gider.
     * Handler extractParameters() ile kendi çıkarımını yapar.
     * Router seviyesinde sadece intent detection yapılır.
     */
    private function extractTalepCreateParameters(string $text): array
    {
        // Router seviyesinde sadece intent flag döner.
        // Gerçek parametre çıkarımı TalepCreateIntentHandler::extractParameters()'da yapılır.
        return [];
    }
}
