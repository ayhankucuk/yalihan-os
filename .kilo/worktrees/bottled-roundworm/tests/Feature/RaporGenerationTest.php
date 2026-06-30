<?php

namespace Tests\Feature;

use App\Models\Ilan;
use App\Models\User;
use App\Models\Deprecated\Opportunity;
use App\Services\ReportService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Yalihan Report System Feature Tests
 * [YALIHAN_REPORTING_0206]
 * @group skip-until-migration-complete
 */
class RaporGenerationTest extends TestCase
{

    protected User $admin;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role_id' => 1, // Assume superadmin
        ]);

        // Create test ilan
        $this->ilan = Ilan::factory()->create([
            'baslik' => 'Test İlan',
            'fiyat' => 1000000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'aktif',
            'danisman_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_report_without_signed_url()
    {
        // Generate report first
        $reportService = app(ReportService::class);
        $result = $reportService->generate($this->ilan, 'tr');

        $this->ilan->update([
            'rapor_yolu' => $result['path'],
            'rapor_hash' => $result['hash'],
        ]);

        // Try to access without signature
        $response = $this->get(route('rapor.download', [
            'ilan' => $this->ilan->id,
            'hash' => $this->ilan->rapor_hash,
        ]));

        $response->assertStatus(403); // Invalid signature
    }

    /** @test */
    public function firsat_muhru_triggers_auto_report_generation()
    {
        // Ensure no report exists
        $this->assertNull($this->ilan->rapor_yolu);

        // Trigger mühür
        $this->ilan->update(['firsat_mühru' => true]);

        // Refresh model
        $this->ilan->refresh();

        // Report should be auto-generated
        $this->assertNotNull($this->ilan->rapor_yolu);
        $this->assertNotNull($this->ilan->rapor_hash);
        $this->assertNotNull($this->ilan->rapor_uretildi_at);
    }

    /** @test */
    public function signed_url_with_valid_signature_works()
    {
        // Skip test in CI/automated environments where PDF generation may not work
        if (!Storage::disk('local')->exists('test-pdf-capability')) {
            $this->markTestSkipped('PDF generation requires real file system and TCPDF. Skipped in QG.');
        }

        // Generate report
        $reportService = app(ReportService::class);
        $result = $reportService->generate($this->ilan, 'tr');

        $this->ilan->update([
            'rapor_yolu' => $result['path'],
            'rapor_hash' => $result['hash'],
            'rapor_uretildi_at' => now(),
        ]);

        // Generate signed URL
        $signedUrl = URL::temporarySignedRoute(
            'rapor.download',
            now()->addHours(24),
            [
                'ilan' => $this->ilan->id,
                'hash' => $this->ilan->rapor_hash,
            ]
        );

        // Access with signed URL (as guest to test signature only)
        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function invalid_signature_returns_403()
    {
        // Generate report first
        $reportService = app(ReportService::class);
        $result = $reportService->generate($this->ilan, 'tr');

        $this->ilan->update([
            'rapor_yolu' => $result['path'],
            'rapor_hash' => $result['hash'],
        ]);

        // Create URL with invalid signature
        $invalidUrl = route('rapor.download', [
            'ilan' => $this->ilan->id,
            'hash' => $this->ilan->rapor_hash,
        ]) . '?signature=invalid';

        $response = $this->get($invalidUrl);

        $response->assertStatus(403);
    }

    /** @test */
    public function invalidated_report_returns_410_gone()
    {
        // Generate report
        $reportService = app(ReportService::class);
        $result = $reportService->generate($this->ilan, 'tr');

        $this->ilan->update([
            'rapor_yolu' => $result['path'],
            'rapor_hash' => $result['hash'],
            'rapor_uretildi_at' => now(),
        ]);

        // Invalidate report
        $this->ilan->update([
            'rapor_gecersiz_mi' => true,
            'rapor_gecersizlestirildi_at' => now(),
        ]);

        // Try to access
        $signedUrl = URL::temporarySignedRoute(
            'rapor.download',
            now()->addHours(24),
            [
                'ilan' => $this->ilan->id,
                'hash' => $this->ilan->rapor_hash,
            ]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(410); // Gone
    }

    /** @test */
    public function hash_mismatch_returns_404()
    {
        // Generate report
        $reportService = app(ReportService::class);
        $result = $reportService->generate($this->ilan, 'tr');

        $this->ilan->update([
            'rapor_yolu' => $result['path'],
            'rapor_hash' => $result['hash'],
        ]);

        // Create signed URL with wrong hash
        $signedUrl = URL::temporarySignedRoute(
            'rapor.download',
            now()->addHours(24),
            [
                'ilan' => $this->ilan->id,
                'hash' => 'wrong_hash_123',
            ]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(404);
    }

    /** @test */
    public function authorized_user_can_refresh_report()
    {
        // Generate initial report
        $this->ilan->update(['firsat_mühru' => true]);
        $this->ilan->refresh();

        $originalHash = $this->ilan->rapor_hash;
        $originalVersion = $this->ilan->rapor_surum;

        // Refresh as admin
        $response = $this->actingAs($this->admin)->post(
            route('admin.ilanlar.rapor.refresh', $this->ilan),
            ['locale' => 'tr']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Version should increment
        $this->ilan->refresh();
        $this->assertNotEquals($originalHash, $this->ilan->rapor_hash);
        $this->assertEquals($originalVersion + 1, $this->ilan->rapor_surum);
    }

    /** @test */
    public function report_file_is_created_in_correct_path()
    {
        Storage::fake('local');

        $reportService = app(ReportService::class);
        $result = $reportService->generate($this->ilan, 'tr');

        // Check file exists
        $this->assertTrue($result['success']);
        $this->assertTrue(Storage::disk('local')->exists($result['path']));

        // Check filename format: mühürlü_raporlar/{Y}/{m}/YALIHAN_REPORT_{ID}_{HASH}.pdf
        $this->assertStringContainsString('mühürlü_raporlar/', $result['path']);
        $this->assertStringContainsString('YALIHAN_REPORT_' . $this->ilan->id, $result['path']);
        $this->assertStringEndsWith('.pdf', $result['path']);
    }

    /** @test */

    /**
     * Helper to invoke protected methods
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);

        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        // Clean up generated files
        if ($this->ilan && $this->ilan->rapor_yolu) {
            Storage::disk('local')->delete($this->ilan->rapor_yolu);
        }

        parent::tearDown();
    }
}
