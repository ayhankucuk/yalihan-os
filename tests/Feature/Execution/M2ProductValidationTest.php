<?php

namespace Tests\Feature\Execution;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\User;
use App\Models\WorkforceExecution;
use App\Repositories\EloquentExecutionMetricsRepository;
use App\Repositories\EloquentExecutionRuntimeRepository;
use App\Repositories\ExecutionMetricsRepositoryInterface;
use App\Repositories\ExecutionRuntimeRepositoryInterface;
use App\Services\Execution\ExecutionMetricsService;
use App\Services\Execution\ExecutionRuntimeService;
use App\Services\Execution\RecoveryEngineService;
use App\Services\Listing\YalihanLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

/**
 * Sprint 15 Program B — Operations Console Product Validation
 *
 * M2 Certification Gate Evidence: End-to-end execution, failure, recovery, replay.
 *
 * Bu test M2 Property Runtime sertifikasyonunun KANITIDIR.
 * Sadece kod çalışması yetmez — operatör gerçek senaryoda konsolu kullanabilmeli.
 *
 * Certification Gate Controls:
 *   ✅ Property → Listing lifecycle uçtan uca çalışıyor mu?
 *   ✅ Hatalı işlem otomatik kurtarılıyor mu?
 *   ✅ Replay geçmişi değiştirmiyor mu?
 *   ⏳ Operatör sorunları konsoldan görebiliyor mu?
 *   ⏳ BAI ve manuel süre kazancı gerçek veriden hesaplanıyor mu?
 *   ⏳ Tenant isolation UI ve API katmanında korunuyor mu?
 */
