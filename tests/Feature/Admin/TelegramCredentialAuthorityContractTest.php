<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Modules\TakimYonetimi\Services\TelegramBotService;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramCredentialAuthorityContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure clean test baseline
        config([
            'services.telegram.bot_token' => 'configured_test_token_123',
            'services.telegram.bot_username' => 'TestBot',
            'services.telegram.admin_chat_id' => '999888777',
        ]);
    }

    /**
     * Contract 1 & 2: TelegramService credential resolution strictly uses config authority
     * and ignores any rogue settings.telegram_bot_token DB entry.
     */
    public function test_telegram_service_credential_resolution_ignores_settings_db_token(): void
    {
        // Insert rogue token into settings table
        Setting::create([
            'key' => 'telegram_bot_token',
            'value' => 'rogue_db_token_override',
            'group' => 'telegram',
            'type' => 'string',
        ]);

        $service = app(TelegramService::class);

        // Reflection to inspect private botToken
        $ref = new \ReflectionClass($service);
        $prop = $ref->getProperty('botToken');
        $prop->setAccessible(true);
        $resolvedToken = $prop->getValue($service);

        $this->assertEquals('configured_test_token_123', $resolvedToken);
        $this->assertNotEquals('rogue_db_token_override', $resolvedToken);
    }

    /**
     * Contract 3: TelegramBotService uses the same configured token authority.
     */
    public function test_telegram_bot_service_uses_configured_token_authority(): void
    {
        $botService = app(TelegramBotService::class);

        $ref = new \ReflectionClass($botService);
        $prop = $ref->getProperty('botToken');
        $prop->setAccessible(true);
        $resolvedToken = $prop->getValue($botService);

        $this->assertEquals('configured_test_token_123', $resolvedToken);
    }

    /**
     * Contract 4: Admin update endpoint cannot mutate bot_token.
     */
    public function test_admin_update_endpoint_rejects_bot_token_mutation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.telegram-bot.update-settings'), [
                'bot_token' => 'malicious_injected_token',
                'team_id' => 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Telegram bot token is deployment-managed and cannot be modified via the admin panel.',
        ]);
    }

    /**
     * Contract 5: TelegramBotService::updateSettings() cannot mutate .env.
     */
    public function test_telegram_bot_service_update_settings_does_not_mutate_env(): void
    {
        $envPath = base_path('.env');
        $mtimeBefore = file_exists($envPath) ? filemtime($envPath) : null;

        $botService = app(TelegramBotService::class);
        $result = $botService->updateSettings([
            'bot_token' => 'attempted_token',
            'chat_id' => '12345',
        ]);

        $this->assertTrue($result['success']);

        if (file_exists($envPath)) {
            $mtimeAfter = filemtime($envPath);
            $this->assertEquals($mtimeBefore, $mtimeAfter, 'The .env file was modified unexpectedly!');
        }
    }

    /**
     * Contract 6: Admin settings/status response does not expose raw bot token.
     */
    public function test_telegram_bot_service_get_settings_does_not_expose_raw_token(): void
    {
        $botService = app(TelegramBotService::class);
        $settings = $botService->getSettings();

        $this->assertArrayNotHasKey('bot_token', $settings);
        $this->assertArrayHasKey('bot_token_configured', $settings);
        $this->assertTrue($settings['bot_token_configured']);
    }

    /**
     * Contract 7: Existing team_id and telegram_channel_id behavior is preserved.
     */
    public function test_admin_can_update_team_channel_id(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.telegram-bot.update-settings'), [
                'team_id' => 1,
                'telegram_channel_id' => '-100987654321',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertEquals('-100987654321', Setting::get('team:1:telegram_channel_id'));
    }

    /**
     * Contract 8 (F4): TelegramBotService explicitly supports cache invalidation.
     */
    public function test_telegram_bot_service_clear_settings_cache(): void
    {
        \Illuminate\Support\Facades\Cache::put('telegram_settings', ['test' => 123], 3600);
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has('telegram_settings'));

        $botService = app(TelegramBotService::class);
        $botService->clearSettingsCache();

        $this->assertFalse(\Illuminate\Support\Facades\Cache::has('telegram_settings'));

        $result = $botService->updateSettings();
        $this->assertTrue($result['success']);
        $this->assertEquals('Ayarlar önbelleği temizlendi', $result['message']);
    }

    /**
     * Contract 9 (F5): Legacy /admin/telegram redirects to canonical /admin/telegram-bot.
     */
    public function test_legacy_admin_telegram_route_redirects_to_canonical(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.telegram.index'));

        $response->assertRedirect(route('admin.telegram-bot.index'));
    }
}
