<?php

namespace App\Services\Intelligence;

use App\DTOs\CortexFinding;
use App\Enums\FindingDecision;
use App\Models\GovernanceDecision;
use App\Services\Governance\GovernanceDashboardService;
use Illuminate\Support\Facades\Log;

/**
 * SabDecisionBridgeService — SAB2/SAB3 Decision Engine Bridge
 *
 * Converts CortexFinding DTOs into SAB proposal JSON files
 * compatible with the existing sab-watch.sh / sab-apply.sh pipeline.
 *
 * SAB3: Stores rollback snapshots, passes explanation/confidence,
 * handles apply failures with FAILED status, logs suppressed findings.
 */
class SabDecisionBridgeService
{
    public function __construct(
        private GovernanceDashboardService $dashboard,
        private GuardPolicyService $guard,
    ) {}

    /**
     * Process a batch of findings through the guard + bridge pipeline
     *
     * @param CortexFinding[] $findings
     * @return array{auto_run: string[], queued: int, blocked: int, suppressed: int, failed: int}
     */
    public function processBatch(array $findings): array
    {
        $classified = $this->guard->classifyBatch($findings);

        return $this->executeClassified($classified);
    }

    /**
     * SAB4: Execute pre-classified findings (used by ExecutionAgent).
     * Takes the output of GuardPolicyService::classifyBatch() and applies each bucket.
     *
     * @param array{auto_run: CortexFinding[], needs_review: CortexFinding[], blocked: CortexFinding[], suppressed: CortexFinding[]} $classified
     * @return array{auto_run: string[], queued: int, blocked: int, suppressed: int, failed: int}
     */
    public function executeClassified(array $classified): array
    {
        $created = [];
        $failedCount = 0;

        // Auto-run: create proposals in .sab/proposals/ for watcher pickup
        foreach ($classified['auto_run'] as $finding) {
            try {
                $filename = $this->createProposalFromFinding($finding);
                if ($filename) {
                    $created[] = $filename;
                }
            } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Silent catch: " . $e->getMessage());
                $failedCount++;
                $this->handleFailure($finding, $e);
            }
        }

        // Needs review: queue for approval UI (stored in DB via GovernanceDecision model)
        foreach ($classified['needs_review'] as $finding) {
            $this->queueForReview($finding);
        }

        // Blocked: log only, no action
        foreach ($classified['blocked'] as $finding) {
            $this->logBlocked($finding);
        }

        // SAB3: Log suppressed findings
        foreach ($classified['suppressed'] ?? [] as $finding) {
            $this->logSuppressed($finding);
        }

        $result = [
            'auto_run' => $created,
            'queued' => count($classified['needs_review']),
            'blocked' => count($classified['blocked']),
            'suppressed' => count($classified['suppressed'] ?? []),
            'failed' => $failedCount,
        ];

        Log::info('SabDecisionBridge: batch processed', $result);

