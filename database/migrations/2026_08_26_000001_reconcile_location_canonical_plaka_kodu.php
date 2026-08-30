<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Location Canonical Reconciliation Migration
 *
 * PURPOSE:
 * Veritabanindaki eksik iller/ilceler/mahalleler kayitlarini
 * TurkiyeLocationSeeder canonical modeliyle uyumlu hale getirir.
 *
 * STRATEGY:
 * - Idempotent: Ayni migration birden cok calistirilabilir.
 * - iller: PLAKA_KODU bazli EXISTS check -> sadece eksik olanlari INSERT
 * - ilceler: ID bazli EXISTS check -> sadece eksik olanlari INSERT
 * - mahalleler: ID bazli EXISTS check -> sadece eksik olanlari INSERT
 * - Bodrum FK: ONCEKI degeri log'la, rollback'te geri yukle
 * - Orphan ilceler: RAPORLA, silme
 * - Tum islemler transaction icinde
 *
 * SAFETY RULES:
 * - TRUNCATE YOK
 * - Mevcut kayit overwrite YOK
 * - updateOrInsert(id=X) YOK
 * - Otomatik orphan silme YOK
 * - Bodrum FK degisikligi: oncki deger log'lanir ve rollback'te geri yuklenir
 */
return new class extends Migration
{
    /** Canonical 81 il verisi (TurkiyeLocationSeeder'dan) */
    private const CANONICAL_ILLER = [
        ['id' => 1,  'il_adi' => 'Adana',             'plaka_kodu' => '01', 'lat' => 37.0000,  'lng' => 35.3213],
        ['id' => 2,  'il_adi' => 'Adiyaman',          'plaka_kodu' => '02', 'lat' => 37.7648,  'lng' => 38.2786],
        ['id' => 3,  'il_adi' => 'Afyonkarahisar',    'plaka_kodu' => '03', 'lat' => 38.7507,  'lng' => 30.5567],
        ['id' => 4,  'il_adi' => 'Agri',              'plaka_kodu' => '04', 'lat' => 39.7191,  'lng' => 43.0503],
        ['id' => 5,  'il_adi' => 'Amasya',            'plaka_kodu' => '05', 'lat' => 40.6499,  'lng' => 35.8353],
        ['id' => 6,  'il_adi' => 'Ankara',            'plaka_kodu' => '06', 'lat' => 39.9334,  'lng' => 32.8597],
        ['id' => 7,  'il_adi' => 'Antalya',           'plaka_kodu' => '07', 'lat' => 36.8969,  'lng' => 30.7133],
        ['id' => 8,  'il_adi' => 'Artvin',            'plaka_kodu' => '08', 'lat' => 41.1828,  'lng' => 41.8183],
        ['id' => 9,  'il_adi' => 'Aydin',             'plaka_kodu' => '09', 'lat' => 37.8560,  'lng' => 27.8416],
        ['id' => 10, 'il_adi' => 'Balikesir',         'plaka_kodu' => '10', 'lat' => 39.6484,  'lng' => 27.8826],
        ['id' => 11, 'il_adi' => 'Bilecik',          'plaka_kodu' => '11', 'lat' => 40.0567,  'lng' => 30.0665],
        ['id' => 12, 'il_adi' => 'Bingol',            'plaka_kodu' => '12', 'lat' => 38.8854,  'lng' => 40.4966],
        ['id' => 13, 'il_adi' => 'Bitlis',            'plaka_kodu' => '13', 'lat' => 38.4006,  'lng' => 42.1095],
        ['id' => 14, 'il_adi' => 'Bolu',              'plaka_kodu' => '14', 'lat' => 40.7260,  'lng' => 31.6089],
        ['id' => 15, 'il_adi' => 'Burdur',            'plaka_kodu' => '15', 'lat' => 37.7203,  'lng' => 30.2908],
        ['id' => 16, 'il_adi' => 'Bursa',             'plaka_kodu' => '16', 'lat' => 40.1826,  'lng' => 29.0665],
        ['id' => 17, 'il_adi' => 'Canakkale',         'plaka_kodu' => '17', 'lat' => 40.1553,  'lng' => 26.4142],
        ['id' => 18, 'il_adi' => 'Cankiri',           'plaka_kodu' => '18', 'lat' => 40.6013,  'lng' => 33.6134],
        ['id' => 19, 'il_adi' => 'Corum',             'plaka_kodu' => '19', 'lat' => 40.5506,  'lng' => 34.9556],
        ['id' => 20, 'il_adi' => 'Denizli',           'plaka_kodu' => '20', 'lat' => 37.7765,  'lng' => 29.0864],
        ['id' => 21, 'il_adi' => 'Diyarbakir',        'plaka_kodu' => '21', 'lat' => 37.9144,  'lng' => 40.2306],
        ['id' => 22, 'il_adi' => 'Edirne',            'plaka_kodu' => '22', 'lat' => 41.6771,  'lng' => 26.5557],
        ['id' => 23, 'il_adi' => 'Elazig',            'plaka_kodu' => '23', 'lat' => 38.6810,  'lng' => 39.2264],
        ['id' => 24, 'il_adi' => 'Erzincan',          'plaka_kodu' => '24', 'lat' => 39.7500,  'lng' => 39.5000],
        ['id' => 25, 'il_adi' => 'Erzurum',           'plaka_kodu' => '25', 'lat' => 39.9000,  'lng' => 41.2700],
        ['id' => 26, 'il_adi' => 'Eskisehir',         'plaka_kodu' => '26', 'lat' => 39.7767,  'lng' => 30.5206],
        ['id' => 27, 'il_adi' => 'Gaziantep',         'plaka_kodu' => '27', 'lat' => 37.0662,  'lng' => 37.3833],
        ['id' => 28, 'il_adi' => 'Giresun',           'plaka_kodu' => '28', 'lat' => 40.9128,  'lng' => 38.3895],
        ['id' => 29, 'il_adi' => 'Gumushane',         'plaka_kodu' => '29', 'lat' => 40.4386,  'lng' => 39.5086],
        ['id' => 30, 'il_adi' => 'Hakkari',           'plaka_kodu' => '30', 'lat' => 37.5833,  'lng' => 43.7333],
        ['id' => 31, 'il_adi' => 'Hatay',             'plaka_kodu' => '31', 'lat' => 36.4018,  'lng' => 36.3498],
        ['id' => 32, 'il_adi' => 'Isparta',           'plaka_kodu' => '32', 'lat' => 37.7648,  'lng' => 30.5566],
        ['id' => 33, 'il_adi' => 'Mersin',            'plaka_kodu' => '33', 'lat' => 36.8121,  'lng' => 34.6415],
        ['id' => 34, 'il_adi' => 'Istanbul',           'plaka_kodu' => '34', 'lat' => 41.0082,  'lng' => 28.9784],
        ['id' => 35, 'il_adi' => 'Izmir',             'plaka_kodu' => '35', 'lat' => 38.4189,  'lng' => 27.1287],
        ['id' => 36, 'il_adi' => 'Kars',              'plaka_kodu' => '36', 'lat' => 40.6167,  'lng' => 43.1000],
        ['id' => 37, 'il_adi' => 'Kastamonu',         'plaka_kodu' => '37', 'lat' => 41.3887,  'lng' => 33.7827],
        ['id' => 38, 'il_adi' => 'Kayseri',           'plaka_kodu' => '38', 'lat' => 38.7312,  'lng' => 35.4787],
        ['id' => 39, 'il_adi' => 'Kirklareli',        'plaka_kodu' => '39', 'lat' => 41.7333,  'lng' => 27.2167],
        ['id' => 40, 'il_adi' => 'Kirsehir',          'plaka_kodu' => '40', 'lat' => 39.1425,  'lng' => 34.1709],
        ['id' => 41, 'il_adi' => 'Kocaeli',           'plaka_kodu' => '41', 'lat' => 40.8533,  'lng' => 29.8815],
        ['id' => 42, 'il_adi' => 'Konya',             'plaka_kodu' => '42', 'lat' => 37.8746,  'lng' => 32.4932],
        ['id' => 43, 'il_adi' => 'Kutahya',           'plaka_kodu' => '43', 'lat' => 39.4167,  'lng' => 29.9833],
        ['id' => 44, 'il_adi' => 'Malatya',           'plaka_kodu' => '44', 'lat' => 38.3552,  'lng' => 38.3095],
        ['id' => 45, 'il_adi' => 'Manisa',             'plaka_kodu' => '45', 'lat' => 38.6191,  'lng' => 27.4289],
        ['id' => 46, 'il_adi' => 'Kahramanmaras',     'plaka_kodu' => '46', 'lat' => 37.5858,  'lng' => 36.9371],
        ['id' => 47, 'il_adi' => 'Mardin',            'plaka_kodu' => '47', 'lat' => 37.3212,  'lng' => 40.7245],
        ['id' => 48, 'il_adi' => 'Mugla',             'plaka_kodu' => '48', 'lat' => 37.2153,  'lng' => 28.3636],
        ['id' => 49, 'il_adi' => 'Mus',               'plaka_kodu' => '49', 'lat' => 38.9462,  'lng' => 41.7539],
        ['id' => 50, 'il_adi' => 'Nevsehir',          'plaka_kodu' => '50', 'lat' => 38.6939,  'lng' => 34.6857],
        ['id' => 51, 'il_adi' => 'Nigde',             'plaka_kodu' => '51', 'lat' => 37.9667,  'lng' => 34.6833],
        ['id' => 52, 'il_adi' => 'Ordu',              'plaka_kodu' => '52', 'lat' => 40.9839,  'lng' => 37.8764],
        ['id' => 53, 'il_adi' => 'Rize',              'plaka_kodu' => '53', 'lat' => 41.0201,  'lng' => 40.5234],
        ['id' => 54, 'il_adi' => 'Sakarya',           'plaka_kodu' => '54', 'lat' => 40.6940,  'lng' => 30.4358],
        ['id' => 55, 'il_adi' => 'Samsun',            'plaka_kodu' => '55', 'lat' => 41.2867,  'lng' => 36.3300],
        ['id' => 56, 'il_adi' => 'Siirt',             'plaka_kodu' => '56', 'lat' => 37.9333,  'lng' => 41.9500],
        ['id' => 57, 'il_adi' => 'Sinop',             'plaka_kodu' => '57', 'lat' => 42.0231,  'lng' => 35.1531],
        ['id' => 58, 'il_adi' => 'Sivas',             'plaka_kodu' => '58', 'lat' => 39.7477,  'lng' => 37.0179],
        ['id' => 59, 'il_adi' => 'Tekirdag',          'plaka_kodu' => '59', 'lat' => 41.0027,  'lng' => 27.5127],
        ['id' => 60, 'il_adi' => 'Tokat',             'plaka_kodu' => '60', 'lat' => 40.3167,  'lng' => 36.5544],
        ['id' => 61, 'il_adi' => 'Trabzon',           'plaka_kodu' => '61', 'lat' => 41.0027,  'lng' => 39.7168],
        ['id' => 62, 'il_adi' => 'Tunceli',           'plaka_kodu' => '62', 'lat' => 39.1079,  'lng' => 39.5401],
        ['id' => 63, 'il_adi' => 'Sanliurfa',        'plaka_kodu' => '63', 'lat' => 37.1591,  'lng' => 38.7969],
        ['id' => 64, 'il_adi' => 'Usak',              'plaka_kodu' => '64', 'lat' => 38.6823,  'lng' => 29.4082],
        ['id' => 65, 'il_adi' => 'Van',               'plaka_kodu' => '65', 'lat' => 38.4891,  'lng' => 43.4089],
        ['id' => 66, 'il_adi' => 'Yozgat',            'plaka_kodu' => '66', 'lat' => 39.8181,  'lng' => 34.8147],
        ['id' => 67, 'il_adi' => 'Zonguldak',         'plaka_kodu' => '67', 'lat' => 41.4564,  'lng' => 31.7987],
        ['id' => 68, 'il_adi' => 'Aksaray',           'plaka_kodu' => '68', 'lat' => 38.3687,  'lng' => 34.0370],
        ['id' => 69, 'il_adi' => 'Bayburt',           'plaka_kodu' => '69', 'lat' => 40.2552,  'lng' => 40.2249],
        ['id' => 70, 'il_adi' => 'Karaman',           'plaka_kodu' => '70', 'lat' => 37.1759,  'lng' => 33.2287],
        ['id' => 71, 'il_adi' => 'Kirikkale',        'plaka_kodu' => '71', 'lat' => 39.8468,  'lng' => 33.5153],
        ['id' => 72, 'il_adi' => 'Batman',            'plaka_kodu' => '72', 'lat' => 37.8812,  'lng' => 41.1351],
        ['id' => 73, 'il_adi' => 'Sirnak',            'plaka_kodu' => '73', 'lat' => 37.5164,  'lng' => 42.4611],
        ['id' => 74, 'il_adi' => 'Bartin',            'plaka_kodu' => '74', 'lat' => 41.6344,  'lng' => 32.3375],
        ['id' => 75, 'il_adi' => 'Ardahan',           'plaka_kodu' => '75', 'lat' => 41.1105,  'lng' => 42.7022],
        ['id' => 76, 'il_adi' => 'Igdir',             'plaka_kodu' => '76', 'lat' => 39.9237,  'lng' => 44.0450],
        ['id' => 77, 'il_adi' => 'Yalova',            'plaka_kodu' => '77', 'lat' => 40.6500,  'lng' => 29.2667],
        ['id' => 78, 'il_adi' => 'Karabuk',           'plaka_kodu' => '78', 'lat' => 41.2061,  'lng' => 32.6204],
        ['id' => 79, 'il_adi' => 'Kilis',             'plaka_kodu' => '79', 'lat' => 36.7184,  'lng' => 37.1212],
        ['id' => 80, 'il_adi' => 'Osmaniye',          'plaka_kodu' => '80', 'lat' => 37.0746,  'lng' => 36.2464],
        ['id' => 81, 'il_adi' => 'Duzce',             'plaka_kodu' => '81', 'lat' => 40.8438,  'lng' => 31.1565],
    ];

    /** Mugla (id=48) ilceleri */
    private const CANONICAL_ILCELER = [
        ['id' => 1,  'il_id' => 48, 'ilce_adi' => 'Bodrum',       'lat' => 37.0344, 'lng' => 27.4305],
        ['id' => 2,  'il_id' => 48, 'ilce_adi' => 'Fethiye',     'lat' => 36.6538, 'lng' => 29.1258],
        ['id' => 3,  'il_id' => 48, 'ilce_adi' => 'Marmaris',    'lat' => 36.8510, 'lng' => 28.2671],
        ['id' => 4,  'il_id' => 48, 'ilce_adi' => 'Milas',       'lat' => 37.3175, 'lng' => 27.7839],
        ['id' => 5,  'il_id' => 48, 'ilce_adi' => 'Dalaman',     'lat' => 36.7667, 'lng' => 28.8000],
        ['id' => 6,  'il_id' => 48, 'ilce_adi' => 'Datca',        'lat' => 36.7333, 'lng' => 27.6833],
        ['id' => 7,  'il_id' => 48, 'ilce_adi' => 'Kavaklidere', 'lat' => 37.4333, 'lng' => 28.3833],
        ['id' => 8,  'il_id' => 48, 'ilce_adi' => 'Koycegiz',    'lat' => 36.9667, 'lng' => 28.6833],
        ['id' => 9,  'il_id' => 48, 'ilce_adi' => 'Mentese',     'lat' => 37.2153, 'lng' => 28.3636],
        ['id' => 10, 'il_id' => 48, 'ilce_adi' => 'Ortaca',       'lat' => 36.8333, 'lng' => 28.7667],
        ['id' => 11, 'il_id' => 48, 'ilce_adi' => 'Seydikemer',   'lat' => 36.6167, 'lng' => 29.3500],
        ['id' => 12, 'il_id' => 48, 'ilce_adi' => 'Ula',          'lat' => 37.1000, 'lng' => 28.4167],
        ['id' => 13, 'il_id' => 48, 'ilce_adi' => 'Yatagan',      'lat' => 37.3333, 'lng' => 28.1333],
    ];

    /** Bodrum (ilce_id=1) mahalleleri */
    private const CANONICAL_MAHALLER = [
        ['id' => 1,  'ilce_id' => 1, 'mahalle_adi' => 'Yalikavak',     'posta_kodu' => '48990', 'lat' => 37.1042, 'lng' => 27.2900],
        ['id' => 2,  'ilce_id' => 1, 'mahalle_adi' => 'Tuerkbuekue',     'posta_kodu' => '48990', 'lat' => 37.1100, 'lng' => 27.3600],
        ['id' => 3,  'ilce_id' => 1, 'mahalle_adi' => 'Guendogan',    'posta_kodu' => '48990', 'lat' => 37.0900, 'lng' => 27.3100],
        ['id' => 4,  'ilce_id' => 1, 'mahalle_adi' => 'Goltuerkbuekue', 'posta_kodu' => '48990', 'lat' => 37.1050, 'lng' => 27.3400],
        ['id' => 5,  'ilce_id' => 1, 'mahalle_adi' => 'Bitez',       'posta_kodu' => '48400', 'lat' => 37.0400, 'lng' => 27.4100],
        ['id' => 6,  'ilce_id' => 1, 'mahalle_adi' => 'Ortakent',    'posta_kodu' => '48400', 'lat' => 37.0500, 'lng' => 27.3600],
        ['id' => 7,  'ilce_id' => 1, 'mahalle_adi' => 'Yahsi',       'posta_kodu' => '48400', 'lat' => 37.0450, 'lng' => 27.3700],
        ['id' => 8,  'ilce_id' => 1, 'mahalle_adi' => 'Turgutreis',  'posta_kodu' => '48960', 'lat' => 37.0167, 'lng' => 27.2594],
        ['id' => 9,  'ilce_id' => 1, 'mahalle_adi' => 'Gumuesluek',   'posta_kodu' => '48960', 'lat' => 37.0500, 'lng' => 27.2333],
        ['id' => 10, 'ilce_id' => 1, 'mahalle_adi' => 'Akyarlar',    'posta_kodu' => '48960', 'lat' => 36.9833, 'lng' => 27.3167],
        ['id' => 11, 'ilce_id' => 1, 'mahalle_adi' => 'Torba',        'posta_kodu' => '48400', 'lat' => 37.0600, 'lng' => 27.4700],
        ['id' => 12, 'ilce_id' => 1, 'mahalle_adi' => 'Guvercinlik', 'posta_kodu' => '48400', 'lat' => 37.0100, 'lng' => 27.4800],
        ['id' => 13, 'ilce_id' => 1, 'mahalle_adi' => 'Konacik',     'posta_kodu' => '48400', 'lat' => 37.0500, 'lng' => 27.4400],
        ['id' => 14, 'ilce_id' => 1, 'mahalle_adi' => 'Kumbahce',    'posta_kodu' => '48400', 'lat' => 37.0350, 'lng' => 27.4300],
        ['id' => 15, 'ilce_id' => 1, 'mahalle_adi' => 'Carssi',       'posta_kodu' => '48400', 'lat' => 37.0344, 'lng' => 27.4305],
        ['id' => 16, 'ilce_id' => 1, 'mahalle_adi' => 'Tepecik',    'posta_kodu' => '48400', 'lat' => 37.0380, 'lng' => 27.4280],
        ['id' => 17, 'ilce_id' => 1, 'mahalle_adi' => 'Yokusbasi',   'posta_kodu' => '48400', 'lat' => 37.0370, 'lng' => 27.4250],
        ['id' => 18, 'ilce_id' => 1, 'mahalle_adi' => 'Icmeler',    'posta_kodu' => '48400', 'lat' => 37.0300, 'lng' => 27.4200],
        ['id' => 19, 'ilce_id' => 1, 'mahalle_adi' => 'Mumcular',   'posta_kodu' => '48400', 'lat' => 37.0700, 'lng' => 27.5600],
        ['id' => 20, 'ilce_id' => 1, 'mahalle_adi' => 'Karakaya',    'posta_kodu' => '48400', 'lat' => 37.0200, 'lng' => 27.3800],
    ];

    private const MIGRATION_SIGNATURE = '2026_08_26_reconcile_locations';

    public function up(): void
    {
        $this->createLogTable();

        // PRE-CHECK: Transaction oncesi mevcut kayitlari oku
        $rawPlakalar = DB::table('iller')->pluck('plaka_kodu')->toArray();
        $mevcutIllerPlakalari = collect($rawPlakalar)
            ->map(fn($p) => str_pad((string) $p, 2, '0', STR_PAD_LEFT))
            ->toArray();

        file_put_contents('/tmp/mig_precheck.txt',
            'RAW:' . json_encode($rawPlakalar) . "\n" .
            'NORM:' . json_encode($mevcutIllerPlakalari) . "\n" .
            'HAS_06:' . (in_array('06', $mevcutIllerPlakalari) ? 'YES' : 'NO') . "\n" .
            'HAS_34:' . (in_array('34', $mevcutIllerPlakalari) ? 'YES' : 'NO') . "\n" .
            'HAS_48:' . (in_array('48', $mevcutIllerPlakalari) ? 'YES' : 'NO') . "\n"
        );
        $mevcutIlceIdleri = DB::table('ilceler')->pluck('id')->toArray();
        $mevcutMahalleIdleri = DB::table('mahalleler')->pluck('id')->toArray();

        // PRE-SNAPSHOT: Mevcut ilceler.il_id -> down() rollback icin sakla
        // PHASE 1/2 iller ID tasimalarindan sonra ilceler.il_id cascade guncellenir.
        // down() reversal'da eski iller ID'leri artik mevcut olmayacagi icin
        // ilceler.il_id FK'lari orphan kalir. Bu snapshot ile her ilcenin orijinal
        // il_id'si saklanir -- down() reversal SONRASI ilceler.il_id geri yuklenir.
        $ilceSnapshot = DB::table('ilceler')
            ->get(['id', 'il_id'])
            ->keyBy('id')
            ->map(fn($r) => (int) $r->il_id)
            ->toArray();

        $orphanReport = [];

        // Orphan ilceler raporla (silme)
        $validIlIds = collect(self::CANONICAL_ILLER)->pluck('id')->toArray();
        $orphanIlceler = DB::table('ilceler')
            ->whereNotIn('il_id', $validIlIds)
            ->get(['id', 'il_id', 'ilce_adi']);
        foreach ($orphanIlceler as $o) {
            $orphanReport[] = [
                'table' => 'ilceler',
                'id' => $o->id,
                'il_id' => $o->il_id,
                'name' => $o->ilce_adi,
                'issue' => 'il_id references non-canonical il',
            ];
        }

        $counts = ['iller' => 0, 'ilceler' => 0, 'mahalleler' => 0, 'bodrum_fk_update' => false];

        DB::transaction(function () use (
            &$counts, $mevcutIllerPlakalari, $mevcutIlceIdleri,
            $mevcutMahalleIdleri, $ilceSnapshot, $orphanReport
        ) {
            // Bodrum_FK yakalama notu:
            // Bodrum kaydinin orijinal il_id'si transaction basinda yakalanabilir,
            // ancak PHASE 4'te Bodrum'un GUNCEL il_id'si dogrudan okunur.
            // Bodrum zaten canonical il_id=48 ile basladiysa (State 3 gibi):
            //   - Bodrum id=1'de DEGIL (orn. id=14) -> find(1) = null -> do nothing
            // Bodrum id=1'de ve il_id=48 degilse (State 1 gibi):
            //   - Bodrum id=1, il_id=1 -> find(1) Bodrum -> il_id != 48 -> update + log
            // Bodrum id=1'de ve il_id=48 ise (zaten dogru):
            //   - Bodrum id=1, il_id=48 -> find(1) Bodrum -> il_id = 48 -> do nothing
            // down() rollback'te bodrum_fk_reconcile_log'dan eski degere geri yuklenir.

            // PRE-ILCE-FK-SNAPSHOT: Bodrum il_id snapshot (down() reversal sonrasi)
            // Bodrum il_id'si down() reversal sonrasi snapshot'tan geri yuklenecek.
            // Bodrum'un orijinal il_id'si (up() basinda) ve güncel il_id'si (PHASE 1 sonrasi)
            // arasindaki fark, Bodrum FK degisikligi mi yoksa Bodrum ID tasiamasi mi
            // oldugunu belirler. Bodrum id=14->1 tasiamasinda Bodrum'un güncel il_id=48 okunur
            // (Bodrum zaten Mugla'nin altinda) -- Bodrum_FK log YOK.

            // PHASE 1: Mevcut illerin ID'lerini canonical ID'lere tasi
            // Plaka kodu eslesmesiyle mevcut kaydin ID'si canonical ID'ye guncellenir.
            // Bu, ID cakismasini onler ve canonical ID tutarliligi saglar.
            // FK referanslari (ilceler.il_id) da guncellenir.
            $idMappings = []; // eski_id => yeni_id
            foreach (self::CANONICAL_ILLER as $il) {
                // Plaka kodu normalize edilerek eslestirilir (orn. '6' -> '06')
                $mevcut = DB::table('iller')
                    ->get(['id', 'plaka_kodu'])
                    ->first(function ($row) use ($il) {
                        $norm = str_pad((string) $row->plaka_kodu, 2, '0', STR_PAD_LEFT);
                        return $norm === $il['plaka_kodu'];
                    });
                if (!$mevcut) {
                    continue; // Eksik -- PHASE 2'de eklenecek
                }
                $eskiId = (int) $mevcut->id;
                $yeniId = (int) $il['id'];
                if ($eskiId === $yeniId) {
                    continue; // Zaten canonical ID
                }
                // ID cakismasi kontrolue: hedef ID basa bir kayit tarafindan kullaniliyor mu?
                $cakisan = DB::table('iller')->where('id', $yeniId)->first();
                if ($cakisan) {
                    // Hedef ID basa bir il tarafindan kullaniliyor.
                    // Bu il de canonical ID'sine tasinmali (ozyinelemeli).
                    // Guvenlik: cakisan kaydi gecici olarak yuksek bir ID'ye tasi,
                    // sonra asil kaydi canonical ID'ye tasi, sonra cakisani kendi canonical ID'sine tasi.
                    $tempId = 90000 + $yeniId;
                    DB::table('iller')->where('id', $yeniId)->update(['id' => $tempId]);
                    $idMappings[$yeniId] = $tempId;
                }
                DB::table('iller')->where('id', $eskiId)->update(['id' => $yeniId]);
                $idMappings[$eskiId] = $yeniId;
                $this->logIdMove('iller', $eskiId, $yeniId);
                $counts['iller']++;
            }

            // FK referanslarini guncelle (ilceler.il_id)
            foreach ($idMappings as $eskiId => $yeniId) {
                DB::table('ilceler')->where('il_id', $eskiId)->update(['il_id' => $yeniId]);
            }

            // PHASE 2: Idempotent iller INSERT (sadece eksik plakalar)
            foreach (self::CANONICAL_ILLER as $il) {
                if (in_array($il['plaka_kodu'], $mevcutIllerPlakalari)) {
                    continue; // Zaten var -- idempotent skip
                }
                $now = now();
                DB::table('iller')->insert([
                    'id' => $il['id'],
                    'il_adi' => $il['il_adi'],
                    'plaka_kodu' => $il['plaka_kodu'],
                    'lat' => $il['lat'],
                    'lng' => $il['lng'],
                    'aktiflik_durumu' => true,
                    'display_order' => $il['id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->logInsert('iller', $il['id']);
                $counts['iller']++;
            }

            // PHASE 3: Idempotent ilceler INSERT (sadece eksik ID'ler)
            // once mevcut ilcelerin ID'lerini canonical ID'lere tasi (isim eslesmesiyle).
            // Bu, ID cakismasini onler ve canonical ID tutarliligi saglar.
            $ilceIdMappings = []; // eski_id => yeni_id
            // Mevcut tum ilceleri oku (ID yeniden esleme icin)
            $mevcutIlceler = DB::table('ilceler')->get(['id', 'il_id', 'ilce_adi']);
            // Her mevcut ilce icin canonical ID'yi belirle (isim + il eslesmesi)
            $canonicalByKey = [];
            foreach (self::CANONICAL_ILCELER as $ilce) {
                $key = (int) $ilce['il_id'] . '|' . mb_strtolower(trim($ilce['ilce_adi']));
                $canonicalByKey[$key] = (int) $ilce['id'];
            }
            // Mevcut ilcelerin canonical ID'lerini hesapla
            $idPlan = []; // eski_id => yeni_id (canonical)
            $usedCanonicalIds = [];
            foreach ($mevcutIlceler as $row) {
                $key = (int) $row->il_id . '|' . mb_strtolower(trim($row->ilce_adi));
                if (isset($canonicalByKey[$key])) {
                    $canonicalId = $canonicalByKey[$key];
                    $idPlan[(int) $row->id] = $canonicalId;
                    $usedCanonicalIds[] = $canonicalId;
                }
            }
            // ID'leri guvenli sekilde yeniden esle (zincirleme cakismalari coz)
            // Strateji: Tum tasiacak kayitlari once gecici ID'lere tasi, sonra
            // gecici ID'lerden canonical ID'lere tasi. Bu, zincirleme tasimalarda
            // (2->3, 3->4) cakismayi onler.
            $tempMoves = []; // eski_id => temp_id
            foreach ($idPlan as $eskiId => $yeniId) {
                if ($eskiId === $yeniId) continue;
                $tempId = 99900 + $eskiId;
                DB::table('ilceler')->where('id', $eskiId)->update(['id' => $tempId]);
                $tempMoves[$eskiId] = $tempId;
                $this->logIdMove('ilceler', $eskiId, $tempId);
            }
            // Gecici ID'lerden canonical ID'lere tasi
            foreach ($idPlan as $eskiId => $yeniId) {
                if ($eskiId === $yeniId) continue;
                $tempId = $tempMoves[$eskiId];
                // Hedef ID'de tasiimayacak bir kayit varsa, onu gecici ID'ye tasi
                $hedefDolu = DB::table('ilceler')->where('id', $yeniId)->exists();
                if ($hedefDolu) {
                    $temp2 = 99900 + $yeniId;
                    DB::table('ilceler')->where('id', $yeniId)->update(['id' => $temp2]);
                    $this->logIdMove('ilceler', $yeniId, $temp2);
                }
                DB::table('ilceler')->where('id', $tempId)->update(['id' => $yeniId]);
                $ilceIdMappings[$eskiId] = $yeniId;
                $this->logIdMove('ilceler', $tempId, $yeniId);
                $counts['ilceler']++;
            }
            // Non-canonical ilceleri canonical ID'lerin disina tasi
            // Canonical ilcelerin ID'leri 1-13'tuer. Non-canonical ilceler
            // (Kadikoy, Besiktas gibi canonical'da olmayanlar) bu ID'leri
            // isgal ederse canonical ilceler (Dalaman=5, Datca=6) eklenemez.
            // Bu yuzden non-canonical ilceler canonical ID'lerin sonrasina
            // (14+) tassinir. Bu, hem gecici ID'de kalanlari (99900+) hem de
            // dusuk ID'de kalanlari (orn. Kadikoy=5) kapsar.
            $canonicalIlceIds = array_map('intval', array_column(self::CANONICAL_ILCELER, 'id'));
            $maxCanonicalIlceId = max($canonicalIlceIds); // 13
            $nextIlceId = $maxCanonicalIlceId + 1; // 14
            $nonCanonicalIlceler = DB::table('ilceler')->get(['id', 'il_id', 'ilce_adi']);
            foreach ($nonCanonicalIlceler as $row) {
                $key = (int) $row->il_id . '|' . mb_strtolower(trim($row->ilce_adi));
                if (isset($canonicalByKey[$key])) {
                    continue; // canonical ilce -- dokunma
                }
                $eskiId = (int) $row->id;
                if ($eskiId === $nextIlceId) {
                    $nextIlceId++; // bu ID zaten kullaniliyor
                    continue;
                }
                // Hedef ID'de basa bir kayit varsa atla
                while (DB::table('ilceler')->where('id', $nextIlceId)->exists()) {
                    $nextIlceId++;
                }
                DB::table('ilceler')->where('id', $eskiId)->update(['id' => $nextIlceId]);
                $ilceIdMappings[$eskiId] = $nextIlceId;
                $this->logIdMove('ilceler', $eskiId, $nextIlceId);
                $nextIlceId++;
            }

            // FK referanslarini guncelle (mahalleler.ilce_id)
            foreach ($ilceIdMappings as $eskiId => $yeniId) {
                DB::table('mahalleler')->where('ilce_id', $eskiId)->update(['ilce_id' => $yeniId]);
            }

            // Mevcut ilce ID'lerini yeniden oku (ID tasiama sonrasi)
            $mevcutIlceIdleri = DB::table('ilceler')->pluck('id')->toArray();

            // Eksik ilceleri ekle
            foreach (self::CANONICAL_ILCELER as $ilce) {
                if (!in_array($ilce['il_id'], array_column(self::CANONICAL_ILLER, 'id'))) {
                    continue; // Guvenlik: canonical olmayan il
                }
                if (in_array($ilce['id'], $mevcutIlceIdleri)) {
                    continue; // Zaten var
                }
                $now = now();
                DB::table('ilceler')->insert([
                    'id' => $ilce['id'],
                    'il_id' => $ilce['il_id'],
                    'ilce_adi' => $ilce['ilce_adi'],
                    'lat' => $ilce['lat'],
                    'lng' => $ilce['lng'],
                    'aktiflik_durumu' => true,
                    'display_order' => $ilce['id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->logInsert('ilceler', $ilce['id']);
                $counts['ilceler']++;
            }

            // PHASE 4: Bodrum FK repair + idempotent mahalleler
            // Bodrum'un il_id'si canonical Mugla(id=48) olmalidir.
            //
            // Strateji: Bodrum'un GUNCEL il_id'sini kontrol et.
            // Bodrum id=14->1 tasiamasindan sonra find(1) Bodrum'un kendisini dondurur --
            // bodrumFkPrev artik Bodrum'un orijinal degil, Bodrum'un güncel il_id'sini tutar.
            // Bu yuzden Bodrum'un mevcut il_id'sini dogrudan okuyup 48'den farkli
            // olup olmadigini kontrol ederiz.
            //
            // down() rollback'te sadece bodrum_fk_reconcile_log'dan eski degere
            // geri yuklenir. Bodrum'un il_id zaten 48 ise log YOK -> down()
            // Bodrum'a dokunmaz -> Bodrum il_id=48 korunur.
            $bodrumIlceId = 1;
            $bodrumCurrent = DB::table('ilceler')->find($bodrumIlceId);
            if ($bodrumCurrent && (int) $bodrumCurrent->il_id !== 48) {
                // Bodrum'un güncel il_id'si 48 DEGIL -> guncelle ve logla.
                // down() rollback'te eski degere (orn. 1) geri yuklenir.
                $previousIlId = (int) $bodrumCurrent->il_id;
                DB::table('ilceler')
                    ->where('id', $bodrumIlceId)
                    ->update(['il_id' => 48, 'updated_at' => now()]);
                $this->logBodrumFk($bodrumIlceId, $previousIlId, 48);
                $counts['bodrum_fk_update'] = true;
            }
            // Bodrum'un il_id'si zaten 48 ise: guncelleme YOK, log YOK.
            // down() Bodrum'a dokunmaz -> Bodrum il_id=48 korunur.

            // Bodrum mahalleleri -- sadece eksik ID'ler
            foreach (self::CANONICAL_MAHALLER as $mh) {
                if (in_array($mh['id'], $mevcutMahalleIdleri)) {
                    continue;
                }
                $now = now();
                DB::table('mahalleler')->insert([
                    'id' => $mh['id'],
                    'ilce_id' => $bodrumIlceId,
                    'mahalle_adi' => $mh['mahalle_adi'],
                    'posta_kodu' => $mh['posta_kodu'],
                    'lat' => $mh['lat'],
                    'lng' => $mh['lng'],
                    'aktiflik_durumu' => true,
                    'display_order' => $mh['id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->logInsert('mahalleler', $mh['id']);
                $counts['mahalleler']++;
            }
        });

        $this->report($counts, $orphanReport);

        // SNAPSHOT LOG: ilceler.il_id for down() FK restore
        // down() rollback'de orphan FK sorununu onlemek icin, up() transaction'ndan
        // ONCE mevcut ilceler.il_id degerleri loglanir. down() reversal sonrasinda
        // tum ilceler.il_id degerleri bu log'dan geri yuklenir.
        DB::table('location_reconciliation_log')->insert([
            'migration_signature' => self::MIGRATION_SIGNATURE,
            'table_name' => 'ilce_fk_snapshot',
            'text_value' => json_encode($ilceSnapshot),
            'action' => 'ilce_fk_snapshot',
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        // 1. Eklenen kayitlari log'dan okuyup sil
        // YALNIZCA bu migration'in ekledigi kayitlari siler.
        // Mevcut kayitlar ve iliskiler korunur.
        $logEntries = DB::table('location_reconciliation_log')
            ->where('migration_signature', self::MIGRATION_SIGNATURE)
            ->where('action', 'insert')
            ->orderBy('table_name')
            ->get(['table_name', 'record_id']);

        $deleted = 0;
        foreach ($logEntries as $entry) {
            $affected = DB::table($entry->table_name)
                ->where('id', $entry->record_id)
                ->delete();
            if ($affected > 0) {
                $deleted++;
            }
        }

        // 2. Bodrum FK: OKU ama Step 5 SONRASI uygula
        // Bodrum_FK_log'tan eski degeri al. Step 5 (ilce ID reversals) sonrasi
        // uygulanacak. Bu, Bodrum ID reversals cascade'in Bodrum FK'yi bozmasini onler.
        $bodrumLog = DB::table('bodrum_fk_reconcile_log')
            ->orderBy('id', 'desc')
            ->first();

        $bodrumFkPrev = null;
        $bodrumLogId = null;
        if ($bodrumLog) {
            $bodrumFkPrev = (int) $bodrumLog->previous_il_id;
            $bodrumLogId = $bodrumLog->id;
        }

        // 3. iller ID reversals
        // iller tablosunu orijinal durumuna dondurur.
        $illerMoves = DB::table('location_reconciliation_log')
            ->where('migration_signature', self::MIGRATION_SIGNATURE)
            ->where('action', 'id_move')
            ->where('table_name', 'iller')
            ->orderByDesc('id')
            ->get(['old_id', 'new_id']);

        foreach ($illerMoves as $move) {
            $oldId = (int) $move->old_id;
            $newId = (int) $move->new_id;
            if ($oldId === $newId) continue;
            if (DB::table('iller')->where('id', $oldId)->exists()) {
                DB::table('iller')->where('id', $oldId)->update(['id' => 99000 + $oldId]);
            }
            DB::table('iller')->where('id', $newId)->update(['id' => $oldId]);
        }

        // 4. ilceler ID reversals
        // ilceler tablosunu orijinal ID'lerine dondurur.
        // Bu cascade mahalle FK'lerini dogru gunceller.
        $ilceMoves = DB::table('location_reconciliation_log')
            ->where('migration_signature', self::MIGRATION_SIGNATURE)
            ->where('action', 'id_move')
            ->where('table_name', 'ilceler')
            ->orderByDesc('id')
            ->get(['old_id', 'new_id']);

        foreach ($ilceMoves as $move) {
            $oldId = (int) $move->old_id;
            $newId = (int) $move->new_id;
            if ($oldId === $newId) continue;
            DB::table('mahalleler')->where('ilce_id', $newId)->update(['ilce_id' => $oldId]);
            if (DB::table('ilceler')->where('id', $oldId)->exists()) {
                DB::table('ilceler')->where('id', $oldId)->update(['id' => 99000 + $oldId]);
            }
            DB::table('ilceler')->where('id', $newId)->update(['id' => $oldId]);
        }

        // 5. Bodrum FK restore (ilce ID reversals SONRASI)
        // Bodrum_FK_log uygula. Bodrum isim eslesmesiyle bulunur.
        if ($bodrumFkPrev !== null) {
            DB::table('ilceler')
                ->where('ilce_adi', 'Bodrum')
                ->update(['il_id' => $bodrumFkPrev, 'updated_at' => now()]);
            DB::table('bodrum_fk_reconcile_log')
                ->where('id', $bodrumLogId)
                ->delete();
        }

        // 6. TUM ilce FK'leri snapshot'tan geri yukle
        // Snapshot, up() oncesi yakalanan ilce FK orijinal degerlerini icerir.
        // ID reversals sonrasi ilce ID'leri degismis olabilir.
        // Bu yuzden snapshot'taki ID->FK eslemesini mevcut tablo ID'leriyle eslestir.
        $snapshotRow = DB::table('location_reconciliation_log')
            ->where('migration_signature', self::MIGRATION_SIGNATURE)
            ->where('action', 'ilce_fk_snapshot')
            ->orderByDesc('id')
            ->first();

        if ($snapshotRow && $snapshotRow->text_value) {
            $ilceSnapshot = json_decode($snapshotRow->text_value, true);
            if (is_array($ilceSnapshot)) {
                // Mevcut tum ilceleri oku (down() sonrasi ID'lerle)
                $mevcutIlceler = DB::table('ilceler')->get(['id', 'ilce_adi', 'il_id']);
                $ilcelerById = [];
                foreach ($mevcutIlceler as $ic) {
                    $ilcelerById[$ic->id] = $ic;
                }

                // Snapshot'taki her ID icin: snapshot FK degerini mevcut tabloya uygula
                foreach ($ilceSnapshot as $snapshotId => $snapshotFk) {
                    if (isset($ilcelerById[$snapshotId])) {
                        $ilce = $ilcelerById[$snapshotId];
                        if ((int) $ilce->il_id !== (int) $snapshotFk) {
                            DB::table('ilceler')
                                ->where('id', $snapshotId)
                                ->update(['il_id' => (int) $snapshotFk, 'updated_at' => now()]);
                        }
                    }
                }
            }
        }

        // Log kayitlarini temizle
        DB::table('location_reconciliation_log')
            ->where('migration_signature', self::MIGRATION_SIGNATURE)
            ->delete();

        // Bos log tablolarini temizle
        if ((int) DB::table('location_reconciliation_log')->count() === 0) {
            Schema::dropIfExists('location_reconciliation_log');
        }
        if (DB::getSchemaBuilder()->hasTable('bodrum_fk_reconcile_log')
            && (int) DB::table('bodrum_fk_reconcile_log')->count() === 0) {
            Schema::dropIfExists('bodrum_fk_reconcile_log');
        }

        info("Location reconciliation rollback: {$deleted} records removed.");

        // FK ASSERTIONS: Bodrum, Besiktas, Kadikoy rollback sonrasi FK dogrulama
        // Beklenen degerler:
        // - Bodrum (ilce_id=1): orijinal il_id degeri (snapshot veya Bodrum_FK_log'dan)
        // - Besiktas (ilce_id=4): orijinal il_id=2 (Istanbul)
        // - Kadikoy (ilce_id=5): orijinal il_id=2 (Istanbul)
        $assertions = [];

        $bodrum = DB::table('ilceler')->find(1);
        if ($bodrum) {
            $assertions['bodrum'] = [
                'ilce_adi' => $bodrum->ilce_adi,
                'il_id' => (int) $bodrum->il_id,
                'il_adi' => DB::table('iller')->find($bodrum->il_id)?->il_adi ?? 'BULUNAMADI',
            ];
        }

        $besiktas = DB::table('ilceler')->where('ilce_adi', 'Besiktas')->first();
        if ($besiktas) {
            $assertions['besiktas'] = [
                'ilce_id' => (int) $besiktas->id,
                'il_id' => (int) $besiktas->il_id,
                'il_adi' => DB::table('iller')->find($besiktas->il_id)?->il_adi ?? 'BULUNAMADI',
            ];
        }

        $kadikoy = DB::table('ilceler')->where('ilce_adi', 'Kadikoy')->first();
        if ($kadikoy) {
            $assertions['kadikoy'] = [
                'ilce_id' => (int) $kadikoy->id,
                'il_id' => (int) $kadikoy->il_id,
                'il_adi' => DB::table('iller')->find($kadikoy->il_id)?->il_adi ?? 'BULUNAMADI',
            ];
        }

        file_put_contents('/tmp/mig_down_assertions.txt', json_encode($assertions, JSON_PRETTY_PRINT));
        info("FK Assertions: " . json_encode($assertions));
    }

    // HELPERS

    private function createLogTable(): void
    {
        if (!Schema::hasTable('location_reconciliation_log')) {
            Schema::create('location_reconciliation_log', function (Blueprint $table) {
                $table->id();
                $table->string('migration_signature', 60);
                $table->string('table_name', 60);
                $table->unsignedBigInteger('record_id')->nullable();
                $table->text('text_value')->nullable(); // snapshot JSON payload
                $table->unsignedBigInteger('old_id')->nullable();
                $table->unsignedBigInteger('new_id')->nullable();
                $table->string('action', 20)->default('insert'); // insert | id_move | ilce_fk_snapshot
                $table->timestamp('created_at')->useCurrent();
                $table->index('migration_signature');
            });
        } else {
            // Production'da eski semali log tablosu mevcut
            // (yalnizca id, migration_signature, table_name, record_id, created_at).
            // Eksik kolonlari ekle ve record_id'yi nullable yap.
            $schema = Schema::getColumnListing('location_reconciliation_log');
            if (!in_array('old_id', $schema, true)) {
                Schema::table('location_reconciliation_log', function (Blueprint $table) {
                    $table->unsignedBigInteger('old_id')->nullable()->after('record_id');
                });
            }
            if (!in_array('new_id', $schema, true)) {
                Schema::table('location_reconciliation_log', function (Blueprint $table) {
                    $table->unsignedBigInteger('new_id')->nullable()->after('old_id');
                });
            }
            if (!in_array('action', $schema, true)) {
                Schema::table('location_reconciliation_log', function (Blueprint $table) {
                    $table->string('action', 20)->default('insert')->after('new_id');
                });
            }
            if (!in_array('text_value', $schema, true)) {
                // Snapshot JSON payload icin gerekli
                Schema::table('location_reconciliation_log', function (Blueprint $table) {
                    $table->text('text_value')->nullable()->after('record_id');
                });
            }
            // record_id eski tabloda NOT NULL olabilir; logIdMove() insert'i
            // record_id gondermez -> nullable yap.
            Schema::table('location_reconciliation_log', function (Blueprint $table) {
                $table->unsignedBigInteger('record_id')->nullable()->change();
            });
        }

        if (!Schema::hasTable('bodrum_fk_reconcile_log')) {
            Schema::create('bodrum_fk_reconcile_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ilce_id');
                $table->unsignedBigInteger('previous_il_id');
                $table->unsignedBigInteger('new_il_id');
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    private function logInsert(string $tableName, int $recordId): void
    {
        DB::table('location_reconciliation_log')->insert([
            'migration_signature' => self::MIGRATION_SIGNATURE,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => 'insert',
            'created_at' => now(),
        ]);
    }

    private function logIdMove(string $tableName, int $oldId, int $newId): void
    {
        DB::table('location_reconciliation_log')->insert([
            'migration_signature' => self::MIGRATION_SIGNATURE,
            'table_name' => $tableName,
            'old_id' => $oldId,
            'new_id' => $newId,
            'action' => 'id_move',
            'created_at' => now(),
        ]);
    }

    private function logBodrumFk(int $ilceId, int $prevIlId, int $newIlId): void
    {
        DB::table('bodrum_fk_reconcile_log')->insert([
            'ilce_id' => $ilceId,
            'previous_il_id' => $prevIlId,
            'new_il_id' => $newIlId,
            'created_at' => now(),
        ]);
    }

    /**
     * down() rollback'de bir ilce icin hedef il adini dondurur.
     * Ilce isimlerini iller tablosundaki canonical karsiliklariyla eslestirir.
     * Bodrum/Marmaris/Milas -> Mugla, Besiktas/Kadikoy -> Istanbul.
     */
    private function targetIlForIlce(string $ilceAdi): ?string
    {
        $map = [
            // Mugla ilceleri
            'bodrum'       => 'Mugla',
            'fethiye'      => 'Mugla',
            'marmaris'     => 'Mugla',
            'milas'        => 'Mugla',
            'dalaman'      => 'Mugla',
            'datca'        => 'Mugla',
            'kavaklidere'  => 'Mugla',
            'koycegiz'     => 'Mugla',
            'mentese'      => 'Mugla',
            'ortaca'       => 'Mugla',
            'seydikemer'   => 'Mugla',
            'ula'          => 'Mugla',
            'yatagan'      => 'Mugla',
            // Istanbul ilceleri
            'besiktas'     => 'Istanbul',
            'kadikoy'      => 'Istanbul',
            'sisli'        => 'Istanbul',
            'fatih'        => 'Istanbul',
            'beyoglu'      => 'Istanbul',
            'bakirkoy'     => 'Istanbul',
            'uskudar'      => 'Istanbul',
            'maltepe'      => 'Istanbul',
            'pendik'       => 'Istanbul',
            'tuzla'        => 'Istanbul',
            // Ankara ilceleri
            'cankaya'      => 'Ankara',
            'kecioren'     => 'Ankara',
            'sincan'       => 'Ankara',
            'etimesgut'     => 'Ankara',
        ];
        $normalized = mb_strtolower(trim($ilceAdi));
        return $map[$normalized] ?? null;
    }

    private function report(array $counts, array $orphans): void
    {
        $total = $counts['iller'] + $counts['ilceler'] + $counts['mahalleler'];

        if ($total === 0 && empty($orphans) && !$counts['bodrum_fk_update']) {
            info('Location reconciliation: idempotent -- nothing to add.');
            return;
        }

        $parts = [];
        if ($counts['iller'] > 0)       $parts[] = "{$counts['iller']} iller";
        if ($counts['ilceler'] > 0)    $parts[] = "{$counts['ilceler']} ilceler";
        if ($counts['mahalleler'] > 0)  $parts[] = "{$counts['mahalleler']} mahalleler";
        if ($counts['bodrum_fk_update']) $parts[] = "Bodrum FK guncellendi";

        $msg = 'Location reconciliation: ' . ($parts ? implode(', ', $parts) : 'no changes');

        if (!empty($orphans)) {
            $msg .= ' | Orphan ilceler: ' . count($orphans);
            foreach ($orphans as $o) {
                $msg .= sprintf(
                    "\n   - [ilceler] id=%d, il_id=%d (%s)",
                    $o['id'], $o['il_id'], $o['name']
                );
            }
            $msg .= "\n   (Preserved -- manual review required)";
        }

        info($msg);
    }
};
