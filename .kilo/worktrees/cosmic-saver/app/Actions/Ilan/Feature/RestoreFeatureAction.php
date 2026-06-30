<?php

namespace App\Actions\Ilan\Feature;

use App\Models\Feature;

class RestoreFeatureAction
{
    public function handle(int $id): Feature
    {
        $feature = Feature::withTrashed()->findOrFail($id);
        $feature->restore();
        $feature->aktiflik_durumu = true;
        $feature->save();

        return $feature;
    }
}
