<?php

namespace Tests\Feature\Emlak;

use Tests\TestCase;
use App\Models\Ilan;
use App\Models\User;
use App\Modules\Emlak\Models\Proje;
use App\Modules\Emlak\Models\ProjeTranslation;
use App\Modules\Emlak\Models\ProjeGorsel;
use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\Bus;

/**
 * ADR #006 Bounded Context Regression Test
 *
 * Validates that:
 *   1. Ilan::proje()  resolves to App\Modules\Emlak\Models\Proje (emlak_projeleri)
 *   2. Proje::ilanlar() resolves to App\Models\Ilan (ilanlar) — inverse relation
 *   3. The bounded context is isolated from Takım Projesi (App\Models\Proje / projeler)
 *
 * @group emlak
 * @group adr006
 */
class EmlakProjeBoundedContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
    }

    /**
     * ADR #006 Invariant A: Ilan.proje() returns an Emlak Proje instance.
     *
     * @test
     * @group emlak
     * @group adr006
     */
    public function ilan_proje_returns_emlak_proje_instance(): void
    {
        // Create Emlak Proje directly in emlak_projeleri
        $emlakProje = Proje::create([
            'gelistirici_adi' => 'Test Geliştirici A.Ş.',
            'yayin_durumu' => 'yayinda',
            'one_cikan' => false,
            'adres_il' => 'Muğla',
            'adres_ilce' => 'Bodrum',
        ]);

        // Create Ilan and associate with Emlak Proje via proje_id
        $ilan = Ilan::factory()->make([
            'baslik' => 'Bodrumda Satılık Villa',
            'yayin_durumu' => 'taslak',
        ]);
        $ilan->proje_id = $emlakProje->id;
        $ilan->save();

        // Act: Resolve the proje relation
        $resolved = $ilan->proje;

        // Assert: Ilan.proje() returns Emlak\Proje, not Takım\Models\Proje
        $this->assertInstanceOf(Proje::class, $resolved);
        $this->assertEquals($emlakProje->id, $resolved->id);
        $this->assertEquals('emlak_projeleri', $resolved->getTable());
        $this->assertEquals('Test Geliştirici A.Ş.', $resolved->gelistirici_adi);
        $this->assertEquals('Muğla', $resolved->adres_il);
    }

    /**
     * ADR #006 Invariant B: Proje.ilanlar() returns Ilan collection (inverse).
     *
     * @test
     * @group emlak
     * @group adr006
     */
    public function emlak_proje_ilanlar_returns_ilan_collection(): void
    {
        // Create Emlak Proje
        $emlakProje = Proje::create([
            'gelistirici_adi' => 'Bodrum Yapı Ltd.',
            'yayin_durumu' => 'yayinda',
            'one_cikan' => false,
            'adres_il' => 'Muğla',
            'adres_ilce' => 'Bodrum',
        ]);

        // Create two Ilans associated with the Emlak Proje
        $ilan1 = Ilan::factory()->make([
            'baslik' => 'Bodrum Satılık 1',
            'yayin_durumu' => 'yayinda',
        ]);
        $ilan1->proje_id = $emlakProje->id;
        $ilan1->save();

        $ilan2 = Ilan::factory()->make([
            'baslik' => 'Bodrum Satılık 2',
            'yayin_durumu' => 'yayinda',
        ]);
        $ilan2->proje_id = $emlakProje->id;
        $ilan2->save();

        // Act: Resolve the ilanlar relation from Emlak Proje
        $ilanlar = $emlakProje->ilanlar;

        // Assert: Returns Ilan collection with correct count
        $this->assertCount(2, $ilanlar);
        $this->assertTrue($ilanlar->contains('id', $ilan1->id));
        $this->assertTrue($ilanlar->contains('id', $ilan2->id));
    }

    /**
     * ADR #006 Invariant C: ilanlar.proje_id stores Emlak Proje ID (not Takım Projesi).
     *
     * @test
     * @group emlak
     * @group adr006
     */
    public function ilan_proje_id_stores_emlak_proje_id(): void
    {
        // Create Emlak Proje
        $emlakProje = Proje::create([
            'gelistirici_adi' => 'Fırsat İnşaat',
            'yayin_durumu' => 'taslak',
            'one_cikan' => false,
            'adres_il' => 'Antalya',
            'adres_ilce' => 'Alanya',
        ]);

        // Create Ilan
        $ilan = Ilan::factory()->make([
            'baslik' => 'Alanya Satılık Daire',
            'yayin_durumu' => 'taslak',
        ]);
        $ilan->proje_id = $emlakProje->id;
        $ilan->save();

        // Assert: Database stores emlak_projeleri.id in ilanlar.proje_id
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilan->id,
            'proje_id' => $emlakProje->id,
        ]);

        // Assert: The stored ID resolves to emlak_projeleri
        $this->assertDatabaseHas('emlak_projeleri', [
            'id' => $emlakProje->id,
            'gelistirici_adi' => 'Fırsat İnşaat',
        ]);
    }

    /**
     * ADR #006 Invariant D: Ilan without proje_id has null proje relation.
     *
     * @test
     * @group emlak
     * @group adr006
     */
    public function ilan_without_proje_id_returns_null_proje(): void
    {
        $ilan = Ilan::factory()->make([
            'baslik' => 'Bağımsız İlan',
            'yayin_durumu' => 'yayinda',
        ]);
        // proje_id not set — should be null
        $ilan->save();

        $this->assertNull($ilan->proje_id);
        $this->assertNull($ilan->proje);
    }

    /**
     * ADR #006 Invariant E: Emlak Proje stores in emlak_projeleri table, not projeler.
     *
     * @test
     * @group emlak
     * @group adr006
     */
    public function emlak_proje_stored_in_emlak_projeleri_not_projeler(): void
    {
        // Create Emlak Proje
        $emlakProje = Proje::create([
            'gelistirici_adi' => 'Konut Vadisi A.Ş.',
            'yayin_durumu' => 'yayinda',
            'one_cikan' => false,
            'adres_il' => 'İstanbul',
            'adres_ilce' => 'Sarıyer',
        ]);

        // Assert: Record is in emlak_projeleri
        $this->assertDatabaseHas('emlak_projeleri', [
            'id' => $emlakProje->id,
            'gelistirici_adi' => 'Konut Vadisi A.Ş.',
        ]);

        // Assert: Record is NOT in Takım projeler table
        // (projeler may or may not exist — but this ID should not be there)
        if (\Illuminate\Support\Facades\Schema::hasTable('projeler')) {
            $this->assertDatabaseMissing('projeler', [
                'id' => $emlakProje->id,
            ]);
        }
    }

    /**
     * ADR #006 Invariant F: Emlak Proje soft-delete cascades to nullify ilanlar.proje_id.
     * Note: Laravel soft-deletes do not automatically nullify FK columns.
     * This test documents current behavior. FK constraint to enforce null-on-delete
     * would require a future additive migration.
     *
     * @test
     * @group emlak
     * @group adr006
     */
    public function emlak_proje_soft_delete_removes_from_active_relation(): void
    {
        // Create Emlak Proje
        $emlakProje = Proje::create([
            'gelistirici_adi' => 'Geçici Proje Ltd.',
            'yayin_durumu' => 'yayinda',
            'one_cikan' => false,
            'adres_il' => 'Ankara',
            'adres_ilce' => 'Çankaya',
        ]);

        // Create Ilan linked to it
        $ilan = Ilan::factory()->make(['baslik' => 'Ankara Kiralık']);
        $ilan->proje_id = $emlakProje->id;
        $ilan->save();

        // Soft-delete the Emlak Proje
        $emlakProje->delete();

        // The FK column still holds the ID (no automatic nullify without DB constraint)
        $this->assertEquals($emlakProje->id, $ilan->fresh()->proje_id);

        // But the Proje is no longer reachable via the relation (soft-deleted scope)
        $this->assertNull($ilan->fresh()->proje);
    }
}
