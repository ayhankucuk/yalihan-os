<?php

namespace App\Actions\Ilan\Feature;

use App\Models\Feature;
use App\Helpers\FeatureCacheHelper;

class DestroyFeatureAction
{
    public function handle(Feature $feature): bool
    {
        $result = $feature->delete();

        FeatureCacheHelper::clearCategoryCache();

        return $result;
    }
}
