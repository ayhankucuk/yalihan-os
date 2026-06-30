<?php

namespace Database\Factories\Modules\TakimYonetimi\Models;

use App\Models\Kisi;
use App\Models\Lead;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Modules\TakimYonetimi\Models\Proje;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\TakimYonetimi\Models\Gorev>
 */
class GorevFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Gorev::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $durumlar = Gorev::getDurumlar();
        $oncelikler = Gorev::getOncelikler();
        $tipler = Gorev::getTipler();

        $baslangicTarihi = $this->faker->dateTimeBetween('-30 days', '+30 days');
        $bitisTarihi = $this->faker->dateTimeBetween($baslangicTarihi, '+60 days');

        return [
            'baslik' => $this->faker->sentence(4),
            'aciklama' => $this->faker->optional(0.7)->paragraph(),
            'oncelik' => $this->faker->randomElement($oncelikler),
            'atanan_user_id' => User::factory(),
            'olusturan_user_id' => User::factory(),
            'kisi_id' => null, // Optional - can be set explicitly
            'lead_id' => null, // Optional - can be set explicitly
            'proje_id' => null, // Optional - can be set explicitly
            'baslangic_tarihi' => $baslangicTarihi,
            'bitis_tarihi' => $bitisTarihi,
            'tamamlanma_yuzdesi' => $this->faker->numberBetween(0, 100),
            'notlar' => $this->faker->optional(0.5)->text(200),
            'gorev_durumu' => $this->faker->randomElement($durumlar),
            'gorev_tipi' => $this->faker->randomElement($tipler),
        ];
    }

    /**
     * Indicate that the task is assigned to a specific user.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'atanan_user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the task was created by a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'olusturan_user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the task is related to a specific customer.
     */
    public function forKisi(Kisi $kisi): static
    {
        return $this->state(fn (array $attributes) => [
            'kisi_id' => $kisi->id,
        ]);
    }

    /**
     * Indicate that the task is related to a specific lead.
     */
    public function forLead(Lead $lead): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_id' => $lead->id,
        ]);
    }

    /**
     * Indicate that the task is related to a specific project.
     */
    public function forProje(Proje $proje): static
    {
        return $this->state(fn (array $attributes) => [
            'proje_id' => $proje->id,
        ]);
    }

    /**
     * Indicate that the task is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'gorev_durumu' => 'bekliyor',
            'tamamlanma_yuzdesi' => 0,
        ]);
    }

    /**
     * Indicate that the task is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'gorev_durumu' => 'devam_ediyor',
            'tamamlanma_yuzdesi' => $this->faker->numberBetween(1, 99),
        ]);
    }

    /**
     * Indicate that the task is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'gorev_durumu' => 'tamamlandi',
            'tamamlanma_yuzdesi' => 100,
            'bitis_tarihi' => now(),
        ]);
    }

    /**
     * Indicate that the task is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'gorev_durumu' => 'iptal',
        ]);
    }

    /**
     * Indicate that the task has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'oncelik' => 'yuksek',
        ]);
    }

    /**
     * Indicate that the task is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'oncelik' => 'acil',
        ]);
    }

    /**
     * Indicate that the task is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'gorev_durumu' => 'devam_ediyor',
            'bitis_tarihi' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the task is due soon.
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'gorev_durumu' => 'devam_ediyor',
            'bitis_tarihi' => $this->faker->dateTimeBetween('now', '+2 days'),
        ]);
    }
}