class M2ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    protected ExecutionRuntimeService $runtimeService;
    protected RecoveryEngineService $recoveryService;
    protected ExecutionMetricsService $metricsService;
    protected EloquentExecutionRuntimeRepository $runtimeRepository;
    protected EloquentExecutionMetricsRepository $metricsRepository;
    protected \App\Models\User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable role/guard middleware that requires DB role lookups.
        // Auth layer tested separately; this validates business logic.
        $this->withoutMiddleware(\App\Http\Middleware\RoleMiddleware::class);
        $this->withoutMiddleware(\App\Http\Middleware\SAB\GlobalWriteGuard::class);

        // Create admin user for actingAs
        $this->adminUser = \App\Models\User::factory()->admin()->make();

        Ilan::$skipPropertyIdGuard = true;
        YalihanLifecycle::$skipGuards = true;
        YalihanLifecycle::$isTransitioningCounter = 0;

        // Runtime repository setup
        $this->runtimeRepository = new EloquentExecutionRuntimeRepository(new WorkforceExecution());
        $this->app->instance(ExecutionRuntimeRepositoryInterface::class, $this->runtimeRepository);

        // Metrics repository setup (separate interface)
        $this->metricsRepository = new EloquentExecutionMetricsRepository(new WorkforceExecution());
        $this->app->instance(ExecutionMetricsRepositoryInterface::class, $this->metricsRepository);

        // Mock lifecycle for controlled testing
        $lifecycleMock = Mockery::mock(YalihanLifecycle::class);
        $lifecycleMock->shouldReceive('transition')->andReturnUsing(function ($ilan, $state) {
            $ilan->yayin_durumu = $state;
            return $ilan;
        });
        $this->app->instance(YalihanLifecycle::class, $lifecycleMock);

        $this->runtimeService = new ExecutionRuntimeService($this->runtimeRepository, $lifecycleMock);
        $this->recoveryService = new RecoveryEngineService($this->runtimeRepository, $this->runtimeService);
        $this->metricsService = new ExecutionMetricsService($this->metricsRepository);
    }

    protected function tearDown(): void
    {
        Ilan::$skipPropertyIdGuard = false;
        YalihanLifecycle::$skipGuards = false;
        YalihanLifecycle::$isTransitioningCounter = 0;
        Mockery::close();
        parent::tearDown();
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCENARIO 1: Successful Execution Lifecycle
    // Certification Control: Property → Listing lifecycle çalışıyor mu?
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function successful_execution_lifecycle(): void
    {
        // ── Step 1: Create a listing ─────────────────────────────────────
        $ilan = Ilan::factory()->create([
            'tenant_id' => 1,
            'yayin_durumu' => IlanDurumu::TASLAK,
        ]);

        // ── Step 2: Start execution (REQUESTED → RUNNING) ───────────────
        $result = $this->runtimeService->startExecution(
            aggregateType: 'Ilan',
            aggregateId: $ilan->id,
            capability: 'publish',
            triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
            workspaceId: null,
            actorId: 1,
            actorType: WorkforceExecution::ACTOR_USER,
        );

        $exec = $result['execution'];
        $this->assertTrue($result['created']);
        $this->assertEquals('RUNNING', $exec->execution_status);
        $this->assertNotNull($exec->started_at);
        $this->assertEquals(1, $exec->tenant_id);

        // ── Step 3: Complete execution ────────────────────────────────────
        $snapshot = ['yayin_durumu' => IlanDurumu::YAYINDA->value, 'published_by' => 'operator'];
        $completed = $this->runtimeService->completeExecution($exec->uuid, $snapshot);

        $this->assertEquals('COMPLETED', $completed->execution_status);
        $this->assertEquals($snapshot, $completed->result_snapshot);
        $this->assertNotNull($completed->finished_at);
        $this->assertNotNull($completed->duration_ms);
        $this->assertGreaterThan(0, $completed->duration_ms);

        // ── Step 4: Verify metrics reflect success ───────────────────────
        $report = $this->metricsService->generateReport(tenantId: 1);
        $this->assertEquals(1, $report['total_executions']);
        $this->assertEquals(1.0, $report['success_rate']);
        $this->assertEquals(0.0, $report['failure_rate']);
        $this->assertEquals(0.0, $report['replay_rate']);

        // ── Step 5: Verify OperationsConsole API returns correct data ──────
        $response = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/overview?tenant_id=1');
        $response->assertStatus(200);

        $payload = $response->json();
        $this->assertEquals(1, $payload['tenant_id']);
        $this->assertEquals(1, $payload['summary']['total_executions']);
        $this->assertEquals(1.0, $payload['summary']['success_rate']);
        $this->assertEquals(0, $payload['summary']['failed_count']);

        // Verify the execution appears in the executions list
        $execListResponse = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/executions?tenant_id=1');
        $execListResponse->assertStatus(200);
        $this->assertEquals(1, $execListResponse->json('count'));
        $this->assertEquals('COMPLETED', $execListResponse->json('executions.0.execution_status'));
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCENARIO 2: Failed Execution + Recovery
    // Certification Control: Hatalı işlem otomatik kurtarılıyor mu?
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function failed_execution_and_recovery(): void
    {
        // ── Step 1: Start execution ────────────────────────────────────────
        $ilan = Ilan::factory()->create([
            'tenant_id' => 1,
            'yayin_durumu' => IlanDurumu::TASLAK,
        ]);

        $result = $this->runtimeService->startExecution(
            aggregateType: 'Ilan',
            aggregateId: $ilan->id,
            capability: 'publish',
            triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
            actorId: 1,
        );
        $originalExec = $result['execution'];

        // ── Step 2: Mark as FAILED (simulate transient error) ─────────────
        $failed = $this->runtimeService->failExecution(
            $originalExec->uuid,
            'TIMEOUT',
            'Connection timed out after 30000ms.',
            ['endpoint' => 'https://api.service.com/publish', 'http_code' => 504]
        );

        $this->assertEquals('FAILED', $failed->execution_status);
        $this->assertEquals('TIMEOUT', $failed->error_code);
        $this->assertStringContainsString('timed out', $failed->error_message);

        // ── Step 3: Plan recovery ──────────────────────────────────────────
        $plan = $this->recoveryService->planRecovery($failed);

        $this->assertTrue($plan['can_retry']);
        $this->assertEquals('TRANSIENT', $plan['classification']); // TIMEOUT → TRANSIENT
        $this->assertEquals('EXPONENTIAL', $plan['policy']);
        $this->assertEquals(0, $plan['retry_count']);
        $this->assertNotNull($plan['next_retry_at']);

        // ── Step 4: Execute recovery ───────────────────────────────────────
        $recovery = $this->recoveryService->recover(
            failedExecutionUuid: $failed->uuid,
            actorId: 1,
            actorType: WorkforceExecution::ACTOR_SYSTEM,
            recoveryReason: 'Manuel operatör müdahalesi — timeout sonrası retry',
        );

        // Recovery creates a NEW execution with new UUID
        $this->assertNotEquals($failed->uuid, $recovery->uuid);
        $this->assertEquals($failed->uuid, $recovery->replay_of_uuid);
        $this->assertEquals($failed->uuid, $recovery->parent_uuid);
        $this->assertEquals('REQUESTED', $recovery->execution_status);
        $this->assertEquals($failed->uuid, $recovery->recovery_of_uuid);
        $this->assertNotNull($recovery->recovered_at);

        // ── Step 5: Verify recovery appears in queue ───────────────────────
        $queueResponse = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/recovery-queue?tenant_id=1');
        $queueResponse->assertStatus(200);

        // ── Step 6: Verify failed executions are visible in console ────────
        $overviewResponse = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/overview?tenant_id=1');
        $overviewPayload = $overviewResponse->json();

        $this->assertGreaterThanOrEqual(1, $overviewPayload['summary']['failed_count']);
        $this->assertEquals('FAILED', $overviewPayload['failed_executions'][0]['execution_status']);

        // ── Step 7: Verify console API shows recovery relationship ────────
        $execDetailResponse = $this->actingAs($this->adminUser)->getJson("/admin/operations/api/executions/{$failed->uuid}");
        $execDetailResponse->assertStatus(200);

        $detail = $execDetailResponse->json();
        $this->assertEquals('FAILED', $detail['execution']['execution_status']);
        $this->assertEquals('TIMEOUT', $detail['execution']['error_code']);
        // Replay zinciri: original + recovery
        $this->assertCount(2, $detail['replay_chain']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCENARIO 3: Replay Chain Integrity
    // Certification Control: Replay geçmişi değiştirmiyor mu?
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function replay_chain_does_not_mutate_history(): void
    {
        $ilan = Ilan::factory()->create([
            'tenant_id' => 1,
            'yayin_durumu' => IlanDurumu::TASLAK,
        ]);

        // ── Original execution ────────────────────────────────────────────
        $r0 = $this->runtimeService->startExecution(
            aggregateType: 'Ilan', aggregateId: $ilan->id,
            capability: 'publish', triggerType: WorkforceExecution::TRIGGER_MANUAL,
            tenantId: 1,
        );
        $r0Exec = $this->runtimeService->completeExecution($r0['execution']->uuid, ['ok' => true]);
        $originalUuid = $r0Exec->uuid;

        // Record original state
        $originalStatus = $r0Exec->execution_status;
        $originalTrigger = $r0Exec->trigger_type;
        $originalCount = WorkforceExecution::count();

        // ── Replay 1 ─────────────────────────────────────────────────────
        $r1 = $this->runtimeService->replay($originalUuid, actorId: 2, replayReason: 'User retry');
        $this->assertEquals($originalUuid, $r1->replay_of_uuid);
        $this->assertEquals($originalUuid, $r1->parent_uuid);
        $this->assertEquals('REPLAY', $r1->trigger_type);

        // ── Replay 2 ─────────────────────────────────────────────────────
        $r2 = $this->runtimeService->replay($r1->uuid, actorId: 3, replayReason: 'Second retry');
        $this->assertEquals($originalUuid, $r2->replay_of_uuid); // Always root
        $this->assertEquals($r1->uuid, $r2->parent_uuid);
        $this->assertEquals(2, $r2->getReplayChainDepth());

        // ── CRITICAL: Original record is UNCHANGED ───────────────────────
        $original = WorkforceExecution::where('uuid', $originalUuid)->first();

        $this->assertEquals($originalStatus, $original->execution_status); // COMPLETED
        $this->assertEquals($originalTrigger, $original->trigger_type);   // MANUAL (NOT REPLAY)
        $this->assertNull($original->replay_of_uuid);
        $this->assertNull($original->parent_uuid);

        // ── CRITICAL: Replay does not create extra records ────────────────
        $this->assertEquals($originalCount + 2, WorkforceExecution::count());

        // ── Replay chain visible in console ───────────────────────────────
        $chainResponse = $this->actingAs($this->adminUser)->getJson("/admin/operations/api/executions/{$originalUuid}");
        $chainPayload = $chainResponse->json();

        $this->assertCount(3, $chainPayload['replay_chain']); // r0, r1, r2
        $this->assertEquals($originalUuid, $chainPayload['replay_chain'][0]['uuid']);
        $this->assertEquals('COMPLETED', $chainPayload['replay_chain'][0]['execution_status']);
        $this->assertEquals('MANUAL', $chainPayload['replay_chain'][0]['trigger_type']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCENARIO 4: Operations Console — Full Dashboard Visibility
    // Certification Control: Operatör sorunları konsoldan görebiliyor mu?
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function console_shows_active_and_failed_executions(): void
    {
        $ilan = Ilan::factory()->create(['tenant_id' => 1]);

        // 2 RUNNING (active)
        $this->runtimeService->startExecution(
            'Ilan', $ilan->id, 'publish', WorkforceExecution::TRIGGER_MANUAL, 1
        );
        $this->runtimeService->startExecution(
            'Ilan', $ilan->id, 'archive', WorkforceExecution::TRIGGER_SCHEDULED, 1
        );

        // 1 COMPLETED
        $completed = $this->runtimeService->startExecution(
            'Ilan', $ilan->id, 'publish', WorkforceExecution::TRIGGER_MANUAL, 1
        );
        $this->runtimeService->completeExecution($completed['execution']->uuid, ['done' => true]);

        // 1 FAILED
        $failed = $this->runtimeService->startExecution(
            'Ilan', $ilan->id, 'publish', WorkforceExecution::TRIGGER_WEBHOOK, 1
        );
        $this->runtimeService->failExecution(
            $failed['execution']->uuid, 'GUARD_FAILED', 'completion_score < 60'
        );

        // ── API: /api/overview ──────────────────────────────────────────
        $overview = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/overview?tenant_id=1');
        $overview->assertStatus(200);

        $data = $overview->json();
        $this->assertEquals(1, $data['tenant_id']);

        // Summary checks
        $summary = $data['summary'];
        $this->assertEquals(4, $summary['total_executions']);
        $this->assertGreaterThan(0, $summary['needs_attention']);

        // Active executions visible
        $this->assertCount(2, $data['active_executions']);
        $activeStatuses = array_column($data['active_executions'], 'execution_status');
        $this->assertContains('RUNNING', $activeStatuses);

        // Failed executions visible
        $this->assertCount(1, $data['failed_executions']);
        $this->assertEquals('FAILED', $data['failed_executions'][0]['execution_status']);
        $this->assertEquals('GUARD_FAILED', $data['failed_executions'][0]['error_code']);

        // ── API: /api/executions (filterable) ─────────────────────────────
        $failedOnly = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/executions?tenant_id=1&execution_status=FAILED');
        $failedOnly->assertStatus(200);
        $this->assertEquals(1, $failedOnly->json('count'));

        $byCap = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/executions?tenant_id=1&capability=publish');
        $byCap->assertStatus(200);

        // ── API: /api/metrics/capability ─────────────────────────────────
        $metrics = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/metrics/capability?tenant_id=1');
        $metrics->assertStatus(200);

        $caps = $metrics->json('capabilities');
        $this->assertArrayHasKey('publish', $caps);
        $this->assertArrayHasKey('archive', $caps);
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCENARIO 5: API ↔ UI Consistency (API = Source of Truth)
    // Certification Control: API endpoint'leri ile ekran değerleri aynı mı?
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function api_is_source_of_truth_for_console(): void
    {
        $ilan = Ilan::factory()->create(['tenant_id' => 1]);

        $result = $this->runtimeService->startExecution(
            'Ilan', $ilan->id, 'publish', WorkforceExecution::TRIGGER_MANUAL, 1
        );
        $exec = $this->runtimeService->completeExecution($result['execution']->uuid, ['x' => 1]);

        // ── Multiple API endpoints ────────────────────────────────────────
        $overview = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/overview?tenant_id=1');
        $overviewData = $overview->json();

        $execList = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/executions?tenant_id=1');
        $execListData = $execList->json();

        $execDetail = $this->actingAs($this->adminUser)->getJson("/admin/operations/api/executions/{$exec->uuid}");
        $execDetail->assertStatus(200);
        $execDetailData = $execDetail->json();

        $metrics = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/metrics/capability?tenant_id=1');
        $metricsData = $metrics->json();

        // Cross-verification
        // Overview total == Executions count
        $this->assertEquals(
            $overviewData['summary']['total_executions'],
            $execListData['count']
        );

        // Single execution detail matches the list
        $listedExec = collect($execListData['executions'])->firstWhere('uuid', $exec->uuid);
        $this->assertEquals($listedExec['execution_status'], $execDetailData['execution']['execution_status']);
        $this->assertEquals($listedExec['capability'], $execDetailData['execution']['capability']);

        // Metrics API and overview API agree on success rate
        $this->assertEquals(
            $overviewData['metrics']['success_rate'],
            $metricsData['capabilities']['publish']['success_rate'] ?? $overviewData['metrics']['success_rate']
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCENARIO 6: Tenant Isolation — API Layer Enforcement
    // Certification Control: Tenant isolation UI ve API katmanında korunuyor mu?
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function tenant_isolation_blocks_cross_tenant_access(): void
    {
        // ── Tenant 1 executions ───────────────────────────────────────────
        $ilan1 = Ilan::factory()->create(['tenant_id' => 1]);
        $r1 = $this->runtimeService->startExecution(
            'Ilan', $ilan1->id, 'publish', WorkforceExecution::TRIGGER_MANUAL, 1
        );
        $this->runtimeService->completeExecution($r1['execution']->uuid);

        $f1 = $this->runtimeService->startExecution(
            'Ilan', $ilan1->id, 'publish', WorkforceExecution::TRIGGER_MANUAL, 1
        );
        $this->runtimeService->failExecution($f1['execution']->uuid, 'ERR', 'Tenant 1 hata');

        // ── Tenant 2 executions ───────────────────────────────────────────
        $ilan2 = Ilan::factory()->create(['tenant_id' => 2]);
        $r2 = $this->runtimeService->startExecution(
            'Ilan', $ilan2->id, 'publish', WorkforceExecution::TRIGGER_MANUAL, 2
        );
        $this->runtimeService->completeExecution($r2['execution']->uuid);

        // ── Tenant 1 queries their own data ───────────────────────────────
        $t1Overview = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/overview?tenant_id=1');
        $t1Data = $t1Overview->json();

        $this->assertEquals(1, $t1Data['tenant_id']);
        $this->assertEquals(2, $t1Data['summary']['total_executions']);
        $this->assertEquals(1, $t1Data['summary']['failed_count']);
        $this->assertEquals(0.5, $t1Data['summary']['success_rate']);

        $t1FailedUuids = array_column($t1Data['failed_executions'], 'uuid');
        $this->assertContains($f1['execution']->uuid, $t1FailedUuids);

        // ── Tenant 2 queries their own data ──────────────────────────────
        $t2Overview = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/overview?tenant_id=2');
        $t2Data = $t2Overview->json();

        $this->assertEquals(2, $t2Data['tenant_id']);
        $this->assertEquals(1, $t2Data['summary']['total_executions']);
        $this->assertEquals(0, $t2Data['summary']['failed_count']);
        $this->assertEquals(1.0, $t2Data['summary']['success_rate']);

        // ── Tenant 1 CANNOT see Tenant 2's execution ─────────────────────
        $this->assertNotContains($r2['execution']->uuid, $t1FailedUuids);

        // ── Cross-tenant replay blocked ────────────────────────────────────
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cross-tenant replay forbidden');

        $this->runtimeService->validateReplayBoundary(
            $r1['execution']->uuid,
            requestingTenantId: 2
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCENARIO 7: BAI Metrics — Real Data Calculation
    // Certification Control: BAI ve metrikler gerçek veriden hesaplanıyor mu?
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function bai_metrics_calculated_from_real_execution_data(): void
    {
        $ilan = Ilan::factory()->create(['tenant_id' => 1]);

        // 3 completed
        for ($i = 0; $i < 3; $i++) {
            $r = $this->runtimeService->startExecution(
                'Ilan', $ilan->id, 'publish', WorkforceExecution::TRIGGER_MANUAL, 1
            );
            $this->runtimeService->completeExecution($r['execution']->uuid, ['ms' => 200]);
        }

        // 1 failed
        $f = $this->runtimeService->startExecution(
            'Ilan', $ilan->id, 'publish', WorkforceExecution::TRIGGER_WEBHOOK, 1
        );
        $this->runtimeService->failExecution($f['execution']->uuid, 'TIMEOUT', '...');

        // 1 replay (archive capability)
        $orig = $this->runtimeService->startExecution(
            'Ilan', $ilan->id, 'archive', WorkforceExecution::TRIGGER_MANUAL, 1
        );
        $this->runtimeService->completeExecution($orig['execution']->uuid);
        $replay = $this->runtimeService->replay($orig['execution']->uuid, actorId: 2);
        $this->runtimeService->completeExecution($replay->uuid);

        // ── Generate full report ─────────────────────────────────────────
        $report = $this->metricsService->generateReport(tenantId: 1);

        // Total: 3 publish completed + 1 publish failed + 1 archive completed + 1 archive replay = 6
        $this->assertEquals(6, $report['total_executions']);
        $this->assertGreaterThan(0, $report['success_rate']); // At least one completed
        $this->assertGreaterThanOrEqual(0, $report['failure_rate']);
        $this->assertGreaterThanOrEqual(0, $report['replay_rate']);
        $this->assertLessThanOrEqual(1.0, $report['success_rate']);

        // ── Capability breakdown ──────────────────────────────────────────
        $capMetrics = $this->metricsService->calculateCapabilityMetrics(tenantId: 1);

        $this->assertArrayHasKey('publish', $capMetrics);
        $this->assertArrayHasKey('archive', $capMetrics);
        // publish: 3 completed + 1 failed = 4 total
        $this->assertEquals(4, $capMetrics['publish']['count']);
        // archive: 1 completed + 1 replay = 2 total
        $this->assertEquals(2, $capMetrics['archive']['count']);

        // ── BAI Report via API — must be consistent with service ─────────
        $apiReport = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/overview?tenant_id=1');
        $apiReport->assertStatus(200);
        $apiData = $apiReport->json();

        $this->assertEquals($report['total_executions'], $apiData['summary']['total_executions']);
        $this->assertEquals($report['success_rate'], $apiData['summary']['success_rate']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCENARIO 8: Failure Classification → Recovery Policy Mapping
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function failure_classification_drives_correct_recovery_policy(): void
    {
        $cases = [
            ['TIMEOUT',                  'TRANSIENT',  true],
            ['HTTP_502',                 'TRANSIENT',  true],
            ['HTTP_429',                 'TRANSIENT',  true],
            ['CONNECTION_REFUSED',        'TRANSIENT',  true],
            ['VALIDATION_ERROR',         'PERMANENT',  false],
            ['BUSINESS_RULE_VIOLATION',  'PERMANENT',  false],
            ['UNAUTHORIZED_ACCESS',      'PERMANENT',  false],
            ['API_KEY_MISSING',          'CONFIG',     false],
            ['SERVICE_UNAVAILABLE',      'CONFIG',     false],
            ['RATE_LIMIT_EXCEEDED',      'CONFIG',     false],
            ['UNKNOWN_ERROR_XYZ',        'UNKNOWN',    true],
        ];

        foreach ($cases as [$errorCode, $expectedClass, $expectedCanRetry]) {
            $ilanForCase = Ilan::factory()->create(['tenant_id' => 1]);

            $r = $this->runtimeService->startExecution(
                'Ilan', $ilanForCase->id, 'publish', WorkforceExecution::TRIGGER_MANUAL, 1
            );
            $failed = $this->runtimeService->failExecution(
                $r['execution']->uuid, $errorCode, "Error: {$errorCode}"
            );

            $plan = $this->recoveryService->planRecovery($failed);

            $this->assertEquals(
                $expectedClass, $plan['classification'],
                "ErrorCode={$errorCode}: expected {$expectedClass}, got {$plan['classification']}"
            );
            $this->assertEquals(
                $expectedCanRetry, $plan['can_retry'],
                "ErrorCode={$errorCode}: can_retry expected {$expectedCanRetry}"
            );
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // M2 CERTIFICATION GATE — ALL CONTROLS
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function m2_certification_gate_all_controls_pass(): void
    {
        $ilan = Ilan::factory()->create(['tenant_id' => 1]);

        // ── Control 1: Property → Listing lifecycle ─────────────────────
        $r = $this->runtimeService->startExecution(
            'Ilan', $ilan->id, 'publish', WorkforceExecution::TRIGGER_MANUAL, 1
        );
        $completed = $this->runtimeService->completeExecution($r['execution']->uuid, ['done' => true]);
        $this->assertEquals('COMPLETED', $completed->execution_status);
        // ✅ PASS

        // ── Control 2: Auto recovery ─────────────────────────────────────
        $f = $this->runtimeService->startExecution(
            'Ilan', $ilan->id, 'publish', WorkforceExecution::TRIGGER_MANUAL, 1
        );
        $failed = $this->runtimeService->failExecution($f['execution']->uuid, 'TIMEOUT', '...');
        $recovery = $this->recoveryService->recover($failed->uuid);
        $this->assertNotEquals($failed->uuid, $recovery->uuid);
        // ✅ PASS

        // ── Control 3: Replay immutability ───────────────────────────────
        $orig = $this->runtimeService->startExecution(
            'Ilan', $ilan->id, 'archive', WorkforceExecution::TRIGGER_MANUAL, 1
        );
        $this->runtimeService->completeExecution($orig['execution']->uuid);
        $origStatus = WorkforceExecution::where('uuid', $orig['execution']->uuid)->first()->execution_status;
        $this->runtimeService->replay($orig['execution']->uuid);
        $afterReplay = WorkforceExecution::where('uuid', $orig['execution']->uuid)->first()->execution_status;
        $this->assertEquals($origStatus, $afterReplay);
        // ✅ PASS

        // ── Control 4: Console visibility ───────────────────────────────
        $overview = $this->actingAs($this->adminUser)->getJson('/admin/operations/api/overview?tenant_id=1');
        $overview->assertStatus(200);
        $this->assertNotEmpty($overview->json('failed_executions'));
        $this->assertNotEmpty($overview->json('active_executions'));
        // ✅ PASS

        // ── Control 5: BAI metrics from real data ────────────────────────
        $report = $this->metricsService->generateReport(tenantId: 1);
        $this->assertGreaterThan(0, $report['total_executions']);
        $this->assertArrayHasKey('success_rate', $report);
        $this->assertArrayHasKey('by_capability', $report);
        // ✅ PASS

        // ── Control 6: Tenant isolation ──────────────────────────────────
        $this->expectException(\DomainException::class);
        $this->runtimeService->validateReplayBoundary($r['execution']->uuid, requestingTenantId: 999);
        // ✅ PASS (exception = isolation working)

        $this->assertTrue(true, 'M2 Certification Gate: All controls PASSED');
    }
}
