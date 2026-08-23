<?php

declare(strict_types=1);

namespace Database\Factories\Settlement;

use App\Enums\BankTransactionMatchStatus;
use App\Models\Settlement\BankTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankTransaction>
 */
class BankTransactionFactory extends Factory
{
    protected $model = BankTransaction::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'bank_account_id' => null,
            'transaction_date' => Carbon::today(),
            'value_date' => Carbon::today(),
            'amount' => $this->faker->randomFloat(4, 100, 5000),
            'currency' => 'TRY',
            'debit_credit' => 'C',
            'reference_text' => $this->faker->sentence(4),
            'iban' => $this->faker->iban('TR'),
            'sender_name' => $this->faker->company(),
            'raw_payload' => ['source' => 'csv'],
            'source' => 'csv',
            'source_reference' => 'TX-' . $this->faker->unique()->uuid(),
            'match_status' => BankTransactionMatchStatus::UNMATCHED->value,
            'matched_settlement_id' => null,
            'reconciliation_execution_id' => null,
            'ingestion_status' => 'active',
            'idempotency_key' => null,
        ];
    }

    public function matched(): self
    {
        return $this->state(fn (array $attrs) => [
            'match_status' => BankTransactionMatchStatus::MATCHED->value,
        ]);
    }

    public function debit(): self
    {
        return $this->state(fn (array $attrs) => [
            'debit_credit' => 'D',
        ]);
    }
}
