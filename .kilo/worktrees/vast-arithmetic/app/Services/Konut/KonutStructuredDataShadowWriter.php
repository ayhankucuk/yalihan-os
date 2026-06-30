<?php

namespace App\Services\Konut;

use App\Models\Ilan;
use App\Models\Feature;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Arr;

class KonutStructuredDataShadowWriter
{
    /**
     * Map structured data fields to feature slugs
     * Key: structured_data field (supports dot notation)
     * Value: feature slug
     */
    protected const MAPPING = [
        // Dimensions
        'brut_m2' => 'brut-alan',
        'net_m2' => 'net-alan',
        'alan_m2' => 'alan-m2',
        
        // Structure
        'bina_yasi' => 'yapim-yili', 
        'kat' => 'bulundugu-kat',
        'toplam_kat' => 'kat-sayisi',
        
        // Status (Nested in tapu_imar)
        'tapu_imar.imar_durumu' => 'imar-durumu',
        'tapu_imar.tapu_durumu' => 'tapu-durumu',
        'tapu_imar.krediye_uygun' => 'krediye-uygun',
        'tapu_imar.takas' => 'takas',
        
        // Rooms
        'oda_sayisi' => 'oda-sayisi',
        'salon_sayisi' => 'salon-sayisi',
        'banyo_sayisi' => 'banyo-sayisi',
        
        // Heating/Energy (Nested in enerji)
        'enerji.isitma_tipi' => 'isitma-tipi',
        'enerji.yakit_tipi' => 'yakit-tipi',
    ];

    /**
     * Shadow write structured data to feature assignments
     * 
     * @param Ilan $ilan
     * @param array $structuredData
     * @return array Result metrics
     */
    public function shadowWrite(Ilan $ilan, array $structuredData): array
    {
        $metrics = [
            'mapped_count' => 0,
            'written_count' => 0,
            'features_found' => 0,
            'features_missing' => 0,
        ];

        try {
            // 1. Identify valid features from mapping
            $slugs = array_values(self::MAPPING);
            $features = Feature::whereIn('slug', $slugs)->pluck('id', 'slug');
            
            $syncData = [];
            
            // Flatten structured data to handle dot notation keys
            $flattenedData = Arr::dot($structuredData);
            
            foreach ($flattenedData as $key => $value) {
                // Skip empty values
                if ($value === null || $value === '') {
                    continue;
                }

                // Check mapping
                if (!isset(self::MAPPING[$key])) {
                    continue;
                }

                $targetSlug = self::MAPPING[$key];
                
                // Check if feature exists in DB
                if (!isset($features[$targetSlug])) {
                    $metrics['features_missing']++;
                    continue;
                }

                $featureId = $features[$targetSlug];
                
                // Prepare sync data (Context7: store value in pivot)
                $syncData[$featureId] = [
                    'value' => (string) $value, // Cast to string for flexibility
                ];
                
                $metrics['mapped_count']++;
            }

            if (empty($syncData)) {
                return $metrics;
            }

            // 2. Perform Shadow Write (Sync without detaching to preserve other features)
            $ilan->features()->syncWithoutDetaching($syncData);
            
            $metrics['written_count'] = count($syncData);
            $metrics['features_found'] = count($features);

            LogService::info('Konut Shadow Write Success', [
                'ilan_id' => $ilan->id,
                'metrics' => $metrics
            ]);

        } catch (\Exception $e) {
            LogService::error('Konut Shadow Write Failed', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage()
            ]);
            // Do not rethrow - shadow write should be non-blocking
        }

        return $metrics;
    }
}
