<?php

namespace Database\Seeders;

use App\Models\IlanKategori;
use App\Models\YayinTipi;
use App\Models\YayinTipiSablonu;
use Illuminate\Database\Seeder;

/**
 * Canonical Yayin Tipi Seeder
 *
 * PILOT-01 Recovery-A: Tüm kategoriler için canonical YayinTipiSablonu provisioning.
 * Mevcut kayıtları DEĞİŞTİRMEZ — sadece eksikleri ekler.
 *
 * Pattern: updateOrCreate ile idempotent seeding (slug bazlı, hardcoded ID yok).
 *
 * Recovery-A Canonical Contract:
 *   - V1 YayinTipi: Global master types (Satılık, Kiralık, Günlük...)
 *   - V2 YayinTipiSablonu: Kategori-specific templates
 *   - YayinTipiSablonu.yayin_tipi_id → YayinTipi.id (FK)
 *   - YayinTipiSablonu.slug = "{kategori}-{yayin-tipi-slug}"
 */
class YayinTipiSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedYayinTipleri();
        $this->seedAllCategoryTemplates();
        $this->report();
    }

    /**
     * V1 YayinTipi: Global master types
     */
    private function seedYayinTipleri(): void
    {
        $types = [
            // Base types
            ['slug' => 'satilik',         'name' => 'Satılık',          'aktiflik_durumu' => 1],
            ['slug' => 'kiralik',        'name' => 'Kiralık',         'aktiflik_durumu' => 1],
            ['slug' => 'kat-karsiligi',  'name' => 'Kat Karşılığı',   'aktiflik_durumu' => 1],
            ['slug' => 'devren',         'name' => 'Devren',          'aktiflik_durumu' => 1],
            // Seasonal types
            ['slug' => 'gunluk-kiralik', 'name' => 'Günlük Kiralık',  'aktiflik_durumu' => 1],
            ['slug' => 'haftalik-kiralik', 'name' => 'Haftalık Kiralık', 'aktiflik_durumu' => 1],
            ['slug' => 'aylik-kiralik',  'name' => 'Aylık Kiralık',   'aktiflik_durumu' => 1],
            ['slug' => 'sezonluk-kiralik', 'name' => 'Sezonluk Kiralık', 'aktiflik_durumu' => 1],
        ];

        foreach ($types as $tip) {
            YayinTipi::updateOrCreate(['slug' => $tip['slug']], $tip);
        }

        $this->command->info('  ✅ YayinTipi: ' . YayinTipi::count() . ' kayıt (V1 master types)');
    }

    /**
     * V2 YayinTipiSablonu: Kategori-specific templates
     * Policy matrix'e göre tüm kategoriler için template üretir.
     */
    private function seedAllCategoryTemplates(): void
    {
        $matrix = $this->getCanonicalMatrix();

        // Önce eksik kategorileri oluştur (test database desteği)
        $this->ensureCategories($matrix);

        foreach ($matrix as $kategoriSlug => $allowedTypes) {
            $kategori = IlanKategori::where('slug', $kategoriSlug)->first();

            if (!$kategori) {
                $this->command->warn("  ⚠️ Kategori bulunamadı: {$kategoriSlug} — atlanıyor.");
                continue;
            }

            $count = 0;
            foreach ($allowedTypes as $typeSlug) {
                $template = $this->createTemplate($kategori, $typeSlug);
                if ($template) {
                    $count++;
                }
            }

            if ($count > 0) {
                $this->command->info("  ✅ {$kategoriSlug}: {$count} template");
            }
        }
    }

    /**
     * Test database desteği için eksik kategorileri oluşturur
     */
    private function ensureCategories(array $matrix): void
    {
        $categoryNames = [
            'arsa-arazi'              => 'Arsa Arazi',
            'arsa-konut-villa'       => 'Arsa Konut Villa',
            'sanayi-ticari-imar'     => 'Sanayi Ticari İmar',
            'tarla'                  => 'Tarla',
            'zeytinlik'              => 'Zeytinlik',
            'bag-bahce'              => 'Bağ Bahçe',
            'zeytinli-tarla'         => 'Zeytinli Tarla',
            'turizm-otel-kamp'       => 'Turizm Otel Kamp',
            'turizm-konut'           => 'Turizm Konut',
            'konut'                  => 'Konut',
            'daire'                  => 'Daire',
            'villa'                  => 'Villa',
            'mustakil-ev'           => 'Müstakil Ev',
            'dubleks'               => 'Dubleks',
            'isyeri'                 => 'İşyeri',
            'ofis'                  => 'Ofis',
            'dukkan'                => 'Dükkan',
            'fabrika'               => 'Fabrika',
            'depo'                  => 'Depo',
            'yazlik-kiralama'        => 'Yazlık Kiralama',
            'villa-tipi'            => 'Villa Tipi',
            'rezidans-tipi'         => 'Rezidans Tipi',
            'daire-tipi'            => 'Daire Tipi',
            'tas-ev-tipi'           => 'Taş Ev Tipi',
            'malikane-tipi'         => 'Malikane Tipi',
            'minimal-tipi'          => 'Minimal Tipi',
            'turistik-tesisler'      => 'Turistik Tesisler',
            'otel'                  => 'Otel',
            'pansiyon'              => 'Pansiyon',
            'tatil-koyu'            => 'Tatil Köyü',
            'projeden-satis'         => 'Projeden Satış',
            'konut-projesi'         => 'Konut Projesi',
            'villa-projesi'         => 'Villa Projesi',
            'karma-proje'           => 'Karma Proje',
        ];

        foreach (array_keys($matrix) as $slug) {
            if (!isset($categoryNames[$slug])) {
                $categoryNames[$slug] = ucfirst(str_replace('-', ' ', $slug));
            }
        }

        foreach ($categoryNames as $slug => $name) {
            IlanKategori::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'seviye' => 1,
                    'aktiflik_durumu' => true,
                ]
            );
        }
    }

    /**
     * Tek bir template oluşturur.
     * Slug bazlı updateOrCreate - hardcoded ID yok.
     */
    private function createTemplate(IlanKategori $kategori, string $typeSlug): ?YayinTipiSablonu
    {
        // YayinTipi (V1) lookup
        $yayinTipi = YayinTipi::where('slug', $typeSlug)
            ->orWhere('slug', $typeSlug . '-kiralik')
            ->orWhere('slug', $typeSlug . '-kiralama')
            ->first();

        if (!$yayinTipi) {
            $this->command->warn("    ⚠️ YayinTipi bulunamadı: {$typeSlug}");
            return null;
        }

        $templateSlug = $kategori->slug . '-' . $this->shortenSlug($typeSlug);
        $templateName = $kategori->name . ' ' . ucfirst($this->shortenSlug($typeSlug));

        return YayinTipiSablonu::updateOrCreate(
            [
                'kategori_id'   => $kategori->id,
                'yayin_tipi_id' => $yayinTipi->id,
            ],
            [
                'ad'             => $templateName,
                'slug'           => $templateSlug,
                'aktiflik_durumu' => true,
            ]
        );
    }

    /**
     * Full slug'ı kısa forma dönüştürür.
     * gunluk-kiralik → gunluk
     * sezonluk-kiralik → sezonluk
     */
    private function shortenSlug(string $slug): string
    {
        return preg_replace('/(-kiralik|-kiralama)$/', '', $slug);
    }

    /**
     * Policy matrix: kategori slug → izin verilen yayın tipi slugs
     * PropertyPublicationPolicy::getMatrixPolicyIds() ile senkronize.
     */
    private function getCanonicalMatrix(): array
    {
        return [
            // ── Arsa & Arazi Family ──
            'arsa-arazi'              => ['satilik', 'kiralik'],
            'arsa-konut-villa'       => ['satilik', 'kiralik', 'kat-karsiligi'],
            'sanayi-ticari-imar'     => ['satilik', 'kiralik'],
            'tarla'                  => ['satilik', 'kiralik'],
            'zeytinlik'              => ['satilik'],
            'bag-bahce'              => ['satilik'],
            'zeytinli-tarla'         => ['satilik', 'kiralik'],
            'turizm-otel-kamp'       => ['satilik', 'kiralik'],
            'turizm-konut'           => ['satilik', 'kiralik'],

            // ── Konut Family ──
            'konut'                  => ['satilik', 'kiralik'],
            'daire'                 => ['satilik', 'kiralik'],
            'villa'                 => ['satilik', 'kiralik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'mustakil-ev'           => ['satilik', 'kiralik'],
            'dubleks'               => ['satilik', 'kiralik'],

            // ── İşyeri Family ──
            'isyeri'                 => ['satilik', 'kiralik', 'devren'],
            'ofis'                  => ['satilik', 'kiralik', 'devren'],
            'dukkan'                => ['satilik', 'kiralik', 'devren'],
            'fabrika'               => ['satilik', 'kiralik', 'devren'],
            'depo'                  => ['satilik', 'kiralik'],

            // ── Yazlık Kiralama Family ──
            'yazlik-kiralama'        => ['gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'villa-tipi'            => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'rezidans-tipi'         => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'daire-tipi'            => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'tas-ev-tipi'           => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'malikane-tipi'         => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'minimal-tipi'           => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],

            // ── Turistik Tesisler Family ──
            'turistik-tesisler'      => ['satilik', 'kiralik'],
            'otel'                  => ['satilik', 'kiralik'],
            'pansiyon'              => ['satilik', 'kiralik'],
            'tatil-koyu'            => ['satilik', 'kiralik'],

            // ── Projeden Satış Family ──
            'projeden-satis'         => ['satilik'],
            'konut-projesi'         => ['satilik'],
            'villa-projesi'         => ['satilik'],
            'karma-proje'           => ['satilik'],
        ];
    }

    private function report(): void
    {
        $this->command->info('');
        $this->command->info('  📊 YayinTipiSablonu Summary:');
        $this->command->info('    Total: ' . YayinTipiSablonu::count());
        $this->command->info('    Active: ' . YayinTipiSablonu::where('aktiflik_durumu', true)->count());
    }
}
