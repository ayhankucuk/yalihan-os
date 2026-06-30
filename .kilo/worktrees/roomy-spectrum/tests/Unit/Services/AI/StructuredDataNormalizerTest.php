<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\StructuredDataNormalizer;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 * Ghost class: App\Services\AI\StructuredDataNormalizer henüz implement edilmedi.
 */
class StructuredDataNormalizerTest extends TestCase
{
    protected StructuredDataNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new StructuredDataNormalizer();
    }

    public function test_normalizes_location_data()
    {
        $data = [
            'lokasyon' => [
                'il' => '  Muğla  ',
                'ilce' => 'Bodrum',
                'mahalle' => '',
            ],
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertEquals('Muğla', $result['lokasyon']['il']);
        $this->assertEquals('Bodrum', $result['lokasyon']['ilce']);
        $this->assertArrayNotHasKey('mahalle', $result['lokasyon']);
    }

    public function test_normalizes_konut_tipi()
    {
        $data = [
            'konut_tipi' => '  Villa  ',
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertEquals('Villa', $result['konut_tipi']);
    }

    public function test_normalizes_capacity_to_integer()
    {
        $data = [
            'kapasite' => [
                'kisi_kapasitesi' => '8',
                'yatak_odasi' => 4,
                'banyo' => '3',
            ],
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertIsInt($result['kapasite']['kisi_kapasitesi']);
        $this->assertEquals(8, $result['kapasite']['kisi_kapasitesi']);
        $this->assertEquals(4, $result['kapasite']['yatak_odasi']);
    }

    public function test_normalizes_boolean_fields()
    {
        $data = [
            'havuz_deniz' => [
                'ozel_havuz' => 'true',
                'denize_sifir' => 1,
                'ozel_plaj' => false,
            ],
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertTrue($result['havuz_deniz']['ozel_havuz']);
        $this->assertTrue($result['havuz_deniz']['denize_sifir']);
        $this->assertFalse($result['havuz_deniz']['ozel_plaj']);
    }

    public function test_normalizes_deniz_manzarasi_enum()
    {
        $data = [
            'havuz_deniz' => [
                'deniz_manzarasi' => 'TAM',
            ],
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertEquals('tam', $result['havuz_deniz']['deniz_manzarasi']);
    }

    public function test_removes_invalid_deniz_manzarasi()
    {
        $data = [
            'havuz_deniz' => [
                'deniz_manzarasi' => 'invalid_value',
            ],
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertArrayNotHasKey('deniz_manzarasi', $result['havuz_deniz'] ?? []);
    }

    public function test_normalizes_distance_to_float()
    {
        $data = [
            'mesafe' => [
                'havalimani' => '35.5',
                'market' => 2,
            ],
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertIsFloat($result['mesafe']['havalimani']);
        $this->assertEquals(35.5, $result['mesafe']['havalimani']);
        $this->assertIsFloat($result['mesafe']['market']);
    }

    public function test_ignores_unknown_keys()
    {
        $data = [
            'lokasyon' => ['il' => 'Muğla'],
            'unknown_key' => 'value',
            'aktiflik_durumu' => 'aktif',
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertArrayNotHasKey('unknown_key', $result);
        $this->assertArrayNotHasKey('aktiflik_durumu', $result);
    }

    public function test_handles_empty_strings_as_null()
    {
        $data = [
            'lokasyon' => [
                'il' => '',
                'ilce' => 'Bodrum',
            ],
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertArrayHasKey('lokasyon', $result);
        $this->assertArrayNotHasKey('il', $result['lokasyon']);
        $this->assertEquals('Bodrum', $result['lokasyon']['ilce']);
    }

    public function test_handles_null_values()
    {
        $data = [
            'lokasyon' => [
                'il' => null,
                'ilce' => 'Bodrum',
            ],
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertArrayHasKey('lokasyon', $result);
        $this->assertArrayNotHasKey('il', $result['lokasyon']);
        $this->assertEquals('Bodrum', $result['lokasyon']['ilce']);
    }
}
