<?php

namespace Tests\Feature\AI;

use App\Application\AI\DTOs\ListingAIResultDTO;
use App\Services\AI\AIOrchestrator;
use Illuminate\Support\Facades\Http;
use Tests\SimpleTestCase;

/**
 * 🏆 GOLDEN CONTRACT TEST
 * Verifies the end-to-code AI Infrastructure:
 * Prompt -> Model -> Validation -> Immutable DTO
 */
class AIGoldenContractTest extends \Tests\SimpleTestCase
{
    public function test_listing_generation_golden_path()
    {
        // 🛡️ Mock Tenant Context
        $fakeTenant = new \App\Models\SaaS\Tenant(['uuid' => 'test', 'name' => 'Test', 'domain' => 'test.local', 'status' => 'active']);
        $resolver = \Mockery::mock(\App\Application\Shared\Services\TenantContextResolver::class);
        $resolver->shouldReceive('resolve')->andReturn(new \App\Application\Shared\DTOs\TenantContext(1, 1, 'test-trace'));
        $resolver->shouldReceive('getTenant')->andReturn($fakeTenant);
        $this->app->instance(\App\Application\Shared\Services\TenantContextResolver::class, $resolver);

        // 🛡️ Mock AiBudgetGuard — ai_credit_balances tablosu test DB'de yok
        $budgetGuard = \Mockery::mock(\App\Services\AI\Monetization\AiBudgetGuard::class);
        $budgetGuard->shouldReceive('canExecute')->andReturn(true);
        $budgetGuard->shouldReceive('deductCredits')->andReturnNull();
        $this->app->instance(\App\Services\AI\Monetization\AiBudgetGuard::class, $budgetGuard);

        // 1. Mock the AI Provider (DeepSeek)
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'baslik' => 'Lüks Villa',
                                'aciklama' => 'Havuzlu muhteşem villa.',
                                'type' => 'Satılık', // Testing Self-Healing (type -> tip)
                                'kategori' => 'Konut',
                                'ozellikler' => ['Havuz', 'Bahçe'],
                                'one_cikanlar' => ['Deniz Manzaralı']
                            ]),
                            'role' => 'assistant'
                        ]
                    ]
                ],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20],
                'model' => 'deepseek-chat'
            ], 200)
        ]);

        $orchestrator = app(AIOrchestrator::class);

        // 2. Execute the Golden Flow
        $result = $orchestrator->generateListing(['prompt' => 'deniz manzaralı villa']);

        // 3. Assert Contract Integrity
        $this->assertInstanceOf(\App\Services\AI\DTO\ListingAIResultData::class, $result);
        $this->assertEquals('Lüks Villa', $result->baslik);
        $this->assertEquals('Satılık', $result->tip); // Verified Self-Healing
        $this->assertEquals('Konut', $result->kategori);
        $this->assertCount(2, $result->ozellikler);
        $this->assertCount(1, $result->one_cikanlar);
    }

    public function test_listing_generation_fails_on_invalid_schema()
    {
        // 🛡️ Mock Tenant Context
        $fakeTenant = new \App\Models\SaaS\Tenant(['uuid' => 'test', 'name' => 'Test', 'domain' => 'test.local', 'status' => 'active']);
        $resolver = \Mockery::mock(\App\Application\Shared\Services\TenantContextResolver::class);
        $resolver->shouldReceive('resolve')->andReturn(new \App\Application\Shared\DTOs\TenantContext(1, 1, 'test-trace'));
        $resolver->shouldReceive('getTenant')->andReturn($fakeTenant);
        $this->app->instance(\App\Application\Shared\Services\TenantContextResolver::class, $resolver);

        // 🛡️ Mock AiBudgetGuard — ai_credit_balances tablosu test DB'de yok
        $budgetGuard = \Mockery::mock(\App\Services\AI\Monetization\AiBudgetGuard::class);
        $budgetGuard->shouldReceive('canExecute')->andReturn(true);
        $budgetGuard->shouldReceive('deductCredits')->andReturnNull();
        $this->app->instance(\App\Services\AI\Monetization\AiBudgetGuard::class, $budgetGuard);

        // Mock invalid response (missing fields)
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['oops' => 'invalid'])]]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
                'model' => 'deepseek-v4-flash'
            ], 200)
        ]);

        $this->expectException(\App\Domain\AI\Exceptions\InvalidAIResponseException::class);

        $orchestrator = app(AIOrchestrator::class);
        $orchestrator->generateListing(['prompt' => 'test']);
    }
}
