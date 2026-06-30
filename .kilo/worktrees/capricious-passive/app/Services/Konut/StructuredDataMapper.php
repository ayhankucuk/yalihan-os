<?php

namespace App\Services\Konut;

class StructuredDataMapper
{
    public function mapFromWizardInput(array $payload): array
    {
        $structured = [];
        
        if (isset($payload['lokasyon'])) {
            $structured['lokasyon'] = $this->mapLocation($payload['lokasyon']);
        }
        
        if (isset($payload['konut_tipi'])) {
            $structured['konut_tipi'] = $this->trimString($payload['konut_tipi']);
        }
        
        if (isset($payload['oda_sayisi'])) {
            $structured['oda_sayisi'] = $this->toInt($payload['oda_sayisi']);
        }
        
        if (isset($payload['salon_sayisi'])) {
            $structured['salon_sayisi'] = $this->toInt($payload['salon_sayisi']);
        }
        
        if (isset($payload['brut_m2'])) {
            $structured['brut_m2'] = $this->toFloat($payload['brut_m2']);
        }
        
        if (isset($payload['net_m2'])) {
            $structured['net_m2'] = $this->toFloat($payload['net_m2']);
        }
        
        if (isset($payload['banyo_sayisi'])) {
            $structured['banyo_sayisi'] = $this->toInt($payload['banyo_sayisi']);
        }
        
        if (isset($payload['bina_yasi'])) {
            $structured['bina_yasi'] = $this->toInt($payload['bina_yasi']);
        }
        
        if (isset($payload['kat'])) {
            $structured['kat'] = $this->toInt($payload['kat']);
        }
        
        if (isset($payload['toplam_kat'])) {
            $structured['toplam_kat'] = $this->toInt($payload['toplam_kat']);
        }
        
        if (isset($payload['ic_ozellikler'])) {
            $structured['ic_ozellikler'] = $this->mapIcOzellikler($payload['ic_ozellikler']);
        }
        
        if (isset($payload['dis_ozellikler'])) {
            $structured['dis_ozellikler'] = $this->mapDisOzellikler($payload['dis_ozellikler']);
        }
        
        if (isset($payload['bina'])) {
            $structured['bina'] = $this->mapBina($payload['bina']);
        }
        
        if (isset($payload['tapu_imar'])) {
            $structured['tapu_imar'] = $this->mapTapuImar($payload['tapu_imar']);
        }
        
        if (isset($payload['ulasim'])) {
            $structured['ulasim'] = $this->mapUlasim($payload['ulasim']);
        }
        
        if (isset($payload['sosyal'])) {
            $structured['sosyal'] = $this->mapSosyal($payload['sosyal']);
        }
        
        if (isset($payload['guvenlik'])) {
            $structured['guvenlik'] = $this->mapGuvenlik($payload['guvenlik']);
        }
        
        if (isset($payload['enerji'])) {
            $structured['enerji'] = $this->mapEnerji($payload['enerji']);
        }
        
        if (isset($payload['fiyat'])) {
            $structured['fiyat'] = $this->mapFiyat($payload['fiyat']);
        }
        
        if (isset($payload['seo_meta'])) {
            $structured['seo_meta'] = $this->mapSeoMeta($payload['seo_meta']);
        }
        
        $structured['etiketler'] = $this->computeEtiketler($structured);
        
        return $structured;
    }
    
    public function mapFromFeatureValues(array $featureValues): array
    {
        return $this->mapFromWizardInput($featureValues);
    }
    
