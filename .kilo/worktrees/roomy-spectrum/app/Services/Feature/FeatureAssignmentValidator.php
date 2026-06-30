<?php

namespace App\Services\Feature;

use App\Models\Feature;
use App\Models\YayinTipiSablonu;

/**
 * Feature Assignment Validator Service
 *
 * Context7 Standardı: C7-FEATURE-ASSIGNMENT-VALIDATOR-2025-12-07
 *
 * Özellik atamalarını validate eder.
 * Mantıksız özellik atamalarını önler.
 *
 * @package App\Services\Feature
 */
class FeatureAssignmentValidator
{
    /**
     * Satılık için özellikler (kiralama tiplerinde olmamalı)
     *
     * @var array
     */
    private const FORBIDDEN_FOR_RENTAL = [
        'tapu',
        'tapu_statusu',
        'tapu_tipi',
        'krediye_uygun',
        'kredi',
        'satis_fiyati',
        'm2_fiyati',
        'm²_fiyati',
        'takas',
        'takasa_uygun',
        'kat_karsiligi',
        'ifrazsiz',
    ];

    /**
     * Kiralama için özellikler (satılık tiplerinde olmamalı)
     *
     * @var array
     */
    private const FORBIDDEN_FOR_SALE = [
        'gunluk_fiyat',
        'haftalik_fiyat',
        'aylik_fiyat',
        'sezonluk_fiyat',
        'min_konaklama',
        'max_misafir',
        'temizlik_ucreti',
        'check_in',
        'check_out',
        'sezon_baslangic',
        'sezon_bitis',
    ];

    /**
     * Özellik atamasını validate et
     *
     * @param Feature $feature
     * @param YayinTipiSablonu $yayinTipi
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validate(Feature $feature, YayinTipiSablonu $yayinTipi): array
    {
        $featureSlug = mb_strtolower($feature->slug ?? '', 'UTF-8');
        $featureName = mb_strtolower($feature->name ?? '', 'UTF-8');
        $yayinTipiName = mb_strtolower($yayinTipi->ad ?? '', 'UTF-8');

        // Kiralama tipleri kontrolü
        if ($this->isRentalType($yayinTipiName)) {
            foreach (self::FORBIDDEN_FOR_RENTAL as $forbidden) {
                $forbiddenLower = mb_strtolower($forbidden, 'UTF-8');
                if (strpos($featureSlug, $forbiddenLower) !== false ||
                    strpos($featureName, $forbiddenLower) !== false) {
                    return [
                        'valid' => false,
                        'message' => "Bu özellik ({$feature->name}) kiralama tipleri için uygun değil. Satılık için özellik.",
                    ];
                }
            }
        }

        // Satılık tipleri kontrolü
        if ($this->isSaleType($yayinTipiName)) {
            foreach (self::FORBIDDEN_FOR_SALE as $forbidden) {
                $forbiddenLower = mb_strtolower($forbidden, 'UTF-8');
                if (strpos($featureSlug, $forbiddenLower) !== false ||
                    strpos($featureName, $forbiddenLower) !== false) {
                    return [
                        'valid' => false,
                        'message' => "Bu özellik ({$feature->name}) satılık tipleri için uygun değil. Kiralama için özellik.",
                    ];
                }
            }
        }

        return [
            'valid' => true,
            'message' => 'Özellik ataması geçerli.',
        ];
    }

    /**
     * Kiralama tipi mi?
     *
     * @param string $yayinTipiName
     * @return bool
     */
    private function isRentalType(string $yayinTipiName): bool
    {
        $rentalTypes = [
            'kiralık',
            'kiralama',
            'günlük kiralama',
            'haftalık kiralama',
            'aylık kiralama',
            'sezonluk kiralama',
        ];

        $yayinTipiName = mb_strtolower($yayinTipiName, 'UTF-8');

        foreach ($rentalTypes as $type) {
            if (mb_strpos($yayinTipiName, $type) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Satılık tipi mi?
     *
     * @param string $yayinTipiName
     * @return bool
     */
    private function isSaleType(string $yayinTipiName): bool
    {
        $saleTypes = [
            'satılık',
            'devren satış',
            'kat karşılığı',
        ];

        $yayinTipiName = mb_strtolower($yayinTipiName, 'UTF-8');

        foreach ($saleTypes as $type) {
            if (mb_strpos($yayinTipiName, $type) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Toplu validate et
     *
     * @param array $featureIds
     * @param YayinTipiSablonu $yayinTipi
     * @return array ['valid' => bool, 'invalid_features' => array]
     */
    public function validateBatch(array $featureIds, YayinTipiSablonu $yayinTipi): array
    {
        $features = Feature::whereIn('id', $featureIds)->get();
        $invalidFeatures = [];

        foreach ($features as $feature) {
            $validation = $this->validate($feature, $yayinTipi);
            if (!$validation['valid']) {
                $invalidFeatures[] = [
                    'feature_id' => $feature->id,
                    'feature_name' => $feature->name,
                    'message' => $validation['message'],
                ];
            }
        }

        return [
            'valid' => empty($invalidFeatures),
            'invalid_features' => $invalidFeatures,
        ];
    }
}
