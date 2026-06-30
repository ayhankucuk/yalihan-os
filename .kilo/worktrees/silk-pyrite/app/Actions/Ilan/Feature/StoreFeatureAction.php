<?php

namespace App\Actions\Ilan\Feature;

use App\Models\Feature;
use App\Helpers\FeatureCacheHelper;

class StoreFeatureAction
{
    public function handle(array $data): Feature
    {
        $feature = Feature::create($data);

        FeatureCacheHelper::clearCategoryCache();

        return $feature;
    }
}
