<?php

namespace Tests\Feature\Listing;

use App\Services\Listing\ListingStateMachine;
use DomainException;
use Tests\TestCase;

class ListingStateMachineTest extends TestCase
{
    private ListingStateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->machine = new ListingStateMachine();
    }

    /** @test */
    public function taslak_gonderilince_incelemeye_girer(): void
    {
        // TASLAK(0) → BEKLEMEDE(1) — geçerli
        $this->machine->gecisYap(ListingStateMachine::TASLAK, ListingStateMachine::BEKLEMEDE);
        $this->assertTrue(true); // Exception atılmadıysa geçiş geçerlidir
    }

    /** @test */
    public function taslaktan_dogrudan_yayina_gecis_yasaktir(): void
    {
        // SAB §5 kritik kural: Taslak → Yayında YASAK
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Taslak.*Yayında/u');

        $this->machine->gecisYap(ListingStateMachine::TASLAK, ListingStateMachine::YAYINDA);
    }

    /** @test */
    public function incelemeden_yayinlanabilire_gecilir(): void
    {
        $this->machine->gecisYap(ListingStateMachine::BEKLEMEDE, ListingStateMachine::YAYINDA);
        $this->assertTrue(true);
    }

    /** @test */
    public function incelemeden_reddedilebilir(): void
    {
        $this->machine->gecisYap(ListingStateMachine::BEKLEMEDE, ListingStateMachine::PASIF);
        $this->assertTrue(true);
    }

    /** @test */
    public function yayinlanabilirden_yayina_gecilir(): void
    {
        $this->machine->gecisYap(ListingStateMachine::PASIF, ListingStateMachine::YAYINDA);
        $this->assertTrue(true);
    }

    /** @test */
    public function yayindayken_arsive_alinir(): void
    {
        $this->machine->gecisYap(ListingStateMachine::YAYINDA, ListingStateMachine::ARSIV);
        $this->assertTrue(true);
    }

    /** @test */
    public function arsivden_taslagia_donulebilir(): void
    {
        $this->machine->gecisYap(ListingStateMachine::ARSIV, ListingStateMachine::TASLAK);
        $this->assertTrue(true);
    }

    /** @test */
    public function gecersiz_hedef_durum_exception_atar(): void
    {
        $this->expectException(DomainException::class);
        $this->machine->gecisYap(ListingStateMachine::YAYINDA, ListingStateMachine::TASLAK);
    }

    /** @test */
    public function tam_kalite_skoruyla_yayin_kontrolu_gecer(): void
    {
        $this->machine->yayinIcinKontrolEt(kaliteSkoru: 75, tamamlanmaSkoru: 100);
        $this->assertTrue(true);
    }

    /** @test */
    public function eksik_tamamlanma_skoru_yayini_engeller(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/tamamlanma skoru/u');

        $this->machine->yayinIcinKontrolEt(kaliteSkoru: 90, tamamlanmaSkoru: 80);
    }

    /** @test */
    public function dusuk_kalite_skoru_yayini_engeller(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/kalite skoru/u');

        $this->machine->yayinIcinKontrolEt(kaliteSkoru: 20, tamamlanmaSkoru: 100);
    }

    /** @test */
    public function izin_verilen_gecisleri_listeler(): void
    {
        $gecisler = $this->machine->izinlenenGecisler(ListingStateMachine::TASLAK);

        $this->assertCount(1, $gecisler);
        $this->assertEquals(ListingStateMachine::BEKLEMEDE, $gecisler[0]['deger']);
        $this->assertEquals('Beklemede', $gecisler[0]['isim']);
    }

    /** @test */
    public function durum_ismini_dogru_doner(): void
    {
        $this->assertEquals('Taslak',   $this->machine->durumIsmi(0));
        $this->assertEquals('Yayında',  $this->machine->durumIsmi(2));
        $this->assertEquals('Arşiv',    $this->machine->durumIsmi(3));
    }
}
