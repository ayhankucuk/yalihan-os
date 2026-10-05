<?php

namespace Tests\Feature\Frontend;

use App\Enums\IlanDurumu;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Mahalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression Test Suite for ROUTER-500 — Minimal Canonical Fix
 * 
 * Task ID: REASONING_PIPELINE_V1_ROUTER_500_01_FIX
 * Verifier: b85ad27e-0412-4dfc-86f1-6e52d78bcff0 (VERIFIED_FAIL → Corrective Action)
 * 
 * Fix: Removed unauthorized show-yazlik.blade.php and $isYazlik conditional logic.
 * All ilan show routes now use canonical frontend.ilanlar.show view.
 * 
 * @see https://github.com/.../issues/ROUTER-500
 */
class IlanShowRouter500RegressionTest extends TestCase
{
    use RefreshDatabase;

    private IlanKategori $yazlikKategori;
    private IlanKategori $daireKategori;
    private Il $il;
    private Ilce $ilce;
    private Mahalle $mahalle;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create location
        $this->il = Il::firstOrCreate(
            ['plaka_kodu' => 48],
            ['il_adi' => 'Muğla', 'slug' => 'mugla', 'aktiflik_durumu' => 1]
        );
        
        $this->ilce = Ilce::firstOrCreate(
            ['il_id' => $this->il->id, 'slug' => 'bodrum'],
            ['ilce_adi' => 'Bodrum', 'aktiflik_durumu' => 1]
        );
        
        $this->mahalle = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilce->id, 'slug' => 'yalikavak'],
            ['mahalle_adi' => 'Yalıkavak', 'aktiflik_durumu' => 1]
        );

        // Create categories
        $this->yazlikKategori = IlanKategori::firstOrCreate(
            ['slug' => 'yazlik-kiralama'],
            [
                'name' => 'Yazlık Kiralama',
                'seviye' => 0,
                'aktiflik_durumu' => 1,
                'display_order' => 1,
            ]
        );
        
        $this->daireKategori = IlanKategori::firstOrCreate(
            ['slug' => 'daire-satilik'],
            [
                'name' => 'Satılık Daire',
                'seviye' => 0,
                'aktiflik_durumu' => 1,
                'display_order' => 2,
            ]
        );

        // Create user
        $this->user = User::factory()->create(['tenant_id' => 1]);
    }

    /**
     * R1: Public yazlık kategorili ilan → HTTP 200 + canonical show view
     * 
     * This was the original ROUTER-500 failure case.
     * Before fix: Controller tried to load non-existent show-yazlik.blade.php → HTTP 500
     * After fix: All ilanlar use canonical frontend.ilanlar.show → HTTP 200
     */
    public function test_public_yazlik_ilan_returns_200_with_canonical_view(): void
    {
        $ilan = Ilan::create([
            'tenant_id' => 1,
            'baslik' => 'Deniz Manzaralı Yazlık Villa',
            'slug' => 'deniz-manzarali-yazlik-villa',
            'aciklama' => 'Muhteşem deniz manzarası',
            'fiyat' => 150000,
            'para_birimi' => 'TRY',
            'kategori_id' => $this->yazlikKategori->id,
            'ana_kategori_id' => $this->yazlikKategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $this->mahalle->id,
            'danisman_id' => $this->user->id,
            'ilan_sahibi_id' => $this->user->id,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            // Bodrum/Yalıkavak koordinatları — test fixture için geçerli değerler
            'lat' => 37.0736,
            'lng' => 27.4249,
        ]);

        $response = $this->get(route('ilanlar.show', $ilan->id));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.ilanlar.show');
        $response->assertSee('Deniz Manzaralı Yazlık Villa');
    }

    /**
     * R2: Public non-yazlık ilan → HTTP 200 + canonical show view
     * 
     * Regression check: Normal ilanlar still work correctly.
     */
    public function test_public_non_yazlik_ilan_returns_200(): void
    {
        $ilan = Ilan::create([
            'tenant_id' => 1,
            'baslik' => 'Merkezi Konumda Satılık Daire',
            'slug' => 'merkezi-konumda-satilik-daire',
            'aciklama' => 'Şehir merkezinde',
            'fiyat' => 2500000,
            'para_birimi' => 'TRY',
            'kategori_id' => $this->daireKategori->id,
            'ana_kategori_id' => $this->daireKategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $this->mahalle->id,
            'danisman_id' => $this->user->id,
            'ilan_sahibi_id' => $this->user->id,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            // Bodrum/Yalıkavak koordinatları — test fixture için geçerli değerler
            'lat' => 37.0736,
            'lng' => 27.4249,
        ]);

        $response = $this->get(route('ilanlar.show', $ilan->id));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.ilanlar.show');
        $response->assertSee('Merkezi Konumda Satılık Daire');
    }

    /**
     * R3: Non-public (taslak) ilan → HTTP 404 (fail-closed)
     * 
     * Regression check: Draft ilanlar are not accessible to public.
     */
    public function test_non_public_ilan_returns_404(): void
    {
        $ilan = Ilan::create([
            'tenant_id' => 1,
            'baslik' => 'Taslak İlan',
            'slug' => 'taslak-ilan',
            'aciklama' => 'Henüz yayınlanmadı',
            'fiyat' => 100000,
            'para_birimi' => 'TRY',
            'kategori_id' => $this->yazlikKategori->id,
            'ana_kategori_id' => $this->yazlikKategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $this->mahalle->id,
            'danisman_id' => $this->user->id,
            'ilan_sahibi_id' => $this->user->id,
            'yayin_durumu' => IlanDurumu::TASLAK->value,
        ]);

        $response = $this->get(route('ilanlar.show', $ilan->id));

        $response->assertStatus(404);
    }

    /**
     * R4: Non-existent ilan → HTTP 404
     */
    public function test_nonexistent_ilan_returns_404(): void
    {
        $response = $this->get(route('ilanlar.show', 999999));

        $response->assertStatus(404);
    }

    /**
     * R5: Canonical show view exists and is valid
     */
    public function test_canonical_show_view_exists(): void
    {
        $viewPath = resource_path('views/frontend/ilanlar/show.blade.php');
        
        $this->assertFileExists($viewPath, 'canonical show.blade.php must exist');
        
        $content = file_get_contents($viewPath);
        $this->assertStringContainsString("@extends('layouts.frontend')", $content);
        $this->assertStringContainsString('@section(\'content\')', $content);
    }

    /**
     * R6: No orphaned show-yazlik view reference in controller
     */
    public function test_no_orphaned_yazlik_view_reference_in_controller(): void
    {
        $controllerPath = app_path('Http/Controllers/IlanPublicController.php');
        $content = file_get_contents($controllerPath);

        // After fix: show-yazlik should NOT be referenced
        $this->assertStringNotContainsString('show-yazlik', $content, 
            'Controller should not reference show-yazlik view after canonical fix');
        
        // After fix: All paths use canonical show view
        $this->assertStringContainsString("view('frontend.ilanlar.show'", $content);
    }
}
