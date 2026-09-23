<?php

namespace Tests\Feature\Admin;

use App\Contracts\Notification\NotificationContract;
use App\Contracts\Settings\ConfigurationRegistryInterface;
use App\Contracts\Settings\SettingsAuthorityInterface;
use App\Models\Setting;
use App\Models\User;
use App\Services\Notification\NotificationAuthorityService;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationSettingsContractTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'email' => 'notification-admin-'.uniqid().'@yalihan.local',
        ]);
    }

    public function test_notification_settings_persist_via_canonical_path(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.ayarlar.bulk-update'), [
                'email_notifications' => '1',
                'whatsapp_notifications' => '0',
                'telegram_notifications' => '1',
            ]);

        $response->assertRedirect(route('admin.ayarlar.index'));

        $registry = app(ConfigurationRegistryInterface::class);

        $this->assertTrue((bool) $registry->get('email_notifications'));
        $this->assertFalse((bool) $registry->get('whatsapp_notifications'));
        $this->assertTrue((bool) $registry->get('telegram_notifications'));
    }

    public function test_notification_settings_reload_returns_persisted_state(): void
    {
        $authority = app(SettingsAuthorityInterface::class);
        $authority->bulkUpdate([
            'email_notifications' => '0',
            'whatsapp_notifications' => '1',
            'telegram_notifications' => '0',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.ayarlar.index'));

        $response->assertOk();
        $response->assertSee('name="whatsapp_notifications"', false);
        $response->assertSee('name="email_notifications"', false);
        $response->assertSee('name="telegram_notifications"', false);

        // Ensure SMS toggle is completely purged from the notification tab
        $response->assertDontSee('name="sms_notifications"', false);
    }

    public function test_notification_authority_respects_disabled_channel(): void
    {
        $authority = app(SettingsAuthorityInterface::class);
        $authority->bulkUpdate([
            'email_notifications' => '0',
            'whatsapp_notifications' => '1',
        ]);

        $dispatcher = $this->createMock(NotificationDispatcher::class);
        // Expect dispatch ONLY for whatsapp, NOT email
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotificationContract $notification) {
                return $notification->getChannel() === 'whatsapp';
            }))
            ->willReturn(true);

        $service = new NotificationAuthorityService($dispatcher);

        $service->notify('booking_requested', [
            'email' => 'guest@example.com',
            'phone' => '+905551234567',
        ]);
    }

    public function test_notification_authority_respects_enabled_channel(): void
    {
        $authority = app(SettingsAuthorityInterface::class);
        $authority->bulkUpdate([
            'email_notifications' => '1',
            'whatsapp_notifications' => '0',
        ]);

        $dispatcher = $this->createMock(NotificationDispatcher::class);
        // Expect dispatch ONLY for email, NOT whatsapp
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotificationContract $notification) {
                return $notification->getChannel() === 'email';
            }))
            ->willReturn(true);

        $service = new NotificationAuthorityService($dispatcher);

        $service->notify('booking_requested', [
            'email' => 'guest@example.com',
            'phone' => '+905551234567',
        ]);
    }

    public function test_notification_authority_missing_setting_defaults_to_enabled(): void
    {
        // Flush all caches and DB settings
        Cache::flush();
        Setting::whereIn('key', ['email_notifications', 'whatsapp_notifications', 'telegram_notifications'])->delete();

        $dispatcher = $this->createMock(NotificationDispatcher::class);
        $service = new NotificationAuthorityService($dispatcher);

        // Canonical operational channels default to true
        $this->assertTrue($service->isChannelEnabled('email'));
        $this->assertTrue($service->isChannelEnabled('whatsapp'));
        $this->assertTrue($service->isChannelEnabled('telegram'));
    }

    public function test_technical_channels_remain_always_enabled(): void
    {
        $dispatcher = $this->createMock(NotificationDispatcher::class);
        $service = new NotificationAuthorityService($dispatcher);

        $this->assertTrue($service->isChannelEnabled('webhook'));
        $this->assertTrue($service->isChannelEnabled('instagram'));
    }

    public function test_unknown_and_sms_channels_fail_closed(): void
    {
        $dispatcher = $this->createMock(NotificationDispatcher::class);
        $service = new NotificationAuthorityService($dispatcher);

        $this->assertFalse($service->isChannelEnabled('sms'));
        $this->assertFalse($service->isChannelEnabled('carrier_pigeon'));
        $this->assertFalse($service->isChannelEnabled('random_invalid_channel'));
    }

    public function test_legacy_notification_settings_routes_redirect_to_canonical_surface(): void
    {
        $getResponse = $this->actingAs($this->admin)
            ->get(route('admin.notifications.settings'));

        $getResponse->assertRedirect(route('admin.ayarlar.index').'#bildirim');

        $postResponse = $this->actingAs($this->admin)
            ->post(route('admin.notifications.settings.update'), [
                'notify_new_listing' => '1',
            ]);

        $postResponse->assertRedirect(route('admin.ayarlar.index').'#bildirim');
    }
}
