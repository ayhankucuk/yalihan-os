<?php

namespace Tests\Feature\Notification;

use Tests\TestCase;
use App\Models\Notification\OutboundNotification;
use App\Services\Notification\NotificationRetryService;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotificationRetryTest extends TestCase
{
    protected NotificationRetryService $retryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->retryService = app(NotificationRetryService::class);
    }

    /** @test */
    public function it_marks_notification_as_sent_on_success()
    {
        $notification = OutboundNotification::create([
            'channel' => 'email',
            'recipient' => 'test@example.com',
            'template_key' => 'welcome',
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
            'deneme_sayisi' => 0,
        ]);

        $this->retryService->markAsSent($notification, ['id' => 'msg_123']);

        $notification->refresh();
        $this->assertEquals(OutboundNotification::STATE_SENT, $notification->gonderim_durumu);
        $this->assertNotNull($notification->gonderim_tarihi);
        $this->assertEquals(['id' => 'msg_123'], $notification->provider_response);
    }

    /** @test */
    public function it_schedules_retry_on_transient_failure()
    {
        $notification = OutboundNotification::create([
            'channel' => 'email',
            'recipient' => 'test@example.com',
            'template_key' => 'welcome',
            'gonderim_durumu' => OutboundNotification::STATE_PROCESSING,
            'deneme_sayisi' => 0,
        ]);

        $this->retryService->scheduleRetry($notification, 'SMTP Timeout');

        $notification->refresh();
        $this->assertEquals(OutboundNotification::STATE_RETRY_SCHEDULED, $notification->gonderim_durumu);
        $this->assertEquals(1, $notification->deneme_sayisi);
        $this->assertStringContainsString('SMTP Timeout', $notification->hata_mesaji);
    }

    /** @test */
    public function it_marks_as_failed_after_max_retries()
    {
        $notification = OutboundNotification::create([
            'channel' => 'email',
            'recipient' => 'test@example.com',
            'template_key' => 'welcome',
            'gonderim_durumu' => OutboundNotification::STATE_PROCESSING,
            'deneme_sayisi' => 2,
        ]);

        $this->retryService->markAsFailed($notification, 'Permanent Error');

        $notification->refresh();
        $this->assertEquals(OutboundNotification::STATE_FAILED, $notification->gonderim_durumu);
        $this->assertNotNull($notification->basarisiz_olma_tarihi);
    }

    /** @test */
    public function it_can_be_reset_for_manual_retry()
    {
        $notification = OutboundNotification::create([
            'channel' => 'email',
            'recipient' => 'test@example.com',
            'template_key' => 'welcome',
            'gonderim_durumu' => OutboundNotification::STATE_FAILED,
            'deneme_sayisi' => 3,
            'basarisiz_olma_tarihi' => now(),
        ]);

        $this->retryService->resetForManualRetry($notification);

        $notification->refresh();
        $this->assertEquals(OutboundNotification::STATE_PENDING, $notification->gonderim_durumu);
        $this->assertNull($notification->basarisiz_olma_tarihi);
    }
}
