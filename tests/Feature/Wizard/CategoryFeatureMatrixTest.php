<?php

namespace Tests\Feature\Wizard;

use App\Services\Wizard\FeatureTemplateResolver;
use Database\Seeders\ArsaIsyeriFeatureAssignmentSeeder;
use Database\Seeders\CategoryFeatureMatrixSeeder;
use Database\Seeders\FeatureAssignmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CategoryFeatureMatrixTest — Priority 6 Verification
 *
 * Verifies the Category × Feature Template Matrix across all 6 main categories:
 *   1. Konut
 *   2. İşyeri
 *   3. Arsa & Arazi (including Kat Karşılığı)
 *   4. Yazlık Kiralama
 *   5. Turistik Tesisler
 *   6. Projeden Satış
 *
 * Ensures elimination of silent fallback to 5 generic fields for categories 4, 5, and 6.
 */
class CategoryFeatureMatrixTest extends TestCase
{
    use RefreshDatabase;

    private FeatureTemplateResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPrerequisites();

        $matrixSeeder = new CategoryFeatureMatrixSeeder;
        $matrixSeeder->run();

        $arsaIsyeriSeeder = new ArsaIsyeriFeatureAssignmentSeeder;
        $arsaIsyeriSeeder->run();

        $featureSeeder = new FeatureAssignmentSeeder;
        $featureSeeder->run();

