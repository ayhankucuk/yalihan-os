<?php

namespace App\Services\Listing;

use App\Models\Ilan;
use App\Models\IlanKategori;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * İlan Numarası Otomatik Üretim Servisi
 * 
 * Her ilan için benzersiz, anlamlı numara üretir.
 * Format: {TIP}-{KATEGORİ}-{YIL}-{SIRA}
 * Örnek: STL-DRE-2024-001
 * 
 * Bu numara ilanın TC Kimlik Numarası gibi benzersiz kimliğidir.
 * Arşivleme, arama, raporlama için kullanılır.
 * 
 * @package App\Services\Listing
 */
class IlanNoGenerator
{
    /**
     * İlan tipi kodları
     */
    private const TIP_KODLARI = [
        'satis' => 'STL',      // Satılık
        'kiralama' => 'KRL',   // Kiralık
        'yazlik' => 'YZL',     // Yazlık Kiralama
        'gunluk' => 'GNL',     // Günlük Kiralama
        'devren' => 'DVR',     // Devren
        'takas' => 'TKS',      // Takas
    ];

    /**
     * Kategori kodları
     */
    private const KATEGORI_KODLARI = [
        'daire' => 'DRE',
        'villa' => 'VLA',
        'arsa' => 'ARS',
        'isyeri' => 'ISY',
        'konut' => 'KNT',
        'ticari' => 'TCR',
        'residence' => 'RSD',
        'yazlik' => 'YZL',
        'turistik' => 'TRS',
        'ofis' => 'OFS',
        'magaza' => 'MGZ',
        'depo' => 'DPO',
        'fabrika' => 'FBR',
        'bina' => 'BNA',
        'tarla' => 'TRL',
        'bahce' => 'BHC',
    ];

