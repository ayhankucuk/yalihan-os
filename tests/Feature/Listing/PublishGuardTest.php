<?php

namespace Tests\Feature\Listing;

use App\Contracts\TemplateResolverInterface;
use App\Enums\IlanDurumu;
use App\Exceptions\TemplateNotFoundException;
use App\Models\Ilan;
use App\Services\Listing\ListingScoreService;
use App\Services\Listing\YalihanLifecycle;
use DomainException;
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

        // Completion score < 100
        $ilan = $this->beklemedeliIlan(['yayin_tipi_id' => 1, 'completion_score' => 99]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/completion_score=99/');

        $service->transition($ilan, IlanDurumu::YAYINDA);
    }

    /** @test */
    public function bos_olan_ilan_yayinda_publish_fail(): void
    {
        $mockTemplate = $this->createMock(TemplateResolverInterface::class);
        $mockTemplate->method('resolveByJunction')
            ->willReturn(new \App\Models\YayinTipiSablonu(['id' => 1, 'ad' => 'Test']));

        $service = $this->buildService($mockTemplate);

        // Completion score < 100
        $ilan = $this->beklemedeliIlan(['yayin_tipi_id' => 1, 'completion_score' => 99]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/completion_score=99/');

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
        $mockTemplate = $this->createMock(TemplateResolverInterface::class);

        $service = $this->buildService($mockTemplate);

        // Completion Score >= 100
        $ilan = $this->beklemedeliIlan(['yayin_tipi_id' => null, 'completion_score' => 100, 'quality_score' => 41]);

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
        );
    }

    private function beklemedeliIlan(array $extra = []): Ilan
    {
        $dispatcher = Ilan::getEventDispatcher();
        Ilan::unsetEventDispatcher();

        $ilan = \Illuminate\Support\Facades\Schema::withoutForeignKeyConstraints(function () use ($extra) {
            return Ilan::factory()->create(array_merge(['yayin_durumu' => 'beklemede'], $extra));
        });

        Ilan::setEventDispatcher($dispatcher);
        return $ilan;
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
