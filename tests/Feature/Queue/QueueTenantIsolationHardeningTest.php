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

        $this->contextService = app(TenantContextService::class);
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
}
