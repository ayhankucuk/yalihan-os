<?php

namespace Tests\Feature;

use App\Console\Commands\OpenClawAnomalyDetector;
use App\Models\OpenClawAuditLog;
use App\Services\OpenClaw\OpenClawAuditService;
use App\Support\AgentContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * OpenClawAuditTest — Tests the Observability + Audit Layer.
 *
 * Covers:
 * - OpenClawAuditService: recording request events, write violations, window stats
 * - OpenClawAuditLog: model scopes, event constants, immutability
 * - OpenClawAnomalyDetector: anomaly detection command
 * - Middleware audit integration: DB records created during request lifecycle
 */
class OpenClawAuditTest extends TestCase
{
    private string $validToken = 'test-agent-token-abc123';

    protected function setUp(): void
    {
        parent::setUp();
        AgentContext::reset();

        if (!Schema::hasTable('openclaw_audit_logs')) {
            $this->markTestSkipped('openclaw_audit_logs table not found. Run migration first.');
        }

        // Register test routes for middleware integration tests
        Route::middleware(['openclaw.enabled', 'openclaw.scope', 'openclaw.boundary'])
            ->prefix('api/v1/agent')
            ->group(function () {
                Route::get('/health', fn () => response()->json(['basarili' => true]))
                    ->name('api.agent.health');

                Route::get('/context', fn () => response()->json(['basarili' => true]))
                    ->name('api.agent.context.index');
            });

        // Register a route that matches a forbidden pattern
        Route::middleware(['openclaw.enabled', 'openclaw.scope', 'openclaw.boundary'])
            ->get('/api/v1/admin/users', fn () => response()->json(['basarili' => true]))
            ->name('admin.users.index');
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
            ],
            'openclaw.forbidden_route_patterns' => ['admin.*'],
            'openclaw.allowed_scopes' => [
                'agent.read.context',
                'agent.trigger.workflow',
            ],
            'openclaw.rate_limits.max_payload_bytes' => 65536,
            'openclaw.audit.log_channel' => 'security',
            'openclaw.audit.log_payload' => false,
            'openclaw.anomaly_detection.violation_burst_threshold' => 3,
            'openclaw.anomaly_detection.block_rate_threshold' => 0.5,
            'openclaw.anomaly_detection.token_proliferation_threshold' => 5,
            'openclaw.anomaly_detection.baseline_requests_per_minute' => 10,
            'openclaw.anomaly_detection.spike_multiplier' => 2.0,
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
    // OpenClawAuditService — Direct recording
    // =========================================================================

    public function test_service_records_request_event(): void
    {
        $service = app(OpenClawAuditService::class);

        $request = Request::create('/api/v1/agent/health', 'GET');
        $request->headers->set('X-Agent-Source', 'openclaw');
        $request->headers->set('X-Correlation-Id', 'test-corr-svc-001');

        $record = $service->recordRequest(
            $request,
            OpenClawAuditLog::EVENT_REQUEST_PASSED,
            200,
            true,
            null,
            12.5
        );

        $this->assertNotNull($record);
        $this->assertInstanceOf(OpenClawAuditLog::class, $record);
        $this->assertEquals(OpenClawAuditLog::EVENT_REQUEST_PASSED, $record->event_type);
        $this->assertEquals(200, $record->http_durum_kodu);
        $this->assertTrue($record->basarili);
        $this->assertEquals(12.5, $record->duration_ms);
        $this->assertEquals('openclaw', $record->agent_source);
        $this->assertEquals('test-corr-svc-001', $record->correlation_id);
    }

    public function test_service_records_write_violation(): void
    {
        AgentContext::activate('agent.read.context', 'test-corr-vio-001', 'hash123');
        $service = app(OpenClawAuditService::class);

        $record = $service->recordWriteViolation(
            'App\\Services\\Ilan\\IlanCrudService',
            'store'
        );

        $this->assertNotNull($record);
        $this->assertEquals(OpenClawAuditLog::EVENT_WRITE_VIOLATION, $record->event_type);
        $this->assertFalse($record->basarili);
        $this->assertEquals(403, $record->http_durum_kodu);
        $this->assertEquals('App\\Services\\Ilan\\IlanCrudService', $record->service_class);
        $this->assertEquals('store', $record->service_method);
        $this->assertStringContains('Write blocked', $record->rejection_reason);
    }

    public function test_service_returns_null_on_persist_failure(): void
    {
        $service = app(OpenClawAuditService::class);

        // Create a request with minimum data — use an invalid event_type (too long)
        $request = Request::create('/test', 'GET');

        $logChannel = \Mockery::mock();
        $logChannel->shouldReceive('error')->once();
        Log::shouldReceive('channel')->with('security')->andReturn($logChannel);

        // Force failure by passing a string longer than the column allows (50 chars)
        $record = $service->recordRequest(
            $request,
            str_repeat('x', 100), // exceeds varchar(50)
            200,
            true
        );

        $this->assertNull($record);
    }

