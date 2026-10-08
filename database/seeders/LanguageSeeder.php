<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Enums\AktiflikDurumu;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code' => 'tr',
                'name' => 'Türkçe',
                'aktiflik_durumu' => AktiflikDurumu::AKTIF,
                'varsayilan_durumu' => true,
                'is_rtl' => false,
                'display_order' => 1,
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'aktiflik_durumu' => AktiflikDurumu::AKTIF,
                'varsayilan_durumu' => false,
                'is_rtl' => false,
                'display_order' => 2,
            ],
            [
                'code' => 'ru',
                'name' => 'Русский',
                'aktiflik_durumu' => AktiflikDurumu::AKTIF,
                'varsayilan_durumu' => false,
                'is_rtl' => false,
                'display_order' => 3,
            ],
        ];

        foreach ($languages as $lang) {
            Language::updateOrCreate(
                ['code' => $lang['code']],
                $lang
            );
        }
    }
}
