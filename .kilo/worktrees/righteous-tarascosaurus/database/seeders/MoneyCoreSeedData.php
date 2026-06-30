<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MoneyCoreSeedData extends Seeder
{
    public function run(): void
    {
        // --- Country Financial Rules ---
        $countries = [
            [
                'country_code'            => 'TR',
                'country_name'            => 'Türkiye',
                'rental_commission_rate'  => 0.0800, // 8%
                'sales_commission_rate'   => 0.0300, // 3%
                'advisory_fee_rate'       => 0.0000,
                'tax_rate'                => 0.0000,
                'default_currency'        => 'TRY',
                'aktiflik_durumu'         => true,
            ],
            [
                'country_code'            => 'GR',
                'country_name'            => 'Yunanistan',
                'rental_commission_rate'  => 0.0500, // 5%
                'sales_commission_rate'   => 0.0300, // 3%
                'advisory_fee_rate'       => 0.0200, // 2% Golden Visa advisory
                'tax_rate'                => 0.0000,
                'default_currency'        => 'EUR',
                'aktiflik_durumu'         => true,
            ],
            [
                'country_code'            => 'UK',
                'country_name'            => 'İngiltere',
                'rental_commission_rate'  => 0.0600, // 6%
                'sales_commission_rate'   => 0.0250, // 2.5%
                'advisory_fee_rate'       => 0.0000,
                'tax_rate'                => 0.0000,
                'default_currency'        => 'GBP',
                'aktiflik_durumu'         => true,
            ],
        ];

        foreach ($countries as $country) {
            DB::table('country_financial_rules')->updateOrInsert(
                ['country_code' => $country['country_code']],
                array_merge($country, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // --- FX Rates (TRY base) ---
        $rates = [
            ['from_currency' => 'TRY', 'to_currency' => 'EUR', 'rate' => 0.029000, 'effective_at' => now()],
            ['from_currency' => 'TRY', 'to_currency' => 'GBP', 'rate' => 0.025000, 'effective_at' => now()],
            ['from_currency' => 'TRY', 'to_currency' => 'USD', 'rate' => 0.031000, 'effective_at' => now()],
        ];

        foreach ($rates as $rate) {
            DB::table('fx_rates')->updateOrInsert(
                ['from_currency' => $rate['from_currency'], 'to_currency' => $rate['to_currency']],
                array_merge($rate, ['aktiflik_durumu' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        $this->command?->info("✅ Money Core seed data inserted: 3 country rules + 3 FX rates.");
    }
}
