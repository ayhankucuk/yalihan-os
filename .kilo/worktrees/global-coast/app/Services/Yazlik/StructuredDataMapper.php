<?php

namespace App\Services\Yazlik;

use Illuminate\Support\Arr;

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
        
        if (isset($payload['kapasite'])) {
            $structured['kapasite'] = $this->mapCapacity($payload['kapasite']);
        }
        
        if (isset($payload['havuz_deniz'])) {
            $structured['havuz_deniz'] = $this->mapPoolSea($payload['havuz_deniz']);
        }
        
        if (isset($payload['konfor'])) {
            $structured['konfor'] = $this->mapComfort($payload['konfor']);
        }
        
        if (isset($payload['bahce'])) {
            $structured['bahce'] = $this->mapGarden($payload['bahce']);
        }
        
        if (isset($payload['mutfak'])) {
            $structured['mutfak'] = $this->mapKitchen($payload['mutfak']);
        }
        
        if (isset($payload['banyo'])) {
            $structured['banyo'] = $this->mapBathroom($payload['banyo']);
        }
        
        if (isset($payload['kurallar'])) {
            $structured['kurallar'] = $this->mapRules($payload['kurallar']);
        }
        
        if (isset($payload['mesafe'])) {
            $structured['mesafe'] = $this->mapDistances($payload['mesafe']);
        }
        
        if (isset($payload['depozito_hasar'])) {
            $structured['depozito_hasar'] = $this->mapDepositDamage($payload['depozito_hasar']);
        }
        
        if (isset($payload['yasal'])) {
            $structured['yasal'] = $this->mapLegal($payload['yasal']);
        }
        
        if (isset($payload['fiyatlandirma'])) {
            $structured['fiyatlandirma'] = $this->mapPricing($payload['fiyatlandirma']);
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
        
        if (isset($structuredData['havuz_deniz']['havuz']) && $structuredData['havuz_deniz']['havuz'] === true) {
            if (isset($structuredData['havuz_deniz']['havuz_turu']) && $structuredData['havuz_deniz']['havuz_turu'] === 'ozel') {
                $etiketler[] = 'ozel_havuz';
            }
            if (isset($structuredData['havuz_deniz']['havuz_isitmali']) && $structuredData['havuz_deniz']['havuz_isitmali'] === true) {
                $etiketler[] = 'isitmali_havuz';
            }
        }
        
        if (isset($structuredData['havuz_deniz']['deniz_manzarasi'])) {
            $manzara = $structuredData['havuz_deniz']['deniz_manzarasi'];
            if ($manzara === 'tam') {
                $etiketler[] = 'deniz_manzarasi_tam';
            } elseif ($manzara === 'kismi') {
                $etiketler[] = 'deniz_manzarasi_kismi';
            }
        }
        
        if (isset($structuredData['havuz_deniz']['denize_sifir']) && $structuredData['havuz_deniz']['denize_sifir'] === true) {
            $etiketler[] = 'denize_sifir';
        }
        
        if (isset($structuredData['havuz_deniz']['ozel_plaj']) && $structuredData['havuz_deniz']['ozel_plaj'] === true) {
            $etiketler[] = 'ozel_plaj';
        }
        
        $konutTipi = $structuredData['konut_tipi'] ?? null;
        if (in_array($konutTipi, ['villa', 'tas_ev', 'malikane'], true)) {
            $etiketler[] = 'mustakil';
        }
        
        if (isset($structuredData['kapasite']['max_misafir']) && $structuredData['kapasite']['max_misafir'] >= 8) {
            $etiketler[] = 'kalabalik_aileye_uygun';
        }
        
        if (isset($structuredData['konfor']['jakuzi']) && $structuredData['konfor']['jakuzi'] === true) {
            $etiketler[] = 'jakuzi';
        }
        
        if (isset($structuredData['konfor']['somine']) && $structuredData['konfor']['somine'] === true) {
            $etiketler[] = 'somine';
        }
        
        if (isset($structuredData['bahce']['barbeku']) && $structuredData['bahce']['barbeku'] === true) {
            $etiketler[] = 'barbeku';
        }
        
        if (isset($structuredData['depozito_hasar']['depozito']) && $structuredData['depozito_hasar']['depozito'] > 0) {
            $etiketler[] = 'depozito_var';
        }
        
        if (isset($structuredData['kurallar']['pet_friendly'])) {
            if ($structuredData['kurallar']['pet_friendly'] === true) {
                $etiketler[] = 'evcil_hayvan_uygun';
            } else {
                $etiketler[] = 'evcil_hayvan_uygun_degil';
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
        if (isset($data['havaalani_mesafe'])) {
            $mapped['havaalani_mesafe'] = $this->toInt($data['havaalani_mesafe']);
        }
        if (isset($data['market_mesafe'])) {
            $mapped['market_mesafe'] = $this->toInt($data['market_mesafe']);
        }
        if (isset($data['restoran_mesafe'])) {
            $mapped['restoran_mesafe'] = $this->toInt($data['restoran_mesafe']);
        }
        if (isset($data['plaj_mesafe'])) {
            $mapped['plaj_mesafe'] = $this->toInt($data['plaj_mesafe']);
        }
        
        return $mapped;
    }
    
    protected function mapCapacity(array $data): array
    {
        $mapped = [];
        
        if (isset($data['max_misafir'])) {
            $mapped['max_misafir'] = $this->toInt($data['max_misafir']);
        }
        if (isset($data['min_konaklama'])) {
            $mapped['min_konaklama'] = $this->toInt($data['min_konaklama']);
        }
        if (isset($data['yatak_duzeni'])) {
            $mapped['yatak_duzeni'] = is_array($data['yatak_duzeni']) ? $data['yatak_duzeni'] : null;
        }
        if (isset($data['ekstra_yatak'])) {
            $mapped['ekstra_yatak'] = $this->toBool($data['ekstra_yatak']);
        }
        if (isset($data['bebek_yatagi'])) {
            $mapped['bebek_yatagi'] = $this->toBool($data['bebek_yatagi']);
        }
        
        return $mapped;
    }
    
    protected function mapPoolSea(array $data): array
    {
        $mapped = [];
        
        if (isset($data['havuz'])) {
            $mapped['havuz'] = $this->toBool($data['havuz']);
        }
        if (isset($data['havuz_turu'])) {
            $mapped['havuz_turu'] = $this->trimString($data['havuz_turu']);
        }
        if (isset($data['havuz_boyut'])) {
            $mapped['havuz_boyut'] = $this->trimString($data['havuz_boyut']);
        }
        if (isset($data['havuz_derinlik'])) {
            $mapped['havuz_derinlik'] = $this->toFloat($data['havuz_derinlik']);
        }
        if (isset($data['havuz_isitmali'])) {
            $mapped['havuz_isitmali'] = $this->toBool($data['havuz_isitmali']);
        }
        if (isset($data['denize_uzaklik'])) {
            $mapped['denize_uzaklik'] = $this->toInt($data['denize_uzaklik']);
        }
        if (isset($data['deniz_manzarasi'])) {
            $mapped['deniz_manzarasi'] = $this->trimString($data['deniz_manzarasi']);
        }
        if (isset($data['denize_sifir'])) {
            $mapped['denize_sifir'] = $this->toBool($data['denize_sifir']);
        }
        if (isset($data['ozel_plaj'])) {
            $mapped['ozel_plaj'] = $this->toBool($data['ozel_plaj']);
        }
        if (isset($data['plaj_erisimi'])) {
            $mapped['plaj_erisimi'] = $this->trimString($data['plaj_erisimi']);
        }
        
        return $mapped;
    }
    
    protected function mapComfort(array $data): array
    {
        $mapped = [];
        
        $comfortKeys = [
            'esyali', 'klima', 'merkezi_isitma', 'somine', 'jakuzi', 'sauna', 'hamam',
            'camasir_makinesi', 'bulasik_makinesi', 'wifi', 'uydu', 'tv', 'muzik_sistemi'
        ];
        
        foreach ($comfortKeys as $key) {
            if (isset($data[$key])) {
                if (in_array($key, ['esyali'], true)) {
                    $mapped[$key] = $this->trimString($data[$key]);
                } else {
                    $mapped[$key] = $this->toBool($data[$key]);
                }
            }
        }
        
        return $mapped;
    }
    
    protected function mapGarden(array $data): array
    {
        $mapped = [];
        
        if (isset($data['bahce_var'])) {
            $mapped['bahce_var'] = $this->toBool($data['bahce_var']);
        }
        if (isset($data['bahce_buyuklugu'])) {
            $mapped['bahce_buyuklugu'] = $this->toInt($data['bahce_buyuklugu']);
        }
        if (isset($data['teras'])) {
            $mapped['teras'] = $this->toBool($data['teras']);
        }
        if (isset($data['balkon'])) {
            $mapped['balkon'] = $this->toBool($data['balkon']);
        }
        if (isset($data['barbeku'])) {
            $mapped['barbeku'] = $this->toBool($data['barbeku']);
        }
        if (isset($data['dis_dus'])) {
            $mapped['dis_dus'] = $this->toBool($data['dis_dus']);
        }
        if (isset($data['otopark'])) {
            $mapped['otopark'] = $this->trimString($data['otopark']);
        }
        if (isset($data['otopark_kapasitesi'])) {
            $mapped['otopark_kapasitesi'] = $this->toInt($data['otopark_kapasitesi']);
        }
        
        return $mapped;
    }
    
    protected function mapKitchen(array $data): array
    {
        $mapped = [];
        
        if (isset($data['mutfak_tipi'])) {
            $mapped['mutfak_tipi'] = $this->trimString($data['mutfak_tipi']);
        }
        if (isset($data['buzdolabi'])) {
            $mapped['buzdolabi'] = $this->toBool($data['buzdolabi']);
        }
        if (isset($data['firin'])) {
            $mapped['firin'] = $this->toBool($data['firin']);
        }
        if (isset($data['mikrodalga'])) {
            $mapped['mikrodalga'] = $this->toBool($data['mikrodalga']);
        }
        if (isset($data['kahve_makinesi'])) {
            $mapped['kahve_makinesi'] = $this->toBool($data['kahve_makinesi']);
        }
        if (isset($data['caydanlik'])) {
            $mapped['caydanlik'] = $this->toBool($data['caydanlik']);
        }
        
        return $mapped;
    }
    
    protected function mapBathroom(array $data): array
    {
        $mapped = [];
        
        if (isset($data['banyo_sayisi'])) {
            $mapped['banyo_sayisi'] = $this->toInt($data['banyo_sayisi']);
        }
        if (isset($data['dus'])) {
            $mapped['dus'] = $this->toBool($data['dus']);
        }
        if (isset($data['kuvet'])) {
            $mapped['kuvet'] = $this->toBool($data['kuvet']);
        }
        if (isset($data['sac_kurutma_makinesi'])) {
            $mapped['sac_kurutma_makinesi'] = $this->toBool($data['sac_kurutma_makinesi']);
        }
        if (isset($data['havlu'])) {
            $mapped['havlu'] = $this->toBool($data['havlu']);
        }
        
        return $mapped;
    }
    
    protected function mapRules(array $data): array
    {
        $mapped = [];
        
        if (isset($data['check_in_saati'])) {
            $mapped['check_in_saati'] = $this->trimString($data['check_in_saati']);
        }
        if (isset($data['check_out_saati'])) {
            $mapped['check_out_saati'] = $this->trimString($data['check_out_saati']);
        }
        if (isset($data['bebek_uygun'])) {
            $mapped['bebek_uygun'] = $this->toBool($data['bebek_uygun']);
        }
        if (isset($data['cocuk_uygun'])) {
            $mapped['cocuk_uygun'] = $this->toBool($data['cocuk_uygun']);
        }
        if (isset($data['pet_friendly'])) {
            $mapped['pet_friendly'] = $this->toBool($data['pet_friendly']);
        }
        if (isset($data['sigara_icilir'])) {
            $mapped['sigara_icilir'] = $this->toBool($data['sigara_icilir']);
        }
        if (isset($data['parti_duzenlenir'])) {
            $mapped['parti_duzenlenir'] = $this->toBool($data['parti_duzenlenir']);
        }
        if (isset($data['iptal_politikasi'])) {
            $mapped['iptal_politikasi'] = $this->trimString($data['iptal_politikasi']);
        }
        if (isset($data['havuz_kullanim'])) {
            $mapped['havuz_kullanim'] = $this->trimString($data['havuz_kullanim']);
        }
        
        return $mapped;
    }
    
    protected function mapDistances(array $data): array
    {
        $mapped = [];
        
        $distanceKeys = ['merkez_mesafe', 'havaalani_mesafe', 'market_mesafe', 'restoran_mesafe', 'plaj_mesafe'];
        
        foreach ($distanceKeys as $key) {
            if (isset($data[$key])) {
                $mapped[$key] = $this->toInt($data[$key]);
            }
        }
        
        return $mapped;
    }
    
    protected function mapDepositDamage(array $data): array
    {
        $mapped = [];
        
        if (isset($data['depozito'])) {
            $mapped['depozito'] = $this->toFloat($data['depozito']);
        }
        if (isset($data['hasar_sigortasi'])) {
            $mapped['hasar_sigortasi'] = $this->toBool($data['hasar_sigortasi']);
        }
        if (isset($data['hasar_sigortasi_tutari'])) {
            $mapped['hasar_sigortasi_tutari'] = $this->toFloat($data['hasar_sigortasi_tutari']);
        }
        if (isset($data['temizlik_ucreti'])) {
            $mapped['temizlik_ucreti'] = $this->toFloat($data['temizlik_ucreti']);
        }
        
        return $mapped;
    }
    
    protected function mapLegal(array $data): array
    {
        $mapped = [];
        
        if (isset($data['ktb_izin_belgesi'])) {
            $mapped['ktb_izin_belgesi'] = $this->toBool($data['ktb_izin_belgesi']);
        }
        if (isset($data['ktb_belge_no'])) {
            $mapped['ktb_belge_no'] = $this->trimString($data['ktb_belge_no']);
        }
        if (isset($data['ktb_belge_tarihi'])) {
            $mapped['ktb_belge_tarihi'] = $this->trimString($data['ktb_belge_tarihi']);
        }
        if (isset($data['isletme_belgesi'])) {
            $mapped['isletme_belgesi'] = $this->toBool($data['isletme_belgesi']);
        }
        if (isset($data['vergi_levhasi'])) {
            $mapped['vergi_levhasi'] = $this->toBool($data['vergi_levhasi']);
        }
        
        return $mapped;
    }
    
    protected function mapPricing(array $data): array
    {
        $mapped = [];
        
        if (isset($data['gunluk_fiyat'])) {
            $mapped['gunluk_fiyat'] = $this->toFloat($data['gunluk_fiyat']);
        }
        if (isset($data['haftalik_fiyat'])) {
            $mapped['haftalik_fiyat'] = $this->toFloat($data['haftalik_fiyat']);
        }
        if (isset($data['aylik_fiyat'])) {
            $mapped['aylik_fiyat'] = $this->toFloat($data['aylik_fiyat']);
        }
        if (isset($data['sezonluk_fiyat'])) {
            $mapped['sezonluk_fiyat'] = $this->toFloat($data['sezonluk_fiyat']);
        }
        if (isset($data['yaz_sezonu_fiyat'])) {
            $mapped['yaz_sezonu_fiyat'] = $this->toFloat($data['yaz_sezonu_fiyat']);
        }
        if (isset($data['ara_sezon_fiyat'])) {
            $mapped['ara_sezon_fiyat'] = $this->toFloat($data['ara_sezon_fiyat']);
        }
        if (isset($data['kis_sezonu_fiyat'])) {
            $mapped['kis_sezonu_fiyat'] = $this->toFloat($data['kis_sezonu_fiyat']);
        }
        if (isset($data['sezon_baslangic'])) {
            $mapped['sezon_baslangic'] = $this->trimString($data['sezon_baslangic']);
        }
        if (isset($data['sezon_bitis'])) {
            $mapped['sezon_bitis'] = $this->trimString($data['sezon_bitis']);
        }
        if (isset($data['elektrik_dahil'])) {
            $mapped['elektrik_dahil'] = $this->toBool($data['elektrik_dahil']);
        }
        if (isset($data['su_dahil'])) {
            $mapped['su_dahil'] = $this->toBool($data['su_dahil']);
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
