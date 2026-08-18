<?php

namespace Tests\Feature\Ydl;

use App\DTOs\Ydl\YdlContextOutput;
use App\DTOs\Ydl\YdlPublishRecommendation;
use App\Enums\Governance\GovernanceState;
use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Services\Governance\GovernanceTransitionGuard;
use App\Services\Listing\ListingScoreService;
use App\Services\Ydl\YdlPublishReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PILOT-001 Wave 1 — YDL Publish Readiness Integration Tests.
 *
 * Tests the YDL Phase 3 + Property Publish supervised autonomy pipeline:
 *   YdlContextReader → YdlPublishReadinessService → YdlPublishRecommendation
 *
 * DoD coverage:
 *   [W1-T1]  evaluate() → PUBLISH_READY when all gates pass
 *   [W1-T2]  evaluate() → MISSING_FIELDS when completion < 100
 *   [W1-T3]  evaluate() → MISSING_FIELDS when quality < 40
 *   [W1-T4]  evaluate() → MISSING_FIELDS when yayin_tipi_id missing
 *   [W1-T5]  evaluate() → BLOCKED_GATE when YDL authority = STOP
 *   [W1-T6]  evaluate() → ALREADY_PUBLISHED when ilan is YAYINDA
 *   [W1-T7]  evaluate() → NOT_TASLAK when ilan is ARSIV
 *   [W1-T8]  canProceed() returns correct boolean
 *   [W1-T9]  YdlPublishRecommendation DTO: isReady() + toMarkdown()
 *   [W1-T10] toMarkdown() contains pilot + authority + scores + suggested actions
 *   [W1-T11] authority=LIMITED does NOT block publish (only STOP does)
 *   [W1-T12] governance canPublish=false → BLOCKED_GATE
 */
class YdlPublishReadinessServiceTest extends TestCase
{
    use RefreshDatabase;

