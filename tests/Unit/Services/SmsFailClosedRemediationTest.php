<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\Notification\NotificationContract;
use App\Models\Notification\OutboundNotification;
use App\Models\User;
use App\Services\Logging\LogService;
use App\Services\Notification\NotificationDispatcher;
use App\Services\Notification\NotificationRetryService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsFailClosedRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_notification_service_sms_returns_fail_closed(): void
    {
        $user = User::factory()->create([
            'telefon' => '05321234567',
        ]);

        $service = new NotificationService(new LogService());

        $result = $service->sendNotification($user, 'test_alert', [
            'message' => 'Test SMS Message',
        ], [
            'channels' => ['sms'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('channels', $result);
        $this->assertArrayHasKey('sms', $result['channels']);
        $this->assertFalse($result['channels']['sms']['success']);
        $this->assertEquals(
            'SMS provider transport missing or unconfigured',
            $result['channels']['sms']['error']
        );
    }

    public function test_notification_dispatcher_sms_route_fails_closed(): void
    {
        $retryService = $this->createMock(NotificationRetryService::class);
        $dispatcher = new NotificationDispatcher($retryService);

        $notification = new class implements NotificationContract {
            public function getChannel(): string
            {
                return 'sms';
            }

            public function getRecipient(): string
            {
                return '05321234567';
            }

            public function getTemplateKey(): string
            {
                return 'test_sms';
            }

            public function getData(): array
            {
                return ['message' => 'Hello'];
            }

            public function getPriority(): string
            {
                return 'normal';
            }

            public function isAsync(): bool
            {
                return false;
            }

            public function getRenderedBody(): string
            {
                return '';
            }
        };

        $audit = OutboundNotification::create([
            'channel' => 'sms',
            'recipient' => '05321234567',
            'template_key' => 'test_sms',
            'payload_data' => ['message' => 'Hello'],
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
        ]);

        $result = $dispatcher->routeToAdapter($notification, $audit->id);

        $this->assertFalse($result);
    }
}
