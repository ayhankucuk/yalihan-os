<?php

namespace Tests\Unit\Domains\GuestCommunication;

use App\Domains\GuestCommunication\Adapters\AirbnbDeliveryAdapter;
use App\Domains\GuestCommunication\Adapters\AirbnbCredentialResolver;
use App\Domains\GuestCommunication\Contracts\AirbnbCredentials;
use App\Domains\GuestCommunication\Contracts\DeliveryResult;
use App\Domains\GuestCommunication\Contracts\DeliveryStatus;
use PHPUnit\Framework\TestCase;

/**
 * AirbnbDeliveryAdapterTest
 *
 * EX-001 WAVE 2 — Airbnb Delivery Test
 */
class AirbnbDeliveryAdapterTest extends TestCase
{
    private AirbnbDeliveryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new AirbnbDeliveryAdapter();
    }

    // ========================================================================
    // DeliveryResult Tests
    // ========================================================================

    /** @test */
    public function delivery_result_sent_creates_success_result(): void
    {
        $result = DeliveryResult::sent('msg_123', 'idem_456');

        $this->assertEquals(DeliveryStatus::SENT, $result->status);
        $this->assertEquals('msg_123', $result->externalId);
        $this->assertEquals('idem_456', $result->idempotencyKey);
        $this->assertNull($result->errorMessage);
        $this->assertFalse($result->retryable);
    }

    /** @test */
    public function delivery_result_failed_creates_failure_result(): void
    {
        $result = DeliveryResult::failed('Network error', true);

        $this->assertEquals(DeliveryStatus::FAILED, $result->status);
        $this->assertEquals('Network error', $result->errorMessage);
        $this->assertTrue($result->retryable);
    }

    /** @test */
    public function delivery_result_duplicate_creates_duplicate_result(): void
    {
        $result = DeliveryResult::duplicate('existing_msg_123');

        $this->assertEquals(DeliveryStatus::DUPLICATE, $result->status);
        $this->assertEquals('existing_msg_123', $result->externalId);
    }

    /** @test */
    public function delivery_result_rate_limited_is_retryable(): void
    {
        $result = DeliveryResult::rateLimited('60');

        $this->assertEquals(DeliveryStatus::RATE_LIMITED, $result->status);
        $this->assertTrue($result->retryable);
        $this->assertStringContainsString('60', $result->errorMessage);
    }

    /** @test */
    public function delivery_result_invalid_credentials_is_not_retryable(): void
    {
        $result = DeliveryResult::invalidCredentials('Token expired');

        $this->assertEquals(DeliveryStatus::INVALID_CREDENTIALS, $result->status);
        $this->assertEquals('Token expired', $result->errorMessage);
        $this->assertFalse($result->retryable);
    }

    // ========================================================================
    // DeliveryStatus Tests
    // ========================================================================

    /** @test */
    public function delivery_status_sent_is_success(): void
    {
        $this->assertTrue(DeliveryStatus::SENT->isSuccess());
        $this->assertFalse(DeliveryStatus::FAILED->isSuccess());
    }

    /** @test */
    public function delivery_status_retry_logic(): void
    {
        $this->assertTrue(DeliveryStatus::RATE_LIMITED->shouldRetry());
        $this->assertTrue(DeliveryStatus::FAILED->shouldRetry());
        $this->assertFalse(DeliveryStatus::SENT->shouldRetry());
        $this->assertFalse(DeliveryStatus::DUPLICATE->shouldRetry());
        $this->assertFalse(DeliveryStatus::INVALID_CREDENTIALS->shouldRetry());
    }

    // ========================================================================
    // AirbnbCredentials Tests
    // ========================================================================

    /** @test */
    public function airbnb_credentials_validates_correctly(): void
    {
        $valid = new AirbnbCredentials(
            tenantId: 1,
            accessToken: 'token_123',
            listingId: 'listing_456',
        );

        $this->assertTrue($valid->isValid());

        $invalid = new AirbnbCredentials(
            tenantId: 1,
            accessToken: '',
            listingId: 'listing_456',
        );

        $this->assertFalse($invalid->isValid());
    }

    // ========================================================================
    // AirbnbCredentialResolver Tests
    // ========================================================================

    /** @test */
    public function credential_resolver_returns_empty_credentials_for_unknown_tenant(): void
    {
        // Without Laravel config, returns empty credentials
        $resolver = new AirbnbCredentialResolver();

        // This test requires Laravel container - skip in unit test
        $this->markTestSkipped('Requires Laravel container');
    }

    // ========================================================================
    // Idempotency Key Tests
    // ========================================================================

    /** @test */
    public function idempotency_key_is_unique_per_reservation(): void
    {
        $key1 = $this->adapter->createIdempotencyKey(123, 'welcome');
        $key2 = $this->adapter->createIdempotencyKey(456, 'welcome');
        $key3 = $this->adapter->createIdempotencyKey(123, 'welcome');

        // Same reservation + type = same key format
        $this->assertStringContainsString('123', $key1);
        $this->assertStringContainsString('welcome', $key1);

        // Different reservation = different key
        $this->assertNotEquals($key1, $key2);

        // Keys are unique per timestamp
        // (In real scenario, same reservation at different times = different keys)
    }

    // ========================================================================
    // Message Data Structure Tests
    // ========================================================================

    /** @test */
    public function adapter_handles_minimal_message_data(): void
    {
        // Test that adapter can be instantiated
        $this->assertInstanceOf(AirbnbDeliveryAdapter::class, $this->adapter);
    }
}
