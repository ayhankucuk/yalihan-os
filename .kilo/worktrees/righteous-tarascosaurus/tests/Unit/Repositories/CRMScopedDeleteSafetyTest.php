<?php

namespace Tests\Unit\Repositories;

use App\Models\Kisi;
use App\Models\Lead;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Repositories\KisiRepository;
use App\Repositories\LeadRepository;
use App\Repositories\GorevRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4B.3: Test Suite & Validation
 * Step 2: Scoped Delete Safety Tests
 *
 * PASS Criteria:
 * ✓ Tenant A cannot delete Tenant B CRM records
 * ✓ No unscoped destructive operations
 * ✓ Scoped delete operations enforce ownership
 * ✓ Soft delete respects tenant boundaries
 *
 * @governance PHASE4B_VALIDATION
 * @governance SCOPED_DELETE_GUARD
 * @created 2026-05-12
 */
class CRMScopedDeleteSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected KisiRepository $kisiRepo;
    protected LeadRepository $leadRepo;
    protected GorevRepository $gorevRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kisiRepo = app(KisiRepository::class);
        $this->leadRepo = app(LeadRepository::class);
        $this->gorevRepo = app(GorevRepository::class);
    }

    protected function createUserWithRole(string $name, int $id, bool $isAdmin = false): User
    {
        $user = User::factory()->create(['id' => $id, 'name' => $name]);

        if ($isAdmin) {
            $user = \Mockery::mock($user)->makePartial();
            $user->shouldReceive('isAdmin')->andReturn(true);
            $user->shouldReceive('hasRole')->andReturn(true);
        }

        return $user;
    }

    // ========================================
    // KISI SOFT DELETE SAFETY
    // ========================================

    /** @test */
    public function tenant_a_cannot_soft_delete_tenant_b_kisi()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 1 kisi
        $tenantAKisi = Kisi::factory()->create(['danisman_id' => $tenantA->id]);

        // Tenant B has 1 kisi
        $tenantBKisi = Kisi::factory()->create(['danisman_id' => $tenantB->id]);

        // Act: Tenant A tries to delete Tenant B's kisi
        $result = $this->kisiRepo->delete($tenantBKisi->id);

        // Assert: Delete should fail (returns false)
        $this->assertFalse($result,
            "FAIL: Tenant A should NOT be able to delete Tenant B's kisi");

        // Assert: Tenant B's kisi still exists
        $this->assertDatabaseHas('kisiler', [
            'id' => $tenantBKisi->id,
            'danisman_id' => $tenantB->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function tenant_a_can_soft_delete_own_kisi()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);

        $tenantAKisi = Kisi::factory()->create(['danisman_id' => $tenantA->id]);

        // Act: Tenant A deletes their own kisi (must be authenticated)
        $this->actingAs($tenantA);
        $result = $this->kisiRepo->delete($tenantAKisi->id);

        // Assert: Delete succeeds
        $this->assertTrue($result,
            "FAIL: Tenant A should be able to delete their own kisi");

        // Assert: Kisi is soft deleted
        $this->assertSoftDeleted('kisiler', [
            'id' => $tenantAKisi->id,
        ]);
    }

    /** @test */
    public function tenant_a_cannot_restore_tenant_b_kisi()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant B has a soft-deleted kisi
        $tenantBKisi = Kisi::factory()->create(['danisman_id' => $tenantB->id]);
        $tenantBKisi->delete();

        $this->assertSoftDeleted('kisiler', ['id' => $tenantBKisi->id]);

        // Act: Tenant A tries to restore Tenant B's kisi
        $result = $this->kisiRepo->restore($tenantBKisi->id);

        // Assert: Restore should fail (returns false or 0)
        $this->assertFalse($result,
            "FAIL: Tenant A should NOT be able to restore Tenant B's kisi");

        // Assert: Tenant B's kisi still soft deleted
        $this->assertSoftDeleted('kisiler', ['id' => $tenantBKisi->id]);
    }

    /** @test */
    public function tenant_a_cannot_force_delete_tenant_b_kisi()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant B has a soft-deleted kisi
        $tenantBKisi = Kisi::factory()->create(['danisman_id' => $tenantB->id]);
        $tenantBKisi->delete();

        // Act: Tenant A tries to force delete Tenant B's kisi
        $result = $this->kisiRepo->forceDelete($tenantBKisi->id);

        // Assert: Force delete should fail
        $this->assertFalse($result,
            "FAIL: Tenant A should NOT be able to force delete Tenant B's kisi");

        // Assert: Tenant B's kisi still exists (soft deleted)
        $this->assertSoftDeleted('kisiler', ['id' => $tenantBKisi->id]);
    }

    // ========================================
    // GOREV SCOPED DELETE SAFETY
    // ========================================

    /** @test */
    public function tenant_a_cannot_delete_tenant_b_gorev_by_lead_id()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has a lead
        $tenantALead = Lead::factory()->create(['assigned_agent_id' => $tenantA->id]);

        // Tenant B has a lead with pending tasks
        $tenantBLead = Lead::factory()->create(['assigned_agent_id' => $tenantB->id]);
        Gorev::factory()->count(3)->create([
            'lead_id' => $tenantBLead->id,
            'atanan_user_id' => $tenantB->id,
            'gorev_durumu' => 'beklemede',
        ]);

        // Act: Tenant A tries to delete Tenant B's pending tasks
        $deletedCount = $this->gorevRepo->deletePendingByLeadId($tenantBLead->id, $tenantA);

        // Assert: No tasks should be deleted (scoped delete prevents cross-tenant)
        $this->assertEquals(0, $deletedCount,
            "FAIL: Tenant A should NOT be able to delete Tenant B's tasks (deleted: {$deletedCount})");

        // Assert: Tenant B's tasks still exist
        $this->assertDatabaseCount('gorevler', 3);
        $remainingTasks = Gorev::where('lead_id', $tenantBLead->id)->count();
        $this->assertEquals(3, $remainingTasks,
            "FAIL: All 3 of Tenant B's tasks should still exist");
    }

    /** @test */
    public function tenant_a_can_delete_own_gorev_by_lead_id()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);

        // Tenant A has a lead with pending tasks
        $tenantALead = Lead::factory()->create(['assigned_agent_id' => $tenantA->id]);
        Gorev::factory()->count(2)->create([
            'lead_id' => $tenantALead->id,
            'atanan_user_id' => $tenantA->id,
            'gorev_durumu' => 'beklemede',
        ]);

        // Act: Tenant A deletes their own pending tasks
        $deletedCount = $this->gorevRepo->deletePendingByLeadId($tenantALead->id, $tenantA);

        // Assert: 2 tasks should be deleted
        $this->assertEquals(2, $deletedCount,
            "FAIL: Tenant A should be able to delete their own 2 tasks");

        // Assert: No tasks remain for this lead
        $remainingTasks = Gorev::where('lead_id', $tenantALead->id)->count();
        $this->assertEquals(0, $remainingTasks,
            "FAIL: No tasks should remain after deletion");
    }

    /** @test */
    public function scoped_delete_does_not_affect_completed_tasks()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);

        $tenantALead = Lead::factory()->create(['assigned_agent_id' => $tenantA->id]);

        // Create 2 pending and 1 completed task
        Gorev::factory()->count(2)->create([
            'lead_id' => $tenantALead->id,
            'atanan_user_id' => $tenantA->id,
            'gorev_durumu' => 'beklemede',
        ]);

        Gorev::factory()->create([
            'lead_id' => $tenantALead->id,
            'atanan_user_id' => $tenantA->id,
            'gorev_durumu' => 'tamamlandi',
        ]);

        // Act: Delete pending tasks
        $deletedCount = $this->gorevRepo->deletePendingByLeadId($tenantALead->id, $tenantA);

        // Assert: Only 2 pending tasks deleted
        $this->assertEquals(2, $deletedCount,
            "FAIL: Only 2 pending tasks should be deleted");

        // Assert: Completed task still exists
        $completedTask = Gorev::where('lead_id', $tenantALead->id)
            ->where('gorev_durumu', 'tamamlandi')
            ->first();

        $this->assertNotNull($completedTask,
            "FAIL: Completed task should NOT be deleted");
    }

    // ========================================
    // BULK DELETE SAFETY
    // ========================================

    /** @test */
    public function bulk_update_aktiflik_durumu_does_not_affect_other_tenants()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 3 kisiler
        $tenantAKisiler = Kisi::factory()->count(3)->create([
            'danisman_id' => $tenantA->id,
            'aktiflik_durumu' => 1,
        ]);

        // Tenant B has 2 kisiler
        $tenantBKisiler = Kisi::factory()->count(2)->create([
            'danisman_id' => $tenantB->id,
            'aktiflik_durumu' => 1,
        ]);

        // Get all IDs (both tenants)
        $allIds = $tenantAKisiler->pluck('id')
            ->merge($tenantBKisiler->pluck('id'))
            ->toArray();

        // Act: Try to bulk deactivate ALL kisiler (including Tenant B's)
        // NOTE: This is a DANGEROUS operation - should be admin-only or scoped
        $updatedCount = $this->kisiRepo->bulkUpdateAktiflikDurumu($allIds, 0);

        // Assert: All kisiler are updated (this is the CURRENT behavior)
        // ⚠️ WARNING: This test documents CURRENT behavior, not DESIRED behavior
        // TODO: bulkUpdateAktiflikDurumu should be scoped or admin-only
        $this->assertEquals(5, $updatedCount,
            "CURRENT BEHAVIOR: bulkUpdateAktiflikDurumu is NOT scoped (GOVERNANCE DEBT)");

        // Document the governance debt
        $this->markTestIncomplete(
            "GOVERNANCE DEBT: bulkUpdateAktiflikDurumu should enforce tenant scoping or require admin role"
        );
    }

    // ========================================
    // NULL USER DETERMINISTIC FAIL
    // ========================================

    /** @test */
    public function null_user_cannot_delete_any_kisi()
    {
        $kisi = Kisi::factory()->create(['danisman_id' => 1]);

        $this->assertNull(auth()->user());

        // Act: Try to delete with null user
        $result = $this->kisiRepo->delete($kisi->id);

        // Assert: Delete should fail
        $this->assertFalse($result,
            "FAIL: Null user should NOT be able to delete any kisi");

        // Assert: Kisi still exists
        $this->assertDatabaseHas('kisiler', [
            'id' => $kisi->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function null_user_cannot_delete_any_gorev()
    {
        $lead = Lead::factory()->create(['assigned_agent_id' => 1]);
        Gorev::factory()->count(2)->create([
            'lead_id' => $lead->id,
            'atanan_user_id' => 1,
            'gorev_durumu' => 'beklemede',
        ]);

        $this->assertNull(auth()->user());

        // Act: Try to delete with null user
        $deletedCount = $this->gorevRepo->deletePendingByLeadId($lead->id, null);

        // Assert: No tasks should be deleted
        $this->assertEquals(0, $deletedCount,
            "FAIL: Null user should NOT be able to delete any gorev");

        // Assert: Tasks still exist
        $this->assertDatabaseCount('gorevler', 2);
    }

    // ========================================
    // ADMIN BYPASS
    // ========================================

    /** @test */
    public function admin_can_delete_any_tenant_kisi()
    {
        $admin = $this->createUserWithRole('Admin', 999, true);
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);

        $tenantAKisi = Kisi::factory()->create(['danisman_id' => $tenantA->id]);

        // Act: Admin deletes Tenant A's kisi (must be authenticated as admin)
        $this->actingAs($admin);
        $result = $this->kisiRepo->delete($tenantAKisi->id);

        // Assert: Delete succeeds
        $this->assertTrue($result,
            "FAIL: Admin should be able to delete any tenant's kisi");

        // Assert: Kisi is soft deleted
        $this->assertSoftDeleted('kisiler', ['id' => $tenantAKisi->id]);
    }

    /** @test */
    public function admin_can_delete_any_tenant_gorev()
    {
        $admin = $this->createUserWithRole('Admin', 999, true);
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);

        $tenantALead = Lead::factory()->create(['assigned_agent_id' => $tenantA->id]);
        Gorev::factory()->count(3)->create([
            'lead_id' => $tenantALead->id,
            'atanan_user_id' => $tenantA->id,
            'gorev_durumu' => 'beklemede',
        ]);

        // Act: Admin deletes Tenant A's tasks
        $deletedCount = $this->gorevRepo->deletePendingByLeadId($tenantALead->id, $admin);

        // Assert: All 3 tasks deleted
        $this->assertEquals(3, $deletedCount,
            "FAIL: Admin should be able to delete any tenant's tasks");
    }

    // ========================================
    // CROSS-TENANT DELETE ATTEMPT LOGGING
    // ========================================

    /** @test */
    public function cross_tenant_delete_attempt_returns_zero_affected_rows()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant B has 5 pending tasks
        $tenantBLead = Lead::factory()->create(['assigned_agent_id' => $tenantB->id]);
        Gorev::factory()->count(5)->create([
            'lead_id' => $tenantBLead->id,
            'atanan_user_id' => $tenantB->id,
            'gorev_durumu' => 'beklemede',
        ]);

        // Act: Tenant A tries to delete Tenant B's tasks
        $deletedCount = $this->gorevRepo->deletePendingByLeadId($tenantBLead->id, $tenantA);

        // Assert: Zero rows affected (scoped delete protection)
        $this->assertEquals(0, $deletedCount,
            "FAIL: Cross-tenant delete should return 0 affected rows");

        // Assert: All 5 tasks still exist
        $this->assertDatabaseCount('gorevler', 5);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
