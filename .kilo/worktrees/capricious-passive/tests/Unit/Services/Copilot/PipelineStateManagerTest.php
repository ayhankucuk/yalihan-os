<?php

namespace Tests\Unit\Services\Copilot;

use App\Enums\PipelineAdimDurumu;
use App\Enums\PipelineDurumu;
use App\Services\AI\Copilot\Pipeline\PipelineStateManager;
use Tests\TestCase;

class PipelineStateManagerTest extends TestCase
{
    private PipelineStateManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new PipelineStateManager();
    }

    // --- Run transition tests ---

    public function test_queued_can_transition_to_normalizing(): void
    {
        $this->assertTrue($this->manager->canTransitionRun('queued', 'normalizing'));
    }

    public function test_queued_can_transition_to_failed(): void
    {
        $this->assertTrue($this->manager->canTransitionRun('queued', 'failed'));
    }

    public function test_queued_can_transition_to_cancelled(): void
    {
        $this->assertTrue($this->manager->canTransitionRun('queued', 'cancelled'));
    }

    public function test_queued_cannot_transition_to_audit_running(): void
    {
        $this->assertFalse($this->manager->canTransitionRun('queued', 'audit_running'));
    }

    public function test_normalizing_can_transition_to_validated(): void
    {
        $this->assertTrue($this->manager->canTransitionRun('normalizing', 'validated'));
    }

    public function test_normalizing_can_transition_to_halted(): void
    {
        $this->assertTrue($this->manager->canTransitionRun('normalizing', 'halted'));
    }

    public function test_validated_transitions_to_audit_running(): void
    {
        $this->assertTrue($this->manager->canTransitionRun('validated', 'audit_running'));
    }

    public function test_audit_running_can_skip_to_governing(): void
    {
        $this->assertTrue($this->manager->canTransitionRun('audit_running', 'governing'));
    }

    public function test_sequential_pipeline_full_flow(): void
    {
        $flow = [
            ['queued', 'normalizing'],
            ['normalizing', 'validated'],
            ['validated', 'audit_running'],
            ['audit_running', 'fix_running'],
            ['fix_running', 'execution_running'],
            ['execution_running', 'verification_running'],
            ['verification_running', 'governing'],
            ['governing', 'completed'],
        ];

        foreach ($flow as [$from, $to]) {
            $this->assertTrue(
                $this->manager->canTransitionRun($from, $to),
                "Expected {$from} → {$to} to be allowed"
            );
        }
    }

    public function test_terminal_states_have_no_transitions(): void
    {
        $terminals = ['completed', 'failed', 'halted', 'cancelled'];
        $allStates = array_map(fn ($case) => $case->value, PipelineDurumu::cases());

        foreach ($terminals as $terminal) {
            foreach ($allStates as $target) {
                $this->assertFalse(
                    $this->manager->canTransitionRun($terminal, $target),
                    "{$terminal} should not transition to {$target}"
                );
            }
        }
    }

    public function test_cannot_skip_normalizing(): void
    {
        $this->assertFalse($this->manager->canTransitionRun('queued', 'validated'));
    }

    public function test_cannot_go_backwards(): void
    {
        $this->assertFalse($this->manager->canTransitionRun('audit_running', 'normalizing'));
        $this->assertFalse($this->manager->canTransitionRun('governing', 'audit_running'));
    }

    // --- Step transition tests ---

    public function test_step_pending_can_transition_to_running(): void
    {
        $this->assertTrue($this->manager->canTransitionStep('pending', 'running'));
    }

    public function test_step_pending_can_be_skipped(): void
    {
        $this->assertTrue($this->manager->canTransitionStep('pending', 'skipped'));
    }

    public function test_step_running_can_complete(): void
    {
        $this->assertTrue($this->manager->canTransitionStep('running', 'completed'));
    }

    public function test_step_running_can_fail(): void
    {
        $this->assertTrue($this->manager->canTransitionStep('running', 'failed'));
    }

    public function test_step_failed_can_retry(): void
    {
        $this->assertTrue($this->manager->canTransitionStep('failed', 'running'));
    }

    public function test_step_completed_is_terminal(): void
    {
        $this->assertFalse($this->manager->canTransitionStep('completed', 'running'));
        $this->assertFalse($this->manager->canTransitionStep('completed', 'failed'));
    }

    public function test_step_skipped_is_terminal(): void
    {
        $this->assertFalse($this->manager->canTransitionStep('skipped', 'running'));
    }

    // --- Stage mapping ---

    public function test_stage_to_step_mapping(): void
    {
        $this->assertEquals('audit', PipelineStateManager::STAGE_TO_STEP['audit_running']);
        $this->assertEquals('fix', PipelineStateManager::STAGE_TO_STEP['fix_running']);
        $this->assertEquals('execution', PipelineStateManager::STAGE_TO_STEP['execution_running']);
        $this->assertEquals('verification', PipelineStateManager::STAGE_TO_STEP['verification_running']);
        $this->assertEquals('govern', PipelineStateManager::STAGE_TO_STEP['governing']);
    }

    // --- Enum helpers ---

    public function test_pipeline_durumu_is_terminal(): void
    {
        $this->assertTrue(PipelineDurumu::COMPLETED->isTerminal());
        $this->assertTrue(PipelineDurumu::FAILED->isTerminal());
        $this->assertTrue(PipelineDurumu::HALTED->isTerminal());
        $this->assertTrue(PipelineDurumu::CANCELLED->isTerminal());
        $this->assertFalse(PipelineDurumu::QUEUED->isTerminal());
        $this->assertFalse(PipelineDurumu::AUDIT_RUNNING->isTerminal());
    }

    public function test_pipeline_durumu_is_running(): void
    {
        $this->assertTrue(PipelineDurumu::NORMALIZING->isRunning());
        $this->assertTrue(PipelineDurumu::AUDIT_RUNNING->isRunning());
        $this->assertTrue(PipelineDurumu::GOVERNING->isRunning());
        $this->assertFalse(PipelineDurumu::QUEUED->isRunning());
        $this->assertFalse(PipelineDurumu::COMPLETED->isRunning());
    }

    public function test_pipeline_adim_durumu_is_terminal(): void
    {
        $this->assertTrue(PipelineAdimDurumu::COMPLETED->isTerminal());
        $this->assertTrue(PipelineAdimDurumu::FAILED->isTerminal());
        $this->assertTrue(PipelineAdimDurumu::SKIPPED->isTerminal());
        $this->assertTrue(PipelineAdimDurumu::BLOCKED->isTerminal());
        $this->assertFalse(PipelineAdimDurumu::PENDING->isTerminal());
        $this->assertFalse(PipelineAdimDurumu::RUNNING->isTerminal());
    }
}
