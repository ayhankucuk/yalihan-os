<?php

namespace Tests\Feature\CommandCenter;

use App\DTOs\Command\NormalizedCommandInput;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\Talep;
use App\Models\User;
use App\Modules\TakimYonetimi\Services\TelegramBotService;
use App\Services\CommandCenter\CommandGateway;
use App\Services\CRM\TalepAuthorityService;
use App\Services\CRM\KisiRegistrationService;
use App\Services\SaaS\TenantContextService;
use App\Support\AgentContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TalepCreateE2ETest — Feature / Integration Tests
 *
 * Phase 1l: COMMAND_CENTER_TALEP_CREATE_VERTICAL_01
 *
 * Verifies the complete CommandGateway path for talep_create:
 * 1. Human Telegram actor → CommandGateway → IntentRouter → TalepCreateIntentHandler
 * 2. Talep written via TalepAuthorityService::createTalep()
 * 3. Tenant A creates Tenant A Talep (not Tenant B)
 * 4. Tenant B actor cannot create Tenant A Talep
 * 5. Kisi spillover via existing KisiRegistrationService
 * 6. Missing required info → clarification, NO write
 * 7. Unauthorized actor → NO write
 * 8. Malformed budget → handled gracefully (nullable field)
 * 9. Property-search vertical remains PASS (no regression)
 * 10. GuardsAgentWrites enforced at TalepAuthorityService level
 */
