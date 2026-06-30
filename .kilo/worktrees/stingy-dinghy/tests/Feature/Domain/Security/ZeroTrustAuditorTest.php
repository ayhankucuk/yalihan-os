<?php

namespace Tests\Feature\Domain\Security;

use Tests\TestCase;
use App\Domain\Core\Security\ZeroTrustAuditor;
use App\Domain\Ilan\IlanDomainYonetici;
use App\Domain\Kisi\KisiDomainYonetici;
use App\Domain\CQRS\Messaging\EventDispatcher;
use App\Services\Ilan\IlanCrudService;
use App\Exceptions\Governance\TenantMismatchException;
use App\Models\User;
use App\Models\Ilan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Database\Seeders\TenantBaselineSeeder;

/**
 * Class ZeroTrustAuditorTest
 * @package Tests\Feature\Domain\Security
 * @description Phase 19: Zero-Trust Auditing and Forensics Ledger integration tests.
 */
class ZeroTrustAuditorTest extends TestCase
{
    use RefreshDatabase;

    private ZeroTrustAuditor $auditor;
    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed multi-tenant baseline
        $this->seed(TenantBaselineSeeder::class);

        $this->auditor = new ZeroTrustAuditor();
    }

    /**
     * @test
     */
    public function it_successfully_logs_forensics_anomaly_to_governance_incidents(): void
    {
        $meta = [
            'source' => 'TestRunner',
            'action' => 'run_leak_test',
            'record_id' => 888,
            'severity' => 'CRITICAL',
            'payload' => ['tamper' => 'attempt']
        ];

        $success = $this->auditor->logForensicsAnomaly('CROSS_TENANT_MUTATION_ATTEMPT', $this->tenantId, $meta);

        $this->assertTrue($success);

        // Verify database entry in governance_incidents
        $incident = DB::table('governance_incidents')
            ->where('tenant_id', (string) $this->tenantId)
            ->where('olay_tipi', 'CROSS_TENANT_MUTATION_ATTEMPT')
            ->first();

        $this->assertNotNull($incident);
        $this->assertEquals('TestRunner', $incident->kaynak);
        $this->assertEquals('CRITICAL', $incident->risk_seviyesi);
        $this->assertNotEmpty($incident->imza_hash);

        // Verify cryptographic SHA-256 hash matching details payload
        $expectedStringToHash = 'CROSS_TENANT_MUTATION_ATTEMPT|' . $this->tenantId . '|' . json_encode($meta, JSON_UNESCAPED_UNICODE);
        $expectedHash = hash('sha256', $expectedStringToHash);
        $this->assertEquals($expectedHash, $incident->imza_hash);

        // Verify JSON details block
        $details = json_decode($incident->details, true);
        $this->assertEquals('TestRunner', $details['source']);
        $this->assertEquals(888, $details['record_id']);
        $this->assertEquals('attempt', $details['payload']['tamper']);
    }

    /**
     * @test
     */
    public function it_automatically_audits_on_mismatch_in_ilan_domain_yonetici(): void
    {
        // 1. Oturum açmış kullanıcı (Tenant 1)
        $user = new User();
        $user->tenant_id = 1;
        $this->actingAs($user);

        // 2. Başka kiracıya ait ilan (Tenant 99)
        $ilanId = DB::table('ilanlar')->insertGetId([
            'tenant_id' => 99,
            'baslik' => 'Forbidden Listing',
            'slug' => 'forbidden-listing-' . uniqid(),
            'yayin_durumu' => 'taslak',
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 3. Orkestratörü başlat
        $crudMock = $this->createMock(IlanCrudService::class);
        $dispatcherMock = $this->createMock(EventDispatcher::class);
        $manager = new IlanDomainYonetici($crudMock, $dispatcherMock, $this->auditor);

        // 4. İhlal denemesi yap - Exception fırlatılmalı
        $this->expectException(TenantMismatchException::class);

        try {
            $manager->yayinDurumuMutasyonu($ilanId, 'yayinda');
        } finally {
            // İhlalin anında governance_incidents tablosuna adli tescil olarak mühürlendiğini teyit et
            $this->assertDatabaseHas('governance_incidents', [
                'tenant_id' => '99',
                'olay_tipi' => 'CROSS_TENANT_MUTATION_ATTEMPT',
                'kaynak' => 'IlanDomainYonetici'
            ]);
        }
    }

    /**
     * @test
     */
    public function it_automatically_audits_on_mismatch_in_kisi_domain_yonetici(): void
    {
        // 1. Oturum açmış kullanıcı (Tenant 1)
        $user = new User();
        $user->tenant_id = 1;
        $this->actingAs($user);

        // 2. Orkestratörü başlat
        $dispatcherMock = $this->createMock(EventDispatcher::class);
        $manager = new KisiDomainYonetici($dispatcherMock, $this->auditor);

        // 3. Yabancı kiracı adına lead ingestion denemesi (Tenant 99)
        $this->expectException(TenantMismatchException::class);

        try {
            $manager->secureLeadIngestion([
                'tenant_id' => 99,
                'ad_soyad' => 'Cross Ingestion Lead',
                'telefon' => '5551234567'
            ]);
        } finally {
            // İhlalin anında governance_incidents tablosuna mühürlendiğini teyit et
            $this->assertDatabaseHas('governance_incidents', [
                'tenant_id' => '99',
                'olay_tipi' => 'CROSS_TENANT_MUTATION_ATTEMPT',
                'kaynak' => 'KisiDomainYonetici'
            ]);
        }
    }

    /**
     * @test
     */
    public function it_evaluates_behavioral_risk_score_correctly(): void
    {
        $kullaniciId = 1;

        // Verify normal action risk evaluation
        $scoreNormal = $this->auditor->evaluateBehavioralRiskScore($kullaniciId, 'VIEW_LISTING');
        $this->assertGreaterThanOrEqual(0.0, $scoreNormal);
        $this->assertLessThanOrEqual(1.0, $scoreNormal);

        // Verify administrative high-risk action evaluation scales risk higher
        $scoreHigh = $this->auditor->evaluateBehavioralRiskScore($kullaniciId, 'BULK_LISTING_EXPORT');
        $this->assertGreaterThan($scoreNormal, $scoreHigh);
        $this->assertEquals(0.35, round($scoreHigh - $scoreNormal, 2));
    }
}
