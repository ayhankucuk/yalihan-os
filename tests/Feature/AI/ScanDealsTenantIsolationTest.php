<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Jobs\AI\GenerateDealPredictionsJob;
use App\Models\Ilan;
use App\Models\SaaS\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Regression tests for ai:scan-deals tenant isolation fix.
 * Tests the fixed command and job structure without relying on
 * deal_prediction_logs table which may not exist in test DB.
 */
class ScanDealsTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private Ilan $ilanA;
    private Ilan $ilanB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'Tenant A',
            'domain' => 'tenant-a.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Tenant B',
            'domain' => 'tenant-b.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->ilanA = Ilan::create([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A Listing',
            'yayin_durumu' => 1,
        ]);

        $this->ilanB = Ilan::create([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Tenant B Listing',
            'yayin_durumu' => 1,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // JOB STRUCTURE TESTS
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function job_implements_tenant_aware_interface(): void
    {
        $interfaces = class_implements(GenerateDealPredictionsJob::class);
        $this->assertContains(
            \App\Queue\Contracts\TenantAwareJobInterface::class,
            $interfaces,
            'GenerateDealPredictionsJob must implement TenantAwareJobInterface'
        );
    }

    /** @test */
    public function job_returns_correct_tenant_id_from_listing(): void
    {
        $job = new GenerateDealPredictionsJob($this->ilanA->id);
        $this->assertEquals(
            $this->tenantA->id,
            $job->getTenantId(),
            'getTenantId must return the listing tenant_id'
        );
    }

    /** @test */
    public function job_returns_null_for_nonexistent_listing(): void
    {
        $job = new GenerateDealPredictionsJob(99999);
        $this->assertNull($job->getTenantId());
    }

    /** @test */
    public function job_declares_restore_tenant_context_middleware(): void
    {
        $job = new GenerateDealPredictionsJob($this->ilanA->id);
        $middleware = $job->middleware();

        $this->assertNotEmpty($middleware);
        $this->assertInstanceOf(
            \App\Queue\Middleware\RestoreTenantContext::class,
            $middleware[0],
            'Job must use RestoreTenantContext middleware'
        );
    }

    /** @test */
    public function job_serializes_ilan_id_not_full_model(): void
    {
        $reflection = new \ReflectionClass(GenerateDealPredictionsJob::class);
        $prop = $reflection->getProperty('ilanId');
        $this->assertEquals('int', $prop->getType()->getName());
    }

    /** @test */
    public function job_has_retry_configured(): void
    {
        $job = new GenerateDealPredictionsJob($this->ilanA->id);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals([30, 60, 120], $job->backoff);
    }

    // ─────────────────────────────────────────────────────────────────
    // COMMAND TESTS
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function command_runs_without_error_with_single_listing(): void
    {
        Queue::fake();

        $this->artisan('ai:scan-deals', ['--ilan_id' => $this->ilanA->id])
            ->assertSuccessful();
    }

    /** @test */
    public function command_dispatches_job_for_single_listing(): void
    {
        Queue::fake();

        $this->artisan('ai:scan-deals', ['--ilan_id' => $this->ilanA->id])
            ->assertSuccessful();

        Queue::assertPushed(GenerateDealPredictionsJob::class, function ($job) {
            return $job->ilanId === $this->ilanA->id;
        });
    }

    /** @test */
    public function command_runs_batch_mode_without_error(): void
    {
        Queue::fake();

        $this->artisan('ai:scan-deals')
            ->assertSuccessful();
    }

    /** @test */
    public function command_handles_nonexistent_listing(): void
    {
        $this->artisan('ai:scan-deals', ['--ilan_id' => 99999])
            ->expectsOutput('Listing not found: 99999')
            ->assertExitCode(1);
    }

    /** @test */
    public function command_handles_listing_with_missing_tenant(): void
    {
        $orphan = Ilan::create([
            'tenant_id' => 99999,
            'baslik' => 'Orphan Listing',
            'yayin_durumu' => 1,
        ]);

        $this->artisan('ai:scan-deals', ['--ilan_id' => $orphan->id])
            ->expectsOutput("Tenant not found for listing: {$orphan->id}")
            ->assertExitCode(1);
    }

    /** @test */
    public function command_handles_no_active_tenants(): void
    {
        Queue::fake();

        Tenant::query()->update(['aktiflik_durumu' => 0]);

        $this->artisan('ai:scan-deals')
            ->expectsOutput('No active tenants found.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    /** @test */
    public function command_accepts_limit_option(): void
    {
        Queue::fake();

        $this->artisan('ai:scan-deals', ['--limit' => 5])
            ->assertSuccessful();
    }

    /** @test */
    public function command_includes_tenant_id_in_output(): void
    {
        Queue::fake();

        $this->artisan('ai:scan-deals', ['--ilan_id' => $this->ilanA->id])
            ->assertSuccessful()
            ->expectsOutputToContain((string) $this->tenantA->id);
    }

    /** @test */
    public function command_includes_listing_id_in_output(): void
    {
        Queue::fake();

        $this->artisan('ai:scan-deals', ['--ilan_id' => $this->ilanA->id])
            ->assertSuccessful()
            ->expectsOutputToContain((string) $this->ilanA->id);
    }
}
