<?php

namespace Tests\Feature\AI;

use App\Application\AI\DTOs\CortexResponseData;
use App\Application\AI\DTOs\CortexUsage;
use App\Application\Shared\Exceptions\TenantContextMissingException;
use App\Models\AI\AiCreditBalance;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\Providers\DeepSeekCortexProvider;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    /** @test */
    public function it_fails_if_no_tenant_context_is_present(): void
    {
        // TenantContextResolver::resolve() checks auth()->user().
        // With no authenticated user, it throws TenantContextMissingException.
        $this->expectException(TenantContextMissingException::class);

        // app() resolves a fresh orchestrator; no actingAs → no auth user
        app(AIOrchestrator::class)->generateListing(['prompt' => 'test']);
    }

    /** @test */
    public function it_succeeds_if_tenant_context_is_present(): void
    {
        $tenant = Tenant::create(['name' => 'AI Test Tenant', 'domain' => 'ai-test.local']);
        $user   = User::factory()->create(['tenant_id' => $tenant->id]);

        // AiBudgetGuard requires ai_credit_balances record for the tenant
        AiCreditBalance::create([
            'tenant_id'         => $tenant->id,
            'available_credits' => 1000,
            'used_credits'      => 0,
            'monthly_limit'     => 10000,
        ]);

        $this->actingAs($user);

        // rawText must pass ListingAIResponseValidator (requires baslik, aciklama, tip, kategori)
        $validRawText = json_encode([
            'baslik'      => 'Test İlan Başlığı',
            'aciklama'    => 'Test ilan açıklaması',
            'tip'         => 'Konut',
            'kategori'    => 'Satılık',
            'ozellikler'  => ['Isıtma', 'Otopark'],
            'one_cikanlar' => ['Merkezi konum'],
        ]);

        // Mock provider BEFORE resolving AIOrchestrator from container
        $this->mock(DeepSeekCortexProvider::class, function ($mock) use ($validRawText) {
            $mock->shouldReceive('execute')->once()->andReturn(
                new CortexResponseData(
                    success: true,
                    output: json_decode($validRawText, true),
                    rawText: $validRawText,
                    usage: new CortexUsage(10, 10, 20),
                    provider: 'deepseek'
                )
            );
        });

        // Resolve AFTER mock is registered so constructor injection gets the mock
        $result = app(AIOrchestrator::class)->generateListing(['prompt' => 'test']);

        $this->assertInstanceOf(\App\Services\AI\DTO\ListingAIResultData::class, $result);
        $this->assertEquals('Test İlan Başlığı', $result->baslik);
    }
}
