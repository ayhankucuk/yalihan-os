<?php

namespace Database\Seeders;

use App\Models\Ozellik;
use App\Models\OzellikKategori;
use Illuminate\Database\Seeder;

/**
 * Property Hub Ozellikler Canonical Seeder
 *
 * ozellikler tablosunu (Ozellik modeli) doğru FK ile doldurur.
 * kategori_id → ozellik_kategorileri.id (not ilan_kategorileri.id)
 *
 * Context7: C7-PROPERTY-HUB-OZELLIKLER-2026-02-20
 */
class PropertyHubOzelliklerSeeder extends Seeder
{
    public function run(): void
    {
        // Önce test kaydını temizle
        Ozellik::where('slug', 'like', 'test-ozellik-%')->forceDelete();

        $temel = OzellikKategori::where('slug', 'temel-bilgiler')->firstOrFail();
        $oda   = OzellikKategori::where('slug', 'oda-ve-alan')->firstOrFail();
        $ek    = OzellikKategori::where('slug', 'ek-ozellikler')->firstOrFail();
        $konum = OzellikKategori::where('slug', 'konum-ve-cevre')->firstOrFail();
        $fiyat = OzellikKategori::where('slug', 'fiyat-ve-odeme')->firstOrFail();

        $ozellikler = [
            // TEMEL BİLGİLER
            ['kategori_id' => $temel->id, 'name' => 'Brüt Metrekare', 'slug' => 'brut-metrekare', 'veri_tipi' => 'number', 'birim' => 'm²', 'zorunlu' => true, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Toplam brüt alan'],
            ['kategori_id' => $temel->id, 'name' => 'Net Metrekare', 'slug' => 'net-metrekare', 'veri_tipi' => 'number', 'birim' => 'm²', 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Net kullanım alanı'],
            ['kategori_id' => $temel->id, 'name' => 'Tapu Durumu', 'slug' => 'tapu-durumu', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['Müstakil Tapu', 'Kat Mülkiyeti', 'Kat İrtifakı', 'Hisseli Tapu']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => false, 'aciklama' => 'Tapu türü'],
            ['kategori_id' => $temel->id, 'name' => 'Kullanım Durumu', 'slug' => 'kullanim-durumu', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['Boş', 'Kiracılı', 'Mülk Sahibi']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => false, 'ilan_kartinda_goster' => false, 'aciklama' => 'Mevcut kullanım durumu'],
            // ODA VE ALAN
            ['kategori_id' => $oda->id, 'name' => 'Oda Sayısı', 'slug' => 'oda-sayisi', 'veri_tipi' => 'text', 'birim' => null, 'zorunlu' => true, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Örn: 1+1, 2+1, 3+1'],
            ['kategori_id' => $oda->id, 'name' => 'Banyo Sayısı', 'slug' => 'banyo-sayisi', 'veri_tipi' => 'number', 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Toplam banyo sayısı'],
            ['kategori_id' => $oda->id, 'name' => 'Toplam Kat', 'slug' => 'toplam-kat', 'veri_tipi' => 'number', 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => false, 'ilan_kartinda_goster' => false, 'aciklama' => 'Binadaki toplam kat sayısı'],
            ['kategori_id' => $oda->id, 'name' => 'Balkon', 'slug' => 'balkon', 'veri_tipi' => 'boolean', 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Balkon var mı?'],
            // EK ÖZELLİKLER
            ['kategori_id' => $ek->id, 'name' => 'Asansör', 'slug' => 'asansor', 'veri_tipi' => 'boolean', 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Asansör var mı?'],
            ['kategori_id' => $ek->id, 'name' => 'Otopark', 'slug' => 'otopark', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['Yok', 'Açık Otopark', 'Kapalı Otopark']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Otopark durumu'],
            ['kategori_id' => $ek->id, 'name' => 'Isıtma', 'slug' => 'isitma', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['Yok', 'Soba', 'Doğalgaz (Kombi)', 'Doğalgaz (Merkezi)', 'Yerden Isıtma', 'Klima']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => false, 'aciklama' => 'Isınma sistemi'],
            ['kategori_id' => $ek->id, 'name' => 'Eşyalı', 'slug' => 'esyali', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['Hayır', 'Kısmen', 'Evet']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Eşyalı mı?'],
            ['kategori_id' => $ek->id, 'name' => 'Bina Yaşı', 'slug' => 'bina-yasi', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['0 (Sıfır)', '1-5 Yıl', '6-10 Yıl', '11-15 Yıl', '16-20 Yıl', '21+ Yıl']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => false, 'aciklama' => 'Bina yaşı'],
            ['kategori_id' => $ek->id, 'name' => 'Kat', 'slug' => 'kat', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['Bodrum', 'Zemin', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '10+', 'Çatı Katı']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Bulunduğu kat'],
            ['kategori_id' => $ek->id, 'name' => 'Site İçerisinde', 'slug' => 'site-icerisinde', 'veri_tipi' => 'boolean', 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Site içerisinde mi?'],
            // KONUM VE ÇEVRE
            ['kategori_id' => $konum->id, 'name' => 'Cephe', 'slug' => 'cephe', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['Kuzey', 'Güney', 'Doğu', 'Batı', 'Güneybatı', 'Güneydoğu']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => false, 'ilan_kartinda_goster' => false, 'aciklama' => 'Cephe yönü'],
            ['kategori_id' => $konum->id, 'name' => 'Denize Mesafe', 'slug' => 'denize-mesafe', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['Deniz Kenarı', '50m İçinde', '100m', '200m', '500m', '1km', '1-5km', '5km+']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Denize yakınlık'],
            ['kategori_id' => $konum->id, 'name' => 'Manzara', 'slug' => 'manzara', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['Yok', 'Deniz', 'Göl', 'Doğa', 'Dağ', 'Bahçe']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => true, 'aciklama' => 'Manzara türü'],
            ['kategori_id' => $konum->id, 'name' => 'İmar Durumu', 'slug' => 'imar-durumu', 'veri_tipi' => 'select', 'veri_secenekleri' => json_encode(['İmarlı', 'İmarsız', 'Ticari İmar', 'Konut İmarı', 'Sanayi İmarı', 'Tarla', 'Bahçe']), 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => false, 'aciklama' => 'Arsanın imar durumu'],
            // FİYAT VE ÖDEME
            ['kategori_id' => $fiyat->id, 'name' => 'Aidat', 'slug' => 'aidat', 'veri_tipi' => 'number', 'birim' => 'TL', 'zorunlu' => false, 'arama_filtresi' => false, 'ilan_kartinda_goster' => false, 'aciklama' => 'Aylık aidat'],
            ['kategori_id' => $fiyat->id, 'name' => 'Takas', 'slug' => 'takas', 'veri_tipi' => 'boolean', 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => false, 'aciklama' => 'Takas kabul edilir mi?'],
            ['kategori_id' => $fiyat->id, 'name' => 'Kredi Uygunluğu', 'slug' => 'kredi-uygunlugu', 'veri_tipi' => 'boolean', 'birim' => null, 'zorunlu' => false, 'arama_filtresi' => true, 'ilan_kartinda_goster' => false, 'aciklama' => 'Krediye uygun mu?'],
        ];

        $added = 0;
        $skipped = 0;

        foreach ($ozellikler as $o) {
            if (!Ozellik::where('slug', $o['slug'])->exists()) {
                Ozellik::create(array_merge($o, ['aktiflik_durumu' => true]));
                $this->command->info("  ✅ {$o['name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        $this->command->newLine();
        $this->command->info("✅ PropertyHub Özellikler: {$added} eklendi, {$skipped} atlandı.");
        $this->command->info('📊 Toplam ozellikler: ' . Ozellik::count());
    }
}