        $this->resolver = app(FeatureTemplateResolver::class);
    }

    /**
     * Category 4: Yazlık Kiralama must resolve operational, financial, rules, and property fields.
     */
    public function test_yazlik_kiralama_resolves_matrix_features(): void
    {
        $features = $this->resolver->resolveFeatures(4, 26, 5); // Günlük Kiralık

        $this->assertGreaterThanOrEqual(15, $features->count(), 'Yazlık Kiralama must resolve at least 15 features');

        $slugs = $features->pluck('slug')->toArray();
        $this->assertContains('minimum-konaklama', $slugs);
        $this->assertContains('maksimum-misafir', $slugs);
        $this->assertContains('giris-saati', $slugs);
        $this->assertContains('cikis-saati', $slugs);
        $this->assertContains('temizlik-ucreti', $slugs);
        $this->assertContains('hasar-depozitosu', $slugs);
        $this->assertContains('havuz-bakimi', $slugs);
        $this->assertContains('evcil-hayvan-izni', $slugs);
        $this->assertContains('parti-etkinlik-izni', $slugs);

        $requiredSlugs = $features->where('required', true)->pluck('slug')->toArray();
        $this->assertContains('minimum-konaklama', $requiredSlugs);
        $this->assertContains('maksimum-misafir', $requiredSlugs);
        $this->assertContains('brut-alan', $requiredSlugs);
        $this->assertContains('oda-sayisi', $requiredSlugs);
    }

    /**
     * Category 5: Turistik Tesisler must resolve hospitality, capacity, and administrative fields.
     */
    public function test_turistik_tesisler_resolves_matrix_features(): void
    {
        $features = $this->resolver->resolveFeatures(5, 32, 1); // Satılık Otel

        $this->assertGreaterThanOrEqual(10, $features->count(), 'Turistik Tesisler must resolve at least 10 features');

        $slugs = $features->pluck('slug')->toArray();
        $this->assertContains('oda-sayisi-turistik', $slugs);
        $this->assertContains('yatak-kapasitesi', $slugs);
        $this->assertContains('yildiz-sayisi', $slugs);
        $this->assertContains('denize-mesafe-turistik', $slugs);
        $this->assertContains('acik-havuz-turistik', $slugs);
        $this->assertContains('restoran-bar', $slugs);
        $this->assertContains('turizm-belgesi', $slugs);

        $requiredSlugs = $features->where('required', true)->pluck('slug')->toArray();
        $this->assertContains('oda-sayisi-turistik', $requiredSlugs);
        $this->assertContains('yatak-kapasitesi', $requiredSlugs);
        $this->assertContains('turizm-belgesi', $requiredSlugs);
        $this->assertContains('brut-alan', $requiredSlugs);
    }

    /**
     * Category 6: Projeden Satış must resolve project timeline, unit count, and financial terms.
     */
    public function test_projeden_satis_resolves_matrix_features(): void
    {
        $features = $this->resolver->resolveFeatures(6, 23, 1); // Satılık Konut Projesi

        $this->assertGreaterThanOrEqual(10, $features->count(), 'Projeden Satış must resolve at least 10 features');

        $slugs = $features->pluck('slug')->toArray();
        $this->assertContains('toplam-unite-sayisi', $slugs);
        $this->assertContains('teslim-tarihi', $slugs);
        $this->assertContains('proje-alani-m2', $slugs);
        $this->assertContains('pesinat-orani', $slugs);
        $this->assertContains('vade-secenegi-ay', $slugs);
        $this->assertContains('insaat-tamamlanma-orani', $slugs);
        $this->assertContains('tapu-teslim-durumu', $slugs);

        $requiredSlugs = $features->where('required', true)->pluck('slug')->toArray();
        $this->assertContains('toplam-unite-sayisi', $requiredSlugs);
        $this->assertContains('teslim-tarihi', $requiredSlugs);
    }

    /**
     * Category 3: Arsa Kat Karşılığı (listing_type_id=3) resolves zoning & development fields.
     */
    public function test_arsa_kat_karsiligi_resolves_matrix_features(): void
    {
        $features = $this->resolver->resolveFeatures(3, 15, 3); // Kat Karşılığı Arsa

        $this->assertGreaterThanOrEqual(15, $features->count(), 'Arsa Kat Karşılığı must resolve at least 15 features');

        $slugs = $features->pluck('slug')->toArray();
        $this->assertContains('imar_durumu', $slugs);
        $this->assertContains('ada_no', $slugs);
        $this->assertContains('parsel_no', $slugs);
        $this->assertContains('pafta_no', $slugs);
        $this->assertContains('kaks', $slugs);
        $this->assertContains('taks', $slugs);
        $this->assertContains('gabari', $slugs);
        $this->assertContains('yola_cephe', $slugs);

        $requiredSlugs = $features->where('required', true)->pluck('slug')->toArray();
        $this->assertContains('imar_durumu', $requiredSlugs);
    }

    /**
     * All 6 main categories must NOT silently fall back to only the 5 generic global fields.
     */
    public function test_all_six_categories_avoid_generic_fallback(): void
    {
        $categoriesToTest = [
            ['name' => 'Konut (Villa Satılık)', 'main' => 1, 'sub' => 8, 'lt' => 1, 'min_count' => 20],
            ['name' => 'İşyeri (Ofis Satılık)', 'main' => 2, 'sub' => 11, 'lt' => 1, 'min_count' => 8],
            ['name' => 'Arsa (Satılık)', 'main' => 3, 'sub' => 15, 'lt' => 1, 'min_count' => 15],
            ['name' => 'Yazlık (Günlük Kiralık)', 'main' => 4, 'sub' => 26, 'lt' => 5, 'min_count' => 15],
            ['name' => 'Turistik (Satılık)', 'main' => 5, 'sub' => 32, 'lt' => 1, 'min_count' => 10],
            ['name' => 'Proje (Satılık)', 'main' => 6, 'sub' => 23, 'lt' => 1, 'min_count' => 10],
        ];

        foreach ($categoriesToTest as $case) {
            $resolved = $this->resolver->resolveFeatures($case['main'], $case['sub'], $case['lt']);

            $this->assertGreaterThan(
                5,
                $resolved->count(),
                "Category {$case['name']} must resolve more than 5 generic fallback fields, got {$resolved->count()}"
            );

            $this->assertGreaterThanOrEqual(
                $case['min_count'],
                $resolved->count(),
                "Category {$case['name']} should have at least {$case['min_count']} features"
            );
        }
    }

    /**
     * Seeder idempotency test: Running seeder twice leaves count unchanged and avoids duplicate errors.
     */
    public function test_category_feature_matrix_seeder_is_idempotent(): void
    {
        $countYazlikBefore = DB::table('feature_assignments')->where('main_category_id', 4)->count();
        $countTuristikBefore = DB::table('feature_assignments')->where('main_category_id', 5)->count();
        $countProjeBefore = DB::table('feature_assignments')->where('main_category_id', 6)->count();

        $seeder = new CategoryFeatureMatrixSeeder;
        $seeder->run();

        $this->assertSame($countYazlikBefore, DB::table('feature_assignments')->where('main_category_id', 4)->count());
        $this->assertSame($countTuristikBefore, DB::table('feature_assignments')->where('main_category_id', 5)->count());
        $this->assertSame($countProjeBefore, DB::table('feature_assignments')->where('main_category_id', 6)->count());
    }

    // ─── Prerequisites Seeding ──────────────────────────────────────────────

    private function seedPrerequisites(): void
    {
        $now = now();

        // 1. Publication Types
        $yayinTipleri = [
            ['id' => 1, 'name' => 'Satılık', 'slug' => 'satilik', 'aktiflik_durumu' => 1],
            ['id' => 2, 'name' => 'Kiralık', 'slug' => 'kiralik', 'aktiflik_durumu' => 1],
            ['id' => 3, 'name' => 'Kat Karşılığı', 'slug' => 'kat-karsiligi', 'aktiflik_durumu' => 1],
            ['id' => 4, 'name' => 'Devren', 'slug' => 'devren', 'aktiflik_durumu' => 1],
            ['id' => 5, 'name' => 'Günlük Kiralık', 'slug' => 'gunluk-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 6, 'name' => 'Haftalık Kiralık', 'slug' => 'haftalik-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 7, 'name' => 'Aylık Kiralık', 'slug' => 'aylik-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 8, 'name' => 'Sezonluk Kiralık', 'slug' => 'sezonluk-kiralik', 'aktiflik_durumu' => 1],
        ];
        foreach ($yayinTipleri as $yt) {
            DB::table('yayin_tipleri')->updateOrInsert(['id' => $yt['id']], array_merge($yt, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // 2. Categories
        $categories = [
            ['id' => 1, 'name' => 'Konut', 'slug' => 'konut', 'seviye' => 0, 'parent_id' => null],
            ['id' => 8, 'name' => 'Villa', 'slug' => 'villa', 'seviye' => 1, 'parent_id' => 1],
            ['id' => 2, 'name' => 'İşyeri', 'slug' => 'isyeri', 'seviye' => 0, 'parent_id' => null],
            ['id' => 11, 'name' => 'Ofis', 'slug' => 'ofis', 'seviye' => 1, 'parent_id' => 2],
            ['id' => 3, 'name' => 'Arsa & Arazi', 'slug' => 'arsa-arazi', 'seviye' => 0, 'parent_id' => null],
            ['id' => 15, 'name' => 'Arsa (Konut/Villa)', 'slug' => 'arsa-konut-villa', 'seviye' => 1, 'parent_id' => 3],
            ['id' => 4, 'name' => 'Yazlık Kiralama', 'slug' => 'yazlik-kiralama', 'seviye' => 0, 'parent_id' => null],
            ['id' => 26, 'name' => 'Yazlık Villa', 'slug' => 'yazlik-villa', 'seviye' => 1, 'parent_id' => 4],
            ['id' => 5, 'name' => 'Turistik Tesisler', 'slug' => 'turistik-tesisler', 'seviye' => 0, 'parent_id' => null],
            ['id' => 32, 'name' => 'Otel', 'slug' => 'otel', 'seviye' => 1, 'parent_id' => 5],
            ['id' => 6, 'name' => 'Projeden Satış', 'slug' => 'projeden-satis', 'seviye' => 0, 'parent_id' => null],
            ['id' => 23, 'name' => 'Konut Projesi', 'slug' => 'konut-projesi', 'seviye' => 1, 'parent_id' => 6],
        ];
        foreach ($categories as $cat) {
            DB::table('ilan_kategorileri')->updateOrInsert(['id' => $cat['id']], array_merge($cat, [
                'tenant_id' => 'SYSTEM',
                'aktiflik_durumu' => 1,
                'display_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // 2b. Templates for FeatureAssignmentSeeder
        $templates = [
            ['slug' => 'villa-satilik', 'ad' => 'Villa Satilik', 'kategori_id' => 8],
            ['slug' => 'villa-kiralik', 'ad' => 'Villa Kiralik', 'kategori_id' => 8],
            ['slug' => 'villa-gunluk', 'ad' => 'Villa Gunluk', 'kategori_id' => 8],
            ['slug' => 'konut-satilik', 'ad' => 'Konut Satilik', 'kategori_id' => 1],
            ['slug' => 'konut-kiralik', 'ad' => 'Konut Kiralik', 'kategori_id' => 1],
        ];

        foreach ($templates as $tpl) {
            DB::table('yayin_tipi_sablonlari')->updateOrInsert(
                ['slug' => $tpl['slug']],
                [
                    'tenant_id' => 'SYSTEM',
                    'ad' => $tpl['ad'],
                    'kategori_id' => $tpl['kategori_id'],
                    'aktiflik_durumu' => 1,
                    'display_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // 3. Common Base Features (needed for cross-category assignments)
        $baseFeatures = [
            ['name' => 'Brüt Alan', 'slug' => 'brut-alan', 'type' => 'number', 'unit' => 'm²'],
            ['name' => 'Oda Sayısı', 'slug' => 'oda-sayisi', 'type' => 'select'],
            ['name' => 'Banyo Sayısı', 'slug' => 'banyo-sayisi', 'type' => 'number'],
            ['name' => 'Havuz', 'slug' => 'havuz', 'type' => 'boolean'],
            ['name' => 'Denize Mesafe', 'slug' => 'denize-mesafe', 'type' => 'select'],
            ['name' => 'Eşyalı', 'slug' => 'esyali', 'type' => 'boolean'],
            ['name' => 'İmar Durumu', 'slug' => 'imar_durumu', 'type' => 'select'],
            ['name' => 'Ada No', 'slug' => 'ada_no', 'type' => 'text'],
            ['name' => 'Parsel No', 'slug' => 'parsel_no', 'type' => 'text'],
            ['name' => 'Pafta No', 'slug' => 'pafta_no', 'type' => 'text'],
            ['name' => 'KAKS (Emsal)', 'slug' => 'kaks', 'type' => 'text'],
            ['name' => 'TAKS', 'slug' => 'taks', 'type' => 'text'],
            ['name' => 'Gabari', 'slug' => 'gabari', 'type' => 'text'],
            ['name' => 'Yola Cephe', 'slug' => 'yola_cephe', 'type' => 'text'],
            ['name' => 'Su Altyapısı', 'slug' => 'altyapi_su', 'type' => 'boolean'],
            ['name' => 'Elektrik Altyapısı', 'slug' => 'altyapi_elektrik', 'type' => 'boolean'],
            ['name' => 'Yol Altyapısı', 'slug' => 'altyapi_yol', 'type' => 'boolean'],
            ['name' => 'Arsa Tapu Durumu', 'slug' => 'tapu_durumu_arsa', 'type' => 'select'],
        ];

        foreach ($baseFeatures as $bf) {
            DB::table('features')->updateOrInsert(
                ['slug' => $bf['slug']],
                array_merge($bf, [
                    'aktiflik_durumu' => 1,
                    'lifecycle' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
