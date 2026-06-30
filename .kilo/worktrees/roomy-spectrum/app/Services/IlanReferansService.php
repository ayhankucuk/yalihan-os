<?php

namespace App\Services;

/**
 * @sab-ignore-catch
 */

use App\Models\Ilan;
use App\Traits\GuardsAgentWrites;

/**
 * Yalıhan Emlak Referans Numarası Sistemi
 *
 * Context7 Standardı: C7-REFERANS-NUMARA-2025-10-11
 *
 * Format: YE-{YAYINTIPI}-{LOKASYON}-{KATEGORI}-{SIRANO}
 * Örnek: YE-SAT-YALKVK-DAİRE-001234
 *
 * Kullanım:
 * $service = app(IlanReferansService::class);
 * $referans = $service->generateReferansNo($ilan);
 * $dosyaAdi = $service->generateDosyaAdi($ilan);
 */
class IlanReferansService
{
    use GuardsAgentWrites;
    /**
     * Yalıhan Emlak prefix (sabit)
     */
    const PREFIX = 'YE';

    /**
     * Referans numarası oluştur
     *
     * Format: YE-{YAYINTIPI}-{LOKASYON}-{KATEGORI}-{SIRANO}
     * Örnek: YE-SAT-YALKVK-DAİRE-001234
     */
    public function generateReferansNo(Ilan $ilan): string
    {
        $parts = [
            self::PREFIX,
            $this->getYayinTipiKodu($ilan),
            $this->getLokasyonKodu($ilan),
            $this->getKategoriKodu($ilan),
            $this->getSiraNo($ilan),
        ];

        return implode('-', array_filter($parts));
    }

    /**
     * Kullanıcı dostu dosya adı oluştur
     *
     * Format: Ref {ReferansNo} - {Lokasyon} {YayınTipi} {Kategori} {Site} ({Mal Sahibi})
     * Örnek: Ref YE-SAT-YALKVK-DAİRE-001234 - Yalıkavak Satılık Daire Ülkerler Sitesi (Ahmet Yılmaz)
     *
     * Context7: Referans numarası başta (telefonda kolay okunur)
     * Yalıhan Bekçi: Smart naming convention (2025-12-02)
     */
    public function generateDosyaAdi(Ilan $ilan): string
    {
        $parts = [];

        // Referans No (BAŞTA - Telefonda kolay okunur)
        $parts[] = 'Ref ' . $this->generateReferansNo($ilan);

        // Ayırıcı
        $parts[] = '-';

        // Lokasyon (İlçe veya Mahalle)
        if ($ilan->mahalle) {
            $parts[] = $ilan->mahalle->mahalle_adi;
        } elseif ($ilan->ilce) {
            $parts[] = $ilan->ilce->ilce_adi;
        } elseif ($ilan->il) {
            $parts[] = $ilan->il->il_adi;
        }

        // Yayın Tipi (Satılık, Kiralık, vb.)
        $parts[] = $this->getYayinTipiAdi($ilan);

        // Kategori
        $parts[] = $this->getKategoriAdi($ilan);

        // Site/Apartman
        if ($ilan->site) {
            $parts[] = $ilan->site->name;
        }

        // Mal Sahibi (Parantez içinde - EN SONDA)
        if ($ilan->ilanSahibi) {
            $malSahibi = trim($ilan->ilanSahibi->ad . ' ' . $ilan->ilanSahibi->soyad);
            $parts[] = "({$malSahibi})";
        }

        return implode(' ', array_filter($parts));
    }

