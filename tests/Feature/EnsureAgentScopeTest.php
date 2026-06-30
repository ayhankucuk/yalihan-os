<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * OpenClawMiddlewareTest
 *
 * Tests the 3-layer OpenClaw middleware stack:
 * 1. EnsureOpenClawEnabled  — kill switch (503)
 * 2. EnsureOpenClawScope    — token + scope (401/403)
 * 3. EnforceOpenClawBoundary — allowlist, forbidden patterns, proposal-only, source (403)
 *
 * 8 test scenarios per spec:
 * T1: disabled mode → 503
 * T2: invalid token → 401/403
 * T3: invalid scope → 403
 * T4: forbidden route → 403
 * T5: execute mutation attempt → 403
 * T6: proposal pass → 200/202
 * T7: audit log verification
 * T8: kill switch doesn't affect Telegram/n8n/core
 */
class EnsureAgentScopeTest extends TestCase
{
    private string $validToken = 'test-agent-token-abc123';

    protected function setUp(): void
    {
        parent::setUp();

        // Register test routes with the 3-layer OpenClaw middleware stack
        Route::middleware(['openclaw.enabled', 'openclaw.scope', 'openclaw.boundary'])
            ->prefix('api/v1/agent')
            ->group(function () {
                Route::get('/health', fn () => response()->json(['basarili' => true]))
                    ->name('api.agent.health');

                Route::get('/context', fn () => response()->json(['basarili' => true, 'context' => []]))
                    ->name('api.agent.context.index');

                Route::post('/proposals', fn () => response()->json(['basarili' => true, 'id' => 1], 202))
                    ->name('api.agent.proposals.store');

                Route::put('/listings/{id}', fn () => response()->json(['basarili' => true]))
                    ->name('api.agent.listings.update');
            });

        // Register a route that matches a forbidden pattern
        Route::middleware(['openclaw.enabled', 'openclaw.scope', 'openclaw.boundary'])
            ->get('/api/v1/admin/users', fn () => response()->json(['basarili' => true]))
            ->name('admin.users.index');

        // Register a core route WITHOUT openclaw middleware (for isolation test)
        Route::get('/api/v1/core-health', fn () => response()->json(['basarili' => true]))
            ->name('api.health');
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
            ],
            'openclaw.forbidden_route_patterns' => [
                'admin.*',
                'api.governance.write.*',
                'api.features.assign*',
                'api.templates.apply*',
                'api.listings.publish*',
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
    // T1: Kill Switch — Disabled Mode → 503
    // =========================================================================

    public function test_t1_kill_switch_blocks_all_traffic_when_disabled(): void
    {
        $this->configureOpenClaw(['openclaw.enabled' => false]);

        $response = $this->getJson('/api/v1/agent/health', $this->agentHeaders());

        $response->assertStatus(503);
        $response->assertJsonFragment(['durum_kodu' => 503]);
        $response->assertJsonFragment(['hata_mesaji' => 'Agent gateway disabled']);
    }

    // =========================================================================
    // T2: Invalid Token → 401 (missing) / 403 (wrong)
    // =========================================================================

    public function test_t2_rejects_request_without_token(): void
    {
        $this->configureOpenClaw();

        $response = $this->getJson('/api/v1/agent/health', [
            'X-Agent-Source' => 'openclaw',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['durum_kodu' => 401]);
    }

    public function test_t2_rejects_request_with_invalid_token(): void
    {
        $this->configureOpenClaw();

        $response = $this->getJson('/api/v1/agent/health', $this->agentHeaders([
            'X-Agent-Token' => 'wrong-token',
        ]));

        $response->assertStatus(403);
        $response->assertJsonFragment(['hata_mesaji' => 'Invalid agent token']);
    }

    public function test_t2_rejects_when_server_token_not_configured(): void
    {
        $this->configureOpenClaw(['openclaw.token.value' => '']);

        $response = $this->getJson('/api/v1/agent/health', $this->agentHeaders());

        $response->assertStatus(403);
    }

    // =========================================================================
    // T3: Invalid Scope → 403
    // =========================================================================

    public function test_t3_rejects_request_with_invalid_scope(): void
    {
        $this->configureOpenClaw();

        $response = $this->getJson('/api/v1/agent/health', $this->agentHeaders([
            'X-Agent-Scope' => 'agent.execute.admin',
        ]));

        $response->assertStatus(403);
        $response->assertJsonFragment(['hata_mesaji' => 'Scope not permitted']);
    }

    public function test_t3_accepts_request_with_valid_scope(): void
    {
        $this->configureOpenClaw();

        $response = $this->getJson('/api/v1/agent/health', $this->agentHeaders([
            'X-Agent-Scope' => 'agent.read.context',
        ]));

        $response->assertStatus(200);
    }

    // =========================================================================
    // T4: Forbidden Route → 403
    // =========================================================================

    public function test_t4_rejects_forbidden_route_pattern(): void
    {
        $this->configureOpenClaw();

        $response = $this->getJson('/api/v1/admin/users', $this->agentHeaders());

        $response->assertStatus(403);
        $response->assertJsonFragment(['hata_mesaji' => 'Route forbidden for agents']);
    }

    public function test_t4_rejects_route_not_in_allowlist(): void
    {
        $this->configureOpenClaw();

        // api.agent.listings.update is NOT in allowed_routes patterns
        $response = $this->putJson('/api/v1/agent/listings/1', ['baslik' => 'test'], $this->agentHeaders());

        $response->assertStatus(403);
    }

    // =========================================================================
    // T5: Execute Mutation Attempt → 403
    // =========================================================================

    public function test_t5_proposal_only_blocks_put_method(): void
    {
        $this->configureOpenClaw([
            'openclaw.allowed_routes' => [
                'api.agent.health',
                'api.agent.proposals.*',
                'api.agent.listings.*', // Allow route but block method
            ],
        ]);

        $response = $this->putJson('/api/v1/agent/listings/1', ['baslik' => 'test'], $this->agentHeaders());

        $response->assertStatus(403);
        $response->assertJsonFragment(['hata_mesaji' => 'Direct mutations forbidden in proposal-only mode']);
    }

    public function test_t5_execute_mode_header_blocked(): void
    {
        $this->configureOpenClaw();

        $response = $this->postJson('/api/v1/agent/proposals', [
            'proposal_type' => 'create_listing',
        ], $this->agentHeaders([
            'X-Agent-Mode' => 'execute',
        ]));

        $response->assertStatus(403);
        $response->assertJsonFragment(['hata_mesaji' => 'Execute mode forbidden in proposal-only mode']);
    }

    public function test_t5_execute_payload_flag_blocked(): void
    {
        $this->configureOpenClaw();

        $response = $this->postJson('/api/v1/agent/proposals', [
            'execute' => true,
        ], $this->agentHeaders());

        $response->assertStatus(403);
        $response->assertJsonFragment(['hata_mesaji' => 'Execute flag forbidden in proposal-only mode']);
    }

    public function test_t5_mutation_allowed_when_proposal_only_disabled(): void
    {
        $this->configureOpenClaw([
            'openclaw.proposal_only' => false,
            'openclaw.allowed_routes' => [
                'api.agent.health',
                'api.agent.proposals.*',
                'api.agent.listings.*',
            ],
        ]);

        $response = $this->putJson('/api/v1/agent/listings/1', ['baslik' => 'test'], $this->agentHeaders());

        $response->assertStatus(200);
    }

    // =========================================================================
    // T6: Proposal Pass → 200/202
    // =========================================================================

    public function test_t6_valid_proposal_returns_202(): void
    {
        $this->configureOpenClaw();

        $response = $this->postJson('/api/v1/agent/proposals', [
            'proposal_type' => 'create_listing',
        ], $this->agentHeaders());

        $response->assertStatus(202);
        $response->assertJsonFragment(['basarili' => true]);
    }

    public function test_t6_valid_get_returns_200(): void
    {
        $this->configureOpenClaw();

        $response = $this->getJson('/api/v1/agent/health', $this->agentHeaders());

        $response->assertStatus(200);
        $response->assertJsonFragment(['basarili' => true]);
    }

    // =========================================================================
    // T7: Audit Log Verification
    // =========================================================================

    public function test_t7_audit_logs_rejected_request(): void
    {
        $this->configureOpenClaw(['openclaw.enabled' => false]);

        Log::spy();

        $this->getJson('/api/v1/agent/health', $this->agentHeaders());

        // Middleware logs via Log::channel('security')->warning()
        // We verify channel() was invoked; the warning() call is on the channel return object
        Log::shouldHaveReceived('channel')->with('security');
    }

    public function test_t7_audit_logs_passed_request(): void
    {
        $this->configureOpenClaw();

        Log::spy();

        $this->getJson('/api/v1/agent/health', $this->agentHeaders());

        // Middleware logs via Log::channel('security')->info()
        // We verify channel() was invoked; the info() call is on the channel return object
        Log::shouldHaveReceived('channel')->with('security');
    }

    // =========================================================================
    // T8: Kill Switch Doesn't Affect Telegram/n8n/Core Routes
    // =========================================================================

    public function test_t8_kill_switch_does_not_affect_core_routes(): void
    {
        $this->configureOpenClaw(['openclaw.enabled' => false]);

        // Core health endpoint has NO openclaw middleware — must be unaffected
        $response = $this->getJson('/api/v1/core-health');

        $response->assertStatus(200);
        $response->assertJsonFragment(['basarili' => true]);
    }

    // =========================================================================
    // Source Header Validation
    // =========================================================================

    public function test_rejects_request_without_openclaw_source(): void
    {
        $this->configureOpenClaw();

        $response = $this->getJson('/api/v1/agent/health', $this->agentHeaders([
            'X-Agent-Source' => 'unknown',
        ]));

        $response->assertStatus(403);
        $response->assertJsonFragment(['hata_mesaji' => 'Invalid agent source']);
    }

    public function test_rejects_request_with_missing_source(): void
    {
        $this->configureOpenClaw();

        $response = $this->getJson('/api/v1/agent/health', [
            'X-Agent-Token' => $this->validToken,
            'X-Correlation-Id' => 'test-corr-1',
        ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // Payload Size Guard
    // =========================================================================

    public function test_rejects_oversized_payload(): void
    {
        $this->configureOpenClaw(['openclaw.rate_limits.max_payload_bytes' => 100]);

        $largePayload = str_repeat('x', 200);

        $response = $this->call('POST', '/api/v1/agent/proposals', [], [], [], [
            'HTTP_X_AGENT_TOKEN' => $this->validToken,
            'HTTP_X_AGENT_SOURCE' => 'openclaw',
            'HTTP_X_AGENT_SCOPE' => 'agent.read.context',
            'HTTP_X_CORRELATION_ID' => 'test-corr-1',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['data' => $largePayload]));

        $response->assertStatus(413);
    }

    // =========================================================================
    // Legacy agent.scope Middleware (Backward Compatibility)
    // =========================================================================

    public function test_legacy_agent_scope_middleware_still_works(): void
    {
        // Register a route using the old agent.scope alias
        Route::middleware('agent.scope')->get('/api/v1/agent-legacy/health', fn () => response()->json(['basarili' => true]))
            ->name('api.agent.legacy.health');

        $this->configureOpenClaw([
            'openclaw.enabled' => true,
            'openclaw.token.header' => 'X-Agent-Token',
            'openclaw.token.value' => $this->validToken,
            'services.openclaw.agent_token' => $this->validToken,
            'openclaw.allowed_routes' => ['api.agent.legacy.health'],
            'openclaw.rate_limits.requests_per_minute' => 30,
            'openclaw.rate_limits.max_payload_bytes' => 65536,
            'openclaw.proposal_only' => true,
            'openclaw.audit.log_channel' => 'security',
        ]);

        $response = $this->getJson('/api/v1/agent-legacy/health', $this->agentHeaders());

        $response->assertStatus(200);
    }
}
