<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CategoryFeatureMatrixSeeder — Priority 6 Implementation
 *
 * Implements the full Category × Feature Template Matrix across all 6 main categories:
 *   1. Konut (already seeded by FeatureAssignmentSeeder)
 *   2. İşyeri (Satılık, Kiralık, Devren)
 *   3. Arsa & Arazi (Satılık, Kiralık, Kat Karşılığı)
 *   4. Yazlık Kiralama (Günlük, Haftalık, Aylık, Sezonluk)
 *   5. Turistik Tesisler (Satılık, Devren, Kiralık)
 *   6. Projeden Satış (Satılık)
 *
 * Eliminates silent fallback to 5 generic fields for categories 4, 5, and 6.
 *
 * Idempotent via updateOrInsert.
 * Tagged with source_type = 'matrix_seed_2026_09'
 */
class CategoryFeatureMatrixSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFeatureCategories();
        $this->seedFeatures();
        $this->seedYazlikKiralamaAssignments();
        $this->seedTuristikTesisAssignments();
        $this->seedProjedenSatisAssignments();
        $this->seedArsaKatKarsiligiAssignments();

        if ($this->command) {
            $yazlikCount = DB::table('feature_assignments')->where('main_category_id', 4)->count();
            $turistikCount = DB::table('feature_assignments')->where('main_category_id', 5)->count();
            $projeCount = DB::table('feature_assignments')->where('main_category_id', 6)->count();
            $this->command->info('✅ CategoryFeatureMatrixSeeder completed:');
            $this->command->info("   Yazlık Kiralama: {$yazlikCount} assignments");
            $this->command->info("   Turistik Tesisler: {$turistikCount} assignments");
            $this->command->info("   Projeden Satış: {$projeCount} assignments");
        }
    }

    // ─── FEATURE CATEGORIES ─────────────────────────────────────────────────

    private function seedFeatureCategories(): void
    {
        $categories = [
            ['name' => 'Yazlık Operasyonel', 'slug' => 'yazlik-operasyonel', 'description' => 'Giriş, çıkış, min konaklama, kapasite', 'applies_to' => 'yazlik', 'icon' => 'calendar', 'display_order' => 10],
            ['name' => 'Yazlık Finansal', 'slug' => 'yazlik-finansal', 'description' => 'Temizlik ücreti, hasar depozitosu', 'applies_to' => 'yazlik', 'icon' => 'credit-card', 'display_order' => 11],
            ['name' => 'Yazlık Kurallar', 'slug' => 'yazlik-kurallar', 'description' => 'Evcil hayvan, parti izni, havuz bakımı', 'applies_to' => 'yazlik', 'icon' => 'shield-check', 'display_order' => 12],
            ['name' => 'Turistik Temel', 'slug' => 'turistik-temel', 'description' => 'Oda sayısı, yatak kapasitesi, yıldız', 'applies_to' => 'turistik', 'icon' => 'hotel', 'display_order' => 13],
            ['name' => 'Turistik Tesis Özellikleri', 'slug' => 'turistik-ozellikler', 'description' => 'Havuz, restoran, plaj mesafesi', 'applies_to' => 'turistik', 'icon' => 'coffee', 'display_order' => 14],
            ['name' => 'Turistik İdari & Ruhsat', 'slug' => 'turistik-idari', 'description' => 'Turizm işletme belgesi, belediye ruhsatı', 'applies_to' => 'turistik', 'icon' => 'file-check', 'display_order' => 15],
            ['name' => 'Proje Temel', 'slug' => 'proje-temel', 'description' => 'Toplam ünite sayısı, proje alanı, teslim tarihi', 'applies_to' => 'proje', 'icon' => 'layout-grid', 'display_order' => 16],
            ['name' => 'Proje Finansal & Ödeme', 'slug' => 'proje-finansal', 'description' => 'Peşinat oranı, vade seçeneği, taksit', 'applies_to' => 'proje', 'icon' => 'banknote', 'display_order' => 17],
            ['name' => 'Proje İnşaat Durumu', 'slug' => 'proje-insaat', 'description' => 'İnşaat tamamlama yüzdesi, tapu teslim durumu', 'applies_to' => 'proje', 'icon' => 'hammer', 'display_order' => 18],
        ];

        foreach ($categories as $cat) {
            DB::table('feature_categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                array_merge($cat, [
                    'aktiflik_durumu' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    // ─── FEATURES ───────────────────────────────────────────────────────────

    private function seedFeatures(): void
    {
        $cat = fn (string $slug) => DB::table('feature_categories')->where('slug', $slug)->value('id');

        $features = [
            // Yazlık Kiralama Özellikleri
            [
                'name' => 'Minimum Konaklama',
                'slug' => 'minimum-konaklama',
                'type' => 'number',
                'unit' => 'gece',
                'feature_category_id' => $cat('yazlik-operasyonel'),
                'is_required' => true,
                'is_filterable' => true,
                'is_searchable' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Maksimum Misafir Kapasitesi',
                'slug' => 'maksimum-misafir',
                'type' => 'number',
                'unit' => 'kişi',
                'feature_category_id' => $cat('yazlik-operasyonel'),
                'is_required' => true,
                'is_filterable' => true,
                'is_searchable' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Giriş Saati (Check-in)',
                'slug' => 'giris-saati',
                'type' => 'select',
                'unit' => null,
                'feature_category_id' => $cat('yazlik-operasyonel'),
                'is_required' => false,
                'is_filterable' => false,
                'is_searchable' => false,
                'display_order' => 3,
                'options' => json_encode(['14:00', '15:00', '16:00', 'Esnek']),
            ],
            [
                'name' => 'Çıkış Saati (Check-out)',
                'slug' => 'cikis-saati',
                'type' => 'select',
                'unit' => null,
                'feature_category_id' => $cat('yazlik-operasyonel'),
                'is_required' => false,
                'is_filterable' => false,
                'is_searchable' => false,
                'display_order' => 4,
                'options' => json_encode(['10:00', '11:00', '12:00', 'Esnek']),
            ],
            [
                'name' => 'Temizlik Ücreti',
                'slug' => 'temizlik-ucreti',
                'type' => 'number',
                'unit' => 'TL',
                'feature_category_id' => $cat('yazlik-finansal'),
                'is_required' => false,
                'is_filterable' => false,
                'is_searchable' => false,
                'display_order' => 1,
            ],
            [
                'name' => 'Hasar Depozitosu',
                'slug' => 'hasar-depozitosu',
                'type' => 'number',
                'unit' => 'TL',
                'feature_category_id' => $cat('yazlik-finansal'),
                'is_required' => false,
                'is_filterable' => false,
                'is_searchable' => false,
                'display_order' => 2,
            ],
            [
                'name' => 'Havuz Bakımı',
                'slug' => 'havuz-bakimi',
                'type' => 'select',
                'unit' => null,
                'feature_category_id' => $cat('yazlik-kurallar'),
                'is_required' => false,
                'is_filterable' => false,
                'is_searchable' => false,
                'display_order' => 1,
                'options' => json_encode(['Haftada 2 Kez (Ücretsiz)', 'Günlük (Ücretsiz)', 'Giriş Öncesi', 'Talep Üzerine']),
            ],
            [
                'name' => 'Evcil Hayvan İzni',
                'slug' => 'evcil-hayvan-izni',
                'type' => 'select',
                'unit' => null,
                'feature_category_id' => $cat('yazlik-kurallar'),
                'is_required' => false,
                'is_filterable' => true,
                'is_searchable' => false,
                'display_order' => 2,
                'options' => json_encode(['İzin Verilmez', 'İzin Verilir', 'Sadece Küçük Irk / Bahçede']),
            ],
            [
                'name' => 'Parti ve Etkinlik İzni',
                'slug' => 'parti-etkinlik-izni',
                'type' => 'select',
                'unit' => null,
                'feature_category_id' => $cat('yazlik-kurallar'),
                'is_required' => false,
                'is_filterable' => false,
                'is_searchable' => false,
                'display_order' => 3,
                'options' => json_encode(['Kesinlikle Yasak', 'İzin Verilir', 'Önceden Onay Gerekir']),
            ],

            // Turistik Tesis Özellikleri
            [
                'name' => 'Oda Sayısı (Tesis)',
                'slug' => 'oda-sayisi-turistik',
                'type' => 'number',
                'unit' => 'oda',
                'feature_category_id' => $cat('turistik-temel'),
                'is_required' => true,
                'is_filterable' => true,
                'is_searchable' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Yatak Kapasitesi',
                'slug' => 'yatak-kapasitesi',
                'type' => 'number',
                'unit' => 'yatak',
                'feature_category_id' => $cat('turistik-temel'),
                'is_required' => true,
                'is_filterable' => true,
                'is_searchable' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Yıldız Sayısı',
                'slug' => 'yildiz-sayisi',
                'type' => 'select',
                'unit' => null,
                'feature_category_id' => $cat('turistik-temel'),
                'is_required' => false,
                'is_filterable' => true,
                'is_searchable' => false,
                'display_order' => 3,
                'options' => json_encode(['Butik Otel', 'Pansiyon / Apart', '1 Yıldız', '2 Yıldız', '3 Yıldız', '4 Yıldız', '5 Yıldız', 'Tatil Köyü']),
            ],
            [
                'name' => 'Denize Mesafe (Turistik)',
                'slug' => 'denize-mesafe-turistik',
                'type' => 'select',
                'unit' => null,
                'feature_category_id' => $cat('turistik-ozellikler'),
                'is_required' => false,
                'is_filterable' => true,
                'is_searchable' => false,
                'display_order' => 1,
                'options' => json_encode(['Denize Sıfır', '50 - 100 m', '100 - 300 m', '300 - 500 m', '500 m - 1 km', '1 km+']),
            ],
            [
                'name' => 'Açık Yüzme Havuzu',
                'slug' => 'acik-havuz-turistik',
                'type' => 'boolean',
                'unit' => null,
                'feature_category_id' => $cat('turistik-ozellikler'),
                'is_required' => false,
                'is_filterable' => true,
                'is_searchable' => false,
                'display_order' => 2,
            ],
            [
                'name' => 'Restoran & Bar',
                'slug' => 'restoran-bar',
                'type' => 'boolean',
                'unit' => null,
                'feature_category_id' => $cat('turistik-ozellikler'),
                'is_required' => false,
                'is_filterable' => true,
                'is_searchable' => false,
                'display_order' => 3,
            ],
            [
                'name' => 'Turizm İşletme Belgesi',
                'slug' => 'turizm-belgesi',
                'type' => 'select',
                'unit' => null,
                'feature_category_id' => $cat('turistik-idari'),
                'is_required' => true,
                'is_filterable' => true,
                'is_searchable' => false,
                'display_order' => 1,
                'options' => json_encode(['Kültür ve Turizm Bakanlığı Belgeli', 'Belediye Ruhsatlı', 'Basit Konaklama Belgeli', 'Başvuru Aşamasında']),
            ],

            // Projeden Satış Özellikleri
            [
                'name' => 'Toplam Bağımsız Bölüm Sayısı',
                'slug' => 'toplam-unite-sayisi',
                'type' => 'number',
                'unit' => 'ünite',
                'feature_category_id' => $cat('proje-temel'),
                'is_required' => true,
                'is_filterable' => true,
                'is_searchable' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Teslim Tarihi',
                'slug' => 'teslim-tarihi',
                'type' => 'text',
                'unit' => null,
                'feature_category_id' => $cat('proje-temel'),
                'is_required' => true,
                'is_filterable' => true,
                'is_searchable' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Proje Alanı (m²)',
                'slug' => 'proje-alani-m2',
                'type' => 'number',
                'unit' => 'm²',
                'feature_category_id' => $cat('proje-temel'),
                'is_required' => false,
                'is_filterable' => false,
                'is_searchable' => false,
                'display_order' => 3,
            ],
            [
                'name' => 'Peşinat Oranı (%)',
                'slug' => 'pesinat-orani',
                'type' => 'number',
                'unit' => '%',
                'feature_category_id' => $cat('proje-finansal'),
                'is_required' => false,
                'is_filterable' => true,
                'is_searchable' => false,
                'display_order' => 1,
            ],
            [
                'name' => 'Vade / Taksit İmkanı (Ay)',
                'slug' => 'vade-secenegi-ay',
                'type' => 'number',
                'unit' => 'ay',
                'feature_category_id' => $cat('proje-finansal'),
                'is_required' => false,
                'is_filterable' => true,
                'is_searchable' => false,
                'display_order' => 2,
            ],
            [
                'name' => 'İnşaat Tamamlanma Oranı (%)',
                'slug' => 'insaat-tamamlanma-orani',
                'type' => 'number',
                'unit' => '%',
                'feature_category_id' => $cat('proje-insaat'),
                'is_required' => false,
                'is_filterable' => true,
                'is_searchable' => false,
                'display_order' => 1,
            ],
            [
                'name' => 'Tapu Teslim Durumu',
                'slug' => 'tapu-teslim-durumu',
                'type' => 'select',
                'unit' => null,
                'feature_category_id' => $cat('proje-insaat'),
                'is_required' => false,
                'is_filterable' => true,
                'is_searchable' => false,
                'display_order' => 2,
                'options' => json_encode(['Hemen Tapu Devri', 'Proje Tesliminde', 'Kat İrtifakı Hazır', 'İnşaat İlerlemesine Göre']),
            ],
        ];

        foreach ($features as $f) {
            DB::table('features')->updateOrInsert(
                ['slug' => $f['slug']],
                array_merge($f, [
                    'aktiflik_durumu' => 1,
                    'lifecycle' => 'active',
                    'updated_at' => now(),
                ])
            );
        }
    }

    // ─── ASSIGNMENTS HELPERS ────────────────────────────────────────────────

    private function assign(
        string $featureSlug,
        int $mainCategoryId,
        ?int $subCategoryId,
        ?int $listingTypeId,
        string $groupName,
        bool $isRequired,
        bool $isVisible,
        int $displayOrder
    ): void {
        $featureId = DB::table('features')->where('slug', $featureSlug)->value('id');
        if (! $featureId) {
            return;
        }

        $exists = DB::table('feature_assignments')
            ->where('feature_id', $featureId)
            ->where('main_category_id', $mainCategoryId)
            ->where('sub_category_id', $subCategoryId)
            ->where('listing_type_id', $listingTypeId)
            ->exists();

        $scopeType = $listingTypeId ? 'listing_type' : ($subCategoryId ? 'sub_category' : 'main_category');

        if ($exists) {
            DB::table('feature_assignments')
                ->where('feature_id', $featureId)
                ->where('main_category_id', $mainCategoryId)
                ->where('sub_category_id', $subCategoryId)
                ->where('listing_type_id', $listingTypeId)
                ->update([
                    'assignable_type' => 'App\\Models\\IlanKategori',
                    'assignable_id' => $mainCategoryId,
                    'scope_type' => $scopeType,
                    'source_type' => 'matrix_seed_2026_09',
                    'group_name' => $groupName,
                    'field_slug' => $featureSlug,
                    'is_required' => $isRequired,
                    'is_visible' => $isVisible,
                    'aktiflik_durumu' => 1,
                    'display_order' => $displayOrder,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('feature_assignments')->insert([
                'feature_id' => $featureId,
                'main_category_id' => $mainCategoryId,
                'sub_category_id' => $subCategoryId,
                'listing_type_id' => $listingTypeId,
                'assignable_type' => 'App\\Models\\IlanKategori',
                'assignable_id' => $mainCategoryId,
                'scope_type' => $scopeType,
                'source_type' => 'matrix_seed_2026_09',
                'group_name' => $groupName,
                'field_slug' => $featureSlug,
                'is_required' => $isRequired,
                'is_visible' => $isVisible,
                'aktiflik_durumu' => 1,
                'display_order' => $displayOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // ─── YAZLIK KİRALAMA ASSIGNMENTS (main=4) ────────────────────────────────

    private function seedYazlikKiralamaAssignments(): void
    {
        $mainCatId = 4; // Yazlık Kiralama

        // Main category level assignments (All subcategories inherit these)
        $fields = [
            // Operasyonel
            ['minimum-konaklama', 'Yazlık Operasyonel', true, true, 1],
            ['maksimum-misafir', 'Yazlık Operasyonel', true, true, 2],
            ['giris-saati', 'Yazlık Operasyonel', false, true, 3],
            ['cikis-saati', 'Yazlık Operasyonel', false, true, 4],

            // Finansal
            ['temizlik-ucreti', 'Yazlık Finansal', false, true, 1],
            ['hasar-depozitosu', 'Yazlık Finansal', false, true, 2],

            // Kurallar
            ['havuz-bakimi', 'Yazlık Kurallar', false, true, 1],
            ['evcil-hayvan-izni', 'Yazlık Kurallar', false, true, 2],
            ['parti-etkinlik-izni', 'Yazlık Kurallar', false, true, 3],

            // Konut genel özelliklerinden gerekli olanlar
            ['brut-alan', 'Temel Bilgiler', true, true, 1],
            ['oda-sayisi', 'Temel Bilgiler', true, true, 2],
            ['banyo-sayisi', 'Temel Bilgiler', false, true, 3],
            ['havuz', 'Yapı Özellikleri', false, true, 1],
            ['denize-mesafe', 'Konum ve Çevre', false, true, 1],
            ['esyali', 'İç Özellikler', false, true, 1],
        ];

        foreach ($fields as [$slug, $group, $req, $vis, $order]) {
            $this->assign($slug, $mainCatId, null, null, $group, $req, $vis, $order);
        }
    }

    // ─── TURİSTİK TESİSLER ASSIGNMENTS (main=5) ──────────────────────────────

    private function seedTuristikTesisAssignments(): void
    {
        $mainCatId = 5; // Turistik Tesisler

        $fields = [
            ['oda-sayisi-turistik', 'Turistik Temel', true, true, 1],
            ['yatak-kapasitesi', 'Turistik Temel', true, true, 2],
            ['yildiz-sayisi', 'Turistik Temel', false, true, 3],
            ['denize-mesafe-turistik', 'Turistik Tesis Özellikleri', false, true, 1],
            ['acik-havuz-turistik', 'Turistik Tesis Özellikleri', false, true, 2],
            ['restoran-bar', 'Turistik Tesis Özellikleri', false, true, 3],
            ['turizm-belgesi', 'Turistik İdari & Ruhsat', true, true, 1],
            ['brut-alan', 'Temel Bilgiler', true, true, 1],
        ];

        foreach ($fields as [$slug, $group, $req, $vis, $order]) {
            $this->assign($slug, $mainCatId, null, null, $group, $req, $vis, $order);
        }
    }

    // ─── PROJEDEN SATIŞ ASSIGNMENTS (main=6) ────────────────────────────────

    private function seedProjedenSatisAssignments(): void
    {
        $mainCatId = 6; // Projeden Satış

        $fields = [
            ['toplam-unite-sayisi', 'Proje Temel', true, true, 1],
            ['teslim-tarihi', 'Proje Temel', true, true, 2],
            ['proje-alani-m2', 'Proje Temel', false, true, 3],
            ['pesinat-orani', 'Proje Finansal & Ödeme', false, true, 1],
            ['vade-secenegi-ay', 'Proje Finansal & Ödeme', false, true, 2],
            ['insaat-tamamlanma-orani', 'Proje İnşaat Durumu', false, true, 1],
            ['tapu-teslim-durumu', 'Proje İnşaat Durumu', false, true, 2],
        ];

        foreach ($fields as [$slug, $group, $req, $vis, $order]) {
            $this->assign($slug, $mainCatId, null, null, $group, $req, $vis, $order);
        }
    }

    // ─── ARSA KAT KARŞILIĞI (main=3, lt=3) ───────────────────────────────────

    private function seedArsaKatKarsiligiAssignments(): void
    {
        $mainCatId = 3; // Arsa & Arazi
        $listingTypeId = 3; // Kat Karşılığı

        $fields = [
            ['ada_no', 'Arsa Temel', false, true, 1],
            ['parsel_no', 'Arsa Temel', false, true, 2],
            ['pafta_no', 'Arsa Temel', false, true, 3],
            ['imar_durumu', 'Arsa Temel', true, true, 4],
            ['kaks', 'Arsa Fiziksel', false, true, 1],
            ['taks', 'Arsa Fiziksel', false, true, 2],
            ['gabari', 'Arsa Fiziksel', false, true, 3],
            ['yola_cephe', 'Arsa Fiziksel', false, true, 4],
            ['altyapi_su', 'Altyapı', false, true, 1],
            ['altyapi_elektrik', 'Altyapı', false, true, 2],
            ['altyapi_yol', 'Altyapı', false, true, 3],
            ['tapu_durumu_arsa', 'Arsa Finansal', false, true, 1],
        ];

        foreach ($fields as [$slug, $group, $req, $vis, $order]) {
            $this->assign($slug, $mainCatId, null, $listingTypeId, $group, $req, $vis, $order);
        }
    }
}