    /**
     * Kısa dosya adı (klasör adı için)
     *
     * Context7 Standardı: Doküman formatına uygun
     * Format: {LOKASYON_KODU}-{YAYINTIPI}{YIL}-{SIRANO}_{Lokasyon}_{Site}_{Kategori}_{MalSahibi}
     * Örnek: YLK-S25-0012_Yalikavak_Sandima_No5_Daire_AKucuk
     */
    public function generateKisaDosyaAdi(Ilan $ilan): string
    {
        // Lokasyon kodu (ilk 3 harf)
        $lokasyonKodu = mb_strtoupper(mb_substr($this->getLokasyonKodu($ilan), 0, 3), 'UTF-8');

        // Yayın tipi kodu (ilk harf)
        $yayinTipi = $this->getYayinTipiKodu($ilan);
        $yayinTipiKodu = substr($yayinTipi, 0, 1); // S, K, G, D

        // Yıl (son 2 rakam)
        $yilKodu = substr(date('Y'), -2);

        // Sıra numarası (4 haneli)
        $siraNo = $this->getSiraNo($ilan);
        $siraNoKodu = substr($siraNo, -4); // Son 4 hane

        // Referans kısmı: YLK-S25-0012
        $refKisim = "{$lokasyonKodu}-{$yayinTipiKodu}{$yilKodu}-{$siraNoKodu}";

        // Lokasyon adı (temizlenmiş)
        $lokasyon = $ilan->mahalle?->mahalle_adi ?? $ilan->ilce?->ilce_adi ?? $ilan->il?->il_adi ?? 'Bilinmeyen';
        $lokasyon = $this->turkceKarakterTemizle($lokasyon);

        // Site adı (varsa)
        $site = '';
        if ($ilan->site) {
            $site = $this->turkceKarakterTemizle($ilan->site->name);
        }

        // Kategori (temizlenmiş)
        $kategori = $this->getKategoriAdi($ilan);
        $kategori = $this->turkceKarakterTemizle($kategori);

        // Mal sahibi (varsa, baş harfleri)
        $malSahibi = '';
        if ($ilan->ilanSahibi) {
            $ad = $ilan->ilanSahibi->ad ?? '';
            $soyad = $ilan->ilanSahibi->soyad ?? '';
            if ($ad && $soyad) {
                $malSahibi = mb_substr($ad, 0, 1) . mb_substr($soyad, 0, 1);
                $malSahibi = mb_strtoupper($malSahibi, 'UTF-8');
            }
        }

        // Parçaları birleştir
        $parts = [$refKisim, $lokasyon];
        if ($site) {
            $parts[] = $site;
        }
        $parts[] = $kategori;
        if ($malSahibi) {
            $parts[] = $malSahibi;
        }

        return implode('_', array_filter($parts));
    }

    /**
     * Drive klasör adı üretimi (insan odaklı, değişmez format)
     *
     * Format: {REFERANS_NO} - {Lokasyon} - {Kategori} - {Mal Sahibi}
     * Örnek: YE-SAT-YALKVK-DAİRE-001234 - Yalıkavak - Daire - Ahmet Yılmaz
     */
    public function generateDriveFolderName(Ilan $ilan): string
    {
        $referans = $ilan->referans_no ?? $this->generateReferansNo($ilan);

        $lokasyon = $ilan->mahalle->mahalle_adi
            ?? $ilan->ilce->ilce_adi
            ?? $ilan->il->il_adi
            ?? 'Lokasyon';

        $kategori = $ilan->kategori->name ?? 'Gayrimenkul';

        $sahibi = 'Bilinmeyen';
        if ($ilan->ilanSahibi) {
            $ad = $ilan->ilanSahibi->ad ?? '';
            $soyad = $ilan->ilanSahibi->soyad ?? '';
            $fullName = trim($ad . ' ' . $soyad);
            if ($fullName !== '') {
                $sahibi = $fullName;
            }
        }

        return sprintf('%s - %s - %s - %s', $referans, $lokasyon, $kategori, $sahibi);
    }

    /**
     * Yayın tipi kodu (SAT, KİR, GÜN)
     */
    protected function getYayinTipiKodu(Ilan $ilan): string
    {
        // İlan kategorisinden yayın tipini al
        $kategori = $ilan->kategori;

        if (! $kategori) {
            return 'SAT'; // Varsayılan
        }

        $name = strtolower($kategori->name);

        if (str_contains($name, 'satılık') || str_contains($name, 'satilik')) {
            return 'SAT';
        } elseif (str_contains($name, 'kiralık') || str_contains($name, 'kiralik')) {
            return 'KİR';
        } elseif (str_contains($name, 'günlük') || str_contains($name, 'gunluk')) {
            return 'GÜN';
        } elseif (str_contains($name, 'devren')) {
            return 'DEV';
        }

        return 'SAT'; // Varsayılan
    }

    /**
     * Yayın tipi adı (Satılık, Kiralık, vb.)
     */
    protected function getYayinTipiAdi(Ilan $ilan): string
    {
        $kod = $this->getYayinTipiKodu($ilan);

        $mapping = [
            'SAT' => 'Satılık',
            'KİR' => 'Kiralık',
            'GÜN' => 'Günlük Kiralık',
            'DEV' => 'Devren',
        ];

        return $mapping[$kod] ?? 'Satılık';
    }

