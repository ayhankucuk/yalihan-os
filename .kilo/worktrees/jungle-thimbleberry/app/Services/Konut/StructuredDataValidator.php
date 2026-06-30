<?php

namespace App\Services\Konut;

class StructuredDataValidator
{
    protected array $errors = [];
    
    public function validate(array $structuredData): array
    {
        $this->errors = [];
        
        $this->validateRequired($structuredData);
        $this->validateContradictions($structuredData);
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
        ];
    }
    
    protected function validateRequired(array $data): void
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
        
        if (!isset($data['oda_sayisi']) || $data['oda_sayisi'] === null) {
            $this->addError('oda_sayisi', 'Oda sayısı zorunludur');
        }
        
        if (!isset($data['salon_sayisi']) || $data['salon_sayisi'] === null) {
            $this->addError('salon_sayisi', 'Salon sayısı zorunludur');
        }
        
        if (!isset($data['brut_m2']) || $data['brut_m2'] === null) {
            $this->addError('brut_m2', 'Brüt m² zorunludur');
        }
        
        if (!isset($data['banyo_sayisi']) || $data['banyo_sayisi'] === null) {
            $this->addError('banyo_sayisi', 'Banyo sayısı zorunludur');
        }
        
        if (!isset($data['fiyat']['satilik_fiyat']) || $data['fiyat']['satilik_fiyat'] === null) {
            $this->addError('fiyat.satilik_fiyat', 'Satılık fiyat zorunludur');
        }
        
        if (!isset($data['fiyat']['para_birimi']) || empty($data['fiyat']['para_birimi'])) {
            $this->addError('fiyat.para_birimi', 'Para birimi zorunludur');
        }
    }
    
    protected function validateContradictions(array $data): void
    {
        if (isset($data['kat']) && isset($data['toplam_kat'])) {
            if ($data['kat'] > $data['toplam_kat']) {
                $this->addError('kat', 'Bulunduğu kat toplam kattan büyük olamaz');
            }
        }
        
        if (isset($data['net_m2']) && isset($data['brut_m2'])) {
            if ($data['net_m2'] > $data['brut_m2']) {
                $this->addError('net_m2', 'Net m² brüt m²\'den büyük olamaz');
            }
        }
        
        if (isset($data['tapu_imar']['krediye_uygun']) && $data['tapu_imar']['krediye_uygun'] === true) {
            if (!isset($data['tapu_imar']['tapu_durumu']) || empty($data['tapu_imar']['tapu_durumu'])) {
                $this->addError('tapu_imar.tapu_durumu', 'Krediye uygun ise tapu durumu belirtilmelidir');
            }
        }
        
        if (isset($data['dis_ozellikler']['havuz_turu']) && $data['dis_ozellikler']['havuz_turu'] !== 'yok') {
            if (!isset($data['dis_ozellikler']['havuz']) || $data['dis_ozellikler']['havuz'] !== true) {
                $this->addError('dis_ozellikler.havuz', 'Havuz türü belirtilmişse havuz alanı true olmalıdır');
            }
        }
        
        if (isset($data['dis_ozellikler']['otopark_kapasitesi']) && $data['dis_ozellikler']['otopark_kapasitesi'] > 0) {
            if (!isset($data['dis_ozellikler']['otopark']) || $data['dis_ozellikler']['otopark'] === 'yok') {
                $this->addError('dis_ozellikler.otopark', 'Otopark kapasitesi belirtilmişse otopark yok olamaz');
            }
        }
        
        if (isset($data['dis_ozellikler']['bahce_buyuklugu']) && $data['dis_ozellikler']['bahce_buyuklugu'] > 0) {
            if (!isset($data['dis_ozellikler']['bahce_var']) || $data['dis_ozellikler']['bahce_var'] !== true) {
                $this->addError('dis_ozellikler.bahce_var', 'Bahçe büyüklüğü belirtilmişse bahçe var olmalıdır');
            }
        }
        
        if (isset($data['bina_yasi']) && $data['bina_yasi'] < 0) {
            $this->addError('bina_yasi', 'Bina yaşı negatif olamaz');
        }
        
        if (isset($data['fiyat']['aidat']) && $data['fiyat']['aidat'] < 0) {
            $this->addError('fiyat.aidat', 'Aidat negatif olamaz');
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
