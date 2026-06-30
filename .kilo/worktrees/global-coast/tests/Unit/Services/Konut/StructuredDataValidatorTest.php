<?php

namespace Tests\Unit\Services\Konut;

use App\Services\Konut\StructuredDataValidator;
use Tests\TestCase;

class StructuredDataValidatorTest extends TestCase
{
    protected StructuredDataValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new StructuredDataValidator();
    }

    public function test_validate_returns_false_when_required_fields_missing(): void
    {
        $data = [
            'konut_tipi' => 'villa',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_validate_returns_true_when_all_required_fields_present(): void
    {
        $data = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
            ],
            'konut_tipi' => 'villa',
            'oda_sayisi' => 3,
            'salon_sayisi' => 1,
            'brut_m2' => 150.5,
            'banyo_sayisi' => 2,
            'fiyat' => [
                'satilik_fiyat' => 1000000,
                'para_birimi' => 'TRY',
            ],
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_returns_error_when_kat_greater_than_toplam_kat(): void
    {
        $data = [
            'lokasyon' => ['il_id' => 1, 'ilce_id' => 2],
            'konut_tipi' => 'villa',
            'oda_sayisi' => 3,
            'salon_sayisi' => 1,
            'brut_m2' => 150.5,
            'banyo_sayisi' => 2,
            'kat' => 5,
            'toplam_kat' => 3,
            'fiyat' => ['satilik_fiyat' => 1000000, 'para_birimi' => 'TRY'],
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_validate_returns_error_when_net_m2_greater_than_brut_m2(): void
    {
        $data = [
            'lokasyon' => ['il_id' => 1, 'ilce_id' => 2],
            'konut_tipi' => 'villa',
            'oda_sayisi' => 3,
            'salon_sayisi' => 1,
            'brut_m2' => 100,
            'net_m2' => 150,
            'banyo_sayisi' => 2,
            'fiyat' => ['satilik_fiyat' => 1000000, 'para_birimi' => 'TRY'],
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_validate_returns_error_when_krediye_uygun_true_but_tapu_durumu_missing(): void
    {
        $data = [
            'lokasyon' => ['il_id' => 1, 'ilce_id' => 2],
            'konut_tipi' => 'villa',
            'oda_sayisi' => 3,
            'salon_sayisi' => 1,
            'brut_m2' => 150.5,
            'banyo_sayisi' => 2,
            'tapu_imar' => [
                'krediye_uygun' => true,
            ],
            'fiyat' => ['satilik_fiyat' => 1000000, 'para_birimi' => 'TRY'],
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_validate_returns_error_when_havuz_turu_set_but_havuz_false(): void
    {
        $data = [
            'lokasyon' => ['il_id' => 1, 'ilce_id' => 2],
            'konut_tipi' => 'villa',
            'oda_sayisi' => 3,
            'salon_sayisi' => 1,
            'brut_m2' => 150.5,
            'banyo_sayisi' => 2,
            'dis_ozellikler' => [
                'havuz' => false,
                'havuz_turu' => 'ozel',
            ],
            'fiyat' => ['satilik_fiyat' => 1000000, 'para_birimi' => 'TRY'],
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }
}
