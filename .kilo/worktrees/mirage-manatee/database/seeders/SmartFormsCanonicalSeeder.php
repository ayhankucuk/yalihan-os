<?php

namespace Database\Seeders;

use App\Models\KategoriYayinTipiFieldDependency;
use Illuminate\Database\Seeder;

/**
 * SMART FORMS Canonical Seeder
 *
 * kategori_yayin_tipi_field_dependencies tablosunu canonical kurallarla doldurur.
 * Hangi özelliğin hangi yayın tipinde görüneceğini belirler.
 *
 * yayin_tipi: Blade+Service YayinTipiSablonu.slug referans alıyor
 *
 * Context7: C7-SMARTFORMS-CANONICAL-2026-02-20
 */
class SmartFormsCanonicalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🧠 SMART FORMS canonical kurallar yükleniyor...');

        // [kategori_slug, yayin_tipi_slug, field_slug, field_name, field_type, required]
        $rules = [
            // ═══════════════════════════════════════
            // KONUT — Satılık
            // ═══════════════════════════════════════
            ['konut', 'satilik', 'brut-metrekare', 'Brüt Metrekare', 'number', true],
            ['konut', 'satilik', 'net-metrekare', 'Net Metrekare', 'number', false],
            ['konut', 'satilik', 'oda-sayisi', 'Oda Sayısı', 'text', true],
            ['konut', 'satilik', 'banyo-sayisi', 'Banyo Sayısı', 'number', false],
            ['konut', 'satilik', 'bina-yasi', 'Bina Yaşı', 'select', false],
            ['konut', 'satilik', 'kat', 'Kat', 'select', false],
            ['konut', 'satilik', 'asansor', 'Asansör', 'boolean', false],
            ['konut', 'satilik', 'otopark', 'Otopark', 'select', false],
            ['konut', 'satilik', 'balkon', 'Balkon', 'boolean', false],
            ['konut', 'satilik', 'tapu-durumu', 'Tapu Durumu', 'select', false],
            ['konut', 'satilik', 'isitma', 'Isıtma', 'select', false],
            ['konut', 'satilik', 'site-icerisinde', 'Site İçerisinde', 'boolean', false],
            ['konut', 'satilik', 'takas', 'Takas', 'boolean', false],
            ['konut', 'satilik', 'kredi-uygunlugu', 'Kredi Uygunluğu', 'boolean', false],
            // ═══════════════════════════════════════
            // KONUT — Kiralık
            // ═══════════════════════════════════════
            ['konut', 'kiralik', 'brut-metrekare', 'Brüt Metrekare', 'number', true],
            ['konut', 'kiralik', 'oda-sayisi', 'Oda Sayısı', 'text', true],
            ['konut', 'kiralik', 'banyo-sayisi', 'Banyo Sayısı', 'number', false],
            ['konut', 'kiralik', 'kat', 'Kat', 'select', false],
            ['konut', 'kiralik', 'asansor', 'Asansör', 'boolean', false],
            ['konut', 'kiralik', 'otopark', 'Otopark', 'select', false],
            ['konut', 'kiralik', 'balkon', 'Balkon', 'boolean', false],
            ['konut', 'kiralik', 'esyali', 'Eşyalı', 'select', false],
            ['konut', 'kiralik', 'aidat', 'Aidat', 'number', false],
            ['konut', 'kiralik', 'site-icerisinde', 'Site İçerisinde', 'boolean', false],
            // ═══════════════════════════════════════
            // ARSA — Satılık
            // ═══════════════════════════════════════
            ['arsa-arazi', 'satilik', 'brut-metrekare', 'Alan (m²)', 'number', true],
            ['arsa-arazi', 'satilik', 'imar-durumu', 'İmar Durumu', 'select', true],
            ['arsa-arazi', 'satilik', 'tapu-durumu', 'Tapu Durumu', 'select', false],
            ['arsa-arazi', 'satilik', 'takas', 'Takas', 'boolean', false],
            // ═══════════════════════════════════════
            // İŞYERİ — Satılık
            // ═══════════════════════════════════════
            ['isyeri', 'satilik', 'brut-metrekare', 'Brüt Metrekare', 'number', true],
            ['isyeri', 'satilik', 'kat', 'Kat', 'select', false],
            ['isyeri', 'satilik', 'tapu-durumu', 'Tapu Durumu', 'select', false],
            // ═══════════════════════════════════════
            // İŞYERİ — Kiralık
            // ═══════════════════════════════════════
            ['isyeri', 'kiralik', 'brut-metrekare', 'Brüt Metrekare', 'number', true],
            ['isyeri', 'kiralik', 'kat', 'Kat', 'select', false],
            ['isyeri', 'kiralik', 'aidat', 'Aidat', 'number', false],
            // ═══════════════════════════════════════
            // YAZLIK — Günlük/Haftalık/Aylık/Sezonluk
            // ═══════════════════════════════════════
            ['yazlik-kiralama', 'gunluk', 'brut-metrekare', 'Metrekare', 'number', false],
            ['yazlik-kiralama', 'gunluk', 'oda-sayisi', 'Oda Sayısı', 'text', true],
            ['yazlik-kiralama', 'gunluk', 'denize-mesafe', 'Denize Mesafe', 'select', false],
            ['yazlik-kiralama', 'gunluk', 'manzara', 'Manzara', 'select', false],
            ['yazlik-kiralama', 'haftalik', 'oda-sayisi', 'Oda Sayısı', 'text', true],
            ['yazlik-kiralama', 'haftalik', 'denize-mesafe', 'Denize Mesafe', 'select', false],
            ['yazlik-kiralama', 'aylik', 'oda-sayisi', 'Oda Sayısı', 'text', true],
            ['yazlik-kiralama', 'sezonluk', 'oda-sayisi', 'Oda Sayısı', 'text', true],
        ];

        $added = 0;
        $skipped = 0;

        foreach ($rules as $idx => [$kategoriSlug, $yayinTipi, $fieldSlug, $fieldName, $fieldType, $required]) {
            $exists = KategoriYayinTipiFieldDependency::where('kategori_slug', $kategoriSlug)
                ->where('yayin_tipi', $yayinTipi)
                ->where('field_slug', $fieldSlug)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            KategoriYayinTipiFieldDependency::create([
                'kategori_slug'  => $kategoriSlug,
                'yayin_tipi'     => $yayinTipi,
                'field_slug'     => $fieldSlug,
                'field_name'     => $fieldName,
                'field_type'     => $fieldType,
                'field_category' => 'general',
                'required'       => $required,
                'aktiflik_durumu'=> true,
                'display_order'  => $idx + 1,
            ]);

            $added++;
        }

        $total = KategoriYayinTipiFieldDependency::count();
        $this->command->info("✅ SMART FORMS: {$added} kural eklendi, {$skipped} atlandı.");
        $this->command->info("📊 Toplam kategori_yayin_tipi_field_dependencies: {$total}");
    }
}
