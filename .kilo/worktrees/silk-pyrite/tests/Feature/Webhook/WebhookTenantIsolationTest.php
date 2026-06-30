<?php

namespace Tests\Feature\Webhook;

use App\Http\Middleware\VerifyWebhookTenant;
use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * P1.3 - Webhook Tenant İzolasyonu Test Suite
 *
 * Bu test suite, VerifyWebhookTenant middleware'inin tenant izolasyonunu doğrular.
 *
 * Test Senaryoları:
 * 1. Valid tenant_id in payload
 * 2. Query parameter tenant_id
 * 3. Missing tenant_id (404)
 * 4. Inactive tenant (404)
 * 5. Nonexistent tenant (404)
 * 6. Tenant context setting
 * 7. Priority order validation
 */
class WebhookTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected VerifyWebhookTenant $middleware;
    protected TenantContextService $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        // Spy Log to avoid Mockery BadMethodCallException while asserting log calls
        Log::spy();

        // Create test tenants (manual creation - no factory)
        $this->tenant1 = Tenant::create([
            'name' => 'Tenant 1',
            'domain' => 'tenant1.test',
            'is_active' => true,
        ]);

        $this->tenant2 = Tenant::create([
            'name' => 'Tenant 2',
            'domain' => 'tenant2.test',
            'is_active' => true,
        ]);

        $this->tenantContext = app(TenantContextService::class);
        $this->middleware = new VerifyWebhookTenant($this->tenantContext);
    }

    /** @test */
    public function middleware_accepts_valid_tenant_id_in_payload()
    {
        $request = Request::create('/webhook/test', 'POST', [
            'tenant_id' => $this->tenant1->id,
            'entry' => [
                ['id' => '123456789012345', 'changes' => []],
            ],
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->tenant1->id, $request->attributes->get('verified_tenant_id'));
    }

    /** @test */
    public function middleware_extracts_tenant_from_query_parameter()
    {
        $request = Request::create('/webhook/test?tenant_id=' . $this->tenant2->id, 'POST', [
            'entry' => [
                ['id' => 'unknown_business_id', 'changes' => []],
            ],
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->tenant2->id, $request->attributes->get('verified_tenant_id'));
    }

    /** @test */
    public function middleware_rejects_request_without_tenant_identification()
    {
        $request = Request::create('/webhook/test', 'POST', [
            'entry' => [
                ['changes' => []],
            ],
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });
    }

    /** @test */
    public function middleware_rejects_inactive_tenant()
    {
        // Create tenant and then update is_active directly in database
        $inactiveTenant = Tenant::create([
            'name' => 'Inactive Tenant',
            'domain' => 'inactive.test',
        ]);

        // Update is_active directly (not in fillable)
        \Illuminate\Support\Facades\DB::table('tenants')
            ->where('id', $inactiveTenant->id)
            ->update(['is_active' => false]);

        $request = Request::create('/webhook/test', 'POST', [
            'tenant_id' => $inactiveTenant->id,
            'entry' => [
                ['id' => '123456789012345', 'changes' => []],
            ],
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });
    }

    /** @test */
    public function middleware_rejects_nonexistent_tenant()
    {
        $request = Request::create('/webhook/test', 'POST', [
            'tenant_id' => 9999, // Non-existent tenant
            'entry' => [
                ['id' => '123456789012345', 'changes' => []],
            ],
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });
    }

    /** @test */
    public function middleware_sets_tenant_context()
    {
        $request = Request::create('/webhook/test', 'POST', [
            'tenant_id' => $this->tenant1->id,
            'entry' => [],
        ]);

        $this->middleware->handle($request, function ($req) {
            // Inside the request lifecycle, tenant context should be set
            $this->assertTrue($this->tenantContext->hasTenant());
            $this->assertEquals($this->tenant1->id, $this->tenantContext->getTenant()->id);
            return response()->json(['success' => true]);
        });
    }

    /** @test */
    public function middleware_priority_order_is_correct()
    {
        // Priority 1: Direct tenant_id should override query parameter
        $request = Request::create('/webhook/test?tenant_id=' . $this->tenant2->id, 'POST', [
            'tenant_id' => $this->tenant1->id, // Priority 1 (payload)
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        // Should use tenant1 (priority 1) not tenant2 (priority 2)
        $this->assertEquals($this->tenant1->id, $request->attributes->get('verified_tenant_id'));
    }

    /** @test */
    public function middleware_logs_tenant_verification_failure()
    {
        $request = Request::create('/webhook/test', 'POST', [
            'data' => 'no tenant info',
        ]);

        try {
            $this->middleware->handle($request, function ($req) {
                return response()->json(['success' => true]);
            });
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            // expected
        }

        Log::shouldHaveReceived('critical')->once();
        $this->assertTrue(true);
    }

    /** @test */
    public function middleware_handles_empty_payload()
    {
        $request = Request::create('/webhook/test', 'POST', []);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });
    }

    /** @test */
    public function middleware_attaches_verified_tenant_id_to_request()
    {
        $request = Request::create('/webhook/test', 'POST', [
            'tenant_id' => $this->tenant1->id,
        ]);

        $this->middleware->handle($request, function ($req) {
            // verified_tenant_id should be attached to request attributes
            $this->assertTrue($req->attributes->has('verified_tenant_id'));
            $this->assertEquals($this->tenant1->id, $req->attributes->get('verified_tenant_id'));
            return response()->json(['success' => true]);
        });
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
