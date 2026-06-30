<?php

namespace Tests\Feature\Reliability;

use App\Models\Ilan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CqrsDriftRecoveryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_detects_and_reconciles_cqrs_read_model_drift()
    {
        // 1. Create a listing
        $ilan = Ilan::factory()->create([
            'baslik' => 'Original Title',
            'fiyat' => 500000,
            'aktiflik_durumu' => 1
        ]);

        // Manually insert into proj_listings to simulate a clean state
        DB::table('proj_listings')->updateOrInsert(
            ['ilan_id' => $ilan->id],
            [
                'baslik' => 'Original Title',
                'yayin_durumu' => 1,
                'fiyat' => 500000,
                'para_birimi' => 1,
                'danisman_id' => $ilan->danisman_id,
                'kategori_id' => $ilan->kategori_id,
                'il_id' => $ilan->il_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Introduce drift directly into the read model (proj_listings)
        DB::table('proj_listings')
            ->where('ilan_id', $ilan->id)
            ->update([
                'baslik' => 'Drifted Title',
                'fiyat' => 200000
            ]);

        $projBefore = DB::table('proj_listings')->where('ilan_id', $ilan->id)->first();
        $this->assertEquals('Drifted Title', $projBefore->baslik);
        $this->assertEquals(200000, $projBefore->fiyat);

        // 3. Run reconciliation command
        $this->artisan('cqrs:reconcile')
            ->expectsOutputToContain('CQRS Reconciliation Complete')
            ->assertExitCode(0);

        // 4. Assert drift is healed
        $projAfter = DB::table('proj_listings')->where('ilan_id', $ilan->id)->first();
        $this->assertEquals('Original Title', $projAfter->baslik);
        $this->assertEquals(500000, $projAfter->fiyat);
    }

    /** @test */
    public function it_rebuilds_projections_from_scratch_with_rebuild_option()
    {
        $ilan = Ilan::factory()->create([
            'baslik' => 'Rebuild Test Ilan',
            'aktiflik_durumu' => 1
        ]);

        // Ensure read model table has the record first
        DB::table('proj_listings')->updateOrInsert(
            ['ilan_id' => $ilan->id],
            [
                'baslik' => 'Rebuild Test Ilan',
                'yayin_durumu' => 1,
                'fiyat' => 100000,
                'para_birimi' => 1,
                'danisman_id' => $ilan->danisman_id,
                'kategori_id' => $ilan->kategori_id,
                'il_id' => $ilan->il_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Run with --rebuild: this truncates the table and rebuilds all
        $this->artisan('cqrs:reconcile', ['--rebuild' => true])
            ->expectsOutputToContain('Rebuilding all listings projections')
            ->assertExitCode(0);

        // Assert record is rebuilt and exists
        $proj = DB::table('proj_listings')->where('ilan_id', $ilan->id)->first();
        $this->assertNotNull($proj);
        $this->assertEquals('Rebuild Test Ilan', $proj->baslik);
    }
}
