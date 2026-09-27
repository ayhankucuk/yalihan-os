<?php

declare(strict_types=1);

namespace Tests\Feature\Queue;

use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use App\Queue\Middleware\RestoreTenantContext;
use App\Queue\Contracts\TenantAwareJobInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use RuntimeException;

class QueueTenantIsolationHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private TenantContextService $contextService;
    private RestoreTenantContext $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        // TenantContextService singleton per test isolation
        $this->contextService = app(TenantContextService::class);
        $this->contextService->clearTenant();

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

        $this->middleware = new RestoreTenantContext($this->contextService);
    }

    /** @test */
    public function target_jobs_must_implement_tenant_aware_job_interface(): void
    {
        $targetJobs = [
            \App\Jobs\AI\DailySnapshotsJob::class,
            \App\Jobs\OwnerReport\OwnerReportExportJob::class,
            \App\Jobs\NotifyN8nAboutIlanPriceChange::class,
            \App\Jobs\TalepTopluAnalizJob::class,
            \App\Jobs\TKGMAutoFillJob::class,
            \App\Jobs\GenerateListingReportJob::class,
            \App\Jobs\UpdateListingVisibilityScore::class,
            \App\Jobs\ReverseMatchJob::class,
            \App\Jobs\SendNotificationJob::class,
            \App\Jobs\HandleUrgentMatch::class,
        ];

        foreach ($targetJobs as $jobClass) {
            $interfaces = class_implements($jobClass);
            $this->assertContains(
                TenantAwareJobInterface::class,
                $interfaces,
                "Job [{$jobClass}] must implement TenantAwareJobInterface"
            );
        }
    }

    /** @test */
    public function middleware_restores_and_cleans_up_tenant_context(): void
    {
        // 1. Set current context to Tenant B
        $this->contextService->setTenant($this->tenantB);
        $this->assertEquals($this->tenantB->id, $this->contextService->getTenant()->id);

        // 2. Create a mock job representing a Tenant A job
        $mockJob = \Mockery::mock(TenantAwareJobInterface::class);
        $mockJob->shouldReceive('getTenantId')->andReturn($this->tenantA->id);
        $mockJob->shouldReceive('getUserId')->andReturn(null);

        // 3. Process middleware
        $called = false;
        $this->middleware->handle($mockJob, function ($job) use (&$called) {
            $called = true;
            // Inside the job handler, tenant context must be restored to Tenant A
            $this->assertTrue($this->contextService->hasTenant());
            $this->assertEquals($this->tenantA->id, $this->contextService->getTenant()->id);
            return 'processed';
        });

        $this->assertTrue($called);

        // 4. After processing, context must be restored back to Tenant B (prevent bleeding)
        $this->assertTrue($this->contextService->hasTenant());
        $this->assertEquals($this->tenantB->id, $this->contextService->getTenant()->id);
    }

    /** @test */
    public function middleware_throws_exception_if_tenant_id_is_missing(): void
    {
        $mockJob = \Mockery::mock(TenantAwareJobInterface::class);
        $mockJob->shouldReceive('getTenantId')->andReturn(null);
        $mockJob->shouldReceive('getUserId')->andReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tenant ID missing in Job payload');

        $this->middleware->handle($mockJob, function ($job) {
            return 'should-not-be-called';
        });
    }

    /** @test */
    public function middleware_throws_exception_if_job_does_not_implement_interface(): void
    {
        $nonAwareJob = new class {
            // Does not implement TenantAwareJobInterface
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job must implement TenantAwareJobInterface');

        $this->middleware->handle($nonAwareJob, function ($job) {
            return 'should-not-be-called';
        });
    }

    /**
     * CASE 1 — CLEAN WORKER / SUCCESS
     * Initial: no tenant
     * Run middleware with Tenant A job.
     * During job: Tenant A active.
     * After middleware returns: no tenant.
     *
     * Source: QA-2026-09-27 — TEST_VERIFIED
     * Finding: QUEUE_CROSS_JOB_TENANT_BLEEDING — REAL_FINDING
     */
    /** @test */
    public function clean_worker_success_leaves_no_tenant_context(): void
    {
        // 1. Verify worker starts with NO tenant
        $this->assertFalse($this->contextService->hasTenant());

        // 2. Create a mock job for Tenant A
        $mockJob = \Mockery::mock(TenantAwareJobInterface::class);
        $mockJob->shouldReceive('getTenantId')->andReturn($this->tenantA->id);
        $mockJob->shouldReceive('getUserId')->andReturn(null);

        // 3. Process middleware — job succeeds
        $called = false;
        $this->middleware->handle($mockJob, function ($job) use (&$called) {
            $called = true;
            // Inside the job: Tenant A is active
            $this->assertTrue($this->contextService->hasTenant());
            $this->assertEquals($this->tenantA->id, $this->contextService->getTenant()->id);
            return 'processed';
        });

        $this->assertTrue($called);

        // 4. After middleware: NO tenant context must remain (fix regression)
        $this->assertFalse(
            $this->contextService->hasTenant(),
            'Tenant context leaked after successful job completion on clean worker'
        );
    }

    /**
     * CASE 2 — CLEAN WORKER / EXCEPTION
     * Initial: no tenant
     * Run middleware with Tenant A job.
     * Job throws.
     * After exception handling/finally: no tenant.
     * Original exception must still propagate.
     *
     * Source: QA-2026-09-27 — TEST_VERIFIED
     * Finding: QUEUE_CROSS_JOB_TENANT_BLEEDING — REAL_FINDING
     */
    /** @test */
    public function clean_worker_exception_leaves_no_tenant_context(): void
    {
        // 1. Verify worker starts with NO tenant
        $this->assertFalse($this->contextService->hasTenant());

        // 2. Create a mock job for Tenant A that throws
        $mockJob = \Mockery::mock(TenantAwareJobInterface::class);
        $mockJob->shouldReceive('getTenantId')->andReturn($this->tenantA->id);
        $mockJob->shouldReceive('getUserId')->andReturn(null);

        // 3. Process middleware — job throws
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job failed intentionally');

        try {
            $this->middleware->handle($mockJob, function ($job) {
                // Inside the job: Tenant A is active
                $this->assertTrue($this->contextService->hasTenant());
                $this->assertEquals($this->tenantA->id, $this->contextService->getTenant()->id);
                throw new RuntimeException('Job failed intentionally');
            });
        } catch (RuntimeException $e) {
            // 4. After exception: NO tenant context must remain (fix regression)
            // finally block in middleware MUST have cleared the context before re-throw
            $this->assertFalse(
                $this->contextService->hasTenant(),
                'Tenant context leaked after exceptional job completion on clean worker'
            );
            throw $e; // Re-throw to satisfy expectException
        }
    }

    /**
     * CASE 3 — SEQUENTIAL JOB SAFETY
     * Same TenantContextService instance.
     * Job A: Tenant A.
     * After Job A: no tenant.
     * Then simulate Job B / non-tenant operation.
     * Assert: Tenant A is not inherited.
     *
     * Source: QA-2026-09-27 — TEST_VERIFIED
     * Finding: QUEUE_CROSS_JOB_TENANT_BLEEDING — REAL_FINDING
     */
    /** @test */
    public function sequential_jobs_do_not_inherit_tenant_context(): void
    {
        // 1. First job: Tenant A (clean start)
        $this->assertFalse($this->contextService->hasTenant());

        $mockJobA = \Mockery::mock(TenantAwareJobInterface::class);
        $mockJobA->shouldReceive('getTenantId')->andReturn($this->tenantA->id);
        $mockJobA->shouldReceive('getUserId')->andReturn(null);

        $this->middleware->handle($mockJobA, function ($job) {
            $this->assertEquals($this->tenantA->id, $this->contextService->getTenant()->id);
            return 'job-a-done';
        });

        // 2. After Job A: context must be cleared
        $this->assertFalse(
            $this->contextService->hasTenant(),
            'Tenant A leaked after Job A completion'
        );

        // 3. Simulate Job B running without tenant context
        // (represents a non-tenant-aware or different tenant job)
        // If hasTenant() is false — this is safe; no data can leak
        $this->assertFalse(
            $this->contextService->hasTenant(),
            'Tenant A context persisted into Job B scheduling window'
        );

        // 4. Run a second Tenant B job — must not see Tenant A
        $mockJobB = \Mockery::mock(TenantAwareJobInterface::class);
        $mockJobB->shouldReceive('getTenantId')->andReturn($this->tenantB->id);
        $mockJobB->shouldReceive('getUserId')->andReturn(null);

        $this->middleware->handle($mockJobB, function ($job) {
            // Must see Tenant B, NOT Tenant A
            $this->assertEquals(
                $this->tenantB->id,
                $this->contextService->getTenant()->id,
                'Wrong tenant context inside Job B'
            );
            $this->assertNotEquals(
                $this->tenantA->id,
                $this->contextService->getTenant()->id,
                'Tenant A leaked into Job B'
            );
            return 'job-b-done';
        });

        // 5. After Job B: context must be cleared again
        $this->assertFalse($this->contextService->hasTenant());
    }
}
