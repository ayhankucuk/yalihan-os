<?php

namespace App\Services\Ydl;

use App\DTOs\Ydl\YdlBlocker;
use Illuminate\Support\Facades\File;

/**
 * YdlBlockerEvaluator — Evaluates active blockers and determines safe development actions.
 *
 * YDL v1 Phase 1
 *
 * Deterministic decision table:
 *
 * | Blocker Type               | Parallel OK | Stop Required | Next Action    |
 * |----------------------------|------------|--------------|----------------|
 * | EXTERNAL_DEPENDENCY        | YES        | NO           | PARALLEL_OK    |
 * | INFRASTRUCTURE_ISSUE       | IF NOT BLK | NO           | FIX or PARALLEL |
 * | TEST_INSTABILITY          | NO         | NO           | FIX_REQUIRED   |
 * | SECURITY_ISSUE            | NO         | YES          | STOP_IMMEDIATELY|
 * | INTERNAL_BLOCKER          | NO         | NO           | FIX_REQUIRED   |
 *
 * NO LLM inference — purely deterministic classification.
 */
class YdlBlockerEvaluator
{
    private array $activeBlockers = [];

    public function __construct(private readonly string $blockerPath) {}

    /**
     * Load all active blockers from registry.
     *
     * @return YdlBlocker[]
     */
    public function loadActiveBlockers(): array
    {
        if (!File::exists($this->blockerPath)) {
            return [];
        }

        $raw = File::get($this->blockerPath);
        $data = json_decode($raw, true) ?? [];

        $blockers = [];
        foreach ($data['blockers'] ?? [] as $entry) {
            $blocker = YdlBlocker::fromArray($entry);
            if ($blocker->isActive()) {
                $blockers[] = $blocker;
            }
        }

        $this->activeBlockers = $blockers;
        return $blockers;
    }

    /**
     * Evaluate all active blockers and return combined decision.
     */
    public function evaluate(): BlockerEvaluationResult
    {
        $blockers = $this->activeBlockers;

        if (count($blockers) === 0) {
            return new BlockerEvaluationResult(
                hasActiveBlockers: false,
                blocksAllDevelopment: false,
                allowsParallelWork: true,
                blocksProductionGates: false,
                securityBlockers: [],
                internalBlockers: [],
                externalBlockers: [],
                infrastructureBlockers: [],
                recommendation: YdlBlocker::ACTION_PARALLEL_OK,
            );
        }

        $securityBlockers    = array_filter($blockers, fn($b) => $b->type === YdlBlocker::TYPE_SECURITY_ISSUE);
        $internalBlockers    = array_filter($blockers, fn($b) => $b->type === YdlBlocker::TYPE_INTERNAL_BLOCKER);
        $externalBlockers    = array_filter($blockers, fn($b) => $b->type === YdlBlocker::TYPE_EXTERNAL_DEPENDENCY);
        $infraBlockers       = array_filter($blockers, fn($b) => $b->type === YdlBlocker::TYPE_INFRASTRUCTURE_ISSUE);

        $blocksAll    = count($securityBlockers) > 0;
        $parallelOk   = count($securityBlockers) === 0
            && count($internalBlockers) === 0
            && empty(array_filter($infraBlockers, fn($b) => !$b->allowsParallelWork()));

        $blocksProduction = count($internalBlockers) > 0
            || count($securityBlockers) > 0;

        if ($blocksAll) {
            $rec = YdlBlocker::ACTION_STOP_IMMEDIATELY;
        } elseif (count($internalBlockers) > 0) {
            $rec = YdlBlocker::ACTION_FIX_REQUIRED;
        } else {
            $rec = YdlBlocker::ACTION_PARALLEL_OK;
        }

        return new BlockerEvaluationResult(
            hasActiveBlockers:     true,
            blocksAllDevelopment:  $blocksAll,
            allowsParallelWork:    $parallelOk,
            blocksProductionGates: $blocksProduction,
            securityBlockers:       $securityBlockers,
            internalBlockers:       $internalBlockers,
            externalBlockers:       $externalBlockers,
            infrastructureBlockers: $infraBlockers,
            recommendation:         $rec,
        );
    }

    public function blockerCount(): int
    {
        return count($this->activeBlockers);
    }
}
