<?php

namespace Tests\Unit\CDA;

use App\Models\SaaS\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CDA-007 Critical Reproduction Tests
 * 
 * These tests prove the actual write/read mismatch scenario.
 */
class CDA007CriticalReproductionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function proof_aktiflik_durumu_is_now_in_fillable(): void
    {
        $fillable = (new Tenant())->getFillable();
        // FIXED: aktiflik_durumu is now in fillable (CDA-007 remediation)
        $this->assertContains('aktiflik_durumu', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('durum', $fillable);
    }

    /** @test */
    public function proof_aktiflik_durumu_can_be_mass_assigned(): void
    {
        // FIXED: SaaS\Tenant can now write to 'aktiflik_durumu' via mass assignment
        $fillable = (new Tenant())->getFillable();
        
        // Model CAN write to 'status'
        $this->assertContains('status', $fillable);
        
        // Model CAN now write to 'aktiflik_durumu' via mass assignment
        $this->assertContains('aktiflik_durumu', $fillable);
        
        // Model CAN write to 'durum'
        $this->assertContains('durum', $fillable);
        
        // CONSEQUENCE: Tenant::create() with aktiflik_durumu now works
        // HuntOpportunitiesCommand primary filter will find the tenant
    }

    /** @test */
    public function proof_hunt_opportunities_falls_back_to_status(): void
    {
        // Create tenant with ONLY status='active' (no aktiflik_durumu set by model)
        $tenant = Tenant::create([
            'name' => 'Status Only',
            'domain' => 'status-only.test',
            'status' => 'active',
        ]);

        // Verify current DB state
        $dbStatus = DB::table('tenants')->where('id', $tenant->id)->value('status');
        $dbAktiflik = DB::table('tenants')->where('id', $tenant->id)->value('aktiflik_durumu');

        // aktflik_durumu has DB default 'active', not NULL
        $this->assertEquals('active', $dbStatus);
        $this->assertEquals('active', $dbAktiflik); // Default value from migration

        // Simulate HuntOpportunities query with ALL conditions
        $found = Tenant::where(function ($q) {
            $q->where('aktiflik_durumu', 1)
              ->orWhere('aktiflik_durumu', 'active')
              ->orWhere('aktiflik_durumu', 'aktif')
              ->orWhere('status', 'active');
        })->where('id', $tenant->id)->exists();

        // LUCKY: Both have default 'active' so query finds it
        $this->assertTrue($found);
        
        $tenant->forceDelete();
    }

    /** @test */
    public function proof_migration_default_masks_write_drift(): void
    {
        // This test shows why the bug is HIDDEN:
        // Migration sets aktiflik_durumu default='active'
        // So even without model writing aktiflik_durumu, it has 'active' value
        
        $columns = DB::getSchemaBuilder()->getColumnListing('tenants');
        $this->assertContains('aktiflik_durumu', $columns);
        
        // The default value masks the model contract drift
        // If someone changes aktiflik_durumu default to NULL or 'inactive',
        // the mismatch would become visible
    }

    /** @test */
    public function proof_model_fillable_gap_is_now_fixed(): void
    {
        $fillable = (new Tenant())->getFillable();
        
        // FIXED: aktiflik_durumu is now in fillable
        $this->assertContains('aktiflik_durumu', $fillable);
        $this->assertContains('durum', $fillable);
        $this->assertContains('status', $fillable);
        
        // CONCLUSION: SaaS\Tenant can now write all active-state columns
        // including the Context7 canonical aktiflik_durumu
    }

    /** @test */
    public function proof_hunt_query_is_redundant_and_fragile(): void
    {
        // HuntOpportunitiesCommand has 4 OR conditions for finding active tenants:
        // 1. aktiflik_durumu = 1 (int)
        // 2. aktiflik_durumu = 'active' (string)
        // 3. aktiflik_durumu = 'aktif' (Turkish)
        // 4. status = 'active' (legacy fallback)
        
        // This is a LUCKY workaround, not a proper fix
        // If any of these conditions fails, the system breaks
        
        $query = Tenant::where(function ($q) {
            $q->where('aktiflik_durumu', 1)
              ->orWhere('aktiflik_durumu', 'active')
              ->orWhere('aktiflik_durumu', 'aktif')
              ->orWhere('status', 'active');
        });
        
        $sql = $query->toSql();
        
        // Verify query contains both columns
        $this->assertStringContainsString('aktiflik_durumu', $sql);
        $this->assertStringContainsString('status', $sql);
    }

    /** @test */
    public function summary_write_read_authority_now_aligned(): void
    {
        // CANONICAL WRITE AUTHORITY: SaaS\Tenant model
        $writeModel = new Tenant();
        $writeFields = $writeModel->getFillable();
        
        // CANONICAL READ AUTHORITY: HuntOpportunitiesCommand
        // Reads aktiflik_durumu as primary filter
        
        // FIXED: Model can now write aktiflik_durumu
        $this->assertContains('aktiflik_durumu', $writeFields);
        $this->assertContains('status', $writeFields);
        
        // Write-Read alignment: both columns are now writable by model
    }
    /** @test */
    public function proof_tenant_create_with_aktiflik_durumu_writes_to_db(): void
    {
        // NEW TEST (2026-10-06): End-to-end proof of CDA-007 fix
        // Tenant::create() with aktiflik_durumu should write to DB
        
        $tenant = Tenant::create([
            'name' => 'Aktiflik Test',
            'domain' => 'aktiflik-test.test',
            'status' => 'active',
            'durum' => 'active',
            'aktiflik_durumu' => 'active',
        ]);
        
        // Verify DB has aktiflik_durumu set
        $dbValue = DB::table('tenants')->where('id', $tenant->id)->value('aktiflik_durumu');
        $this->assertEquals('active', $dbValue);
        
        // Verify other columns
        $dbStatus = DB::table('tenants')->where('id', $tenant->id)->value('status');
        $this->assertEquals('active', $dbStatus);
        
        $tenant->forceDelete();
    }
    
    /** @test */
    public function proof_hunt_opportunities_finds_tenant_with_explicit_aktiflik_durumu(): void
    {
        // NEW TEST (2026-10-06): HuntOpportunities can find tenant with explicit aktiflik_durumu
        $tenant = Tenant::create([
            'name' => 'Hunt Test',
            'domain' => 'hunt-test.test',
            'status' => 'active',
            'durum' => 'active',
            'aktiflik_durumu' => 'active',
        ]);
        
        // Simulate HuntOpportunities primary query (no fallback needed)
        $found = Tenant::where(function ($q) {
            $q->where('aktiflik_durumu', 1)
              ->orWhere('aktiflik_durumu', 'active')
              ->orWhere('aktiflik_durumu', 'aktif');
        })->where('id', $tenant->id)->exists();
        
        // FIXED: Now found via aktiflik_durumu primary filter (no legacy fallback needed)
        $this->assertTrue($found);
        
        $tenant->forceDelete();
    }
}
