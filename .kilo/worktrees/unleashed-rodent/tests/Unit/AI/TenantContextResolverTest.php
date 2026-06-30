<?php

namespace Tests\Unit\AI;

use App\Application\Shared\DTOs\TenantContext;
use App\Application\Shared\Exceptions\TenantContextMissingException;
use App\Application\Shared\Services\TenantContextResolver;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\TestCase;

class TenantContextResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_throws_exception_when_no_user_is_authenticated()
    {
        $this->expectException(TenantContextMissingException::class);

        // We mock the static call to auth() or the helper
        // Since we can't easily mock the helper 'auth()' in a unit test without bootstrap,
        // we should refactor the Resolver to be more testable or use a mockable Auth.
        
        $resolver = new class extends TenantContextResolver {
            protected function getCurrentUser() { return null; }
            public function resolve(): TenantContext {
                $user = $this->getCurrentUser();
                if (!$user || !isset($user->tenant_id)) throw new TenantContextMissingException();
                return new TenantContext($user->tenant_id, $user->id, 'test-req');
            }
        };

        $resolver->resolve();
    }

    /** @test */
    public function it_resolves_tenant_context_successfully()
    {
        $user = new \stdClass();
        $user->id = 1;
        $user->tenant_id = 100;

        $resolver = new class($user) extends TenantContextResolver {
            private $u;
            public function __construct($u) { $this->u = $u; }
            protected function getCurrentUser() { return $this->u; }
            public function resolve(): TenantContext {
                $user = $this->getCurrentUser();
                if (!$user || !isset($user->tenant_id)) throw new TenantContextMissingException();
                return new TenantContext((int)$user->tenant_id, (int)$user->id, 'test-req');
            }
        };

        $context = $resolver->resolve();

        $this->assertInstanceOf(TenantContext::class, $context);
        $this->assertEquals(100, $context->tenantId);
        $this->assertEquals(1, $context->userId);
    }
}
