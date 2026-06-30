<?php

namespace Tests\Unit\Services\Yazlik;

use App\Services\Yazlik\StructuredDataMapper;
use Tests\TestCase;

class StructuredDataMapperTest extends TestCase
{
    protected StructuredDataMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new StructuredDataMapper();
    }

    public function test_map_from_wizard_input_maps_location(): void
    {
        $input = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
                'mahalle_id' => 3,
                'adres' => 'Test Adres',
                'lat' => 36.123456,
                'lng' => 29.654321,
            ],
        ];

        $result = $this->mapper->mapFromWizardInput($input);

        $this->assertArrayHasKey('lokasyon', $result);
        $this->assertEquals(1, $result['lokasyon']['il_id']);
        $this->assertEquals(2, $result['lokasyon']['ilce_id']);
        $this->assertEquals(3, $result['lokasyon']['mahalle_id']);
        $this->assertEquals('Test Adres', $result['lokasyon']['adres']);
        $this->assertEquals(36.123456, $result['lokasyon']['lat']);
        $this->assertEquals(29.654321, $result['lokasyon']['lng']);
    }

    public function test_map_from_wizard_input_maps_capacity(): void
    {
        $input = [
            'kapasite' => [
                'max_misafir' => 8,
                'min_konaklama' => 3,
            ],
        ];

        $result = $this->mapper->mapFromWizardInput($input);

        $this->assertArrayHasKey('kapasite', $result);
        $this->assertEquals(8, $result['kapasite']['max_misafir']);
        $this->assertEquals(3, $result['kapasite']['min_konaklama']);
    }

    public function test_compute_etiketler_generates_ozel_havuz(): void
    {
        $structuredData = [
            'havuz_deniz' => [
                'havuz' => true,
                'havuz_turu' => 'ozel',
            ],
        ];

        $etiketler = $this->mapper->computeEtiketler($structuredData);

        $this->assertContains('ozel_havuz', $etiketler);
    }

    public function test_compute_etiketler_generates_denize_sifir(): void
    {
        $structuredData = [
            'havuz_deniz' => [
                'denize_sifir' => true,
            ],
        ];

        $etiketler = $this->mapper->computeEtiketler($structuredData);

        $this->assertContains('denize_sifir', $etiketler);
    }

    public function test_compute_etiketler_generates_kalabalik_aileye_uygun(): void
    {
        $structuredData = [
            'kapasite' => [
                'max_misafir' => 10,
            ],
        ];

        $etiketler = $this->mapper->computeEtiketler($structuredData);

        $this->assertContains('kalabalik_aileye_uygun', $etiketler);
    }

    public function test_mapper_does_not_generate_missing_data(): void
    {
        $input = [
            'konut_tipi' => 'villa',
        ];

        $result = $this->mapper->mapFromWizardInput($input);

        $this->assertArrayNotHasKey('kapasite', $result);
        $this->assertArrayNotHasKey('havuz_deniz', $result);
    }
}
