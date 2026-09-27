<?php

namespace Tests\Feature\CommandCenter;

use App\DTOs\Command\NormalizedCommandInput;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\Talep;
use App\Models\User;
use App\Modules\TakimYonetimi\Services\TelegramBotService;
use App\Services\CommandCenter\CommandGateway;
use App\Services\SaaS\TenantContextService;
use App\Support\AgentContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelegramWebhookAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'secure-telegram-webhook-token-test';

    private Tenant $victimTenant;

    private User $victimUser;

    protected function setUp(): void
    {
        parent::setUp();

        AgentContext::reset();

        $this->victimTenant = Tenant::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Victim Tenant Emlak',
            'domain' => 'victim-tenant.local',
            'status' => 'active',
        ]);

        $this->victimUser = User::factory()->create([
            'tenant_id' => $this->victimTenant->id,
            'telegram_chat_id' => '999888777666',
        ]);
    }

    /**
     * Helper payload generator
     */
    private function makeTelegramPayload(int $fromId, int $chatId, string $text): array
    {
        return [
            'update_id' => 123456,
            'message' => [
                'message_id' => 789,
                'from' => [
                    'id' => $fromId,
                    'first_name' => 'TestActor',
                ],
                'chat' => [
                    'id' => $chatId,
                ],
                'text' => $text,
            ],
        ];
    }

    /**
     * Requirement A: Missing secret token -> 403 Forbidden (when secret configured)
     */
    public function test_missing_secret_token_returns_403_forbidden(): void
    {
        config(['services.telegram.webhook_secret' => self::WEBHOOK_SECRET]);

        $payload = $this->makeTelegramPayload(999888777666, 999888777666, 'Elimizde €1M\'a ne var?');

        $response = $this->postJson(route('api.telegram.webhook.native'), $payload);

        $response->assertStatus(403);
        $response->assertJson([
            'basarili' => false,
            'hata_mesaji' => 'Yetkisiz erişim.',
        ]);
    }

    /**
     * Requirement B: Wrong secret token -> 403 Forbidden
     */
    public function test_wrong_secret_token_returns_403_forbidden(): void
    {
        config(['services.telegram.webhook_secret' => self::WEBHOOK_SECRET]);

        $payload = $this->makeTelegramPayload(999888777666, 999888777666, 'Elimizde €1M\'a ne var?');

        $response = $this->postJson(
            route('api.telegram.webhook.native'),
            $payload,
            ['X-Telegram-Bot-Api-Secret-Token' => 'invalid-token-value']
        );

        $response->assertStatus(403);
        $response->assertJson([
            'basarili' => false,
            'hata_mesaji' => 'Yetkisiz erişim.',
        ]);
    }

    /**
     * Requirement C: Unconfigured secret token (empty config) -> 500 Internal Server Error (fail-closed)
     */
    public function test_unconfigured_secret_token_returns_500_fail_closed(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $payload = $this->makeTelegramPayload(999888777666, 999888777666, 'Elimizde €1M\'a ne var?');

        $response = $this->postJson(
            route('api.telegram.webhook.native'),
            $payload,
            ['X-Telegram-Bot-Api-Secret-Token' => self::WEBHOOK_SECRET]
        );

        $response->assertStatus(500);
        $response->assertJson([
            'basarili' => false,
            'hata_mesaji' => 'Webhook secret yapılandırılmamış.',
        ]);
    }

    /**
     * Requirement D: Valid secret token + legitimate paired user (matching telegram_chat_id) -> 200 OK and command executed successfully
     */
    public function test_valid_secret_token_and_paired_user_executes_successfully(): void
    {
        config(['services.telegram.webhook_secret' => self::WEBHOOK_SECRET]);

        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text, $options) {
                return $chatId === 999888777666
                    && str_contains($text, 'Yalıhan Portföy Arama Sonuçları');
            })
            ->andReturn(true);

        $this->app->instance(TelegramBotService::class, $telegramMock);

        DB::table('ilanlar')->insert([
            'baslik' => 'Yalıhan Sahil Villası',
            'slug' => 'yalihan-sahil-villasi',
            'fiyat' => 750000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
            'tenant_id' => $this->victimTenant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = $this->makeTelegramPayload(999888777666, 999888777666, 'Elimizde €1M\'a ne var?');

        $response = $this->postJson(
            route('api.telegram.webhook.native'),
            $payload,
            ['X-Telegram-Bot-Api-Secret-Token' => self::WEBHOOK_SECRET]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Requirement E: Attacker sends from.id numerically equal to a user's database id,
     * but whose telegram_chat_id does NOT match -> CommandGateway fails-closed,
     * returns unauthorized ("Yetkisiz Erişim"), and does NOT establish victim tenant context
     */
    public function test_attacker_cannot_impersonate_user_via_numeric_database_id(): void
    {
        config(['services.telegram.webhook_secret' => self::WEBHOOK_SECRET]);

        $attackerFromId = (int) $this->victimUser->id;
        $this->assertNotEquals((string) $attackerFromId, (string) $this->victimUser->telegram_chat_id);

        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->twice()
            ->withArgs(function ($chatId, $text) use ($attackerFromId) {
                return $chatId === $attackerFromId
                    && str_contains($text, 'Yetkisiz Erişim');
            })
            ->andReturn(true);

        $this->app->instance(TelegramBotService::class, $telegramMock);

        $payload = $this->makeTelegramPayload($attackerFromId, $attackerFromId, 'Elimizde €1M\'a ne var?');

        $response = $this->postJson(
            route('api.telegram.webhook.native'),
            $payload,
            ['X-Telegram-Bot-Api-Secret-Token' => self::WEBHOOK_SECRET]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify CommandGateway directly to assert response text and ensure victim tenant context is not established
        $tenantContext = app(TenantContextService::class);
        $tenantContext->clearTenant();

        $gateway = app(CommandGateway::class);
        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: (string) $attackerFromId,
            chatId: (string) $attackerFromId,
            rawText: 'Elimizde €1M\'a ne var?'
        );

        $gatewayResponse = $gateway->process($input);
        $this->assertStringContainsString('Yetkisiz Erişim', $gatewayResponse);
        $this->assertFalse($tenantContext->hasTenant(), 'Victim tenant context must not be established');
    }

    /**
     * Requirement F: Attacker sends from.id numerically equal to a user's database id attempting to create Talep
     * -> Talep / Kisi is NOT created for victim tenant
     */
    public function test_attacker_cannot_create_talep_for_victim_tenant_via_numeric_id(): void
    {
        config(['services.telegram.webhook_secret' => self::WEBHOOK_SECRET]);

        $attackerFromId = (int) $this->victimUser->id;

        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) use ($attackerFromId) {
                return $chatId === $attackerFromId
                    && str_contains($text, 'Yetkisiz Erişim');
            })
            ->andReturn(true);

        $this->app->instance(TelegramBotService::class, $telegramMock);

        $payload = $this->makeTelegramPayload(
            $attackerFromId,
            $attackerFromId,
            'Yeni talep var. Saldırgan Müşteri, 0532 999 88 77, Bodrum villa. €900 bin.'
        );

        $response = $this->postJson(
            route('api.telegram.webhook.native'),
            $payload,
            ['X-Telegram-Bot-Api-Secret-Token' => self::WEBHOOK_SECRET]
        );

        $response->assertStatus(200);

        // Assert NO Talep created in victim tenant or anywhere
        $talepCount = Talep::withoutGlobalScopes()->where('tenant_id', $this->victimTenant->id)->count();
        $this->assertEquals(0, $talepCount, 'No Talep should be created for victim tenant');

        // Assert NO Kisi created for victim tenant
        $kisiCount = Kisi::withoutGlobalScopes()->where('tenant_id', $this->victimTenant->id)->count();
        $this->assertEquals(0, $kisiCount, 'No Kisi should be created for victim tenant');
    }

    /**
     * Requirement G: Attacker cannot obtain victim tenant property search results via forged numeric id
     */
    public function test_attacker_cannot_obtain_victim_tenant_search_results_via_forged_numeric_id(): void
    {
        config(['services.telegram.webhook_secret' => self::WEBHOOK_SECRET]);

        $attackerFromId = (int) $this->victimUser->id;

        // Victim tenant has a confidential property
        DB::table('ilanlar')->insert([
            'baslik' => 'Çok Gizli Yalıhan Malikane Portföyü',
            'slug' => 'cok-gizli-yalihan-malikane-portfoyu',
            'fiyat' => 500000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
            'tenant_id' => $this->victimTenant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) use ($attackerFromId) {
                // Must receive Unauthorized notice, NOT property search results or confidential listings
                return $chatId === $attackerFromId
                    && str_contains($text, 'Yetkisiz Erişim')
                    && ! str_contains($text, 'Çok Gizli Yalıhan Malikane Portföyü')
                    && ! str_contains($text, 'Yalıhan Portföy Arama Sonuçları');
            })
            ->andReturn(true);

        $this->app->instance(TelegramBotService::class, $telegramMock);

        $payload = $this->makeTelegramPayload($attackerFromId, $attackerFromId, 'Elimizde €1M\'a ne var?');

        $response = $this->postJson(
            route('api.telegram.webhook.native'),
            $payload,
            ['X-Telegram-Bot-Api-Secret-Token' => self::WEBHOOK_SECRET]
        );

        $response->assertStatus(200);
    }
}
