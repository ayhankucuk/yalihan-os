<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Türkiye İl / İlçe / Mahalle Seeder
 *
 * - 81 İl (plaka kodu sırasıyla)
 * - Muğla ilçeleri (Bodrum-First Strategy)
 * - Bodrum mahalleleri
 */
class TurkiyeLocationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ────────────────────────────────────────────
        // 81 İL
        // ────────────────────────────────────────────
        $iller = [
            ['id' => 1, 'il_adi' => 'Adana', 'plaka_kodu' => '01', 'lat' => 37.0000, 'lng' => 35.3213],
            ['id' => 2, 'il_adi' => 'Adıyaman', 'plaka_kodu' => '02', 'lat' => 37.7648, 'lng' => 38.2786],
            ['id' => 3, 'il_adi' => 'Afyonkarahisar', 'plaka_kodu' => '03', 'lat' => 38.7507, 'lng' => 30.5567],
            ['id' => 4, 'il_adi' => 'Ağrı', 'plaka_kodu' => '04', 'lat' => 39.7191, 'lng' => 43.0503],
            ['id' => 5, 'il_adi' => 'Amasya', 'plaka_kodu' => '05', 'lat' => 40.6499, 'lng' => 35.8353],
            ['id' => 6, 'il_adi' => 'Ankara', 'plaka_kodu' => '06', 'lat' => 39.9334, 'lng' => 32.8597],
            ['id' => 7, 'il_adi' => 'Antalya', 'plaka_kodu' => '07', 'lat' => 36.8969, 'lng' => 30.7133],
            ['id' => 8, 'il_adi' => 'Artvin', 'plaka_kodu' => '08', 'lat' => 41.1828, 'lng' => 41.8183],
            ['id' => 9, 'il_adi' => 'Aydın', 'plaka_kodu' => '09', 'lat' => 37.8560, 'lng' => 27.8416],
            ['id' => 10, 'il_adi' => 'Balıkesir', 'plaka_kodu' => '10', 'lat' => 39.6484, 'lng' => 27.8826],
            ['id' => 11, 'il_adi' => 'Bilecik', 'plaka_kodu' => '11', 'lat' => 40.0567, 'lng' => 30.0665],
            ['id' => 12, 'il_adi' => 'Bingöl', 'plaka_kodu' => '12', 'lat' => 38.8854, 'lng' => 40.4966],
            ['id' => 13, 'il_adi' => 'Bitlis', 'plaka_kodu' => '13', 'lat' => 38.4006, 'lng' => 42.1095],
            ['id' => 14, 'il_adi' => 'Bolu', 'plaka_kodu' => '14', 'lat' => 40.7260, 'lng' => 31.6089],
            ['id' => 15, 'il_adi' => 'Burdur', 'plaka_kodu' => '15', 'lat' => 37.7203, 'lng' => 30.2908],
            ['id' => 16, 'il_adi' => 'Bursa', 'plaka_kodu' => '16', 'lat' => 40.1826, 'lng' => 29.0665],
            ['id' => 17, 'il_adi' => 'Çanakkale', 'plaka_kodu' => '17', 'lat' => 40.1553, 'lng' => 26.4142],
            ['id' => 18, 'il_adi' => 'Çankırı', 'plaka_kodu' => '18', 'lat' => 40.6013, 'lng' => 33.6134],
            ['id' => 19, 'il_adi' => 'Çorum', 'plaka_kodu' => '19', 'lat' => 40.5506, 'lng' => 34.9556],
            ['id' => 20, 'il_adi' => 'Denizli', 'plaka_kodu' => '20', 'lat' => 37.7765, 'lng' => 29.0864],
            ['id' => 21, 'il_adi' => 'Diyarbakır', 'plaka_kodu' => '21', 'lat' => 37.9144, 'lng' => 40.2306],
            ['id' => 22, 'il_adi' => 'Edirne', 'plaka_kodu' => '22', 'lat' => 41.6771, 'lng' => 26.5557],
            ['id' => 23, 'il_adi' => 'Elazığ', 'plaka_kodu' => '23', 'lat' => 38.6810, 'lng' => 39.2264],
            ['id' => 24, 'il_adi' => 'Erzincan', 'plaka_kodu' => '24', 'lat' => 39.7500, 'lng' => 39.5000],
            ['id' => 25, 'il_adi' => 'Erzurum', 'plaka_kodu' => '25', 'lat' => 39.9000, 'lng' => 41.2700],
            ['id' => 26, 'il_adi' => 'Eskişehir', 'plaka_kodu' => '26', 'lat' => 39.7767, 'lng' => 30.5206],
            ['id' => 27, 'il_adi' => 'Gaziantep', 'plaka_kodu' => '27', 'lat' => 37.0662, 'lng' => 37.3833],
            ['id' => 28, 'il_adi' => 'Giresun', 'plaka_kodu' => '28', 'lat' => 40.9128, 'lng' => 38.3895],
            ['id' => 29, 'il_adi' => 'Gümüşhane', 'plaka_kodu' => '29', 'lat' => 40.4386, 'lng' => 39.5086],
            ['id' => 30, 'il_adi' => 'Hakkari', 'plaka_kodu' => '30', 'lat' => 37.5833, 'lng' => 43.7333],
            ['id' => 31, 'il_adi' => 'Hatay', 'plaka_kodu' => '31', 'lat' => 36.4018, 'lng' => 36.3498],
            ['id' => 32, 'il_adi' => 'Isparta', 'plaka_kodu' => '32', 'lat' => 37.7648, 'lng' => 30.5566],
            ['id' => 33, 'il_adi' => 'Mersin', 'plaka_kodu' => '33', 'lat' => 36.8121, 'lng' => 34.6415],
            ['id' => 34, 'il_adi' => 'İstanbul', 'plaka_kodu' => '34', 'lat' => 41.0082, 'lng' => 28.9784],
            ['id' => 35, 'il_adi' => 'İzmir', 'plaka_kodu' => '35', 'lat' => 38.4189, 'lng' => 27.1287],
            ['id' => 36, 'il_adi' => 'Kars', 'plaka_kodu' => '36', 'lat' => 40.6167, 'lng' => 43.1000],
            ['id' => 37, 'il_adi' => 'Kastamonu', 'plaka_kodu' => '37', 'lat' => 41.3887, 'lng' => 33.7827],
            ['id' => 38, 'il_adi' => 'Kayseri', 'plaka_kodu' => '38', 'lat' => 38.7312, 'lng' => 35.4787],
            ['id' => 39, 'il_adi' => 'Kırklareli', 'plaka_kodu' => '39', 'lat' => 41.7333, 'lng' => 27.2167],
            ['id' => 40, 'il_adi' => 'Kırşehir', 'plaka_kodu' => '40', 'lat' => 39.1425, 'lng' => 34.1709],
            ['id' => 41, 'il_adi' => 'Kocaeli', 'plaka_kodu' => '41', 'lat' => 40.8533, 'lng' => 29.8815],
            ['id' => 42, 'il_adi' => 'Konya', 'plaka_kodu' => '42', 'lat' => 37.8746, 'lng' => 32.4932],
            ['id' => 43, 'il_adi' => 'Kütahya', 'plaka_kodu' => '43', 'lat' => 39.4167, 'lng' => 29.9833],
            ['id' => 44, 'il_adi' => 'Malatya', 'plaka_kodu' => '44', 'lat' => 38.3552, 'lng' => 38.3095],
            ['id' => 45, 'il_adi' => 'Manisa', 'plaka_kodu' => '45', 'lat' => 38.6191, 'lng' => 27.4289],
            ['id' => 46, 'il_adi' => 'Kahramanmaraş', 'plaka_kodu' => '46', 'lat' => 37.5858, 'lng' => 36.9371],
            ['id' => 47, 'il_adi' => 'Mardin', 'plaka_kodu' => '47', 'lat' => 37.3212, 'lng' => 40.7245],
            ['id' => 48, 'il_adi' => 'Muğla', 'plaka_kodu' => '48', 'lat' => 37.2153, 'lng' => 28.3636],
            ['id' => 49, 'il_adi' => 'Muş', 'plaka_kodu' => '49', 'lat' => 38.9462, 'lng' => 41.7539],
            ['id' => 50, 'il_adi' => 'Nevşehir', 'plaka_kodu' => '50', 'lat' => 38.6939, 'lng' => 34.6857],
            ['id' => 51, 'il_adi' => 'Niğde', 'plaka_kodu' => '51', 'lat' => 37.9667, 'lng' => 34.6833],
            ['id' => 52, 'il_adi' => 'Ordu', 'plaka_kodu' => '52', 'lat' => 40.9839, 'lng' => 37.8764],
            ['id' => 53, 'il_adi' => 'Rize', 'plaka_kodu' => '53', 'lat' => 41.0201, 'lng' => 40.5234],
            ['id' => 54, 'il_adi' => 'Sakarya', 'plaka_kodu' => '54', 'lat' => 40.6940, 'lng' => 30.4358],
            ['id' => 55, 'il_adi' => 'Samsun', 'plaka_kodu' => '55', 'lat' => 41.2867, 'lng' => 36.3300],
            ['id' => 56, 'il_adi' => 'Siirt', 'plaka_kodu' => '56', 'lat' => 37.9333, 'lng' => 41.9500],
            ['id' => 57, 'il_adi' => 'Sinop', 'plaka_kodu' => '57', 'lat' => 42.0231, 'lng' => 35.1531],
            ['id' => 58, 'il_adi' => 'Sivas', 'plaka_kodu' => '58', 'lat' => 39.7477, 'lng' => 37.0179],
            ['id' => 59, 'il_adi' => 'Tekirdağ', 'plaka_kodu' => '59', 'lat' => 41.0027, 'lng' => 27.5127],
            ['id' => 60, 'il_adi' => 'Tokat', 'plaka_kodu' => '60', 'lat' => 40.3167, 'lng' => 36.5544],
            ['id' => 61, 'il_adi' => 'Trabzon', 'plaka_kodu' => '61', 'lat' => 41.0027, 'lng' => 39.7168],
            ['id' => 62, 'il_adi' => 'Tunceli', 'plaka_kodu' => '62', 'lat' => 39.1079, 'lng' => 39.5401],
            ['id' => 63, 'il_adi' => 'Şanlıurfa', 'plaka_kodu' => '63', 'lat' => 37.1591, 'lng' => 38.7969],
            ['id' => 64, 'il_adi' => 'Uşak', 'plaka_kodu' => '64', 'lat' => 38.6823, 'lng' => 29.4082],
            ['id' => 65, 'il_adi' => 'Van', 'plaka_kodu' => '65', 'lat' => 38.4891, 'lng' => 43.4089],
            ['id' => 66, 'il_adi' => 'Yozgat', 'plaka_kodu' => '66', 'lat' => 39.8181, 'lng' => 34.8147],
            ['id' => 67, 'il_adi' => 'Zonguldak', 'plaka_kodu' => '67', 'lat' => 41.4564, 'lng' => 31.7987],
            ['id' => 68, 'il_adi' => 'Aksaray', 'plaka_kodu' => '68', 'lat' => 38.3687, 'lng' => 34.0370],
            ['id' => 69, 'il_adi' => 'Bayburt', 'plaka_kodu' => '69', 'lat' => 40.2552, 'lng' => 40.2249],
            ['id' => 70, 'il_adi' => 'Karaman', 'plaka_kodu' => '70', 'lat' => 37.1759, 'lng' => 33.2287],
            ['id' => 71, 'il_adi' => 'Kırıkkale', 'plaka_kodu' => '71', 'lat' => 39.8468, 'lng' => 33.5153],
            ['id' => 72, 'il_adi' => 'Batman', 'plaka_kodu' => '72', 'lat' => 37.8812, 'lng' => 41.1351],
            ['id' => 73, 'il_adi' => 'Şırnak', 'plaka_kodu' => '73', 'lat' => 37.5164, 'lng' => 42.4611],
            ['id' => 74, 'il_adi' => 'Bartın', 'plaka_kodu' => '74', 'lat' => 41.6344, 'lng' => 32.3375],
            ['id' => 75, 'il_adi' => 'Ardahan', 'plaka_kodu' => '75', 'lat' => 41.1105, 'lng' => 42.7022],
            ['id' => 76, 'il_adi' => 'Iğdır', 'plaka_kodu' => '76', 'lat' => 39.9237, 'lng' => 44.0450],
            ['id' => 77, 'il_adi' => 'Yalova', 'plaka_kodu' => '77', 'lat' => 40.6500, 'lng' => 29.2667],
            ['id' => 78, 'il_adi' => 'Karabük', 'plaka_kodu' => '78', 'lat' => 41.2061, 'lng' => 32.6204],
            ['id' => 79, 'il_adi' => 'Kilis', 'plaka_kodu' => '79', 'lat' => 36.7184, 'lng' => 37.1212],
            ['id' => 80, 'il_adi' => 'Osmaniye', 'plaka_kodu' => '80', 'lat' => 37.0746, 'lng' => 36.2464],
            ['id' => 81, 'il_adi' => 'Düzce', 'plaka_kodu' => '81', 'lat' => 40.8438, 'lng' => 31.1565],
        ];

        foreach ($iller as $il) {
            DB::table('iller')->updateOrInsert(
                ['id' => $il['id']],
                array_merge($il, [
                    'aktiflik_durumu' => 1,
                    'display_order' => $il['id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
        $this->command->info("✅ {$this->ilCount()} İl eklendi.");

        // ────────────────────────────────────────────
        // MUĞLA İLÇELERİ (Bodrum-First Strategy)
        // ────────────────────────────────────────────
        $muglaIlceleri = [
            ['id' => 1, 'il_id' => 48, 'ilce_adi' => 'Bodrum', 'lat' => 37.0344, 'lng' => 27.4305],
            ['id' => 2, 'il_id' => 48, 'ilce_adi' => 'Fethiye', 'lat' => 36.6538, 'lng' => 29.1258],
            ['id' => 3, 'il_id' => 48, 'ilce_adi' => 'Marmaris', 'lat' => 36.8510, 'lng' => 28.2671],
            ['id' => 4, 'il_id' => 48, 'ilce_adi' => 'Milas', 'lat' => 37.3175, 'lng' => 27.7839],
            ['id' => 5, 'il_id' => 48, 'ilce_adi' => 'Dalaman', 'lat' => 36.7667, 'lng' => 28.8000],
            ['id' => 6, 'il_id' => 48, 'ilce_adi' => 'Datça', 'lat' => 36.7333, 'lng' => 27.6833],
            ['id' => 7, 'il_id' => 48, 'ilce_adi' => 'Kavaklıdere', 'lat' => 37.4333, 'lng' => 28.3833],
            ['id' => 8, 'il_id' => 48, 'ilce_adi' => 'Köyceğiz', 'lat' => 36.9667, 'lng' => 28.6833],
            ['id' => 9, 'il_id' => 48, 'ilce_adi' => 'Menteşe', 'lat' => 37.2153, 'lng' => 28.3636],
            ['id' => 10, 'il_id' => 48, 'ilce_adi' => 'Ortaca', 'lat' => 36.8333, 'lng' => 28.7667],
            ['id' => 11, 'il_id' => 48, 'ilce_adi' => 'Seydikemer', 'lat' => 36.6167, 'lng' => 29.3500],
            ['id' => 12, 'il_id' => 48, 'ilce_adi' => 'Ula', 'lat' => 37.1000, 'lng' => 28.4167],
            ['id' => 13, 'il_id' => 48, 'ilce_adi' => 'Yatağan', 'lat' => 37.3333, 'lng' => 28.1333],
        ];

        foreach ($muglaIlceleri as $ilce) {
            DB::table('ilceler')->updateOrInsert(
                ['id' => $ilce['id']],
                array_merge($ilce, [
                    'aktiflik_durumu' => 1,
                    'display_order' => $ilce['id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
        $this->command->info("✅ " . count($muglaIlceleri) . " Muğla İlçesi eklendi.");

        // ────────────────────────────────────────────
        // BODRUM MAHALLELERİ
        // ────────────────────────────────────────────
        $bodrumMahalleleri = [
            ['id' => 1, 'ilce_id' => 1, 'mahalle_adi' => 'Yalıkavak', 'posta_kodu' => '48990', 'lat' => 37.1042, 'lng' => 27.2900],
            ['id' => 2, 'ilce_id' => 1, 'mahalle_adi' => 'Türkbükü', 'posta_kodu' => '48990', 'lat' => 37.1100, 'lng' => 27.3600],
            ['id' => 3, 'ilce_id' => 1, 'mahalle_adi' => 'Gündoğan', 'posta_kodu' => '48990', 'lat' => 37.0900, 'lng' => 27.3100],
            ['id' => 4, 'ilce_id' => 1, 'mahalle_adi' => 'Göltürkbükü', 'posta_kodu' => '48990', 'lat' => 37.1050, 'lng' => 27.3400],
            ['id' => 5, 'ilce_id' => 1, 'mahalle_adi' => 'Bitez', 'posta_kodu' => '48400', 'lat' => 37.0400, 'lng' => 27.4100],
            ['id' => 6, 'ilce_id' => 1, 'mahalle_adi' => 'Ortakent', 'posta_kodu' => '48400', 'lat' => 37.0500, 'lng' => 27.3600],
            ['id' => 7, 'ilce_id' => 1, 'mahalle_adi' => 'Yahşi', 'posta_kodu' => '48400', 'lat' => 37.0450, 'lng' => 27.3700],
            ['id' => 8, 'ilce_id' => 1, 'mahalle_adi' => 'Turgutreis', 'posta_kodu' => '48960', 'lat' => 37.0167, 'lng' => 27.2594],
            ['id' => 9, 'ilce_id' => 1, 'mahalle_adi' => 'Gümüşlük', 'posta_kodu' => '48960', 'lat' => 37.0500, 'lng' => 27.2333],
            ['id' => 10, 'ilce_id' => 1, 'mahalle_adi' => 'Akyarlar', 'posta_kodu' => '48960', 'lat' => 36.9833, 'lng' => 27.3167],
            ['id' => 11, 'ilce_id' => 1, 'mahalle_adi' => 'Torba', 'posta_kodu' => '48400', 'lat' => 37.0600, 'lng' => 27.4700],
            ['id' => 12, 'ilce_id' => 1, 'mahalle_adi' => 'Güvercinlik', 'posta_kodu' => '48400', 'lat' => 37.0100, 'lng' => 27.4800],
            ['id' => 13, 'ilce_id' => 1, 'mahalle_adi' => 'Konacık', 'posta_kodu' => '48400', 'lat' => 37.0500, 'lng' => 27.4400],
            ['id' => 14, 'ilce_id' => 1, 'mahalle_adi' => 'Kumbahçe', 'posta_kodu' => '48400', 'lat' => 37.0350, 'lng' => 27.4300],
            ['id' => 15, 'ilce_id' => 1, 'mahalle_adi' => 'Çarşı', 'posta_kodu' => '48400', 'lat' => 37.0344, 'lng' => 27.4305],
            ['id' => 16, 'ilce_id' => 1, 'mahalle_adi' => 'Tepecik', 'posta_kodu' => '48400', 'lat' => 37.0380, 'lng' => 27.4280],
            ['id' => 17, 'ilce_id' => 1, 'mahalle_adi' => 'Yokuşbaşı', 'posta_kodu' => '48400', 'lat' => 37.0370, 'lng' => 27.4250],
            ['id' => 18, 'ilce_id' => 1, 'mahalle_adi' => 'İçmeler', 'posta_kodu' => '48400', 'lat' => 37.0300, 'lng' => 27.4200],
            ['id' => 19, 'ilce_id' => 1, 'mahalle_adi' => 'Mumcular', 'posta_kodu' => '48400', 'lat' => 37.0700, 'lng' => 27.5600],
            ['id' => 20, 'ilce_id' => 1, 'mahalle_adi' => 'Karakaya', 'posta_kodu' => '48400', 'lat' => 37.0200, 'lng' => 27.3800],
        ];

        foreach ($bodrumMahalleleri as $mahalle) {
            DB::table('mahalleler')->updateOrInsert(
                ['id' => $mahalle['id']],
                array_merge($mahalle, [
                    'aktiflik_durumu' => 1,
                    'display_order' => $mahalle['id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
        $this->command->info("✅ " . count($bodrumMahalleleri) . " Bodrum Mahallesi eklendi.");
    }

    private function ilCount(): int
    {
        return DB::table('iller')->count();
    }
}