    public function computeEtiketler(array $structuredData): array
    {
        $etiketler = [];
        
        if (isset($structuredData['tapu_imar']['krediye_uygun']) && $structuredData['tapu_imar']['krediye_uygun'] === true) {
            $etiketler[] = 'krediye_uygun';
        }
        
        if (isset($structuredData['bina']['site_icinde']) && $structuredData['bina']['site_icinde'] === true) {
            $etiketler[] = 'site_icinde';
        }
        
        if (isset($structuredData['bina']['asansor']) && $structuredData['bina']['asansor'] === true) {
            $etiketler[] = 'asansor';
        }
        
        if (isset($structuredData['dis_ozellikler']['otopark']) && $structuredData['dis_ozellikler']['otopark'] !== 'yok') {
            $etiketler[] = 'otopark';
        }
        
        if (isset($structuredData['ic_ozellikler']['manzara']) && $structuredData['ic_ozellikler']['manzara'] === 'deniz') {
            $etiketler[] = 'deniz_manzarasi';
        }
        
        if (isset($structuredData['bina_yasi']) && $structuredData['bina_yasi'] <= 5) {
            $etiketler[] = 'yeni_bina';
        }
        
        if (isset($structuredData['fiyat']['yatirimlik']) && $structuredData['fiyat']['yatirimlik'] === true) {
            $etiketler[] = 'yatirimlik';
        }
        
        if (isset($structuredData['ic_ozellikler']['esyali']) && $structuredData['ic_ozellikler']['esyali'] === 'esyali') {
            $etiketler[] = 'esyali';
        }
        
        if ((isset($structuredData['dis_ozellikler']['havuz']) && $structuredData['dis_ozellikler']['havuz'] === true) ||
            (isset($structuredData['bina']['havuz']) && $structuredData['bina']['havuz'] === true)) {
            $etiketler[] = 'havuz';
        }
        
        if (isset($structuredData['bina']['guvenlik']) && $structuredData['bina']['guvenlik'] === true) {
            $etiketler[] = 'guvenlik';
        }
        
        if (isset($structuredData['tapu_imar']['takas']) && $structuredData['tapu_imar']['takas'] === true) {
            $etiketler[] = 'takas';
        }
        
        if (isset($structuredData['enerji']['enerji_sinifi'])) {
            $sinif = $structuredData['enerji']['enerji_sinifi'];
            if ($sinif === 'A') {
                $etiketler[] = 'enerji_sinifi_a';
            } elseif ($sinif === 'B') {
                $etiketler[] = 'enerji_sinifi_b';
            }
        }
        
        return array_unique($etiketler);
    }
    
    protected function mapLocation(array $data): array
    {
        $mapped = [];
        
        if (isset($data['il_id'])) {
            $mapped['il_id'] = $this->toInt($data['il_id']);
        }
        if (isset($data['ilce_id'])) {
            $mapped['ilce_id'] = $this->toInt($data['ilce_id']);
        }
        if (isset($data['mahalle_id'])) {
            $mapped['mahalle_id'] = $this->toInt($data['mahalle_id']);
        }
        if (isset($data['adres'])) {
            $mapped['adres'] = $this->trimString($data['adres']);
        }
        if (isset($data['lat'])) {
            $mapped['lat'] = $this->toFloat($data['lat']);
        }
        if (isset($data['lng'])) {
            $mapped['lng'] = $this->toFloat($data['lng']);
        }
        if (isset($data['merkez_mesafe'])) {
            $mapped['merkez_mesafe'] = $this->toInt($data['merkez_mesafe']);
        }
        if (isset($data['deniz_mesafe'])) {
            $mapped['deniz_mesafe'] = $this->toInt($data['deniz_mesafe']);
        }
        
        return $mapped;
    }
    
    protected function mapIcOzellikler(array $data): array
    {
        $mapped = [];
        
        if (isset($data['esyali'])) {
            $mapped['esyali'] = $this->trimString($data['esyali']);
        }
        
        $boolKeys = ['klima', 'merkezi_isitma', 'somine', 'jakuzi', 'balkon', 'teras'];
        foreach ($boolKeys as $key) {
            if (isset($data[$key])) {
                $mapped[$key] = $this->toBool($data[$key]);
            }
        }
        
        if (isset($data['cephe'])) {
            $mapped['cephe'] = $this->trimString($data['cephe']);
        }
        if (isset($data['manzara'])) {
            $mapped['manzara'] = $this->trimString($data['manzara']);
        }
        
        return $mapped;
    }
    
    protected function mapDisOzellikler(array $data): array
    {
        $mapped = [];
        
        if (isset($data['bahce_var'])) {
            $mapped['bahce_var'] = $this->toBool($data['bahce_var']);
        }
        if (isset($data['bahce_buyuklugu'])) {
            $mapped['bahce_buyuklugu'] = $this->toInt($data['bahce_buyuklugu']);
        }
        if (isset($data['otopark'])) {
            $mapped['otopark'] = $this->trimString($data['otopark']);
        }
        if (isset($data['otopark_kapasitesi'])) {
            $mapped['otopark_kapasitesi'] = $this->toInt($data['otopark_kapasitesi']);
        }
        if (isset($data['barbeku'])) {
            $mapped['barbeku'] = $this->toBool($data['barbeku']);
        }
        if (isset($data['havuz'])) {
            $mapped['havuz'] = $this->toBool($data['havuz']);
        }
        if (isset($data['havuz_turu'])) {
            $mapped['havuz_turu'] = $this->trimString($data['havuz_turu']);
        }
        
        return $mapped;
    }
    
