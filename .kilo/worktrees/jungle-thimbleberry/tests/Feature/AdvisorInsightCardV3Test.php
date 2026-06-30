<?php

namespace Tests\Feature;

use App\DTOs\MarketIntelligence\AdvisorInsightDTO;
use App\DTOs\MarketIntelligence\PricingInsightDTO;
use App\Enums\MarketIntelligence\PricingPosition;
use Tests\TestCase;

/**
 * SAB V3 Harden — Advisor Insight Card Blade Render Tests
 *
 * 3 senaryonun blade çıktısını doğrular:
 * Test 1: Underpriced / HIGH confidence
 * Test 2: Overpriced / MEDIUM confidence
 * Test 3: Insufficient data / LOW confidence
 */
class AdvisorInsightCardV3Test extends TestCase
{
    // ════════════════════════════════════════════
    // TEST 1 — UNDERPRICED / HIGH CONFIDENCE
    // ════════════════════════════════════════════

    public function test_underpriced_high_confidence_renders_buy_badge(): void
    {
        $html = $this->renderCard(
            $this->makeBuyAdvisor(),
            $this->makeBuyPricing(),
        );

        $this->assertStringContainsString('Fırsat', $html);
        // HTML encodes apostrophe as &#039;
        $this->assertStringContainsString('benchmark&#039;a göre avantajlı konum', $html);
    }

    public function test_underpriced_high_confidence_shows_takibe_al_cta(): void
    {
        $html = $this->renderCard(
            $this->makeBuyAdvisor(),
            $this->makeBuyPricing(),
        );

        $this->assertStringContainsString('Takibe Al', $html);
        $this->assertStringContainsString('gündür yayında', $html);
    }

    public function test_underpriced_high_confidence_shows_confidence_with_quality(): void
    {
        $html = $this->renderCard(
            $this->makeBuyAdvisor(),
            $this->makeBuyPricing(),
        );

        $this->assertStringContainsString('85', $html); // score
        $this->assertStringContainsString('/100', $html);
        $this->assertStringContainsString('veri güçlü', $html);
        $this->assertStringContainsString('kesinlik', $html);
    }

    public function test_underpriced_high_confidence_shows_no_risk_fallback(): void
    {
        $html = $this->renderCard(
            $this->makeBuyAdvisor(),
            $this->makeBuyPricing(),
        );

        // S7: risk yoksa bile mesaj
        $this->assertStringContainsString('Ek risk sinyali tespit edilmedi', $html);
        $this->assertStringContainsString('piyasa davranışını belirlemez', $html);
    }

    // ════════════════════════════════════════════
    // TEST 2 — OVERPRICED / MEDIUM CONFIDENCE
    // ════════════════════════════════════════════

    public function test_overpriced_medium_confidence_renders_sell_badge(): void
    {
        $html = $this->renderCard(
            $this->makeSellAdvisor(),
            $this->makeSellPricing(),
        );

        $this->assertStringContainsString('Gözden Geçir', $html);
        $this->assertStringContainsString('fiyat benchmark üzerinde', $html);
    }

    public function test_overpriced_medium_confidence_shows_fiyati_incele_cta(): void
    {
        $html = $this->renderCard(
            $this->makeSellAdvisor(),
            $this->makeSellPricing(),
        );

        $this->assertStringContainsString('Fiyatı İncele', $html);
        $this->assertStringContainsString('benchmark', $html);
        $this->assertStringContainsString('%', $html);
    }

    public function test_overpriced_medium_confidence_no_forbidden_words(): void
    {
        $html = $this->renderCard(
            $this->makeSellAdvisor(),
            $this->makeSellPricing(),
        );

        $this->assertStringNotContainsString('kesin fırsat', $html);
        $this->assertStringNotContainsString('hemen sat', $html);
        $this->assertStringNotContainsString('garanti', $html);
        $this->assertStringNotContainsString('kaçırılmaz', $html);
    }

    public function test_overpriced_shows_confidence_disclaimer(): void
    {
        $html = $this->renderCard(
            $this->makeSellAdvisor(),
            $this->makeSellPricing(),
        );

        $this->assertStringContainsString('veri kalitesini ifade eder', $html);
        $this->assertStringContainsString('sonucu belirlemez', $html);
    }

    // ════════════════════════════════════════════
    // TEST 3 — INSUFFICIENT DATA / LOW CONFIDENCE
    // ════════════════════════════════════════════