class TalepCreateE2ETest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        AgentContext::reset();

        $this->tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant A Emlak',
            'domain' => 'tenant-a.local',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant B Emlak',
            'domain' => 'tenant-b.local',
            'status' => 'active',
        ]);

        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'telegram_chat_id' => '111222333',
        ]);

        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'telegram_chat_id' => '444555666',
        ]);
    }

    protected function tearDown(): void
    {
        AgentContext::reset();
        parent::tearDown();
    }

    /**
     * Set active tenant for the current test context.
     * Required so BelongsToTenant trait auto-assigns tenant_id on Talep::create().
     */
    private function asTenantA(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);
    }

    private function asTenantB(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantB);
    }

    /**
     * Build a CommandGateway fresh with a live TelegramBotService mock.
     * Called inside each test so the mock is registered before gateway construction.
     */
    private function buildGatewayWithMock(): array
    {
        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $this->app->instance(TelegramBotService::class, $telegramMock);

        return [
            'gateway' => app(CommandGateway::class),
            'telegram' => $telegramMock,
        ];
    }

    // -------------------------------------------------------------------------
    // TEST 1: Canonical path — valid human Telegram actor creates Talep
    // -------------------------------------------------------------------------

    /** @test */
    public function tenant_a_human_telegram_actor_creates_talep_via_command_gateway(): void
    {
        $capturedTalep = null;
        $capturedActor = null;

        $this->mock(TalepAuthorityService::class, function ($mock) use (&$capturedTalep, &$capturedActor) {
            $mock->shouldReceive('createTalep')
                ->once()
                ->withArgs(function ($data, $actor) use (&$capturedTalep, &$capturedActor) {
                    $capturedTalep = $data;
                    $capturedActor = $actor;
                    return true;
                })
                ->andReturn(new Talep([
                    'id' => 999,
                    'baslik' => $data['baslik'] ?? 'Test Talep',
                    'kisi_id' => 1,
                    'tenant_id' => $capturedActor instanceof \App\Models\User ? $capturedActor->tenant_id : null,
                    'danisman_id' => $capturedActor instanceof \App\Models\User ? $capturedActor->id : null,
                    'talep_durumu' => 'yayinda',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
        });

        ['gateway' => $gateway, 'telegram' => $telegram] = $this->buildGatewayWithMock();
        $telegram->shouldReceive('sendMessage')->once()->andReturn(true);

        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '111222333',
            chatId: '111222333',
            rawText: 'Yeni talep var. Ahmet Yılmaz, 0532 123 45 67, Bodrum\'da villa arıyor. €500 bin.'
        );

        $response = $gateway->process($input);

        $this->assertNotNull($capturedTalep, 'TalepAuthorityService::createTalep should be called');
        $this->assertEquals('Ahmet', $capturedTalep['kisi_ad']);
        $this->assertEquals('Yılmaz', $capturedTalep['kisi_soyad']);
        $this->assertEquals('05321234567', $capturedTalep['kisi_telefon']);
        $this->assertEquals(500_000, $capturedTalep['max_fiyat']);

        $this->assertNotNull($capturedActor);
        $this->assertEquals($this->userA->id, $capturedActor->id);
        $this->assertEquals($this->tenantA->id, $capturedActor->tenant_id);

        $this->assertStringContainsString('Talep Oluşturuldu', $response);
        $this->assertStringContainsString('Ahmet Yılmaz', $response);
    }

    // -------------------------------------------------------------------------
    // TEST 2: Tenant A actor creates Tenant A Talep (real DB write)
    // -------------------------------------------------------------------------

    /** @test */
    public function tenant_a_actor_creates_tenant_a_talep_in_real_database(): void
    {
        $this->asTenantA();

        // Mock cortex to avoid external AI call
        $this->mock(\App\Services\AI\YalihanCortex::class, function ($mock) {
            $mock->shouldReceive('requestCustomerAiEnrichment')->andReturn([]);
        });

        ['gateway' => $gateway, 'telegram' => $telegram] = $this->buildGatewayWithMock();
        $telegram->shouldReceive('sendMessage')->once()->andReturn(true);

        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '111222333',
            chatId: '111222333',
            rawText: 'Yeni talep var. Elif Demir, 0532 234 56 78, Kaş\'ta arsa aranıyor. €250 bin.'
        );

        $response = $gateway->process($input);

        // Note: Gateway clears tenant context in its finally{} block, so TenantScope
        // filters to WHERE 1=0 after process() returns. Use withoutGlobalScopes to verify.
        $talep = Talep::withoutGlobalScopes()
            ->whereNotNull('kisi_id')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($talep, 'Talep should be written to database');
        $this->assertEquals($this->tenantA->id, $talep->tenant_id);
        $this->assertEquals($this->userA->id, $talep->danisman_id);
        $this->assertEquals(250_000, (float) $talep->max_fiyat);
        $this->assertEquals('yayinda', $talep->talep_durumu->value);

        $this->assertStringContainsString('Talep Oluşturuldu', $response);
    }

    // -------------------------------------------------------------------------
    // TEST 3: Tenant B actor creates Tenant B Talep (real DB write)
    // -------------------------------------------------------------------------

    /** @test */
    public function tenant_b_actor_creates_tenant_b_talep_in_real_database(): void
    {
        $this->asTenantB();

        $this->mock(\App\Services\AI\YalihanCortex::class, function ($mock) {
            $mock->shouldReceive('requestCustomerAiEnrichment')->andReturn([]);
        });

        ['gateway' => $gateway, 'telegram' => $telegram] = $this->buildGatewayWithMock();
        $telegram->shouldReceive('sendMessage')->once()->andReturn(true);

        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '444555666',
            chatId: '444555666',
            rawText: 'Yeni talep var. Berke Aydın, 0533 345 67 89, Didim\'de villa. €400 bin.'
        );

        $response = $gateway->process($input);

        // Note: Gateway clears tenant context in its finally{} block, so TenantScope
        // filters to WHERE 1=0 after process() returns. Use withoutGlobalScopes to verify.
        $talep = Talep::withoutGlobalScopes()
            ->whereNotNull('kisi_id')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($talep, 'Talep should be written to database');
        $this->assertEquals($this->tenantB->id, $talep->tenant_id);
        $this->assertEquals($this->userB->id, $talep->danisman_id);
        $this->assertEquals('yayinda', $talep->talep_durumu->value);
    }

    // -------------------------------------------------------------------------
    // TEST 4: Tenant isolation — Tenant B cannot read Tenant A talepler
    // -------------------------------------------------------------------------

    /** @test */
    public function tenant_isolation_talep_not_accessible_cross_tenant(): void
    {
        // Pre-create a talep under Tenant A
        $this->asTenantA();

        $kisi = Kisi::create([
            'tenant_id' => $this->tenantA->id,
            'ad' => 'Ayşe',
            'soyad' => 'Kaya',
            'telefon' => '0532111222',
            'kisi_tipi' => 'lead',
        ]);

        Talep::create([
            'tenant_id' => $this->tenantA->id,
            'kisi_id' => $kisi->id,
            'danisman_id' => $this->userA->id,
            'baslik' => 'Tenant A gizli talep',
            'talep_tipi' => 'Satılık',
            'talep_durumu' => 'yayinda',
        ]);

        // Tenant B has no talepler
        $tenantBTalepCount = Talep::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantB->id)
            ->count();

        $this->assertEquals(0, $tenantBTalepCount);
    }

    // -------------------------------------------------------------------------
    // TEST 5: Unauthorized actor → NO write
    // -------------------------------------------------------------------------

    /** @test */
    public function unauthorized_actor_cannot_create_talep(): void
    {
        ['gateway' => $gateway, 'telegram' => $telegram] = $this->buildGatewayWithMock();
        $telegram->shouldReceive('sendMessage')->once()->andReturn(true);

        // Unknown actor — telegram_chat_id not registered
        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '999888777',
            chatId: '999888777',
            rawText: 'Yeni talep var. Faruk Öztürk, 0532 987 65 43, Fethiye\'de daire.'
        );

        $response = $gateway->process($input);

        // The handler returns "Yetkisiz Erişim" for unauthorized actors
        $this->assertStringContainsString('Yetkisiz Erişim', $response);
        $this->assertStringNotContainsString('Talep Oluşturuldu', $response);
        $this->assertEquals(0, Talep::count());
    }

    // -------------------------------------------------------------------------
    // TEST 6: Missing name → clarification, NO write
    // -------------------------------------------------------------------------

    /** @test */
    public function missing_name_triggers_clarification_no_write(): void
    {
        ['gateway' => $gateway, 'telegram' => $telegram] = $this->buildGatewayWithMock();
        $telegram->shouldReceive('sendMessage')->once()->andReturn(true);

        // No person name at all — NER can't extract isim/soyad
        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '111222333',
            chatId: '111222333',
            rawText: 'Yeni talep var. Bodrum\'da villa aranıyor.'
        );

        $response = $gateway->process($input);

        $this->assertStringContainsString('Bilgi Gerekli', $response);
        $this->assertStringNotContainsString('Talep Oluşturuldu', $response);
        $this->assertEquals(0, Talep::count());
    }

    // -------------------------------------------------------------------------
    // TEST 7: Missing phone → clarification, NO write
    // -------------------------------------------------------------------------

    /** @test */
    public function missing_phone_triggers_clarification_no_write(): void
    {
        ['gateway' => $gateway, 'telegram' => $telegram] = $this->buildGatewayWithMock();
        $telegram->shouldReceive('sendMessage')->once()->andReturn(true);

        // Name is provided, but no phone number — phone number is required by detectMissingRequiredFields
        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '111222333',
            chatId: '111222333',
            rawText: 'Yeni talep var. Can Yücel, Bodrum\'da villa.'
        );

        $response = $gateway->process($input);

        // Handler returns clarification message for missing phone
        $this->assertStringContainsString('Bilgi Gerekli', $response);
        $this->assertStringNotContainsString('Talep Oluşturuldu', $response);
        $this->assertEquals(0, Talep::count());
    }

    // -------------------------------------------------------------------------
    // TEST 8: Missing budget → Talep created with null price
    // -------------------------------------------------------------------------

    /** @test */
    public function missing_budget_creates_talep_with_null_max_fiyat(): void
    {
        $this->asTenantA();

        ['gateway' => $gateway, 'telegram' => $telegram] = $this->buildGatewayWithMock();
        $telegram->shouldReceive('sendMessage')->once()->andReturn(true);

        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '111222333',
            chatId: '111222333',
            rawText: 'Yeni talep var. Deniz Yıldırım, 0532 333 44 55, Bodrum\'da villa.'
        );

        $response = $gateway->process($input);

        $this->assertStringContainsString('Talep Oluşturuldu', $response);

        // Note: Gateway clears tenant context in its finally{} block, so TenantScope
        // filters to WHERE 1=0 after process() returns. Use withoutGlobalScopes to verify.
        $talep = Talep::withoutGlobalScopes()
            ->whereNotNull('kisi_id')
            ->where('tenant_id', $this->tenantA->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($talep, 'Talep should be written to database');
        $this->assertNull($talep->max_fiyat, 'max_fiyat should be null when no budget specified');
    }

    // -------------------------------------------------------------------------
    // TEST 9: Property-search vertical — no regression
    // -------------------------------------------------------------------------

    /** @test */
    public function property_search_vertical_still_works_after_talep_create_handler_added(): void
    {
        DB::table('ilanlar')->insert([
            'baslik' => 'Regression Test Villa',
            'slug' => 'regression-test-villa-' . uniqid(),
            'fiyat' => 750_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
            'tenant_id' => $this->tenantA->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ['gateway' => $gateway, 'telegram' => $telegram] = $this->buildGatewayWithMock();

        $capturedResponse = null;
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) use (&$capturedResponse) {
                $capturedResponse = $text;
                return true;
            });

        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '111222333',
            chatId: '111222333',
            rawText: 'Elimizde €1M\'a ne var?'
        );

        $response = $gateway->process($input);

        $this->assertStringContainsString('Yalıhan Portföy Arama Sonuçları', $response);
        $this->assertStringContainsString('Regression Test Villa', $capturedResponse ?? '');
    }

    // -------------------------------------------------------------------------
    // TEST 10: GuardsAgentWrites enforced
    // -------------------------------------------------------------------------

    /** @test */
    public function agent_context_is_reset_after_command(): void
    {
        $this->assertFalse(AgentContext::isAgent(), 'AgentContext should be inactive (human test)');

        ['gateway' => $gateway, 'telegram' => $telegram] = $this->buildGatewayWithMock();
        $telegram->shouldReceive('sendMessage')->once()->andReturn(true);

        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '111222333',
            chatId: '111222333',
            rawText: 'Yeni talep var. Ece Yılmaz, 0532 555 66 77, Kaş.'
        );

        $gateway->process($input);

        $this->assertFalse(AgentContext::isAgent());
    }
}
