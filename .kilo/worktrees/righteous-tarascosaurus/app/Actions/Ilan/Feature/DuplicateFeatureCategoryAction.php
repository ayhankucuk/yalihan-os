<?php

namespace App\Actions\Ilan\Feature;

use App\Models\FeatureCategory;
use App\Helpers\FeatureCacheHelper;

class DuplicateFeatureCategoryAction
{
    public function handle(FeatureCategory $category): FeatureCategory
    {
        $new = $category->replicate();
        $new->name = $category->name . ' Kopya';
        $new->slug = null;
        $new->display_order = (int) (FeatureCategory::max('display_order') + 1);
        $new->save();

        FeatureCacheHelper::clearCategoryCache();

        return $new;
    }
}