    public function test_insufficient_data_renders_wait_or_insufficient_badge(): void
    {
        $html = $this->renderCard(
            $this->makeInsufficientAdvisor(),
            $this->makeInsufficientPricing(),
        );

        // insufficient_data or wait
        $this->assertTrue(
            str_contains($html, 'Veri Yetersiz') || str_contains($html, 'Bekle'),
            'Badge should indicate insufficient data or wait',
        );
    }

    public function test_insufficient_data_shows_risk_note(): void
    {
        $html = $this->renderCard(
            $this->makeInsufficientAdvisor(),
            $this->makeInsufficientPricing(),
        );

        // risk_note exists
        $this->assertStringContainsString('Dikkat Edilmesi Gerekenler', $html);
        $this->assertStringContainsString('verisi yetersiz', $html);
        // low confidence warning
        $this->assertStringContainsString('sınırlı veriyle üretildi', $html);
    }

    public function test_insufficient_data_shows_low_confidence_explanation(): void
    {
        $html = $this->renderCard(
            $this->makeInsufficientAdvisor(),
            $this->makeInsufficientPricing(),
        );

        $this->assertStringContainsString('veri çok zayıf', $html);
        $this->assertStringContainsString('/100', $html);
    }

    // ════════════════════════════════════════════
    // GLOBAL RULES
    // ════════════════════════════════════════════

    public function test_trust_stack_always_present(): void
    {
        $html = $this->renderCard(
            $this->makeBuyAdvisor(),
            $this->makeBuyPricing(),
        );

        // S4: Trust footer
        $this->assertStringContainsString('sinyallerine dayanır', $html);
        $this->assertStringContainsString('Nihai karar kullanıcıya aittir', $html);
        // S9: system improvement (line may be split across HTML lines)
        $this->assertStringContainsString('öneri üretir', $html);
        $this->assertStringContainsString('izleyerek', $html);
    }

    public function test_section_order_badge_summary_action_confidence_why_details_risk(): void
    {
        $html = $this->renderCard(
            $this->makeSellAdvisor(),
            $this->makeSellPricing(),
        );

        // Verify section ordering: Badge < Summary < Action < Confidence < Why < Details < Risk
        $posBadge = strpos($html, 'Advisor Insight');
        $posSummary = strpos($html, 'Fiyat benchmark üzerinde');
        $posAction = strpos($html, 'Önerilen Aksiyon');
        $posConfidence = strpos($html, 'Güven Düzeyi');
        $posWhy = strpos($html, 'Bu öneri neden verildi?');
        $posDetails = strpos($html, 'Detaylar');
        $posRisk = strpos($html, 'Dikkat Edilmesi Gerekenler');

        $this->assertNotFalse($posBadge, 'Badge section missing');
        $this->assertNotFalse($posSummary, 'Summary section missing');
        $this->assertNotFalse($posAction, 'Action section missing');
        $this->assertNotFalse($posConfidence, 'Confidence section missing');
        $this->assertNotFalse($posRisk, 'Risk section missing');

        $this->assertLessThan($posSummary, $posBadge, 'Badge must come before Summary');
        $this->assertLessThan($posAction, $posSummary, 'Summary must come before Action');
        $this->assertLessThan($posConfidence, $posAction, 'Action must come before Confidence');

        if ($posWhy !== false) {
            $this->assertLessThan($posWhy, $posConfidence, 'Confidence must come before Why');
        }

        $this->assertLessThan($posRisk, $posConfidence, 'Confidence must come before Risk');
    }

    public function test_reasoning_lines_only_show_numeric_content(): void
    {
        $advisor = new AdvisorInsightDTO(
            summary: 'Test özet.',
            reasoning: "Genel yorum burada\n• Fiyat %12 düşük\n- 14 emsal mevcut\n- Sadece metin satırı",
            recommended_action: 'Test aksiyon',
            urgency: 'MEDIUM',
            risk_note: '',
        );

        $html = $this->renderCard($advisor, $this->makeBuyPricing());

        // S5: Only numeric lines should render
        $this->assertStringContainsString('%12', $html);
        $this->assertStringContainsString('14 emsal', $html);
        // Non-numeric lines should NOT render
        $this->assertStringNotContainsString('Genel yorum burada', $html);
        $this->assertStringNotContainsString('Sadece metin satırı', $html);
    }

    // ════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════

