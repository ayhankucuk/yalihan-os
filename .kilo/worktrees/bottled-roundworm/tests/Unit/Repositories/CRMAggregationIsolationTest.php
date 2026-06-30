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
use Tests\TestCase;

/**
 * Phase 4B.3: Test Suite & Validation
 * Step 3: Cross-Tenant Aggregation Prevention Tests
 *
 * PASS Criteria:
 * ✓ Tenant A aggregates exclude Tenant B data
 * ✓ No unscoped aggregate query
 * ✓ Statistics respect tenant boundaries
 * ✓ Count operations are scoped
 *
 * @governance PHASE4B_VALIDATION
 * @governance AGGREGATION_BOUNDARY
 * @created 2026-05-12
 */
class CRMAggregationIsolationTest extends TestCase
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
    // KISI AGGREGATION ISOLATION
    // ========================================

    /** @test */
    public function kisi_stats_exclude_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 5 aktif kisiler
        Kisi::factory()->count(5)->create([
            'danisman_id' => $tenantA->id,
            'aktiflik_durumu' => 1,
        ]);

        // Tenant B has 10 aktif kisiler
        Kisi::factory()->count(10)->create([
            'danisman_id' => $tenantB->id,
            'aktiflik_durumu' => 1,
        ]);

        // Act: Tenant A queries stats
        $stats = $this->kisiRepo->getStats($tenantA);

        // Assert: Tenant A sees ONLY their 5 kisiler
        $this->assertEquals(5, $stats['total'],
            "FAIL: Tenant A stats should show 5 total, not {$stats['total']}");

        $this->assertEquals(5, $stats['aktif'],
            "FAIL: Tenant A stats should show 5 aktif, not {$stats['aktif']}");

        // Act: Tenant B queries stats
        $statsB = $this->kisiRepo->getStats($tenantB);

        // Assert: Tenant B sees ONLY their 10 kisiler
        $this->assertEquals(10, $statsB['total'],
            "FAIL: Tenant B stats should show 10 total, not {$statsB['total']}");
    }

    /** @test */
    public function kisi_dashboard_stats_exclude_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 3 customers
        Kisi::factory()->count(3)->create([
            'danisman_id' => $tenantA->id,
            'aktiflik_durumu' => 1,
        ]);

        // Tenant B has 7 customers
        Kisi::factory()->count(7)->create([
            'danisman_id' => $tenantB->id,
            'aktiflik_durumu' => 1,
        ]);

        // Act: Tenant A queries dashboard stats
        $stats = $this->kisiRepo->getDashboardStats($tenantA);

        // Assert: Tenant A sees ONLY their 3 customers
        $this->assertEquals(3, $stats['total_customers'],
            "FAIL: Tenant A should see 3 total customers, not {$stats['total_customers']}");

        $this->assertEquals(3, $stats['active_customers'],
            "FAIL: Tenant A should see 3 active customers, not {$stats['active_customers']}");
    }

    /** @test */
    public function kisi_customer_segments_exclude_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 alici, 1 satici
        Kisi::factory()->count(2)->create([
            'danisman_id' => $tenantA->id,
            'kisi_tipi' => 'alici',
            'aktiflik_durumu' => 1,
        ]);
        Kisi::factory()->create([
            'danisman_id' => $tenantA->id,
            'kisi_tipi' => 'satici',
            'aktiflik_durumu' => 1,
        ]);

        // Tenant B has 5 alici, 3 satici
        Kisi::factory()->count(5)->create([
            'danisman_id' => $tenantB->id,
            'kisi_tipi' => 'alici',
            'aktiflik_durumu' => 1,
        ]);
        Kisi::factory()->count(3)->create([
            'danisman_id' => $tenantB->id,
            'kisi_tipi' => 'satici',
            'aktiflik_durumu' => 1,
        ]);

        // Act: Tenant A queries customer segments
        $segments = $this->kisiRepo->getCustomerSegments($tenantA);

        // Assert: Tenant A sees ONLY their segments
        $this->assertEquals(2, $segments->get('alici'),
            "FAIL: Tenant A should see 2 alici, not {$segments->get('alici')}");

        $this->assertEquals(1, $segments->get('satici'),
            "FAIL: Tenant A should see 1 satici, not {$segments->get('satici')}");

        // Assert: Tenant B's data is NOT included
        $totalSegments = $segments->sum();
        $this->assertEquals(3, $totalSegments,
            "FAIL: Tenant A total segments should be 3, not {$totalSegments}");
    }

    /** @test */
    public function kisi_pipeline_stages_exclude_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 potansiyel, 1 sicak
        Kisi::factory()->count(2)->create([
            'danisman_id' => $tenantA->id,
            'crm_surec_asamasi' => 'potansiyel',
            'aktiflik_durumu' => 1,
        ]);
        Kisi::factory()->create([
            'danisman_id' => $tenantA->id,
            'crm_surec_asamasi' => 'sicak',
            'aktiflik_durumu' => 1,
        ]);

        // Tenant B has 5 potansiyel, 3 sicak
        Kisi::factory()->count(5)->create([
            'danisman_id' => $tenantB->id,
            'crm_surec_asamasi' => 'potansiyel',
            'aktiflik_durumu' => 1,
        ]);
        Kisi::factory()->count(3)->create([
            'danisman_id' => $tenantB->id,
            'crm_surec_asamasi' => 'sicak',
            'aktiflik_durumu' => 1,
        ]);

        // Act: Tenant A queries pipeline stages
        $stages = $this->kisiRepo->getPipelineStages($tenantA);

        // Assert: Tenant A sees ONLY their pipeline
        $this->assertCount(2, $stages[1],
            "FAIL: Tenant A should see 2 potansiyel, not " . count($stages[1]));

        $this->assertCount(1, $stages[4],
            "FAIL: Tenant A should see 1 sicak, not " . count($stages[4]));
    }

    /** @test */
    public function kisi_lost_pipeline_count_excludes_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 pasif kisiler (recent)
        Kisi::factory()->count(2)->create([
            'danisman_id' => $tenantA->id,
            'crm_surec_asamasi' => 'pasif',
            'updated_at' => now()->subDays(15),
        ]);

        // Tenant B has 5 pasif kisiler (recent)
        Kisi::factory()->count(5)->create([
            'danisman_id' => $tenantB->id,
            'crm_surec_asamasi' => 'pasif',
            'updated_at' => now()->subDays(15),
        ]);

        // Act: Tenant A queries lost pipeline count
        $count = $this->kisiRepo->getLostPipelineCount($tenantA);

        // Assert: Tenant A sees ONLY their 2 lost kisiler
        $this->assertEquals(2, $count,
            "FAIL: Tenant A should see 2 lost kisiler, not {$count}");
    }

    /** @test */
    public function kisi_lead_source_analytics_exclude_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 from 'website', 1 from 'referral'
        Kisi::factory()->count(2)->create([
            'danisman_id' => $tenantA->id,
            'kaynak' => 'website',
            'aktiflik_durumu' => 1,
            'skor' => 75,
        ]);
        Kisi::factory()->create([
            'danisman_id' => $tenantA->id,
            'kaynak' => 'referral',
            'aktiflik_durumu' => 1,
            'skor' => 85,
        ]);

        // Tenant B has 5 from 'website', 3 from 'referral'
        Kisi::factory()->count(5)->create([
            'danisman_id' => $tenantB->id,
            'kaynak' => 'website',
            'aktiflik_durumu' => 1,
            'skor' => 60,
        ]);
        Kisi::factory()->count(3)->create([
            'danisman_id' => $tenantB->id,
            'kaynak' => 'referral',
            'aktiflik_durumu' => 1,
            'skor' => 70,
        ]);

        // Act: Tenant A queries lead source analytics
        $analytics = $this->kisiRepo->getLeadSourceAnalytics($tenantA);

        // Assert: Tenant A sees ONLY their data
        $websiteData = $analytics->firstWhere('lead_source', 'website');
        $this->assertEquals(2, $websiteData->total,
            "FAIL: Tenant A should see 2 website leads, not {$websiteData->total}");

        $referralData = $analytics->firstWhere('lead_source', 'referral');
        $this->assertEquals(1, $referralData->total,
            "FAIL: Tenant A should see 1 referral lead, not {$referralData->total}");
    }

    /** @test */
    public function kisi_duplicate_emails_exclude_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has duplicate email 'duplicate@test.com'
        Kisi::factory()->count(2)->create([
            'danisman_id' => $tenantA->id,
            'eposta' => 'duplicate@test.com',
        ]);

        // Tenant B also has duplicate email 'duplicate@test.com'
        Kisi::factory()->count(2)->create([
            'danisman_id' => $tenantB->id,
            'eposta' => 'duplicate@test.com',
        ]);

        // Act: Tenant A queries duplicate emails
        $duplicates = $this->kisiRepo->getDuplicateEmails($tenantA, 10);

        // Assert: Tenant A sees their duplicate (scoped to their data)
        // NOTE: This should return 1 duplicate email within Tenant A's scope
        $this->assertContains('duplicate@test.com', $duplicates,
            "FAIL: Tenant A should see their duplicate email");

        // The duplicate detection is scoped, so it only finds duplicates within tenant's data
        $this->assertCount(1, $duplicates,
            "FAIL: Should find 1 duplicate email within Tenant A's scope");
    }

    // ========================================
    // LEAD AGGREGATION ISOLATION
    // ========================================

    /** @test */
    public function lead_summary_stats_exclude_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 new, 1 won
        Lead::factory()->count(2)->create([
            'assigned_agent_id' => $tenantA->id,
            'crm_durumu' => Lead::CRM_NEW,
        ]);
        Lead::factory()->create([
            'assigned_agent_id' => $tenantA->id,
            'crm_durumu' => Lead::CRM_WON,
        ]);

        // Tenant B has 5 new, 2 won
        Lead::factory()->count(5)->create([
            'assigned_agent_id' => $tenantB->id,
            'crm_durumu' => Lead::CRM_NEW,
        ]);
        Lead::factory()->count(2)->create([
            'assigned_agent_id' => $tenantB->id,
            'crm_durumu' => Lead::CRM_WON,
        ]);

        $this->actingAs($tenantA);

        // Act: Tenant A queries summary stats
        $stats = $this->leadRepo->getSummaryStats();

        // Assert: Tenant A sees ONLY their 3 leads
        $this->assertEquals(3, $stats['toplam'],
            "FAIL: Tenant A should see 3 total leads, not {$stats['toplam']}");

        $this->assertEquals(2, $stats['yeni'],
            "FAIL: Tenant A should see 2 new leads, not {$stats['yeni']}");

        $this->assertEquals(1, $stats['kazanildi'],
            "FAIL: Tenant A should see 1 won lead, not {$stats['kazanildi']}");
    }

    /** @test */
    public function lead_hot_leads_aggregation_excludes_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 hot leads
        Lead::factory()->count(2)->create([
            'assigned_agent_id' => $tenantA->id,
            'quality_score' => 85,
            'temperature' => 'hot',
            'crm_durumu' => Lead::CRM_NEW,
            'aktif' => true,
        ]);

        // Tenant B has 5 hot leads
        Lead::factory()->count(5)->create([
            'assigned_agent_id' => $tenantB->id,
            'quality_score' => 90,
            'temperature' => 'hot',
            'crm_durumu' => Lead::CRM_NEW,
            'aktif' => true,
        ]);

        // Act: Tenant A queries hot leads
        $hotLeads = $this->leadRepo->getHotLeads(80, 10, $tenantA);

        // Assert: Tenant A sees ONLY their 2 hot leads
        $this->assertCount(2, $hotLeads,
            "FAIL: Tenant A should see 2 hot leads, not " . $hotLeads->count());
    }

    /** @test */
    public function lead_warm_leads_aggregation_excludes_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 3 warm leads
        Lead::factory()->count(3)->create([
            'assigned_agent_id' => $tenantA->id,
            'quality_score' => 65,
            'temperature' => 'warm',
            'crm_durumu' => Lead::CRM_NEW,
            'aktif' => true,
        ]);

        // Tenant B has 7 warm leads
        Lead::factory()->count(7)->create([
            'assigned_agent_id' => $tenantB->id,
            'quality_score' => 70,
            'temperature' => 'warm',
            'crm_durumu' => Lead::CRM_NEW,
            'aktif' => true,
        ]);

        // Act: Tenant A queries warm leads
        $warmLeads = $this->leadRepo->getWarmLeads(50, 80, 20, $tenantA);

        // Assert: Tenant A sees ONLY their 3 warm leads
        $this->assertCount(3, $warmLeads,
            "FAIL: Tenant A should see 3 warm leads, not " . $warmLeads->count());
    }

    // ========================================
    // GOREV AGGREGATION ISOLATION
    // ========================================

    /** @test */
    public function gorev_statistics_exclude_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 pending, 1 completed
        Gorev::factory()->count(2)->create([
            'atanan_user_id' => $tenantA->id,
            'gorev_durumu' => 'beklemede',
        ]);
        Gorev::factory()->create([
            'atanan_user_id' => $tenantA->id,
            'gorev_durumu' => 'tamamlandi',
        ]);

        // Tenant B has 5 pending, 3 completed
        Gorev::factory()->count(5)->create([
            'atanan_user_id' => $tenantB->id,
            'gorev_durumu' => 'beklemede',
        ]);
        Gorev::factory()->count(3)->create([
            'atanan_user_id' => $tenantB->id,
            'gorev_durumu' => 'tamamlandi',
        ]);

        // Act: Tenant A queries statistics
        $stats = $this->gorevRepo->getStatistics($tenantA);

        // Assert: Tenant A sees ONLY their tasks
        $this->assertEquals(2, $stats['pending_tasks'],
            "FAIL: Tenant A should see 2 pending tasks, not {$stats['pending_tasks']}");

        $this->assertEquals(1, $stats['completed_tasks'],
            "FAIL: Tenant A should see 1 completed task, not {$stats['completed_tasks']}");
    }

    // ========================================
    // KISI ETKILESIM AGGREGATION ISOLATION
    // ========================================

    /** @test */
    public function etkilesim_pending_followups_count_excludes_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 kisiler with 3 etkilesim
        $tenantAKisi = Kisi::factory()->create(['danisman_id' => $tenantA->id]);
        KisiEtkilesim::factory()->count(3)->create([
            'kisi_id' => $tenantAKisi->id,
            'aktiflik_durumu' => 1,
            'created_at' => now()->subDays(10),
        ]);

        // Tenant B has 5 kisiler with 8 etkilesim
        $tenantBKisi = Kisi::factory()->create(['danisman_id' => $tenantB->id]);
        KisiEtkilesim::factory()->count(8)->create([
            'kisi_id' => $tenantBKisi->id,
            'aktiflik_durumu' => 1,
            'created_at' => now()->subDays(10),
        ]);

        // Act: Tenant A queries pending followups count
        $count = $this->etkilesimRepo->getPendingFollowupsCount(30, $tenantA);

        // Assert: Tenant A sees ONLY their 3 etkilesim
        $this->assertEquals(3, $count,
            "FAIL: Tenant A should see 3 pending followups, not {$count}");
    }

    /** @test */
    public function etkilesim_today_activities_count_excludes_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 2 today activities
        $tenantAKisi = Kisi::factory()->create(['danisman_id' => $tenantA->id]);
        KisiEtkilesim::factory()->count(2)->create([
            'kisi_id' => $tenantAKisi->id,
            'created_at' => today(),
        ]);

        // Tenant B has 5 today activities
        $tenantBKisi = Kisi::factory()->create(['danisman_id' => $tenantB->id]);
        KisiEtkilesim::factory()->count(5)->create([
            'kisi_id' => $tenantBKisi->id,
            'created_at' => today(),
        ]);

        // Act: Tenant A queries today activities count
        $count = $this->etkilesimRepo->getTodayActivitiesCount($tenantA);

        // Assert: Tenant A sees ONLY their 2 activities
        $this->assertEquals(2, $count,
            "FAIL: Tenant A should see 2 today activities, not {$count}");
    }

    /** @test */
    public function etkilesim_high_priority_followups_count_excludes_other_tenant_data()
    {
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 1 high priority followup
        $tenantAKisi = Kisi::factory()->create(['danisman_id' => $tenantA->id]);
        KisiEtkilesim::factory()->create([
            'kisi_id' => $tenantAKisi->id,
            'aktiflik_durumu' => 1,
            'created_at' => now()->subDays(10),
        ]);

        // Tenant B has 4 high priority followups
        $tenantBKisi = Kisi::factory()->create(['danisman_id' => $tenantB->id]);
        KisiEtkilesim::factory()->count(4)->create([
            'kisi_id' => $tenantBKisi->id,
            'aktiflik_durumu' => 1,
            'created_at' => now()->subDays(10),
        ]);

        // Act: Tenant A queries high priority followups count
        $count = $this->etkilesimRepo->getHighPriorityFollowupsCount(7, $tenantA);

        // Assert: Tenant A sees ONLY their 1 followup
        $this->assertEquals(1, $count,
            "FAIL: Tenant A should see 1 high priority followup, not {$count}");
    }

    // ========================================
    // ADMIN SEES ALL AGGREGATIONS
    // ========================================

    /** @test */
    public function admin_aggregations_include_all_tenant_data()
    {
        $admin = $this->createUserWithRole('Admin', 999, true);
        $tenantA = $this->createUserWithRole('Tenant A', 1, false);
        $tenantB = $this->createUserWithRole('Tenant B', 2, false);

        // Tenant A has 3 kisiler
        Kisi::factory()->count(3)->create([
            'danisman_id' => $tenantA->id,
            'aktiflik_durumu' => 1,
        ]);

        // Tenant B has 5 kisiler
        Kisi::factory()->count(5)->create([
            'danisman_id' => $tenantB->id,
            'aktiflik_durumu' => 1,
        ]);

        // Act: Admin queries stats
        $stats = $this->kisiRepo->getStats($admin);

        // Assert: Admin sees ALL 8 kisiler
        $this->assertEquals(8, $stats['total'],
            "FAIL: Admin should see 8 total kisiler, not {$stats['total']}");
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
