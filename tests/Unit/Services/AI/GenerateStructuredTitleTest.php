<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\YalihanCortex;
use App\Services\AI\AICostGuardService;
use Tests\TestCase;

class GenerateStructuredTitleTest extends TestCase
{
    protected YalihanCortex $cortex;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cortex = app(YalihanCortex::class);
    }

    public function test_generates_title_from_structured_data(): void
    {
        $structuredData = [
            'emlak_tipi' => 'Daire',
            'alan' => 120,
            'oda_sayisi' => '3+1',
            'ilce' => 'Bodrum',
            'tip' => 'Satilik',
        ];

        $result = $this->cortex->generateStructuredTitle($structuredData);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['title']);
        $this->assertStringContainsString('Daire', $result['data']['title']);
        $this->assertStringContainsString('120', $result['data']['title']);
        $this->assertEquals('cortex', $result['provider']);
    }

    public function test_returns_error_for_empty_data(): void
    {
        $result = $this->cortex->generateStructuredTitle([]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_localizes_property_type(): void
    {
        $structuredData = [
            'emlak_tipi' => 'Villa',
            'alan' => 200,
            'tip' => 'Kiralik',
        ];

        $result = $this->cortex->generateStructuredTitle($structuredData, ['locale' => 'en-US']);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Villa', $result['data']['title']);
        $this->assertEquals('en-US', $result['metadata']['locale']);
    }

    public function test_generates_slug(): void
    {
        $structuredData = [
            'emlak_tipi' => 'Daire',
            'alan' => 100,
            'tip' => 'Satilik',
        ];

        $result = $this->cortex->generateStructuredTitle($structuredData);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['slug']);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $result['data']['slug']);
    }
}