    // =========================================================================
    // OpenClawAuditService — Window stats
    // =========================================================================

    public function test_window_stats_returns_correct_aggregates(): void
    {
        $service = app(OpenClawAuditService::class);
        $request = Request::create('/api/v1/agent/health', 'GET');
        $request->headers->set('X-Agent-Source', 'openclaw');
        $request->headers->set('X-Agent-Token', 'token-a');

        // Create mixed records
        $service->recordRequest($request, OpenClawAuditLog::EVENT_REQUEST_PASSED, 200, true);
        $service->recordRequest($request, OpenClawAuditLog::EVENT_REQUEST_PASSED, 200, true);
        $service->recordRequest($request, OpenClawAuditLog::EVENT_BOUNDARY_REJECTED, 403, false, 'forbidden_route');

        AgentContext::activate('read', 'corr-1', 'hash-a');
        $service->recordWriteViolation('TestService', 'store');

        $stats = $service->getWindowStats(10);

        $this->assertGreaterThanOrEqual(4, $stats['total_requests']);
        $this->assertGreaterThanOrEqual(2, $stats['blocked_count']); // boundary_rejected + write_violation
        $this->assertGreaterThanOrEqual(1, $stats['violation_count']);
        $this->assertGreaterThanOrEqual(1, $stats['unique_tokens']);
        $this->assertIsFloat($stats['block_rate']);
    }

    public function test_violations_by_service(): void
    {
        $service = app(OpenClawAuditService::class);
        AgentContext::activate('read', 'corr-2', 'hash-b');

        $service->recordWriteViolation('ServiceA', 'store');
        $service->recordWriteViolation('ServiceA', 'store');
        $service->recordWriteViolation('ServiceB', 'update');

        $violations = $service->getViolationsByService(10);

        $this->assertNotEmpty($violations);
        // ServiceA should have higher count
        $serviceARow = collect($violations)->firstWhere('service_class', 'ServiceA');
        $this->assertNotNull($serviceARow);
        $this->assertGreaterThanOrEqual(2, $serviceARow['violation_count']);
    }

    // =========================================================================
    // OpenClawAuditLog — Model scopes
    // =========================================================================

    public function test_model_violations_scope(): void
    {
        OpenClawAuditLog::create([
            'event_type' => OpenClawAuditLog::EVENT_WRITE_VIOLATION,
            'basarili' => false,
            'olusturma_tarihi' => now(),
        ]);
        OpenClawAuditLog::create([
            'event_type' => OpenClawAuditLog::EVENT_REQUEST_PASSED,
            'basarili' => true,
            'olusturma_tarihi' => now(),
        ]);

        $violations = OpenClawAuditLog::violations()->recent(5)->count();
        $this->assertGreaterThanOrEqual(1, $violations);
    }

    public function test_model_by_correlation_scope(): void
    {
        $corrId = 'test-unique-' . uniqid();
        OpenClawAuditLog::create([
            'event_type' => OpenClawAuditLog::EVENT_REQUEST_PASSED,
            'correlation_id' => $corrId,
            'basarili' => true,
            'olusturma_tarihi' => now(),
        ]);

        $results = OpenClawAuditLog::byCorrelation($corrId)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($corrId, $results->first()->correlation_id);
    }

    public function test_model_blocked_scope(): void
    {
        OpenClawAuditLog::create([
            'event_type' => OpenClawAuditLog::EVENT_BOUNDARY_REJECTED,
            'basarili' => false,
            'olusturma_tarihi' => now(),
        ]);

        $blocked = OpenClawAuditLog::blocked()->recent(5)->count();
        $this->assertGreaterThanOrEqual(1, $blocked);
    }

    // =========================================================================
    // OpenClawAuditLog — Event constants integrity
    // =========================================================================

    public function test_event_constants_are_defined(): void
    {
        $this->assertEquals('gateway_open', OpenClawAuditLog::EVENT_GATEWAY_OPEN);
        $this->assertEquals('gateway_blocked', OpenClawAuditLog::EVENT_GATEWAY_BLOCKED);
        $this->assertEquals('scope_rejected', OpenClawAuditLog::EVENT_SCOPE_REJECTED);
        $this->assertEquals('token_invalid', OpenClawAuditLog::EVENT_TOKEN_INVALID);
        $this->assertEquals('boundary_rejected', OpenClawAuditLog::EVENT_BOUNDARY_REJECTED);
        $this->assertEquals('request_passed', OpenClawAuditLog::EVENT_REQUEST_PASSED);
        $this->assertEquals('write_violation', OpenClawAuditLog::EVENT_WRITE_VIOLATION);
    }

    // =========================================================================
    // Middleware integration — Audit DB records
    // =========================================================================

