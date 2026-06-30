<?php

namespace Tests\Unit\MarketIntelligence;

use App\Models\MarketIntelligence\FeedbackResult;
use App\Models\MarketIntelligence\PredictionSnapshot;
use App\Services\MarketIntelligence\CalibrationService;
use Tests\TestCase;

/**
 * CalibrationService Drift Guard Tests — MIE Risk 2
 *
 * Time decay, max weight cap, staleness detection.
 * DB-backed tests — transaction isolation via base TestCase.
 */
class CalibrationDriftGuardTest extends TestCase
{
    private CalibrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure clean slate — prevent leakage from other test classes
        FeedbackResult::query()->delete();
        PredictionSnapshot::query()->delete();
        $this->service = new CalibrationService();
    }

    // ─── Staleness ───

    public function test_staleness_true_when_no_feedback(): void
    {
        $result = $this->service->checkStaleness();

        $this->assertTrue($result['is_stale']);
        $this->assertNull($result['days_since_last']);
    }

    public function test_staleness_false_when_recent_feedback(): void
    {
        $snapshot = PredictionSnapshot::create([
            'listing_id' => 1,
            'pricing_position' => 'fair',
            'pricing_score' => 50,
            'demand_score' => 50,
            'demand_label' => 'ACTIVE',
            'confidence_score' => 50,
            'confidence_label' => 'MODERATE',
            'opportunity_action' => 'WAIT',
            'opportunity_score' => 50,
            'priority_score' => 50,
            'priority_label' => 'LOW',
            'current_price' => 100000,
            'benchmark_price' => 100000,
            'snapshot_at' => now(),
        ]);

        FeedbackResult::create([
            'listing_id' => 1,
            'snapshot_id' => $snapshot->id,
            'outcome_id' => 1,
            'pricing_correct' => true,
            'demand_correct' => true,
            'opportunity_correct' => true,
            'feedback_reason' => 'Test recent',
        ]);

        $result = $this->service->checkStaleness();

        $this->assertFalse($result['is_stale']);
        $this->assertSame(0, $result['days_since_last']);
    }

    public function test_staleness_true_when_old_feedback(): void
    {
        $snapshot = PredictionSnapshot::create([
            'listing_id' => 2,
            'pricing_position' => 'fair',
            'pricing_score' => 50,
            'demand_score' => 50,
            'demand_label' => 'ACTIVE',
            'confidence_score' => 50,
            'confidence_label' => 'MODERATE',
            'opportunity_action' => 'WAIT',
            'opportunity_score' => 50,
            'priority_score' => 50,
            'priority_label' => 'LOW',
            'current_price' => 100000,
            'benchmark_price' => 100000,
            'snapshot_at' => now()->subDays(60),
        ]);

        $fb = new FeedbackResult([
            'listing_id' => 2,
            'snapshot_id' => $snapshot->id,
            'outcome_id' => 1,
            'pricing_correct' => true,
            'demand_correct' => false,
            'opportunity_correct' => true,
            'feedback_reason' => 'Test old',
        ]);
        $fb->timestamps = false;
        $fb->created_at = now()->subDays(45);
        $fb->updated_at = now()->subDays(45);
        $fb->save();

        $result = $this->service->checkStaleness();

        $this->assertTrue($result['is_stale']);
        $this->assertSame(45, $result['days_since_last']);
    }

    // ─── Time Decay Accuracy ───

    public function test_decayed_accuracy_null_when_no_feedback(): void
    {
        $result = $this->service->calculateDecayedAccuracy('pricing_correct');
        $this->assertNull($result);
    }

    public function test_decayed_accuracy_rejects_invalid_field(): void
    {
        $result = $this->service->calculateDecayedAccuracy('invalid_field');
        $this->assertNull($result);
    }

    public function test_decayed_accuracy_recent_feedback_full_weight(): void
    {
        // 10 recent feedbacks: 7 correct, 3 incorrect → ~70%
        $this->createFeedbackBatch(10, 7, 0);

        $result = $this->service->calculateDecayedAccuracy('pricing_correct');

        $this->assertNotNull($result);
        // With max weight cap, single item can't dominate — result should be close to 70
        $this->assertGreaterThanOrEqual(60.0, $result);
        $this->assertLessThanOrEqual(80.0, $result);
    }

    public function test_decayed_accuracy_old_feedback_reduced_weight(): void
    {
        // 5 old incorrect feedbacks (180 days ago)
        $this->createFeedbackBatch(5, 0, 180);
        // 5 recent correct feedbacks (today)
        $this->createFeedbackBatch(5, 5, 0);

        $result = $this->service->calculateDecayedAccuracy('pricing_correct');

        $this->assertNotNull($result);
        // Recent correct feedback should dominate → accuracy should be >= 50
        $this->assertGreaterThanOrEqual(50.0, $result);
    }

    public function test_analyze_includes_staleness(): void
    {
        $result = $this->service->analyze();

        $this->assertArrayHasKey('staleness', $result);
        $this->assertArrayHasKey('is_stale', $result['staleness']);
        $this->assertArrayHasKey('days_since_last', $result['staleness']);
    }

    public function test_analyze_recommendations_include_staleness_warning(): void
    {
        // Create only old feedback
        $snapshot = PredictionSnapshot::create([
            'listing_id' => 99,
            'pricing_position' => 'fair',
            'pricing_score' => 50,
            'demand_score' => 50,
            'demand_label' => 'ACTIVE',
            'confidence_score' => 50,
            'confidence_label' => 'MODERATE',
            'opportunity_action' => 'WAIT',
            'opportunity_score' => 50,
            'priority_score' => 50,
            'priority_label' => 'LOW',
            'current_price' => 100000,
            'benchmark_price' => 100000,
            'snapshot_at' => now()->subDays(60),
        ]);

        $fb = new FeedbackResult([
            'listing_id' => 99,
            'snapshot_id' => $snapshot->id,
            'outcome_id' => 1,
            'pricing_correct' => true,
            'demand_correct' => true,
            'opportunity_correct' => true,
            'feedback_reason' => 'Stale feedback test',
        ]);
        $fb->timestamps = false;
        $fb->created_at = now()->subDays(45);
        $fb->updated_at = now()->subDays(45);
        $fb->save();

        $result = $this->service->analyze();

        $hasStaleWarning = false;
        foreach ($result['recommendations'] as $rec) {
            if (str_contains($rec, 'bayatlamış')) {
                $hasStaleWarning = true;
                break;
            }
        }

        $this->assertTrue($hasStaleWarning, 'Staleness warning should be in recommendations');
    }

    // ─── Helper ───

    private function createFeedbackBatch(int $count, int $correctCount, int $daysAgo): void
    {
        static $listingCounter = 100;

        for ($i = 0; $i < $count; $i++) {
            $listingId = $listingCounter++;
            $isCorrect = $i < $correctCount;

            $snapshot = PredictionSnapshot::create([
                'listing_id' => $listingId,
                'pricing_position' => 'fair',
                'pricing_score' => 50,
                'demand_score' => 50,
                'demand_label' => 'ACTIVE',
                'confidence_score' => 50,
                'confidence_label' => 'MODERATE',
                'opportunity_action' => 'WAIT',
                'opportunity_score' => 50,
                'priority_score' => 50,
                'priority_label' => 'LOW',
                'current_price' => 100000,
                'benchmark_price' => 100000,
                'snapshot_at' => now()->subDays($daysAgo),
            ]);

            $fb = new FeedbackResult([
                'listing_id' => $listingId,
                'snapshot_id' => $snapshot->id,
                'outcome_id' => 1,
                'pricing_correct' => $isCorrect,
                'demand_correct' => $isCorrect,
                'opportunity_correct' => $isCorrect,
                'feedback_reason' => 'Batch test',
            ]);

            if ($daysAgo > 0) {
                $fb->timestamps = false;
                $fb->created_at = now()->subDays($daysAgo);
                $fb->updated_at = now()->subDays($daysAgo);
            }

            $fb->save();
        }
    }
}
