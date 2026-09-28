<?php

namespace Tests\Feature\Crud;

use Tests\TestCase;
use App\Models\Ilan;
use App\Models\User;
use App\Models\IlanPriceHistory;
use App\Models\V2\Ilan as V2Ilan;
use App\Actions\Api\V2\Ilan\UnpublishIlanAction;
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

    /**
     * Test A: Partial title update preserves price and currency and does not append price history
     *
     * @test
     * @group crud
     */
    public function test_partial_update_title_preserves_price_and_currency_and_does_not_append_history(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ilan = Ilan::factory()->create([
            'baslik'       => 'Orijinal Baslik',
            'fiyat'        => 2500000,
            'para_birimi'  => 'EUR',
            'yayin_durumu' => 'taslak',
        ]);

        $historyCountBefore = IlanPriceHistory::where('ilan_id', $ilan->id)->count();

        $service = app(IlanCrudService::class);
        $service->update($ilan, [
            'baslik' => 'Guncel Baslik',
        ]);

        $ilan->refresh();
        $this->assertEquals('Guncel Baslik', $ilan->baslik);
        $this->assertEquals(2500000.0, (float) $ilan->fiyat);
        $this->assertEquals('EUR', $ilan->para_birimi);

        $historyCountAfter = IlanPriceHistory::where('ilan_id', $ilan->id)->count();
        $this->assertEquals($historyCountBefore, $historyCountAfter, 'Partial title update must not write any price history record');
    }

    /**
     * Test B: Partial description update preserves price and currency and does not append price history
     *
     * @test
     * @group crud
     */
    public function test_partial_update_description_preserves_price_and_currency_and_does_not_append_history(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ilan = Ilan::factory()->create([
            'aciklama'     => 'Orijinal Aciklama',
            'fiyat'        => 2500000,
            'para_birimi'  => 'EUR',
            'yayin_durumu' => 'taslak',
        ]);

        $historyCountBefore = IlanPriceHistory::where('ilan_id', $ilan->id)->count();

        $service = app(IlanCrudService::class);
        $service->update($ilan, [
            'aciklama' => 'Yeni Aciklama Metni',
        ]);

        $ilan->refresh();
        $this->assertEquals('Yeni Aciklama Metni', $ilan->aciklama);
        $this->assertEquals(2500000.0, (float) $ilan->fiyat);
        $this->assertEquals('EUR', $ilan->para_birimi);

        $historyCountAfter = IlanPriceHistory::where('ilan_id', $ilan->id)->count();
        $this->assertEquals($historyCountBefore, $historyCountAfter, 'Partial description update must not write any price history record');
    }

    /**
     * Test C: UnpublishIlanAction preserves price and currency and does not append price history
     *
     * @test
     * @group crud
     */
    public function test_unpublish_action_preserves_price_and_currency_and_does_not_append_history(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ilan = Ilan::factory()->create([
            'baslik'       => 'Yayindaki Ilan',
            'fiyat'        => 2500000,
            'para_birimi'  => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $historyCountBefore = IlanPriceHistory::where('ilan_id', $ilan->id)->count();

        $v2Ilan = V2Ilan::findOrFail($ilan->id);
        $action = app(UnpublishIlanAction::class);
        $action->handle($v2Ilan);

        $ilan->refresh();
        $this->assertEquals(IlanDurumu::PASIF, $ilan->yayin_durumu);
        $this->assertEquals(2500000.0, (float) $ilan->fiyat);
        $this->assertEquals('EUR', $ilan->para_birimi);

        $historyCountAfter = IlanPriceHistory::where('ilan_id', $ilan->id)->count();
        $this->assertEquals($historyCountBefore, $historyCountAfter, 'Unpublish action must not write any price history record');
    }

    /**
     * Test D: Explicit price change updates price and records exactly one price history record
     *
     * @test
     * @group crud
     */
    public function test_explicit_price_change_updates_price_and_records_exactly_one_history(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ilan = Ilan::factory()->create([
            'fiyat'        => 2500000,
            'para_birimi'  => 'EUR',
            'yayin_durumu' => 'taslak',
        ]);

        $historyCountBefore = IlanPriceHistory::where('ilan_id', $ilan->id)->count();

        $service = app(IlanCrudService::class);
        $service->update($ilan, [
            'fiyat'               => 2750000,
            'price_change_reason' => 'Fiyat artisi',
        ]);

        $ilan->refresh();
        $this->assertEquals(2750000.0, (float) $ilan->fiyat);
        $this->assertEquals('EUR', $ilan->para_birimi);

        $historyCountAfter = IlanPriceHistory::where('ilan_id', $ilan->id)->count();
        $this->assertEquals($historyCountBefore + 1, $historyCountAfter, 'Explicit price change must write exactly one price history record');

        $latestHistory = IlanPriceHistory::where('ilan_id', $ilan->id)->latest('id')->first();
        $this->assertEquals(2500000.0, (float) $latestHistory->old_price);
        $this->assertEquals(2750000.0, (float) $latestHistory->new_price);
        $this->assertEquals('EUR', $latestHistory->currency);
        $this->assertEquals('Fiyat artisi', $latestHistory->change_reason);
        $this->assertEquals($user->id, $latestHistory->changed_by);
    }

    /**
     * Test E: Explicit zero price is treated as explicit price update, not omitted
     *
     * @test
     * @group crud
     */
    public function test_explicit_zero_price_is_treated_as_explicit_price_update(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ilan = Ilan::factory()->create([
            'fiyat'        => 2500000,
            'para_birimi'  => 'EUR',
            'yayin_durumu' => 'taslak',
        ]);

        $historyCountBefore = IlanPriceHistory::where('ilan_id', $ilan->id)->count();

        $service = app(IlanCrudService::class);
        $service->update($ilan, [
            'fiyat'               => 0,
            'price_change_reason' => 'Fiyat sifirlandi',
        ]);

        $ilan->refresh();
        $this->assertEquals(0.0, (float) $ilan->fiyat);
        $this->assertEquals('EUR', $ilan->para_birimi);

        $historyCountAfter = IlanPriceHistory::where('ilan_id', $ilan->id)->count();
        $this->assertEquals($historyCountBefore + 1, $historyCountAfter, 'Explicit zero price must write a price history record');

        $latestHistory = IlanPriceHistory::where('ilan_id', $ilan->id)->latest('id')->first();
        $this->assertEquals(2500000.0, (float) $latestHistory->old_price);
        $this->assertEquals(0.0, (float) $latestHistory->new_price);
        $this->assertEquals('EUR', $latestHistory->currency);
    }

    /**
     * Test F: Explicit price with omitted currency preserves existing currency
     *
     * @test
     * @group crud
     */
    public function test_explicit_price_with_omitted_currency_preserves_existing_currency(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ilan = Ilan::factory()->create([
            'fiyat'        => 2500000,
            'para_birimi'  => 'EUR',
            'yayin_durumu' => 'taslak',
        ]);

        $historyCountBefore = IlanPriceHistory::where('ilan_id', $ilan->id)->count();

        $service = app(IlanCrudService::class);
        $service->update($ilan, [
            'fiyat' => 3000000,
        ]);

        $ilan->refresh();
        $this->assertEquals(3000000.0, (float) $ilan->fiyat);
        $this->assertEquals('EUR', $ilan->para_birimi, 'Existing EUR currency must be preserved when para_birimi is omitted');

        $historyCountAfter = IlanPriceHistory::where('ilan_id', $ilan->id)->count();
        $this->assertEquals($historyCountBefore + 1, $historyCountAfter);

        $latestHistory = IlanPriceHistory::where('ilan_id', $ilan->id)->latest('id')->first();
        $this->assertEquals('EUR', $latestHistory->currency);
    }
}