    public function test_middleware_creates_gateway_blocked_audit_on_kill_switch(): void
    {
        $this->configureOpenClaw(['openclaw.enabled' => false]);

        $countBefore = OpenClawAuditLog::where('event_type', OpenClawAuditLog::EVENT_GATEWAY_BLOCKED)->count();

        $response = $this->getJson('/api/v1/agent/health', $this->agentHeaders());
        $response->assertStatus(503);

        $countAfter = OpenClawAuditLog::where('event_type', OpenClawAuditLog::EVENT_GATEWAY_BLOCKED)->count();
        $this->assertGreaterThan($countBefore, $countAfter);
    }

    public function test_middleware_creates_token_invalid_audit_on_missing_token(): void
    {
        $this->configureOpenClaw();

        $countBefore = OpenClawAuditLog::where('event_type', OpenClawAuditLog::EVENT_TOKEN_INVALID)->count();

        $response = $this->getJson('/api/v1/agent/health', [
            'X-Agent-Source' => 'openclaw',
            // No token
        ]);
        $response->assertStatus(401);

        $countAfter = OpenClawAuditLog::where('event_type', OpenClawAuditLog::EVENT_TOKEN_INVALID)->count();
        $this->assertGreaterThan($countBefore, $countAfter);
    }

    public function test_middleware_creates_request_passed_audit_on_valid_request(): void
    {
        $this->configureOpenClaw();

        $countBefore = OpenClawAuditLog::where('event_type', OpenClawAuditLog::EVENT_REQUEST_PASSED)->count();

        $response = $this->getJson('/api/v1/agent/health', $this->agentHeaders());
        $response->assertStatus(200);

        $countAfter = OpenClawAuditLog::where('event_type', OpenClawAuditLog::EVENT_REQUEST_PASSED)->count();
        $this->assertGreaterThan($countBefore, $countAfter);
    }

    public function test_middleware_creates_boundary_rejected_audit_on_forbidden_route(): void
    {
        $this->configureOpenClaw();

        $countBefore = OpenClawAuditLog::where('event_type', OpenClawAuditLog::EVENT_BOUNDARY_REJECTED)->count();

        // admin.* is in forbidden patterns
        $response = $this->getJson('/api/v1/admin/users', $this->agentHeaders());
        $response->assertStatus(403);

        $countAfter = OpenClawAuditLog::where('event_type', OpenClawAuditLog::EVENT_BOUNDARY_REJECTED)->count();
        $this->assertGreaterThan($countBefore, $countAfter);
    }

    // =========================================================================
    // Anomaly Detector Command
    // =========================================================================

    public function test_anomaly_detector_returns_success_when_no_anomalies(): void
    {
        $this->configureOpenClaw();

        $exitCode = Artisan::call('openclaw:detect-anomalies', [
            '--window' => 1,
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No anomalies detected', Artisan::output());
    }

    public function test_anomaly_detector_detects_violation_burst(): void
    {
        $this->configureOpenClaw([
            'openclaw.anomaly_detection.violation_burst_threshold' => 2,
        ]);

        // Create enough violations to trigger
        OpenClawAuditLog::create([
            'event_type' => OpenClawAuditLog::EVENT_WRITE_VIOLATION,
            'basarili' => false,
            'service_class' => 'TestService',
            'service_method' => 'store',
            'olusturma_tarihi' => now(),
        ]);
        OpenClawAuditLog::create([
            'event_type' => OpenClawAuditLog::EVENT_WRITE_VIOLATION,
            'basarili' => false,
            'service_class' => 'TestService',
            'service_method' => 'update',
            'olusturma_tarihi' => now(),
        ]);

        $exitCode = Artisan::call('openclaw:detect-anomalies', [
            '--window' => 5,
            '--dry-run' => true,
        ]);

        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('anomaly', $output);
        $this->assertStringContainsString('write_violation_burst', $output);
    }

    public function test_anomaly_detector_detects_high_block_rate(): void
    {
        $this->configureOpenClaw([
            'openclaw.anomaly_detection.block_rate_threshold' => 0.3,
        ]);

        // Create 5 requests: 3 blocked, 2 passed = 60% block rate
        for ($i = 0; $i < 3; $i++) {
            OpenClawAuditLog::create([
                'event_type' => OpenClawAuditLog::EVENT_BOUNDARY_REJECTED,
                'basarili' => false,
                'olusturma_tarihi' => now(),
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            OpenClawAuditLog::create([
                'event_type' => OpenClawAuditLog::EVENT_REQUEST_PASSED,
                'basarili' => true,
                'olusturma_tarihi' => now(),
            ]);
        }

        $exitCode = Artisan::call('openclaw:detect-anomalies', [
            '--window' => 5,
            '--dry-run' => true,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('high_block_rate', Artisan::output());
    }

    // =========================================================================
    // Helper assertion
    // =========================================================================

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }
}
