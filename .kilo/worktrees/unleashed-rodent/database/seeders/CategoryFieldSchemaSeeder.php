<?php

namespace Database\Seeders;

use App\Models\KategoriYayinTipiFieldDependency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * CategoryFieldSchemaSeeder — Wizard Engine V2
 *
 * Tüm ana kategori vertikalleri için field definitions seed'ler.
 * SSOT: kategori_yayin_tipi_field_dependencies tablosu.
 *
 * Kullanım:
 *   php artisan db:seed --class=CategoryFieldSchemaSeeder
 *
 * İdempotent: Mevcut kayıtları updateOrCreate ile günceller.
 * Context7 Compliant: Tüm alan adları Türkçe context7 standardında.
 *
 * @version 2.0.0
 */
class CategoryFieldSchemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $created = 0;
        $updated = 0;

        $allDefinitions = array_merge(
            $this->konutSatilik(),
            $this->konutKiralik(),
            $this->arsaSatilik(),
            $this->isyeriSatilik(),
            $this->isyeriKiralik(),
            $this->yazlikGunlukKiralama(),
        );

        foreach ($allDefinitions as $def) {
            $field = KategoriYayinTipiFieldDependency::updateOrCreate(
                [
                    'kategori_slug' => $def['kategori_slug'],
                    'yayin_tipi' => $def['yayin_tipi'],
                    'field_slug' => $def['field_slug'],
                ],
                $def
            );

            if ($field->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->command?->info("✅ CategoryFieldSchemaSeeder: {$created} created, {$updated} updated. Total: " . count($allDefinitions));
        Log::info('CategoryFieldSchemaSeeder completed', [
            'created' => $created,
            'updated' => $updated,
            'total' => count($allDefinitions),
        ]);
    }

    /**
     * 🏠 Konut — Satılık
     */
    private function konutSatilik(): array
    {
        $base = ['kategori_slug' => 'konut', 'yayin_tipi' => 'satilik', 'aktiflik_durumu' => 1];

        return [
            // Temel Bilgiler
            $base + [
                'field_slug' => 'oda_sayisi',
                'field_name' => 'Oda Sayısı',
                'field_type' => 'select',
                'field_category' => 'temel',
                'field_icon' => '🛏️',
                'required' => true,
                'display_order' => 10,
                'ai_auto_fill' => true,
                'ai_suggestion' => false,
                'ai_prompt_key' => 'room_count',
                'searchable' => true,
                'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    '1+0', '1+1', '2+1', '2+2', '3+1', '3+2', '4+1', '4+2', '5+1', '5+2', '6+', '7+',
                ]]),
            ],
            $base + [
                'field_slug' => 'banyo_sayisi',
                'field_name' => 'Banyo Sayısı',
                'field_type' => 'select',
                'field_category' => 'temel',
                'field_icon' => '🚿',
                'required' => true,
                'display_order' => 20,
                'ai_auto_fill' => true,
                'ai_suggestion' => false,
                'searchable' => true,
                'show_in_card' => true,
                'field_options' => json_encode(['items' => ['1', '2', '3', '4', '5+']]),
            ],
            $base + [
                'field_slug' => 'net_m2',
                'field_name' => 'Net m²',
                'field_type' => 'number',
                'field_category' => 'fiziksel',
                'field_icon' => '📐',
                'field_unit' => 'm²',
                'required' => true,
                'display_order' => 30,
                'ai_auto_fill' => false,
                'ai_suggestion' => false,
                'searchable' => true,
                'show_in_card' => true,
                'field_options' => json_encode(['min' => 10, 'max' => 2000, 'step' => 1]),
            ],
            $base + [
                'field_slug' => 'brut_m2',
                'field_name' => 'Brüt m²',
                'field_type' => 'number',
                'field_category' => 'fiziksel',
                'field_icon' => '📏',
                'field_unit' => 'm²',
                'required' => false,
                'display_order' => 40,
                'ai_auto_fill' => false,
                'ai_suggestion' => false,
                'searchable' => true,
                'show_in_card' => false,
                'field_options' => json_encode(['min' => 10, 'max' => 3000, 'step' => 1]),
            ],

            // Fiziksel Özellikler
            $base + [
                'field_slug' => 'bulundugu_kat',
                'field_name' => 'Bulunduğu Kat',
                'field_type' => 'select',
                'field_category' => 'fiziksel',
                'field_icon' => '🏢',
                'required' => true,
                'display_order' => 50,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    'Bodrum', 'Zemin', 'Bahçe Katı', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
                    '11-15', '16-20', '21-25', '26-30', '30+', 'Çatı Katı', 'Dubleks', 'Tripleks',
                ]]),
            ],
            $base + [
                'field_slug' => 'toplam_kat',
                'field_name' => 'Toplam Kat Sayısı',
                'field_type' => 'number',
                'field_category' => 'fiziksel',
                'field_icon' => '🏗️',
                'required' => false,
                'display_order' => 60,
                'ai_auto_fill' => true,
                'searchable' => false,
                'show_in_card' => false,
                'field_options' => json_encode(['min' => 1, 'max' => 50, 'step' => 1]),
            ],
            $base + [
                'field_slug' => 'bina_yasi',
                'field_name' => 'Bina Yaşı',
                'field_type' => 'select',
                'field_category' => 'fiziksel',
                'field_icon' => '🏛️',
                'required' => false,
                'display_order' => 70,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    '0 (Sıfır)', '1-5', '6-10', '11-15', '16-20', '21-25', '26-30', '31+',
                ]]),
            ],

            // Altyapı
            $base + [
                'field_slug' => 'isitma_tipi',
                'field_name' => 'Isıtma Tipi',
                'field_type' => 'select',
                'field_category' => 'altyapi',
                'field_icon' => '🔥',
                'required' => true,
                'display_order' => 80,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    'Doğalgaz (Kombi)', 'Doğalgaz (Kat Kaloriferli)', 'Merkezi Sistem',
                    'Merkezi Sistem (Pay Ölçer)', 'Yerden Isıtma', 'Klima',
                    'Soba', 'Güneş Enerjisi', 'Jeotermal', 'Isı Pompası', 'Yok',
                ]]),
            ],
            $base + [
                'field_slug' => 'esyali',
                'field_name' => 'Eşyalı mı?',
                'field_type' => 'boolean',
                'field_category' => 'altyapi',
                'field_icon' => '🪑',
                'required' => false,
                'display_order' => 90,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => true,
            ],

            // Finansal
            $base + [
                'field_slug' => 'aidat',
                'field_name' => 'Aidat',
                'field_type' => 'number',
                'field_category' => 'finansal',
                'field_icon' => '💰',
                'field_unit' => 'TL/ay',
                'required' => false,
                'display_order' => 100,
                'ai_auto_fill' => false,
                'searchable' => true,
                'show_in_card' => true,
                'field_options' => json_encode(['min' => 0, 'max' => 100000, 'step' => 50]),
            ],
            $base + [
                'field_slug' => 'tapu_durumu',
                'field_name' => 'Tapu Durumu',
                'field_type' => 'select',
                'field_category' => 'finansal',
                'field_icon' => '📜',
                'required' => false,
                'display_order' => 110,
                'ai_auto_fill' => false,
                'searchable' => true,
                'show_in_card' => false,
                'field_options' => json_encode(['items' => [
                    'Kat Mülkiyetli', 'Kat İrtifaklı', 'Hisseli Tapu', 'Müstakil Tapu',
                    'Arsa Tapulu', 'Kooperatif', 'Tapu Kaydı Yok',
                ]]),
            ],

            // Ek Özellikler
            $base + [
                'field_slug' => 'balkon',
                'field_name' => 'Balkon',
                'field_type' => 'boolean',
                'field_category' => 'ek_ozellikler',
                'field_icon' => '🌅',
                'required' => false,
                'display_order' => 120,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => false,
            ],
            $base + [
                'field_slug' => 'otopark',
                'field_name' => 'Otopark',
                'field_type' => 'select',
                'field_category' => 'ek_ozellikler',
                'field_icon' => '🚗',
                'required' => false,
                'display_order' => 130,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    'Açık Otopark', 'Kapalı Otopark', 'Açık & Kapalı', 'Yok',
                ]]),
            ],
            $base + [
                'field_slug' => 'asansor',
                'field_name' => 'Asansör',
                'field_type' => 'boolean',
                'field_category' => 'ek_ozellikler',
                'field_icon' => '🛗',
                'required' => false,
                'display_order' => 140,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => false,
            ],
            $base + [
                'field_slug' => 'site_icinde',
                'field_name' => 'Site İçinde',
                'field_type' => 'boolean',
                'field_category' => 'ek_ozellikler',
                'field_icon' => '🏘️',
                'required' => false,
                'display_order' => 150,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => true,
            ],
            $base + [
                'field_slug' => 'havuz',
                'field_name' => 'Havuz',
                'field_type' => 'boolean',
                'field_category' => 'ek_ozellikler',
                'field_icon' => '🏊',
                'required' => false,
                'display_order' => 160,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => true,
            ],
            $base + [
                'field_slug' => 'guvenlik',
                'field_name' => 'Güvenlik',
                'field_type' => 'boolean',
                'field_category' => 'ek_ozellikler',
                'field_icon' => '🔒',
                'required' => false,
                'display_order' => 170,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => false,
            ],
        ];
    }

    /**
     * 🏠 Konut — Kiralık
     */
    private function konutKiralik(): array
    {
        $base = ['kategori_slug' => 'konut', 'yayin_tipi' => 'kiralik', 'aktiflik_durumu' => 1];

        // Kiralık konut: satılıkla aynı fiziksel özelliklere sahip + deposit/kiralama özel alanlar
        return [
            $base + [
                'field_slug' => 'oda_sayisi',
                'field_name' => 'Oda Sayısı',
                'field_type' => 'select',
                'field_category' => 'temel',
                'field_icon' => '🛏️',
                'required' => true,
                'display_order' => 10,
                'ai_auto_fill' => true,
                'searchable' => true,
                'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    '1+0', '1+1', '2+1', '2+2', '3+1', '3+2', '4+1', '4+2', '5+1', '5+2', '6+',
                ]]),
            ],
            $base + [
                'field_slug' => 'banyo_sayisi', 'field_name' => 'Banyo Sayısı', 'field_type' => 'select',
                'field_category' => 'temel', 'field_icon' => '🚿', 'required' => true, 'display_order' => 20,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['items' => ['1', '2', '3', '4', '5+']]),
            ],
            $base + [
                'field_slug' => 'net_m2', 'field_name' => 'Net m²', 'field_type' => 'number',
                'field_category' => 'fiziksel', 'field_icon' => '📐', 'field_unit' => 'm²', 'required' => true,
                'display_order' => 30, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['min' => 10, 'max' => 2000, 'step' => 1]),
            ],
            $base + [
                'field_slug' => 'bulundugu_kat', 'field_name' => 'Bulunduğu Kat', 'field_type' => 'select',
                'field_category' => 'fiziksel', 'field_icon' => '🏢', 'required' => true, 'display_order' => 40,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    'Bodrum', 'Zemin', 'Bahçe Katı', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
                    '11-15', '16-20', '21-25', '26-30', '30+', 'Çatı Katı', 'Dubleks',
                ]]),
            ],
            $base + [
                'field_slug' => 'isitma_tipi', 'field_name' => 'Isıtma Tipi', 'field_type' => 'select',
                'field_category' => 'altyapi', 'field_icon' => '🔥', 'required' => true, 'display_order' => 50,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    'Doğalgaz (Kombi)', 'Doğalgaz (Kat Kaloriferli)', 'Merkezi Sistem',
                    'Klima', 'Soba', 'Yok',
                ]]),
            ],
            $base + [
                'field_slug' => 'esyali', 'field_name' => 'Eşyalı mı?', 'field_type' => 'boolean',
                'field_category' => 'altyapi', 'field_icon' => '🪑', 'required' => false, 'display_order' => 55,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
            ],

            // Kiralık özel alanlar
            $base + [
                'field_slug' => 'depozito', 'field_name' => 'Depozito', 'field_type' => 'number',
                'field_category' => 'finansal', 'field_icon' => '🏦', 'field_unit' => 'TL', 'required' => false,
                'display_order' => 60, 'searchable' => false, 'show_in_card' => true,
                'field_options' => json_encode(['min' => 0, 'max' => 500000, 'step' => 500]),
            ],
            $base + [
                'field_slug' => 'aidat', 'field_name' => 'Aidat', 'field_type' => 'number',
                'field_category' => 'finansal', 'field_icon' => '💰', 'field_unit' => 'TL/ay', 'required' => false,
                'display_order' => 70, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['min' => 0, 'max' => 50000, 'step' => 50]),
            ],
            $base + [
                'field_slug' => 'minimum_kira_suresi', 'field_name' => 'Minimum Kira Süresi', 'field_type' => 'select',
                'field_category' => 'kiralama', 'field_icon' => '📅', 'required' => false, 'display_order' => 80,
                'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['items' => ['1 ay', '3 ay', '6 ay', '1 yıl', '2 yıl']]),
            ],
        ];
    }

    /**
     * 🌳 Arsa — Satılık
     */
    private function arsaSatilik(): array
    {
        $base = ['kategori_slug' => 'arsa-arazi', 'yayin_tipi' => 'satilik', 'aktiflik_durumu' => 1];

        return [
            $base + [
                'field_slug' => 'ada_no', 'field_name' => 'Ada No', 'field_type' => 'text',
                'field_category' => 'temel', 'field_icon' => '📍', 'required' => false, 'display_order' => 10,
                'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['placeholder' => 'Ör: 234']),
            ],
            $base + [
                'field_slug' => 'parsel_no', 'field_name' => 'Parsel No', 'field_type' => 'text',
                'field_category' => 'temel', 'field_icon' => '📍', 'required' => false, 'display_order' => 20,
                'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['placeholder' => 'Ör: 12']),
            ],
            $base + [
                'field_slug' => 'pafta_no', 'field_name' => 'Pafta No', 'field_type' => 'text',
                'field_category' => 'temel', 'field_icon' => '📍', 'required' => false, 'display_order' => 25,
                'searchable' => false, 'show_in_card' => false,
            ],
            $base + [
                'field_slug' => 'imar_durumu', 'field_name' => 'İmar Durumu', 'field_type' => 'select',
                'field_category' => 'temel', 'field_icon' => '🏗️', 'required' => true, 'display_order' => 30,
                'ai_auto_fill' => false, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    'Konut İmarlı', 'Ticari İmarlı', 'Sanayi İmarlı', 'Tarla',
                    'Zeytinlik', 'Bağ & Bahçe', 'Turizm İmarlı', 'İmarsız',
                ]]),
            ],
            $base + [
                'field_slug' => 'kaks', 'field_name' => 'KAKS (Emsal)', 'field_type' => 'number',
                'field_category' => 'fiziksel', 'field_icon' => '📊', 'required' => false, 'display_order' => 40,
                'searchable' => false, 'show_in_card' => true,
                'field_options' => json_encode(['min' => 0, 'max' => 10, 'step' => 0.01]),
            ],
            $base + [
                'field_slug' => 'taks', 'field_name' => 'TAKS', 'field_type' => 'number',
                'field_category' => 'fiziksel', 'field_icon' => '📊', 'required' => false, 'display_order' => 50,
                'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['min' => 0, 'max' => 1, 'step' => 0.01]),
            ],
            $base + [
                'field_slug' => 'gabari', 'field_name' => 'Gabari (Kat)', 'field_type' => 'number',
                'field_category' => 'fiziksel', 'field_icon' => '🏢', 'required' => false, 'display_order' => 60,
                'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['min' => 1, 'max' => 50, 'step' => 1]),
            ],
            $base + [
                'field_slug' => 'yola_cephe', 'field_name' => 'Yola Cephe', 'field_type' => 'number',
                'field_category' => 'fiziksel', 'field_icon' => '🛤️', 'field_unit' => 'm', 'required' => false,
                'display_order' => 70, 'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['min' => 0, 'max' => 500, 'step' => 1]),
            ],
            $base + [
                'field_slug' => 'altyapi_su', 'field_name' => 'Su', 'field_type' => 'boolean',
                'field_category' => 'altyapi', 'field_icon' => '🚰', 'required' => false, 'display_order' => 80,
                'searchable' => false, 'show_in_card' => false,
            ],
            $base + [
                'field_slug' => 'altyapi_elektrik', 'field_name' => 'Elektrik', 'field_type' => 'boolean',
                'field_category' => 'altyapi', 'field_icon' => '⚡', 'required' => false, 'display_order' => 90,
                'searchable' => false, 'show_in_card' => false,
            ],
            $base + [
                'field_slug' => 'altyapi_dogalgaz', 'field_name' => 'Doğalgaz', 'field_type' => 'boolean',
                'field_category' => 'altyapi', 'field_icon' => '🔥', 'required' => false, 'display_order' => 100,
                'searchable' => false, 'show_in_card' => false,
            ],
            $base + [
                'field_slug' => 'altyapi_kanalizasyon', 'field_name' => 'Kanalizasyon', 'field_type' => 'boolean',
                'field_category' => 'altyapi', 'field_icon' => '🔧', 'required' => false, 'display_order' => 110,
                'searchable' => false, 'show_in_card' => false,
            ],
            $base + [
                'field_slug' => 'altyapi_yol', 'field_name' => 'Yol', 'field_type' => 'boolean',
                'field_category' => 'altyapi', 'field_icon' => '🛣️', 'required' => false, 'display_order' => 120,
                'searchable' => false, 'show_in_card' => false,
            ],
            $base + [
                'field_slug' => 'tapu_durumu', 'field_name' => 'Tapu Durumu', 'field_type' => 'select',
                'field_category' => 'finansal', 'field_icon' => '📜', 'required' => false, 'display_order' => 130,
                'searchable' => true, 'show_in_card' => false,
                'field_options' => json_encode(['items' => [
                    'Müstakil Tapu', 'Hisseli Tapu', 'Zilliyet', 'Tahsisli',
                ]]),
            ],
        ];
    }

    /**
     * 🏢 İşyeri — Satılık
     */
    private function isyeriSatilik(): array
    {
        $base = ['kategori_slug' => 'isyeri', 'yayin_tipi' => 'satilik', 'aktiflik_durumu' => 1];

        return [
            $base + [
                'field_slug' => 'isyeri_tipi', 'field_name' => 'İşyeri Tipi', 'field_type' => 'select',
                'field_category' => 'temel', 'field_icon' => '🏪', 'required' => true, 'display_order' => 10,
                'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    'Dükkan', 'Mağaza', 'Ofis', 'Büro', 'Depo', 'Fabrika', 'Atölye', 'Showroom', 'Plaza Katı',
                ]]),
            ],
            $base + [
                'field_slug' => 'net_m2', 'field_name' => 'Net m²', 'field_type' => 'number',
                'field_category' => 'fiziksel', 'field_icon' => '📐', 'field_unit' => 'm²', 'required' => true,
                'display_order' => 20, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['min' => 10, 'max' => 50000, 'step' => 1]),
            ],
            $base + [
                'field_slug' => 'bulundugu_kat', 'field_name' => 'Bulunduğu Kat', 'field_type' => 'select',
                'field_category' => 'fiziksel', 'field_icon' => '🏢', 'required' => false, 'display_order' => 30,
                'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    'Bodrum', 'Zemin', '1', '2', '3', '4', '5+', 'Çatı Katı',
                ]]),
            ],
            $base + [
                'field_slug' => 'cephe', 'field_name' => 'Cephe', 'field_type' => 'select',
                'field_category' => 'fiziksel', 'field_icon' => '🧭', 'required' => false, 'display_order' => 40,
                'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['items' => ['Cadde Cepheli', 'Sokak Cepheli', 'AVM İçi', 'İç Cephe']]),
            ],
            $base + [
                'field_slug' => 'personel_kapasitesi', 'field_name' => 'Personel Kapasitesi', 'field_type' => 'number',
                'field_category' => 'isyeri', 'field_icon' => '👥', 'required' => false, 'display_order' => 50,
                'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['min' => 1, 'max' => 1000]),
            ],
            $base + [
                'field_slug' => 'aidat', 'field_name' => 'Aidat', 'field_type' => 'number',
                'field_category' => 'finansal', 'field_icon' => '💰', 'field_unit' => 'TL/ay', 'required' => false,
                'display_order' => 60, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['min' => 0, 'max' => 500000, 'step' => 100]),
            ],
        ];
    }

    /**
     * 🏢 İşyeri — Kiralık
     */
    private function isyeriKiralik(): array
    {
        $base = ['kategori_slug' => 'isyeri', 'yayin_tipi' => 'kiralik', 'aktiflik_durumu' => 1];

        return [
            $base + [
                'field_slug' => 'isyeri_tipi', 'field_name' => 'İşyeri Tipi', 'field_type' => 'select',
                'field_category' => 'temel', 'field_icon' => '🏪', 'required' => true, 'display_order' => 10,
                'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    'Dükkan', 'Mağaza', 'Ofis', 'Büro', 'Depo', 'Atölye', 'Showroom', 'Plaza Katı',
                ]]),
            ],
            $base + [
                'field_slug' => 'net_m2', 'field_name' => 'Net m²', 'field_type' => 'number',
                'field_category' => 'fiziksel', 'field_icon' => '📐', 'field_unit' => 'm²', 'required' => true,
                'display_order' => 20, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['min' => 10, 'max' => 50000, 'step' => 1]),
            ],
            $base + [
                'field_slug' => 'depozito', 'field_name' => 'Depozito', 'field_type' => 'number',
                'field_category' => 'finansal', 'field_icon' => '🏦', 'field_unit' => 'TL', 'required' => false,
                'display_order' => 30, 'searchable' => false, 'show_in_card' => true,
                'field_options' => json_encode(['min' => 0, 'max' => 5000000, 'step' => 1000]),
            ],
            $base + [
                'field_slug' => 'aidat', 'field_name' => 'Aidat', 'field_type' => 'number',
                'field_category' => 'finansal', 'field_icon' => '💰', 'field_unit' => 'TL/ay', 'required' => false,
                'display_order' => 40, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['min' => 0, 'max' => 500000, 'step' => 100]),
            ],
        ];
    }

    /**
     * 🏖️ Yazlık — Günlük Kiralama
     */
    private function yazlikGunlukKiralama(): array
    {
        $base = ['kategori_slug' => 'yazlik-kiralama', 'yayin_tipi' => 'gunluk-kiralama', 'aktiflik_durumu' => 1];

        return [
            $base + [
                'field_slug' => 'oda_sayisi', 'field_name' => 'Oda Sayısı', 'field_type' => 'select',
                'field_category' => 'temel', 'field_icon' => '🛏️', 'required' => true, 'display_order' => 10,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['items' => [
                    '1+0', '1+1', '2+1', '3+1', '3+2', '4+1', '4+2', '5+1', '6+',
                ]]),
            ],
            $base + [
                'field_slug' => 'banyo_sayisi', 'field_name' => 'Banyo Sayısı', 'field_type' => 'select',
                'field_category' => 'temel', 'field_icon' => '🚿', 'required' => true, 'display_order' => 20,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['items' => ['1', '2', '3', '4', '5+']]),
            ],
            $base + [
                'field_slug' => 'max_misafir', 'field_name' => 'Maksimum Misafir', 'field_type' => 'number',
                'field_category' => 'kiralama', 'field_icon' => '👨‍👩‍👧‍👦', 'required' => true, 'display_order' => 30,
                'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['min' => 1, 'max' => 30, 'step' => 1]),
            ],
            $base + [
                'field_slug' => 'min_konaklama_gece', 'field_name' => 'Minimum Konaklama', 'field_type' => 'number',
                'field_category' => 'kiralama', 'field_icon' => '🌙', 'field_unit' => 'gece', 'required' => false,
                'display_order' => 40, 'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['min' => 1, 'max' => 30, 'step' => 1]),
            ],
            $base + [
                'field_slug' => 'havuz_var', 'field_name' => 'Havuz', 'field_type' => 'select',
                'field_category' => 'ek_ozellikler', 'field_icon' => '🏊', 'required' => false, 'display_order' => 50,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
                'field_options' => json_encode(['items' => ['Özel Havuz', 'Ortak Havuz', 'Yok']]),
            ],
            $base + [
                'field_slug' => 'check_in_saati', 'field_name' => 'Giriş Saati', 'field_type' => 'select',
                'field_category' => 'kiralama', 'field_icon' => '🔑', 'required' => false, 'display_order' => 60,
                'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['items' => ['12:00', '13:00', '14:00', '15:00', '16:00', 'Esnek']]),
            ],
            $base + [
                'field_slug' => 'check_out_saati', 'field_name' => 'Çıkış Saati', 'field_type' => 'select',
                'field_category' => 'kiralama', 'field_icon' => '🚪', 'required' => false, 'display_order' => 70,
                'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['items' => ['09:00', '10:00', '11:00', '12:00', 'Esnek']]),
            ],
            $base + [
                'field_slug' => 'temizlik_ucreti', 'field_name' => 'Temizlik Ücreti', 'field_type' => 'number',
                'field_category' => 'finansal', 'field_icon' => '🧹', 'field_unit' => 'TL', 'required' => false,
                'display_order' => 80, 'searchable' => false, 'show_in_card' => false,
                'field_options' => json_encode(['min' => 0, 'max' => 10000, 'step' => 50]),
            ],
            $base + [
                'field_slug' => 'klima', 'field_name' => 'Klima', 'field_type' => 'boolean',
                'field_category' => 'ek_ozellikler', 'field_icon' => '❄️', 'required' => false, 'display_order' => 90,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
            ],
            $base + [
                'field_slug' => 'wifi', 'field_name' => 'Wi-Fi', 'field_type' => 'boolean',
                'field_category' => 'ek_ozellikler', 'field_icon' => '📶', 'required' => false, 'display_order' => 100,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
            ],
            $base + [
                'field_slug' => 'bahce', 'field_name' => 'Bahçe', 'field_type' => 'boolean',
                'field_category' => 'ek_ozellikler', 'field_icon' => '🌿', 'required' => false, 'display_order' => 110,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
            ],
            $base + [
                'field_slug' => 'mangal', 'field_name' => 'Mangal / BBQ', 'field_type' => 'boolean',
                'field_category' => 'ek_ozellikler', 'field_icon' => '🍖', 'required' => false, 'display_order' => 120,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => true,
            ],
            $base + [
                'field_slug' => 'otopark', 'field_name' => 'Otopark', 'field_type' => 'boolean',
                'field_category' => 'ek_ozellikler', 'field_icon' => '🚗', 'required' => false, 'display_order' => 130,
                'ai_auto_fill' => true, 'searchable' => true, 'show_in_card' => false,
            ],
        ];
    }
}
