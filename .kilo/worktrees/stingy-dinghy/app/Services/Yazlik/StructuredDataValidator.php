<?php

namespace App\Services\Yazlik;

use Illuminate\Support\Arr;

class StructuredDataValidator
{
    protected array $errors = [];
    
    public function validate(array $structuredData, string $yayinTipiSlug): array
    {
        $this->errors = [];
        
        $this->validateRequired($structuredData, $yayinTipiSlug);
        $this->validateContradictions($structuredData);
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
        ];
    }
    
    protected function validateRequired(array $data, string $yayinTipiSlug): void
    {
        if (!isset($data['lokasyon']['il_id']) || $data['lokasyon']['il_id'] === null) {
            $this->addError('lokasyon.il_id', 'İl bilgisi zorunludur');
        }
        
        if (!isset($data['lokasyon']['ilce_id']) || $data['lokasyon']['ilce_id'] === null) {
            $this->addError('lokasyon.ilce_id', 'İlçe bilgisi zorunludur');
        }
        
        if (!isset($data['konut_tipi']) || empty($data['konut_tipi'])) {
            $this->addError('konut_tipi', 'Konut tipi zorunludur');
        }
        
        if (!isset($data['kapasite']['max_misafir']) || $data['kapasite']['max_misafir'] === null) {
            $this->addError('kapasite.max_misafir', 'Maksimum misafir sayısı zorunludur');
        }
        
        if (!isset($data['kapasite']['min_konaklama']) || $data['kapasite']['min_konaklama'] === null) {
            $this->addError('kapasite.min_konaklama', 'Minimum konaklama süresi zorunludur');
        }
        
        if (!isset($data['banyo']['banyo_sayisi']) || $data['banyo']['banyo_sayisi'] === null) {
            $this->addError('banyo.banyo_sayisi', 'Banyo sayısı zorunludur');
        }
        
        $this->validateYayinTipiSpecific($data, $yayinTipiSlug);
    }
    
    protected function validateYayinTipiSpecific(array $data, string $yayinTipiSlug): void
    {
        $normalizedSlug = strtolower(trim($yayinTipiSlug));
        
        if ($normalizedSlug === 'gunluk' || str_contains($normalizedSlug, 'günlük')) {
            if (!isset($data['fiyatlandirma']['gunluk_fiyat']) || $data['fiyatlandirma']['gunluk_fiyat'] === null) {
                $this->addError('fiyatlandirma.gunluk_fiyat', 'Günlük fiyat zorunludur');
            }
        }
        
        if ($normalizedSlug === 'haftalik' || str_contains($normalizedSlug, 'haftalık')) {
            if (!isset($data['fiyatlandirma']['haftalik_fiyat']) || $data['fiyatlandirma']['haftalik_fiyat'] === null) {
                $this->addError('fiyatlandirma.haftalik_fiyat', 'Haftalık fiyat zorunludur');
            }
        }
        
        if ($normalizedSlug === 'aylik' || str_contains($normalizedSlug, 'aylık')) {
            if (!isset($data['fiyatlandirma']['aylik_fiyat']) || $data['fiyatlandirma']['aylik_fiyat'] === null) {
                $this->addError('fiyatlandirma.aylik_fiyat', 'Aylık fiyat zorunludur');
            }
        }
        
        if ($normalizedSlug === 'sezonluk' || str_contains($normalizedSlug, 'sezonluk')) {
            if (!isset($data['fiyatlandirma']['gunluk_fiyat']) || $data['fiyatlandirma']['gunluk_fiyat'] === null) {
                $this->addError('fiyatlandirma.gunluk_fiyat', 'Günlük fiyat zorunludur (sezonluk kiralama için)');
            }
            if (!isset($data['fiyatlandirma']['sezon_baslangic']) || empty($data['fiyatlandirma']['sezon_baslangic'])) {
                $this->addError('fiyatlandirma.sezon_baslangic', 'Sezon başlangıç tarihi zorunludur');
            }
            if (!isset($data['fiyatlandirma']['sezon_bitis']) || empty($data['fiyatlandirma']['sezon_bitis'])) {
                $this->addError('fiyatlandirma.sezon_bitis', 'Sezon bitiş tarihi zorunludur');
            }
        }
    }
    
    protected function validateContradictions(array $data): void
    {
        if (isset($data['havuz_deniz']['havuz_turu']) && !empty($data['havuz_deniz']['havuz_turu'])) {
            if (!isset($data['havuz_deniz']['havuz']) || $data['havuz_deniz']['havuz'] !== true) {
                $this->addError('havuz_deniz.havuz', 'Havuz türü belirtilmişse havuz alanı true olmalıdır');
            }
        }
        
        if (isset($data['havuz_deniz']['denize_sifir']) && $data['havuz_deniz']['denize_sifir'] === true) {
            $denizeUzaklik = $data['havuz_deniz']['denize_uzaklik'] ?? null;
            if ($denizeUzaklik !== null && $denizeUzaklik !== 0) {
                $this->addError('havuz_deniz.denize_uzaklik', 'Denize sıfır ise denize uzaklık 0 olmalıdır');
            }
        }
        
        if (isset($data['fiyatlandirma']['sezon_baslangic']) && isset($data['fiyatlandirma']['sezon_bitis'])) {
            $baslangic = strtotime($data['fiyatlandirma']['sezon_baslangic']);
            $bitis = strtotime($data['fiyatlandirma']['sezon_bitis']);
            
            if ($baslangic !== false && $bitis !== false && $bitis <= $baslangic) {
                $this->addError('fiyatlandirma.sezon_bitis', 'Sezon bitiş tarihi başlangıç tarihinden sonra olmalıdır');
            }
        }
    }
    
    protected function addError(string $fieldPath, string $message): void
    {
        $this->errors[] = [
            'field' => $fieldPath,
            'message' => $message,
        ];
    }
}
