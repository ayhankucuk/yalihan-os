<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\IlanReservation;
use App\Models\User;
use App\Services\Calendar\IlanReservationService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Context7 TDD: IlanReservationService canonical field compliance.
 *
 * Problem: Service used 'status' key instead of canonical 'islem_statusu'.
 * Result: create() silently discarded status, idempotency check always false.
 *
 * Requires: ilan_reservations table (run migration before enabling).
 *
 * @group calendar
 * @group context7-compliance
 * @group skip-until-migration-complete
 */
class IlanReservationIslemStatusuTest extends TestCase
{

    private IlanReservationService $service;
    private Ilan $ilan;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(IlanReservationService::class);
        $this->user    = User::factory()->create();

        $parent   = IlanKategori::factory()->create(['parent_id' => null, 'seviye' => 0]);
        $kategori = IlanKategori::factory()->create(['parent_id' => $parent->id, 'seviye' => 1]);
        $this->ilan = Ilan::factory()->create(['ana_kategori_id' => $kategori->id]);
    }

    /** @test */
    public function create_stores_islem_statusu_as_active(): void
    {
        $from = Carbon::tomorrow()->setTime(10, 0);
        $to   = Carbon::tomorrow()->setTime(12, 0);

        $reservation = $this->service->create($this->ilan->id, [
            'starts_at' => $from->toDateTimeString(),
            'ends_at'   => $to->toDateTimeString(),
        ], $this->user->id);

        $this->assertDatabaseHas('ilan_reservations', [
            'id'            => $reservation->id,
            'islem_statusu' => 'active',
        ]);

        // Forbidden field must NOT be persisted
        $raw = \DB::table('ilan_reservations')->where('id', $reservation->id)->first();
        $this->assertObjectNotHasProperty('status', $raw,
            'Forbidden "status" column must not exist on ilan_reservations table.'
        );
    }

    /** @test */
    public function cancel_sets_islem_statusu_to_cancelled(): void
    {
        $reservation = IlanReservation::create([
            'ilan_id'       => $this->ilan->id,
            'islem_statusu' => 'active',
            'starts_at'     => Carbon::tomorrow()->setTime(10, 0),
            'ends_at'       => Carbon::tomorrow()->setTime(12, 0),
            'source'        => 'test',
        ]);

        $this->service->cancel($reservation, $this->user->id);

        $this->assertDatabaseHas('ilan_reservations', [
            'id'            => $reservation->id,
            'islem_statusu' => 'cancelled',
        ]);
    }

    /** @test */
    public function confirm_sets_islem_statusu_to_confirmed(): void
    {
        $reservation = IlanReservation::create([
            'ilan_id'       => $this->ilan->id,
            'islem_statusu' => 'active',
            'starts_at'     => Carbon::tomorrow()->setTime(10, 0),
            'ends_at'       => Carbon::tomorrow()->setTime(12, 0),
            'source'        => 'test',
        ]);

        $result = $this->service->confirm($reservation->id, $this->user->id);

        $this->assertDatabaseHas('ilan_reservations', [
            'id'            => $reservation->id,
            'islem_statusu' => 'confirmed',
        ]);
        $this->assertSame('confirmed', $result->islem_statusu);
    }

    /** @test */
    public function confirm_is_idempotent_when_already_confirmed(): void
    {
        $reservation = IlanReservation::create([
            'ilan_id'       => $this->ilan->id,
            'islem_statusu' => 'confirmed',
            'starts_at'     => Carbon::tomorrow()->setTime(10, 0),
            'ends_at'       => Carbon::tomorrow()->setTime(12, 0),
            'source'        => 'test',
        ]);

        // Second confirm must return the same reservation without throwing
        $result = $this->service->confirm($reservation->id, $this->user->id);

        $this->assertSame($reservation->id, $result->id);
        $this->assertSame('confirmed', $result->islem_statusu);

        // Must still be confirmed, not changed to another status
        $this->assertDatabaseHas('ilan_reservations', [
            'id'            => $reservation->id,
            'islem_statusu' => 'confirmed',
        ]);
    }
}