    /**
     * Lokasyon kodu (İlçe/Mahalle kısa adı)
     */
    protected function getLokasyonKodu(Ilan $ilan): string
    {
        // Mahalle öncelikli, sonra ilçe, sonra il
        $lokasyon = $ilan->mahalle?->mahalle_adi
            ?? $ilan->ilce?->ilce_adi
            ?? $ilan->il?->il_adi
            ?? 'GENEL';

        // İlk 6 karakteri al ve büyük harfe çevir
        $kod = mb_strtoupper(mb_substr($lokasyon, 0, 6), 'UTF-8');

        // Türkçe karakterleri değiştir
        $kod = str_replace(
            ['Ç', 'Ğ', 'İ', 'Ö', 'Ş', 'Ü', 'ç', 'ğ', 'ı', 'ö', 'ş', 'ü'],
            ['C', 'G', 'I', 'O', 'S', 'U', 'C', 'G', 'I', 'O', 'S', 'U'],
            $kod
        );

        // Boşlukları kaldır
        $kod = str_replace(' ', '', $kod);

        return $kod;
    }

    /**
     * Kategori kodu
     */
    protected function getKategoriKodu(Ilan $ilan): string
    {
        $kategori = $ilan->kategori;

        if (! $kategori) {
            return 'GENEL';
        }

        // Ana kategoriyi al (parent varsa)
        while ($kategori->parent) {
            $kategori = $kategori->parent;
        }

        $name = mb_strtoupper($kategori->name, 'UTF-8');

        // Bilinen kategoriler için özel kodlar
        $kodlama = [
            'DAİRE' => 'DAİRE',
            'DAIRE' => 'DAİRE',
            'VİLLA' => 'VİLLA',
            'VILLA' => 'VİLLA',
            'ARSA' => 'ARSA',
            'YAZLIK' => 'YAZLK',
            'İŞYERİ' => 'İŞYER',
            'ISYERI' => 'İŞYER',
            'OFİS' => 'OFİS',
            'OFIS' => 'OFİS',
            'DÜKKAN' => 'DÜKKAN',
            'DEPO' => 'DEPO',
            'BINA' => 'BİNA',
        ];

        foreach ($kodlama as $anahtar => $kod) {
            if (str_contains($name, $anahtar)) {
                return $kod;
            }
        }

        // Varsayılan: İlk 5 karakter
        return mb_substr($name, 0, 5);
    }

    /**
     * Kategori adı (tam)
     */
    protected function getKategoriAdi(Ilan $ilan): string
    {
        return $ilan->kategori?->name ?? 'Gayrimenkul';
    }

    /**
     * Sıra numarası (6 haneli) - Sequence tablosu ile benzersiz
     */
    protected function getSiraNo(Ilan $ilan): string
    {
        // Sequence tablosundan benzersiz sıra numarası al
        $yayinTipi = $this->getYayinTipiKodu($ilan);
        $lokasyonKodu = $this->getLokasyonKodu($ilan);
        $kategoriKodu = $this->getKategoriKodu($ilan);
        $year = date('Y');

        $sequenceKey = \App\Models\RefSequence::generateSequenceKey(
            $yayinTipi,
            $lokasyonKodu,
            $kategoriKodu,
            $year
        );

        // Thread-safe sequence numarası al
        $sequence = \App\Models\RefSequence::getNextSequence($sequenceKey);

        // 6 haneli formata çevir
        return str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Türkçe karakter temizle (dosya adı için güvenli)
     */
    protected function turkceKarakterTemizle(string $text): string
    {
        $text = str_replace(
            ['Ç', 'Ğ', 'İ', 'Ö', 'Ş', 'Ü', 'ç', 'ğ', 'ı', 'ö', 'ş', 'ü', ' '],
            ['C', 'G', 'I', 'O', 'S', 'U', 'c', 'g', 'i', 'o', 's', 'u', '_'],
            $text
        );

        // Özel karakterleri kaldır
        $text = preg_replace('/[^A-Za-z0-9_-]/', '', $text);

        return $text;
    }

    /**
     * Referans numarasından ilan bul
     */
    public function findByReferansNo(string $referansNo): ?Ilan
    {
        // Referans numarasından ID'yi çıkar
        // Format: YE-SAT-YALKVK-DAİRE-001234

        $parts = explode('-', $referansNo);

        if (count($parts) < 5) {
            return null;
        }

        // Son kısım ID
        $id = (int) ltrim(end($parts), '0');

        return Ilan::find($id);
    }

    /**
     * Toplu referans numarası güncelle (mevcut ilanlar için)
     */
    public function updateAllReferansNumbers(): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        $ilanlar = Ilan::whereNull('referans_no')->get();
        $updated = 0;
        $errors = 0;

        // ✅ PERFORMANCE FIX: Bulk update için hazırlık
        $updates = [];

        foreach ($ilanlar as $ilan) {
            try {
                $referansNo = $this->generateReferansNo($ilan);
                $dosyaAdi = $this->generateDosyaAdi($ilan);

                $updates[$ilan->id] = [
                    'referans_no' => $referansNo,
                    'dosya_adi' => $dosyaAdi,
                ];
            } catch (\Exception $e) {
                $errors++;
            }
        }

        // ✅ PERFORMANCE FIX: Bulk update (CASE WHEN ile)
        if (! empty($updates)) {
            $referansCases = [];
            $dosyaCases = [];
            $bindings = [];
            $ids = [];

            foreach ($updates as $id => $data) {
                $referansCases[] = 'WHEN ? THEN ?';
                $dosyaCases[] = 'WHEN ? THEN ?';
                $bindings[] = $id;
                $bindings[] = $data['referans_no'];
                $bindings[] = $id;
                $bindings[] = $data['dosya_adi'];
                $ids[] = $id;
            }

            $idsPlaceholder = implode(',', array_fill(0, count($ids), '?'));
            $referansCasesSql = implode(' ', $referansCases);
            $dosyaCasesSql = implode(' ', $dosyaCases);

            \Illuminate\Support\Facades\DB::statement(
                "UPDATE ilanlar
                 SET referans_no = CASE id {$referansCasesSql} END,
                     dosya_adi = CASE id {$dosyaCasesSql} END,
                     updated_at = NOW()
                 WHERE id IN ({$idsPlaceholder})",
                array_merge($bindings, $ids)
            );

            $updated = count($updates);
        }

        return [
            'success' => true,
            'updated' => $updated,
            'errors' => $errors,
            'total' => $ilanlar->count(),
        ];
    }

