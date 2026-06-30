<?php

namespace Tests\Unit\Hermes;

use App\Services\Hermes\DefaultRetryPolicy;
use App\Services\Hermes\Contracts\RetryPolicyInterface;
use Tests\TestCase;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Tests for DefaultRetryPolicy
 */
class DefaultRetryPolicyTest extends TestCase
{
    private DefaultRetryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DefaultRetryPolicy(
            maxAttempts: 3,
            baseDelaySeconds: 10,
            multiplier: 2.0
        );
    }

    /** @test */
    public function max_attempts_is_configured_correctly(): void
    {
        $this->assertEquals(3, $this->policy->getMaxAttempts());
    }

    /** @test */
    public function base_delay_is_configured_correctly(): void
    {
        $this->assertEquals(10, $this->policy->getBaseDelaySeconds());
    }

    /** @test */
    public function multiplier_is_configured_correctly(): void
    {
        $this->assertEquals(2.0, $this->policy->getBackoffMultiplier());
    }

    /** @test */
    public function exponential_backoff_calculates_correct_delays(): void
    {
        // Attempt 1: 10 * 2^0 = 10 seconds
        $this->assertEquals(10, $this->policy->getDelaySeconds(1));

        // Attempt 2: 10 * 2^1 = 20 seconds
        $this->assertEquals(20, $this->policy->getDelaySeconds(2));

        // Attempt 3: 10 * 2^2 = 40 seconds
        $this->assertEquals(40, $this->policy->getDelaySeconds(3));
    }

    /** @test */
    public function invalid_attempt_returns_zero_delay(): void
    {
        $this->assertEquals(0, $this->policy->getDelaySeconds(0));
        $this->assertEquals(0, $this->policy->getDelaySeconds(-1));
    }

    /** @test */
    public function should_retry_returns_true_for_all_exceptions(): void
    {
        $exception = new \RuntimeException('Test error');
        $this->assertTrue($this->policy->shouldRetry($exception));
    }

    /** @test */
    public function implements_retry_policy_interface(): void
    {
        $this->assertInstanceOf(RetryPolicyInterface::class, $this->policy);
    }

    /** @test */
    public function can_create_from_config(): void
    {
        config([
            'hermes.retry.max_attempts' => 5,
            'hermes.retry.base_delay_seconds' => 15,
            'hermes.retry.multiplier' => 3.0,
        ]);

        $policy = DefaultRetryPolicy::fromConfig();

        $this->assertEquals(5, $policy->getMaxAttempts());
        $this->assertEquals(15, $policy->getBaseDelaySeconds());
        $this->assertEquals(3.0, $policy->getBackoffMultiplier());
    }
}
