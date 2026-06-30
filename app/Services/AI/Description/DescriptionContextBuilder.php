<?php

namespace App\Services\AI\Description;

use App\Models\Ilan;

/**
 * Description Context Builder
 *
 * Gathers structured data from Ilan for AI Description generation.
 *
 * AI Rules:
 * - ASLA varsayım yapma
 * - ASLA veri olmayan özelliği yazma
 * - SADECE structured data üzerinden konuş
 */
class DescriptionContextBuilder
{
    /**
     * Build context array for AI prompt
     *
     * @return array{
     *     lokasyon: array{il: string|null, ilce: string|null, mahalle: string|null},
     *     konut_tipi: string|null,
     *     kapasite: array{kisi_kapasitesi: int|null, yatak_odasi: int|null, banyo: int|null, mustakil: bool},
     *     havuz_deniz: array<string, mixed>,
     *     konfor: array<string, mixed>,
     *     bahce: array<string, mixed>,
     *     kurallar: array<string, mixed>,
     *     mesafe: array<string, mixed>,
     *     mevcut_aciklama: string|null
     * }
     */
    public function build(Ilan $ilan): array
    {
        return [
            'lokasyon' => $this->buildLokasyon($ilan),
            'konut_tipi' => $this->buildKonutTipi($ilan),
            'kapasite' => $this->buildKapasite($ilan),
            'havuz_deniz' => $this->buildHavuzDeniz($ilan),
            'konfor' => $this->buildKonfor($ilan),
            'bahce' => $this->buildBahce($ilan),
            'kurallar' => $this->buildKurallar($ilan),
            'mesafe' => $this->buildMesafe($ilan),
            'mevcut_aciklama' => $ilan->aciklama,
        ];
    }

    /**
     * Format context for AI prompt
     */
    public function formatForPrompt(array $context): string
    {
        $parts = [];

        // Lokasyon
        $lokasyon = $context['lokasyon'] ?? [];
        $lokasyonParts = [];
        if (! empty($lokasyon['il'])) {
            $lokasyonParts[] = "İl: {$lokasyon['il']}";
        }
        if (! empty($lokasyon['ilce'])) {
            $lokasyonParts[] = "İlçe: {$lokasyon['ilce']}";
        }
        if (! empty($lokasyon['mahalle'])) {
            $lokasyonParts[] = "Mahalle: {$lokasyon['mahalle']}";
        }
        $parts[] = "LOKASYON:\n".(! empty($lokasyonParts) ? implode("\n", $lokasyonParts) : 'Bilgi yok');

        // Konut Tipi
        $parts[] = 'KONUT TİPİ: '.($context['konut_tipi'] ?? 'Belirtilmemiş');

        // Kapasite
        $kapasite = $context['kapasite'] ?? [];
        $kapasiteParts = [];
        if (isset($kapasite['kisi_kapasitesi'])) {
            $kapasiteParts[] = "Kişi Kapasitesi: {$kapasite['kisi_kapasitesi']}";
        }
        if (isset($kapasite['yatak_odasi'])) {
            $kapasiteParts[] = "Yatak Odası: {$kapasite['yatak_odasi']}";
        }
        if (isset($kapasite['banyo'])) {
            $kapasiteParts[] = "Banyo: {$kapasite['banyo']}";
        }
        if (isset($kapasite['mustakil'])) {
            $kapasiteParts[] = 'Müstakil: '.($kapasite['mustakil'] ? 'Evet' : 'Hayır');
        }
        $parts[] = "KAPASİTE:\n".(! empty($kapasiteParts) ? implode("\n", $kapasiteParts) : 'Bilgi yok');

        // Havuz & Deniz
        $havuzDeniz = $context['havuz_deniz'] ?? [];
        $havuzParts = [];
        if (! empty($havuzDeniz['detay'])) {
            $havuzParts = array_merge($havuzParts, $havuzDeniz['detay']);
        }
        if (! empty($havuzDeniz['manzara'])) {
            $havuzParts[] = "Manzara: {$havuzDeniz['manzara']}";
        }
        $parts[] = "HAVUZ & DENİZ:\n".(! empty($havuzParts) ? implode("\n", $havuzParts) : 'Bilgi yok');

        // Konfor
        $konfor = $context['konfor'] ?? [];
        if (! empty($konfor)) {
            $konforList = array_keys(array_filter($konfor, fn ($v) => $v === true));
            $parts[] = 'KONFOR: '.(! empty($konforList) ? implode(', ', $konforList) : 'Belirtilmemiş');
        }

        // Bahçe
        $bahce = $context['bahce'] ?? [];
        if (! empty($bahce)) {
            $bahceList = array_keys(array_filter($bahce, fn ($v) => $v === true));
            $parts[] = 'BAHÇE & DIŞ ALAN: '.(! empty($bahceList) ? implode(', ', $bahceList) : 'Yok');
        }

        // Kurallar
        $kurallar = $context['kurallar'] ?? [];
        if (! empty($kurallar)) {
            $kurallarList = [];
            if (isset($kurallar['evcil_hayvan'])) {
                $kurallarList[] = 'Evcil Hayvan: '.($kurallar['evcil_hayvan'] ? 'İzinli' : 'Yasak');
            }
            if (isset($kurallar['sigara'])) {
                $kurallarList[] = 'Sigara: '.($kurallar['sigara'] ? 'İzinli' : 'Yasak');
            }
            $parts[] = "KURALLAR:\n".(! empty($kurallarList) ? implode("\n", $kurallarList) : 'Belirtilmemiş');
        }

        // Mesafe
        $mesafe = $context['mesafe'] ?? [];
        $mesafeParts = [];
        foreach ($mesafe as $key => $value) {
            if ($value !== null) {
                $mesafeParts[] = ucfirst(str_replace('_', ' ', $key)).": {$value} km";
            }
        }
        $parts[] = "MESAFE:\n".(! empty($mesafeParts) ? implode("\n", $mesafeParts) : 'Bilgi yok');

        return implode("\n\n", $parts);
    }

