<?php

namespace Tests\Feature\AI;

use App\Application\Shared\DTOs\TenantContext;
use App\Application\Shared\Exceptions\TenantContextMissingException;
use App\Models\User;
use App\Models\SaaS\Tenant;
use App\Services\AI\AIOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected AIOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orchestrator = app(AIOrchestrator::class);
    }

    /** @test */
    public function it_fails_if_no_tenant_context_is_present()
    {
        $this->expectException(TenantContextMissingException::class);

        // No user authenticated
        $this->orchestrator->generateListing(['prompt' => 'test']);
    }

    /** @test */
    public function it_succeeds_if_tenant_context_is_present()
    {
        $tenant = Tenant::create(['name' => 'Test Tenant', 'slug' => 'test-tenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        $this->actingAs($user);

        // Mock the provider to avoid real API calls
        $this->mock(\App\Services\AI\Providers\DeepSeekCortexProvider::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->andReturn(
                new \App\Application\AI\DTOs\CortexResponseData(
                    success: true,
                    output: ['baslik' => 'Test', 'aciklama' => 'Test', 'tip' => 'Konut', 'kategori' => 'Satılık', 'ozellikler' => [], 'one_cikanlar' => []],
                    rawText: '{}',
                    usage: new \App\Application\AI\DTOs\CortexUsage(10, 10, 20),
                    provider: 'deepseek'
                )
            );
        });

        $result = $this->orchestrator->generateListing(['prompt' => 'test']);

        $this->assertInstanceOf(\App\Services\AI\DTO\ListingAIResultData::class, $result);
    }
}
