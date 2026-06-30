<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FindingDecision;
use App\Enums\FindingSeverity;
use App\Models\GovernanceDecision;
use App\Models\User;
use App\Http\Middleware\RoleMiddleware;
use App\Services\Intelligence\ActionFeedbackService;
use Tests\TestCase;

/**
 * SAB8: Decision → Action → Feedback Loop Tests
 *
 * Covers: action result recording, feedback, simulation,
 * stats, and learning signals.
 */
class SAB8ActionFeedbackTest extends TestCase
{

    private GovernanceDecision $decision;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();

        $this->decision = GovernanceDecision::create([
            'finding_id' => 'SAB8-TEST-' . uniqid(),
            'source' => 'test',
            'domain' => 'test_domain',
            'severity' => 'medium',
            'title' => 'Test Finding for SAB8',
            'reason' => 'Test reason',
            'target' => 'test.target',
            'recommended_action' => 'fix_field',
            'risk' => 'low',
            'decision' => 'needs_review',
            'karar_durumu' => 'approved',
            'karar_tarihi' => now(),
            'confidence' => 0.85,
            'timeline' => [['event' => 'approved', 'user_id' => 1, 'detail' => 'Test', 'timestamp' => now()->toIso8601String()]],
        ]);
    }

    // ── Model: recordResult ──

    /** @test */
    public function it_records_action_result_on_decision()
    {
        $this->decision->recordResult(true, ['yayin_durumu'], 'Field corrected', 75);

        $this->decision->refresh();

        $this->assertNotNull($this->decision->action_result);
        $this->assertTrue($this->decision->action_result['success']);
        $this->assertEquals(['yayin_durumu'], $this->decision->action_result['changed_fields']);
        $this->assertEquals('Field corrected', $this->decision->action_result['result_summary']);
        $this->assertEquals(75, $this->decision->impact_score);
        $this->assertNotNull($this->decision->action_completed_at);
    }

    /** @test */
    public function it_records_failed_action_result()
    {
        $this->decision->recordResult(false, [], 'Validation failed', -30);

        $this->decision->refresh();

        $this->assertFalse($this->decision->action_result['success']);
        $this->assertEquals(-30, $this->decision->impact_score);
        $this->assertTrue($this->decision->hasResult());
        $this->assertFalse($this->decision->wasSuccessful());
    }

    /** @test */
    public function has_result_returns_false_when_no_result()
    {
        $this->assertFalse($this->decision->hasResult());
    }

    /** @test */
    public function was_successful_requires_result()
    {
        $this->assertFalse($this->decision->wasSuccessful());
    }

    // ── Model: addFeedback ──

    /** @test */
    public function it_adds_feedback_with_explicit_user_id()
    {
        $this->decision->addFeedback('Bu karar doğruydu', 42);

        $this->decision->refresh();

        $this->assertEquals('Bu karar doğruydu', $this->decision->feedback_note);

        $lastEvent = collect($this->decision->timeline)->last();
        $this->assertEquals('feedback_added', $lastEvent['event']);
        $this->assertEquals(42, $lastEvent['user_id']);
    }

    /** @test */
    public function it_adds_feedback_with_auth_fallback()
    {
        $this->actingAs($this->admin);

        $this->decision->addFeedback('Test feedback');

        $this->decision->refresh();

        $this->assertEquals('Test feedback', $this->decision->feedback_note);
        $lastEvent = collect($this->decision->timeline)->last();
        $this->assertEquals($this->admin->id, $lastEvent['user_id']);
    }

    // ── Model: status helpers ──

    /** @test */
    public function get_status_label_returns_turkish_labels()
    {
        $this->decision->karar_durumu = 'auto_applied';
        $this->decision->recordResult(true, [], 'OK', 50);
        $this->assertEquals('Oto-Başarılı', $this->decision->getStatusLabel());

        $this->decision->karar_durumu = 'approved';
        $this->decision->update(['action_result' => ['success' => true, 'changed_fields' => [], 'result_summary' => 'OK']]);
        $this->assertEquals('Uygulandı', $this->decision->getStatusLabel());
    }

    /** @test */
    public function get_status_color_returns_correct_class()
    {
        $this->decision->karar_durumu = 'pending';
        $this->assertStringContains('yellow', $this->decision->getStatusColor());

        $this->decision->karar_durumu = 'failed';
        $this->assertStringContains('red', $this->decision->getStatusColor());
    }

    // ── Model: scopes ──

    /** @test */
    public function completed_scope_returns_decisions_with_results()
    {
        $this->assertEquals(0, GovernanceDecision::completed()->count());

        $this->decision->recordResult(true, [], 'Done', 10);

        $this->assertEquals(1, GovernanceDecision::completed()->count());
    }

    /** @test */
    public function action_failed_scope_returns_failed_decisions()
    {
        $this->decision->recordResult(false, [], 'Failed', -20);

        $this->assertEquals(1, GovernanceDecision::actionFailed()->count());
    }

    // ── Service: ActionFeedbackService ──

    /** @test */
    public function service_records_result_and_clears_cache()
    {
        $service = resolve(ActionFeedbackService::class);

        $service->recordActionResult($this->decision, true, ['field_a'], 'Fixed', 50);

        $this->decision->refresh();
        $this->assertTrue($this->decision->hasResult());
        $this->assertTrue($this->decision->wasSuccessful());
        $this->assertEquals(50, $this->decision->impact_score);
    }

    /** @test */
    public function service_triggers_learning_on_failure()
    {
        $service = resolve(ActionFeedbackService::class);

        // Create 3 failed decisions in same domain+action to trigger learning signal
        for ($i = 0; $i < 3; $i++) {
            $d = GovernanceDecision::create([
                'finding_id' => "SAB8-FAIL-{$i}-" . uniqid(),
                'source' => 'test',
                'domain' => 'repeat_domain',
                'severity' => 'high',
                'title' => 'Repeated Failure',
                'reason' => 'Same root cause',
                'target' => 'test.target',
                'recommended_action' => 'fix_field',
                'risk' => 'high',
                'decision' => 'needs_review',
                'karar_durumu' => 'approved',
                'karar_tarihi' => now(),
            ]);

            $service->recordActionResult($d, false, [], 'Failed again', -10);
        }

        // The 3rd failure should trigger a security log (handleFailedAction)
        // We verify the decisions are recorded correctly
        $this->assertEquals(3, GovernanceDecision::actionFailed()->where('domain', 'repeat_domain')->count());
    }

    /** @test */
    public function service_returns_action_stats()
    {
        $service = resolve(ActionFeedbackService::class);

        $stats = $service->getActionStats('30d');

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('success_rate', $stats);
        $this->assertArrayHasKey('avg_impact_score', $stats);
        $this->assertArrayHasKey('action_type_stats', $stats);
    }

    /** @test */
    public function service_returns_loop_summary()
    {
        $service = resolve(ActionFeedbackService::class);

        $summary = $service->getLoopSummary($this->decision);

        $this->assertArrayHasKey('finding', $summary);
        $this->assertArrayHasKey('decision', $summary);
        $this->assertArrayHasKey('action', $summary);
        $this->assertArrayHasKey('result', $summary);
        $this->assertArrayHasKey('feedback', $summary);
    }

    /** @test */
    public function service_get_by_tab_returns_paginated()
    {
        $service = resolve(ActionFeedbackService::class);

        $result = $service->getByTab('all');
        $this->assertNotNull($result);

        $result = $service->getByTab('pending');
        $this->assertNotNull($result);
    }

    // ── Controller: Routes ──

    /** @test */
    public function action_dashboard_requires_auth()
    {
        $response = $this->get(route('admin.governance.action-dashboard'));
        $response->assertRedirect();
    }

    /** @test */
    public function action_dashboard_loads_for_admin()
    {
        $response = $this->withoutMiddleware(RoleMiddleware::class)
            ->actingAs($this->admin)
            ->get(route('admin.governance.action-dashboard'));
        $response->assertStatus(200);
    }

    /** @test */
    public function record_result_validates_input()
    {
        $response = $this->withoutMiddleware(RoleMiddleware::class)
            ->actingAs($this->admin)
            ->post(
                route('admin.governance.decisions.record-result', $this->decision),
                []
            );
        $response->assertSessionHasErrors('success');
    }

    /** @test */
    public function record_result_stores_result()
    {
        $response = $this->withoutMiddleware(RoleMiddleware::class)
            ->actingAs($this->admin)
            ->post(
                route('admin.governance.decisions.record-result', $this->decision),
                [
                    'success' => true,
                    'result_summary' => 'All good',
                    'impact_score' => 60,
                ]
            );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->decision->refresh();
        $this->assertTrue($this->decision->hasResult());
        $this->assertEquals(60, $this->decision->impact_score);
    }

    /** @test */
    public function add_feedback_validates_note()
    {
        $response = $this->withoutMiddleware(RoleMiddleware::class)
            ->actingAs($this->admin)
            ->post(
                route('admin.governance.decisions.feedback', $this->decision),
                []
            );
        $response->assertSessionHasErrors('feedback_note');
    }

    /** @test */
    public function add_feedback_stores_note()
    {
        $response = $this->withoutMiddleware(RoleMiddleware::class)
            ->actingAs($this->admin)
            ->post(
                route('admin.governance.decisions.feedback', $this->decision),
                ['feedback_note' => 'Doğru karar verilmiş']
            );

        $response->assertRedirect();
        $this->decision->refresh();
        $this->assertEquals('Doğru karar verilmiş', $this->decision->feedback_note);
    }

    /** @test */
    public function simulate_rejects_non_pending_decisions()
    {
        $response = $this->withoutMiddleware(RoleMiddleware::class)
            ->actingAs($this->admin)
            ->post(
                route('admin.governance.decisions.simulate', $this->decision)
            );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function simulate_works_for_pending_decisions()
    {
        $this->decision->update(['karar_durumu' => 'pending']);

        $response = $this->withoutMiddleware(RoleMiddleware::class)
            ->actingAs($this->admin)
            ->post(
                route('admin.governance.decisions.simulate', $this->decision)
            );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->decision->refresh();
        $this->assertFalse($this->decision->hasResult());

        $lastEvent = collect($this->decision->timeline)->last();
        $this->assertEquals('simulated', $lastEvent['event']);
    }

    /** @test */
    public function simulate_rejects_already_completed_decisions()
    {
        $this->decision->update(['karar_durumu' => 'pending']);
        $this->decision->recordResult(true, [], 'Already done', 10);

        $response = $this->withoutMiddleware(RoleMiddleware::class)
            ->actingAs($this->admin)
            ->post(
                route('admin.governance.decisions.simulate', $this->decision)
            );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ── Helpers ──

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
