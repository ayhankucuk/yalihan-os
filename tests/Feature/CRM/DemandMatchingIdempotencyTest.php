<?php

namespace Tests\Feature\CRM;

use App\Domain\CRM\DTOs\DemandMatchResult;
use App\Events\CRM\DemandMatched;
use App\Listeners\CRM\CreateActionCenterTaskForMatchedDemand;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Ilce;
use App\Models\Il;
use App\Models\Mahalle;
use App\Models\Role;
use App\Models\Talep;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * DemandMatchingIdempotencyTest
 *
 * Proves that duplicate event dispatching produces exactly one ActionCenter task.
 */
class DemandMatchingIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private Il $il;
    private Ilce $ilce;
    private Mahalle $mahalle;
    private IlanKategori $kategori;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->il = Il::firstOrCreate(
            ['plaka_kodu' => '48'],
            ['il_adi' => 'Muğla', 'aktiflik_durumu' => 1]
        );

        $this->ilce = Ilce::firstOrCreate(
            ['il_id' => $this->il->id, 'ilce_adi' => 'Bodrum'],
            ['aktiflik_durumu' => 1]
        );

        $this->mahalle = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilce->id, 'mahalle_adi' => 'Yalıkavak'],
            ['aktiflik_durumu' => 1]
        );

        $this->kategori = IlanKategori::firstOrCreate(
            ['slug' => 'konut'],
            ['name' => 'Konut', 'aktiflik_durumu' => 1]
        );

        $role = Role::firstOrCreate(['name' => 'danisman'], ['guard_name' => 'web']);
        $this->user = new User([
            'name'      => 'Danışman Test',
            'email'     => 'danisman-idemp@yalihan.test',
            'password'  => bcrypt('secret'),
            'tenant_id' => 'tenant-100',
        ]);
        $this->user->role_id = $role->id;
        $this->user->save();
    }

    public function test_duplicate_demand_matched_event_creates_only_one_action_center_task(): void
    {
        $ilan = Ilan::create([
            'tenant_id'       => 'tenant-100',
            'user_id'         => $this->user->id,
            'baslik'          => 'Yalıkavak Villa',
            'slug'            => 'yalikavak-villa-idemp',
            'fiyat'           => 10000000,
            'para_birimi'     => 'TRY',
            'tip'             => 'Satılık',
            'yayin_tipi_id'   => 1,
            'kategori_id'     => $this->kategori->id,
            'alt_kategori_id' => $this->kategori->id,
            'il_id'           => $this->il->id,
            'ilce_id'         => $this->ilce->id,
            'mahalle_id'      => $this->mahalle->id,
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => 1,
        ]);

        $kisi = \App\Models\Kisi::create([
            'ad'        => 'Test',
            'soyad'     => 'Kişi',
            'kisi_tipi' => 'lead',
        ]);

        $talep = Talep::create([
            'tenant_id'       => 'tenant-100',
            'danisman_id'     => $this->user->id,
            'kisi_id'         => $kisi->id,
            'baslik'          => 'Villa Talebi',
            'talep_tipi'      => 'Satılık',
            'talep_durumu'    => 'yayinda',
            'kategori_id'     => $this->kategori->id,
            'alt_kategori_id' => $this->kategori->id,
            'il_id'           => $this->il->id,
            'ilce_id'         => $this->ilce->id,
            'mahalle_id'      => $this->mahalle->id,
            'min_fiyat'       => 9000000,
            'max_fiyat'       => 12000000,
        ]);

        $matchResult = new DemandMatchResult(
            talepId: $talep->id,
            talepBaslik: $talep->baslik,
            kisiId: null,
            kisiAdSoyad: null,
            danismanId: $this->user->id,
            ilanId: $ilan->id,
            score: 95.0,
            matchLevel: 'STRONG',
            urgencyLevel: 'HIGH',
            matchReasons: ['Tam lokasyon uyumu', 'Bütçe aralığı uyumlu', 'Mülk tipi eşleşti']
        );

        $idempotencyKey = "tenant-100:{$ilan->id}:{$talep->id}:demand_match";

        $event = new DemandMatched(
            ilan: $ilan,
            matchResult: $matchResult,
            idempotencyKey: $idempotencyKey
        );

        $listener = new CreateActionCenterTaskForMatchedDemand();

        // 1st invocation
        $listener->handle($event);

        $tasksCount = Gorev::where('source_event', 'DemandMatched')
            ->where('ilan_id', $ilan->id)
            ->count();
        $this->assertEquals(1, $tasksCount, 'First event should create exactly one Gorev');

        // 2nd invocation with same event / idempotency key
        $listener->handle($event);

        $tasksCountAfter = Gorev::where('source_event', 'DemandMatched')
            ->where('ilan_id', $ilan->id)
            ->count();
        $this->assertEquals(1, $tasksCountAfter, 'Duplicate event must NOT create a duplicate Gorev');
    }
}
