<?php

namespace App\Actions\Ilan\Feature;

use App\Models\Feature;
use App\Helpers\FeatureCacheHelper;

class UpdateFeatureAction
{
    public function handle(Feature $feature, array $data): Feature
    {
        $feature->update($data);

        FeatureCacheHelper::clearCategoryCache();

        return $feature->fresh();
    }
}
