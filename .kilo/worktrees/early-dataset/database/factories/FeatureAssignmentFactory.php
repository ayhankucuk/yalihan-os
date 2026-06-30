<?php

namespace Database\Factories;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeatureAssignmentFactory extends Factory
{
    protected $model = FeatureAssignment::class;

    public function definition(): array
    {
        return [
            'feature_id' => Feature::factory(),
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => YayinTipiSablonu::factory(),
            'is_required' => false,
            'is_visible' => true,
            'display_order' => 0,
            'conditional_logic' => [],
            'group_name' => 'Genel Özellikler',
        ];
    }
}
