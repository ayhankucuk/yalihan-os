<?php

namespace App\Domain\Workforce\Support;

use App\Models\Ilan;
use App\Models\Ozellik;
use Illuminate\Support\Facades\DB;

/**
 * PropertyDescriptionGenerator — Sprint 7.3
 *
 * Ilan verilerinden standart Türkçe emlak açıklaması üretir.
 * AI LLM yerine kural-tabanlı template + veri sentezi kullanır.
 * LLM entegrasyonu sonradan eklenebilir.
 */
class PropertyDescriptionGenerator
{
    /**
     * İlan için Türkçe emlak açıklaması üretir.
     *
     * @return array{baslik: string, aciklama: string, kisa_aciklama: string, anahtar kelimeler: array<string>}
     */
    public function generate(Ilan $ilan): array
    {
        $baslik = $this->generateBaslik($ilan);
        $aciklama = $this->generateAciklama($ilan);
        $kisaAciklama = $this->generateKisaAciklama($ilan, $baslik);
        $anahtarKelimeler = $this->extractAnahtarKelimeler($ilan);

        return [
            'baslik' => $baslik,
            'aciklama' => $aciklama,
            'kisa_aciklama' => $kisaAciklama,
            'anahtar_kelimeler' => $anahtarKelimeler,
        ];
    }

    /**
     * AI kullanarak açıklama üret (Cortex entegrasyonu).
     * Fallback: kural-tabanlı üretim.
     */
    public function generateWithAI(Ilan $ilan, \App\Services\AI\YalihanCortex $cortex): array
    {
        try {
            // Mevcut AI pipeline kullan
            $ilanData = $ilan->toArray();
            $response = $cortex->generateStructuredDescription([
                'ilan' => $ilanData,
                'context_builder_data' => (new \App\Services\AI\Description\DescriptionContextBuilder)->build($ilan),
            ]);

            if (!empty($response['description'])) {
                return [
                    'baslik' => $response['title'] ?? $this->generateBaslik($ilan),
                    'aciklama' => $response['description'],
                    'kisa_aciklama' => $this->generateKisaAciklama($ilan, $baslik ?? $this->generateBaslik($ilan)),
                    'anahtar_kelimeler' => $response['keywords'] ?? $this->extractAnahtarKelimeler($ilan),
                    'source' => 'ai',
                ];
            }
        } catch (\Throwable) {
            // AI başarısız → fallback kural-tabanlı
        }

        // Fallback
        $result = $this->generate($ilan);
        $result['source'] = 'template';
        return $result;
    }

    private function generateBaslik(Ilan $ilan): string
    {
        $parts = [];

        $parts[] = $ilan->yayin_tipi ?? 'Satılık';
        $parts[] = $ilan->alt_kategori ?? $ilan->kategori ?? 'Gayrimenkul';

        if ($ilan->brut_m2) {
            $parts[] = $ilan->brut_m2 . ' m²';
        }

        if ($ilan->oda_sayisi) {
            $parts[] = $ilan->oda_sayisi . '+1';
        }

        if ($ilan->bina_yasi && $ilan->bina_yasi < 2026) {
            $yasi = 2026 - $ilan->bina_yasi;
            $parts[] = $yasi . ' yaşında';
        } else {
            $parts[] = 'Sıfır Bina';
        }

        $parts[] = 'Bodrum';

        return implode(' ', $parts);
    }

