<?php

/**
 * Feature Assignment Rules - Publication Type Based
 * 
 * Context7 Standard: C7-FEATURE-ASSIGNMENT-RULES-2026-01-05
 * Phase 3: Publication-Type Intelligence System
 * 
 * Bu config, yayın tipi bazında yasaklı (disallowed) feature slug'larını tanımlar.
 * FeatureCategoryService bu kuralları kullanarak akıllı filtreleme yapar.
 */

return [
    /**
     * Arsa & Arazi kategorileri için yayın tipi bazlı yasaklı özellikler
     * 
     * MANTIK: Arsa'da konut/bina özellikleri gösterilmemeli
     */
    'arsa-arazi' => [
        'satilik' => [
            // Konut İç Mekan
            'oda-sayisi', 'banyo-sayisi', 'salon-sayisi', 'balkon-sayisi',
            'yatak', 'oda', 'banyo', 'salon', 'balkon',
            
            // Teknik Donanım
            'ankastre', 'klima', 'wifi', 'tv', 'buzdolabi', 'camasir', 'bulasik',
            'asansor', 'jenerator', 'hidrofor',
            
            // Site Özellikleri
            'havuz', 'fitness', 'guvenlik', 'kamerali-guvenlik',
            'cocuk-oyun', 'basketbol', 'tenis-kortu',
            
            // Yazlık/Kiralama Özellikleri
            'gunluk-fiyat', 'haftalik-fiyat', 'aylik-fiyat', 'sezonluk-fiyat',
            'min-konaklama', 'max-misafir', 'check-in', 'check-out',
            'depozito', 'temizlik-ucreti', 'pet-friendly',
            
            // İşyeri Özellikleri
            'kira-getirisi', 'aidat', 'isitma-bedeli', 'ciro',
        ],
        
        'kiralik' => [
            // Arsa kiralama (tarla vb.) için aynı mantık
            'oda-sayisi', 'banyo-sayisi', 'salon-sayisi', 'balkon-sayisi',
            'ankastre', 'klima', 'asansor', 'havuz', 'fitness',
            
            // Satış özellikleri (kiralamada yok)
            'tapu-durumu', 'ipotek', 'kredi-uygun',
        ],
    ],

    /**
     * Yazlık/Villa kategorileri için yayın tipi bazlı yasaklı özellikler
     * 
     * MANTIK: Yazlık kiralama için arsa/inşaat özellikleri gereksiz
     */
    'yazlik-kiralama' => [
        'gunluk' => [
            // Arsa Özellikleri
            'imar-durumu', 'kaks', 'taks', 'gabari', 'emsal',
            'ada-no', 'parsel-no', 'tapu-durumu',
            
            // İnşaat Teknikleri
            'radye-temel', 'tunel-kalip', 'prekast', 'betonarme',
            'yalitim-tipi', 'cati-izolasyonu',
            
            // Satış Özellikleri
            'ipotek', 'kredi-uygun', 'takas', 'devren-satilik',
        ],
        
        'haftalik' => [
            // Gunluk ile aynı
            'imar-durumu', 'kaks', 'taks', 'gabari', 'emsal',
            'ada-no', 'parsel-no', 'tapu-durumu',
            'radye-temel', 'tunel-kalip', 'prekast', 'betonarme',
            'ipotek', 'kredi-uygun', 'takas',
        ],
        
        'aylik' => [
            // Aylık kiralama için daha az kısıtlama
            'imar-durumu', 'kaks', 'taks', 'ada-no', 'parsel-no',
            'radye-temel', 'tunel-kalip',
        ],
    ],

    /**
     * Konut kategorileri için yayın tipi bazlı yasaklı özellikler
     * 
     * MANTIK: Satılık/Kiralık/Günlük farklı özellikler gerektirir
     */
    'konut' => [
        'satilik' => [
            // Kiralama Özellikleri
            'gunluk-fiyat', 'haftalik-fiyat', 'aylik-kira',
            'depozito', 'aidat-dahil', 'kira-artisi',
            'min-konaklama', 'check-in', 'check-out',
            'temizlik-ucreti', 'ekstra-misafir-ucreti',
            
            // Arsa Özellikleri (konut için gereksiz)
            'kaks', 'taks', 'gabari', 'emsal', 'imar-durumu',
        ],
        
        'kiralik' => [
            // Satış Özellikleri
            'tapu-durumu', 'ipotek', 'kredi-uygun', 'takas', 'devren-satilik',
            
            // Günlük Kiralama
            'gunluk-fiyat', 'haftalik-fiyat', 'sezonluk-fiyat',
            'min-konaklama', 'check-in', 'check-out',
            
            // Arsa Özellikleri
            'kaks', 'taks', 'gabari', 'imar-durumu', 'ada-no', 'parsel-no',
        ],
        
        'gunluk-kiralik' => [
            // Satış Özellikleri
            'tapu-durumu', 'ipotek', 'kredi-uygun', 'takas',
            
            // Uzun Dönem Kiralama
            'aidat-dahil', 'kira-artisi', 'senet-sayisi',
            
            // Arsa Özellikleri
            'kaks', 'taks', 'imar-durumu', 'ada-no', 'parsel-no',
        ],
    ],

    /**
     * İşyeri kategorileri için yayın tipi bazlı yasaklı özellikler
     */
    'isyeri' => [
        'satilik' => [
            // Kiralama
            'gunluk-fiyat', 'aylik-kira', 'depozito', 'aidat-dahil',
            
            // Konut Özellikleri
            'oda-sayisi', 'banyo-sayisi', 'balkon-sayisi',
            'mutfak', 'ankastre', 'ebeveyn-banyosu',
            'denize-mesafe', 'plaj', 'havuz-kullanim',
        ],
        
        'kiralik' => [
            // Satış
            'tapu-durumu', 'ipotek', 'kredi-uygun', 'takas',
            
            // Konut Özellikleri
            'oda-sayisi', 'banyo-sayisi', 'balkon-sayisi',
            'mutfak', 'ankastre', 'denize-mesafe',
        ],
        
        'devren-satilik' => [
            // Sadece işyeri devir için özel
            // Konut ve arsa özellikleri hariç
            'oda-sayisi', 'banyo-sayisi', 'balkon-sayisi',
            'imar-durumu', 'kaks', 'taks', 'ada-no',
            'gunluk-fiyat', 'haftalik-fiyat',
        ],
    ],

    /**
     * Ofis kategorileri için yayın tipi bazlı yasaklı özellikler
     */
    'ofis' => [
        'satilik' => [
            // Kiralama
            'gunluk-fiyat', 'aylik-kira', 'depozito',
            
            // Konut Özellikleri
            'oda-sayisi', 'banyo-sayisi', 'mutfak', 'ankastre',
            'balkon-sayisi', 'ebeveyn-banyosu',
            'denize-mesafe', 'plaj', 'havuz',
        ],
        
        'kiralik' => [
            // Satış
            'tapu-durumu', 'ipotek', 'kredi-uygun',
            
            // Konut
            'oda-sayisi', 'banyo-sayisi', 'mutfak', 'ankastre',
            'denize-mesafe', 'plaj',
        ],
    ],

    /**
     * Dükkan kategorileri için yayın tipi bazlı yasaklı özellikler
     */
    'dukkan' => [
        'satilik' => [
            'gunluk-fiyat', 'aylik-kira', 'depozito',
            'oda-sayisi', 'banyo-sayisi', 'balkon-sayisi',
            'ankastre', 'mutfak', 'ebeveyn-banyosu',
            'imar-durumu', 'kaks', 'taks',
        ],
        
        'kiralik' => [
            'tapu-durumu', 'ipotek', 'kredi-uygun',
            'oda-sayisi', 'banyo-sayisi', 'balkon-sayisi',
            'imar-durumu', 'kaks', 'taks',
        ],
    ],

    /**
     * Bina kategorileri için yayın tipi bazlı yasaklı özellikler
     */
    'bina' => [
        'satilik' => [
            'gunluk-fiyat', 'aylik-kira', 'depozito',
            // Daire özellikleri (bina tüm yapı)
            'oda-sayisi', 'banyo-sayisi', 'balkon-sayisi',
            'ankastre', 'mutfak', 'klima',
        ],
        
        'kiralik' => [
            'tapu-durumu', 'ipotek', 'kredi-uygun',
            'oda-sayisi', 'banyo-sayisi', 'balkon-sayisi',
        ],
    ],

    /**
     * Fallback: Tanımlanmamış kategori/yayın tipi kombinasyonları için boş array
     * Bu durumda hiçbir özellik yasaklanmaz (güvenli varsayılan)
     */
    'default' => [],
];
