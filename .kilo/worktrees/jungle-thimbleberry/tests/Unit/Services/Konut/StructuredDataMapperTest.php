<?php

namespace Tests\Unit\Services\Konut;

use App\Services\Konut\StructuredDataMapper;
use Tests\TestCase;

class StructuredDataMapperTest extends TestCase
{
    protected StructuredDataMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new StructuredDataMapper();
    }

    public function test_map_from_wizard_input_maps_basic_fields(): void
    {
        $payload = [
            'konut_tipi' => 'villa',
            'oda_sayisi' => 3,
            'salon_sayisi' => 1,
            'brut_m2' => 150.5,
            'banyo_sayisi' => 2,
        ];

        $result = $this->mapper->mapFromWizardInput($payload);

        $this->assertEquals('villa', $result['konut_tipi']);
        $this->assertEquals(3, $result['oda_sayisi']);
        $this->assertEquals(1, $result['salon_sayisi']);
        $this->assertEquals(150.5, $result['brut_m2']);
        $this->assertEquals(2, $result['banyo_sayisi']);
    }

    public function test_map_from_wizard_input_maps_location(): void
    {
        $payload = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
                'mahalle_id' => 3,
                'adres' => 'Test Adres',
                'merkez_mesafe' => 5,
            ],
        ];

        $result = $this->mapper->mapFromWizardInput($payload);

        $this->assertEquals(1, $result['lokasyon']['il_id']);
        $this->assertEquals(2, $result['lokasyon']['ilce_id']);
        $this->assertEquals(3, $result['lokasyon']['mahalle_id']);
        $this->assertEquals('Test Adres', $result['lokasyon']['adres']);
        $this->assertEquals(5, $result['lokasyon']['merkez_mesafe']);
    }

    public function test_compute_etiketler_generates_correct_tags(): void
    {
        $structuredData = [
            'tapu_imar' => ['krediye_uygun' => true],
            'bina' => ['site_icinde' => true, 'asansor' => true, 'guvenlik' => true],
            'dis_ozellikler' => ['otopark' => 'kapali'],
            'ic_ozellikler' => ['manzara' => 'deniz', 'esyali' => 'esyali'],
            'bina_yasi' => 3,
            'fiyat' => ['yatirimlik' => true],
            'enerji' => ['enerji_sinifi' => 'A'],
        ];

        $etiketler = $this->mapper->computeEtiketler($structuredData);

        $this->assertContains('krediye_uygun', $etiketler);
        $this->assertContains('site_icinde', $etiketler);
        $this->assertContains('asansor', $etiketler);
        $this->assertContains('otopark', $etiketler);
        $this->assertContains('deniz_manzarasi', $etiketler);
        $this->assertContains('yeni_bina', $etiketler);
        $this->assertContains('yatirimlik', $etiketler);
        $this->assertContains('esyali', $etiketler);
        $this->assertContains('enerji_sinifi_a', $etiketler);
    }

    public function test_map_from_wizard_input_handles_nested_structures(): void
    {
        $payload = [
            'ic_ozellikler' => [
                'esyali' => 'esyali',
                'klima' => true,
                'somine' => false,
            ],
            'fiyat' => [
                'satilik_fiyat' => 1000000,
                'para_birimi' => 'TRY',
                'aidat' => 500,
            ],
        ];

        $result = $this->mapper->mapFromWizardInput($payload);

        $this->assertEquals('esyali', $result['ic_ozellikler']['esyali']);
        $this->assertTrue($result['ic_ozellikler']['klima']);
        $this->assertFalse($result['ic_ozellikler']['somine']);
        $this->assertEquals(1000000, $result['fiyat']['satilik_fiyat']);
        $this->assertEquals('TRY', $result['fiyat']['para_birimi']);
        $this->assertEquals(500, $result['fiyat']['aidat']);
    }

    public function test_compute_etiketler_returns_empty_array_when_no_conditions_met(): void
    {
        $structuredData = [];

        $etiketler = $this->mapper->computeEtiketler($structuredData);

        $this->assertIsArray($etiketler);
        $this->assertEmpty($etiketler);
    }
}
