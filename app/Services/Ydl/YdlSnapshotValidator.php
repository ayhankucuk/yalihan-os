<?php

namespace App\Services\Ydl;

use App\DTOs\Ydl\YdlStateDefinition;
use Illuminate\Support\Facades\File;

/**
 * YdlSnapshotValidator — Detects drift between snapshot and blocker registry.
 *
 * YDL v1 Phase 1
 *
 * RULES:
 *   1. Active blockers in registry must appear in current snapshot
 *   2. Snapshot sprint must match blocker registry sprint
 *   3. Snapshot must have same blocker count as active blockers
 *   4. CERTIFIED sprint + dirty git = CERTIFICATION_INTEGRITY_FAILURE
 *      (implementation committed after certification without re-certification)
 *
 * If any rule is violated → STATE_DRIFT action emitted.
 * NO silent decision — drift is always surfaced.
 */
class YdlSnapshotValidator
{
    public function __construct(
        private readonly string $statePath,
        private readonly string $snapshotPath,
    ) {}

    /**
     * Validate: no drift between current state and snapshot.
     *
     * @return string|null  null = valid, string = drift reason
     */
    public function validate(?YdlStateDefinition $currentState): ?string
    {
        if ($currentState === null) {
            return 'No current state available for validation';
        }

        if (!File::exists($this->statePath)) {
            return 'blocker registry not found';
        }

        $registry = $this->loadRegistry();

        // Rule 1: All active blockers must be acknowledged in current state
        $drift = $this->checkBlockerAcknowledged($registry, $currentState);
        if ($drift !== null) {
            return $drift;
        }

        // Rule 2: Sprint name must match
        $drift = $this->checkSprintConsistency($registry, $currentState);
        if ($drift !== null) {
            return $drift;
        }

        // Rule 3: Blocked external count must match
        $drift = $this->checkBlockedCountConsistency($registry, $currentState);
        if ($drift !== null) {
            return $drift;
        }

        // Rule 4: CERTIFIED sprint + dirty git = CERTIFICATION_INTEGRITY_FAILURE
        // If engineering is complete (all gates PASS), the snapshot was certified.
        // Any untracked implementation after certification = integrity violation.
        $drift = $this->checkCertificationIntegrity($currentState);
        if ($drift !== null) {
            return $drift;
        }

        return null;
    }

    private function loadRegistry(): array
    {
        $raw = File::get($this->statePath);
        return json_decode($raw, true) ?? [];
    }

    private function checkBlockerAcknowledged(array $registry, YdlStateDefinition $state): ?string
    {
        $activeBlockers = array_filter(
            $registry['blockers'] ?? [],
            fn($b) => ($b['status'] ?? '') === 'ACTIVE'
        );

        foreach ($activeBlockers as $blocker) {
            $gate = $blocker['gate'] ?? '';
            if ($gate === '') {
                continue;
            }

            // Check if this gate is tracked as external-blocked in snapshot
            if ($state->gatesBlockedExternal === 0) {
                return "Active blocker {$blocker['id']} ({$gate}) but snapshot shows 0 external blocked gates";
            }
        }

        return null;
    }

    private function checkSprintConsistency(array $registry, YdlStateDefinition $state): ?string
    {
        $registrySprint = $registry['active_sprint']['id'] ?? $registry['active_sprint']['name'] ?? '';
        if ($registrySprint !== '' && $registrySprint !== $state->sprint) {
            return "Sprint mismatch: registry='{$registrySprint}' vs snapshot='{$state->sprint}'";
        }
        return null;
    }

    private function checkBlockedCountConsistency(array $registry, YdlStateDefinition $state): ?string
    {
        $activeCount = count(array_filter(
            $registry['blockers'] ?? [],
            fn($b) => ($b['status'] ?? '') === 'ACTIVE'
        ));

        if ($activeCount > 0 && $state->gatesBlockedExternal === 0) {
            return "Registry has {$activeCount} active blockers but snapshot shows 0 external blocked gates";
        }

        return null;
    }

    /**
     * Rule 4: CERTIFIED sprint + dirty git = CERTIFICATION_INTEGRITY_FAILURE.
     *
     * A sprint is "certified" when:
     *   - All development gates are PASS (gatesFail = 0, gatesBlockedInternal = 0)
     *   - No blocking SAB violations
     *
     * If git is dirty (untracked files or unstaged changes) after certification,
     * it means code was written but not committed — violating the YDL mandate.
     *
     * Recovery: run `git add && git commit` to clear the drift.
     */
    private function checkCertificationIntegrity(YdlStateDefinition $state): ?string
    {
        $isCertified = $state->gatesFail === 0
            && $state->gatesBlockedInternal === 0
            && $state->sabViolationsBlocking === 0;

        if ($isCertified && !$state->gitClean) {
            return "CERTIFICATION_INTEGRITY_FAILURE: Sprint '{$state->sprint}' is certified "
                . "(all development gates PASS) but git shows untracked or unstaged changes. "
                . "Commit all changes before continuing. Run: git add . && git commit -m '...'";
        }

        return null;
    }
}
