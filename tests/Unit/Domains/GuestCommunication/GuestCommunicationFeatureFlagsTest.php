<?php

namespace Tests\Unit\Domains\GuestCommunication;

use App\Domains\GuestCommunication\Services\GuestCommunicationFeatureFlags;
use Tests\TestCase;

/**
 * GuestCommunicationFeatureFlagsTest
 *
 * EX-001 WAVE 2 — Feature Flags Integration Test
 */
class GuestCommunicationFeatureFlagsTest extends TestCase
{
    private GuestCommunicationFeatureFlags $flags;

    protected function setUp(): void
    {
        parent::setUp();
        $this->flags = new GuestCommunicationFeatureFlags();
    }

    // ========================================================================
    // Global Flag Tests
    // ========================================================================

    /** @test */
    public function global_flag_disabled_by_default(): void
    {
        config(['guest_communication.enabled' => false]);

        $this->assertFalse($this->flags->isEnabled());
    }

    /** @test */
    public function global_flag_enabled_when_set_to_true(): void
    {
        config(['guest_communication.enabled' => true]);

        $this->assertTrue($this->flags->isEnabled());
    }

    // ========================================================================
    // Channel Flag Tests
    // ========================================================================

    /** @test */
    public function airbnb_disabled_when_global_disabled(): void
    {
        config([
            'guest_communication.enabled' => false,
            'guest_communication.channels.airbnb.enabled' => true,
        ]);

        $this->assertFalse($this->flags->isAirbnbEnabled());
    }

    /** @test */
    public function airbnb_disabled_when_channel_disabled(): void
    {
        config([
            'guest_communication.enabled' => true,
            'guest_communication.channels.airbnb.enabled' => false,
        ]);

        $this->assertFalse($this->flags->isAirbnbEnabled());
    }

    /** @test */
    public function airbnb_enabled_when_both_flags_enabled(): void
    {
        config([
            'guest_communication.enabled' => true,
            'guest_communication.channels.airbnb.enabled' => true,
        ]);

        $this->assertTrue($this->flags->isAirbnbEnabled());
    }

    // ========================================================================
    // Welcome Message Flag Tests
    // ========================================================================

    /** @test */
    public function welcome_flag_disabled_when_global_disabled(): void
    {
        config([
            'guest_communication.enabled' => false,
            'guest_communication.messages.welcome_enabled' => true,
        ]);

        $this->assertFalse($this->flags->isWelcomeEnabled());
    }

    /** @test */
    public function welcome_flag_enabled_by_default(): void
    {
        config([
            'guest_communication.enabled' => true,
            'guest_communication.messages.welcome_enabled' => true,
        ]);

        $this->assertTrue($this->flags->isWelcomeEnabled());
    }

    // ========================================================================
    // Pilot Mode Tests
    // ========================================================================

    /** @test */
    public function pilot_disabled_when_global_disabled(): void
    {
        config([
            'guest_communication.enabled' => false,
            'guest_communication.pilot.strict_mode' => false,
            'guest_communication.pilot.tenants' => [1],
            'guest_communication.pilot.properties' => [101],
        ]);

        $this->assertFalse($this->flags->isPilotEnabled(1, 101));
    }

    /** @test */
    public function pilot_allows_explicit_tenant_in_strict_mode(): void
    {
        config([
            'guest_communication.enabled' => true,
            'guest_communication.pilot.strict_mode' => true,
            'guest_communication.pilot.tenants' => [1, 2, 3],
            'guest_communication.pilot.properties' => [101, 102],
        ]);

        $this->assertTrue($this->flags->isPilotEnabled(1, 101));
        $this->assertTrue($this->flags->isPilotEnabled(2, 102));
        $this->assertFalse($this->flags->isPilotEnabled(999, 101));
    }

    /** @test */
    public function pilot_blocks_unknown_tenant_in_strict_mode(): void
    {
        config([
            'guest_communication.enabled' => true,
            'guest_communication.pilot.strict_mode' => true,
            'guest_communication.pilot.tenants' => [1],
            'guest_communication.pilot.properties' => [101],
        ]);

        $this->assertFalse($this->flags->isPilotEnabled(999, 999));
    }

    // ========================================================================
    // Retry Tests
    // ========================================================================

    /** @test */
    public function retry_enabled_by_default(): void
    {
        config([
            'guest_communication.retry.enabled' => true,
            'guest_communication.retry.max_attempts' => 3,
            'guest_communication.retry.backoff_seconds' => 60,
        ]);

        $this->assertTrue($this->flags->isRetryEnabled());
        $this->assertEquals(3, $this->flags->getMaxRetries());
        $this->assertEquals(60, $this->flags->getRetryBackoff());
    }

    /** @test */
    public function retry_disabled_when_config_disabled(): void
    {
        config([
            'guest_communication.retry.enabled' => false,
        ]);

        $this->assertFalse($this->flags->isRetryEnabled());
    }

    // ========================================================================
    // Integration Tests
    // ========================================================================

    /** @test */
    public function full_pipeline_respects_all_flags(): void
    {
        config([
            'guest_communication.enabled' => true,
            'guest_communication.channels.airbnb.enabled' => true,
            'guest_communication.messages.welcome_enabled' => true,
            'guest_communication.pilot.strict_mode' => true,
            'guest_communication.pilot.tenants' => [1],
            'guest_communication.pilot.properties' => [101],
            'guest_communication.retry.enabled' => true,
        ]);

        // All enabled for pilot tenant/property
        $this->assertTrue($this->flags->isEnabled());
        $this->assertTrue($this->flags->isAirbnbEnabled());
        $this->assertTrue($this->flags->isWelcomeEnabled());
        $this->assertTrue($this->flags->isPilotEnabled(1, 101));
        $this->assertTrue($this->flags->isRetryEnabled());

        // Unknown tenant/property blocked
        $this->assertFalse($this->flags->isPilotEnabled(999, 999));
    }
}