    /**
     * Referans numarası benzersizlik kontrolü
     */
    public function isUnique(string $referansNo, ?int $excludeIlanId = null): bool
    {
        $query = Ilan::where('referans_no', $referansNo);

        if ($excludeIlanId) {
            $query->where('id', '!=', $excludeIlanId);
        }

        return $query->count() === 0;
    }

    /**
     * Başarı mesajı oluştur (copyable referans no ile)
     */
    public function getSuccessMessage(Ilan $ilan): array
    {
        $referansNo = $ilan->referans_no ?? $this->generateReferansNo($ilan);
        $dosyaAdi = $ilan->dosya_adi ?? $this->generateDosyaAdi($ilan);

        return [
            'title' => '🎉 İlan Başarıyla Oluşturuldu!',
            'referans_no' => $referansNo,
            'dosya_adi' => $dosyaAdi,
            'message' => "İlanınız başarıyla eklendi. Referans No: {$referansNo}",
            'copy_text' => $dosyaAdi,
            'show_modal' => true,
        ];
    }

    /**
     * Arama query builder (referans no, telefon, portal, site)
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function searchQuery(string $searchTerm)
    {
        $query = Ilan::query();

        // Referans no ile arama
        if (str_starts_with(strtoupper($searchTerm), 'YE-')) {
            return $query->where('referans_no', 'LIKE', "%{$searchTerm}%");
        }

        // Telefon ile arama
        if (preg_match('/^[0-9+\s()-]+$/', $searchTerm)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $searchTerm);

            return $query->whereHas('ilanSahibi', function ($q) use ($cleanPhone) {
                $q->where('telefon', 'LIKE', "%{$cleanPhone}%")
                    ->orWhere('cep_telefonu', 'LIKE', "%{$cleanPhone}%");
            });
        }

        // Portal ID ile arama
        if (preg_match('/^\d{8,}$/', $searchTerm)) {
            return $query->where(function ($q) use ($searchTerm) {
                $q->where('sahibinden_id', $searchTerm)
                    ->orWhere('emlakjet_id', $searchTerm)
                    ->orWhere('hepsiemlak_id', $searchTerm)
                    ->orWhere('zingat_id', $searchTerm);
            });
        }

        // Site/Apartman adı ile arama
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('baslik', 'LIKE', "%{$searchTerm}%")
                ->orWhere('dosya_adi', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('site', function ($sq) use ($searchTerm) {
                    $sq->where('name', 'LIKE', "%{$searchTerm}%");
                });
        });
    }
}
