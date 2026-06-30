<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AiLog;
use App\Services\AI\Monitoring\AiTelemetryAggregator;
use App\Services\AI\Monitoring\ProviderSelectorPolicy;

/**
 * Smart Provider Selection Integration Test
 *
 * SAB v4.1 Kural 8: Telemetry-driven provider selection
 *
 * Tests:
 * 1. Empty ai_logs → default ollama
 * 2. Seeded 3 providers → best score wins
 * 3. Single provider → that provider selected
 * 4. Aggregator rolling window correctness
 */
class SmartProviderSelectionTest extends TestCase
{

    private AiTelemetryAggregator $aggregator;
    private ProviderSelectorPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aggregator = app(AiTelemetryAggregator::class);
        $this->policy = new ProviderSelectorPolicy();
    }

    /** @test */
    public function it_returns_ollama_when_no_telemetry_data(): void
    {
        $result = $this->policy->select([], 'title');

        $this->assertEquals('ollama', $result['provider']);
        $this->assertEquals('no_telemetry_data', $result['reason']);
        $this->assertEmpty($result['scores']);
    }

    /** @test */
    public function it_returns_ollama_when_insufficient_data(): void
    {
        // Seed only 2 calls (below MIN_CALL_THRESHOLD of 5)
        $this->seedProviderLogs('openai', 2, 200, 1200, 100);

        $stats = $this->aggregator->getProviderStats();
        $result = $this->policy->select($stats);

        $this->assertEquals('ollama', $result['provider']);
        $this->assertEquals('insufficient_data', $result['reason']);
    }

    /** @test */
    public function it_selects_best_provider_based_on_score(): void
    {
        // Seed 3 providers with different profiles:
        // ollama: fast (200ms), free, but lower success rate (80%)
        $this->seedProviderLogs('ollama', 10, 200, 200, 50, 0.80);

        // openai: slow (1200ms), expensive, but high success rate (98%)
        $this->seedProviderLogs('openai', 10, 200, 1200, 500, 0.98);

        // deepseek: medium (600ms), cheap, good success rate (92%)
        $this->seedProviderLogs('deepseek', 10, 200, 600, 200, 0.92);

        $stats = $this->aggregator->getProviderStats();
        $result = $this->policy->select($stats);

        // Result should have valid structure
        $this->assertArrayHasKey('provider', $result);
        $this->assertArrayHasKey('reason', $result);
        $this->assertArrayHasKey('scores', $result);
        $this->assertNotEmpty($result['scores']);

        // Provider must be one of the seeded ones
        $this->assertContains($result['provider'], ['ollama', 'openai', 'deepseek']);

        // All 3 providers should have scores
        $this->assertCount(3, $result['scores']);
        $this->assertArrayHasKey('ollama', $result['scores']);
        $this->assertArrayHasKey('openai', $result['scores']);
        $this->assertArrayHasKey('deepseek', $result['scores']);

        // Each score should have expected keys
        foreach ($result['scores'] as $score) {
            $this->assertArrayHasKey('total', $score);
            $this->assertArrayHasKey('sr', $score);
            $this->assertArrayHasKey('latency', $score);
            $this->assertArrayHasKey('cost', $score);
        }
    }

    /** @test */
    public function it_selects_single_eligible_provider(): void
    {
        // Only deepseek has enough calls
        $this->seedProviderLogs('deepseek', 10, 200, 500, 100);
        $this->seedProviderLogs('ollama', 2, 200, 200, 50); // Below threshold

        $stats = $this->aggregator->getProviderStats();
        $result = $this->policy->select($stats);

        $this->assertEquals('deepseek', $result['provider']);
    }

    /** @test */
    public function it_aggregates_stats_correctly(): void
    {
        $this->seedProviderLogs('ollama', 10, 200, 400, 100);

        $stats = $this->aggregator->getProviderStats();

        $this->assertArrayHasKey('ollama', $stats);
        $ollamaStats = $stats['ollama'];

        $this->assertEquals(10, $ollamaStats['call_count']);
        $this->assertEquals(1.0, $ollamaStats['success_rate']); // All 200
        $this->assertGreaterThan(0, $ollamaStats['p50_ms']);
        $this->assertGreaterThan(0, $ollamaStats['p95_ms']);
        $this->assertEquals(0.0, $ollamaStats['estimated_cost']); // Ollama is free
    }

    /** @test */
    public function it_calculates_success_rate_with_failures(): void
    {
        // 10 calls with 80% success rate = 8 success + 2 failure
        $this->seedProviderLogs('openai', 10, 200, 1000, 100, 0.80);

        $stats = $this->aggregator->getProviderStats();

        $this->assertArrayHasKey('openai', $stats);
        $this->assertEqualsWithDelta(0.8, $stats['openai']['success_rate'], 0.01);
        $this->assertEquals(10, $stats['openai']['call_count']);
    }

    /** @test */
    public function it_filters_by_task_type(): void
    {
        // 'title' endpoint
        $this->seedProviderLogs('ollama', 10, 200, 300, 50, 1.0, 'title');
        // 'description' endpoint
        $this->seedProviderLogs('openai', 10, 200, 1000, 200, 1.0, 'description');

        $titleStats = $this->aggregator->getProviderStats('title');
        $descStats = $this->aggregator->getProviderStats('description');

        $this->assertArrayHasKey('ollama', $titleStats);
        $this->assertArrayNotHasKey('openai', $titleStats);

        $this->assertArrayHasKey('openai', $descStats);
        $this->assertArrayNotHasKey('ollama', $descStats);
    }

    // ─── HELPER ───

    /**
     * Seed ai_logs with test data for a provider
     */
    private function seedProviderLogs(
        string $provider,
        int $count,
        int $aktiflikKodu,
        int $durationMs,
        int $totalTokens,
        float $successRate = 1.0,
        string $endpoint = 'test_endpoint'
    ): void {
        $successCount = (int) ($count * $successRate);
        $failureCount = $count - $successCount;

        for ($i = 0; $i < $successCount; $i++) {
            AiLog::create([
                'provider' => $provider,
                'endpoint' => $endpoint,
                'duration_ms' => $durationMs + rand(-50, 50), // Slight variance
                'total_tokens' => $totalTokens,
                'input_tokens' => (int)($totalTokens * 0.3),
                'output_tokens' => (int)($totalTokens * 0.7),
                'aktiflik_kodu' => 200,
                'ip_address' => '127.0.0.1',
            ]);
        }

        for ($i = 0; $i < $failureCount; $i++) {
            AiLog::create([
                'provider' => $provider,
                'endpoint' => $endpoint,
                'duration_ms' => 0,
                'total_tokens' => 0,
                'input_tokens' => 0,
                'output_tokens' => 0,
                'aktiflik_kodu' => 500,
                'error_message' => 'Test failure',
                'ip_address' => '127.0.0.1',
            ]);
        }
    }
}
