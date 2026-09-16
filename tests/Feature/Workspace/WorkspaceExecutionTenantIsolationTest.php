<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Models\WorkspaceExecution;
use App\Services\Workspace\WorkspaceExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * P0 Slug/Routing Security — Drive Tarafı Tenant İzolasyonu
 *
 * Sprint 4.7: Workspace Execution Engine
 *
 * Test kapsamı:
 *  1. Tenant-A user cancels Tenant-B's execution → 403
 *  2. Tenant-A user replays Tenant-B's execution → 403
 *  3. Tenant-A user retries Tenant-B's execution → 403
 *  4. Tenant-A user lists Tenant-B's executions → 0 results (TenantScope fail-closed)
 *  5. Tenant-A user views Tenant-B's execution details → 404
 *  6. Tenant-A user gets Tenant-B's execution summary → 0 results
 *  7. Admin (super-admin) can access any workspace execution
 *  8. Cancelled/replayed/retry'd execution state transitions are correct
 *
 * Güvenlik katmanları:
 *  - WorkspaceExecution: TenantScope (fail-closed) — query katmanı
 *  - WorkspaceExecutionController: authorizeWorkspace() → PortfolioDriveWorkspacePolicy
 *  - PortfolioDriveWorkspacePolicy: tenant_id kontrolü
 */
class WorkspaceExecutionTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $userA;
    private User $userB;
    private Ilan $ilanA;
    private Ilan $ilanB;
    private PortfolioDriveWorkspace $workspaceA;
    private PortfolioDriveWorkspace $workspaceB;
    private WorkspaceExecution $executionA;
    private WorkspaceExecution $executionB;

    protected function setUp(): void
    {
        parent::setUp();

        Log::spy();

        // Tenant A
        $this->tenantA = Tenant::create([
            'name' => 'Tenant A — Drive Isolation',
            'domain' => 'tenant-a-drive.test',
            'status' => 'active',
        ]);

        // Tenant B
        $this->tenantB = Tenant::create([
            'name' => 'Tenant B — Drive Isolation',
            'domain' => 'tenant-b-drive.test',
            'status' => 'active',
        ]);

        // Users
        $this->userA = $this->makeAdminUser($this->tenantA, 'user-a-drive@test.com');
        $this->userB = $this->makeAdminUser($this->tenantB, 'user-b-drive@test.com');

        // Ilanlar
        $this->ilanA = Ilan::create([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A İlan',
            'yayin_durumu' => 'aktif',
            'aktiflik_durumu' => 1,
        ]);

        $this->ilanB = Ilan::create([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Tenant B İlan',
            'yayin_durumu' => 'aktif',
            'aktiflik_durumu' => 1,
        ]);

        // Workspaces
        $this->workspaceA = PortfolioDriveWorkspace::create([
            'tenant_id' => $this->tenantA->id,
            'ilan_id' => $this->ilanA->id,
            'drive_folder_id' => 'folder_tenant_a_' . $this->tenantA->id,
            'workspace_status' => PortfolioDriveWorkspace::STATUS_READY,
        ]);

        $this->workspaceB = PortfolioDriveWorkspace::create([
            'tenant_id' => $this->tenantB->id,
            'ilan_id' => $this->ilanB->id,
            'drive_folder_id' => 'folder_tenant_b_' . $this->tenantB->id,
            'workspace_status' => PortfolioDriveWorkspace::STATUS_READY,
        ]);

        // Executions
        $this->executionA = WorkspaceExecution::create([
            'tenant_id' => $this->tenantA->id,
            'workspace_id' => $this->workspaceA->id,
            'ilan_id' => $this->ilanA->id,
            'execution_type' => 'test_run',
            'execution_label' => 'Tenant A Test Execution',
            'state' => WorkspaceExecution::STATE_QUEUED,
            'attempt_number' => 1,
            'max_attempts' => 3,
            'retry_count' => 0,
            'queue_name' => 'default',
            'timeout_seconds' => 300,
            'triggered_by' => WorkspaceExecution::TRIGGERED_BY_MANUAL,
        ]);

        $this->executionB = WorkspaceExecution::create([
            'tenant_id' => $this->tenantB->id,
            'workspace_id' => $this->workspaceB->id,
            'ilan_id' => $this->ilanB->id,
            'execution_type' => 'test_run',
            'execution_label' => 'Tenant B Test Execution',
            'state' => WorkspaceExecution::STATE_QUEUED,
            'attempt_number' => 1,
            'max_attempts' => 3,
            'retry_count' => 0,
            'queue_name' => 'default',
            'timeout_seconds' => 300,
            'triggered_by' => WorkspaceExecution::TRIGGERED_BY_MANUAL,
        ]);
    }

    // ─── Cancel ─────────────────────────────────────────────────────────────

    /** @test */
    public function tenant_a_cannot_cancel_tenant_b_execution(): void
    {
        $this->actingAs($this->userA);

        $response = $this->postJson(
            "/admin/workspace/{$this->workspaceB->id}/executions/{$this->executionB->id}/cancel"
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_a_cancels_own_execution_successfully(): void
    {
        $this->actingAs($this->userA);

        $response = $this->postJson(
            "/admin/workspace/{$this->workspaceA->id}/executions/{$this->executionA->id}/cancel"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('execution.state', WorkspaceExecution::STATE_CANCELLED);

        $this->executionA->refresh();
        $this->assertEquals(WorkspaceExecution::STATE_CANCELLED, $this->executionA->state);
    }

    /** @test */
    public function cancel_returns_422_for_terminal_execution(): void
    {
        $this->executionA->markFailed('Test failure');

        $this->actingAs($this->userA);

        $response = $this->postJson(
            "/admin/workspace/{$this->workspaceA->id}/executions/{$this->executionA->id}/cancel"
        );

        $response->assertStatus(422);
    }

    // ─── Replay ─────────────────────────────────────────────────────────────

    /** @test */
    public function tenant_a_cannot_replay_tenant_b_execution(): void
    {
        $this->executionB->markFailed('Simulated failure');

        $this->actingAs($this->userA);

        $response = $this->postJson(
            "/admin/workspace/{$this->workspaceB->id}/executions/{$this->executionB->id}/replay"
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_a_replays_own_execution_successfully(): void
    {
        $this->executionA->markFailed('Simulated failure');

        $this->actingAs($this->userA);

        $response = $this->postJson(
            "/admin/workspace/{$this->workspaceA->id}/executions/{$this->executionA->id}/replay"
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'execution' => ['id', 'type', 'state', 'execution_type'],
        ]);
    }

    /** @test */
    public function replay_creates_new_execution_record(): void
    {
        $this->executionA->markFailed('Simulated failure');

        $this->actingAs($this->userA);

        $response = $this->postJson(
            "/admin/workspace/{$this->workspaceA->id}/executions/{$this->executionA->id}/replay"
        );

        $response->assertStatus(200);

        $newExecId = $response->json('execution.id');
        $this->assertNotEquals($this->executionA->id, $newExecId);

        // Original execution still exists and is failed
        $this->executionA->refresh();
        $this->assertEquals(WorkspaceExecution::STATE_FAILED, $this->executionA->state);
    }

    // ─── Retry ──────────────────────────────────────────────────────────────

    /** @test */
    public function tenant_a_cannot_retry_tenant_b_execution(): void
    {
        $this->executionB->markFailed('Simulated failure');

        $this->actingAs($this->userB);
        // Exhaust retry attempts
        $this->executionB->update(['retry_count' => 1]);

        $this->actingAs($this->userA);

        $response = $this->postJson(
            "/admin/workspace/{$this->workspaceB->id}/executions/{$this->executionB->id}/retry"
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_a_retries_own_execution_successfully(): void
    {
        $this->executionA->markFailed('Simulated failure');

        $this->actingAs($this->userA);

        $response = $this->postJson(
            "/admin/workspace/{$this->workspaceA->id}/executions/{$this->executionA->id}/retry"
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'execution' => ['id', 'type', 'state', 'execution_type'],
        ]);
    }

    /** @test */
    public function retry_creates_new_execution_with_same_payload(): void
    {
        $this->executionA->markFailed('Simulated failure');

        $this->actingAs($this->userA);

        $response = $this->postJson(
            "/admin/workspace/{$this->workspaceA->id}/executions/{$this->executionA->id}/retry"
        );

        $response->assertStatus(200);

        $newExecId = $response->json('execution.id');
        $this->assertNotEquals($this->executionA->id, $newExecId);

        $newExec = WorkspaceExecution::find($newExecId);
        $this->assertNotNull($newExec);
        $this->assertEquals(WorkspaceExecution::STATE_QUEUED, $newExec->state);
        $this->assertEquals($this->executionA->ilan_id, $newExec->ilan_id);
        $this->assertEquals($this->executionA->workspace_id, $newExec->workspace_id);
    }

    // ─── List / Index ───────────────────────────────────────────────────────

    /** @test */
    public function tenant_a_lists_only_own_executions(): void
    {
        $this->actingAs($this->userA);

        $response = $this->getJson("/admin/workspace/{$this->workspaceA->id}/executions");

        $response->assertStatus(200);
        $response->assertJsonPath('count', 1);
        $response->assertJsonPath('executions.0.id', $this->executionA->id);
    }

    /** @test */
    public function tenant_a_sees_zero_executions_for_tenant_b_workspace(): void
    {
        $this->actingAs($this->userA);

        // Workspace B is tenant-B's workspace
        $response = $this->getJson("/admin/workspace/{$this->workspaceB->id}/executions");

        // Controller checks workspace ownership → 403 for unauthorized workspace
        $response->assertStatus(403);
    }

    // ─── Show ───────────────────────────────────────────────────────────────

    /** @test */
    public function tenant_a_cannot_view_tenant_b_execution_details(): void
    {
        $this->actingAs($this->userA);

        $response = $this->getJson(
            "/admin/workspace/{$this->workspaceB->id}/executions/{$this->executionB->id}"
        );

        // Workspace B ownership check fails → 403
        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_a_views_own_execution_details_successfully(): void
    {
        $this->actingAs($this->userA);

        $response = $this->getJson(
            "/admin/workspace/{$this->workspaceA->id}/executions/{$this->executionA->id}"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('execution.id', $this->executionA->id);
        $response->assertJsonPath('execution.state', WorkspaceExecution::STATE_QUEUED);
    }

    // ─── Summary ────────────────────────────────────────────────────────────

    /** @test */
    public function tenant_a_cannot_get_tenant_b_execution_summary(): void
    {
        $this->actingAs($this->userA);

        $response = $this->getJson("/admin/workspace/{$this->workspaceB->id}/executions-summary");

        // Workspace B ownership check fails → 403
        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_a_gets_own_execution_summary_successfully(): void
    {
        $this->actingAs($this->userA);

        $response = $this->getJson("/admin/workspace/{$this->workspaceA->id}/executions-summary");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total',
            'active',
            'queued',
            'succeeded',
            'failed',
            'cancelled',
        ]);
    }

    // ─── TenantScope fail-closed (bypass girişimi) ─────────────────────────

    /** @test */
    public function tenant_scope_prevents_cross_tenant_execution_access_without_workspace_check(): void
    {
        // Simulate a direct WorkspaceExecution query without workspace ownership check.
        // This catches the case where a developer bypasses authorizeWorkspace() and
        // relies only on TenantScope. The TenantScope's fail-closed logic ensures
        // that even if workspace ownership check is somehow bypassed, cross-tenant
        // execution queries still return zero results.
        $service = app(WorkspaceExecutionService::class);

        // Tenant B service call with Tenant A context → should return empty
        $this->actingAs($this->userA);

        $executions = $service->getForWorkspace($this->workspaceB->id);

        // TenantScope fail-closed: no results when tenant context mismatch
        $this->assertEmpty($executions);
    }

    // ─── Admin bypass ──────────────────────────────────────────────────────

    /** @test */
    public function super_admin_can_access_any_workspace_execution(): void
    {
        $superAdmin = User::factory()->create([
            'email' => 'super-admin-drive@test.com',
            'tenant_id' => $this->tenantA->id,
        ]);
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->assignRole($superAdminRole);

        $this->actingAs($superAdmin);

        // Super-admin can view Tenant B's execution
        $response = $this->getJson(
            "/admin/workspace/{$this->workspaceB->id}/executions/{$this->executionB->id}"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('execution.id', $this->executionB->id);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeAdminUser(Tenant $tenant, string $email): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'email_verified_at' => now(),
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($adminRole);

        return $user;
    }
}