    protected function mapBina(array $data): array
    {
        $mapped = [];
        
        $boolKeys = ['asansor', 'site_icinde', 'guvenlik', 'kapali_otopark', 'cocuk_oyun_alani', 'spor_salonu', 'havuz', 'yonetim'];
        foreach ($boolKeys as $key) {
            if (isset($data[$key])) {
                $mapped[$key] = $this->toBool($data[$key]);
            }
        }
        
        return $mapped;
    }
    
    protected function mapTapuImar(array $data): array
    {
        $mapped = [];
        
        if (isset($data['tapu_durumu'])) {
            $mapped['tapu_durumu'] = $this->trimString($data['tapu_durumu']);
        }
        if (isset($data['imar_durumu'])) {
            $mapped['imar_durumu'] = $this->trimString($data['imar_durumu']);
        }
        if (isset($data['krediye_uygun'])) {
            $mapped['krediye_uygun'] = $this->toBool($data['krediye_uygun']);
        }
        if (isset($data['takas'])) {
            $mapped['takas'] = $this->toBool($data['takas']);
        }
        if (isset($data['deprem_yonetmeligi'])) {
            $mapped['deprem_yonetmeligi'] = $this->trimString($data['deprem_yonetmeligi']);
        }
        
        return $mapped;
    }
    
    protected function mapUlasim(array $data): array
    {
        $mapped = [];
        
        $distanceKeys = ['otobus_duragi_mesafe', 'metro_mesafe', 'market_mesafe', 'okul_mesafe', 'saglik_merkezi_mesafe'];
        foreach ($distanceKeys as $key) {
            if (isset($data[$key])) {
                $mapped[$key] = $this->toInt($data[$key]);
            }
        }
        
        return $mapped;
    }
    
    protected function mapSosyal(array $data): array
    {
        $mapped = [];
        
        $boolKeys = ['wifi', 'uydu', 'tv', 'muzik_sistemi'];
        foreach ($boolKeys as $key) {
            if (isset($data[$key])) {
                $mapped[$key] = $this->toBool($data[$key]);
            }
        }
        
        return $mapped;
    }
    
    protected function mapGuvenlik(array $data): array
    {
        $mapped = [];
        
        $boolKeys = ['kamera', 'alarm', 'interkom'];
        foreach ($boolKeys as $key) {
            if (isset($data[$key])) {
                $mapped[$key] = $this->toBool($data[$key]);
            }
        }
        
        return $mapped;
    }
    
    protected function mapEnerji(array $data): array
    {
        $mapped = [];
        
        if (isset($data['isitma_tipi'])) {
            $mapped['isitma_tipi'] = $this->trimString($data['isitma_tipi']);
        }
        if (isset($data['yakit_tipi'])) {
            $mapped['yakit_tipi'] = $this->trimString($data['yakit_tipi']);
        }
        if (isset($data['enerji_sinifi'])) {
            $mapped['enerji_sinifi'] = $this->trimString($data['enerji_sinifi']);
        }
        
        return $mapped;
    }
    
    protected function mapFiyat(array $data): array
    {
        $mapped = [];
        
        if (isset($data['satilik_fiyat'])) {
            $mapped['satilik_fiyat'] = $this->toFloat($data['satilik_fiyat']);
        }
        if (isset($data['para_birimi'])) {
            $mapped['para_birimi'] = $this->trimString($data['para_birimi']);
        }
        if (isset($data['aidat'])) {
            $mapped['aidat'] = $this->toFloat($data['aidat']);
        }
        if (isset($data['kira_getirisi'])) {
            $mapped['kira_getirisi'] = $this->toFloat($data['kira_getirisi']);
        }
        if (isset($data['yatirimlik'])) {
            $mapped['yatirimlik'] = $this->toBool($data['yatirimlik']);
        }
        
        return $mapped;
    }
    
    protected function mapSeoMeta(array $data): array
    {
        $mapped = [];
        
        if (isset($data['meta_title'])) {
            $mapped['meta_title'] = $this->trimString($data['meta_title']);
        }
        if (isset($data['meta_description'])) {
            $mapped['meta_description'] = $this->trimString($data['meta_description']);
        }
        if (isset($data['meta_keywords']) && is_array($data['meta_keywords'])) {
            $mapped['meta_keywords'] = array_filter(array_map('trim', $data['meta_keywords']));
        }
        
        return $mapped;
    }
    
    protected function trimString($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
    
    protected function toInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $int = filter_var($value, FILTER_VALIDATE_INT);
        return $int !== false ? $int : null;
    }
    
    protected function toFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $float !== false ? $float : null;
    }
    
    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            return in_array($lower, ['true', '1', 'yes', 'evet', 'var'], true);
        }
        return (bool) $value;
    }
}
