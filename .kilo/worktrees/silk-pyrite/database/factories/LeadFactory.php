<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * LeadFactory - Social Media CRM Lead Test Data Generator
 *
 * Generates realistic lead data for testing CRM queue operations,
 * tenant isolation, and lead scoring workflows.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platforms = ['whatsapp', 'instagram', 'facebook', 'telegram'];
        $platform = $this->faker->randomElement($platforms);

        $intents = ['buy', 'rent', 'sell', 'inquiry', 'valuation'];
        $propertyTypes = ['apartment', 'villa', 'land', 'commercial', 'office'];

        return [
            // Contact Info
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),

            // Platform Info
            'platform' => $platform,
            'platform_user_id' => $this->faker->unique()->numerify('user_###########'),
            'platform_phone' => $this->faker->phoneNumber(),
            'platform_username' => $this->faker->userName(),

            // Property Interest
            'interested_location_id' => null, // Will be set by tests if needed
            'interested_property_type' => $this->faker->randomElement($propertyTypes),

            // Deal Details
            'budget_min' => $this->faker->numberBetween(100000, 500000),
            'budget_max' => $this->faker->numberBetween(500000, 2000000),
            'area_min' => $this->faker->numberBetween(50, 100),
            'area_max' => $this->faker->numberBetween(100, 300),
            'rooms' => $this->faker->numberBetween(1, 5),

            // Parsing Results
            'intent' => $this->faker->randomElement($intents),
            'confidence' => $this->faker->randomFloat(2, 0.50, 0.99),
            'entities' => [
                'location' => $this->faker->city(),
                'property_type' => $this->faker->randomElement($propertyTypes),
                'budget' => $this->faker->numberBetween(100000, 2000000),
            ],
            'first_message' => $this->faker->sentence(10),

            // CRM Durumu - Use Lead::CRM_* constants
            'crm_durumu' => Lead::CRM_NEW,
            'assigned_agent_id' => User::factory(),
            'last_contacted_at' => null,
            'follow_up_date' => null,
            'notes' => null,
            'aktif' => true,
            'tags' => [],
            'ulke_id' => 1, // Default Turkey, tests can override
            'sesli_onay_verildi' => false,
        ];
    }

    /**
     * Lead with high confidence score (>= 0.70)
     */
    public function highConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence' => $this->faker->randomFloat(2, 0.70, 0.99),
        ]);
    }

    /**
     * Lead with low confidence score (< 0.50)
     */
    public function lowConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence' => $this->faker->randomFloat(2, 0.20, 0.49),
        ]);
    }

    /**
     * Lead that has been contacted
     */
    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_durumu' => Lead::CRM_REACHED,
            'last_contacted_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Qualified lead (high confidence + contacted)
     */
    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_durumu' => Lead::CRM_QUALIFIED,
            'confidence' => $this->faker->randomFloat(2, 0.70, 0.99),
            'last_contacted_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'follow_up_date' => $this->faker->dateTimeBetween('now', '+7 days'),
        ]);
    }

    /**
     * Lost lead
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_durumu' => Lead::CRM_LOST,
            'aktif' => false,
            'notes' => 'Lead lost: ' . $this->faker->sentence(),
        ]);
    }

    /**
     * Won lead (converted to customer)
     */
    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_durumu' => Lead::CRM_WON,
            'aktif' => false,
            'notes' => 'Lead converted successfully',
        ]);
    }

    /**
     * Lead from specific platform
     */
    public function fromPlatform(string $platform): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
            'platform_user_id' => $this->faker->unique()->numerify($platform . '_###########'),
        ]);
    }

    /**
     * Lead with assigned agent (use existing user)
     */
    public function assignedTo(User $agent): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_agent_id' => $agent->id,
        ]);
    }

    /**
     * Lead with follow-up scheduled
     */
    public function withFollowUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'follow_up_date' => $this->faker->dateTimeBetween('now', '+14 days'),
            'notes' => 'Follow-up scheduled: ' . $this->faker->sentence(),
        ]);
    }

    /**
     * Active lead (not lost/won)
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_durumu' => $this->faker->randomElement([
                Lead::CRM_NEW,
                Lead::CRM_REACHED,
                Lead::CRM_QUALIFIED,
            ]),
            'aktif' => true,
        ]);
    }

    /**
     * Lead with specific intent
     */
    public function withIntent(string $intent): static
    {
        return $this->state(fn (array $attributes) => [
            'intent' => $intent,
        ]);
    }

    /**
     * Lead with specific country
     */
    public function forCountry(int $countryId): static
    {
        return $this->state(fn (array $attributes) => [
            'ulke_id' => $countryId,
        ]);
    }

    /**
     * Lead with voice consent
     */
    public function withVoiceConsent(): static
    {
        return $this->state(fn (array $attributes) => [
            'sesli_onay_verildi' => true,
        ]);
    }
}
