<?php

namespace Tests\Feature\Resilience;

use App\Services\Resilience\CircuitBreaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CircuitBreakerMultiProviderTest extends TestCase
{
    private CircuitBreaker $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear();
        $this->circuitBreaker = new CircuitBreaker();
    }

    /** @test */
    public function it_isolates_circuit_states_and_thresholds_per_provider()
    {
        // Setup provider configurations
        config([
            'ai-runtime.circuit_breaker.ollama.failure_threshold' => 2,
            'ai-runtime.circuit_breaker.ollama.cooldown_seconds' => 5,

            'ai-runtime.circuit_breaker.openai.failure_threshold' => 3,
            'ai-runtime.circuit_breaker.openai.cooldown_seconds' => 10,
        ]);

        // 1. Initial State: both closed (available)
        $this->assertEquals('closed', $this->circuitBreaker->getState('ollama'));
        $this->assertEquals('closed', $this->circuitBreaker->getState('openai'));
        $this->assertTrue($this->circuitBreaker->isAvailable('ollama'));
        $this->assertTrue($this->circuitBreaker->isAvailable('openai'));

        // 2. Report 1 failure on ollama
        $this->circuitBreaker->failure('ollama');
        $this->assertEquals('closed', $this->circuitBreaker->getState('ollama'));
        $this->assertEquals('closed', $this->circuitBreaker->getState('openai'));

        // 3. Report 2nd failure on ollama -> should open (threshold is 2)
        $this->circuitBreaker->failure('ollama');
        $this->assertEquals('open', $this->circuitBreaker->getState('ollama'));
        $this->assertFalse($this->circuitBreaker->isAvailable('ollama'));

        // OpenAI must still be closed (and available)
        $this->assertEquals('closed', $this->circuitBreaker->getState('openai'));
        $this->assertTrue($this->circuitBreaker->isAvailable('openai'));

        // 4. OpenAI failures: report 2 failures -> remains closed (threshold is 3)
        $this->circuitBreaker->failure('openai');
        $this->circuitBreaker->failure('openai');
        $this->assertEquals('closed', $this->circuitBreaker->getState('openai'));

        // Report 3rd failure on openai -> opens
        $this->circuitBreaker->failure('openai');
        $this->assertEquals('open', $this->circuitBreaker->getState('openai'));
        $this->assertFalse($this->circuitBreaker->isAvailable('openai'));

        // 5. Success reset: success on ollama closes it
        $this->circuitBreaker->success('ollama');
        $this->assertEquals('closed', $this->circuitBreaker->getState('ollama'));
        $this->assertTrue($this->circuitBreaker->isAvailable('ollama'));

        // OpenAI remains open
        $this->assertEquals('open', $this->circuitBreaker->getState('openai'));
    }
}
