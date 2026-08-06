<?php

namespace Tests\Unit\ChannelManager;

use App\Domain\ChannelManager\RetryPolicy;
use PHPUnit\Framework\TestCase;

/**
 * RetryPolicyTest
 *
 * CHANNEL_MANAGER Wave 1: Retry Engine Tests
 */
class RetryPolicyTest extends TestCase
{
    /** @test */
    public function delay_increases_exponentially(): void
    {
        $this->assertEquals(1000, RetryPolicy::delay(1));  // 1s
        $this->assertEquals(2000, RetryPolicy::delay(2));  // 2s
        $this->assertEquals(4000, RetryPolicy::delay(3));  // 4s
    }

    /** @test */
    public function delay_is_capped_at_max(): void
    {
        $this->assertEquals(60000, RetryPolicy::delay(10));  // Should be 512s but capped at 60s
    }

    /** @test */
    public function delay_returns_zero_for_invalid_attempt(): void
    {
        $this->assertEquals(0, RetryPolicy::delay(0));
        $this->assertEquals(0, RetryPolicy::delay(-1));
    }

    /** @test */
    public function should_retry_respects_max_attempts(): void
    {
        $this->assertTrue(RetryPolicy::shouldRetry(1));  // Attempt 1, can retry
        $this->assertTrue(RetryPolicy::shouldRetry(2));  // Attempt 2, can retry
        $this->assertFalse(RetryPolicy::shouldRetry(3)); // Attempt 3 = MAX, no more
    }

    /** @test */
    public function delay_seconds_converts_correctly(): void
    {
        $this->assertEquals(1.0, RetryPolicy::delaySeconds(1));
        $this->assertEquals(2.0, RetryPolicy::delaySeconds(2));
        $this->assertEquals(4.0, RetryPolicy::delaySeconds(3));
    }

    /** @test */
    public function total_wait_time_accumulates(): void
    {
        // 1s + 2s + 4s = 7s = 7000ms
        $this->assertEquals(7000, RetryPolicy::totalWaitTime(3));
    }

    /** @test */
    public function schedule_generates_timestamps(): void
    {
        $startTs = 1000000000; // Fixed timestamp

        $schedule = RetryPolicy::schedule($startTs);

        $this->assertCount(3, $schedule);
        $this->assertEquals($startTs + 1, $schedule[1]);  // +1s
        $this->assertEquals($startTs + 3, $schedule[2]);  // +1s + 2s
        $this->assertEquals($startTs + 7, $schedule[3]);  // +1s + 2s + 4s
    }

    /** @test */
    public function append_attempt_modifies_key(): void
    {
        $base = 'ical:1:42:abc123';

        $this->assertEquals('ical:1:42:abc123_attempt_1', RetryPolicy::appendAttempt($base, 1));
        $this->assertEquals('ical:1:42:abc123_attempt_2', RetryPolicy::appendAttempt($base, 2));
        $this->assertEquals('ical:1:42:abc123_attempt_3', RetryPolicy::appendAttempt($base, 3));
    }

    /** @test */
    public function append_attempt_replaces_existing_attempt(): void
    {
        $key = 'ical:1:42:abc123_attempt_2';

        $this->assertEquals('ical:1:42:abc123_attempt_1', RetryPolicy::appendAttempt($key, 1));
    }

    /** @test */
    public function extract_attempt_returns_one_when_not_present(): void
    {
        $this->assertEquals(1, RetryPolicy::extractAttempt('ical:1:42:abc123'));
        $this->assertEquals(1, RetryPolicy::extractAttempt('no-attempt-here'));
    }

    /** @test */
    public function extract_attempt_parses_existing(): void
    {
        $this->assertEquals(1, RetryPolicy::extractAttempt('ical:1:42:abc123_attempt_1'));
        $this->assertEquals(2, RetryPolicy::extractAttempt('ical:1:42:abc123_attempt_2'));
        $this->assertEquals(3, RetryPolicy::extractAttempt('ical:1:42:abc123_attempt_3'));
    }

    /** @test */
    public function max_attempts_constant_is_three(): void
    {
        $this->assertEquals(3, RetryPolicy::MAX_ATTEMPTS);
    }
}
