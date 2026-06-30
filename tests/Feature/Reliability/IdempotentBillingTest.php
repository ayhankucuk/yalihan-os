<?php

namespace Tests\Feature\Reliability;

use App\Models\AI\AiTransaction;
use App\Models\SaaS\Tenant;
use App\Services\AI\AiWalletService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IdempotentBillingTest extends TestCase
{
    use DatabaseTransactions;

    private AiWalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AiWalletService();
    }

    /** @test */
    public function it_deducts_credits_idempotently_with_key()
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'billing-tenant-1'],
            ['name' => 'Billing Tenant 1', 'domain' => 'billing1.com', 'status' => 'active']
        );
        $tenantId = $tenant->id;

        // Initialize with 1000 credits
        $this->service->addCredits($tenantId, 1000, 'initial_funding');
        $this->assertEquals(1000, $this->service->getBalance($tenantId));

        $idempotencyKey = 'unique-deduct-key-123';

        // First deduction: should succeed
        $this->service->deductCredits(
            $tenantId,
            150,
            'ai_image_generation',
            'Ilan',
            1,
            ['engine' => 'dall-e-3'],
            $idempotencyKey
        );

        $this->assertEquals(850, $this->service->getBalance($tenantId));

        // Second deduction with same key: should be ignored/bypassed
        $this->service->deductCredits(
            $tenantId,
            150,
            'ai_image_generation',
            'Ilan',
            1,
            ['engine' => 'dall-e-3'],
            $idempotencyKey
        );

        // Balance should remain 850 (not 700!)
        $this->assertEquals(850, $this->service->getBalance($tenantId));

        // Total transactions count for deduct should be 1 (excluding initial_funding)
        $deductTransactions = AiTransaction::where('tenant_id', $tenantId)
            ->where('idempotency_key', $idempotencyKey)
            ->get();

        $this->assertCount(1, $deductTransactions);
        $this->assertEquals(-150, $deductTransactions->first()->amount);
    }

    /** @test */
    public function it_adds_credits_idempotently_with_key()
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'billing-tenant-2'],
            ['name' => 'Billing Tenant 2', 'domain' => 'billing2.com', 'status' => 'active']
        );
        $tenantId = $tenant->id;

        $idempotencyKey = 'unique-add-key-456';

        // First top-up
        $this->service->addCredits(
            $tenantId,
            500,
            'subscription_topup',
            null,
            null,
            [],
            $idempotencyKey
        );

        $this->assertEquals(500, $this->service->getBalance($tenantId));

        // Second top-up with same key: should be ignored
        $this->service->addCredits(
            $tenantId,
            500,
            'subscription_topup',
            null,
            null,
            [],
            $idempotencyKey
        );

        // Balance remains 500 (not 1000!)
        $this->assertEquals(500, $this->service->getBalance($tenantId));

        // Total transactions count for add with key should be 1
        $addTransactions = AiTransaction::where('tenant_id', $tenantId)
            ->where('idempotency_key', $idempotencyKey)
            ->get();

        $this->assertCount(1, $addTransactions);
        $this->assertEquals(500, $addTransactions->first()->amount);
    }
}
