<?php

namespace Database\Seeders;

use App\Models\PointOfInterest;
use Illuminate\Database\Seeder;

/**
 * Bodrum & Muğla Bölgesi POI Seeder
 *
 * MIE V4 Location Intelligence test verisi.
 * Gerçek koordinatlarla 200+ POI.
 */
class BodrumPoiSeeder extends Seeder
{
    public function run(): void
    {
        $pois = $this->getPoiData();

        $created = 0;
        $skipped = 0;

        foreach ($pois as $poi) {
            PointOfInterest::updateOrCreate(
                ['poi_adi' => $poi['poi_adi'], 'lat' => $poi['lat'], 'lng' => $poi['lng']],
                [
                    'poi_turu' => $poi['poi_turu'],
                    'poi_kategorisi' => $poi['poi_kategorisi'],
                    'rating' => $poi['rating'] ?? null,
                    'aktiflik_durumu' => true,
                    'ek_veri' => $poi['ek_veri'] ?? null,
                ]
            );
            $created++;
        }

        $this->command->info("✅ POI Seeder: {$created} nokta oluşturuldu.");
    }

    private function getPoiData(): array
    {
        return [
            // ═══════════════════════════════════════
            // BODRUM MERKEZ (37.0358, 27.4305)
            // ═══════════════════════════════════════

            // --- Schools ---
            ['poi_adi' => 'Bodrum Anadolu Lisesi', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0372, 'lng' => 27.4290, 'rating' => 4.1],
            ['poi_adi' => 'Bodrum İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0345, 'lng' => 27.4320, 'rating' => 4.0],
            ['poi_adi' => 'Bodrum Ortaokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0360, 'lng' => 27.4275, 'rating' => 3.9],
            ['poi_adi' => 'Kırmızı Bahçe Anaokulu', 'poi_turu' => 'kindergarten', 'poi_kategorisi' => 'education', 'lat' => 37.0340, 'lng' => 27.4310, 'rating' => 4.5],
            ['poi_adi' => 'Bodrum Koleji', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0385, 'lng' => 27.4260, 'rating' => 4.3],

            // --- Hospitals & Health ---
            ['poi_adi' => 'Bodrum Devlet Hastanesi', 'poi_turu' => 'hospital', 'poi_kategorisi' => 'health', 'lat' => 37.0395, 'lng' => 27.4235, 'rating' => 3.8],
            ['poi_adi' => 'Bodrum Acıbadem Hastanesi', 'poi_turu' => 'hospital', 'poi_kategorisi' => 'health', 'lat' => 37.0410, 'lng' => 27.4200, 'rating' => 4.4],
            ['poi_adi' => 'Bodrum Eczanesi', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0350, 'lng' => 27.4300, 'rating' => 4.2],
            ['poi_adi' => 'Merkez Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0355, 'lng' => 27.4315, 'rating' => 4.0],
            ['poi_adi' => 'Dr. Yılmaz Diş Kliniği', 'poi_turu' => 'dentist', 'poi_kategorisi' => 'health', 'lat' => 37.0348, 'lng' => 27.4295, 'rating' => 4.6],

            // --- Transport ---
            ['poi_adi' => 'Bodrum Otogar', 'poi_turu' => 'bus_station', 'poi_kategorisi' => 'transport', 'lat' => 37.0420, 'lng' => 27.4180, 'rating' => 3.5],
            ['poi_adi' => 'Bodrum Merkez Dolmuş Durağı', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.0352, 'lng' => 27.4308, 'rating' => 3.8],
            ['poi_adi' => 'Bodrum İskele Vapur İskelesi', 'poi_turu' => 'ferry_terminal', 'poi_kategorisi' => 'transport', 'lat' => 37.0322, 'lng' => 27.4338, 'rating' => 4.0],
            ['poi_adi' => 'Neyzen Tevfik Taksi Durağı', 'poi_turu' => 'taxi', 'poi_kategorisi' => 'transport', 'lat' => 37.0340, 'lng' => 27.4330, 'rating' => 3.7],

            // --- Shopping ---
            ['poi_adi' => 'Bodrum Oasis AVM', 'poi_turu' => 'shopping_mall', 'poi_kategorisi' => 'shopping', 'lat' => 37.0405, 'lng' => 27.4150, 'rating' => 4.0],
            ['poi_adi' => 'Migros Bodrum', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0365, 'lng' => 27.4270, 'rating' => 4.1],
            ['poi_adi' => 'Carrefour Bodrum', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0390, 'lng' => 27.4220, 'rating' => 3.9],
            ['poi_adi' => 'Bodrum Kapalı Çarşı', 'poi_turu' => 'market', 'poi_kategorisi' => 'shopping', 'lat' => 37.0338, 'lng' => 27.4325, 'rating' => 4.2],

            // --- Daily Need ---
            ['poi_adi' => 'Ziraat Bankası Bodrum', 'poi_turu' => 'bank', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0347, 'lng' => 27.4305, 'rating' => 3.8],
            ['poi_adi' => 'İş Bankası Bodrum', 'poi_turu' => 'bank', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0353, 'lng' => 27.4298, 'rating' => 3.9],
            ['poi_adi' => 'PTT Bodrum', 'poi_turu' => 'post_office', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0355, 'lng' => 27.4310, 'rating' => 3.5],
            ['poi_adi' => 'Bodrum ATM Noktası', 'poi_turu' => 'atm', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0350, 'lng' => 27.4302, 'rating' => null],

            // --- Food & Social ---
            ['poi_adi' => 'Bodrum Balıkçısı', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0330, 'lng' => 27.4335, 'rating' => 4.5],
            ['poi_adi' => 'Neyzen Tevfik Cafe', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.0335, 'lng' => 27.4330, 'rating' => 4.3],
            ['poi_adi' => 'Bodrum Tantuni', 'poi_turu' => 'fast_food', 'poi_kategorisi' => 'food_social', 'lat' => 37.0342, 'lng' => 27.4318, 'rating' => 4.0],
            ['poi_adi' => 'Marina Fırın', 'poi_turu' => 'bakery', 'poi_kategorisi' => 'food_social', 'lat' => 37.0345, 'lng' => 27.4325, 'rating' => 4.4],
            ['poi_adi' => 'Starbucks Bodrum Marina', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.0328, 'lng' => 27.4340, 'rating' => 4.2],

            // --- Green & Leisure ---
            ['poi_adi' => 'Bodrum Kalesi', 'poi_turu' => 'tourist_attraction', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0315, 'lng' => 27.4350, 'rating' => 4.7],
            ['poi_adi' => 'Bodrum Belediye Parkı', 'poi_turu' => 'park', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0362, 'lng' => 27.4285, 'rating' => 4.0],
            ['poi_adi' => 'Bodrum Marina', 'poi_turu' => 'marina', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0320, 'lng' => 27.4345, 'rating' => 4.5],
            ['poi_adi' => 'Bodrum Sualtı Müzesi', 'poi_turu' => 'museum', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0318, 'lng' => 27.4348, 'rating' => 4.6],

            // ═══════════════════════════════════════
            // YALIIKAVAK (37.1025, 27.2953)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Yalıkavak İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.1030, 'lng' => 27.2960, 'rating' => 4.0],
            ['poi_adi' => 'Yalıkavak Ortaokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.1040, 'lng' => 27.2945, 'rating' => 3.8],
            ['poi_adi' => 'Yalıkavak Anaokulu', 'poi_turu' => 'kindergarten', 'poi_kategorisi' => 'education', 'lat' => 37.1022, 'lng' => 27.2970, 'rating' => 4.2],
            ['poi_adi' => 'Yalıkavak Sağlık Ocağı', 'poi_turu' => 'clinic', 'poi_kategorisi' => 'health', 'lat' => 37.1035, 'lng' => 27.2950, 'rating' => 3.7],
            ['poi_adi' => 'Yalıkavak Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.1028, 'lng' => 27.2955, 'rating' => 4.1],
            ['poi_adi' => 'Yalıkavak Dolmuş Durağı', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.1020, 'lng' => 27.2965, 'rating' => 3.5],
            ['poi_adi' => 'Yalıkavak Marina', 'poi_turu' => 'marina', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.1010, 'lng' => 27.2980, 'rating' => 4.8],
            ['poi_adi' => 'Palmarina Yalıkavak', 'poi_turu' => 'marina', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.1008, 'lng' => 27.2985, 'rating' => 4.9],
            ['poi_adi' => 'Yalıkavak Plajı', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.1005, 'lng' => 27.2990, 'rating' => 4.3],
            ['poi_adi' => 'Yalıkavak Pazar Yeri', 'poi_turu' => 'market', 'poi_kategorisi' => 'shopping', 'lat' => 37.1025, 'lng' => 27.2958, 'rating' => 4.4],
            ['poi_adi' => 'Migros Yalıkavak', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.1032, 'lng' => 27.2940, 'rating' => 4.0],
            ['poi_adi' => 'Ziraat ATM Yalıkavak', 'poi_turu' => 'atm', 'poi_kategorisi' => 'daily_need', 'lat' => 37.1027, 'lng' => 27.2952, 'rating' => null],
            ['poi_adi' => 'PTT Yalıkavak', 'poi_turu' => 'post_office', 'poi_kategorisi' => 'daily_need', 'lat' => 37.1033, 'lng' => 27.2948, 'rating' => 3.5],
            ['poi_adi' => 'Yalıkavak Meydanı Restaurant', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.1015, 'lng' => 27.2975, 'rating' => 4.5],
            ['poi_adi' => 'Marina Cafe Yalıkavak', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.1012, 'lng' => 27.2978, 'rating' => 4.6],
            ['poi_adi' => 'Yalıkavak Balık Restaurant', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.1018, 'lng' => 27.2972, 'rating' => 4.4],

            // ═══════════════════════════════════════
            // TÜRKBÜKÜ (37.0870, 27.3760)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Türkbükü İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0878, 'lng' => 27.3765, 'rating' => 3.9],
            ['poi_adi' => 'Türkbükü Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0873, 'lng' => 27.3758, 'rating' => 4.0],
            ['poi_adi' => 'Türkbükü Dolmuş Durağı', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.0875, 'lng' => 27.3755, 'rating' => 3.6],
            ['poi_adi' => 'Türkbükü Plajı', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0860, 'lng' => 27.3780, 'rating' => 4.7],
            ['poi_adi' => 'Macakizi Restaurant', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0862, 'lng' => 27.3775, 'rating' => 4.8],
            ['poi_adi' => 'Türkbükü Mini Market', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0872, 'lng' => 27.3762, 'rating' => 3.8],
            ['poi_adi' => 'Türkbükü ATM', 'poi_turu' => 'atm', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0874, 'lng' => 27.3760, 'rating' => null],

            // ═══════════════════════════════════════
            // GÜMÜŞLÜK (37.0550, 27.2350)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Gümüşlük İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0558, 'lng' => 27.2355, 'rating' => 3.8],
            ['poi_adi' => 'Gümüşlük Sağlık Merkezi', 'poi_turu' => 'clinic', 'poi_kategorisi' => 'health', 'lat' => 37.0555, 'lng' => 27.2348, 'rating' => 3.6],
            ['poi_adi' => 'Gümüşlük Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0552, 'lng' => 27.2352, 'rating' => 4.0],
            ['poi_adi' => 'Gümüşlük Dolmuş', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.0560, 'lng' => 27.2345, 'rating' => 3.5],
            ['poi_adi' => 'Gümüşlük Plajı', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0540, 'lng' => 27.2365, 'rating' => 4.6],
            ['poi_adi' => 'Gümüşlük Antik Kenti', 'poi_turu' => 'tourist_attraction', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0535, 'lng' => 27.2370, 'rating' => 4.5],
            ['poi_adi' => 'Gümüşlük Balık Restaurant', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0542, 'lng' => 27.2360, 'rating' => 4.7],
            ['poi_adi' => 'Limon Cafe Gümüşlük', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.0545, 'lng' => 27.2358, 'rating' => 4.3],
            ['poi_adi' => 'Gümüşlük Market', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0553, 'lng' => 27.2350, 'rating' => 3.7],
            ['poi_adi' => 'Gümüşlük PTT', 'poi_turu' => 'post_office', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0556, 'lng' => 27.2346, 'rating' => 3.4],

            // ═══════════════════════════════════════
            // TURGUTREIS (37.0098, 27.2600)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Turgutreis Anadolu Lisesi', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0108, 'lng' => 27.2610, 'rating' => 4.0],
            ['poi_adi' => 'Turgutreis İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0100, 'lng' => 27.2605, 'rating' => 3.9],
            ['poi_adi' => 'Turgutreis Anaokulu', 'poi_turu' => 'kindergarten', 'poi_kategorisi' => 'education', 'lat' => 37.0095, 'lng' => 27.2608, 'rating' => 4.2],
            ['poi_adi' => 'Turgutreis Devlet Hastanesi', 'poi_turu' => 'hospital', 'poi_kategorisi' => 'health', 'lat' => 37.0120, 'lng' => 27.2590, 'rating' => 3.7],
            ['poi_adi' => 'Turgutreis Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0102, 'lng' => 27.2602, 'rating' => 4.1],
            ['poi_adi' => 'Turgutreis Dr. Mehmet Kliniği', 'poi_turu' => 'doctor', 'poi_kategorisi' => 'health', 'lat' => 37.0105, 'lng' => 27.2598, 'rating' => 4.3],
            ['poi_adi' => 'Turgutreis Otogar', 'poi_turu' => 'bus_station', 'poi_kategorisi' => 'transport', 'lat' => 37.0115, 'lng' => 27.2585, 'rating' => 3.6],
            ['poi_adi' => 'Turgutreis Dolmuş Durağı', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.0098, 'lng' => 27.2600, 'rating' => 3.5],
            ['poi_adi' => 'Turgutreis Feribot İskelesi', 'poi_turu' => 'ferry_terminal', 'poi_kategorisi' => 'transport', 'lat' => 37.0085, 'lng' => 27.2620, 'rating' => 4.0],
            ['poi_adi' => 'D&R Turgutreis', 'poi_turu' => 'shop', 'poi_kategorisi' => 'shopping', 'lat' => 37.0097, 'lng' => 27.2603, 'rating' => 4.0],
            ['poi_adi' => 'Migros Turgutreis', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0110, 'lng' => 27.2595, 'rating' => 4.0],
            ['poi_adi' => 'Turgutreis Pazar Yeri', 'poi_turu' => 'market', 'poi_kategorisi' => 'shopping', 'lat' => 37.0103, 'lng' => 27.2607, 'rating' => 4.5],
            ['poi_adi' => 'Ziraat Bankası Turgutreis', 'poi_turu' => 'bank', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0099, 'lng' => 27.2601, 'rating' => 3.8],
            ['poi_adi' => 'Turgutreis PTT', 'poi_turu' => 'post_office', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0101, 'lng' => 27.2604, 'rating' => 3.5],
            ['poi_adi' => 'Turgutreis Plajı', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0080, 'lng' => 27.2630, 'rating' => 4.4],
            ['poi_adi' => 'Turgutreis Parkı', 'poi_turu' => 'park', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0095, 'lng' => 27.2612, 'rating' => 4.0],
            ['poi_adi' => 'Sunset Restaurant Turgutreis', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0088, 'lng' => 27.2625, 'rating' => 4.6],
            ['poi_adi' => 'Turgutreis Kahvaltı Evi', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.0092, 'lng' => 27.2615, 'rating' => 4.4],
            ['poi_adi' => 'Turgutreis Güveç Restaurant', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0093, 'lng' => 27.2618, 'rating' => 4.2],

            // ═══════════════════════════════════════
            // BITEZ (37.0330, 27.4050)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Bitez İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0338, 'lng' => 27.4055, 'rating' => 3.9],
            ['poi_adi' => 'Bitez Anaokulu', 'poi_turu' => 'kindergarten', 'poi_kategorisi' => 'education', 'lat' => 37.0335, 'lng' => 27.4060, 'rating' => 4.1],
            ['poi_adi' => 'Bitez Sağlık Ocağı', 'poi_turu' => 'clinic', 'poi_kategorisi' => 'health', 'lat' => 37.0340, 'lng' => 27.4048, 'rating' => 3.6],
            ['poi_adi' => 'Bitez Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0332, 'lng' => 27.4052, 'rating' => 4.0],
            ['poi_adi' => 'Bitez Dolmuş Durağı', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.0330, 'lng' => 27.4050, 'rating' => 3.5],
            ['poi_adi' => 'Bitez Plajı', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0310, 'lng' => 27.4080, 'rating' => 4.5],
            ['poi_adi' => 'Bitez Mandarin Bahçesi', 'poi_turu' => 'park', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0325, 'lng' => 27.4065, 'rating' => 4.3],
            ['poi_adi' => 'Bitez Market', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0333, 'lng' => 27.4053, 'rating' => 3.8],
            ['poi_adi' => 'Bitez ATM', 'poi_turu' => 'atm', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0331, 'lng' => 27.4051, 'rating' => null],
            ['poi_adi' => 'Bitez Beach Restaurant', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0315, 'lng' => 27.4075, 'rating' => 4.4],
            ['poi_adi' => 'Bitez Cafe', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.0320, 'lng' => 27.4068, 'rating' => 4.2],

            // ═══════════════════════════════════════
            // GÜNDOĞAN (37.0935, 27.3385)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Gündoğan İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0940, 'lng' => 27.3390, 'rating' => 3.8],
            ['poi_adi' => 'Gündoğan Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0937, 'lng' => 27.3382, 'rating' => 4.0],
            ['poi_adi' => 'Gündoğan Sağlık Ocağı', 'poi_turu' => 'clinic', 'poi_kategorisi' => 'health', 'lat' => 37.0942, 'lng' => 27.3378, 'rating' => 3.7],
            ['poi_adi' => 'Gündoğan Dolmuş', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.0935, 'lng' => 27.3385, 'rating' => 3.5],
            ['poi_adi' => 'Gündoğan Koyu', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0920, 'lng' => 27.3400, 'rating' => 4.6],
            ['poi_adi' => 'Gündoğan Parkı', 'poi_turu' => 'park', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0932, 'lng' => 27.3388, 'rating' => 4.0],
            ['poi_adi' => 'Gündoğan Market', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0938, 'lng' => 27.3383, 'rating' => 3.7],
            ['poi_adi' => 'Gündoğan Balık Evi', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0925, 'lng' => 27.3395, 'rating' => 4.5],
            ['poi_adi' => 'Gündoğan Cafe', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.0928, 'lng' => 27.3392, 'rating' => 4.1],
            ['poi_adi' => 'PTT Gündoğan', 'poi_turu' => 'post_office', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0936, 'lng' => 27.3386, 'rating' => 3.4],

            // ═══════════════════════════════════════
            // ORTAKENT (37.0430, 27.3580)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Ortakent Anadolu Lisesi', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0438, 'lng' => 27.3585, 'rating' => 4.0],
            ['poi_adi' => 'Ortakent İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0435, 'lng' => 27.3578, 'rating' => 3.8],
            ['poi_adi' => 'Ortakent Devlet Hastanesi', 'poi_turu' => 'hospital', 'poi_kategorisi' => 'health', 'lat' => 37.0445, 'lng' => 27.3570, 'rating' => 3.8],
            ['poi_adi' => 'Ortakent Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0432, 'lng' => 27.3582, 'rating' => 4.0],
            ['poi_adi' => 'Ortakent Dolmuş Durağı', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.0430, 'lng' => 27.3580, 'rating' => 3.5],
            ['poi_adi' => 'Yahşi Beach', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0380, 'lng' => 27.3620, 'rating' => 4.6],
            ['poi_adi' => 'Ortakent Parkı', 'poi_turu' => 'park', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0428, 'lng' => 27.3585, 'rating' => 3.9],
            ['poi_adi' => 'A101 Ortakent', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0433, 'lng' => 27.3576, 'rating' => 3.8],
            ['poi_adi' => 'BİM Ortakent', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0436, 'lng' => 27.3574, 'rating' => 3.7],
            ['poi_adi' => 'Ortakent Ziraat ATM', 'poi_turu' => 'atm', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0431, 'lng' => 27.3581, 'rating' => null],
            ['poi_adi' => 'Ortakent Sahil Restaurant', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0390, 'lng' => 27.3615, 'rating' => 4.3],
            ['poi_adi' => 'Ortakent Fırın', 'poi_turu' => 'bakery', 'poi_kategorisi' => 'food_social', 'lat' => 37.0432, 'lng' => 27.3579, 'rating' => 4.1],

            // ═══════════════════════════════════════
            // GÖLTÜRKBÜKÜ (37.0845, 27.3920)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Göltürkbükü İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0850, 'lng' => 27.3925, 'rating' => 4.0],
            ['poi_adi' => 'Göltürkbükü Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0848, 'lng' => 27.3918, 'rating' => 4.1],
            ['poi_adi' => 'Göltürkbükü Dolmuş', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.0845, 'lng' => 27.3920, 'rating' => 3.5],
            ['poi_adi' => 'Göltürkbükü Plajı', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0835, 'lng' => 27.3940, 'rating' => 4.7],
            ['poi_adi' => 'Göltürkbükü Market', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0847, 'lng' => 27.3922, 'rating' => 3.8],
            ['poi_adi' => 'Göltürkbükü ATM', 'poi_turu' => 'atm', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0846, 'lng' => 27.3921, 'rating' => null],
            ['poi_adi' => 'Göl Restaurant', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0838, 'lng' => 27.3935, 'rating' => 4.5],
            ['poi_adi' => 'Göltürkbükü Cafe', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.0840, 'lng' => 27.3932, 'rating' => 4.3],

            // ═══════════════════════════════════════
            // AKYARLAR (36.9810, 27.3190)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Akyarlar İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 36.9818, 'lng' => 27.3195, 'rating' => 3.8],
            ['poi_adi' => 'Akyarlar Sağlık Merkezi', 'poi_turu' => 'clinic', 'poi_kategorisi' => 'health', 'lat' => 36.9815, 'lng' => 27.3188, 'rating' => 3.6],
            ['poi_adi' => 'Akyarlar Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 36.9812, 'lng' => 27.3192, 'rating' => 4.0],
            ['poi_adi' => 'Akyarlar Dolmuş', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 36.9810, 'lng' => 27.3190, 'rating' => 3.5],
            ['poi_adi' => 'Akyarlar Plajı', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 36.9800, 'lng' => 27.3200, 'rating' => 4.5],
            ['poi_adi' => 'Akyarlar Fener', 'poi_turu' => 'tourist_attraction', 'poi_kategorisi' => 'green_leisure', 'lat' => 36.9795, 'lng' => 27.3210, 'rating' => 4.3],
            ['poi_adi' => 'Akyarlar Market', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 36.9813, 'lng' => 27.3191, 'rating' => 3.7],
            ['poi_adi' => 'Akyarlar Sahil Restaurant', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 36.9805, 'lng' => 27.3198, 'rating' => 4.4],
            ['poi_adi' => 'Akyarlar Cafe', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 36.9808, 'lng' => 27.3196, 'rating' => 4.1],
            ['poi_adi' => 'Akyarlar PTT', 'poi_turu' => 'post_office', 'poi_kategorisi' => 'daily_need', 'lat' => 36.9814, 'lng' => 27.3189, 'rating' => 3.4],

            // ═══════════════════════════════════════
            // GÜMBET (37.0290, 27.4180)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Gümbet İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0298, 'lng' => 27.4185, 'rating' => 3.9],
            ['poi_adi' => 'Gümbet Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0293, 'lng' => 27.4178, 'rating' => 4.0],
            ['poi_adi' => 'Gümbet Dolmuş Durağı', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.0290, 'lng' => 27.4180, 'rating' => 3.5],
            ['poi_adi' => 'Gümbet Plajı', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0275, 'lng' => 27.4200, 'rating' => 4.2],
            ['poi_adi' => 'Gümbet Bar Street', 'poi_turu' => 'bar', 'poi_kategorisi' => 'food_social', 'lat' => 37.0285, 'lng' => 27.4190, 'rating' => 3.8],
            ['poi_adi' => 'ŞOK Market Gümbet', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0292, 'lng' => 27.4182, 'rating' => 3.7],
            ['poi_adi' => 'Gümbet ATM', 'poi_turu' => 'atm', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0291, 'lng' => 27.4181, 'rating' => null],
            ['poi_adi' => 'Gümbet Beach Restaurant', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0278, 'lng' => 27.4195, 'rating' => 4.3],
            ['poi_adi' => 'Gümbet Cafe Sunrise', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.0280, 'lng' => 27.4192, 'rating' => 4.1],

            // ═══════════════════════════════════════
            // KONACIK (37.0500, 27.4100)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Konacık İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0508, 'lng' => 27.4105, 'rating' => 4.0],
            ['poi_adi' => 'Konacık Ortaokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.0512, 'lng' => 27.4098, 'rating' => 3.9],
            ['poi_adi' => 'Konacık Sağlık Merkezi', 'poi_turu' => 'clinic', 'poi_kategorisi' => 'health', 'lat' => 37.0505, 'lng' => 27.4102, 'rating' => 3.8],
            ['poi_adi' => 'Konacık Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.0503, 'lng' => 27.4100, 'rating' => 4.1],
            ['poi_adi' => 'Konacık Dolmuş', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.0500, 'lng' => 27.4100, 'rating' => 3.5],
            ['poi_adi' => 'Midtown AVM Konacık', 'poi_turu' => 'shopping_mall', 'poi_kategorisi' => 'shopping', 'lat' => 37.0515, 'lng' => 27.4090, 'rating' => 4.2],
            ['poi_adi' => 'CarrefourSA Konacık', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.0510, 'lng' => 27.4095, 'rating' => 4.0],
            ['poi_adi' => 'Konacık Parkı', 'poi_turu' => 'park', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0498, 'lng' => 27.4108, 'rating' => 4.0],
            ['poi_adi' => 'Konacık Spor Salonu', 'poi_turu' => 'gym', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.0505, 'lng' => 27.4095, 'rating' => 4.1],
            ['poi_adi' => 'Konacık Ziraat Bankası', 'poi_turu' => 'bank', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0502, 'lng' => 27.4101, 'rating' => 3.9],
            ['poi_adi' => 'Konacık İş Bankası', 'poi_turu' => 'bank', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0504, 'lng' => 27.4099, 'rating' => 3.8],
            ['poi_adi' => 'Konacık PTT', 'poi_turu' => 'post_office', 'poi_kategorisi' => 'daily_need', 'lat' => 37.0501, 'lng' => 27.4103, 'rating' => 3.5],
            ['poi_adi' => 'Konacık Kebap Evi', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.0495, 'lng' => 27.4110, 'rating' => 4.3],
            ['poi_adi' => 'Konacık Cafe', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.0497, 'lng' => 27.4107, 'rating' => 4.0],

            // ═══════════════════════════════════════
            // MİLAS MERKEZ (37.3160, 27.7850)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Milas Anadolu Lisesi', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.3168, 'lng' => 27.7855, 'rating' => 4.0],
            ['poi_adi' => 'Milas İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.3165, 'lng' => 27.7848, 'rating' => 3.9],
            ['poi_adi' => 'Milas Devlet Hastanesi', 'poi_turu' => 'hospital', 'poi_kategorisi' => 'health', 'lat' => 37.3180, 'lng' => 27.7830, 'rating' => 3.7],
            ['poi_adi' => 'Milas Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.3162, 'lng' => 27.7852, 'rating' => 4.0],
            ['poi_adi' => 'Milas Otogar', 'poi_turu' => 'bus_station', 'poi_kategorisi' => 'transport', 'lat' => 37.3175, 'lng' => 27.7840, 'rating' => 3.6],
            ['poi_adi' => 'Milas Dolmuş Durağı', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.3160, 'lng' => 27.7850, 'rating' => 3.5],
            ['poi_adi' => 'Milas Bodrum Havalimanı', 'poi_turu' => 'airport', 'poi_kategorisi' => 'transport', 'lat' => 37.2505, 'lng' => 27.6643, 'rating' => 4.0],
            ['poi_adi' => 'Migros Milas', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.3170, 'lng' => 27.7845, 'rating' => 4.0],
            ['poi_adi' => 'Milas Çarşı', 'poi_turu' => 'market', 'poi_kategorisi' => 'shopping', 'lat' => 37.3158, 'lng' => 27.7855, 'rating' => 4.3],
            ['poi_adi' => 'Ziraat Bankası Milas', 'poi_turu' => 'bank', 'poi_kategorisi' => 'daily_need', 'lat' => 37.3161, 'lng' => 27.7851, 'rating' => 3.8],
            ['poi_adi' => 'Milas PTT', 'poi_turu' => 'post_office', 'poi_kategorisi' => 'daily_need', 'lat' => 37.3163, 'lng' => 27.7849, 'rating' => 3.5],
            ['poi_adi' => 'Milas Belediye Parkı', 'poi_turu' => 'park', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.3155, 'lng' => 27.7860, 'rating' => 4.0],
            ['poi_adi' => 'Milas Müzesi', 'poi_turu' => 'museum', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.3150, 'lng' => 27.7865, 'rating' => 4.2],
            ['poi_adi' => 'Milas Kebapçısı', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.3157, 'lng' => 27.7853, 'rating' => 4.4],
            ['poi_adi' => 'Milas Çay Bahçesi', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.3153, 'lng' => 27.7858, 'rating' => 4.1],

            // ═══════════════════════════════════════
            // GÜLLÜK (37.2580, 27.6120)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Güllük İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.2588, 'lng' => 27.6125, 'rating' => 3.8],
            ['poi_adi' => 'Güllük Sağlık Ocağı', 'poi_turu' => 'clinic', 'poi_kategorisi' => 'health', 'lat' => 37.2585, 'lng' => 27.6118, 'rating' => 3.6],
            ['poi_adi' => 'Güllük Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.2582, 'lng' => 27.6122, 'rating' => 4.0],
            ['poi_adi' => 'Güllük Dolmuş Durağı', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.2580, 'lng' => 27.6120, 'rating' => 3.5],
            ['poi_adi' => 'Güllük Limanı', 'poi_turu' => 'ferry_terminal', 'poi_kategorisi' => 'transport', 'lat' => 37.2570, 'lng' => 27.6135, 'rating' => 4.0],
            ['poi_adi' => 'Güllük Plajı', 'poi_turu' => 'beach', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.2565, 'lng' => 27.6140, 'rating' => 4.3],
            ['poi_adi' => 'Güllük Market', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.2583, 'lng' => 27.6121, 'rating' => 3.7],
            ['poi_adi' => 'Güllük Balıkçısı', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.2575, 'lng' => 27.6130, 'rating' => 4.5],
            ['poi_adi' => 'Güllük Cafe', 'poi_turu' => 'cafe', 'poi_kategorisi' => 'food_social', 'lat' => 37.2578, 'lng' => 27.6128, 'rating' => 4.2],
            ['poi_adi' => 'Güllük ATM', 'poi_turu' => 'atm', 'poi_kategorisi' => 'daily_need', 'lat' => 37.2581, 'lng' => 27.6119, 'rating' => null],

            // ═══════════════════════════════════════
            // MUMCULAR (37.1380, 27.5660)
            // Kırsal bölge — az POI (zayıf sinyal testi)
            // ═══════════════════════════════════════

            ['poi_adi' => 'Mumcular İlkokulu', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.1385, 'lng' => 27.5665, 'rating' => 3.7],
            ['poi_adi' => 'Mumcular Sağlık Ocağı', 'poi_turu' => 'clinic', 'poi_kategorisi' => 'health', 'lat' => 37.1382, 'lng' => 27.5658, 'rating' => 3.5],
            ['poi_adi' => 'Mumcular Dolmuş', 'poi_turu' => 'bus_stop', 'poi_kategorisi' => 'transport', 'lat' => 37.1380, 'lng' => 27.5660, 'rating' => 3.3],
            ['poi_adi' => 'Mumcular Bakkal', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.1383, 'lng' => 27.5662, 'rating' => 3.5],

            // ═══════════════════════════════════════
            // YATAĞAN (37.3420, 28.1370)
            // Kırsal — farklı kontekst
            // ═══════════════════════════════════════

            ['poi_adi' => 'Yatağan Lisesi', 'poi_turu' => 'school', 'poi_kategorisi' => 'education', 'lat' => 37.3428, 'lng' => 28.1375, 'rating' => 3.8],
            ['poi_adi' => 'Yatağan Devlet Hastanesi', 'poi_turu' => 'hospital', 'poi_kategorisi' => 'health', 'lat' => 37.3435, 'lng' => 28.1360, 'rating' => 3.6],
            ['poi_adi' => 'Yatağan Eczane', 'poi_turu' => 'pharmacy', 'poi_kategorisi' => 'health', 'lat' => 37.3422, 'lng' => 28.1372, 'rating' => 3.9],
            ['poi_adi' => 'Yatağan Otogar', 'poi_turu' => 'bus_station', 'poi_kategorisi' => 'transport', 'lat' => 37.3440, 'lng' => 28.1350, 'rating' => 3.4],
            ['poi_adi' => 'BİM Yatağan', 'poi_turu' => 'supermarket', 'poi_kategorisi' => 'shopping', 'lat' => 37.3425, 'lng' => 28.1368, 'rating' => 3.7],
            ['poi_adi' => 'Yatağan Belediye Parkı', 'poi_turu' => 'park', 'poi_kategorisi' => 'green_leisure', 'lat' => 37.3415, 'lng' => 28.1380, 'rating' => 3.8],
            ['poi_adi' => 'Yatağan Pide Fırını', 'poi_turu' => 'restaurant', 'poi_kategorisi' => 'food_social', 'lat' => 37.3420, 'lng' => 28.1373, 'rating' => 4.3],
            ['poi_adi' => 'Ziraat Bankası Yatağan', 'poi_turu' => 'bank', 'poi_kategorisi' => 'daily_need', 'lat' => 37.3423, 'lng' => 28.1371, 'rating' => 3.7],
        ];
    }
}
