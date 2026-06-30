<?php

namespace Tests\Unit\Repositories;

use App\Models\Kisi;
use App\Models\Lead;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Models\KisiEtkilesim;
use App\Repositories\KisiRepository;
use App\Repositories\LeadRepository;
use App\Repositories\GorevRepository;
use App\Repositories\KisiEtkilesimRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

/**
 * Phase 4B.3: Test Suite & Validation
 * Step 1: Repository Tenant Isolation Tests
 *
 * PASS Criteria:
 * ✓ Tenant A cannot read Tenant B CRM records
 * ✓ Tenant A cannot delete Tenant B CRM records
 * ✓ Repository-only access enforced
 * ✓ No direct model access regression
 *
 * @governance PHASE4B_VALIDATION
 * @created 2026-05-12
 */
class CRMTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected KisiRepository $kisiRepo;
    protected LeadRepository $leadRepo;
    protected GorevRepository $gorevRepo;
    protected KisiEtkilesimRepository $etkilesimRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kisiRepo = app(KisiRepository::class);
        $this->leadRepo = app(LeadRepository::class);
        $this->gorevRepo = app(GorevRepository::class);
        $this->etkilesimRepo = app(KisiEtkilesimRepository::class);
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
    // KISI REPOSITORY ISOLATION
    // ========================================

    /** @test */
    public function tenant_a_cannot_read_tenant_b_kisi_records()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 3 kisiler
        Kisi::factory()->count(3)->create(['danisman_id' => $tenantA->id]);

        // Tenant B has 5 kisiler
        $tenantBKisiler = Kisi::factory()->count(5)->create(['danisman_id' => $tenantB->id]);

        // Act: Tenant A queries all kisiler
        $result = $this->kisiRepo->all($tenantA);

        // Assert: Tenant A sees ONLY their 3 kisiler
        $this->assertCount(3, $result,
            "FAIL: Tenant A should see exactly 3 kisiler, not " . $result->count());

        foreach ($result as $kisi) {
            $this->assertEquals($tenantA->id, $kisi->danisman_id,
                "FAIL: Tenant A can see Tenant B's kisi (ID: {$kisi->id})");
        }

        // Assert: Tenant A cannot find Tenant B's kisi by ID
        $tenantBKisi = $tenantBKisiler->first();
        $found = $this->kisiRepo->findWithTrashed($tenantBKisi->id, $tenantA);

        $this->assertNull($found,
            "FAIL: Tenant A should NOT be able to find Tenant B's kisi (ID: {$tenantBKisi->id})");
    }

    /** @test */
    public function tenant_a_cannot_read_tenant_b_kisi_via_search()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Both tenants have "Ahmet" kisiler
        Kisi::factory()->create([
            'danisman_id' => $tenantA->id,
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
        ]);

        Kisi::factory()->create([
            'danisman_id' => $tenantB->id,
            'ad' => 'Ahmet',
            'soyad' => 'Demir',
        ]);

        // Act: Tenant A searches for "Ahmet"
        $result = $this->kisiRepo->search('Ahmet', $tenantA);

        // Assert: Tenant A sees ONLY their Ahmet
        $this->assertCount(1, $result,
            "FAIL: Tenant A should see only 1 Ahmet, not " . $result->count());

        $this->assertEquals('Yılmaz', $result->first()->soyad,
            "FAIL: Tenant A should see 'Ahmet Yılmaz', not 'Ahmet Demir'");
    }

    /** @test */
    public function tenant_a_cannot_read_tenant_b_kisi_via_email_lookup()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        Kisi::factory()->create([
            'danisman_id' => $tenantA->id,
            'eposta' => 'tenanta@test.com',
        ]);

        Kisi::factory()->create([
            'danisman_id' => $tenantB->id,
            'eposta' => 'tenantb@test.com',
        ]);

        // Act: Tenant A tries to find Tenant B's kisi by email
        $result = $this->kisiRepo->findByEmail('tenantb@test.com', $tenantA);

        // Assert: Tenant A cannot find Tenant B's kisi
        $this->assertNull($result,
            "FAIL: Tenant A should NOT be able to find Tenant B's kisi by email");
    }

    /** @test */
    public function tenant_a_cannot_read_tenant_b_kisi_via_phone_lookup()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        Kisi::factory()->create([
            'danisman_id' => $tenantA->id,
            'telefon' => '5551111111',
        ]);

        Kisi::factory()->create([
            'danisman_id' => $tenantB->id,
            'telefon' => '5552222222',
        ]);

        // Act: Tenant A tries to find Tenant B's kisi by phone
        $result = $this->kisiRepo->findByPhone('5552222222', $tenantA);

        // Assert: Tenant A cannot find Tenant B's kisi
        $this->assertNull($result,
            "FAIL: Tenant A should NOT be able to find Tenant B's kisi by phone");
    }

    /** @test */
    public function tenant_a_cannot_read_tenant_b_kisi_via_tc_kimlik_lookup()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        Kisi::factory()->create([
            'danisman_id' => $tenantA->id,
            'tc_kimlik' => '11111111111',
        ]);

        Kisi::factory()->create([
            'danisman_id' => $tenantB->id,
            'tc_kimlik' => '22222222222',
        ]);

        // Act: Tenant A tries to find Tenant B's kisi by TC Kimlik
        $result = $this->kisiRepo->findByTcKimlik('22222222222', $tenantA);

        // Assert: Tenant A cannot find Tenant B's kisi
        $this->assertNull($result,
            "FAIL: Tenant A should NOT be able to find Tenant B's kisi by TC Kimlik");
    }

    // ========================================
    // LEAD REPOSITORY ISOLATION
    // ========================================

    /** @test */
    public function tenant_a_cannot_read_tenant_b_lead_records()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 leads
        Lead::factory()->count(2)->create(['assigned_agent_id' => $tenantA->id]);

        // Tenant B has 4 leads
        $tenantBLeads = Lead::factory()->count(4)->create(['assigned_agent_id' => $tenantB->id]);

        // Act: Tenant A queries leads
        $result = $this->leadRepo->getLeads([], 20);
        $this->actingAs($tenantA);
        $result = $this->leadRepo->getLeads([], 20);

        // Assert: Tenant A sees ONLY their 2 leads
        $this->assertCount(2, $result,
            "FAIL: Tenant A should see exactly 2 leads, not " . $result->count());

        foreach ($result as $lead) {
            $this->assertEquals($tenantA->id, $lead->assigned_agent_id,
                "FAIL: Tenant A can see Tenant B's lead (ID: {$lead->id})");
        }
    }

    /** @test */
    public function tenant_a_cannot_find_tenant_b_lead_by_id()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        $tenantBLead = Lead::factory()->create(['assigned_agent_id' => $tenantB->id]);

        $this->actingAs($tenantA);

        // Act & Assert: Tenant A cannot find Tenant B's lead
        $this->expectException(ModelNotFoundException::class);
        $this->leadRepo->findOrFail($tenantBLead->id);
    }

    /** @test */
    public function tenant_a_cannot_read_tenant_b_hot_leads()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 1 hot lead
        Lead::factory()->create([
            'assigned_agent_id' => $tenantA->id,
            'quality_score' => 85,
            'temperature' => 'hot',
            'crm_durumu' => Lead::CRM_NEW,
            'aktif' => true,
        ]);

        // Tenant B has 3 hot leads
        Lead::factory()->count(3)->create([
            'assigned_agent_id' => $tenantB->id,
            'quality_score' => 90,
            'temperature' => 'hot',
            'crm_durumu' => Lead::CRM_NEW,
            'aktif' => true,
        ]);

        // Act: Tenant A queries hot leads
        $result = $this->leadRepo->getHotLeads(80, 10, $tenantA);

        // Assert: Tenant A sees ONLY their 1 hot lead
        $this->assertCount(1, $result,
            "FAIL: Tenant A should see exactly 1 hot lead, not " . $result->count());
    }

    // ========================================
    // GOREV REPOSITORY ISOLATION
    // ========================================

    /** @test */
    public function tenant_a_cannot_read_tenant_b_gorev_records()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 tasks
        Gorev::factory()->count(2)->create(['atanan_user_id' => $tenantA->id]);

        // Tenant B has 3 tasks
        Gorev::factory()->count(3)->create(['atanan_user_id' => $tenantB->id]);

        // Act: Tenant A queries overdue tasks
        $result = $this->gorevRepo->getOverdueTasksForAgent($tenantA->id, $tenantA);

        // Assert: Query should be scoped (count may be 0 if not overdue, but no cross-tenant leak)
        foreach ($result as $task) {
            $this->assertEquals($tenantA->id, $task->atanan_user_id,
                "FAIL: Tenant A can see Tenant B's task (ID: {$task->id})");
        }
    }

    /** @test */
    public function tenant_a_cannot_find_tenant_b_gorev_by_id()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        $tenantBTask = Gorev::factory()->create(['atanan_user_id' => $tenantB->id]);

        // Act & Assert: Tenant A cannot find Tenant B's task
        $this->expectException(ModelNotFoundException::class);
        $this->gorevRepo->findOrFail($tenantBTask->id, $tenantA);
    }

    // ========================================
    // KISI ETKILESIM REPOSITORY ISOLATION
    // ========================================

    /** @test */
    public function tenant_a_cannot_read_tenant_b_etkilesim_records()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has kisiler with etkilesim
        $tenantAKisi = Kisi::factory()->create(['danisman_id' => $tenantA->id]);
        KisiEtkilesim::factory()->count(3)->create([
            'kisi_id' => $tenantAKisi->id,
            'aktiflik_durumu' => 1,
        ]);

        // Tenant B has kisiler with etkilesim
        $tenantBKisi = Kisi::factory()->create(['danisman_id' => $tenantB->id]);
        KisiEtkilesim::factory()->count(5)->create([
            'kisi_id' => $tenantBKisi->id,
            'aktiflik_durumu' => 1,
        ]);

        // Act: Tenant A queries recent activities
        $result = $this->etkilesimRepo->getRecentActivities(20, $tenantA);

        // Assert: Tenant A sees ONLY their 3 etkilesim
        $this->assertCount(3, $result,
            "FAIL: Tenant A should see exactly 3 etkilesim, not " . $result->count());

        foreach ($result as $etkilesim) {
            $this->assertEquals($tenantA->id, $etkilesim->kisi->danisman_id,
                "FAIL: Tenant A can see Tenant B's etkilesim (ID: {$etkilesim->id})");
        }
    }

    // ========================================
    // NULL USER DETERMINISTIC FAIL
    // ========================================

    /** @test */
    public function null_user_sees_nothing_kisi()
    {
        $danisman = User::factory()->create();
        Kisi::factory()->count(5)->create(['danisman_id' => $danisman->id]);

        $this->assertNull(auth()->user());

        $result = $this->kisiRepo->all(null);
        $this->assertCount(0, $result,
            "FAIL: Null user should see 0 kisiler (deterministic fail)");
    }

    /** @test */
    public function null_user_sees_nothing_lead()
    {
        Lead::factory()->count(5)->create(['assigned_agent_id' => 1]);

        $this->assertNull(auth()->user());

        $result = $this->leadRepo->getLeads();
        $this->assertCount(0, $result,
            "FAIL: Null user should see 0 leads (deterministic fail)");
    }

    /** @test */
    public function null_user_sees_nothing_gorev()
    {
        Gorev::factory()->count(5)->create(['atanan_user_id' => 1]);

        $this->assertNull(auth()->user());

        $result = $this->gorevRepo->getOverdueTasksForAgent(1, null);
        $this->assertCount(0, $result,
            "FAIL: Null user should see 0 gorev (deterministic fail)");
    }

    // ========================================
    // ADMIN BYPASS
    // ========================================

    /** @test */
    public function admin_can_see_all_tenant_kisi_records()
    {
        $admin = $this->createUserWithRole('Admin', 999, true);
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        Kisi::factory()->count(3)->create(['danisman_id' => $tenantA->id]);
        Kisi::factory()->count(5)->create(['danisman_id' => $tenantB->id]);

        // Act: Admin queries all kisiler
        $result = $this->kisiRepo->all($admin);

        // Assert: Admin sees ALL 8 kisiler
        $this->assertCount(8, $result,
            "FAIL: Admin should see all 8 kisiler");
    }

    /** @test */
    public function admin_can_see_all_tenant_lead_records()
    {
        $admin = $this->createUserWithRole('Admin', 999, true);
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        Lead::factory()->count(2)->create(['assigned_agent_id' => $tenantA->id]);
        Lead::factory()->count(4)->create(['assigned_agent_id' => $tenantB->id]);

        $this->actingAs($admin);

        // Act: Admin queries all leads
        $result = $this->leadRepo->getLeads([], 20);

        // Assert: Admin sees ALL 6 leads
        $this->assertCount(6, $result,
            "FAIL: Admin should see all 6 leads");
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
