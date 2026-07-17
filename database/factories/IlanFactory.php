<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ilan;
use App\Models\Property;
use App\Models\User;
use App\Models\IlanKategori;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for Ilan (Listing) model.
 *
 * Sprint 12B: Automatically creates associated Property for each Ilan.
 * This satisfies the domain invariant: "Every Listing must have a Property."
 *
 * Strategy: Use afterMaking to create Property BEFORE Ilan is saved.
 * This ensures property_id is available when the creating event fires.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ilan>
 */
class IlanFactory extends Factory
{
    protected $model = Ilan::class;

    public function definition(): array
    {
        $baslik = $this->faker->sentence(5);

        return [
            'baslik' => $baslik,
            'slug' => Str::slug($baslik . '-' . uniqid()),
            'fiyat' => $this->faker->randomFloat(2, 100000, 10000000),
            'para_birimi' => 'TL',
            'referans_no' => 'REF-' . uniqid(),
            'yayin_durumu' => 'yayinda',
            'danisman_id' => User::factory(),
            'ana_kategori_id' => IlanKategori::factory(),
            'alt_kategori_id' => null,
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'yayin_tipi_id' => null,
            'brut_m2' => $this->faker->numberBetween(50, 500),
            'net_m2' => $this->faker->numberBetween(40, 450),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Configure the model factory.
     *
     * Sprint 12B: Create Property BEFORE Ilan is saved (afterMaking).
     * This ensures property_id is available when the creating event fires.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Ilan $ilan) {
            // Skip if property_id already set
            if ($ilan->property_id !== null) {
                return;
            }

            // Skip workspace_id guard for factory-created properties
            Property::$skipWorkspaceIdGuard = true;

            // Create Property first
            $property = Property::factory()->create([
                'tenant_id' => $ilan->tenant_id ?? 1,
                'canonical_reference' => 'factory-' . uniqid(),
            ]);

            // Reset guard
            Property::$skipWorkspaceIdGuard = false;

            // Set property_id BEFORE save (before creating event)
            $ilan->property_id = $property->id;
        });
    }

    /**
     * Create with a specific Property.
     */
    public function withProperty(Property $property): static
    {
        return $this->state(fn (array $attributes) => [
            'property_id' => $property->id,
        ]);
    }

    /**
     * Create without a Property (for legacy tests).
     * WARNING: This will fail if the property_id guard is active.
     */
    public function withoutProperty(): static
    {
        return $this->state(fn (array $attributes) => [
            'property_id' => null,
        ]);
    }
}