    private function generateAciklama(Ilan $ilan): string
    {
        $lines = [];

        // Giriş cümlesi
        $konutTipi = $ilan->alt_kategori ?? $ilan->kategori ?? 'gayrimenkul';
        $yayinTipi = $ilan->yayin_tipi ?? 'Satılık';
        $lines[] = "Bodrum'un en prestijli bölgesinde, " . mb_strtolower($konutTipi) . " " . mb_strtolower($yayinTipi) . " fırsatı.";

        // Konum
        $konum = $this->buildKonumText($ilan);
        if ($konum) {
            $lines[] = $konum;
        }

        // Alan bilgisi
        if ($ilan->brut_m2) {
            $lines[] = $ilan->brut_m2 . " m² brut alana sahip bu gayrimenkul" .
                ($ilan->net_m2 ? ", " . $ilan->net_m2 . " m² net kullanım alanı ile" : "") . " geniş ve ferah bir yaşam alanı sunmaktadır.";
        }

        // Oda/banyo
        $odaBilgi = [];
        if ($ilan->oda_sayisi) $odaBilgi[] = $ilan->oda_sayisi . ' oda';
        if ($ilan->salon_sayisi) $odaBilgi[] = $ilan->salon_sayisi . ' salon';
        if ($ilan->banyo_sayisi) $odaBilgi[] = $ilan->banyo_sayisi . ' banyo';
        if ($odaBilgi) {
            $lines[] = implode(' ve ', $odaBilgi) . ' ile aileler için ideal bir düzen sunmaktadır.';
        }

        // Bina yaşı
        if ($ilan->bina_yasi) {
            $yasi = 2026 - $ilan->bina_yasi;
            if ($yasi == 0) {
                $lines[] = 'Sıfır bina olarak tamamen yeni ve modern bir yapıda yaşamın keyfini çıkarabilirsiniz.';
            } else {
                $lines[] = $yasi . " yıllık ".mb_strtolower($konutTipi)." olarak " .
                    ($yasi < 5 ? 'yeni sayılır bir yapıdadır.' : 'sağlam bir yapıya sahiptir.');
            }
        }

        // Isıtma
        if ($ilan->isinma_tipi || $ilan->isitma) {
            $isitma = $ilan->isinma_tipi ?? $ilan->isitma;
            $lines[] = ucfirst($isitma) . " ile yıl boyunca konforlu bir ortam sağlanmaktadır.";
        }

        // Özellikler (varsa)
        $ozellikler = $this->getOzellikText($ilan);
        if ($ozellikler) {
            $lines[] = 'Diğer özellikler: ' . $ozellikler . '.';
        }

        // Kapanış
        $lines[] = "Bu fırsatı kaçırmamak için bizimle iletişime geçin. Bodrum'da hayalinizdeki eve kavuşun.";

        return implode("\n\n", array_filter($lines));
    }

    private function generateKisaAciklama(Ilan $ilan, string $baslik): string
    {
        $parts = [];

        if ($ilan->brut_m2) $parts[] = $ilan->brut_m2 . ' m²';
        if ($ilan->oda_sayisi) $parts[] = $ilan->oda_sayisi . '+1';
        if ($ilan->banyo_sayisi) $parts[] = $ilan->banyo_sayisi . ' banyo';
        if ($ilan->bina_yasi) {
            $yasi = 2026 - $ilan->bina_yasi;
            $parts[] = $yasi == 0 ? 'Sıfır bina' : $yasi . ' yaşında';
        }

        $meta = implode(' · ', $parts);
        return $baslik . ($meta ? ' | ' . $meta : '');
    }

    /**
     * @return array<string>
     */
    private function extractAnahtarKelimeler(Ilan $ilan): array
    {
        $kelimeler = [];

        $kelimeler[] = $ilan->kategori ?? '';
        $kelimeler[] = $ilan->alt_kategori ?? '';
        $kelimeler[] = $ilan->yayin_tipi ?? '';
        $kelimeler[] = 'Bodrum';
        $kelimeler[] = $ilan->il ?? '';

        if ($ilan->brut_m2) $kelimeler[] = 'm²';
        if ($ilan->esyali) $kelimeler[] = 'Eşyalı';
        if ($ilan->havuz_var) $kelimeler[] = 'Havuzlu';
        if ($ilan->denize_mesafe ?? $ilan->deniz_mesafesi) $kelimeler[] = 'Denize yakın';
        if ($ilan->site_icerisinde) $kelimeler[] = 'Site içi';

        return array_values(array_filter(array_unique($kelimeler)));
    }

    private function buildKonumText(Ilan $ilan): string
    {
        $parts = [];

        if ($ilan->mahalle) $parts[] = $ilan->mahalle . ' Mahallesi';
        if ($ilan->ilce) $parts[] = $ilan->ilce . ' / Bodrum';
        else if ($ilan->il) $parts[] = $ilan->il;

        if (empty($parts)) return '';

        $text = implode(', ', $parts);
        return "Bodrum'un $text bölgesinde stratejik bir konumda yer almaktadır.";
    }

    /**
     * Özellik tablosundan string üret.
     */
    private function getOzellikText(Ilan $ilan): string
    {
        $labels = [];

        if ($ilan->esyali) $labels[] = 'Eşyalı';
        if ($ilan->havuz_var) $labels[] = 'Havuz';
        if ($ilan->site_icerisinde) $labels[] = 'Site içi';
        if ($ilan->asansor) $labels[] = 'Asansör';
        if ($ilan->otopark) $labels[] = 'Otopark';
        if ($ilan->balkon) $labels[] = 'Balkon';
        if ($ilan->dogalgaz_altyapisi || $ilan->dogalgaz) $labels[] = 'Doğalgaz';
        if ($ilan->guvenlik) $labels[] = 'Güvenlik';
        if ($ilan->spor_salonu) $labels[] = 'Spor salonu';

        return implode(', ', $labels);
    }
}