    // ========================================================================
    // PRIVATE BUILDERS
    // ========================================================================

    private function buildLokasyon(Ilan $ilan): array
    {
        return [
            'il' => $ilan->il?->il_adi,
            'ilce' => $ilan->ilce?->ilce_adi,
            'mahalle' => $ilan->mahalle?->mahalle_adi,
        ];
    }

    private function buildKonutTipi(Ilan $ilan): ?string
    {
        // Kategori ve alt kategoriden konut tipi çıkar
        $kategori = $ilan->anaKategori?->name ?? $ilan->altKategori?->name;
        $altKategori = $ilan->altKategori?->name;

        return $altKategori ?: $kategori;
    }

    private function buildKapasite(Ilan $ilan): array
    {
        return [
            'kisi_kapasitesi' => $ilan->max_guests ?? null,
            'yatak_odasi' => $ilan->oda_sayisi ?? null,
            'banyo' => $ilan->banyo_sayisi ?? null,
            'mustakil' => $ilan->toplam_hisseli !== true,
        ];
    }

    private function buildHavuzDeniz(Ilan $ilan): array
    {
        $detay = [];
        $manzara = [];

        // Havuz
        if ($ilan->havuz_var) {
            $detay[] = 'Özel Havuz: Var';
            if ($ilan->havuz_isitmali) {
                $detay[] = '- Isıtmalı';
            }
        }

        // Manzara
        if ($ilan->deniz_manzarali) {
            $manzara[] = 'Deniz';
        }
        if ($ilan->doga_manzarali) {
            $manzara[] = 'Doğa';
        }
        if ($ilan->dag_manzarali) {
            $manzara[] = 'Dağ';
        }

        return [
            'detay' => $detay,
            'manzara' => ! empty($manzara) ? implode(', ', $manzara) : null,
        ];
    }

    private function buildKonfor(Ilan $ilan): array
    {
        return array_filter([
            'Klima' => $ilan->isitma_var || $ilan->isinma_tipi,
            'Jakuzi' => property_exists($ilan, 'jakuzi') && $ilan->jakuzi,
            'Sauna' => property_exists($ilan, 'sauna') && $ilan->sauna,
            'Şömine' => property_exists($ilan, 'somine') && $ilan->somine,
            'Akıllı Ev' => property_exists($ilan, 'akilli_ev') && $ilan->akilli_ev,
            'Esyali' => $ilan->esyali,
        ]);
    }

    private function buildBahce(Ilan $ilan): array
    {
        return array_filter([
            'Bahçe' => $ilan->bahce_var,
            'Barbekü' => $ilan->barbeku_var,
            'Şezlong' => $ilan->sezlong_var,
            'Veranda' => property_exists($ilan, 'veranda') && $ilan->veranda,
        ]);
    }

    private function buildKurallar(Ilan $ilan): array
    {
        return array_filter([
            'evcil_hayvan' => $ilan->evcil_hayvan_uygun ?? null,
            'sigara' => ! ($ilan->sigara_icilmez ?? true),
        ]);
    }

    private function buildMesafe(Ilan $ilan): array
    {
        // Mesafe bilgileri genellikle metadata veya extra_ozellikler içinde
        $extra = $ilan->ekstra_ozellikler ?? [];

        return array_filter([
            'havalimani' => $extra['mesafe_havalimani'] ?? null,
            'market' => $extra['mesafe_market'] ?? null,
            'merkez' => $extra['mesafe_merkez'] ?? null,
            'plaj' => $extra['mesafe_plaj'] ?? null,
        ], fn ($v) => $v !== null);
    }
}
