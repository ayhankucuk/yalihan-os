<?php

namespace Tests\Feature;

use App\Exceptions\AgentWriteViolationException;
use App\Support\AgentContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * OpenClawWriteIsolationTest
 *
 * Verifies the "inner lock" — service-layer write guards that block
 * agent requests from performing DB writes, regardless of middleware.
 *
 * 5 test scenarios:
 * 1. Write enforcement: agent route → service write → exception
 * 2. Service bypass: direct service call with agent context → block
 * 3. Kill switch: OPENCLAW_ENABLED=false → all agent endpoints 503
 * 4. Scope: wrong scope token → 403
 * 5. Audit: agent request → log with trace/correlation ID
 */
class OpenClawWriteIsolationTest extends TestCase
{
    private string $validToken = 'test-agent-token-abc123';

    protected function setUp(): void
    {
        parent::setUp();
        AgentContext::reset();
    }

    protected function tearDown(): void
    {
        AgentContext::reset();
        parent::tearDown();
    }

    private function configureOpenClaw(array $overrides = []): void
    {
        $defaults = [
            'openclaw.enabled' => true,
            'openclaw.proposal_only' => true,
            'openclaw.token.value' => $this->validToken,
            'openclaw.headers.token' => 'X-Agent-Token',
            'openclaw.headers.source' => 'X-Agent-Source',
            'openclaw.headers.mode' => 'X-Agent-Mode',
            'openclaw.headers.correlation_id' => 'X-Correlation-Id',
            'openclaw.headers.scope' => 'X-Agent-Scope',
            'openclaw.allowed_routes' => [
                'api.agent.health',
                'api.agent.context.*',
                'api.agent.proposals.*',
                'api.agent.suggestions.*',
                'api.agent.test.*',
            ],
            'openclaw.forbidden_route_patterns' => [
                'admin.*',
            ],
            'openclaw.allowed_scopes' => [
                'agent.read.context',
                'agent.trigger.workflow',
                'agent.request.suggestion',
            ],
            'openclaw.rate_limits.requests_per_minute' => 30,
            'openclaw.rate_limits.max_payload_bytes' => 65536,
            'openclaw.audit.log_channel' => 'security',
            'openclaw.audit.log_payload' => false,
        ];

        config(array_merge($defaults, $overrides));
    }

    private function agentHeaders(array $overrides = []): array
    {
        return array_merge([
            'X-Agent-Source' => 'openclaw',
            'X-Agent-Token' => $this->validToken,
            'X-Agent-Scope' => 'agent.read.context',
            'X-Correlation-Id' => 'test-corr-' . uniqid(),
        ], $overrides);
    }

    // =========================================================================
    // TEST 1: Write Enforcement — Agent → Service Write → Exception
    // =========================================================================

    public function test_agent_context_blocks_ilan_crud_service_store(): void
    {
        // Simulate agent context being active (as middleware would set it)
        AgentContext::activate('agent.read.context', 'test-corr-001', 'hash123');

        $service = app(\App\Services\Ilan\IlanCrudService::class);

        $this->expectException(AgentWriteViolationException::class);
        $this->expectExceptionMessage('Agent write violation: App\Services\Ilan\IlanCrudService::store()');

        $service->store([
            'baslik' => 'Agent should not write this',
            'yayin_durumu' => 0,
        ]);
    }

    public function test_agent_context_blocks_ilan_crud_service_update(): void
    {
        AgentContext::activate('agent.read.context', 'test-corr-002', 'hash123');

        $service = app(\App\Services\Ilan\IlanCrudService::class);

        $this->expectException(AgentWriteViolationException::class);
        $this->expectExceptionMessage('Agent write violation: App\Services\Ilan\IlanCrudService::update()');

        // We don't need a real Ilan — the guard fires before any DB access
        $service->update(new \App\Models\Ilan(), ['baslik' => 'hacked']);
    }

    public function test_agent_context_blocks_ilan_crud_service_destroy(): void
    {
        AgentContext::activate('agent.read.context', 'test-corr-003', 'hash123');

        $service = app(\App\Services\Ilan\IlanCrudService::class);

        $this->expectException(AgentWriteViolationException::class);
        $this->expectExceptionMessage('Agent write violation: App\Services\Ilan\IlanCrudService::destroy()');

        $service->destroy(new \App\Models\Ilan());
    }

    // =========================================================================
    // TEST 2: Service Bypass — Direct service call with agent context → block
    // =========================================================================

    public function test_service_bypass_blocked_even_without_middleware(): void
    {
        // No HTTP request, no middleware — just agent context manually activated
        // This simulates a bypass scenario where middleware is skipped
        AgentContext::activate('agent.trigger.workflow', 'bypass-corr-001');

        $service = app(\App\Services\Ilan\IlanCrudService::class);

        try {
            $service->store(['baslik' => 'bypass attempt']);
            $this->fail('Expected AgentWriteViolationException was not thrown');
        } catch (AgentWriteViolationException $e) {
            $context = $e->context();
            $this->assertEquals('App\Services\Ilan\IlanCrudService', $context['service']);
            $this->assertEquals('store', $context['method']);
            $this->assertEquals('agent.trigger.workflow', $context['scope']);
            $this->assertEquals('bypass-corr-001', $context['correlation_id']);
        }
    }

