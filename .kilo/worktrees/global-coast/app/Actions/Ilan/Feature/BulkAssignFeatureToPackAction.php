<?php

namespace App\Actions\Ilan\Feature;

use App\Models\Feature;
use App\Models\FeaturePack;

class BulkAssignFeatureToPackAction
{
    public function handle(int $packId, array $featureIds): int
    {
        $pack = FeaturePack::findOrFail($packId);
        $features = Feature::whereIn('id', $featureIds)->get();

        $addedCount = 0;
        foreach ($features as $feature) {
            if ($pack->addFeature($feature)) {
                $addedCount++;
            }
        }

        return $addedCount;
    }
}
