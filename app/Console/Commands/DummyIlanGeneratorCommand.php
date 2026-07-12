<?php

namespace App\Console\Commands;

use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sprint 7.1A — Dummy İlan Generator
 *
 * 50-100 kontrollü ilan üretir.
 * Tenant-safe, idempotent — aynı komut tekrar çalıştırılabilir.
 *
 * Usage:
 *   php artisan ilan:dummy                  # 75 ilan (default)
 *   php artisan ilan:dummy --count=100      # 100 ilan
 *   php artisan ilan:dummy --reset           # Önce temizle, sonra üret
 */
class DummyIlanGeneratorCommand extends Command
{
    protected $signature = 'ilan:dummy
                            {--count=75 : Kaç ilan üretilecek}
                            {--reset : Önce mevcut dummy ilanları sil}';

    protected $description = 'Dummy ilan dataset üretir (Sprint 7.1A)';

    /** @var array<string, array{kategori_id: int, yayin_tipi: string, yayin_tipi_id: int|null, label: string, dagilim: int}> */
    private array $profiles = [
        // Konut — Daire
        ['kategori_id' => 7,  'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => 16, 'label' => 'Konut > Daire > Satılık',       'dagilim' => 12],
        ['kategori_id' => 7,  'yayin_tipi' => 'Kiralık', 'yayin_tipi_id' => null, 'label' => 'Konut > Daire > Kiralık',       'dagilim' => 6],
        ['kategori_id' => 10, 'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => null, 'label' => 'Konut > Dubleks > Satılık',      'dagilim' => 4],
        ['kategori_id' => 9,  'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => null, 'label' => 'Konut > Müstakil Ev > Satılık',  'dagilim' => 5],

        // Konut — Villa
        ['kategori_id' => 8,  'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => 15, 'label' => 'Konut > Villa > Satılık',         'dagilim' => 10],
        ['kategori_id' => 26, 'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => 15, 'label' => 'Konut > Villa > Satılık (Alt)',    'dagilim' => 5],
        ['kategori_id' => 8,  'yayin_tipi' => 'Kiralık', 'yayin_tipi_id' => null, 'label' => 'Konut > Villa > Kiralık',        'dagilim' => 8],

        // Arsa & Arazi
        ['kategori_id' => 15, 'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => 13, 'label' => 'Arsa > Arsa (Konut/Villa)',        'dagilim' => 8],
        ['kategori_id' => 17, 'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => null, 'label' => 'Arsa > Tarla',                   'dagilim' => 4],
        ['kategori_id' => 18, 'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => null, 'label' => 'Arsa > Zeytinlik',              'dagilim' => 3],
        ['kategori_id' => 19, 'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => null, 'label' => 'Arsa > Bağ & Bahçe',           'dagilim' => 2],

        // İşyeri
        ['kategori_id' => 11, 'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => null, 'label' => 'İşyeri > Ofis > Satılık',        'dagilim' => 4],
        ['kategori_id' => 12, 'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => null, 'label' => 'İşyeri > Dükkan > Satılık',     'dagilim' => 4],
        ['kategori_id' => 13, 'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => null, 'label' => 'İşyeri > Fabrika',              'dagilim' => 2],
        ['kategori_id' => 11, 'yayin_tipi' => 'Kiralık', 'yayin_tipi_id' => null, 'label' => 'İşyeri > Ofis > Kiralık',       'dagilim' => 3],

        // Yazlık Kiralama
        ['kategori_id' => 26, 'yayin_tipi' => 'Günlük Kiralama', 'yayin_tipi_id' => null, 'label' => 'Yazlık > Villa',          'dagilim' => 5],
        ['kategori_id' => 28, 'yayin_tipi' => 'Günlük Kiralama', 'yayin_tipi_id' => null, 'label' => 'Yazlık > Daire',          'dagilim' => 3],
        ['kategori_id' => 27, 'yayin_tipi' => 'Günlük Kiralama', 'yayin_tipi_id' => null, 'label' => 'Yazlık > Rezidans',       'dagilim' => 2],

        // Turistik
        ['kategori_id' => 32, 'yayin_tipi' => 'Satılık', 'yayin_tipi_id' => null, 'label' => 'Turistik > Otel',               'dagilim' => 1],
        ['kategori_id' => 33, 'yayin_tipi' => 'Kiralık', 'yayin_tipi_id' => null, 'label' => 'Turistik > Pansiyon',           'dagilim' => 1],
    ];

    /** @var array<string, string> */
    private array $bodrumMahalleler = [
        1  => 'Yalıkavak',
        2  => 'Gümüşlük',
        3  => 'Turgutreis',
        4  => 'Bitez',
        5  => 'Azmakara',
        6  => 'Konacık',
        7  => 'Meriç',
        8  => 'Çiftlik',
        9  => 'Pınarlıbelen',
        10 => 'Kömürcüler',
    ];

    /** @var array<string, array<string, array{birim: int, min: int, max: int}>> */
    private array $fiyatAraliklari = [
        'Satılık' => [
            'Daire'       => ['birim' => 1_000_000,  'min' => 2,   'max' => 20],
            'Villa'       => ['birim' => 5_000_000,  'min' => 5,   'max' => 40],
            'Müstakil Ev' => ['birim' => 1_000_000,  'min' => 2,   'max' => 15],
            'Dubleks'     => ['birim' => 1_000_000,  'min' => 3,   'max' => 18],
            'Ofis'        => ['birim' => 1_000_000,  'min' => 2,   'max' => 25],
            'Dükkan'      => ['birim' => 1_000_000,  'min' => 1,   'max' => 15],
            'Arsa'        => ['birim' => 500_000,    'min' => 1,   'max' => 15],
            'Tarla'       => ['birim' => 200_000,    'min' => 0.5, 'max' => 8],
            'Zeytinlik'   => ['birim' => 300_000,    'min' => 0.5, 'max' => 10],
            'Bağ & Bahçe' => ['birim' => 200_000,    'min' => 0.3, 'max' => 6],
            'Fabrika'     => ['birim' => 5_000_000, 'min' => 5,   'max' => 80],
            'Otel'        => ['birim' => 5_000_000, 'min' => 10,  'max' => 100],
        ],
        'Kiralık' => [
            'Daire'       => ['birim' => 20_000, 'min' => 10, 'max' => 60],
            'Villa'       => ['birim' => 50_000, 'min' => 20, 'max' => 200],
            'Ofis'        => ['birim' => 25_000, 'min' => 15, 'max' => 100],
            'Dükkan'      => ['birim' => 30_000, 'min' => 15, 'max' => 150],
            'Pansiyon'    => ['birim' => 30_000, 'min' => 15, 'max' => 80],
        ],
        'Günlük Kiralama' => [
            'Villa'       => ['birim' => 5_000,  'min' => 2,  'max' => 20],
            'Daire'       => ['birim' => 2_000,  'min' => 1,  'max' => 8],
            'Rezidans'    => ['birim' => 3_000,  'min' => 1,  'max' => 10],
        ],
    ];

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $reset = (bool) $this->option('reset');

        $this->info("=== Sprint 7.1A: Dummy İlan Generator ===");
        $this->info("Hedef: {$count} ilan | Reset: " . ($reset ? 'EVET' : 'HAYIR'));

        if ($reset) {
            $this->resetDummyIlans();
        }

        $users = User::all();
        if ($users->isEmpty()) {
            $this->error('User yok. Önce seeder çalıştır: php artisan db:seed --class=UserSeeder');
            return self::FAILURE;
        }

        $userIds = $users->pluck('id')->toArray();

        // İlan dağılımını hesapla
        $dagilimlar = collect($this->profiles)->pluck('dagilim', 'label')->toArray();
        $toplamDagilim = array_sum($dagilimlar);

        $ilanlar = [];
        $ilceId = 1;
        $mahalleIdOffset = 1;

        for ($i = 0; $i < $count; $i++) {
            // Profil seç (ağırlıklı rastgele)
            $r = random_int(1, $toplamDagilim);
            $kumulatif = 0;
            $profile = null;
            foreach ($this->profiles as $p) {
                $kumulatif += $p['dagilim'];
                if ($r <= $kumulatif) {
                    $profile = $p;
                    break;
                }
            }
            $profile = $profile ?? $this->profiles[0];

            // Kategori bilgisi
            $kategori = IlanKategori::find($profile['kategori_id']);
            $anaKategoriId = $kategori?->parent_id ?? $profile['kategori_id'];
            $kategoriAdi = $kategori?->name ?? 'Bilinmeyen';

            // Fiyat hesapla
            $fiyatKey = collect(['Daire', 'Villa', 'Müstakil Ev', 'Dubleks', 'Ofis', 'Dükkan', 'Arsa', 'Tarla', 'Zeytinlik', 'Bağ & Bahçe', 'Fabrika', 'Otel', 'Pansiyon', 'Rezidans'])
                ->first(fn($k) => str_contains($kategoriAdi, $k), 'Daire');

            $aralik = $this->fiyatAraliklari[$profile['yayin_tipi']][$fiyatKey]
                ?? ['birim' => 1_000_000, 'min' => 1, 'max' => 10];

            $carpan = random_int($aralik['min'], $aralik['max']);
            $fiyat = $aralik['birim'] * $carpan;

            // Mahalle seç
            $mahalleKeys = array_keys($this->bodrumMahalleler);
            $mahalleId = $mahalleKeys[array_rand($mahalleKeys)];

            // Brut/net m2
            $brutM2 = random_int(50, 600);
            $netM2  = (int) ($brutM2 * (mt_rand(82, 92) / 100));

            // Bina yaşı
            $binaYasi = random_int(0, 35);

            // Yayın durumu
            $yayinDurumlari = ['yayinda', 'yayinda', 'yayinda', 'taslak', 'yayinda'];
            $yayinDurumu = $yayinDurumlari[array_rand($yayinDurumlari)];

            // Oda sayısı (sadece konut için)
            $odaSayisi = in_array($anaKategoriId, [1, 4]) ? random_int(1, 6) : null;
            $salonSayisi = in_array($anaKategoriId, [1, 4]) ? random_int(0, 2) : null;
            $banyoSayisi = in_array($anaKategoriId, [1, 4]) ? random_int(1, 4) : null;

            // Başlık oluştur
            $baslik = $this->generateBaslik($profile['yayin_tipi'], $kategoriAdi, $brutM2, $odaSayisi, $binaYasi);
            $slug = Str::slug($baslik . '-' . uniqid());

            $ilan = [
                'baslik'             => $baslik,
                'slug'               => $slug,
                'fiyat'              => $fiyat,
                'para_birimi'        => 'TL',
                'referans_no'        => 'REF-DUMMY-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'yayin_durumu'       => $yayinDurumu,
                'user_id'            => $userIds[array_rand($userIds)],
                'ana_kategori_id'    => $anaKategoriId,
                'alt_kategori_id'    => $profile['kategori_id'],
                'yayin_tipi_id'     => $profile['yayin_tipi_id'],
                'yayin_tipi'        => $profile['yayin_tipi'],
                'kategori'           => IlanKategori::find($anaKategoriId)?->name,
                'alt_kategori'       => $kategoriAdi,
                'il_id'              => 1,
                'ilce_id'            => $ilceId,
                'mahalle_id'         => $mahalleId,
                'brut_m2'            => $brutM2,
                'net_m2'             => $netM2,
                'bina_yasi'          => $binaYasi,
                'oda_sayisi'         => $odaSayisi,
                'salon_sayisi'       => $salonSayisi,
                'banyo_sayisi'       => $banyoSayisi,
                'kat'                => $kategoriAdi === 'Daire' ? random_int(1, 8) : null,
                'toplam_kat'         => $kategoriAdi === 'Daire' ? random_int(4, 12) : null,
                'aktiflik_durumu'    => 1,
                'yayin_tipi'         => $profile['yayin_tipi'],
                'lat'                => 37.03 + (mt_rand(-50, 50) / 1000),
                'lng'                => 27.43 + (mt_rand(-50, 50) / 1000),
                'aciklama'           => $this->generateAciklama($profile['yayin_tipi'], $kategoriAdi, $brutM2, $binaYasi, $odaSayisi),
                'created_at'         => now()->subDays(random_int(0, 60)),
                'updated_at'         => now()->subDays(random_int(0, 5)),
            ];

            $ilanlar[] = $ilan;
        }

        // Batch insert
        $chunks = array_chunk($ilanlar, 50);
        $inserted = 0;
        foreach ($chunks as $chunk) {
            foreach ($chunk as $ilanData) {
                try {
                    Ilan::withoutGlobalScopes()->create($ilanData);
                    $inserted++;
                } catch (\Throwable $e) {
                    $this->warn("  Atlanan: {$ilanData['baslik']} — {$e->getMessage()}");
                }
            }
        }

        $this->info("{$inserted}/{$count} ilan üretildi.");
        $this->printStats($count);

        return self::SUCCESS;
    }

    private function resetDummyIlans(): void
    {
        $deleted = Ilan::withoutGlobalScopes()
            ->where('referans_no', 'like', 'REF-DUMMY-%')
            ->forceDelete();
        $this->warn("Silinen dummy ilan: {$deleted}");
    }

    private function printStats(int $count): void
    {
        $this->info('');
        $this->info('=== Üretim Sonrası Metrikler ===');

        $total = Ilan::withoutGlobalScopes()->count();
        $yayinda = Ilan::withoutGlobalScopes()->where('yayin_durumu', 'yayinda')->count();
        $taslak = Ilan::withoutGlobalScopes()->where('yayin_durumu', 'taslak')->count();
        $satilik = Ilan::withoutGlobalScopes()->where('yayin_tipi', 'Satılık')->count();
        $kiralik = Ilan::withoutGlobalScopes()->where('yayin_tipi', 'Kiralık')->count();
        $gunluk = Ilan::withoutGlobalScopes()->where('yayin_tipi', 'Günlük Kiralama')->count();

        $this->table(
            ['Metrik', 'Değer'],
            [
                ['Toplam İlan', $total],
                ['Yayında', $yayinda],
                ['Taslak', $taslak],
                ['Satılık', $satilik],
                ['Kiralık', $kiralik],
                ['Günlük Kiralama', $gunluk],
                ['Boş pivot (ilan_ozellikleri)', \Illuminate\Support\Facades\DB::table('ilan_ozellikleri')->count()],
            ]
        );

        // Property Hub metrikleri
        $this->info('');
        $this->info('=== Property Hub Metrikleri ===');

        $orphanCount = Ilan::withoutGlobalScopes()
            ->leftJoin('ilan_ozellikleri', 'ilan_ozellikleri.ilan_id', '=', 'ilanlar.id')
            ->whereNull('ilan_ozellikleri.ilan_id')
            ->count();

        $this->table(
            ['Metrik', 'Değer'],
            [
                ['Orphan Feature (atanmamış)', $orphanCount],
                ['Ortalama Fiyat', number_format(Ilan::withoutGlobalScopes()->avg('fiyat') ?? 0) . ' TL'],
                ['Fiyat Aralığı', number_format(Ilan::withoutGlobalScopes()->min('fiyat') ?? 0) . ' - ' . number_format(Ilan::withoutGlobalScopes()->max('fiyat') ?? 0) . ' TL'],
            ]
        );
    }

    private function generateBaslik(string $yayinTipi, string $kategoriAdi, int $m2, ?int $oda, int $yasi): string
    {
        $bodrumMahalleleri = ['Yalıkavak', 'Gümüşlük', 'Turgutreis', 'Bitez', 'Konacık'];
        $mahalle = $bodrumMahalleleri[array_rand($bodrumMahalleleri)];

        $parts = [];

        if ($yayinTipi === 'Satılık') {
            $parts[] = 'Satılık';
        } elseif ($yayinTipi === 'Kiralık') {
            $parts[] = 'Kiralık';
        } else {
            $parts[] = $yayinTipi;
        }

        $parts[] = $kategoriAdi;

        if ($oda) {
            $parts[] = $oda . '+1';
        }

        $parts[] = $m2 . ' m²';

        if ($yasi > 0) {
            $parts[] = $yasi . ' yaşında';
        } else {
            $parts[] = 'Sıfır bina';
        }

        $parts[] = $mahalle . ', Bodrum';

        return implode(' ', $parts);
    }

    private function generateAciklama(string $yayinTipi, string $kategoriAdi, int $m2, int $yasi, ?int $oda): string
    {
        $bodrum = 'Bodrum\'un en prestijli bölgesinde, denize ve merkeze yakın konumda yer alan bu ';
        $ozellikler = ['modern tasarım', 'yüksek tavanlar', 'geniş pencereler', 'doğal aydınlatma', 'akıllı ev sistemi'];
        $oz = $ozellikler[array_rand($ozellikler)];

        return $bodrum . strtolower($kategoriAdi) . ', ' . $oz . ' ile donatılmıştır. ' .
            $m2 . ' m² alana sahip bu gayrimenkul, ' .
            ($yasi > 0 ? $yasi . ' yıllık tecrübesiyle ' : 'sıfır bina olarak ') .
            ' size konforlu bir yaşam sunmaktadır.' .
            ($oda ? ' ' . $oda . ' oda seçeneği ile aileler için idealdir.' : '');
    }
}
