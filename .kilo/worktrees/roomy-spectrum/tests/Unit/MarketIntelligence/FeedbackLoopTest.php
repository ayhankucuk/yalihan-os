<?php

namespace Tests\Unit\MarketIntelligence;

use App\Models\MarketIntelligence\FeedbackResult;
use App\Models\MarketIntelligence\ListingOutcome;
use App\Models\MarketIntelligence\PredictionSnapshot;
use App\Services\MarketIntelligence\FeedbackEvaluationService;
use App\Services\MarketIntelligence\OutcomeTrackingService;
use App\Services\MarketIntelligence\PredictionSnapshotService;
use Tests\TestCase;

/**
 * MIE V2 — Self-Learning & Feedback Loop Tests
 *
 * DB-backed tests with transaction isolation from base TestCase.
 */
class FeedbackLoopTest extends TestCase
{
    private PredictionSnapshotService $snapshotService;
    private OutcomeTrackingService $outcomeService;
    private FeedbackEvaluationService $evaluationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->snapshotService = new PredictionSnapshotService();
        $this->outcomeService = new OutcomeTrackingService();
        $this->evaluationService = new FeedbackEvaluationService();
    }

    // ─── Test 1: Snapshot kaydediliyor ───

    public function test_snapshot_is_saved_correctly(): void
    {
        $payload = [
            'pricing_position' => 'underpriced',
            'pricing_score' => 72,
            'demand_score' => 80,
            'demand_label' => 'HOT',
            'confidence_score' => 85,
            'confidence_label' => 'HIGH',
            'opportunity_action' => 'BUY',
            'opportunity_score' => 78,
            'priority_score' => 82,
            'priority_label' => 'CRITICAL',
            'current_price' => 1500000,
            'benchmark_price' => 2000000,
        ];

        $snapshot = $this->snapshotService->saveSnapshot(101, $payload);

        $this->assertInstanceOf(PredictionSnapshot::class, $snapshot);
        $this->assertEquals(101, $snapshot->listing_id);
        $this->assertEquals('underpriced', $snapshot->pricing_position);
        $this->assertEquals('BUY', $snapshot->opportunity_action);
        $this->assertEquals(82, $snapshot->priority_score);
        $this->assertNotNull($snapshot->snapshot_at);

        $this->assertDatabaseHas('prediction_snapshots', [
            'listing_id' => 101,
            'pricing_position' => 'underpriced',
            'opportunity_action' => 'BUY',
        ]);
    }

    // ─── Test 2: Outcome kaydediliyor ───

    public function test_outcome_is_recorded_correctly(): void
    {
        $outcome = $this->outcomeService->recordOutcome(101, [
            'outcome_type' => 'sold',
            'days_to_close' => 22,
            'final_price' => 1550000,
            'price_changes_count' => 0,
            'lead_count' => 5,
        ]);

        $this->assertInstanceOf(ListingOutcome::class, $outcome);
        $this->assertEquals(101, $outcome->listing_id);
        $this->assertEquals('sold', $outcome->outcome_type);
        $this->assertEquals(22, $outcome->days_to_close);

        $this->assertDatabaseHas('listing_outcomes', [
            'listing_id' => 101,
            'outcome_type' => 'sold',
            'days_to_close' => 22,
        ]);
    }

    // ─── Test 3: Invalid outcome type rejected ───

    public function test_invalid_outcome_type_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->outcomeService->recordOutcome(101, [
            'outcome_type' => 'invalid_type',
        ]);
    }

    // ─── Test 4: Evaluation doğru — underpriced + hızlı satış = pricing_correct ───

    public function test_evaluation_underpriced_fast_sale_is_correct(): void
    {
        // Snapshot: underpriced, HOT demand, BUY
        $this->snapshotService->saveSnapshot(201, [
            'pricing_position' => 'underpriced',
            'demand_label' => 'HOT',
            'opportunity_action' => 'BUY',
            'confidence_label' => 'HIGH',
            'pricing_score' => 70,
            'demand_score' => 80,
            'confidence_score' => 85,
            'opportunity_score' => 78,
            'priority_score' => 82,
            'priority_label' => 'CRITICAL',
        ]);

        // Outcome: sold fast, no price changes
        $this->outcomeService->recordOutcome(201, [
            'outcome_type' => 'sold',
            'days_to_close' => 18,
            'final_price' => 1600000,
            'price_changes_count' => 0,
        ]);

        $result = $this->evaluationService->evaluate(201);

        $this->assertInstanceOf(FeedbackResult::class, $result);
        $this->assertTrue($result->pricing_correct);   // underpriced → fast sale ✓
        $this->assertTrue($result->demand_correct);     // HOT → <30 days ✓
        $this->assertTrue($result->opportunity_correct); // BUY → sold fast, no drops ✓
        $this->assertNotEmpty($result->feedback_reason);
    }

    // ─── Test 5: Evaluation — overpriced + slow = pricing_correct ───

    public function test_evaluation_overpriced_slow_sale_is_correct(): void
    {
        $this->snapshotService->saveSnapshot(202, [
            'pricing_position' => 'overpriced',
            'demand_label' => 'WEAK',
            'opportunity_action' => 'SELL',
            'pricing_score' => 30,
            'demand_score' => 15,
            'confidence_score' => 60,
            'confidence_label' => 'MEDIUM',
            'opportunity_score' => 35,
            'priority_score' => 65,
            'priority_label' => 'HIGH',
        ]);

        $this->outcomeService->recordOutcome(202, [
            'outcome_type' => 'sold',
            'days_to_close' => 95,
            'final_price' => 1800000,
            'price_changes_count' => 3,
        ]);

        $result = $this->evaluationService->evaluate(202);

        $this->assertTrue($result->pricing_correct);    // overpriced → slow (>60d) ✓
        $this->assertTrue($result->demand_correct);     // WEAK → >60 days ✓
        $this->assertTrue($result->opportunity_correct); // SELL → price changes >0 ✓
    }

    // ─── Test 6: Evaluation — wrong prediction = false ───

    public function test_evaluation_wrong_prediction_is_false(): void
    {
        // Predicted: underpriced, HOT, BUY
        $this->snapshotService->saveSnapshot(203, [
            'pricing_position' => 'underpriced',
            'demand_label' => 'HOT',
            'opportunity_action' => 'BUY',
            'pricing_score' => 70,
            'demand_score' => 80,
            'confidence_score' => 85,
            'confidence_label' => 'HIGH',
            'opportunity_score' => 78,
            'priority_score' => 82,
            'priority_label' => 'CRITICAL',
        ]);

        // Outcome: expired after 120 days — prediction was wrong
        $this->outcomeService->recordOutcome(203, [
            'outcome_type' => 'expired',
            'days_to_close' => 120,
            'final_price' => null,
            'price_changes_count' => 0,
        ]);

        $result = $this->evaluationService->evaluate(203);

        $this->assertFalse($result->pricing_correct);    // underpriced → didn't sell fast ✗
        $this->assertFalse($result->demand_correct);      // HOT → 120 days ✗
        $this->assertFalse($result->opportunity_correct);  // BUY → expired ✗
    }

    // ─── Test 7: Repeat evaluation deterministic ───

    public function test_repeat_evaluation_returns_same_result(): void
    {
        $this->snapshotService->saveSnapshot(204, [
            'pricing_position' => 'fair',
            'demand_label' => 'ACTIVE',
            'opportunity_action' => 'WAIT',
            'pricing_score' => 50,
            'demand_score' => 55,
            'confidence_score' => 60,
            'confidence_label' => 'MEDIUM',
            'opportunity_score' => 45,
            'priority_score' => 50,
            'priority_label' => 'MEDIUM',
        ]);

        $this->outcomeService->recordOutcome(204, [
            'outcome_type' => 'sold',
            'days_to_close' => 45,
            'final_price' => 1200000,
            'price_changes_count' => 1,
        ]);

        $result1 = $this->evaluationService->evaluate(204);
        $result2 = $this->evaluationService->evaluate(204);

        // Same DB record returned (idempotent)
        $this->assertEquals($result1->id, $result2->id);
        $this->assertEquals($result1->pricing_correct, $result2->pricing_correct);
        $this->assertEquals($result1->demand_correct, $result2->demand_correct);
        $this->assertEquals($result1->opportunity_correct, $result2->opportunity_correct);
        $this->assertEquals($result1->feedback_reason, $result2->feedback_reason);
    }

    // ─── Test 8: No snapshot or outcome → null ───

    public function test_evaluate_returns_null_without_data(): void
    {
        $result = $this->evaluationService->evaluate(999);
        $this->assertNull($result);
    }

    // ─── Test 9: Accuracy calculation ───

    public function test_accuracy_calculation(): void
    {
        // Insert 3 correct + 1 wrong feedback
        for ($i = 301; $i <= 303; $i++) {
            $this->snapshotService->saveSnapshot($i, [
                'pricing_position' => 'underpriced',
                'demand_label' => 'HOT',
                'opportunity_action' => 'BUY',
                'pricing_score' => 70,
                'demand_score' => 80,
                'confidence_score' => 85,
                'confidence_label' => 'HIGH',
                'opportunity_score' => 78,
                'priority_score' => 82,
                'priority_label' => 'CRITICAL',
            ]);
            $this->outcomeService->recordOutcome($i, [
                'outcome_type' => 'sold',
                'days_to_close' => 15,
                'price_changes_count' => 0,
            ]);
            $this->evaluationService->evaluate($i);
        }

        // One wrong prediction
        $this->snapshotService->saveSnapshot(304, [
            'pricing_position' => 'underpriced',
            'demand_label' => 'HOT',
            'opportunity_action' => 'BUY',
            'pricing_score' => 70,
            'demand_score' => 80,
            'confidence_score' => 85,
            'confidence_label' => 'HIGH',
            'opportunity_score' => 78,
            'priority_score' => 82,
            'priority_label' => 'CRITICAL',
        ]);
        $this->outcomeService->recordOutcome(304, [
            'outcome_type' => 'expired',
            'days_to_close' => 120,
            'price_changes_count' => 0,
        ]);
        $this->evaluationService->evaluate(304);

        $accuracy = $this->evaluationService->calculateAccuracy();

        $this->assertEquals(4, $accuracy['total_evaluated']);
        $this->assertEquals(75.0, $accuracy['pricing_accuracy']);   // 3 out of 4
        $this->assertNotNull($accuracy['demand_accuracy']);
        $this->assertNotNull($accuracy['opportunity_accuracy']);
    }

    // ─── Test 10: Latest snapshot retrieval ───

    public function test_get_latest_snapshot(): void
    {
        $this->snapshotService->saveSnapshot(401, [
            'pricing_position' => 'fair',
            'pricing_score' => 50,
            'demand_score' => 50,
            'confidence_score' => 50,
            'priority_score' => 40,
        ]);

        // Save another snapshot (later)
        $second = $this->snapshotService->saveSnapshot(401, [
            'pricing_position' => 'overpriced',
            'pricing_score' => 30,
            'demand_score' => 30,
            'confidence_score' => 60,
            'priority_score' => 55,
        ]);

        $latest = $this->snapshotService->getLatestSnapshot(401);

        $this->assertEquals($second->id, $latest->id);
        $this->assertEquals('overpriced', $latest->pricing_position);
    }

    // ─── Test 11: Pure evaluation functions without DB ───

    public function test_pure_evaluation_functions(): void
    {
        $snapshot = new PredictionSnapshot([
            'pricing_position' => 'fair',
            'demand_label' => 'ACTIVE',
            'opportunity_action' => 'WAIT',
        ]);

        $outcome = new ListingOutcome([
            'outcome_type' => 'sold',
            'days_to_close' => 50,
            'price_changes_count' => 1,
        ]);

        $pricingCorrect = $this->evaluationService->evaluatePricing($snapshot, $outcome);
        $demandCorrect = $this->evaluationService->evaluateDemand($snapshot, $outcome);
        $opportunityCorrect = $this->evaluationService->evaluateOpportunity($snapshot, $outcome);

        $this->assertTrue($pricingCorrect);    // fair → 15-90 day sale ✓
        $this->assertTrue($demandCorrect);     // ACTIVE → <60 days ✓
        $this->assertTrue($opportunityCorrect); // WAIT → sold in 30-120 days ✓
    }

    // ─── Test 12: Feedback reason is deterministic Turkish ───

    public function test_feedback_reason_is_deterministic_and_turkish(): void
    {
        $this->snapshotService->saveSnapshot(501, [
            'pricing_position' => 'overpriced',
            'demand_label' => 'WEAK',
            'opportunity_action' => 'SELL',
            'pricing_score' => 30,
            'demand_score' => 15,
            'confidence_score' => 60,
            'confidence_label' => 'MEDIUM',
            'opportunity_score' => 35,
            'priority_score' => 65,
            'priority_label' => 'HIGH',
        ]);

        $this->outcomeService->recordOutcome(501, [
            'outcome_type' => 'withdrawn',
            'days_to_close' => 90,
            'price_changes_count' => 2,
        ]);

        $result = $this->evaluationService->evaluate(501);

        $this->assertStringContainsString('fiyat pozisyonu', $result->feedback_reason);
        $this->assertStringContainsString('talep tahmini', $result->feedback_reason);
        $this->assertStringContainsString('aksiyon önerisi', $result->feedback_reason);
    }
}
