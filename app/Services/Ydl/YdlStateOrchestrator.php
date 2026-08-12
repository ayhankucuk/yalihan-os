<?php

namespace App\Services\Ydl;

use App\DTOs\Ydl\YdlRecommendation;
use App\DTOs\Ydl\YdlStateDefinition;
use Illuminate\Support\Facades\File;

/**
 * YdlStateOrchestrator — Single pipeline that runs all three engines.
 *
 * YDL v1 Phase 1
 *
 * Pipeline:
 *   1. YdlStateCollector → YdlStateDefinition
 *   2. YdlSnapshotValidator → drift check
 *   3. YdlBlockerEvaluator → blocker evaluation
 *   4. YdlNextBestActionEngine → YdlRecommendation
 *
 * Deterministic. No LLM inference.
 */
class YdlStateOrchestrator
{
    private readonly string $basePath;
    private readonly string $statePath;
    private readonly string $snapshotDir;
    private readonly string $blockerPath;

    public function __construct(
        ?string $basePath = null,
    ) {
        $this->basePath    = $basePath ?? base_path();
        $this->statePath   = $this->basePath . '/memory/ydl/state/current.json';
        $this->snapshotDir = $this->basePath . '/memory/ydl/snapshots';
        $this->blockerPath = $this->basePath . '/memory/ydl/blockers.json';
    }

    /**
     * Run the full YDL state pipeline.
     *
     * @return array{state: YdlStateDefinition, recommendation: YdlRecommendation, drift: string|null}
     */
    public function run(): array
    {
        // 1. Collect current state
        $collector = new YdlStateCollector($this->basePath);
        $state = $collector->collect();

        // 2. Validate snapshot integrity
        $validator = new YdlSnapshotValidator(
            statePath: $this->statePath,
            snapshotPath: $this->snapshotDir,
        );

        $drift = $validator->validate($state);

        // 3. Load blockers
        $blockerEvaluator = new YdlBlockerEvaluator($this->blockerPath);
        $blockerEvaluator->loadActiveBlockers();

        // 4. Decide next action
        $engine = new YdlNextBestActionEngine();
        $recommendation = $engine->decide($state, $blockerEvaluator->evaluate());

        // 5. Persist snapshot
        $this->persistSnapshot($state);

        // 6. Update current.json if not drifting
        if ($drift === null) {
            $this->updateStateFile($state, $recommendation);
        }

        return [
            'state'          => $state,
            'recommendation'  => $recommendation,
            'drift'          => $drift,
        ];
    }

    private function persistSnapshot(YdlStateDefinition $state): void
    {
        if (!File::isDirectory($this->snapshotDir)) {
            File::makeDirectory($this->snapshotDir, 0755, true);
        }

        $path = $this->snapshotDir . '/' . $state->snapshotId . '.json';
        File::put($path, json_encode($state->toArray(), JSON_PRETTY_PRINT));
    }

    private function updateStateFile(YdlStateDefinition $state, YdlRecommendation $recommendation): void
    {
        $existing = File::exists($this->statePath)
            ? json_decode(File::get($this->statePath), true)
            : [];

        $existing['active_sprint'] = [
            'id'                   => $state->sprint,
            'status'               => $state->sprintStatus,
            'certification_score'   => $state->certificationScore(),
             'tests'                => $state->testsPassed . '/' . ($state->testsPassed + $state->testsFailed) . ' PASS',
             'sab'                 => $state->sabViolationsNew === 0 ? 'CLEAN' : $state->sabViolationsNew . ' violations',
            'active_blockers'      => count(
                array_filter(
                    $existing['active_blockers'] ?? [],
                    fn($b) => ($b['status'] ?? '') === 'ACTIVE'
                )
            ),
        ];

        $existing['recommendation'] = [
            'action'               => $recommendation->action,
            'rationale'            => $recommendation->rationale,
            'confidence'           => $recommendation->confidence,
            'updated'              => now()->toIso8601String(),
        ];

        File::put($this->statePath, json_encode($existing, JSON_PRETTY_PRINT));
    }
}