    public function test_normal_request_not_blocked(): void
    {
        // AgentContext is NOT active — normal requests must pass through
        $this->assertFalse(AgentContext::isAgent());

        // The guard should not throw — we just verify it doesn't interfere
        // (We can't actually call store() without DB setup, so test the guard directly)
        $service = app(\App\Services\Ilan\IlanCrudService::class);

        // Use reflection to call blockAgentWrite directly
        $reflection = new \ReflectionMethod($service, 'blockAgentWrite');
        $reflection->setAccessible(true);

        // Should NOT throw for normal requests
        $reflection->invoke($service, 'store');
        $this->assertTrue(true); // If we reach here, guard didn't block
    }

    // =========================================================================
    // TEST 3: Kill Switch — All agent endpoints return 503
    // =========================================================================

    public function test_kill_switch_blocks_all_agent_endpoints(): void
    {
        $this->configureOpenClaw(['openclaw.enabled' => false]);

        // Register test routes
        Route::middleware(['openclaw.enabled', 'openclaw.scope', 'openclaw.boundary'])
            ->prefix('api/v1/agent')
            ->group(function () {
                Route::get('/health', fn () => response()->json(['basarili' => true]))
                    ->name('api.agent.test.health');
                Route::get('/context', fn () => response()->json(['basarili' => true]))
                    ->name('api.agent.test.context');
                Route::post('/proposals', fn () => response()->json(['basarili' => true], 202))
                    ->name('api.agent.test.proposals');
            });

        $endpoints = [
            ['GET', '/api/v1/agent/health'],
            ['GET', '/api/v1/agent/context'],
            ['POST', '/api/v1/agent/proposals'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = $this->json($method, $uri, [], $this->agentHeaders());
            $this->assertEquals(503, $response->getStatusCode(), "Expected 503 for {$method} {$uri}");
        }
    }

    // =========================================================================
    // TEST 4: Wrong Scope Token → 403
    // =========================================================================

    public function test_wrong_scope_returns_403(): void
    {
        $this->configureOpenClaw();

        Route::middleware(['openclaw.enabled', 'openclaw.scope', 'openclaw.boundary'])
            ->get('/api/v1/agent/scope-test', fn () => response()->json(['basarili' => true]))
            ->name('api.agent.test.scope');

        $response = $this->getJson('/api/v1/agent/scope-test', $this->agentHeaders([
            'X-Agent-Scope' => 'agent.admin.override', // Not in allowed_scopes
        ]));

        $response->assertStatus(403);
        $response->assertJsonFragment(['hata_mesaji' => 'Scope not permitted']);
    }

    // =========================================================================
    // TEST 5: Audit — Agent request logged with correlation ID
    // =========================================================================

    public function test_agent_context_carries_correlation_id(): void
    {
        $correlationId = 'trace-abc-123';

        AgentContext::activate('agent.read.context', $correlationId, 'tokenhash');

        $this->assertTrue(AgentContext::isAgent());
        $this->assertEquals('agent.read.context', AgentContext::scope());
        $this->assertEquals($correlationId, AgentContext::correlationId());
        $this->assertEquals('tokenhash', AgentContext::tokenHash());
    }

    public function test_middleware_sets_agent_context_with_correlation_id(): void
    {
        $this->configureOpenClaw();

        Route::middleware(['openclaw.enabled', 'openclaw.scope', 'openclaw.boundary'])
            ->get('/api/v1/agent/audit-test', function () {
                // Inside the request, AgentContext should be active
                return response()->json([
                    'is_agent' => AgentContext::isAgent(),
                    'scope' => AgentContext::scope(),
                    'correlation_id' => AgentContext::correlationId(),
                    'has_token_hash' => !empty(AgentContext::tokenHash()),
                ]);
            })->name('api.agent.test.audit');

        $correlationId = 'corr-audit-test-xyz';
        $response = $this->getJson('/api/v1/agent/audit-test', $this->agentHeaders([
            'X-Correlation-Id' => $correlationId,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'is_agent' => true,
            'scope' => 'agent.read.context',
            'correlation_id' => $correlationId,
            'has_token_hash' => true,
        ]);
    }

    public function test_write_violation_logged_to_security_channel(): void
    {
        AgentContext::activate('agent.read.context', 'test-corr-log', 'tokenhash');

        // Log::channel() returns a channel logger — we need a mock chain
        $channelMock = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $channelMock->shouldReceive('critical')->once()->withArgs(function ($message, $context) {
            return $message === 'agent_write_violation'
                && $context['method'] === 'store'
                && $context['correlation_id'] === 'test-corr-log';
        });

        Log::shouldReceive('channel')->with('security')->andReturn($channelMock);

        $service = app(\App\Services\Ilan\IlanCrudService::class);

        try {
            $service->store(['baslik' => 'should fail']);
        } catch (AgentWriteViolationException $e) {
            // Expected
        }

        // Mockery auto-verifies shouldReceive expectations
    }

    // =========================================================================
    // AgentContext Reset
    // =========================================================================

    public function test_agent_context_reset_clears_state(): void
    {
        AgentContext::activate('agent.read.context', 'corr-1', 'hash1');
        $this->assertTrue(AgentContext::isAgent());

        AgentContext::reset();
        $this->assertFalse(AgentContext::isAgent());
        $this->assertNull(AgentContext::scope());
        $this->assertNull(AgentContext::correlationId());
    }
}
