<?php

namespace App\Services\Ydl;

use App\DTOs\Ydl\YdlBlocker;
use App\DTOs\Ydl\YdlContextOutput;
use App\DTOs\Ydl\YdlStateDefinition;
use Illuminate\Support\Facades\File;

/**
 * YdlContextReader — Reads YDL state and produces agent-readable context.
 *
 * YDL v1 Phase 3
 *
 * Reads:
 *   - memory/ydl/state/current.json
 *   - memory/ydl/blockers.json
 *
 * Produces:
 *   - YdlContextOutput (structured DTO)
 *   - Markdown for agent prompt injection
 *
 * Deterministic. No LLM inference.
 */
class YdlContextReader
{
    private string $basePath;
    private string $statePath;
    private string $blockerPath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath    = $basePath ?? base_path();
        $this->statePath   = $this->basePath . '/memory/ydl/state/current.json';
        $this->blockerPath = $this->basePath . '/memory/ydl/blockers.json';
    }

    /**
     * Read current YDL state and produce a structured context output.
     */
    public function read(): YdlContextOutput
    {
        $state = $this->loadState();
        $blockers = $this->loadActiveBlockers();

        if ($state === null) {
            return YdlContextOutput::empty();
        }

        $authorityLevel = $this->computeAuthorityLevel($state, $blockers);

        $blockerSummaries = array_map(fn(YdlBlocker $b) => [
            'id'                  => $b->id,
            'gate'                => $b->gate,
            'type'                => $b->type,
            'owner'               => $b->owner,
            'development_action'  => $b->developmentAction,
        ], $blockers);

        return new YdlContextOutput(
            sprint:                   $state['active_sprint']['id'] ?? '',
            sprintStatus:             $state['active_sprint']['status'] ?? '',
            recommendationAction:      $state['recommendation']['action'] ?? '',
            recommendationTarget:      $state['recommendation']['target'] ?? '',
            recommendationRationale:   $state['recommendation']['rationale'] ?? '',
            confidence:                $state['recommendation']['confidence'] ?? '',
            activeBlockers:           $blockerSummaries,
            authorityLevel:           $authorityLevel['level'],
            authorityRationale:       $authorityLevel['rationale'],
            gitBranch:               $state['git']['branch'] ?? '',
            gitCommit:               $state['git']['commit'] ?? '',
            sabStatus:               $this->computeSabStatus($state),
            sabViolationsNew:        $state['sab']['new_violations'] ?? 0,
            sabViolationsBlocking:     $state['sab']['blocking_violations'] ?? 0,
            lastUpdated:             $state['updated'] ?? '',
            snapshotId:             $state['active_sprint']['id'] ?? '',
        );
    }

    /**
     * Output Markdown suitable for agent prompt injection.
     */
    public function toMarkdown(): string
    {
        return $this->read()->toMarkdown();
    }

    /**
     * Output JSON for machine parsing.
     */
    public function toJson(): string
    {
        return json_encode($this->read()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Output only authority summary (minimal context for quick checks).
     */
    public function toAuthoritySummary(): string
    {
        $ctx = $this->read();

        if ($ctx->sprint === '') {
            return "YDL: No active sprint\n";
        }

        $lines = [
            "YDL — {$ctx->sprint}",
            "Status: {$ctx->sprintStatus}",
            "Authority: {$ctx->authorityLevel} — {$ctx->authorityRationale}",
            "Sıradaki: {$ctx->recommendationAction} → {$ctx->recommendationTarget}",
        ];

        if (count($ctx->activeBlockers) > 0) {
            $lines[] = '';
            $lines[] = 'Blockers:';
            foreach ($ctx->activeBlockers as $b) {
                $lines[] = "  {$b['id']} [{$b['gate']}] {$b['type']} — {$b['owner']}";
            }
        }

        return implode("\n", $lines) . "\n";
    }

    // ─────────────────────────────────────────────────────────────────
    // Private
    // ─────────────────────────────────────────────────────────────────

    private function loadState(): ?array
    {
        if (!File::exists($this->statePath)) {
            return null;
        }

        $raw = File::get($this->statePath);
        return json_decode($raw, true) ?: null;
    }

    /** @return YdlBlocker[] */
    private function loadActiveBlockers(): array
    {
        if (!File::exists($this->blockerPath)) {
            return [];
        }

        $raw = File::get($this->blockerPath);
        $data = json_decode($raw, true) ?: [];

        $blockers = [];
        foreach ($data['blockers'] ?? [] as $entry) {
            $blocker = YdlBlocker::fromArray($entry);
            if ($blocker->isActive()) {
                $blockers[] = $blocker;
            }
        }

        return $blockers;
    }

    /**
     * Compute authority level based on sprint status and active blockers.
     *
     * @param array|null $state
     * @param YdlBlocker[] $blockers
     * @return array{level: string, rationale: string}
     */
    private function computeAuthorityLevel(?array $state, array $blockers): array
    {
        if ($state === null || ($state['active_sprint']['id'] ?? '') === '') {
            return [
                'level'     => YdlContextOutput::AUTHORITY_NO_SPRINT,
                'rationale' => 'No active sprint — run php artisan ydl:state to initialize',
            ];
        }

        // SECURITY blockers → STOP
        $securityBlockers = array_filter(
            $blockers,
            fn(YdlBlocker $b) => $b->requiresStop()
        );

        if (count($securityBlockers) > 0) {
            $ids = implode(', ', array_map(fn($b) => $b->id, $securityBlockers));
            return [
                'level'     => YdlContextOutput::AUTHORITY_STOP,
                'rationale' => "Security blocker(s): {$ids} — tüm geliştirme durduruldu",
            ];
        }

        // Internal blockers that prevent all development
        $internalBlockers = array_filter(
            $blockers,
            fn(YdlBlocker $b) => $b->type === YdlBlocker::TYPE_INTERNAL_BLOCKER
                && !$b->allowsParallelWork()
        );

        if (count($internalBlockers) > 0) {
            $ids = implode(', ', array_map(fn($b) => $b->id, $internalBlockers));
            return [
                'level'     => YdlContextOutput::AUTHORITY_STOP,
                'rationale' => "Internal blocker(s): {$ids} — üretim kapıları bloke",
            ];
        }

        // Check if any blocker says DO_NOT_CONTINUE or STOP_IMMEDIATELY (prefix match)
        // Note: blockers.json stores values like "DO_NOT_CONTINUE_BOOKING_CODE", "STOP_IMMEDIATELY"
        // so we use str_starts_with for safe comparison
        $recommendationTarget = $state['recommendation']['target'] ?? '';
        $blockedByStopAction = false;
        foreach ($blockers as $blocker) {
            $action = $blocker->developmentAction;
            if (str_starts_with($action, 'DO_NOT_CONTINUE')
                || str_starts_with($action, 'STOP_IMMEDIATELY')) {
                $blockedByStopAction = true;
                break;
            }
        }

        if ($blockedByStopAction) {
            $stoppedBlockers = array_filter(
                $blockers,
                fn(YdlBlocker $b) => str_starts_with($b->developmentAction, 'DO_NOT_CONTINUE')
                    || str_starts_with($b->developmentAction, 'STOP_IMMEDIATELY')
            );
            $ids = implode(', ', array_map(fn($b) => $b->id, $stoppedBlockers));
            $firstBlocker = reset($stoppedBlockers);
            $action = $firstBlocker !== false
                ? $firstBlocker->developmentAction
                : 'DO_NOT_CONTINUE';
            return [
                'level'     => YdlContextOutput::AUTHORITY_LIMITED_BY_BLOCKER,
                'rationale' => "{$ids}: {$action} — bağımsız iş yapılabilir",
            ];
        }

        // Parallel work allowed but with active blockers
        if (count($blockers) > 0) {
            $ids = implode(', ', array_map(fn($b) => $b->id, $blockers));
            return [
                'level'     => YdlContextOutput::AUTHORITY_LIMITED_BY_BLOCKER,
                'rationale' => "{$ids} aktif — paralel iş yapılabilir",
            ];
        }

        return [
            'level'     => YdlContextOutput::AUTHORITY_FULL,
            'rationale' => 'Blokör yok, üretim serbest',
        ];
    }

    private function computeSabStatus(array $state): string
    {
        $new = $state['sab']['new_violations'] ?? 0;
        $blocking = $state['sab']['blocking_violations'] ?? 0;

        if ($blocking > 0) {
            return YdlContextOutput::SAB_VIOLATIONS;
        }

        if ($new > 0) {
            return YdlContextOutput::SAB_WARNINGS;
        }

        return YdlContextOutput::SAB_CLEAN;
    }
}
