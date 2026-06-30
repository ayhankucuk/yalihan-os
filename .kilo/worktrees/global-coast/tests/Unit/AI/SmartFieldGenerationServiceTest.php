<?php

namespace Tests\Unit\AI;

use App\Services\AI\SmartFieldGenerationService;
use Tests\TestCase;

class SmartFieldGenerationServiceTest extends TestCase
{
    private SmartFieldGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $wallet = $this->createMock(\App\Services\AI\AiWalletService::class);
        $pricing = $this->createMock(\App\Services\AI\AiPricingService::class);

        // Mock deductCredits and getPrice for generateSmartRecommendations test
        $pricing->method('getPrice')->willReturn(10);

        $this->service = new SmartFieldGenerationService($wallet, $pricing);
    }

    /**
     * Test: Text extraction normalizes Turkish characters and matches keywords correctly.
     *
     * @test
     */
    public function extract_from_text_matches_keywords(): void
    {
        $text = "Muhteşem deniz manzaralı, havuzlu ve geniş balkonlu bir villa. Ayrıca otoparkı var.";

        $results = $this->service->extractFromText($text);
        $slugs = array_column($results, 'slug'); // Extract slugs from structured response

        // Expect: deniz-manzarali, ortak-havuz, balkon, otopark
        $this->assertContains('deniz-manzarali', $slugs); // deniz manzaralı -> deniz manzarali
        $this->assertContains('ortak-havuz', $slugs);     // havuzlu -> ortak-havuz
        $this->assertContains('balkon', $slugs);          // balkonlu -> balkon
        $this->assertContains('otopark', $slugs);         // otoparkı -> otopark (via 'otopark' substring match)

        // Assert: Unique
        $this->assertEquals(count($slugs), count(array_unique($slugs)));
    }

    /**
     * Test: Multi-word phrases are prioritized/matched.
     *
     * @test
     */
    public function multi_word_keywords_match(): void
    {
        $text = "Dairemizde ankastre set ve ebeveyn banyosu mevcuttur. Kapalı otopark vardır.";

        $results = $this->service->extractFromText($text);
        $slugs = array_column($results, 'slug'); // Extract slugs from structured response

        $this->assertContains('ankastre-mutfak', $slugs); // ankastre set
        $this->assertContains('ebeveyn-banyosu', $slugs); // ebeveyn banyosu
        $this->assertContains('kapali-otopark', $slugs);  // kapalı otopark
    }

    /**
     * Test: Confidence scoring logic in recommendations.
     *
     * @test
     */
    public function generate_recommendations_assigns_confidence(): void
    {
        $slugs = ['ortak-havuz', 'balkon', 'unknown-feature'];

        $recommendations = $this->service->generateSmartRecommendations($slugs);

        $this->assertCount(3, $recommendations);

        // 1. Ortak Havuz (Known, Confidence 1.0)
        $havuz = collect($recommendations)->firstWhere('slug', 'ortak-havuz');
        $this->assertNotNull($havuz);
        $this->assertEquals(1.0, $havuz['confidence']);

        // 2. Balkon (Known, Confidence 1.0)
        $balkon = collect($recommendations)->firstWhere('slug', 'balkon');
        $this->assertNotNull($balkon);
        $this->assertEquals(1.0, $balkon['confidence']);

        // 3. Unknown (Confidence 0.5)
        $unknown = collect($recommendations)->firstWhere('slug', 'unknown-feature');
        $this->assertNotNull($unknown);
        $this->assertEquals(0.5, $unknown['confidence']);
    }
}