    /**
     * İlan için benzersiz numara üret
     * 
     * @param Ilan $ilan
     * @return string
     * @throws \Exception
     */
    public function generate(Ilan $ilan): string
    {
        // Zaten numara varsa değiştirme
        if (!empty($ilan->ilan_no)) {
            return $ilan->ilan_no;
        }

        try {
            return DB::transaction(function () use ($ilan) {
                $tipKodu = $this->getTipKodu($ilan);
                $kategoriKodu = $this->getKategoriKodu($ilan);
                $yil = date('Y');

                // Sequence tablosundan sıra numarasını al (lock ile)
                $sequence = DB::table('ilan_no_sequences')
                    ->where('tip_kodu', $tipKodu)
                    ->where('kategori_kodu', $kategoriKodu)
                    ->where('yil', $yil)
                    ->lockForUpdate()
                    ->orderBy('id')->first();

                if (!$sequence) {
                    // İlk kayıt - yeni sequence oluştur
                    DB::table('ilan_no_sequences')->insert([
                        'tip_kodu' => $tipKodu,
                        'kategori_kodu' => $kategoriKodu,
                        'yil' => $yil,
                        'son_sira' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $sira = 1;
                } else {
                    // Mevcut sequence - sırayı artır
                    $sira = $sequence->son_sira + 1;
                    DB::table('ilan_no_sequences')
                        ->where('id', $sequence->id)
                        ->update([
                            'son_sira' => $sira,
                            'updated_at' => now(),
                        ]);
                }

                $ilanNo = sprintf('%s-%s-%s-%03d', $tipKodu, $kategoriKodu, $yil, $sira);

                Log::info('İlan numarası üretildi', [
                    'ilan_id' => $ilan->id,
                    'ilan_no' => $ilanNo,
                    'tip' => $tipKodu,
                    'kategori' => $kategoriKodu,
                    'yil' => $yil,
                    'sira' => $sira,
                ]);

                return $ilanNo;
            });
        } catch (\Exception $e) {
            Log::error('İlan numarası üretilirken hata', [
                'ilan_id' => $ilan->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * İlan tipinden kod üret
     * 
     * @param Ilan $ilan
     * @return string
     */
    private function getTipKodu(Ilan $ilan): string
    {
        // yayin_tipi_id'den tip belirle
        if ($ilan->yayinTipi) {
            $tipAdi = strtolower($ilan->yayinTipi->name ?? '');
            
            foreach (self::TIP_KODLARI as $key => $kod) {
                if (str_contains($tipAdi, $key)) {
                    return $kod;
                }
            }
        }

        // Fallback: islem_tipi alanından
        if ($ilan->islem_tipi) {
            $islemTipi = strtolower($ilan->islem_tipi);
            return self::TIP_KODLARI[$islemTipi] ?? 'STL';
        }

        // Default: Satılık
        return 'STL';
    }

    /**
     * Kategori'den kod üret
     * 
     * @param Ilan $ilan
     * @return string
     */
    private function getKategoriKodu(Ilan $ilan): string
    {
        // Ana kategoriden kod belirle
        if ($ilan->anaKategori) {
            $kategoriAdi = strtolower($ilan->anaKategori->name ?? '');
            $kategoriSlug = strtolower($ilan->anaKategori->slug ?? '');
            
            // Slug'dan kontrol et
            foreach (self::KATEGORI_KODLARI as $key => $kod) {
                if (str_contains($kategoriSlug, $key) || str_contains($kategoriAdi, $key)) {
                    return $kod;
                }
            }
        }

        // Fallback: Alt kategoriden
        if ($ilan->altKategori) {
            $altKategoriAdi = strtolower($ilan->altKategori->name ?? '');
            $altKategoriSlug = strtolower($ilan->altKategori->slug ?? '');
            
            foreach (self::KATEGORI_KODLARI as $key => $kod) {
                if (str_contains($altKategoriSlug, $key) || str_contains($altKategoriAdi, $key)) {
                    return $kod;
                }
            }
        }

        // Default: Konut
        return 'KNT';
    }

    /**
     * İlan numarasını parse et
     * 
     * @param string $ilanNo
     * @return array{tip: string, kategori: string, yil: int, sira: int}|null
     */
    public function parse(string $ilanNo): ?array
    {
        $parts = explode('-', $ilanNo);
        
        if (count($parts) !== 4) {
            return null;
        }

        return [
            'tip' => $parts[0],
            'kategori' => $parts[1],
            'yil' => (int) $parts[2],
            'sira' => (int) $parts[3],
        ];
    }

    /**
     * İlan numarasının geçerli olup olmadığını kontrol et
     * 
     * @param string $ilanNo
     * @return bool
     */
    public function validate(string $ilanNo): bool
    {
        $parsed = $this->parse($ilanNo);
        
        if (!$parsed) {
            return false;
        }

        // Tip kodu geçerli mi?
        if (!in_array($parsed['tip'], self::TIP_KODLARI)) {
            return false;
        }

        // Kategori kodu geçerli mi?
        if (!in_array($parsed['kategori'], self::KATEGORI_KODLARI)) {
            return false;
        }

        // Yıl mantıklı mı?
        if ($parsed['yil'] < 2020 || $parsed['yil'] > 2100) {
            return false;
        }

        // Sıra pozitif mi?
        if ($parsed['sira'] < 1) {
            return false;
        }

        return true;
    }

    /**
     * Tip kodundan açıklama al
     * 
     * @param string $tipKodu
     * @return string
     */
    public function getTipAciklama(string $tipKodu): string
    {
        $map = [
            'STL' => 'Satılık',
            'KRL' => 'Kiralık',
            'YZL' => 'Yazlık',
            'GNL' => 'Günlük',
            'DVR' => 'Devren',
            'TKS' => 'Takas',
        ];

        return $map[$tipKodu] ?? $tipKodu;
    }

    /**
     * Kategori kodundan açıklama al
     * 
     * @param string $kategoriKodu
     * @return string
     */
    public function getKategoriAciklama(string $kategoriKodu): string
    {
        $map = [
            'DRE' => 'Daire',
            'VLA' => 'Villa',
            'ARS' => 'Arsa',
            'ISY' => 'İşyeri',
            'KNT' => 'Konut',
            'TCR' => 'Ticari',
            'RSD' => 'Residence',
            'YZL' => 'Yazlık',
            'TRS' => 'Turistik',
            'OFS' => 'Ofis',
            'MGZ' => 'Mağaza',
            'DPO' => 'Depo',
            'FBR' => 'Fabrika',
            'BNA' => 'Bina',
            'TRL' => 'Tarla',
            'BHC' => 'Bahçe',
        ];

        return $map[$kategoriKodu] ?? $kategoriKodu;
    }
}
