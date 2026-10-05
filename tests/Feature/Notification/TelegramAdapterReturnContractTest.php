<?php

namespace Tests\Feature\Notification;

use App\DTOs\Notification\GenericNotification;
use App\Models\Notification\OutboundNotification;
use App\Models\User;
use App\Services\Notification\Adapters\TelegramAdapter;
use App\Services\Notification\NotificationDispatcher;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * R1: TelegramService::sendMessage() returns true → no member-call-on-bool, returns true
 * R2: TelegramService::sendMessage() returns false → no member-call-on-bool, returns false
 *
 * Source: REASONING_PIPELINE_V1_TELEGRAM_ADAPTER_CRASH_01
 */
class TelegramAdapterReturnContractTest extends TestCase
{
    use RefreshDatabase;

    private TelegramAdapter $adapter;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = app(TelegramAdapter::class);

        // Create a test user with telegram_chat_id
        $this->user = User::factory()->create([
            'telegram_chat_id' => '123456789',
        ]);
    }

    /** @test */
    public function r1_sendMessage_true_returns_true_and_updates_audit(): void
    {
        // Arrange: Mock TelegramService::sendMessage() to return true
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturn(true);
        });

        $notification = new GenericNotification(
            channel: 'telegram',
            recipient: '123456789',
            templateKey: 'test.template',
            data: ['body' => 'Test message']
        );

        // Pre-condition: Create audit record
        $audit = OutboundNotification::create([
            'channel' => 'telegram',
            'recipient' => '123456789',
            'template_key' => 'test.template',
            'payload_data' => ['body' => 'Test message'],
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
        ]);

        // Act
        $result = $this->adapter->send($notification, $audit->id);

        // Assert R1: No exception, returns true
        $this->assertTrue($result, 'Adapter should return true when TelegramService::sendMessage returns true');

        // Assert R1: Audit updated with success marker (provider_response only; state is dispatcher responsibility)
        $audit->refresh();
        $this->assertIsArray($audit->provider_response);
        $this->assertTrue($audit->provider_response['ok'] ?? false);
    }

    /** @test */
    public function r2_sendMessage_false_returns_false_and_updates_audit(): void
    {
        // Arrange: Mock TelegramService::sendMessage() to return false
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturn(false);
        });

        $notification = new GenericNotification(
            channel: 'telegram',
            recipient: '123456789',
            templateKey: 'test.template',
            data: ['body' => 'Test message']
        );

        // Pre-condition: Create audit record
        $audit = OutboundNotification::create([
            'channel' => 'telegram',
            'recipient' => '123456789',
            'template_key' => 'test.template',
            'payload_data' => ['body' => 'Test message'],
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
        ]);

        // Act
        $result = $this->adapter->send($notification, $audit->id);

        // Assert R2: No exception, returns false
        $this->assertFalse($result, 'Adapter should return false when TelegramService::sendMessage returns false');

        // Assert R2: Audit updated with failure info (provider_response only; state is dispatcher responsibility)
        $audit->refresh();
        $this->assertIsArray($audit->provider_response);
        $this->assertFalse($audit->provider_response['ok'] ?? true);
        $this->assertNotEmpty($audit->provider_response['error'] ?? null);
    }

    /** @test */
    public function adapter_handles_exception_without_calling_methods_on_bool(): void
    {
        // Arrange: Mock TelegramService::sendMessage() to throw
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andThrow(new \Exception('Network error'));
        });

        $notification = new GenericNotification(
            channel: 'telegram',
            recipient: '123456789',
            templateKey: 'test.template',
            data: ['body' => 'Test message']
        );

        $audit = OutboundNotification::create([
            'channel' => 'telegram',
            'recipient' => '123456789',
            'template_key' => 'test.template',
            'payload_data' => ['body' => 'Test message'],
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
        ]);

        // Act & Assert: Should not throw, should return false
        $result = $this->adapter->send($notification, $audit->id);
        $this->assertFalse($result);

        // Assert: Audit updated with error in provider_response
        $audit->refresh();
        $this->assertIsArray($audit->provider_response);
        $this->assertFalse($audit->provider_response['ok'] ?? true);
    }
}
