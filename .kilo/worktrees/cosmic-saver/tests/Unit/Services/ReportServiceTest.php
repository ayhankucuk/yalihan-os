<?php

namespace Tests\Unit\Services;

use App\Models\Ilan;
use App\Services\ReportService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ReportService Unit Tests
 * [YALIHAN_REPORTING_0206]
 *
 * Requires seeded ilan_kategorileri data and ilanlar FK constraints.
 * Excluded from standard CI quality gate.
 *
 * @group skip-until-migration-complete
 */
class ReportServiceTest extends TestCase
{

    protected ReportService $service;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReportService::class);

        $this->ilan = Ilan::factory()->create([
            'baslik' => 'Test İlan',
            'fiyat' => 500000,
            'para_birimi' => 'EUR',
            'aktiflik_durumu' => true,
        ]);
    }

    /** @test */
    public function build_data_returns_required_keys()
    {
        $data = $this->invokeMethod($this->service, 'buildData', [$this->ilan, 'tr']);

        // Check all required keys exist
        $this->assertArrayHasKey('ilan', $data);
        $this->assertArrayHasKey('baslik', $data);
        $this->assertArrayHasKey('ilan_id', $data);
        $this->assertArrayHasKey('kategori', $data);
        $this->assertArrayHasKey('lokasyon', $data);
        $this->assertArrayHasKey('fiyat', $data);
        $this->assertArrayHasKey('firsat', $data);
        $this->assertArrayHasKey('roi', $data);
        $this->assertArrayHasKey('coordinates', $data);
        $this->assertArrayHasKey('locale', $data);
        $this->assertArrayHasKey('rapor_tarihi', $data);
    }

    /** @test */
    public function build_data_formats_price_correctly()
    {
        $data = $this->invokeMethod($this->service, 'buildData', [$this->ilan, 'tr']);

        // Price should be formatted with thousands separator
        $this->assertEquals('500.000', $data['fiyat']);
        $this->assertEquals('EUR', $data['para_birimi']);
    }

    /** @test */
    public function build_data_handles_missing_relationships_gracefully()
    {
        // Create ilan without relationships
        $ilanMinimal = Ilan::factory()->create([
            'baslik' => 'Minimal İlan',
            'ana_kategori_id' => null,
            'alt_kategori_id' => null,
            'yayin_tipi_id' => null,
        ]);

        $data = $this->invokeMethod($this->service, 'buildData', [$ilanMinimal, 'tr']);

        // Should return "Kategorisiz" for missing data (as per Ilan::anaKategori default)
        $this->assertEquals('Kategorisiz', $data['kategori']);
        $this->assertEquals('-', $data['yayin_tipi']);
    }

    /** @test */
    public function store_pdf_creates_correct_filename_format()
    {
        Storage::fake('local');

        $pdfBinary = 'fake-pdf-content';

        $result = $this->invokeMethod(
            $this->service,
            'storePdf',
            [$this->ilan, $pdfBinary, 'tr']
        );

        // Check path format
        $this->assertStringContainsString('mühürlü_raporlar/', $result['path']);
        $this->assertStringContainsString(date('Y'), $result['path']);
        $this->assertStringContainsString(date('m'), $result['path']);

        // Check filename
        $this->assertStringContainsString('YALIHAN_REPORT_' . $this->ilan->id, $result['path']);
        $this->assertStringEndsWith('.pdf', $result['path']);

        // Check hash length (16 chars)
        $this->assertEquals(16, strlen($result['hash']));
    }

    /** @test */
    public function invalidate_sets_correct_flags()
    {
        // First generate a report
        $this->ilan->update([
            'rapor_yolu' => 'test/path.pdf',
            'rapor_hash' => 'test_hash',
        ]);

        $result = $this->service->invalidate($this->ilan);

        $this->assertTrue($result);
        $this->ilan->refresh();

        $this->assertTrue($this->ilan->rapor_gecersiz_mi);
        $this->assertNotNull($this->ilan->rapor_gecersizlestirildi_at);
    }

    /** @test */
    public function invalidate_returns_false_when_no_report_exists()
    {
        $ilanNoReport = Ilan::factory()->create(['rapor_yolu' => null]);

        $result = $this->service->invalidate($ilanNoReport);

        $this->assertFalse($result);
    }

    /** @test */
    public function regenerate_invalidates_old_and_increments_version()
    {
        Storage::fake('local');

        // Generate initial report
        $this->ilan->update([
            'rapor_yolu' => 'old/path.pdf',
            'rapor_hash' => 'old_hash',
            'rapor_surum' => 1,
        ]);

        $result = $this->service->regenerate($this->ilan, 'tr');

        $this->assertTrue($result['success']);

        // Old report should be invalidated
        $this->ilan->refresh();
        $this->assertTrue($this->ilan->rapor_gecersiz_mi);

        // Version should increment
        $this->assertEquals(2, $this->ilan->rapor_surum);
    }

    /** @test */
    public function generate_creates_pdf_binary()
    {
        Storage::fake('local');

        $result = $this->service->generate($this->ilan, 'tr');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertArrayHasKey('metadata', $result);

        /** @var \Illuminate\Support\Facades\Storage $storage */
        $storage = Storage::disk('local');
        $storage->assertExists($result['path']);
    }

    /** @test */
    public function generate_supports_english_locale()
    {
        Storage::fake('local');

        $result = $this->service->generate($this->ilan, 'en');

        $this->assertTrue($result['success']);
        $this->assertEquals('en', $result['metadata']['locale']);
    }

    /** @test */
    public function generate_falls_back_to_turkish_if_locale_invalid()
    {
        Storage::fake('local');

        // Try with invalid locale
        $result = $this->service->generate($this->ilan, 'invalid');

        // Should still succeed with Turkish fallback
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function build_address_string_handles_complete_address()
    {
        $this->markTestSkipped('Location models (Il, Ilce, Mahalle) need to be factory-created for this test');
        $this->ilan->il_id = 1;
        $this->ilan->ilce_id = 1;
        $this->ilan->mahalle_id = 1;
        $this->ilan->load(['il', 'ilce', 'mahalle']);

        $address = $this->invokeMethod($this->service, 'buildAddressString', [$this->ilan]);

        $this->assertNotEquals('-', $address);
        $this->assertStringContainsString(',', $address); // Should have separators
    }

    /** @test */
    public function build_address_string_returns_dash_when_empty()
    {
        $ilanNoAddress = Ilan::factory()->create([
            'il_id' => null,
            'ilce_id' => null,
            'mahalle_id' => null,
        ]);

        $address = $this->invokeMethod($this->service, 'buildAddressString', [$ilanNoAddress]);

        $this->assertEquals('-', $address);
    }

    /**
     * Helper to invoke protected methods
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