    private YdlPublishReadinessService $service;
    private ListingScoreService $scoreService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service    = new YdlPublishReadinessService(
            new ListingScoreService(),
            new GovernanceTransitionGuard()
        );
        $this->scoreService = new ListingScoreService();
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T1: All gates pass → PUBLISH_READY
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t1_publish_ready_when_all_gates_pass(): void
    {
        $ilan = $this->makeTaslak([
            'baslik'          => 'Bodrum Deniz Manzaralı Lüks Villa 5+2',
            'aciklama'        => str_repeat('Bodrumun en merkezi lokasyonunda denize yürüme mesafesinde, özel havuzlu, tam donanımlı lüks villa. Klimalı odalar, açık mutfak, barbekü alanı, bahçe, garaj.', 5),
            'fiyat'           => 8500000,
            'il_id'           => 1,
            'ilce_id'         => 1,
            'ana_kategori_id' => 1,
            'yayin_tipi_id'   => 1,
            'ilan_sahibi_id'  => 1,
        ]);

        // Add at least 1 photo
        $ilan->fotograflar()->create([
            'ilan_id'   => $ilan->id,
            'dosya_adi' => 'test-villa-01.jpg',
            'dosya_yolu' => '/photos/test-villa-01.jpg',
            'display_order' => 1,
        ]);

        $result = $this->service->evaluate($ilan, YdlContextOutput::AUTHORITY_FULL);

        $this->assertSame(YdlPublishRecommendation::DECISION_PUBLISH_READY, $result->decision);
        $this->assertSame('Yayına Hazır', $result->decisionLabel);
        $this->assertTrue($result->canPublish);
        $this->assertTrue($result->humanApprovalRequired);
        $this->assertSame(100, $result->completionScore);
        $this->assertSame(YdlContextOutput::AUTHORITY_FULL, $result->ydlAuthority);
        $this->assertSame('PILOT-001', $result->pilot);
        $this->assertNotEmpty($result->suggestedActions);
        $this->assertTrue($result->isReady());
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T2: completion < 100 → MISSING_FIELDS
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t2_missing_fields_when_completion_incomplete(): void
    {
        // No baslik, no aciklama, no fiyat, no il_id etc.
        $ilan = $this->makeTaslak([]);

        $result = $this->service->evaluate($ilan, YdlContextOutput::AUTHORITY_FULL);

        $this->assertSame(YdlPublishRecommendation::DECISION_MISSING_FIELDS, $result->decision);
        $this->assertFalse($result->canPublish);
        $this->assertFalse($result->humanApprovalRequired);
        $this->assertNotEmpty($result->missingFields);
        $this->assertNotEmpty($result->suggestedActions);
        $this->assertFalse($result->isReady());
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T3: quality < 40 → MISSING_FIELDS
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t3_missing_fields_when_quality_below_threshold(): void
    {
        // baslik < 10 chars → low quality
        $ilan = $this->makeTaslak([
            'baslik'  => 'Villa',
            'aciklama' => 'Short',
            'fiyat'    => 1000,
            'il_id'    => 1,
            'ilce_id'  => 1,
            'ana_kategori_id' => 1,
            'yayin_tipi_id'  => 1,
            'ilan_sahibi_id'  => 1,
        ]);

        $ilan->fotograflar()->create(['ilan_id' => $ilan->id, 'dosya_adi' => 'test.jpg', 'dosya_yolu' => '/photos/test.jpg', 'display_order' => 1]);

        $result = $this->service->evaluate($ilan, YdlContextOutput::AUTHORITY_FULL);

        $this->assertSame(YdlPublishRecommendation::DECISION_MISSING_FIELDS, $result->decision);
        $this->assertFalse($result->canPublish);
        $this->assertArrayHasKey(YdlPublishRecommendation::GATE_QUALITY, $result->blockingReasons);
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T4: yayin_tipi_id missing → MISSING_FIELDS
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t4_missing_fields_when_yayin_tipi_missing(): void
    {
        $ilan = $this->makeTaslak([
            'baslik'          => 'Bodrum Deniz Manzaralı Lüks Villa',
            'aciklama'        => str_repeat('Bodrumun en merkezi lokasyonunda denize yürüme mesafesinde.', 6),
            'fiyat'           => 8500000,
            'il_id'           => 1,
            'ilce_id'         => 1,
            'ana_kategori_id' => 1,
            'yayin_tipi_id'   => null, // MISSING
            'ilan_sahibi_id'  => 1,
        ]);

        $ilan->fotograflar()->create(['ilan_id' => $ilan->id, 'dosya_adi' => 'test.jpg', 'dosya_yolu' => '/photos/test.jpg', 'display_order' => 1]);

        $result = $this->service->evaluate($ilan, YdlContextOutput::AUTHORITY_FULL);

        $this->assertSame(YdlPublishRecommendation::DECISION_MISSING_FIELDS, $result->decision);
        $this->assertFalse($result->canPublish);
        $this->assertArrayHasKey(YdlPublishRecommendation::GATE_TEMPLATE, $result->blockingReasons);
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T5: YDL authority = STOP → BLOCKED_GATE
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t5_blocked_when_ydl_authority_stop(): void
    {
        $ilan = $this->makeTaslak([
            'baslik'          => 'Bodrum Deniz Manzaralı Lüks Villa 5+2',
            'aciklama'        => str_repeat('Bodrumun en merkezi lokasyonunda denize yürüme mesafesinde, özel havuzlu, tam donanımlı lüks villa. Klimalı odalar, açık mutfak, barbekü alanı, bahçe, garaj.', 5),
            'fiyat'           => 8500000,
            'il_id'           => 1,
            'ilce_id'         => 1,
            'ana_kategori_id' => 1,
            'yayin_tipi_id'   => 1,
            'ilan_sahibi_id'  => 1,
        ]);
        $ilan->fotograflar()->create(['ilan_id' => $ilan->id, 'dosya_adi' => 'test.jpg', 'dosya_yolu' => '/photos/test.jpg', 'display_order' => 1]);

        $result = $this->service->evaluate($ilan, YdlContextOutput::AUTHORITY_STOP);

        $this->assertSame(YdlPublishRecommendation::DECISION_BLOCKED_GATE, $result->decision);
        $this->assertFalse($result->canPublish);
        $this->assertStringContainsString('STOP', $result->rationale);
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T6: Ilan already YAYINDA → ALREADY_PUBLISHED
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t6_already_published_when_ilan_is_yayinda(): void
    {
        $ilan = $this->makeTaslak(['yayin_durumu' => IlanDurumu::YAYINDA]);

        $result = $this->service->evaluate($ilan, YdlContextOutput::AUTHORITY_FULL);

        $this->assertSame(YdlPublishRecommendation::DECISION_ALREADY_PUBLISHED, $result->decision);
        $this->assertFalse($result->canPublish);
        $this->assertFalse($result->humanApprovalRequired);
        $this->assertFalse($result->isReady());
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T7: Ilan in ARSIV → NOT_TASLAK
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t7_not_taslak_when_ilan_is_arsiv(): void
    {
        $ilan = $this->makeTaslak(['yayin_durumu' => IlanDurumu::ARSIV]);

        $result = $this->service->evaluate($ilan, YdlContextOutput::AUTHORITY_FULL);

        $this->assertSame(YdlPublishRecommendation::DECISION_NOT_TASLAK, $result->decision);
        $this->assertFalse($result->canPublish);
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T8: canProceed() returns correct boolean
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t8_can_proceed_returns_boolean(): void
    {
        $ilanReady = $this->makeTaslak([
            'baslik'          => 'Bodrum Deniz Manzaralı Lüks Villa 5+2',
            'aciklama'        => str_repeat('Bodrumun en merkezi lokasyonunda denize yürüme mesafesinde, özel havuzlu, tam donanımlı lüks villa.', 5),
            'fiyat'           => 8500000,
            'il_id'           => 1,
            'ilce_id'         => 1,
            'ana_kategori_id' => 1,
            'yayin_tipi_id'   => 1,
            'ilan_sahibi_id'  => 1,
        ]);
        $ilanReady->fotograflar()->create(['ilan_id' => $ilanReady->id, 'dosya_adi' => 'test-ready.jpg', 'dosya_yolu' => '/photos/test-ready.jpg', 'display_order' => 1]);

        $ilanMissing = $this->makeTaslak([]);

        $this->assertTrue($this->service->canProceed($ilanReady));
        $this->assertFalse($this->service->canProceed($ilanMissing));
        $this->assertFalse($this->service->canProceed($ilanReady, YdlContextOutput::AUTHORITY_STOP));
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T9: YdlPublishRecommendation DTO — isReady() + toMarkdown()
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t9_recommendation_dto_is_ready_and_to_markdown(): void
    {
        $ilan = $this->makeTaslak([
            'baslik'          => 'Bodrum Deniz Manzaralı Lüks Villa 5+2',
            'aciklama'        => str_repeat('Bodrumun en merkezi lokasyonunda denize yürüme mesafesinde, özel havuzlu, tam donanımlı lüks villa.', 5),
            'fiyat'           => 8500000,
            'il_id'           => 1,
            'ilce_id'         => 1,
            'ana_kategori_id' => 1,
            'yayin_tipi_id'   => 1,
            'ilan_sahibi_id'  => 1,
        ]);
        $ilan->fotograflar()->create(['ilan_id' => $ilan->id, 'dosya_adi' => 'test.jpg', 'dosya_yolu' => '/photos/test.jpg', 'display_order' => 1]);

        $result = $this->service->evaluate($ilan);

        // isReady()
        $this->assertTrue($result->isReady());

        // toMarkdown() must contain key sections
        $md = $result->toMarkdown();
        $this->assertStringContainsString('## YDL — Publish Readiness', $md);
        $this->assertStringContainsString('PILOT-001', $md);
        $this->assertStringContainsString('Yayına Hazır', $md);
        $this->assertStringContainsString('completion=', $md);
        $this->assertStringContainsString('quality=', $md);
        $this->assertStringContainsString('Agent Önerileri', $md);
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T10: toMarkdown() contains agent suggestions for MISSING_FIELDS
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t10_missing_fields_markdown_has_suggestions(): void
    {
        $ilan = $this->makeTaslak(['baslik' => 'X']); // incomplete

        $result = $this->service->evaluate($ilan);

        $md = $result->toMarkdown();

        $this->assertStringContainsString('Eksik Alanlar Var', $md);
        $this->assertStringContainsString('Eksik Alanlar', $md);
        $this->assertNotEmpty($result->suggestedActions);
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T11: authority=LIMITED does NOT block (only STOP does)
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t11_limited_authority_does_not_block_publish(): void
    {
        $ilan = $this->makeTaslak([
            'baslik'          => 'Bodrum Deniz Manzaralı Lüks Villa 5+2',
            'aciklama'        => str_repeat('Bodrumun en merkezi lokasyonunda denize yürüme mesafesinde, özel havuzlu, tam donanımlı lüks villa.', 5),
            'fiyat'           => 8500000,
            'il_id'           => 1,
            'ilce_id'         => 1,
            'ana_kategori_id' => 1,
            'yayin_tipi_id'   => 1,
            'ilan_sahibi_id'  => 1,
        ]);
        $ilan->fotograflar()->create(['ilan_id' => $ilan->id, 'dosya_adi' => 'test.jpg', 'dosya_yolu' => '/photos/test.jpg', 'display_order' => 1]);

        // LIMITED_BY_BLOCKER should NOT block — only STOP does
        $result = $this->service->evaluate($ilan, YdlContextOutput::AUTHORITY_LIMITED_BY_BLOCKER);

        $this->assertSame(YdlPublishRecommendation::DECISION_PUBLISH_READY, $result->decision);
        $this->assertTrue($result->canPublish);
    }

    // ─────────────────────────────────────────────────────────────────
    // W1-T12: governance canPublish=false → BLOCKED_GATE
    // ─────────────────────────────────────────────────────────────────

    public function test_w1_t12_blocked_when_governance_state_not_promoted(): void
    {
        $ilan = $this->makeTaslak([
            'baslik'          => 'Bodrum Deniz Manzaralı Lüks Villa 5+2',
            'aciklama'        => str_repeat('Bodrumun en merkezi lokasyonunda denize yürüme mesafesinde, özel havuzlu, tam donanımlı lüks villa.', 5),
            'fiyat'           => 8500000,
            'il_id'           => 1,
            'ilce_id'         => 1,
            'ana_kategori_id' => 1,
            'yayin_tipi_id'   => 1,
            'ilan_sahibi_id'  => 1,
        ]);
        $ilan->fotograflar()->create(['ilan_id' => $ilan->id, 'dosya_adi' => 'test.jpg', 'dosya_yolu' => '/photos/test.jpg', 'display_order' => 1]);

        // Override governance state to DRAFT (not PROMOTED → canPublish=false)
        $result = $this->service->evaluate($ilan, YdlContextOutput::AUTHORITY_FULL, GovernanceState::DRAFT);

        $this->assertSame(YdlPublishRecommendation::DECISION_BLOCKED_GATE, $result->decision);
        $this->assertFalse($result->canPublish);
        $this->assertStringContainsString('canPublish=false', $result->rationale);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Create an Ilan in TASLAK state with optional overrides.
     *
     * @param array<string, mixed> $overrides
     */
    private function makeTaslak(array $overrides = []): Ilan
    {
        $defaults = [
            'baslik'          => 'Test Ilan',
            'aciklama'        => null,
            'fiyat'           => null,
            'il_id'           => null,
            'ilce_id'         => null,
            'ana_kategori_id' => null,
            'yayin_tipi_id'   => null,
            'ilan_sahibi_id'  => null,
            'yayin_durumu'    => IlanDurumu::TASLAK->value,
            'user_id'         => 1,
        ];

        $attributes = array_merge($defaults, $overrides);
        // Remove nulls — Ilan defaults will handle them
        $attributes = array_filter($attributes, fn($v) => ! is_null($v));

        return Ilan::factory()->create($attributes);
    }
}
