<?php

namespace Database\Factories;

use App\Models\Kisi;
use App\Models\KisiEtkilesim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KisiEtkilesim>
 */
class KisiEtkilesimFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = KisiEtkilesim::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipler = ['telefon', 'email', 'whatsapp', 'yuz_yuze', 'video_gorusme', 'sms', 'diger'];

        return [
            'kisi_id' => Kisi::factory(),
            'kullanici_id' => User::factory(),
            'tip' => $this->faker->randomElement($tipler),
            'notlar' => $this->faker->optional(0.8)->paragraph(),
            'etkilesim_tarihi' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'aktiflik_durumu' => $this->faker->boolean(90),
            'display_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the interaction is for a specific person.
     */
    public function forKisi(Kisi $kisi): static
    {
        return $this->state(fn (array $attributes) => [
            'kisi_id' => $kisi->id,
        ]);
    }

    /**
     * Indicate that the interaction was made by a specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'kullanici_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the interaction is a phone call.
     */
    public function phoneCall(): static
    {
        return $this->state(fn (array $attributes) => [
            'tip' => 'telefon',
        ]);
    }

    /**
     * Indicate that the interaction is an email.
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'tip' => 'email',
        ]);
    }

    /**
     * Indicate that the interaction is via WhatsApp.
     */
    public function whatsapp(): static
    {
        return $this->state(fn (array $attributes) => [
            'tip' => 'whatsapp',
        ]);
    }

    /**
     * Indicate that the interaction is face-to-face.
     */
    public function faceToFace(): static
    {
        return $this->state(fn (array $attributes) => [
            'tip' => 'yuz_yuze',
        ]);
    }

    /**
     * Indicate that the interaction is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'aktiflik_durumu' => true,
        ]);
    }

    /**
     * Indicate that the interaction is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'aktiflik_durumu' => false,
        ]);
    }

    /**
     * Indicate that the interaction happened recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'etkilesim_tarihi' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the interaction happened today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'etkilesim_tarihi' => now(),
        ]);
    }
}
