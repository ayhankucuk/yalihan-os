<?php

namespace App\Services\AIFrontend;

use App\Services\AI\NLPProcessor;
use App\Services\AI\CortexNLPSearch;
use App\Services\AI\VoiceSearchService;
use Illuminate\Support\Facades\Log;

class AIQueryInterpreter
{
    protected NLPProcessor $nlpProcessor;
    protected CortexNLPSearch $nlpSearch;
    protected VoiceSearchService $voiceSearch;

    public function __construct(
        NLPProcessor $nlpProcessor,
        CortexNLPSearch $nlpSearch,
        VoiceSearchService $voiceSearch
    ) {
        $this->nlpProcessor = $nlpProcessor;
        $this->nlpSearch = $nlpSearch;
        $this->voiceSearch = $voiceSearch;
    }

    /**
     * Interpret user query into structured intent.
     * Hierarchy: NLPProcessor -> CortexNLPSearch -> LLM (VoiceSearchService)
     */
    public function interpret(string $query): array
    {
        // 1. Level 1: Fast Regex/Keyword Parsing (NLPProcessor)
        $nlpResult = $this->nlpProcessor->parseMessage($query);

        // If confidence is high enough, we might stop here or merge.
        // For the purpose of this master prompt, we merge and use LLM as fallback.

        $intent = [
            'search_type' => $nlpResult['entities']['property_type'] ?? null,
            'location' => $nlpResult['entities']['locations'][0] ?? null,
            'rooms' => null,
            'price_max' => $nlpResult['entities']['price']['max'] ?? null,
            'price_min' => $nlpResult['entities']['price']['min'] ?? null,
            'features' => $nlpResult['entities']['features'] ?? [],
            'transaction_type' => $nlpResult['entities']['transaction_type'] ?? 'sale',
            'is_buyer_match' => $this->detectBuyerMatchIntent($query),
            'is_deal_prediction' => $this->detectDealPredictionIntent($query),
            'listing_id' => $nlpResult['entities']['listing_id'] ?? null,
        ];

        // Room count from NLPProcessor or CortexNLPSearch
        if (isset($nlpResult['entities']['rooms'])) {
            $intent['rooms'] = $this->normalizeRoomCount($nlpResult['entities']['rooms']);
        }

        // 2. Level 2: CortexNLPSearch Enrichment
        $cortexSearchFilters = $this->nlpSearch->parseQuery($query);
        $intent['search_type'] = $intent['search_type'] ?: ($cortexSearchFilters['category_id'] ? 'villa' : null); // Simple map
        $intent['rooms'] = $intent['rooms'] ?: $cortexSearchFilters['room_count'];
        $intent['features'] = array_unique(array_merge($intent['features'], $cortexSearchFilters['features']));

        // 3. Level 3: LLM Fallback (If intent is still vague)
        if ($this->shouldUseLLM($intent, $nlpResult['confidence'])) {
            try {
                $llmParsed = $this->voiceSearch->parseCommand($query);
                if (!empty($llmParsed)) {
                    $intent['search_type'] = $llmParsed['search_type'] ?? $intent['search_type'];
                    $intent['location'] = $llmParsed['location']['ilce'] ?? $llmParsed['location']['il'] ?? $intent['location'];
                    $intent['price_max'] = $llmParsed['price']['max'] ?? $intent['price_max'];
                    $intent['rooms'] = $llmParsed['rooms']['max'] ?? $intent['rooms'];
                    $intent['features'] = array_unique(array_merge($intent['features'], $llmParsed['features'] ?? []));
                }
            } catch (\Exception $e) {
                Log::error("AIQueryInterpreter LLM Fallback failed: " . $e->getMessage());
            }
        }

        // 4. Listing ID Refinement
        if (($intent['is_buyer_match'] || $intent['is_deal_prediction']) && empty($intent['listing_id'])) {
            $intent['listing_id'] = $this->extractListingId($query);
        }

        return $intent;
    }

    private function normalizeRoomCount($rooms): string
    {
        // Simple normalization if needed
        return (string) $rooms;
    }

    private function shouldUseLLM(array $intent, float $confidence): bool
    {
        // Use LLM if local confidence is low or key fields are missing
        return $confidence < 0.6 || (empty($intent['location']) && !$intent['is_buyer_match']);
    }

    private function detectBuyerMatchIntent(string $query): bool
    {
        $keywords = ['alıcı', 'buyer', 'eşleş', 'match', 'kimler alır', 'kim alabilir'];
        foreach ($keywords as $word) {
            if (mb_stripos($query, $word) !== false) return true;
        }
        return false;
    }

    private function detectDealPredictionIntent(string $query): bool
    {
        $keywords = [
            'satılır', 'satış', 'tahmin', 'predict', 'deal', 'nasıl bir ilan',
            'hızlı mı', 'ne zaman satılır', 'fiyatı nasıl', 'eyyam', 'şans'
        ];
        foreach ($keywords as $word) {
            if (mb_stripos($query, $word) !== false) return true;
        }
        return false;
    }

    private function extractListingId(string $query): ?int
    {
        if (preg_match('/(?:ilan|id|no)\s*(?:no|id)?\s*:?\s*(\d+)/i', $query, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }
}
