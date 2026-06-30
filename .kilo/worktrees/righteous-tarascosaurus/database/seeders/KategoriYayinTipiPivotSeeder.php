<?php

namespace Database\Seeders;

use App\Models\IlanKategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Canonical Yayin Tipi Pivot Atama Seeder
 *
 * alt_kategori_yayin_tipi tablosunu slug-bazlı olarak doldurur.
 * Hardcoded ID kullanmaz — slug üzerinden dinamik lookup yapar.
 *
 * Context7: C7-YAYIN-TIPI-PIVOT-2026-02-20
 */
class KategoriYayinTipiPivotSeeder extends Seeder
{
    public function run(): void
    {
        // yayin_tipleri'nden slug-bazlı lookup
        $yt = DB::table('yayin_tipleri')->pluck('id', 'slug');

        $satilik       = $yt['satilik']        ?? null;
        $kiralik       = $yt['kiralik']         ?? null;
        $gunlukKiralik = $yt['gunluk-kiralik']  ?? null;
        $devrenSatilik = $yt['devren-satilik']  ?? null;
        $katKarsiligi  = $yt['kat-karsiligi']   ?? null;

        // Atama Matrisi: [alt_kategori_slug => [yayin_tipi_id, ...]]
        $atamaMatrisi = [
            // KONUT alt kategorileri
            'daire'        => [$satilik, $kiralik, $katKarsiligi],
            'villa'        => [$satilik, $kiralik, $katKarsiligi],
            'mustakil-ev'  => [$satilik, $kiralik, $katKarsiligi],
            'dubleks'      => [$satilik, $kiralik, $katKarsiligi],

            // ARSA & ARAZİ alt kategorileri
            'arsa-konut-villa'   => [$satilik, $katKarsiligi],
            'sanayi-ticari-imar' => [$satilik],
            'tarla'              => [$satilik],
            'zeytinlik'          => [$satilik],
            'bag-bahce'          => [$satilik],
            'zeytinli-tarla'     => [$satilik],
            'turizm-otel-kamp'   => [$satilik, $katKarsiligi],
            'turizm-konut'       => [$satilik, $katKarsiligi],

            // İŞYERİ alt kategorileri
            'ofis'   => [$satilik, $kiralik, $devrenSatilik],
            'dukkan' => [$satilik, $kiralik, $devrenSatilik],
            'fabrika'=> [$satilik, $kiralik],
            'depo'   => [$satilik, $kiralik],

            // YAZLIK KİRALAMA alt kategorileri
            'villa-tipi'    => [$gunlukKiralik, $kiralik],
            'rezidans-tipi' => [$gunlukKiralik, $kiralik],
            'daire-tipi'    => [$gunlukKiralik, $kiralik],
            'tas-ev-tipi'   => [$gunlukKiralik, $kiralik],
            'malikane-tipi' => [$gunlukKiralik, $kiralik],
            'minimal-tipi'  => [$gunlukKiralik, $kiralik],

            // PROJEDen SATIŞ alt kategorileri
            'konut-projesi' => [$satilik],
            'villa-projesi' => [$satilik],
            'karma-proje'   => [$satilik],

            // TURİSTİK TESİSLER
            'otel'     => [$satilik, $kiralik],
            'pansiyon' => [$satilik, $kiralik],
            'tatil-koyu' => [$satilik, $kiralik],
        ];

        $added = 0;

        foreach ($atamaMatrisi as $katSlug => $yayinTipleri) {
            $kategori = IlanKategori::where('slug', $katSlug)->first();

            if (!$kategori) {
                $this->command->warn("  ⚠️ Kategori bulunamadı: {$katSlug}");
                continue;
            }

            foreach (array_filter($yayinTipleri) as $idx => $ytId) {
                DB::table('alt_kategori_yayin_tipi')->updateOrInsert(
                    [
                        'alt_kategori_id' => $kategori->id,
                        'yayin_tipi_id'   => $ytId,
                    ],
                    [
                        'aktiflik_durumu' => true,
                        'display_order'   => $idx + 1,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]
                );
                $added++;
            }

            $this->command->info("  ✅ {$kategori->name} → " . count(array_filter($yayinTipleri)) . " yayin tipi");
        }

        $this->command->newLine();
        $this->command->info("✅ KategoriYayinTipiPivot: {$added} atama oluşturuldu.");
        $this->command->info('📊 alt_kategori_yayin_tipi: ' . DB::table('alt_kategori_yayin_tipi')->count());
    }
}
