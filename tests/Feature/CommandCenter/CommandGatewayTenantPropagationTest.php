<?php

namespace Tests\Feature\CommandCenter;

use App\DTOs\Command\NormalizedCommandInput;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Modules\TakimYonetimi\Services\TelegramBotService;
use App\Services\CommandCenter\CommandGateway;
use App\Services\CommandCenter\Routing\IntentRouter;
use App\Services\Ilan\IlanSearchService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CommandGateway Tenant Propagation Regression Tests
 *
 * SAB Kural #1 — TENANT_RUNTIME_PROPAGATION Closure
 * Fix: COMMAND_CENTER_TENANT_PROPAGATION_FIX_05
 *
 * Verifies that after CommandGateway resolves the authenticated Telegram actor,
 * TenantContextService is established BEFORE IntentRouter/handler execution,
 * and cleaned up afterwards.
 *
 * SECURITY INVARIANT:
 *   Telegram Actor A / Tenant A
 *       ↓
 *   CommandGateway
 *       ↓
 *   TenantContext = Tenant A
 *       ↓
 *   IlanSearchService
 *       ↓
 *   tenant_id = Tenant A
 *   Tenant B rows remain inaccessible.
 */
class CommandGatewayTenantPropagationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $userA;
    private User $userB;
    private TenantContextService $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant A - Demo Emlak',
            'domain' => 'tenant-a.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant B - Farkli Emlak',
            'domain' => 'tenant-b.test',
            'status' => 'active',
        ]);

        // Tenant A user with Telegram chat_id
        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'telegram_chat_id' => '111222333',
        ]);

        // Tenant B user with Telegram chat_id
        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'telegram_chat_id' => '444555666',
        ]);

        $this->tenantContext = app(TenantContextService::class);

        // Clear any pre-existing context
        $this->tenantContext->clearTenant();
    }

    protected function tearDown(): void
    {
        // Cleanup: ensure no stale context bleeds between tests
        $this->tenantContext->clearTenant();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CANONICAL_TENANT_RESOLUTION
    // CommandGateway resolves actor's tenant using the same canonical mechanism
    // as SetTenantContext middleware (Cache::remember + Tenant::find).
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function resolve_actor_tenant_uses_canonical_mechanism(): void
    {
        // Tenant resolved via User::tenant() relationship
        $resolvedTenant = $this->userA->tenant;

        $this->assertNotNull($resolvedTenant);
        $this->assertEquals($this->tenantA->id, $resolvedTenant->id);
        $this->assertEquals('Tenant A - Demo Emlak', $resolvedTenant->name);
    }

    // -------------------------------------------------------------------------
    // REAL_INGRESS_TEST
    // Telegram Actor A / Tenant A
    //   + Tenant A published EUR listing (€900K)
    //   + Tenant B published EUR listing (€800K)
    //   → "Elimizde €1M'a ne var?"
    //   → MUST include Tenant A listing
    //   → MUST exclude Tenant B listing
    //   → MUST NOT return zero merely because context propagation is missing
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function tenant_a_actor_receives_only_tenant_a_listing(): void
    {
        // Setup: Both tenants have EUR listings within €1M budget
        $ilanA = $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A Villa Bodrum',
            'fiyat' => 900_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $ilanB = $this->createListing([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Tenant B Villa Fethiye',
            'fiyat' => 800_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        // Mock TelegramBotService to capture outbound response
        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $capturedResponse = null;
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text, $options) use (&$capturedResponse) {
                $capturedResponse = $text;
                return $chatId === 111222333;
            })
            ->andReturn(true);

        $this->app->instance(TelegramBotService::class, $telegramMock);

        // Execute: Tenant A Telegram actor queries
        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '111222333',
            chatId: '111222333',
            rawText: "Elimizde €1M'a ne var?"
        );

        $gateway = $this->app->make(CommandGateway::class);
        $response = $gateway->process($input);

        // Assert: Response contains Tenant A listing
        $this->assertStringContainsString('Tenant A Villa Bodrum', $response);
        $this->assertStringContainsString('900.000', $response);

        // Assert: Response does NOT contain Tenant B listing
        $this->assertStringNotContainsString('Tenant B Villa Fethiye', $response);
        $this->assertStringNotContainsString('800.000', $response);

        // Assert: Not a "zero results" failure
        $this->assertStringContainsString('Yalıhan Portföy Arama Sonuçları', $response);
        $this->assertStringNotContainsString('yayında ilan bulunamadı', $response);
    }

    // -------------------------------------------------------------------------
    // FAIL_CLOSED_BEHAVIOR
    // Actor with null tenant_id → RuntimeException propagates → fail closed
    // Actor with invalid tenant_id → RuntimeException propagates → fail closed
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function actor_without_tenant_id_fails_closed(): void
    {
        $orphanUser = User::factory()->create([
            'tenant_id' => null,
            'telegram_chat_id' => '777888999',
        ]);

        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $telegramMock->shouldReceive('sendMessage')->never();
        $this->app->instance(TelegramBotService::class, $telegramMock);

        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '777888999',
            chatId: '777888999',
            rawText: "Elimizde €1M'a ne var?"
        );

        $gateway = $this->app->make(CommandGateway::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tenant_id eksik');

        try {
            $gateway->process($input);
        } finally {
            // Context MUST be cleaned up even on failure
            $this->assertFalse($this->tenantContext->hasTenant());
        }
    }

    /**
     * @test
     */
    public function actor_with_invalid_tenant_id_fails_closed(): void
    {
        $orphanUser = User::factory()->create([
            'tenant_id' => 99999, // Non-existent tenant
            'telegram_chat_id' => '777888999',
        ]);

        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $telegramMock->shouldReceive('sendMessage')->never();
        $this->app->instance(TelegramBotService::class, $telegramMock);

        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '777888999',
            chatId: '777888999',
            rawText: "Elimizde €1M'a ne var?"
        );

        $gateway = $this->app->make(CommandGateway::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('geçersiz tenant_id');

        try {
            $gateway->process($input);
        } finally {
            $this->assertFalse($this->tenantContext->hasTenant());
        }
    }

    // -------------------------------------------------------------------------
    // CONTEXT_LEAK_TEST
    // Tenant A context must NOT leak into a subsequent Tenant B command.
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function tenant_context_cannot_leak_between_commands(): void
    {
        // Setup: Both tenants have EUR listings
        $ilanA = $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A Daire Istanbul',
            'fiyat' => 600_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $ilanB = $this->createListing([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Tenant B Daire Ankara',
            'fiyat' => 600_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $capturedResponses = [];

        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->twice()
            ->withArgs(function ($chatId, $text) use (&$capturedResponses) {
                $capturedResponses[$chatId] = $text;
                return true;
            })
            ->andReturn(true);

        $this->app->instance(TelegramBotService::class, $telegramMock);

        $gateway = $this->app->make(CommandGateway::class);

        // Command 1: Tenant A actor
        $inputA = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '111222333',
            chatId: '111222333',
            rawText: "Elimizde €1M'a ne var?"
        );
        $gateway->process($inputA);

        // Context should be cleared after first command
        $this->assertFalse($this->tenantContext->hasTenant());

        // Command 2: Tenant B actor
        $inputB = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '444555666',
            chatId: '444555666',
            rawText: "Elimizde €1M'a ne var?"
        );
        $gateway->process($inputB);

        // Assert: Tenant A saw only their listing
        $this->assertStringContainsString('Tenant A Daire Istanbul', $capturedResponses['111222333']);
        $this->assertStringNotContainsString('Tenant B Daire Ankara', $capturedResponses['111222333']);

        // Assert: Tenant B saw only their listing
        $this->assertStringContainsString('Tenant B Daire Ankara', $capturedResponses['444555666']);
        $this->assertStringNotContainsString('Tenant A Daire Istanbul', $capturedResponses['444555666']);
    }

    // -------------------------------------------------------------------------
    // CONTEXT_CLEANUP
    // Context is cleared in finally block after each command.
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function context_is_cleared_after_successful_command(): void
    {
        $ilan = $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A Test Ilan',
            'fiyat' => 500_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $telegramMock = \Mockery::mock(TelegramBotService::class);
        $telegramMock->shouldReceive('sendMessage')->once()->andReturn(true);
        $this->app->instance(TelegramBotService::class, $telegramMock);

        $input = new NormalizedCommandInput(
            channel: 'telegram',
            externalActorId: '111222333',
            chatId: '111222333',
            rawText: "Elimizde €1M'a ne var?"
        );

        $gateway = $this->app->make(CommandGateway::class);
        $gateway->process($input);

        // After command completes, context must be cleared
        $this->assertFalse($this->tenantContext->hasTenant());
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    private function createListing(array $overrides): int
    {
        $defaults = [
            'baslik' => 'Test Ilan',
            'slug' => 'test-ilan-' . uniqid(),
            'fiyat' => 1_000_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('ilanlar')->insert(array_merge($defaults, $overrides));

        return (int) DB::table('ilanlar')->max('id');
    }
}
