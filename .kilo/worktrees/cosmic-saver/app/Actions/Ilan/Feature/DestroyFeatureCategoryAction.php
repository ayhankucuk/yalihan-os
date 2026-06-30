<?php

namespace App\Actions\Ilan\Feature;

use App\Models\FeatureCategory;
use App\Helpers\FeatureCacheHelper;

class DestroyFeatureCategoryAction
{
    public function handle(FeatureCategory $category): bool
    {
        $result = $category->delete();

        FeatureCacheHelper::clearCategoryCache();

        return $result;
    }
}
