<?php

namespace Tests\Feature\Crud;

use Tests\TestCase;
use App\Models\Ilan;
use App\Models\User;
use App\Services\Ilan\IlanCrudService;
use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\Bus;

class IlanCrudTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Fake job dispatch — downstream jobs (n8n webhook, listing projection)
        // are integration concerns, not CRUD unit test scope.
        Bus::fake();
    }

    /**
     * Test: Can create ilan with Context7 fields via CrudService
     *
     * @test
     * @group crud
     */
    public function test_can_create_ilan(): void
    {
        // Arrange: Create danisman
        $danisman = User::factory()->danisman()->create();

        // Arrange: Prepare Context7-compliant data
        $data = [
            'baslik' => 'Test İlan',
            'aciklama' => 'Test açıklama',
            'yayin_durumu' => 'taslak',
            'aktiflik_durumu' => true,
            'one_cikan' => false,
            'danisman_id' => $danisman->id,
        ];

        // Act: Create ilan via Canonical Service
        $service = app(IlanCrudService::class);
        $ilan = $service->store($data);

        // Assert: Database has record with Context7 fields
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilan->id,
            'baslik' => 'Test İlan',
            'yayin_durumu' => 'taslak',
        ]);

        // Assert: Model has correct values
        $this->assertEquals('Test İlan', $ilan->baslik);
        $this->assertEquals(IlanDurumu::TASLAK, $ilan->yayin_durumu);
    }

    /**
     * Test: Can read ilan
     *
     * @test
     * @group crud
     */
    public function test_can_read_ilan(): void
    {
        // Arrange: Create ilan (Factory bypasses seal but here we just need a record)
        $ilan = Ilan::factory()->create([
            'baslik' => 'Okunacak İlan',
            'yayin_durumu' => 'yayinda',
        ]);

        // Act: Retrieve ilan
        $found = Ilan::find($ilan->id);

        // Assert: Found correct record
        $this->assertNotNull($found);
        $this->assertEquals($ilan->id, $found->id);
        $this->assertEquals('Okunacak İlan', $found->baslik);
        $this->assertEquals(IlanDurumu::YAYINDA, $found->yayin_durumu);
    }

    /**
     * Test: Can update ilan with Context7 fields via CrudService
     *
     * @test
     * @group crud
     */
    public function test_can_update_ilan(): void
    {
        // Arrange: Ensure a canonical YayinTipiSablonu exists for the listing FK.
        $sablonu = \App\Models\YayinTipiSablonu::factory()->create([
            'ad' => 'Test Şablon',
            'slug' => 'test-sablon',
            'kategori_id' => \App\Models\IlanKategori::factory()->create()->id,
            'aktiflik_durumu' => true,
        ]);

        // Keep this CRUD test in 'beklemede'. Publishing eligibility belongs to
        // the lifecycle test suite and requires a complete listing plus photos.
        $ilan = Ilan::factory()->create([
            'baslik'           => 'Eski Başlık',
            'yayin_durumu'     => 'beklemede',
            'completion_score' => 100,
            'quality_score'    => 41,
        ]);

        // Force the FK so the lifecycle validator sees it
        $ilan->yayin_tipi_id = $sablonu->id;
        $ilan->save();

        // Act: Update with Context7 fields via Canonical Service
        $service = app(IlanCrudService::class);
        $service->update($ilan, [
            'baslik'         => 'Yeni Başlık',
            'yayin_durumu'   => 'beklemede',
            'yayin_tipi_id'  => $sablonu->id,
        ]);

        // Assert: Database updated
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilan->id,
            'baslik' => 'Yeni Başlık',
            'yayin_durumu' => 'beklemede',
        ]);

        // Assert: Model reflects changes
        $ilan->refresh();
        $this->assertEquals('Yeni Başlık', $ilan->baslik);
        $this->assertEquals(IlanDurumu::BEKLEMEDE, $ilan->yayin_durumu);
    }

    /**
     * Test: Can soft delete ilan via CrudService
     *
     * @test
     * @group crud
     */
    public function test_can_delete_ilan(): void
    {
        // Arrange: Create ilan
        $ilan = Ilan::factory()->create();

        // Act: Soft delete via Canonical Service
        $service = app(IlanCrudService::class);
        $service->destroy($ilan);

        // Assert: Soft deleted (deleted_at set)
        $this->assertSoftDeleted('ilanlar', [
            'id' => $ilan->id,
        ]);
    }

    /**
     * Test: Can restore soft deleted ilan
     *
     * @test
     * @group crud
     */
    public function test_can_restore_ilan(): void
    {
        // Arrange: Create and delete ilan
        $ilan = Ilan::factory()->create();
        $ilan->delete();

        // Assert: Initially soft deleted
        $this->assertSoftDeleted('ilanlar', ['id' => $ilan->id]);

        // Act: Restore
        $ilan->restore();

        // Assert: Restored (deleted_at is null)
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilan->id,
        ]);

        $ilan->refresh();
        $this->assertNull($ilan->deleted_at);

        // Assert: Can find with normal query
        $this->assertNotNull(Ilan::find($ilan->id));
    }

    /**
     * Test: Price change on update appends exactly one price history record
     *
     * @test
     * @group crud
     */
    public function test_price_change_appends_price_history_record_on_update(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ilan = Ilan::factory()->create([
            'fiyat' => 1000000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'taslak',
        ]);

        $historyCountBefore = \App\Models\IlanPriceHistory::where('ilan_id', $ilan->id)->count();

        $service = app(IlanCrudService::class);
        $service->update($ilan, [
            'fiyat' => 1500000,
            'price_change_reason' => 'Piyasa guncellemesi',
        ]);

        $historyCountAfter = \App\Models\IlanPriceHistory::where('ilan_id', $ilan->id)->count();
        $this->assertEquals($historyCountBefore + 1, $historyCountAfter, 'Price change MUST append exactly one history record');

        $latestHistory = \App\Models\IlanPriceHistory::where('ilan_id', $ilan->id)->latest('id')->first();
        $this->assertEquals(1000000.0, (float) $latestHistory->old_price);
        $this->assertEquals(1500000.0, (float) $latestHistory->new_price);
        $this->assertEquals('TRY', $latestHistory->currency);
        $this->assertEquals('Piyasa guncellemesi', $latestHistory->change_reason);
        $this->assertEquals($user->id, $latestHistory->changed_by);
    }

    /**
     * Test: Unchanged price on update produces zero new price history records
     *
     * @test
     * @group crud
     */
    public function test_unchanged_price_does_not_append_history_on_update(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ilan = Ilan::factory()->create([
            'fiyat' => 2000000,
            'para_birimi' => 'TRY',
            'baslik' => 'Eski Baslik',
            'yayin_durumu' => 'taslak',
        ]);

        $historyCountBefore = \App\Models\IlanPriceHistory::where('ilan_id', $ilan->id)->count();

        $service = app(IlanCrudService::class);
        $service->update($ilan, [
            'baslik' => 'Sadece Baslik Degisti',
            'fiyat' => 2000000,
        ]);

        $historyCountAfter = \App\Models\IlanPriceHistory::where('ilan_id', $ilan->id)->count();
        $this->assertEquals($historyCountBefore, $historyCountAfter, 'Unchanged price MUST produce zero new history records');
    }
}

