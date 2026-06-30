<?php

namespace Database\Seeders;

use App\Models\Ilan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ArsaIlanlarSeeder — Yalıkavak ve Gündoğan arsa demo ilanları.
 * SAB: @sab-ignore-service (Seeder katmanı)
 */
class ArsaIlanlarSeeder extends Seeder
{
    public function run(): void
    {
        $arsalar = [
            [
                'ilan' => [
                    'baslik'          => 'Yalıkavak Marina Bölgesi Konut İmarlı Arsa',
                    'aciklama'        => "Yalıkavak Palmarina'ya 800 metre, deniz manzaralı 620 m² konut imarlı arsa.\n\nParselin tüm altyapısı hazır (elektrik, su, yol). TAKS: 0.20, KAKS: 0.40. Yüksek kotta konumlanmış, hem deniz hem marina manzarası sunuyor.\n\nAda/Parsel: 342/18 — Tapu devri hazır.",
                    'fiyat'           => 12500000,
                    'para_birimi'     => 'TRY',
                    'ana_kategori_id' => 3,
                    'alt_kategori_id' => 15,
                    'il_id'           => 48,
                    'ilce_id'         => 1,
                    'mahalle_id'      => 53,
                    'mahalle'         => 'Yalıkavak',
                    'lat'             => 37.1058,
                    'lng'             => 27.2983,
                    'net_m2'          => 620,
                    'ada_no'          => '342',
                    'parsel_no'       => '18',
                    'ada_parsel'      => '342/18',
                    'yola_cephe'      => 1,
                    'altyapi_elektrik'=> 1,
                    'altyapi_su'      => 1,
                    'altyapi_dogalgaz'=> 0,
                    'yayin_durumu'    => \App\Enums\IlanDurumu::YAYINDA,
                    'country_code'    => 'TR',
                ],
                'detay' => [
                    'imar_durumu' => 'Konut İmarlı',
                    'kaks'        => 0.40,
                    'taks'        => 0.20,
                ],
            ],
            [
                'ilan' => [
                    'baslik'          => 'Gündoğan Koy Manzaralı Bağ-Bahçe Nitelikli Arazi',
                    'aciklama'        => "Gündoğan koyuna hakim tepe konumda, 1.250 m² bağ-bahçe nitelikli arazi.\n\nZeytinlikler arasında huzurlu konum. Yola cepheli, elektrik parsele kadar çekilmiş.\n\nAda/Parsel: 118/7 — Bodrum merkeze 22 km, Gündoğan sahiline 1,5 km.",
                    'fiyat'           => 7800000,
                    'para_birimi'     => 'TRY',
                    'ana_kategori_id' => 3,
                    'alt_kategori_id' => 15,
                    'il_id'           => 48,
                    'ilce_id'         => 1,
                    'mahalle_id'      => 25,
                    'mahalle'         => 'Gündoğan',
                    'lat'             => 37.0721,
                    'lng'             => 27.3102,
                    'net_m2'          => 1250,
                    'ada_no'          => '118',
                    'parsel_no'       => '7',
                    'ada_parsel'      => '118/7',
                    'yola_cephe'      => 1,
                    'altyapi_elektrik'=> 1,
                    'altyapi_su'      => 0,
                    'altyapi_dogalgaz'=> 0,
                    'yayin_durumu'    => \App\Enums\IlanDurumu::YAYINDA,
                    'country_code'    => 'TR',
                ],
                'detay' => [
                    'imar_durumu' => 'Bağ-Bahçe',
                    'kaks'        => null,
                    'taks'        => null,
                ],
            ],
        ];

        $created = 0;
        foreach ($arsalar as $kayit) {
            $data  = $kayit['ilan'];
            $detay = $kayit['detay'];

            if (Ilan::where('baslik', $data['baslik'])->exists()) {
                $this->command->warn("⏭  Zaten mevcut: {$data['baslik']}");
                continue;
            }

            foreach (['yola_cephe','altyapi_elektrik','altyapi_su','altyapi_dogalgaz'] as $f) {
                if (isset($data[$f])) $data[$f] = (int) $data[$f];
            }

            $ilan = Ilan::create($data);

            // ilan_arsa_details tablosuna yaz
            if (DB::getSchemaBuilder()->hasTable('ilan_arsa_details')) {
                DB::table('ilan_arsa_details')->insert(array_merge(
                    ['ilan_id' => $ilan->id],
                    $detay
                ));
            }

            $created++;
            $this->command->info("✅ Oluşturuldu: {$data['baslik']} (ID: {$ilan->id})");
        }

        $this->command->info("🏗  Tamamlandı: {$created} arsa ilanı eklendi.");
    }
}
