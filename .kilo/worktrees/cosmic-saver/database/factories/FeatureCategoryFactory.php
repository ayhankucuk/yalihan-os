<?php

namespace Database\Factories;

use App\Models\FeatureCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FeatureCategoryFactory extends Factory
{
    protected $model = FeatureCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::random(4),
            'aktiflik_durumu' => true,
            'display_order' => 0,
        ];
    }
}
