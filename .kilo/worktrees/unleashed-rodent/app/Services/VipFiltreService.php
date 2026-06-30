<?php

namespace App\Services;

use App\Models\Ilan;
use App\Models\VipTercihMatrisi;
use Illuminate\Support\Collection;

/**
 * VIP Filtre Service
 *
 * [YALIHAN_COMMUNICATION_0206]
 * İlan-VIP eşleştirme/filtreleme
 */
class VipFiltreService
{
    /**
     * İlan için uygun VIP'leri filtrele
     *
     * @param Ilan $ilan
     * @return Collection<VipTercihMatrisi>
     */
    public function filtrele(Ilan $ilan): Collection
    {
        return VipTercihMatrisi::aktif()
            ->get()
            ->filter(function (VipTercihMatrisi $vip) use ($ilan) {
                return $this->tumFiltreleriGecti($ilan, $vip);
            });
    }

    /**
     * Tüm filtreleri kontrol et
     */
    private function tumFiltreleriGecti(Ilan $ilan, VipTercihMatrisi $vip): bool
    {
        // Lokasyon kontrolü
        if (!$this->lokasyonUygunMu($ilan, $vip)) {
            return false;
        }

        // Kategori kontrolü
        if (!$this->kategoriUygunMu($ilan, $vip)) {
            return false;
        }

        // Fiyat kontrolü
        if (!$this->fiyatUygunMu($ilan, $vip)) {
            return false;
        }

        return true;
    }

    /**
     * Lokasyon uyumu kontrolü
     */
    private function lokasyonUygunMu(Ilan $ilan, VipTercihMatrisi $vip): bool
    {
        // Tercih yoksa tüm lokasyonlar uygun
        if (empty($vip->tercih_lokasyonlar)) {
            return true;
        }

        $ilanLokasyonlari = array_filter([
            $ilan->il->name ?? null,
            $ilan->ilce->name ?? null,
        ]);

        // İlan lokasyonlarından biri VIP tercihlerinde var mı?
        foreach ($ilanLokasyonlari as $lokasyon) {
            if (in_array($lokasyon, $vip->tercih_lokasyonlar)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kategori uyumu kontrolü
     */
    private function kategoriUygunMu(Ilan $ilan, VipTercihMatrisi $vip): bool
    {
        // Tercih yoksa tüm kategoriler uygun
        if (empty($vip->tercih_kategoriler)) {
            return true;
        }

        $ilanKategorileri = array_filter([
            $ilan->anaKategori->name ?? null,
            $ilan->altKategori->name ?? null,
        ]);

        // İlan kategorilerinden biri VIP tercihlerinde var mı?
        foreach ($ilanKategorileri as $kategori) {
            if (in_array($kategori, $vip->tercih_kategoriler)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fiyat uyumu kontrolü
     */
    private function fiyatUygunMu(Ilan $ilan, VipTercihMatrisi $vip): bool
    {
        // Fiyat bilgisi yoksa geç
        if (!$ilan->fiyat) {
            return true;
        }

        // Hem min hem max yoksa tüm fiyatlar uygun
        if (!$vip->min_fiyat && !$vip->max_fiyat) {
            return true;
        }

        // Para birimi uyumu (basit kontrol - gerçekte currency conversion gerekebilir)
        $ilanFiyat = $ilan->fiyat;

        // Para birimi farklıysa approximate check (@warning Sabit kur — gerçek döviz servisi gerekli)
        if ($ilan->para_birimi !== $vip->para_birimi) {
            // Basit conversion rates (örnek)
            $rates = [
                'USD' => 1.0,
                'EUR' => 1.1,
                'TRY' => 0.037,
            ];

            $ilanRate = $rates[$ilan->para_birimi] ?? 1.0;
            $vipRate = $rates[$vip->para_birimi] ?? 1.0;

            $ilanFiyat = ($ilanFiyat * $ilanRate) / $vipRate;
        }

        // Min fiyat kontrolü
        if ($vip->min_fiyat && $ilanFiyat < $vip->min_fiyat) {
            return false;
        }

        // Max fiyat kontrolü
        if ($vip->max_fiyat && $ilanFiyat > $vip->max_fiyat) {
            return false;
        }

        return true;
    }
}
