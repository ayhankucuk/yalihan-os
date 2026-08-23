<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'bank_name' => $this->faker->randomElement(['Garanti', 'Akbank', 'İş Bankası', 'QNB Finansbank', 'Yapı Kredi']),
            'account_name' => $this->faker->company() . ' İşletme Hesabı',
            'iban' => $this->faker->iban('TR'),
            'account_number' => $this->faker->numerify('##########'),
            'currency' => 'TRY',
            'account_type' => 'checking',
            'is_active' => true,
            'source' => 'manual',
            'metadata' => null,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attrs) => ['is_active' => false]);
    }

    public function eur(): self
    {
        return $this->state(fn (array $attrs) => ['currency' => 'EUR']);
    }
}
