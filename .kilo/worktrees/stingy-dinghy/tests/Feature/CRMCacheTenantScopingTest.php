<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\Cache\TenantCacheService;
use Illuminate\Support\Facades\Auth;

class CRMCacheTenantScopingTest extends TestCase
{
    protected TenantCacheService $tenantCacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantCacheService = app(TenantCacheService::class);
    }

    public function test_cache_resolves_to_global_when_no_user_and_no_override()
    {
        config(['app.tenant_id' => null]);
        Auth::logout();

        $resolvedTenant = $this->tenantCacheService->resolveTenantId();
        $this->assertEquals('global', $resolvedTenant);

        $key = $this->tenantCacheService->key('crm', 'test_key');
        $this->assertEquals('{tenant:global}:crm:test_key', $key);
    }

    public function test_cache_resolves_to_config_override_when_set()
    {
        config(['app.tenant_id' => 99]);
        Auth::logout();

        $resolvedTenant = $this->tenantCacheService->resolveTenantId();
        $this->assertEquals('99', $resolvedTenant);

        $key = $this->tenantCacheService->key('crm', 'test_key');
        $this->assertEquals('{tenant:99}:crm:test_key', $key);
    }

    public function test_cache_resolves_to_authenticated_user_tenant_when_logged_in()
    {
        config(['app.tenant_id' => null]);

        $user = new User();
        $user->id = 1;
        $user->tenant_id = 123;
        Auth::login($user);

        $resolvedTenant = $this->tenantCacheService->resolveTenantId();
        $this->assertEquals('123', $resolvedTenant);

        $key = $this->tenantCacheService->key('crm', 'test_key');
        $this->assertEquals('{tenant:123}:crm:test_key', $key);
    }

    public function test_cache_operations_work_with_tenant_scope()
    {
        config(['app.tenant_id' => 88]);

        // Key should be {tenant:88}:crm:my_secret_key
        $key = $this->tenantCacheService->key('crm', 'my_secret_key');
        $this->assertEquals('{tenant:88}:crm:my_secret_key', $key);

        $this->tenantCacheService->put($key, 'cortex_secret_value', 120);

        $this->assertEquals('cortex_secret_value', $this->tenantCacheService->get($key));

        $this->tenantCacheService->forget($key);
        $this->assertNull($this->tenantCacheService->get($key));
    }
}
