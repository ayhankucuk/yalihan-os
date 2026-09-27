<?php

declare(strict_types=1);

namespace Tests\Feature\Queue;

use App\Jobs\Concierge\ProcessGuestMessageJob;
use App\Queue\Middleware\RestoreTenantContext;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ProcessGuestMessageJobMiddlewareResolutionTest
 *
 * CONCIERGE_RESTORE_TENANT_CONTEXT_IMPORT_DEFECT regression
 * Finding: REPO_VERIFIED — CODE_AUDIT
 *
 * Verifies that ProcessGuestMessageJob::middleware() resolves to the
 * canonical App\Queue\Middleware\RestoreTenantContext, not the non-existent
 * Illuminate\Queue\Middleware\RestoreTenantContext framework class.
 *
 * Source: QA-2026-09-27 — bounded remediation
 */
class ProcessGuestMessageJobMiddlewareResolutionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function middleware_returns_canonical_restore_tenant_context_instance(): void
    {
        // 1. middleware() must not throw ClassNotFoundException
        $middleware = (new ProcessGuestMessageJob(
            senderPhone: '+905551112233',
            senderName: 'Test Guest',
            messageText: 'Merhaba',
            messageId: null,
            messageType: 'text',
            routingDecision: \App\Services\Concierge\RoutingDecision::unknown(
                phone: '+905551112233',
            ),
        ))->middleware();

        $this->assertIsArray($middleware);
        $this->assertNotEmpty($middleware, 'middleware() must return at least one middleware');

        // 2. First middleware must be an instance of the canonical class
        $first = $middleware[0];
        $this->assertInstanceOf(
            RestoreTenantContext::class,
            $first,
            'ProcessGuestMessageJob middleware must use App\Queue\Middleware\RestoreTenantContext, '
            . 'not Illuminate\Queue\Middleware\RestoreTenantContext (which does not exist)'
        );
    }

    /** @test */
    public function middleware_can_be_instantiated_without_class_not_found(): void
    {
        // Regression: Previously used Illuminate\Queue\Middleware\RestoreTenantContext
        // which does not exist as a framework class. This would cause:
        //   Class 'Illuminate\Queue\Middleware\RestoreTenantContext' not found
        $job = new ProcessGuestMessageJob(
            senderPhone: '+905551112233',
            senderName: 'Test Guest',
            messageText: 'Test message',
            messageId: 'msg_123',
            messageType: 'text',
            routingDecision: \App\Services\Concierge\RoutingDecision::unknown(
                phone: '+905551112233',
            ),
        );

        $middleware = $job->middleware();
        $instance = $middleware[0];

        $this->assertInstanceOf(RestoreTenantContext::class, $instance);
    }

    /** @test */
    public function middleware_has_handle_method(): void
    {
        $job = new ProcessGuestMessageJob(
            senderPhone: '+905551112233',
            senderName: null,
            messageText: 'Hello',
            messageId: null,
            messageType: 'text',
            routingDecision: \App\Services\Concierge\RoutingDecision::unknown(
                phone: '+905551112233',
            ),
        );

        $middleware = $job->middleware();
        $this->assertInstanceOf(RestoreTenantContext::class, $middleware[0]);
        $this->assertTrue(
            method_exists($middleware[0], 'handle'),
            'RestoreTenantContext middleware must have a handle() method'
        );
    }
}
