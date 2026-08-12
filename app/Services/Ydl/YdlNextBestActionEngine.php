<?php

namespace App\Services\Ydl;

use App\DTOs\Ydl\YdlRecommendation;
use App\DTOs\Ydl\YdlStateDefinition;

/**
 * YdlNextBestActionEngine — Deterministic next-action decision engine.
 *
 * YDL v1 Phase 1
 *
 * Priority queue (strict order):
 *
 *   1. FAIL gates exist        → ACTION_FIX
 *   2. Security blockers exist   → ACTION_STOP
 *   3. Internal blockers exist  → ACTION_FIX (blocking)
 *   4. SAB blocking violations  → ACTION_FIX
 *   5. All internal PASS
 *      + external blockers only  → ACTION_START (independent work)
 *   6. Everything PASS          → ACTION_NO_OP
 *
 * NO LLM inference in this class.
 * All decisions are deterministic.
 */
class YdlNextBestActionEngine
{
    public function decide(YdlStateDefinition $state, BlockerEvaluationResult $blockerResult): YdlRecommendation
    {
        // Priority 1: FAIL gates → FIX required
        if ($state->hasFailingGates()) {
            return $this->recommendFix($state, 'FAIL gates must be resolved before any new work');
        }

        // Priority 2: SAB blocking violations → STOP
        if ($state->hasBlockingViolations()) {
            return $this->recommendStop($state, 'SAB blocking violations present');
        }

        // Priority 3: Security blockers → STOP IMMEDIATELY
        if ($blockerResult->hasSecurityBlockers()) {
            return $this->recommendStop($state, 'Security blockers: ' . $blockerResult->summary());
        }

        // Priority 4: Internal blockers blocking production → FIX required
        if ($blockerResult->blocksProductionGates) {
            return $this->recommendFix($state, 'Internal blockers prevent production certification');
        }

        // Priority 5: All internal PASS + external blockers only → START independent work
        if ($state->isEngineeringComplete() && $blockerResult->allowsParallelWork) {
            return $this->recommendStart($state, $blockerResult);
        }

        // Priority 6: Everything complete → NO_OP
        if ($state->isAllGatesComplete() && !$blockerResult->hasActiveBlockers) {
            return $this->recommendNoOp($state, 'All gates PASS, no active blockers');
        }

        // Default: no-op
        return YdlRecommendation::noOp('Inconclusive state — review required');
    }

    private function recommendFix(YdlStateDefinition $state, string $reason): YdlRecommendation
    {
        return YdlRecommendation::fromState(
            state,
            YdlRecommendation::ACTION_FIX,
            $state->sprint,
            $reason,
            false,
            ['gates_fail' => $state->gatesFail, 'blocks_current' => true],
        );
    }

    private function recommendStop(YdlStateDefinition $state, string $reason): YdlRecommendation
    {
        return YdlRecommendation::fromState(
            state,
            YdlRecommendation::ACTION_STOP,
            $state->sprint,
            $reason,
            false,
            [],
        );
    }

    private function recommendStart(YdlStateDefinition $state, BlockerEvaluationResult $blockerResult): YdlRecommendation
    {
        return YdlRecommendation::fromState(
            state,
            YdlRecommendation::ACTION_START,
            'YDL_V1',
            'All development gates PASS. External blockers only (' . $blockerResult->summary() . '). Independent work allowed.',
            true,
            ['gates_pass' => $state->gatesPass, 'external_blocked' => $state->gatesBlockedExternal],
        );
    }

    private function recommendNoOp(YdlStateDefinition $state, string $reason): YdlRecommendation
    {
        return YdlRecommendation::noOp($reason);
    }
}
