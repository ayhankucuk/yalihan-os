<?php

namespace Tests\Unit\AIFrontend;

use App\Services\AI\CortexNLPSearch;
use App\Services\AI\NLPProcessor;
use App\Services\AI\VoiceSearchService;
use App\Services\AIFrontend\AIQueryInterpreter;
use PHPUnit\Framework\TestCase;

/**
 * Phase 17 Safety Net — AIQueryInterpreter Payload Unit Test
 *
 * PURPOSE:
 * Kilit: NLP parser katmanındaki `listing_id` çıkarım sözleşmesini kilitler.
 * Cortex AI sistemi, intent objesindeki `listing_id` anahtarıyla veri alıp işlemektedir.
 * Bu anahtar adı değişirse tüm AI pipeline iflas eder.
 *
 * KAYNAK: App\Services\AIFrontend\AIQueryInterpreter::interpret()
 * - Satır 48: `'listing_id' => $nlpResult['entities']['listing_id'] ?? null`
 * - Satır 80: `$intent['listing_id'] = $this->extractListingId($query)`
 */
class AIQueryInterpreterTest extends TestCase
{
    private AIQueryInterpreter $interpreter;
    private NLPProcessor $nlpProcessor;
    private CortexNLPSearch $nlpSearch;
    private VoiceSearchService $voiceSearch;

    protected function setUp(): void
    {
        parent::setUp();

        // Tüm bağımlılıkları sahte (mock) olarak oluştur
        $this->nlpProcessor = $this->createMock(NLPProcessor::class);
        $this->nlpSearch    = $this->createMock(CortexNLPSearch::class);
        $this->voiceSearch  = $this->createMock(VoiceSearchService::class);

        $this->interpreter = new AIQueryInterpreter(
            $this->nlpProcessor,
            $this->nlpSearch,
            $this->voiceSearch
        );
    }

    /**
     * BARIKAT 1: interpret() çıktısında `listing_id` anahtarı her zaman mevcut olmalı.
     * Anahtar yeniden adlandırılsa (ilan_id vb.) bu test kırılır — kasıtlı.
     */
    public function test_interpret_output_always_contains_listing_id_key(): void
    {
        $this->nlpProcessor->method('parseMessage')->willReturn([
            'entities'   => ['property_type' => null, 'locations' => [], 'price' => [], 'features' => [], 'transaction_type' => 'sale', 'listing_id' => null],
            'confidence' => 0.9,
        ]);
        $this->nlpSearch->method('parseQuery')->willReturn([
            'category_id' => null,
            'room_count'  => null,
            'features'    => [],
        ]);

        $result = $this->interpreter->interpret('test sorgusu');

        // ANA BARIKAT: `listing_id` anahtarı mevcut mu?
        $this->assertArrayHasKey('listing_id', $result, '`listing_id` anahtarı intent içinde bulunmalıdır.');
    }

    /**
     * BARIKAT 2: NLP, `listing_id` içeriyorsa bunu intent'e kopyalar.
     * NLPProcessor -> interpreter -> listing_id zinciri kırılmamalı.
     */
    public function test_nlp_listing_id_is_passed_through_to_intent(): void
    {
        $this->nlpProcessor->method('parseMessage')->willReturn([
            'entities'   => [
                'property_type'    => null,
                'locations'        => [],
                'price'            => [],
                'features'         => [],
                'transaction_type' => 'sale',
                'listing_id'       => 42,
            ],
            'confidence' => 0.95,
        ]);
        $this->nlpSearch->method('parseQuery')->willReturn([
            'category_id' => null,
            'room_count'  => null,
            'features'    => [],
        ]);

        $result = $this->interpreter->interpret('ilan 42 hakkında tahmin yap');

        $this->assertSame(42, $result['listing_id'], 'NLP\'den gelen listing_id intent\'e geçirilmeli.');
    }

    /**
     * BARIKAT 3: Sorgu içinde "ilan no: 123" formatı varsa extractListingId çalışır.
     * Regex extractor'ın sözleşmesi kilitli.
     */
    public function test_listing_id_extracted_from_query_text_when_nlp_returns_null(): void
    {
        // NLP listing_id bulamazken, deal prediction intent'i aktif
        $this->nlpProcessor->method('parseMessage')->willReturn([
            'entities'   => [
                'property_type'    => null,
                'locations'        => [],
                'price'            => [],
                'features'         => [],
                'transaction_type' => 'sale',
                'listing_id'       => null,
            ],
            'confidence' => 0.9,
        ]);
        $this->nlpSearch->method('parseQuery')->willReturn([
            'category_id' => null,
            'room_count'  => null,
            'features'    => [],
        ]);

        // "ilan no: 123" formatı → extractListingId regex'i yakalamalı
        $result = $this->interpreter->interpret('ilan no: 123 hızlı satılır mı?');

        $this->assertSame(123, $result['listing_id'], 'Sorgu metninden listing_id regex ile çıkarılmalı.');
    }

    /**
     * BARIKAT 4: listing_id yoksa null dönmeli, exception atılmamalı.
     * Graceful fallback sözleşmesi: sistem çökmemeli.
     */
    public function test_listing_id_returns_null_gracefully_when_not_found(): void
    {
        $this->nlpProcessor->method('parseMessage')->willReturn([
            'entities'   => [
                'property_type'    => null,
                'locations'        => [],
                'price'            => [],
                'features'         => [],
                'transaction_type' => 'sale',
                'listing_id'       => null,
            ],
            'confidence' => 0.9,
        ]);
        $this->nlpSearch->method('parseQuery')->willReturn([
            'category_id' => null,
            'room_count'  => null,
            'features'    => [],
        ]);

        // Sorguda listing ID içermeyen bir metin
        $result = $this->interpreter->interpret('İstanbul da deniz manzaralı villa');

        $this->assertNull($result['listing_id'], 'listing_id bulunamadığında null dönmeli.');
    }

    /**
     * BARIKAT 5: interpret() her zaman asgari intent anahtarlarını döndürür.
     * Bu anahtar seti değişirse Cortex ve diğer tüketiciler haber alır.
     */
    public function test_interpret_returns_expected_intent_shape(): void
    {
        $this->nlpProcessor->method('parseMessage')->willReturn([
            'entities'   => ['property_type' => null, 'locations' => [], 'price' => [], 'features' => [], 'transaction_type' => 'sale', 'listing_id' => null],
            'confidence' => 0.9,
        ]);
        $this->nlpSearch->method('parseQuery')->willReturn([
            'category_id' => null,
            'room_count'  => null,
            'features'    => [],
        ]);

        $result = $this->interpreter->interpret('herhangi bir sorgu');

        $requiredKeys = [
            'search_type',
            'location',
            'rooms',
            'price_max',
            'price_min',
            'features',
            'transaction_type',
            'is_buyer_match',
            'is_deal_prediction',
            'listing_id', // Phase 17'nin izlediği kritik anahtar
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Intent shape'de `{$key}` anahtarı eksik.");
        }
    }
}
