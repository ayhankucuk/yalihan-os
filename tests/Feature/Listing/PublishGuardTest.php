<?php

namespace Tests\Feature\Listing;

use App\Contracts\TemplateResolverInterface;
use App\Enums\IlanDurumu;
use App\Exceptions\TemplateNotFoundException;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Services\Listing\ListingScoreService;
use App\Services\Listing\YalihanLifecycle;
use DomainException;
use Tests\Helpers\TestFixtureHelper;
use Tests\TestCase;

/**
 * Phase 17B Publish Guard Tests
 *
 * Pass kriterleri:
 * 1. completion 99 → YAYINDA publish FAIL
 * 2. template missing → YAYINDA publish FAIL
 * 3. completion 100 + valid template → publish PASS
 * 4. raw state bypass → FAIL
 * 5. completion/quality score ayrımı doğru
 */
class PublishGuardTest extends TestCase
{
    use TestFixtureHelper;

    private YalihanLifecycle $service;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        $this->service = app(YalihanLifecycle::class);
    }

    // ── 1. Completion guard ──────────────────────────────────────────────────

    /** @test */
    public function completion_99_yayinda_publish_fail(): void
    {
        $mockTemplate = $this->createMock(TemplateResolverInterface::class);
        $mockTemplate->method('resolveByJunction')
            ->willReturn(new \App\Models\YayinTipiSablonu(['id' => 1, 'ad' => 'Test']));

        $service = $this->buildService($mockTemplate);

        // Minimal listing - completion will be recalculated as < 100
        $ilan = $this->eksikIlan(['yayin_tipi_id' => 1]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/completion_score=\d+/');

        $service->transition($ilan, IlanDurumu::YAYINDA);
    }

    /** @test */
    public function bos_olan_ilan_yayinda_publish_fail(): void
    {
        $mockTemplate = $this->createMock(TemplateResolverInterface::class);
        $mockTemplate->method('resolveByJunction')
            ->willReturn(new \App\Models\YayinTipiSablonu(['id' => 1, 'ad' => 'Test']));

        $service = $this->buildService($mockTemplate);

        // Minimal listing - completion will be recalculated as < 100
        $ilan = $this->eksikIlan(['yayin_tipi_id' => 1]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/completion_score=\d+/');

        $service->transition($ilan, IlanDurumu::YAYINDA);
    }

    /** @test */
    public function template_eksik_yayinda_publish_fail(): void
    {
        // Template resolver: exceptıon fırlat
        $mockTemplate = $this->createMock(TemplateResolverInterface::class);
        $mockTemplate->method('resolveByJunction')
            ->willThrowException(new TemplateNotFoundException('Template bulunamadı', 0));

        $service = $this->buildService($mockTemplate);
        $ilan    = $this->beklemedeliIlan(['yayin_tipi_id' => 999, 'completion_score' => 100, 'quality_score' => 41]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Template mapping bulunamad/');

        $service->transition($ilan, IlanDurumu::YAYINDA);
    }

    /** @test */
    public function yayin_tipi_id_eksik_yayinda_publish_fail(): void
    {
        // Mock template resolver - will never be called because templateGuard checks null first
        $mockTemplate = $this->createMock(TemplateResolverInterface::class);
        // Mock score service to return 100 (bypass completion guard)
        $mockScore = $this->getMockBuilder(ListingScoreService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['computeCompletionScore', 'computeQualityScore'])
            ->getMock();
        $mockScore->method('computeCompletionScore')->willReturn(100);
        $mockScore->method('computeQualityScore')->willReturn(50.0);

        $service = new YalihanLifecycle(
            app(\App\Services\Listing\ListingStateMachine::class),
            $mockTemplate,
            $mockScore,
        );

        // Create listing with null yayin_tipi_id (but other required fields present)
        $ilan = Ilan::factory()->create([
            'danisman_id' => $this->createAdminUser()->id,
            'yayin_durumu' => IlanDurumu::BEKLEMEDE,
            'yayin_tipi_id' => null,  // Missing required field
            'baslik' => 'Test Ilan Yayin Tipi Eksik',
            'fiyat' => 1000,
            'aciklama' => 'Test aciklama for yayin_tipi_id eksik test with enough chars',
            'il_id' => 1,
            'ilce_id' => 1,
            'ana_kategori_id' => IlanKategori::factory()->create()->id,
            'lat' => 37.0344,
            'lng' => 27.4305,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/yayin_tipi_id.*seçilmemiş/');

        $service->transition($ilan, IlanDurumu::YAYINDA);
    }

    /** @test */
    public function completion_100_ve_gecerli_template_publish_pass(): void
    {
        // Template resolver: başarılı
        $mockTemplate = $this->createMock(TemplateResolverInterface::class);
        $mockTemplate->method('resolveByJunction')
            ->willReturn(new \App\Models\YayinTipiSablonu(['id' => 1, 'ad' => 'Test Şablon']));

        $service = $this->buildService($mockTemplate);
        $ilan    = $this->beklemedeliIlan(['yayin_tipi_id' => 1, 'completion_score' => 100, 'quality_score' => 41]);

        // DomainException FIRLATILMAMALI
        $ilan = $service->transition($ilan, IlanDurumu::YAYINDA);

        $this->assertSame('yayinda', $ilan->yayin_durumu instanceof IlanDurumu
            ? $ilan->yayin_durumu->value
            : (string) $ilan->yayin_durumu
        );
    }

    // ── 2. Completion vs Quality ayrımı ─────────────────────────────────────

    /** @test */
    public function score_service_iki_ayri_skor_dondurur(): void
    {
        $svc  = app(ListingScoreService::class);
        $ilan = \App\Models\Ilan::factory()->make(['baslik' => 'Harika bir test ilani', 'fiyat' => 1000]);

        $completion = $svc->computeCompletionScore($ilan);
        $quality    = $svc->computeQualityScore($ilan);

        $this->assertIsInt($completion);
        $this->assertIsFloat($quality);
    }

    /** @test */
    public function bos_ilan_completion_score_dusuk(): void
    {
        $svc  = app(ListingScoreService::class);
        $ilan = \App\Models\Ilan::factory()->make(['baslik' => null, 'fiyat' => null, 'aciklama' => null]);

        $skor = $svc->computeCompletionScore($ilan);

        $this->assertLessThan(100, $skor);
    }

    // ── 3. Non-YAYINDA geçişlerde guard devreye girmez ──────────────────────

    /** @test */
    public function taslak_beklemede_gecisi_completion_gerektirmez(): void
    {
        // Guard sadece YAYINDA için çalışır
        $mockTemplate = $this->createMock(TemplateResolverInterface::class);
        $mockTemplate->expects($this->never())->method('resolveByJunction');

        $service = $this->buildService($mockTemplate);

        // Completion 0
        $ilan = $this->taslakIlan(['completion_score' => 0]);

        // Taslak -> Beklemede geçerli bir StateMachine geçişidir ve tamamlanma kontrolü tetiklemez.
        $sonuc = $service->transition($ilan, IlanDurumu::BEKLEMEDE);

        $this->assertSame('beklemede', $sonuc->yayin_durumu instanceof IlanDurumu ? $sonuc->yayin_durumu->value : (string) $sonuc->yayin_durumu);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function buildService(
        TemplateResolverInterface $templateResolver,
    ): YalihanLifecycle {
        return new YalihanLifecycle(
            app(\App\Services\Listing\ListingStateMachine::class),
            $templateResolver,
            app(\App\Services\Listing\ListingScoreService::class),
        );
    }

    /**
     * Create a publishable listing (100% completion).
     * For success test: completion_100_ve_gecerli_template_publish_pass
     */
    private function beklemedeliIlan(array $extra = []): Ilan
    {
        $user = $this->createAdminUser();

        return $this->createPublishableListing($user, array_merge([
            'yayin_durumu' => IlanDurumu::BEKLEMEDE,
        ], $extra));
    }

    /**
     * Create an INCOMPLETE listing (completion < 100).
     * For failure tests: completion_99, bos_olan_ilan
     */
    private function eksikIlan(array $extra = []): Ilan
    {
        $user = $this->createAdminUser();

        // Minimal listing - missing required fields will result in low completion score
        return Ilan::factory()->create(array_merge([
            'danisman_id' => $user->id,
            'yayin_durumu' => IlanDurumu::BEKLEMEDE,
            'baslik' => 'Test',
            'fiyat' => 1000,
            // Missing: aciklama, il_id, ilce_id, ana_kategori_id, yayin_tipi_id, ilan_sahibi_id, fotograf
        ], $extra));
    }

    private function taslakIlan(array $extra = []): Ilan
    {
        $dispatcher = Ilan::getEventDispatcher();
        Ilan::unsetEventDispatcher();

        $ilan = \Illuminate\Support\Facades\Schema::withoutForeignKeyConstraints(function () use ($extra) {
            return Ilan::factory()->create(array_merge(['yayin_durumu' => 'taslak'], $extra));
        });

        Ilan::setEventDispatcher($dispatcher);
        return $ilan;
    }
}
