<?php

namespace Database\Seeders;

use App\Enums\IlanDurumu;
use App\Models\Il;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * MiniDemoIlanSeeder
 *
 * Idempotent, local-only demo listings for admin panel CRUD testing.
 *
 * Rules:
 * - Idempotent: slug-based firstOrCreate guard.
 * - Local/dev only: skips on production/staging.
 * - No hardcoded IDs: all FK via slug/name lookups.
 * - No images.
 * - No truncate.
 * - Context7 compliant: no forbidden fields.
 */
class MiniDemoIlanSeeder extends Seeder
{
    private const SLUG_PREFIX = 'mini-demo-';

    public function run(): void
    {
        if (app()->environment('production', 'staging')) {
            $this->command->warn('Skipping MiniDemoIlanSeeder: production/staging detected.');
            return;
        }

        $this->command->info('MiniDemoIlanSeeder: creating demo listings...');

        // Resolve FK via slug/name lookups (no hardcoded IDs)
        $il = Il::where('il_adi', 'Muğla')->firstOrFail();
        $ilce = Ilce::where('il_id', $il->id)->where('ilce_adi', 'Bodrum')->firstOrFail();

        $yalikavak = Mahalle::where('ilce_id', $ilce->id)->where('mahalle_adi', 'Yalıkavak')->first();
        $turkbuku = Mahalle::where('ilce_id', $ilce->id)->where('mahalle_adi', 'Türkbükü')->first();
        $gunduz = Mahalle::where('ilce_id', $ilce->id)->where('mahalle_adi', 'Gündoğan')->first();

        $konut = IlanKategori::where('slug', 'konut')->first();
        $arsaAna = IlanKategori::where('slug', 'arsa-arazi')->first();
        $villa = IlanKategori::where('slug', 'villa')->first();
        $daire = IlanKategori::where('slug', 'daire')->first();
        $arsaAlt = IlanKategori::where('slug', 'arsa-konut-villa')->first();

        $admin = User::where('email', 'ayhankucuk@gmail.com')->first();

        $created = 0;
        $skipped = 0;

        // ── 1. Bodrum Yalıkavak Satılık Villa ─────────────────────────
        $s1 = 'bodrum-yyalikavak-satilik-villa';
        if (Ilan::withoutGlobalScopes()->where('slug', self::SLUG_PREFIX . $s1)->exists()) {
            $this->command->info("  skipped (exists): {$s1}");
            $skipped++;
        } else {
            Ilan::withoutGlobalScopes()->create([
                'baslik' => 'Bodrum Yalıkavak Satılık Lüks Villa',
                'aciklama' => 'Deniz manzaralı özel havuzlu 4+1 lüks villa. Yalıkavak marina ve merkeze yürüme mesafesinde. Tamamen eşyalı.',
                'fiyat' => 18500000,
                'para_birimi' => 'TRY',
                'yayin_durumu' => IlanDurumu::YAYINDA->value,
                'il_id' => $il->id,
                'ilce_id' => $ilce->id,
                'mahalle_id' => $yalikavak?->id,
                'ana_kategori_id' => $konut?->id,
                'alt_kategori_id' => $villa?->id,
                'danisman_id' => $admin?->id,
                'slug' => self::SLUG_PREFIX . $s1,
                'ilan_no' => 'DEMO-VILA-001',
                'referans_no' => 'DEMO-VILA-001-YALK',
                'alan_m2' => 280,
                'oda_sayisi' => 4,
                'bina_yasi' => 3,
                'kat' => 3,
                'banyo_sayisi' => 3,
                'isitma' => 'Kombi Doğalgaz',
                'esyali' => true,
                'havuz_var' => true,
                'lat' => 37.0876,
                'lng' => 27.2966,
                'one_cikan' => true,
                'display_order' => 1,
            ]);
            $this->command->info("  created: {$s1}");
            $created++;
        }

        // ── 2. Bodrum Türkbükü Satılık Daire ────────────────────────
        $s2 = 'bodrum-turkbuku-satilik-daire';
        if (Ilan::withoutGlobalScopes()->where('slug', self::SLUG_PREFIX . $s2)->exists()) {
            $this->command->info("  skipped (exists): {$s2}");
            $skipped++;
        } else {
            Ilan::withoutGlobalScopes()->create([
                'baslik' => 'Bodrum Türkbükü Satılık Deniz Manzaralı Daire',
                'aciklama' => 'Türkbükü merkezde, denize 200m mesafede 2+1 daire. Site içinde güvenlikli.',
                'fiyat' => 7500000,
                'para_birimi' => 'TRY',
                'yayin_durumu' => IlanDurumu::YAYINDA->value,
                'il_id' => $il->id,
                'ilce_id' => $ilce->id,
                'mahalle_id' => $turkbuku?->id,
                'ana_kategori_id' => $konut?->id,
                'alt_kategori_id' => $daire?->id,
                'danisman_id' => $admin?->id,
                'slug' => self::SLUG_PREFIX . $s2,
                'ilan_no' => 'DEMO-DAIR-001',
                'referans_no' => 'DEMO-DAIR-001-TRBK',
                'alan_m2' => 95,
                'oda_sayisi' => 2,
                'bina_yasi' => 8,
                'kat' => 2,
                'banyo_sayisi' => 1,
                'isitma' => 'Merkezi Doğalgaz',
                'esyali' => false,
                'lat' => 37.0561,
                'lng' => 27.3723,
                'one_cikan' => false,
                'display_order' => 2,
            ]);
            $this->command->info("  created: {$s2}");
            $created++;
        }

        // ── 3. Bodrum Gündoğan Satılık Arsa ──────────────────────────
        $s3 = 'bodrum-gundugan-satilik-arsa';
        if (Ilan::withoutGlobalScopes()->where('slug', self::SLUG_PREFIX . $s3)->exists()) {
            $this->command->info("  skipped (exists): {$s3}");
            $skipped++;
        } else {
            Ilan::withoutGlobalScopes()->create([
                'baslik' => 'Bodrum Gündoğan Satılık İmarlı Arsa',
                'aciklama' => 'Gündoğan köy içinde konut imarlı 500m² arsa. Yola cepheli altyapı mevcut. Deniz manzarası.',
                'fiyat' => 4200000,
                'para_birimi' => 'TRY',
                'yayin_durumu' => IlanDurumu::YAYINDA->value,
                'il_id' => $il->id,
                'ilce_id' => $ilce->id,
                'mahalle_id' => $gunduz?->id,
                'ana_kategori_id' => $arsaAna?->id,
                'alt_kategori_id' => $arsaAlt?->id,
                'danisman_id' => $admin?->id,
                'slug' => self::SLUG_PREFIX . $s3,
                'ilan_no' => 'DEMO-ARSA-001',
                'referans_no' => 'DEMO-ARSA-001-GUND',
                'alan_m2' => 500,
                'kaks' => 0.30,
                'taks' => 0.15,
                'yola_cephesi' => true,
                'altyapi_elektrik' => true,
                'altyapi_su' => true,
                'imar_statusu' => 'Konut İmarlı',
                'lat' => 37.1061,
                'lng' => 27.4379,
                'one_cikan' => false,
                'display_order' => 3,
            ]);
            $this->command->info("  created: {$s3}");
            $created++;
        }

        $this->command->info("Done: {$created} created, {$skipped} skipped. Total ilanlar: " . Ilan::withoutGlobalScopes()->count());
    }
}
