<?php

namespace Database\Factories;

use App\Models\Feature;
use App\Models\FeatureCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'feature_category_id' => FeatureCategory::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::random(5),
            'type' => 'text',
            'aktiflik_durumu' => true,
            'display_order' => 0,
        ];
    }
}
