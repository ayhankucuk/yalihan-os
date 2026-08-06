<?php

namespace Tests\Feature\CQRS;

use App\Jobs\SyncListingProjectionJob;
use App\Models\Ilan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ADR-001 Phase 1C — proj_listings ownership alignment regression tests.
 *
 * Validates that SyncListingProjectionJob writes `danisman_id` cleanly
 * (no user_id fallback, no stale sahip_id key) per the canonical ownership model.
 *
 * Schema contract:
 *   proj_listings.danisman_id → operasyonel danışman sahipliği (users.id)
 *   proj_listings has NO sahip_id column (that lives on ilanlar_read_model)
 *
 * Note: Job is invoked via handle() directly to surface exceptions in test context.
 * dispatchSync() swallows exceptions via failed() handler.
 */
class SyncListingProjectionOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private User $danisman;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->danisman = User::factory()->create();
        $this->owner    = User::factory()->create();
    }

    /**
     * Run job synchronously and surface any exceptions.
     */
    private function runJob(int $ilanId): void
    {
        (new SyncListingProjectionJob($ilanId))->handle();
    }

    /** @test */
    public function sync_job_writes_danisman_id_when_ilan_has_danisman(): void
    {
        $ilan = Ilan::factory()->create([
            'danisman_id' => $this->danisman->id,
            'user_id'     => $this->owner->id,
        ]);

        $this->runJob($ilan->id);

        $proj = DB::table('proj_listings')->where('ilan_id', $ilan->id)->first();

        $this->assertNotNull($proj, 'proj_listings kaydı oluşturulmalı');
        $this->assertEquals(
            $this->danisman->id,
            $proj->danisman_id,
            'ADR-001: danisman_id danışmanın id\'si olmalı'
        );
    }

    /** @test */
    public function sync_job_writes_null_danisman_id_when_ilan_has_no_danisman(): void
    {
        // Owner Portal ilanı: sadece user_id var, danisman_id yok.
        // ADR-001 Phase 1C: user_id fallback kaldırıldı — danisman_id null kalmalı.
        $ilan = Ilan::factory()->create([
            'danisman_id' => null,
            'user_id'     => $this->owner->id,
        ]);

        $this->runJob($ilan->id);

        $proj = DB::table('proj_listings')->where('ilan_id', $ilan->id)->first();

        $this->assertNotNull($proj, 'proj_listings kaydı oluşturulmalı');
        $this->assertNull(
            $proj->danisman_id,
            'ADR-001 Phase 1C: danisman_id null olmalı, user_id ile doldurulmamalı'
        );
    }

    /** @test */
    public function sync_job_does_not_write_sahip_id_column(): void
    {
        // proj_listings şemasında sahip_id kolonu yok.
        // SyncListingProjectionJob eski kod 'sahip_id' key'i ile yazıyordu (silent fail).
        // Bu test, job'ın herhangi bir bilinmeyen kolona yazmaya çalışmadığını doğrular.
        $ilan = Ilan::factory()->create([
            'danisman_id' => $this->danisman->id,
            'user_id'     => $this->owner->id,
        ]);

        $this->runJob($ilan->id);

        $proj = DB::table('proj_listings')->where('ilan_id', $ilan->id)->first();
        $projArray = (array) $proj;

        $this->assertNotNull($proj, 'proj_listings kaydı oluşturulmalı');
        $this->assertArrayNotHasKey(
            'sahip_id',
            $projArray,
            'proj_listings kaydında sahip_id kolonu bulunmamalı'
        );
    }

    /** @test */
    public function sync_job_deletes_projection_when_ilan_is_soft_deleted(): void
    {
        $ilan = Ilan::factory()->create([
            'danisman_id' => $this->danisman->id,
            'user_id'     => $this->owner->id,
        ]);

        // Önce projeksiyon oluştur
        $this->runJob($ilan->id);
        $this->assertNotNull(
            DB::table('proj_listings')->where('ilan_id', $ilan->id)->first(),
            'İlk çalışmada proj_listings kaydı oluşturulmalı'
        );

        // İlanı sil
        $ilan->delete();

        // Job tekrar çalışınca kaydı silmeli
        $this->runJob($ilan->id);

        $this->assertNull(
            DB::table('proj_listings')->where('ilan_id', $ilan->id)->first(),
            'Silinen ilan proj_listings\'ten kaldırılmalı'
        );
    }
}
