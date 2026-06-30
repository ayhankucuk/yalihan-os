<?php

namespace Tests\Unit\Services\Yazlik;

use App\Services\Yazlik\StructuredDataValidator;
use Tests\TestCase;

class StructuredDataValidatorTest extends TestCase
{
    protected StructuredDataValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new StructuredDataValidator();
    }

    public function test_validate_requires_il_id(): void
    {
        $data = [
            'lokasyon' => [
                'ilce_id' => 1,
            ],
            'konut_tipi' => 'villa',
            'kapasite' => [
                'max_misafir' => 8,
                'min_konaklama' => 3,
            ],
            'banyo' => [
                'banyo_sayisi' => 2,
            ],
        ];

        $result = $this->validator->validate($data, 'gunluk');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertTrue(collect($result['errors'])->contains(fn($e) => $e['field'] === 'lokasyon.il_id'));
    }

    public function test_validate_requires_gunluk_fiyat_for_gunluk(): void
    {
        $data = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
            ],
            'konut_tipi' => 'villa',
            'kapasite' => [
                'max_misafir' => 8,
                'min_konaklama' => 3,
            ],
            'banyo' => [
                'banyo_sayisi' => 2,
            ],
            'fiyatlandirma' => [],
        ];

        $result = $this->validator->validate($data, 'gunluk');

        $this->assertFalse($result['valid']);
        $this->assertTrue(collect($result['errors'])->contains(fn($e) => $e['field'] === 'fiyatlandirma.gunluk_fiyat'));
    }

    public function test_validate_requires_sezon_dates_for_sezonluk(): void
    {
        $data = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
            ],
            'konut_tipi' => 'villa',
            'kapasite' => [
                'max_misafir' => 8,
                'min_konaklama' => 3,
            ],
            'banyo' => [
                'banyo_sayisi' => 2,
            ],
            'fiyatlandirma' => [
                'gunluk_fiyat' => 1000,
            ],
        ];

        $result = $this->validator->validate($data, 'sezonluk');

        $this->assertFalse($result['valid']);
        $this->assertTrue(collect($result['errors'])->contains(fn($e) => $e['field'] === 'fiyatlandirma.sezon_baslangic'));
        $this->assertTrue(collect($result['errors'])->contains(fn($e) => $e['field'] === 'fiyatlandirma.sezon_bitis'));
    }

    public function test_validate_checks_havuz_turu_contradiction(): void
    {
        $data = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
            ],
            'konut_tipi' => 'villa',
            'kapasite' => [
                'max_misafir' => 8,
                'min_konaklama' => 3,
            ],
            'banyo' => [
                'banyo_sayisi' => 2,
            ],
            'havuz_deniz' => [
                'havuz' => false,
                'havuz_turu' => 'ozel',
            ],
            'fiyatlandirma' => [
                'gunluk_fiyat' => 1000,
            ],
        ];

        $result = $this->validator->validate($data, 'gunluk');

        $this->assertFalse($result['valid']);
        $this->assertTrue(collect($result['errors'])->contains(fn($e) => $e['field'] === 'havuz_deniz.havuz'));
    }

    public function test_validate_checks_denize_sifir_contradiction(): void
    {
        $data = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
            ],
            'konut_tipi' => 'villa',
            'kapasite' => [
                'max_misafir' => 8,
                'min_konaklama' => 3,
            ],
            'banyo' => [
                'banyo_sayisi' => 2,
            ],
            'havuz_deniz' => [
                'denize_sifir' => true,
                'denize_uzaklik' => 50,
            ],
            'fiyatlandirma' => [
                'gunluk_fiyat' => 1000,
            ],
        ];

        $result = $this->validator->validate($data, 'gunluk');

        $this->assertFalse($result['valid']);
        $this->assertTrue(collect($result['errors'])->contains(fn($e) => $e['field'] === 'havuz_deniz.denize_uzaklik'));
    }

    public function test_validate_checks_sezon_dates_order(): void
    {
        $data = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
            ],
            'konut_tipi' => 'villa',
            'kapasite' => [
                'max_misafir' => 8,
                'min_konaklama' => 3,
            ],
            'banyo' => [
                'banyo_sayisi' => 2,
            ],
            'fiyatlandirma' => [
                'gunluk_fiyat' => 1000,
                'sezon_baslangic' => '2026-06-01',
                'sezon_bitis' => '2026-05-01',
            ],
        ];

        $result = $this->validator->validate($data, 'sezonluk');

        $this->assertFalse($result['valid']);
        $this->assertTrue(collect($result['errors'])->contains(fn($e) => $e['field'] === 'fiyatlandirma.sezon_bitis'));
    }

    public function test_validate_passes_with_valid_data(): void
    {
        $data = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
            ],
            'konut_tipi' => 'villa',
            'kapasite' => [
                'max_misafir' => 8,
                'min_konaklama' => 3,
            ],
            'banyo' => [
                'banyo_sayisi' => 2,
            ],
            'fiyatlandirma' => [
                'gunluk_fiyat' => 1000,
            ],
        ];

        $result = $this->validator->validate($data, 'gunluk');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
}
