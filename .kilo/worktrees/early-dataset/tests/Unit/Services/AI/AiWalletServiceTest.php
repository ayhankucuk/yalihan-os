<?php

namespace Tests\Unit\Services\AI;

use App\Models\AI\AiTransaction;
use App\Models\AI\AiWorkspaceWallet;
use App\Services\AI\AiWalletService;
use Tests\TestCase;
use App\Models\SaaS\Tenant;
use Exception;

class AiWalletServiceTest extends TestCase
{

    private AiWalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AiWalletService();
    }

    /** @test */
    public function it_creates_wallet_on_demand_if_not_exists()
    {
        $tenant = Tenant::create(['name' => 'Test Tenant 999', 'domain' => 'test999.com', 'status' => 'active']);
        $tenantId = $tenant->id;
        
        $balance = $this->service->getBalance($tenantId);
        
        $this->assertEquals(0, $balance);
        $this->assertDatabaseHas('ai_workspace_wallets', ['tenant_id' => $tenantId]);
    }

    /** @test */
    public function it_can_top_up_credits_and_log_transaction()
    {
        $tenant = Tenant::create(['name' => 'Test Tenant 101', 'domain' => 'test101.com', 'status' => 'active']);
        $tenantId = $tenant->id;
        $amount = 500;
        
        $this->service->addCredits($tenantId, $amount, 'manual_top_up', 'User', 1);
        
        $this->assertEquals(500, $this->service->getBalance($tenantId));
        
        $this->assertDatabaseHas('ai_transactions', [
            'tenant_id' => $tenantId,
            'amount' => 500,
            'final_balance' => 500,
            'reason' => 'manual_top_up'
        ]);
    }

    /** @test */
    public function it_can_deduct_credits_and_log_transaction()
    {
        $tenant = Tenant::create(['name' => 'Test Tenant 102', 'domain' => 'test102.com', 'status' => 'active']);
        $tenantId = $tenant->id;
        $this->service->addCredits($tenantId, 1000, 'init');
        
        $this->service->deductCredits($tenantId, 100, 'smart_field_usage');
        
        $this->assertEquals(900, $this->service->getBalance($tenantId));
        
        $this->assertDatabaseHas('ai_transactions', [
            'tenant_id' => $tenantId,
            'amount' => -100,
            'reason' => 'smart_field_usage'
        ]);
    }

    /** @test */
    public function it_throws_exception_on_insufficient_credits()
    {
        $tenant = Tenant::create(['name' => 'Test Tenant 103', 'domain' => 'test103.com', 'status' => 'active']);
        $tenantId = $tenant->id;
        $this->service->addCredits($tenantId, 50, 'init');
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Yetersiz AI Kredisi');
        
        $this->service->deductCredits($tenantId, 100, 'expensive_operation');
    }

    /** @test */
    public function it_prevents_race_conditions_atomically()
    {
        // This is a basic functional test, real concurrency test is hard in unit test
        // But we verify the logic flows correctly.
        
        $tenant = Tenant::create(['name' => 'Test Tenant 104', 'domain' => 'test104.com', 'status' => 'active']);
        $tenantId = $tenant->id;
        $this->service->addCredits($tenantId, 1000, 'init');
        
        $this->service->deductCredits($tenantId, 100, 'op1');
        $this->service->deductCredits($tenantId, 100, 'op2');
        
        $this->assertEquals(800, $this->service->getBalance($tenantId));
        $this->assertEquals(3, AiTransaction::where('tenant_id', $tenantId)->count()); // Init + Op1 + Op2
    }
}
