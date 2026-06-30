<?php

namespace App\Services\Intelligence;

use App\Models\GovernanceDecision;
use App\Models\GovernanceRollback;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\Logging\LogService;

class RollbackService
{
    private string $proposalPath;

    public function __construct()
    {
        $this->proposalPath = base_path('.sab/proposals');
    }

    /**
     * Store a before-state snapshot for a decision (called before apply).
     */
    public function storeSnapshot(GovernanceDecision $decision, array $beforeState): void
    {
        $decision->update([
            'rollback_snapshot' => $beforeState,
        ]);

        $decision->addTimelineEvent('snapshot_stored', null, 'Before-state captured');
    }

    /**
     * Rollback a decision by its ID.
     */
    public function rollbackDecision(int $decisionId, string $reason, ?int $userId = null): GovernanceRollback
    {
        $decision = GovernanceDecision::findOrFail($decisionId);
        $userId = $userId ?? Auth::id();

        if (!$decision->isRollbackable()) {
            throw new \RuntimeException(
                "Decision #{$decisionId} is not rollbackable (durum: {$decision->karar_durumu}, snapshot: "
                . ($decision->rollback_snapshot ? 'yes' : 'no') . ')'
            );
        }

        $afterSnapshot = $this->captureCurrentState($decision);

        $rollback = GovernanceRollback::create([
            'decision_id' => $decision->id,
            'proposal_filename' => $decision->proposal_filename,
            'before_snapshot' => $decision->rollback_snapshot,
            'after_snapshot' => $afterSnapshot,
            'rollback_reason' => $reason,
            'rolled_back_by' => $userId,
            'rollback_durumu' => 'pending',
        ]);

        try {
            $this->executeRollback($decision, $rollback);
            $rollback->update(['rollback_durumu' => 'completed']);
            $decision->markRolledBack($userId, $reason);

            Log::channel('daily')->info('Rollback completed', [
                'decision_id' => $decisionId,
                'rollback_id' => $rollback->id,
                'reason' => $reason,
            ]);
        } catch (\Throwable $e) {
            $rollback->markFailed($e->getMessage());
            $decision->addTimelineEvent('rollback_failed', $userId, $e->getMessage());

            Log::channel('daily')->error('Rollback failed', [
                'decision_id' => $decisionId,
                'rollback_id' => $rollback->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $rollback;
    }

    /**
     * Execute the actual rollback (restore proposal file / revert state).
     */
    private function executeRollback(GovernanceDecision $decision, GovernanceRollback $rollback): void
    {
        // If a proposal file was created, remove it
        if ($decision->proposal_filename) {
            $filePath = $this->proposalPath . '/' . $decision->proposal_filename;

            if (File::exists($filePath)) {
                // Archive instead of delete
                $archivePath = base_path('.sab/rollbacks/' . $decision->proposal_filename);
                File::ensureDirectoryExists(dirname($archivePath));
                File::move($filePath, $archivePath);
            }
        }

        // Restore before-state if snapshot contains restorable actions
        $beforeSnapshot = $rollback->before_snapshot;

        if (!empty($beforeSnapshot['config_key']) && !empty($beforeSnapshot['config_value'])) {
            // Restore configuration value
            $key = $beforeSnapshot['config_key'];
            $value = $beforeSnapshot['config_value'];

            // Write restore proposal
            $restoreProposal = [
                'target' => $key,
                'action' => 'restore',
                'value' => $value,
                '_meta' => [
                    'reason' => "Rollback of decision #{$decision->id}: {$rollback->rollback_reason}",
                    'risk' => 'low',
                    'rule' => 'rollback_restore',
                    'engine' => 'rollback-service',
                    'rollback_id' => $rollback->id,
                    'decided_at' => now()->toIso8601String(),
                ],
            ];

            $restoreFilename = sprintf(
                'rollback-%d-%s.json',
                $decision->id,
                now()->format('Ymd-His')
            );

            File::ensureDirectoryExists($this->proposalPath);
            File::put(
                $this->proposalPath . '/' . $restoreFilename,
                json_encode($restoreProposal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }
    }

    /**
     * Capture current state for after_snapshot.
     */
    private function captureCurrentState(GovernanceDecision $decision): array
    {
        $state = [
            'karar_durumu' => $decision->karar_durumu,
            'captured_at' => now()->toIso8601String(),
        ];

        if ($decision->proposal_filename) {
            $filePath = $this->proposalPath . '/' . $decision->proposal_filename;
            $state['proposal_exists'] = File::exists($filePath);

            if (File::exists($filePath)) {
                $state['proposal_content'] = json_decode(File::get($filePath), true);
            }
        }

        return $state;
    }

    /**
     * Get rollback history for a decision.
     */
    public function getHistory(int $decisionId): \Illuminate\Database\Eloquent\Collection
    {
        return GovernanceRollback::forDecision($decisionId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Rollback all decisions within a time window.
     */
    public function rollbackByTimeWindow(string $from, string $to, string $reason, ?int $userId = null): array
    {
        $decisions = GovernanceDecision::where('karar_tarihi', '>=', $from)
            ->where('karar_tarihi', '<=', $to)
            ->whereIn('karar_durumu', ['approved', 'auto_applied'])
            ->whereNotNull('rollback_snapshot')
            ->get();

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($decisions as $decision) {
            try {
                $this->rollbackDecision($decision->id, $reason, $userId);
                $results['success']++;
            } catch (\Throwable $e) {
                LogService::error('RollbackByTimeWindow: rollback failed', [
                    'decision_id' => $decision->id,
                ], $e);

                $results['failed']++;
                $results['errors'][] = [
                    'decision_id' => $decision->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
