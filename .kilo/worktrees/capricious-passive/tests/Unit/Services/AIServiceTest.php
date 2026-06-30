<?php

namespace Tests\Unit\Services;

use App\Services\AIService;
use Tests\TestCase;

/**
 * AIServiceTest — Requires real OpenAI API key and live AI infrastructure.
 * Excluded from standard CI quality gate.
 *
 * @group skip-until-migration-complete
 * @group requires-api-key
 */
class AIServiceTest extends TestCase
{

    protected AIService $aiService;
    protected $promptGovernanceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promptGovernanceMock = \Mockery::mock(\App\Services\AI\PromptGovernanceService::class);

        // Add default expectations
        $this->promptGovernanceMock->shouldReceive('checkCompliance')
            ->byDefault()
            ->andReturn([
                'score' => 100,
                'violations' => []
            ]);

        $this->promptGovernanceMock->shouldReceive('log')
            ->byDefault()
            ->andReturn(new \App\Models\AiPromptLog());

        $this->aiService = new AIService($this->promptGovernanceMock);
    }

    /**
     * Test AIService analyze method
     */
    public function test_ai_service_analyze(): void
    {
        $data = [
            'baslik' => 'Test İlan',
            'tip' => 'Satılık',
            'kategori_id' => 1,
        ];

        $result = $this->aiService->analyze($data, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('priority', $result);
    }

    /**
     * Test AIService suggest method
     */
    public function test_ai_service_suggest(): void
    {
        $context = [
            'category' => 'test',
            'type' => 'general',
        ];

        $result = $this->aiService->suggest($context, 'general');

        $this->assertIsArray($result);
    }

    /**
     * Test AIService generate method
     */
    public function test_ai_service_generate(): void
    {
        $prompt = 'Test prompt';
        $options = ['test' => 'options'];

        $result = $this->aiService->generate($prompt, $options);

        // generate() returns array in test environment: ['value' => 'generated']
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
    }

    public function test_ai_service_health_check(): void
    {
        $result = $this->aiService->healthCheck();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response_state', $result);
    }

    /**
     * Test AIService with empty data
     */
    public function test_ai_service_analyze_with_empty_data(): void
    {
        $result = $this->aiService->analyze([], []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
    }

    /**
     * Test AIService with invalid context
     */
    public function test_ai_service_suggest_with_invalid_context(): void
    {
        $result = $this->aiService->suggest([], 'invalid');

        $this->assertIsArray($result);
    }
}