    private function renderCard(AdvisorInsightDTO $advisor, PricingInsightDTO $pricing): string
    {
        $ilan = new \stdClass();
        $ilan->yayin_tarihi = now()->subDays(23);

        return $this->blade(
            '@include("admin.ilanlar.components.cockpit.advisor-insight", [
                "advisorInsight" => $advisorInsight,
                "pricingInsight" => $pricingInsight,
                "ilan" => $ilan,
            ])',
            [
                'advisorInsight' => $advisor,
                'pricingInsight' => $pricing,
                'ilan' => $ilan,
            ],
        );
    }

    private function makeBuyAdvisor(): AdvisorInsightDTO
    {
        return new AdvisorInsightDTO(
            summary: 'Fiyat piyasa ortalamasının altında, talep güçlü.',
            reasoning: "• Fiyat benchmark'a göre %12 düşük\n• 14 karşılaştırmalı ilan mevcut\n• Ortalama kapanış süresi: 18 gün",
            recommended_action: 'İlanı takip listesine alın ve fırsatı değerlendirin.',
            urgency: 'HIGH',
            risk_note: '',
        );
    }

    private function makeBuyPricing(): PricingInsightDTO
    {
        return new PricingInsightDTO(
            ilan_id: 1,
            current_price: 3500000,
            benchmark_price: 4000000,
            benchmark_min: 3200000,
            benchmark_max: 4800000,
            sample_size: 14,
            price_delta_percent: -12.5,
            pricing_position: PricingPosition::UNDERPRICED,
            pricing_score: 82,
            confidence: 'high',
            insufficient_data: false,
            reason: 'Yeterli emsal verisi mevcut.',
            confidence_score: 85,
            confidence_label: 'HIGH',
            confidence_reason: '14 emsal ve güçlü talep verisine dayanır.',
            demand_score: 78,
            demand_label: 'HOT',
            demand_reason: 'Bölgede aktif talep yüksek.',
            opportunity_score: 80,
            opportunity_action: 'BUY',
            opportunity_reason: 'Fiyat avantajlı, talep güçlü.',
        );
    }

    private function makeSellAdvisor(): AdvisorInsightDTO
    {
        return new AdvisorInsightDTO(
            summary: 'Fiyat benchmark üzerinde, revizyon öneriliyor.',
            reasoning: "• Fiyat benchmark'a göre %14 yüksek\n• 8 karşılaştırmalı ilan mevcut\n• Talep orta seviyede",
            recommended_action: 'Fiyat revizyonunu değerlendirin.',
            urgency: 'HIGH',
            risk_note: 'Uzun süre satışta kalma riski mevcut.',
        );
    }

    private function makeSellPricing(): PricingInsightDTO
    {
        return new PricingInsightDTO(
            ilan_id: 2,
            current_price: 5700000,
            benchmark_price: 5000000,
            benchmark_min: 4500000,
            benchmark_max: 5500000,
            sample_size: 8,
            price_delta_percent: 14.0,
            pricing_position: PricingPosition::OVERPRICED,
            pricing_score: 35,
            confidence: 'medium',
            insufficient_data: false,
            reason: 'Sınırlı emsal verisi mevcut.',
            confidence_score: 58,
            confidence_label: 'MEDIUM',
            confidence_reason: '8 emsal mevcut ama bölge verisi sınırlı.',
            demand_score: 45,
            demand_label: 'SLOW',
            demand_reason: 'Bölgede talep düşük.',
            opportunity_score: 30,
            opportunity_action: 'SELL',
            opportunity_reason: 'Fiyat benchmark üzerinde.',
        );
    }

    private function makeInsufficientAdvisor(): AdvisorInsightDTO
    {
        return new AdvisorInsightDTO(
            summary: 'Yeterli karşılaştırma verisi bulunmuyor.',
            reasoning: 'Bölgede yeterli emsal yok, analiz güvenilir değil.',
            recommended_action: 'Daha fazla veri toplanmasını bekleyin.',
            urgency: 'LOW',
            risk_note: 'Karşılaştırma verisi yetersiz, sonuçlar güvenilir olmayabilir.',
        );
    }

    private function makeInsufficientPricing(): PricingInsightDTO
    {
        return new PricingInsightDTO(
            ilan_id: 3,
            current_price: 2000000,
            benchmark_price: null,
            benchmark_min: null,
            benchmark_max: null,
            sample_size: 2,
            price_delta_percent: null,
            pricing_position: PricingPosition::INSUFFICIENT_DATA,
            pricing_score: 0,
            confidence: 'very_low',
            insufficient_data: true,
            reason: 'Yetersiz emsal verisi.',
            confidence_score: 15,
            confidence_label: 'VERY_LOW',
            confidence_reason: 'Sadece 2 emsal mevcut.',
            demand_score: 10,
            demand_label: 'WEAK',
            demand_reason: 'Talep verisi yetersiz.',
            opportunity_score: 0,
            opportunity_action: 'INSUFFICIENT_DATA',
            opportunity_reason: 'Veri yetersiz.',
        );
    }
}
