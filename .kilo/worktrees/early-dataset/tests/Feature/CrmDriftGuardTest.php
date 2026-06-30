<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Kisi;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CrmDriftGuardTest extends TestCase
{
    // Removing RefreshDatabase to avoid SAVEPOINT trans3 errors during Artisan calls

    /**
     * Test: crm:drift-scan command must pass with 0 exit code.
     */
    public function test_crm_drift_scan_must_pass()
    {
        $exitCode = Artisan::call('crm:drift-scan');
        $this->assertEquals(0, $exitCode, 'CRM Drift Scan failed! Critical naming drift detected.');
    }

    /**
     * Test: Kisi model must not have legacy fields in fillable.
     */
    public function test_kisi_model_must_not_contain_legacy_fields()
    {
        $model = new Kisi();
        $fillable = $model->getFillable();
        
        $this->assertNotContains('kisi_aktiflik_durumu', $fillable, 'Kisi model still contains legacy kisi_aktiflik_durumu in $fillable!');
    }

    /**
     * Test: Active/Passive scopes must work with boolean aktiflik_durumu.
     */
    public function test_active_passive_scopes_runtime_integrity()
    {
        // Create active and passive persons
        $active = Kisi::factory()->create(['aktiflik_durumu' => true]);
        $passive = Kisi::factory()->create(['aktiflik_durumu' => false]);

        // Check Aktif scope
        $activeResults = Kisi::aktif()->get();
        $this->assertTrue($activeResults->contains($active));
        $this->assertFalse($activeResults->contains($passive));

        // Check Pasif scope
        $passiveResults = Kisi::pasif()->get();
        $this->assertTrue($passiveResults->contains($passive));
        $this->assertFalse($passiveResults->contains($active));
    }

    /**
     * Test: Normalization of aktiflik_durumu input (Smoke check).
     * This ensures the service layer hardening works at the model layer too.
     */
    public function test_aktiflik_durumu_cast_is_boolean()
    {
        $kisi = new Kisi();
        $kisi->aktiflik_durumu = 1;
        $this->assertIsBool($kisi->aktiflik_durumu);
        $this->assertTrue($kisi->aktiflik_durumu);
        
        $kisi->aktiflik_durumu = '0';
        $this->assertFalse($kisi->aktiflik_durumu);
    }
}