        return $result;
    }

    /**
     * Process a single finding
     */
    public function processSingle(CortexFinding $finding): array
    {
        return $this->processBatch([$finding]);
    }

    /**
     * Create a SAB proposal JSON from a finding (for auto-run).
     * SAB3: Records as GovernanceDecision with rollback snapshot + explanation.
     */
    private function createProposalFromFinding(CortexFinding $finding): ?string
    {
        if ($this->isDuplicate($finding)) {
            Log::info('SabDecisionBridge: skipping duplicate auto_run finding', [
                'finding_id' => $finding->finding_id,
                'title' => $finding->title,
            ]);
            return null;
        }

        // SAB3: Capture before-state for rollback
        $beforeState = $this->captureBeforeState($finding);

        $filename = $this->dashboard->createProposal(
            $finding->target,
            $this->mapRecommendedActionToSabAction($finding->recommended_action),
            [
                'action' => $finding->recommended_action,
                'finding_title' => $finding->title,
                'finding_source' => $finding->source,
                'count' => $finding->meta['count'] ?? null,
            ],
            $finding->toProposalMeta()
        );

        if ($filename) {
            // SAB3: Create DB record with explanation + snapshot for auto-run too
            try {
                $decision = \App\Models\GovernanceDecision::create([
                    'finding_id' => $finding->finding_id,
                    'source' => $finding->source,
                    'domain' => $finding->domain,
                    'severity' => $finding->severity->value,
                    'title' => $finding->title,
                    'reason' => $finding->reason,
                    'target' => $finding->target,
                    'recommended_action' => $finding->recommended_action,
                    'risk' => $finding->risk,
                    'decision' => $finding->decision->value,
                    'karar_durumu' => 'auto_applied',
                    'karar_tarihi' => now(),
                    'proposal_filename' => $filename,
                    'meta' => $finding->meta,
                    // SAB3 fields
                    'explanation' => $finding->toExplanation(),
                    'signals' => $finding->signals,
                    'confidence' => $finding->confidence,
                    'rollback_snapshot' => $beforeState,
                    'timeline' => [[
                        'event' => 'auto_applied',
                        'user_id' => null,
                        'detail' => "Proposal: {$filename}",
                        'timestamp' => now()->toIso8601String(),
                    ]],
                ]);
            } catch (\Throwable $e) {
                Log::warning('SabDecisionBridge: auto-run DB record failed (proposal still created)', [
                    'finding_id' => $finding->finding_id,
                    'hata_mesaji' => $e->getMessage(),
                ]);
            }

            $this->dashboard->appendAuditLog(
                'AUTO_RUN',
                "Finding auto-run proposal created: {$finding->title} [{$finding->finding_id}] → {$filename}"
            );
        }

        return $filename;
    }

    /**
     * Queue a finding for operator review via GovernanceDecision model.
     * SAB3: Includes explanation, signals, confidence fields.
     */
    private function queueForReview(CortexFinding $finding): void
    {
        if ($this->isDuplicate($finding)) {
            Log::info('SabDecisionBridge: skipping duplicate needs_review finding', [
                'finding_id' => $finding->finding_id,
                'title' => $finding->title,
            ]);
            return;
        }

        try {
            \App\Models\GovernanceDecision::create([
                'finding_id' => $finding->finding_id,
                'source' => $finding->source,
                'domain' => $finding->domain,
                'severity' => $finding->severity->value,
                'title' => $finding->title,
                'reason' => $finding->reason,
                'target' => $finding->target,
                'recommended_action' => $finding->recommended_action,
                'risk' => $finding->risk,
                'decision' => FindingDecision::NEEDS_REVIEW->value,
                'karar_durumu' => 'pending',
                'meta' => $finding->meta,
                // SAB3 fields
                'explanation' => $finding->toExplanation(),
                'signals' => $finding->signals,
                'confidence' => $finding->confidence,
                'timeline' => [[
                    'event' => 'queued_for_review',
                    'user_id' => null,
                    'detail' => "Severity: {$finding->severity->value}, Confidence: " . ($finding->confidence ?? 'N/A'),
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);

            $this->dashboard->appendAuditLog(
                'DECISION',
                "Finding queued for review: {$finding->title} [{$finding->finding_id}] severity={$finding->severity->value} confidence=" . ($finding->confidence ?? 'N/A')
            );
        } catch (\Throwable $e) {
            Log::error('SabDecisionBridge: failed to queue for review', [
                'finding_id' => $finding->finding_id,
                'hata_mesaji' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log a blocked finding (no automated action)
     */
    private function logBlocked(CortexFinding $finding): void
    {
        Log::channel('security')->warning('SabDecisionBridge: finding blocked by guard policy', [
            'finding_id' => $finding->finding_id,
            'title' => $finding->title,
            'severity' => $finding->severity->value,
            'source' => $finding->source,
            'target' => $finding->target,
        ]);

        // SAB3: Also store blocked findings in DB for complete history
        if ($this->isDuplicate($finding)) {
            Log::info('SabDecisionBridge: skipping duplicate blocked finding', [
                'finding_id' => $finding->finding_id,
                'title' => $finding->title,
            ]);
            return;
        }

        try {
            \App\Models\GovernanceDecision::create([
                'finding_id' => $finding->finding_id,
                'source' => $finding->source,
                'domain' => $finding->domain,
                'severity' => $finding->severity->value,
                'title' => $finding->title,
                'reason' => $finding->reason,
                'target' => $finding->target,
                'recommended_action' => $finding->recommended_action,
                'risk' => $finding->risk,
                'decision' => FindingDecision::BLOCKED->value,
                'karar_durumu' => 'blocked',
                'meta' => $finding->meta,
                'explanation' => $finding->toExplanation(),
                'signals' => $finding->signals,
                'confidence' => $finding->confidence,
                'timeline' => [[
                    'event' => 'blocked',
                    'user_id' => null,
                    'detail' => "Guard policy blocked: {$finding->severity->value}",
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Silent catch: " . $e->getMessage());
            // Non-critical: blocked record already in security log
        }

        $this->dashboard->appendAuditLog(
            'BLOCKED',
            "Finding blocked by guard: {$finding->title} [{$finding->finding_id}] severity={$finding->severity->value}"
        );
    }

    /**
     * SAB3: Log a suppressed finding
     */
    private function logSuppressed(CortexFinding $finding): void
    {
        $this->dashboard->appendAuditLog(
            'SUPPRESSED',
            "Finding suppressed: {$finding->title} [{$finding->finding_id}] source={$finding->source} domain={$finding->domain}"
        );
    }

    /**
     * SAB3: Handle proposal creation failure — mark as FAILED, prevent retry loop
     */
    private function handleFailure(CortexFinding $finding, \Throwable $e): void
    {
        Log::error('SabDecisionBridge: proposal creation failed', [
            'finding_id' => $finding->finding_id,
            'hata_mesaji' => $e->getMessage(),
        ]);

        try {
            \App\Models\GovernanceDecision::create([
                'finding_id' => $finding->finding_id,
                'source' => $finding->source,
                'domain' => $finding->domain,
                'severity' => $finding->severity->value,
                'title' => $finding->title,
                'reason' => $finding->reason,
                'target' => $finding->target,
                'recommended_action' => $finding->recommended_action,
                'risk' => $finding->risk,
                'decision' => $finding->decision->value,
                'karar_durumu' => 'failed',
                'karar_notu' => 'Auto-run failed: ' . $e->getMessage(),
                'meta' => $finding->meta,
                'explanation' => $finding->toExplanation(),
                'signals' => $finding->signals,
                'confidence' => $finding->confidence,
                'timeline' => [[
                    'event' => 'failed',
                    'user_id' => null,
                    'detail' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);
        } catch (\Throwable $dbErr) {
            Log::error('SabDecisionBridge: failed to record failure', [
                'finding_id' => $finding->finding_id,
                'hata_mesaji' => $dbErr->getMessage(),
            ]);
        }

        $this->dashboard->appendAuditLog(
            'FAILED',
            "Finding auto-run FAILED: {$finding->title} [{$finding->finding_id}] — {$e->getMessage()}"
        );
    }

    /**
     * SAB3: Capture before-state for rollback capability
     */
    private function captureBeforeState(CortexFinding $finding): array
    {
        $state = [
            'target' => $finding->target,
            'captured_at' => now()->toIso8601String(),
            'source' => $finding->source,
            'domain' => $finding->domain,
        ];

        // Check if target is a config/authority key
        if (str_contains($finding->target, '.')) {
            $configValue = config($finding->target);
            if ($configValue !== null) {
                $state['config_key'] = $finding->target;
                $state['config_value'] = $configValue;
            }
        }

        // Check if target is a .sab file
        $sabPath = base_path('.sab/' . $finding->target);
        if (file_exists($sabPath)) {
            $state['file_path'] = $sabPath;
            $state['file_hash'] = md5_file($sabPath);
        }

        return $state;
    }

    /**
     * Map recommended_action to SAB action type
     */
    private function mapRecommendedActionToSabAction(string $action): string
    {
        return match (true) {
            str_contains($action, 'cleanup') => 'update',
            str_contains($action, 'deassign') => 'update',
            str_contains($action, 'sync') => 'merge',
            str_contains($action, 'review') => 'update',
            str_contains($action, 'plan') => 'append',
            default => 'update',
        };
    }

    /**
     * Check if a duplicate active decision already exists for this finding.
     * Prevents the same source+domain+title from creating multiple records.
     */
    private function isDuplicate(CortexFinding $finding): bool
    {
        return GovernanceDecision::where('source', $finding->source)
            ->where('domain', $finding->domain)
            ->where('title', $finding->title)
            ->whereIn('karar_durumu', ['pending', 'blocked', 'auto_applied'])
            ->exists();
    }
}
