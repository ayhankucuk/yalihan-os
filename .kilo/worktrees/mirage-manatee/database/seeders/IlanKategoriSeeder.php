<?php

namespace Database\Seeders;

use App\Models\IlanKategori;
use Illuminate\Database\Seeder;

class IlanKategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Context7: C7-KATEGORI-FINAL-2025-12-28
     * Yalıhan AI - Final Kategori Yapısı
     */
    public function run(): void
    {
        // Ana Kategoriler (Seviye 0)
        $anaKategoriler = [
            [
                'name' => 'Konut',
                'slug' => 'konut',
                'seviye' => 0,
                'aktiflik_durumu' => true,
                'display_order' => 1,
                'icon' => 'home',
                'aciklama' => 'Daire, villa, müstakil ev gibi konut türleri',
            ],
            [
                'name' => 'İşyeri',
                'slug' => 'isyeri',
                'seviye' => 0,
                'aktiflik_durumu' => true,
                'display_order' => 2,
                'icon' => 'building',
                'aciklama' => 'Ofis, dükkan, fabrika gibi ticari alanlar',
            ],
            [
                'name' => 'Arsa & Arazi',
                'slug' => 'arsa-arazi',
                'seviye' => 0,
                'aktiflik_durumu' => true,
                'display_order' => 3,
                'icon' => 'map',
                'aciklama' => 'İmar, tarla, zeytinlik, bağ/bahçe arazileri',
            ],
            [
                'name' => 'Yazlık Kiralama',
                'slug' => 'yazlik-kiralama',
                'seviye' => 0,
                'aktiflik_durumu' => true,
                'display_order' => 4,
                'icon' => 'sun',
                'aciklama' => 'Günlük, haftalık, aylık yazlık kiralama',
            ],
            [
                'name' => 'Turistik Tesisler',
                'slug' => 'turistik-tesisler',
                'seviye' => 0,
                'aktiflik_durumu' => true,
                'display_order' => 5,
                'icon' => 'hotel',
                'aciklama' => 'Otel, pansiyon, tatil köyü gibi tesisler',
            ],
            [
                'name' => 'Projeden Satış',
                'slug' => 'projeden-satis',
                'seviye' => 0,
                'aktiflik_durumu' => true,
                'display_order' => 6,
                'icon' => 'rocket',
                'aciklama' => 'İnşaat halindeki projeler ve ön satış fırsatları',
            ],
        ];

        $anaKategoriIds = [];
        foreach ($anaKategoriler as $kategori) {
            $anaKategori = IlanKategori::updateOrCreate(
                ['slug' => $kategori['slug']],
                $kategori
            );
            $anaKategoriIds[$kategori['slug']] = $anaKategori->id;
        }

        // Alt Kategoriler (Seviye 1)
        $altKategoriler = [
            // Konut Alt Kategorileri
            [
                'name' => 'Daire',
                'slug' => 'daire',
                'parent_id' => $anaKategoriIds['konut'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 1,
                'icon' => 'apartment',
                'aciklama' => 'Apartman dairesi',
            ],
            [
                'name' => 'Villa',
                'slug' => 'villa',
                'parent_id' => $anaKategoriIds['konut'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 2,
                'icon' => 'villa',
                'aciklama' => 'Müstakil villa',
            ],
            [
                'name' => 'Müstakil Ev',
                'slug' => 'mustakil-ev',
                'parent_id' => $anaKategoriIds['konut'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 3,
                'icon' => 'house',
                'aciklama' => 'Tek ailelik müstakil ev',
            ],
            [
                'name' => 'Dubleks',
                'slug' => 'dubleks',
                'parent_id' => $anaKategoriIds['konut'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 4,
                'icon' => 'duplex',
                'aciklama' => 'İki katlı konut',
            ],

            // İşyeri Alt Kategorileri
            [
                'name' => 'Ofis',
                'slug' => 'ofis',
                'parent_id' => $anaKategoriIds['isyeri'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 1,
                'icon' => 'office',
                'aciklama' => 'Büro ve ofis alanları',
            ],
            [
                'name' => 'Dükkan',
                'slug' => 'dukkan',
                'parent_id' => $anaKategoriIds['isyeri'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 2,
                'icon' => 'shop',
                'aciklama' => 'Perakende satış dükkanları',
            ],
            [
                'name' => 'Fabrika',
                'slug' => 'fabrika',
                'parent_id' => $anaKategoriIds['isyeri'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 3,
                'icon' => 'factory',
                'aciklama' => 'Üretim tesisleri',
            ],
            [
                'name' => 'Depo',
                'slug' => 'depo',
                'parent_id' => $anaKategoriIds['isyeri'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 4,
                'icon' => 'warehouse',
                'aciklama' => 'Depolama alanları',
            ],

            // 🚀 YENİ: Arsa & Arazi Alt Kategorileri (Revize)
            [
                'name' => 'Arsa (Konut/Villa)',
                'slug' => 'arsa-konut-villa',
                'parent_id' => $anaKategoriIds['arsa-arazi'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 1,
                'icon' => 'land',
                'aciklama' => 'Konut imarlı, villa parseli vb.',
            ],
            [
                'name' => 'Sanayi & Ticari İmar (Fabrika/Depo)',
                'slug' => 'sanayi-ticari-imar',
                'parent_id' => $anaKategoriIds['arsa-arazi'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 2,
                'icon' => 'industry',
                'aciklama' => 'Depo, fabrika, ticari alanlar için imar',
            ],
            [
                'name' => 'Tarla',
                'slug' => 'tarla',
                'parent_id' => $anaKategoriIds['arsa-arazi'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 3,
                'icon' => 'tractor',
                'aciklama' => 'Kuru tarım, sulu tarım arazileri',
            ],
            [
                'name' => 'Zeytinlik',
                'slug' => 'zeytinlik',
                'parent_id' => $anaKategoriIds['arsa-arazi'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 4,
                'icon' => 'olive',
                'aciklama' => 'Zeytin ağaçlı araziler',
            ],
            [
                'name' => 'Bağ & Bahçe',
                'slug' => 'bag-bahce',
                'parent_id' => $anaKategoriIds['arsa-arazi'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 5,
                'icon' => 'grape',
                'aciklama' => 'Meyve bahçeleri, bağ alanları',
            ],
            [
                'name' => 'Zeytinli Tarla',
                'slug' => 'zeytinli-tarla',
                'parent_id' => $anaKategoriIds['arsa-arazi'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 6,
                'icon' => 'olive-field',
                'aciklama' => 'Zeytinlik vasıflı tarla arazileri',
            ],
            [
                'name' => 'Turizm (Otel/Kamp)',
                'slug' => 'turizm-otel-kamp',
                'parent_id' => $anaKategoriIds['arsa-arazi'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 7,
                'icon' => 'umbrella-beach',
                'aciklama' => 'Otel, kamp alanı veya turistik tesis imarlı araziler',
            ],
            [
                'name' => 'Turizm + Konut',
                'slug' => 'turizm-konut',
                'parent_id' => $anaKategoriIds['arsa-arazi'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 8,
                'icon' => 'hotel-home',
                'aciklama' => 'Karma turizm ve konut imarlı araziler',
            ],

            // 🚀 YENİ: Projeden Satış Alt Kategorileri
            [
                'name' => 'Konut Projesi',
                'slug' => 'konut-projesi',
                'parent_id' => $anaKategoriIds['projeden-satis'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 1,
                'icon' => 'city',
                'aciklama' => 'İnşaatı devam eden veya yeni başlayacak konut kompleksleri',
            ],
            [
                'name' => 'Villa Projesi',
                'slug' => 'villa-projesi',
                'parent_id' => $anaKategoriIds['projeden-satis'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 2,
                'icon' => 'villas',
                'aciklama' => 'Lüks villa siteleri ve proje mülkleri',
            ],
            [
                'name' => 'Karma Proje',
                'slug' => 'karma-proje',
                'parent_id' => $anaKategoriIds['projeden-satis'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 3,
                'icon' => 'mix',
                'aciklama' => 'Hem konut hem ticari üniteler içeren projeler',
            ],

            // Yazlık Kiralama Alt Kategorileri (Fiziksel Tipler - Context7 & SSOT)
            [
                'name' => 'Villa',
                'slug' => 'villa-tipi', // Mühürlü slug
                'parent_id' => $anaKategoriIds['yazlik-kiralama'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 1,
                'icon' => 'villa',
                'aciklama' => 'Müstakil, özel havuzlu, geniş bahçeli birimler',
            ],
            [
                'name' => 'Rezidans',
                'slug' => 'rezidans-tipi',
                'parent_id' => $anaKategoriIds['yazlik-kiralama'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 2,
                'icon' => 'building',
                'aciklama' => 'Lüks hizmetlere sahip, site içi veya yüksek katlı üniteler',
            ],
            [
                'name' => 'Daire',
                'slug' => 'daire-tipi',
                'parent_id' => $anaKategoriIds['yazlik-kiralama'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 3,
                'icon' => 'apartment',
                'aciklama' => 'Standart apartman dairesi veya dubleks yapılar',
            ],
            [
                'name' => 'Taş Ev',
                'slug' => 'tas-ev-tipi',
                'parent_id' => $anaKategoriIds['yazlik-kiralama'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 4,
                'icon' => 'house-chimney',
                'aciklama' => 'Bölgesel mimariye uygun taş yapılar',
            ],
            [
                'name' => 'Malikane',
                'slug' => 'malikane-tipi',
                'parent_id' => $anaKategoriIds['yazlik-kiralama'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 5,
                'icon' => 'house-luxury',
                'aciklama' => 'Yüksek segmentli, geniş arazili özel yapılar',
            ],
            [
                'name' => 'Tiny House / Bungalov',
                'slug' => 'minimal-tipi',
                'parent_id' => $anaKategoriIds['yazlik-kiralama'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 6,
                'icon' => 'campground',
                'aciklama' => 'Trend odaklı, minimal yaşam alanları',
            ],

            // Turistik Tesisler Alt Kategorileri
            [
                'name' => 'Otel',
                'slug' => 'otel',
                'parent_id' => $anaKategoriIds['turistik-tesisler'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 1,
                'icon' => 'hotel',
                'aciklama' => 'Otel tesisleri',
            ],
            [
                'name' => 'Pansiyon',
                'slug' => 'pansiyon',
                'parent_id' => $anaKategoriIds['turistik-tesisler'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 2,
                'icon' => 'pension',
                'aciklama' => 'Pansiyon tesisleri',
            ],
            [
                'name' => 'Tatil Köyü',
                'slug' => 'tatil-koyu',
                'parent_id' => $anaKategoriIds['turistik-tesisler'],
                'seviye' => 1,
                'aktiflik_durumu' => true,
                'display_order' => 3,
                'icon' => 'resort',
                'aciklama' => 'Tatil köyü tesisleri',
            ],
        ];

        $altKategoriIds = [];
        foreach ($altKategoriler as $kategori) {
            $altKategori = IlanKategori::updateOrCreate(
                ['slug' => $kategori['slug'], 'parent_id' => $kategori['parent_id']],
                $kategori
            );
            $altKategoriIds[$kategori['slug']] = $altKategori->id;
        }

        // Yayın Tipleri (Seviye 2) - NOT: Yayın tipleri artık yayin_tipleri tablosunda tutulmaktadır.
        // Bu yüzden IlanKategori tablosundaki Seviye 2 kayıtları kaldırılmıştır.

        $this->command->info('✅ İlan kategorileri başarıyla oluşturuldu!');
        $this->command->info('📊 Ana Kategoriler: '.count($anaKategoriler));
        $this->command->info('📊 Alt Kategoriler: '.count($altKategoriler));
        $this->command->info('🚀 YENİ: Tarla, Zeytinlik, Bağ/Bahçe, Sanayi İmarı eklendi!');
    }
}
