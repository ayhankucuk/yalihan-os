<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Language;
use Illuminate\Database\Seeder;

class LocaleCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Languages
        $languages = [
            [
                'code'              => 'tr',
                'name'              => 'Türkçe',
                'aktiflik_durumu'   => true,
                'varsayilan_durumu' => true,
                'is_rtl'            => false,
                'display_order'     => 1,
            ],
            [
                'code'              => 'en',
                'name'              => 'English',
                'aktiflik_durumu'   => true,
                'varsayilan_durumu' => false,
                'is_rtl'            => false,
                'display_order'     => 2,
            ],
            [
                'code'              => 'ru',
                'name'              => 'Pусский',
                'aktiflik_durumu'   => true,
                'varsayilan_durumu' => false,
                'is_rtl'            => false,
                'display_order'     => 3,
            ],
            [
                'code'              => 'ar',
                'name'              => 'العربية',
                'aktiflik_durumu'   => false,
                'varsayilan_durumu' => false,
                'is_rtl'            => true,
                'display_order'     => 4,
            ],
            [
                'code'              => 'de',
                'name'              => 'Deutsch',
                'aktiflik_durumu'   => false,
                'varsayilan_durumu' => false,
                'is_rtl'            => false,
                'display_order'     => 5,
            ],
            [
                'code'              => 'fr',
                'name'              => 'Français',
                'aktiflik_durumu'   => false,
                'varsayilan_durumu' => false,
                'is_rtl'            => false,
                'display_order'     => 6,
            ],
        ];

        foreach ($languages as $lang) {
            Language::updateOrCreate(['code' => $lang['code']], $lang);
        }

        // 2. Currencies
        $currencies = [
            [
                'code'              => 'TRY',
                'symbol'            => '₺',
                'aktiflik_durumu'   => true,
                'varsayilan_durumu' => true,
                'decimal_precision' => 2,
                'display_order'     => 1,
            ],
            [
                'code'              => 'EUR',
                'symbol'            => '€',
                'aktiflik_durumu'   => true,
                'varsayilan_durumu' => false,
                'decimal_precision' => 2,
                'display_order'     => 2,
            ],
            [
                'code'              => 'GBP',
                'symbol'            => '£',
                'aktiflik_durumu'   => true,
                'varsayilan_durumu' => false,
                'decimal_precision' => 2,
                'display_order'     => 3,
            ],
            [
                'code'              => 'USD',
                'symbol'            => '$',
                'aktiflik_durumu'   => true,
                'varsayilan_durumu' => false,
                'decimal_precision' => 2,
                'display_order'     => 4,
            ],
        ];

        foreach ($currencies as $curr) {
            Currency::updateOrCreate(['code' => $curr['code']], $curr);
        }

        // 3. Cache Invalidation
        app(\App\Services\LocaleControlService::class)->clearCache();
    }
}
