<?php

namespace App\Http\Controllers\Admin;

use App\Models\GovernanceDecision;
use App\Models\GovernanceSuppression;
use App\Services\Governance\GovernanceDashboardService;
use App\Services\Intelligence\CortexFindingService;
use App\Services\Intelligence\GuardPolicyService;
use App\Services\Intelligence\AutonomyService;
use App\Services\Intelligence\ActionFeedbackService;
use App\Services\Intelligence\OperatorIntelligenceService;
use App\Services\Intelligence\OptimizerService;
use App\Services\Intelligence\RollbackService;
use App\Services\Intelligence\SabDecisionBridgeService;
use App\Services\Intelligence\SuppressionService;
use App\Agents\WatcherAgent;
use App\Services\Logging\LogService;
use App\Models\AgentRun;
use App\Models\OptimizerSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * DecisionEngineController — SAB2/SAB3/SAB5/SAB6/SAB8
 *
 * Review queue, approval/rejection, rollback, suppression,
 * policy overrides, decision history, behavior control,
 * controlled autonomy, and action feedback loop.
 */
class DecisionEngineController extends AdminController
{
    public function __construct(
        private CortexFindingService $findingService,
        private SabDecisionBridgeService $bridge,
        private GuardPolicyService $guard,
        private GovernanceDashboardService $dashboard,
        private RollbackService $rollbackService,
        private SuppressionService $suppressionService,
        private WatcherAgent $watcherAgent,
        private OptimizerService $optimizerService,
        private OperatorIntelligenceService $operatorIntelligence,
        private AutonomyService $autonomyService,
        private ActionFeedbackService $feedbackService,
    ) {
        parent::__construct();
    }

    /**
     * Review queue — pending decisions awaiting operator approval
     */
    public function reviewQueue(Request $request)
    {
        $query = GovernanceDecision::pending()->latest();

        if ($request->filled('source')) {
            $query->bySource($request->input('source'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        $pending = $query->paginate(25);
        $counts = [
            'pending' => GovernanceDecision::pending()->count(),
            'approved' => GovernanceDecision::approved()->count(),
            'rejected' => GovernanceDecision::rejected()->count(),
            'auto_applied' => GovernanceDecision::autoApplied()->count(),
            'failed' => GovernanceDecision::failed()->count(),
            'rolled_back' => GovernanceDecision::rolledBack()->count(),
            'overridden' => GovernanceDecision::overridden()->count(),
        ];

        return view('admin.governance.review-queue', compact('pending', 'counts'));
    }

    /**
     * Decision detail — SAB8: includes loop summary
     */
    public function show(GovernanceDecision $decision)
    {
        $decision->load('kararVeren');
        $loopSummary = $this->feedbackService->getLoopSummary($decision);

        return view('admin.governance.decision-detail', compact('decision', 'loopSummary'));
    }

    /**
     * Approve a pending decision → create SAB proposal
     */
    public function approve(Request $request, GovernanceDecision $decision)
    {
        if ($decision->karar_durumu !== 'pending') {
            return back()->with('error', 'Bu karar zaten işlenmiş.');
        }

        $decision->approve(auth()->id(), $request->input('karar_notu'));

        // Create SAB proposal for the approved decision
        $filename = $this->dashboard->createProposal(
            $decision->target,
            'update',
            [
                'action' => $decision->recommended_action,
                'finding_title' => $decision->title,
                'finding_source' => $decision->source,
            ],
            [
                'reason' => $decision->reason,
                'risk' => $decision->risk,
                'rule' => $decision->source . '_' . $decision->domain,
                'engine' => 'cortex-decision-engine',
                'finding_id' => $decision->finding_id,
                'approved_by' => auth()->id(),
                'decision_mode' => 'manual_approval',
            ]
        );

        if ($filename) {
            $decision->markProposalCreated($filename);
        }

        $this->dashboard->appendAuditLog(
            'APPROVED',
            "Decision approved: {$decision->title} [{$decision->finding_id}] by user #{$decision->karar_veren_id}"
        );

        return redirect()->route('admin.governance.review-queue')
            ->with('success', "Karar onaylandı: {$decision->title}");
    }

    /**
     * Reject a pending decision
     */
    public function reject(Request $request, GovernanceDecision $decision)
    {
        if ($decision->karar_durumu !== 'pending') {
            return back()->with('error', 'Bu karar zaten işlenmiş.');
        }

        $decision->reject(auth()->id(), $request->input('karar_notu'));

        $this->dashboard->appendAuditLog(
            'REJECTED',
            "Decision rejected: {$decision->title} [{$decision->finding_id}] by user #{$decision->karar_veren_id}"
        );

        return redirect()->route('admin.governance.review-queue')
            ->with('success', "Karar reddedildi: {$decision->title}");
    }

    /**
     * Decision history — all resolved decisions
     */
    public function history(Request $request)
    {
        $query = GovernanceDecision::whereIn('karar_durumu', [
                'approved', 'rejected', 'auto_applied', 'failed', 'rolled_back', 'blocked',
            ])
            ->with('kararVeren')
            ->latest('karar_tarihi');

        if ($request->filled('karar_durumu')) {
            $query->where('karar_durumu', $request->input('karar_durumu'));
        }

        $decisions = $query->paginate(25);

        return view('admin.governance.decision-history', compact('decisions'));
    }

    /**
     * Trigger a finding scan via multi-agent pipeline (SAB4).
     * Cortex → Governance → Execution → Optimizer
     */
    public function scan()
    {
        $result = $this->watcherAgent->run([
            'skip_optimizer' => false,
        ]);

        if (!($result['success'] ?? false)) {
            return redirect()->route('admin.governance.review-queue')
                ->with('error', 'Tarama başarısız: ' . ($result['message'] ?? 'Pipeline hatası'));
        }

        $exec = $result['summary']['execution'] ?? [];
        $opt = $result['pipeline']['optimizer'] ?? [];

        return redirect()->route('admin.governance.review-queue')
            ->with('success', sprintf(
                'Multi-Agent tarama tamamlandı: %d bulgu, %d otomatik, %d inceleme, %d engellendi, %d bastırıldı, %d başarısız. Optimizer: %d öneri.',
                $result['findings_count'] ?? 0,
                $exec['auto_run'] ?? 0,
                $exec['queued'] ?? 0,
                $exec['blocked'] ?? 0,
                $exec['suppressed'] ?? 0,
                $exec['failed'] ?? 0,
                $opt['suggestions_count'] ?? 0
            ));
    }

    // ─── SAB4: Intelligence Center ─────────────────────────────

    /**
     * AI Intelligence Center — agent status, suggestions, learning metrics.
     */
    public function intelligenceCenter()
    {
        $agentHealth = $this->watcherAgent->pipelineHealth();
        $learningMetrics = $this->optimizerService->getLearningMetrics();
        $pendingSuggestions = OptimizerSuggestion::pending()
            ->orderByDesc('confidence')
            ->limit(20)
            ->get();
        $recentRuns = AgentRun::latest('started_at')
            ->limit(25)
            ->get();
        $appliedSuggestions = OptimizerSuggestion::applied()
            ->latest('applied_at')
            ->limit(10)
            ->get();

        // SAB5: Operator Intelligence data
        $overview = $this->operatorIntelligence->getSystemOverview();
        $liveFeed = $this->operatorIntelligence->getLiveDecisionFeed();
        $riskPanel = $this->operatorIntelligence->getRiskPanel();
        $systemMemory = $this->operatorIntelligence->getSystemMemory();
        $behaviorSettings = $this->operatorIntelligence->getBehaviorSettings();

        return view('admin.governance.intelligence-center', compact(
            'agentHealth',
            'learningMetrics',
            'pendingSuggestions',
            'recentRuns',
            'appliedSuggestions',
            'overview',
            'liveFeed',
            'riskPanel',
            'systemMemory',
            'behaviorSettings'
        ));
    }

    /**
     * Approve an optimizer suggestion.
     */
    public function approveSuggestion(OptimizerSuggestion $suggestion)
    {
        if (!$suggestion->isPending()) {
            return back()->with('error', 'Bu öneri zaten işlenmiş.');
        }

        $suggestion->approve(auth()->id());

        $this->dashboard->appendAuditLog(
            'SUGGESTION_APPROVED',
            "Optimizer suggestion #{$suggestion->id} approved: {$suggestion->target_rule} ({$suggestion->suggestion_type}) by user #" . auth()->id()
        );

        return back()->with('success', "Öneri onaylandı: {$suggestion->target_rule}");
    }

    /**
     * Reject an optimizer suggestion.
     */
    public function rejectSuggestion(OptimizerSuggestion $suggestion)
    {
        if (!$suggestion->isPending()) {
            return back()->with('error', 'Bu öneri zaten işlenmiş.');
        }

        $suggestion->reject(auth()->id());

        $this->dashboard->appendAuditLog(
            'SUGGESTION_REJECTED',
            "Optimizer suggestion #{$suggestion->id} rejected: {$suggestion->target_rule} by user #" . auth()->id()
        );

        return back()->with('success', "Öneri reddedildi: {$suggestion->target_rule}");
    }

    // ─── SAB3: Rollback ────────────────────────────────────────

    /**
     * Rollback a decision (undo its effect).
     */
    public function rollback(Request $request, GovernanceDecision $decision)
    {
        $request->validate([
            'rollback_reason' => 'required|string|max:500',
        ]);

        if (!$decision->isRollbackable()) {
            return back()->with('error', 'Bu karar geri alınamaz (snapshot yok veya uygun durumda değil).');
        }

        try {
            $rollback = $this->rollbackService->rollbackDecision(
                $decision->id,
                $request->input('rollback_reason'),
                auth()->id()
            );

            $this->dashboard->appendAuditLog(
                'ROLLBACK_EXECUTED',
                "Decision #{$decision->id} rolled back by user #{$rollback->rolled_back_by}: {$request->input('rollback_reason')}"
            );

            return redirect()->route('admin.governance.decision-detail', $decision)
                ->with('success', 'Karar başarıyla geri alındı.');
        } catch (\Throwable $e) {
            LogService::error('DecisionEngine: rollback failed', [
                'decision_id' => $decision->id,
            ], $e);
            return back()->with('error', 'Rollback başarısız: ' . $e->getMessage());
        }
    }

    // ─── SAB3: Suppression ─────────────────────────────────────

    /**
     * Suppress a decision's finding rule (prevent future matching).
     */
    public function suppress(Request $request, GovernanceDecision $decision)
    {
        $request->validate([
            'suppression_reason' => 'required|string|max:500',
            'suppression_scope' => 'required|in:global,source,domain',
            'suppression_expires_days' => 'nullable|integer|min:1|max:365',
        ]);

        $expiresAt = $request->filled('suppression_expires_days')
            ? now()->addDays($request->integer('suppression_expires_days'))
            : null;

        $ruleKey = $decision->source . '_' . $decision->domain;

        $suppression = $this->suppressionService->createSuppression([
            'rule_key' => $ruleKey,
            'scope' => $request->input('suppression_scope'),
            'source' => $decision->source,
            'domain' => $decision->domain,
            'reason' => $request->input('suppression_reason'),
            'suppressed_by' => auth()->id(),
            'expires_at' => $expiresAt,
        ]);

        $this->dashboard->appendAuditLog(
            'SUPPRESSED',
            "Rule suppressed: {$ruleKey} scope={$request->input('suppression_scope')} by user #" . auth()->id()
        );

        return redirect()->route('admin.governance.decision-detail', $decision)
            ->with('success', "Kural bastırıldı: {$ruleKey}");
    }

    /**
     * Suppression list page
     */
    public function suppressionList()
    {
        $active = $this->suppressionService->getActiveSuppressions();
        $expired = GovernanceSuppression::where('aktiflik_durumu', false)
            ->orderBy('updated_at', 'desc')
            ->limit(25)
            ->get();

        return view('admin.governance.suppression-list', compact('active', 'expired'));
    }

    /**
     * Remove a suppression rule
     */
    public function removeSuppression(GovernanceSuppression $suppression)
    {
        $ruleKey = $suppression->rule_key;
        $this->suppressionService->removeSuppression($suppression->id);

        $this->dashboard->appendAuditLog(
            'SUPPRESSION_REMOVED',
            "Suppression removed: {$ruleKey} by user #" . auth()->id()
        );

        return redirect()->route('admin.governance.suppression-list')
            ->with('success', "Bastırma kuralı kaldırıldı: {$ruleKey}");
    }

    // ─── SAB3: Policy Override ─────────────────────────────────

    /**
     * Override a decision (force auto_run, needs_review, or block).
     */
    public function override(Request $request, GovernanceDecision $decision)
    {
        $request->validate([
            'override_decision' => 'required|in:auto_run,needs_review,blocked',
            'override_reason' => 'required|string|max:500',
        ]);

        $decision->applyOverride(
            $request->input('override_decision'),
            $request->input('override_reason'),
            auth()->id()
        );

        $this->dashboard->appendAuditLog(
            'OVERRIDE_APPLIED',
            "Decision #{$decision->id} overridden to {$request->input('override_decision')} by user #" . auth()->id() . ": {$request->input('override_reason')}"
        );

        // If override is auto_run, create proposal
        if ($request->input('override_decision') === 'auto_run') {
            $filename = $this->dashboard->createProposal(
                $decision->target,
                'update',
                [
                    'action' => $decision->recommended_action,
                    'finding_title' => $decision->title,
                    'finding_source' => $decision->source,
                ],
                [
                    'reason' => $decision->reason,
                    'risk' => $decision->risk,
                    'rule' => $decision->source . '_' . $decision->domain,
                    'engine' => 'cortex-decision-engine',
                    'finding_id' => $decision->finding_id,
                    'override_by' => auth()->id(),
                    'decision_mode' => 'policy_override',
                ]
            );

            if ($filename) {
                $decision->markProposalCreated($filename);
            }
        }

        return redirect()->route('admin.governance.decision-detail', $decision)
            ->with('success', "Karar override edildi: {$request->input('override_decision')}");
    }

    // ─── SAB5: Behavior Control ────────────────────────────────

    /**
     * Toggle safe mode on/off.
     */
    public function toggleSafeMode(Request $request)
    {
        $currentValue = config('governance.safe_mode', false);
        $newValue = !$currentValue;

        $this->updateEnvValue('AI_SAFE_MODE', $newValue ? 'true' : 'false');

        $this->operatorIntelligence->invalidateCache();

        $this->dashboard->appendAuditLog(
            $newValue ? 'SAFE_MODE_ENABLED' : 'SAFE_MODE_DISABLED',
            'Safe mode ' . ($newValue ? 'enabled' : 'disabled') . ' by user #' . auth()->id()
        );

        return redirect()->route('admin.governance.intelligence-center')
            ->with('success', 'Güvenli Mod ' . ($newValue ? 'AKTİF' : 'DEVRE DIŞI') . ' edildi.');
    }

    /**
     * Update AI behavior parameters.
     */
    public function updateBehavior(Request $request)
    {
        $validated = $request->validate([
            'auto_run_threshold' => 'required|in:none,low,medium,high',
            'risk_tolerance' => 'required|in:low,medium,high',
            'confidence_minimum' => 'required|numeric|min:0|max:1',
            'max_daily_actions' => 'required|integer|min:0|max:500',
        ]);

        $this->updateEnvValue('AI_AUTO_RUN_THRESHOLD', $validated['auto_run_threshold']);
        $this->updateEnvValue('AI_RISK_TOLERANCE', $validated['risk_tolerance']);
        $this->updateEnvValue('AI_CONFIDENCE_MINIMUM', (string) $validated['confidence_minimum']);
        $this->updateEnvValue('AI_MAX_DAILY_ACTIONS', (string) $validated['max_daily_actions']);

        $this->operatorIntelligence->invalidateCache();

        $this->dashboard->appendAuditLog(
            'BEHAVIOR_UPDATED',
            sprintf(
                'AI behavior updated by user #%d: threshold=%s, tolerance=%s, confidence=%s, max=%s',
                auth()->id(),
                $validated['auto_run_threshold'],
                $validated['risk_tolerance'],
                $validated['confidence_minimum'],
                $validated['max_daily_actions']
            )
        );

        return redirect()->route('admin.governance.intelligence-center')
            ->with('success', 'AI davranış parametreleri güncellendi.');
    }

    /**
     * Update a single .env variable.
     */
    private function updateEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        if (preg_match("/^{$key}=.*/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($envPath, $content);
    }

    // ─── SAB6: Controlled Autonomy ─────────────────────────────

    /**
     * Autonomy control panel page.
     */
    public function autonomyPanel()
    {
        $autonomyStatus = $this->autonomyService->getAutonomyStatus();
        $dryRunLog = $this->autonomyService->getDryRunLog();

        return view('admin.governance.autonomy-panel', compact(
            'autonomyStatus',
            'dryRunLog'
        ));
    }

    /**
     * Update autonomy level.
     */
    public function updateAutonomyLevel(Request $request)
    {
        $validated = $request->validate([
            'autonomy_level' => 'required|integer|min:0|max:4',
        ]);

        $oldLevel = config('governance.autonomy_level', 2);
        $newLevel = $validated['autonomy_level'];

        $this->updateEnvValue('AI_AUTONOMY_LEVEL', (string) $newLevel);
        $this->operatorIntelligence->invalidateCache();

        $this->dashboard->appendAuditLog(
            'AUTONOMY_LEVEL_CHANGED',
            sprintf(
                'Autonomy level changed from %d to %d by user #%d',
                $oldLevel,
                $newLevel,
                auth()->id()
            )
        );

        Log::channel('security')->info('SAB6: Autonomy level changed', [
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('admin.governance.autonomy-panel')
            ->with('success', sprintf(
                'Otonom seviye %d → %d olarak güncellendi: %s',
                $oldLevel,
                $newLevel,
                $this->autonomyService->getAutonomyLevelLabel($newLevel)
            ));
    }

    /**
     * Pause all autonomous operations (STOP AI).
     */
    public function pauseSystem()
    {
        $this->autonomyService->pauseSystem(auth()->id());
        $this->operatorIntelligence->invalidateCache();

        $this->dashboard->appendAuditLog(
            'SYSTEM_PAUSED',
            'All autonomous operations PAUSED by user #' . auth()->id()
        );

        return redirect()->route('admin.governance.autonomy-panel')
            ->with('success', 'Tüm otonom operasyonlar DURDURULDU.');
    }

    /**
     * Resume autonomous operations.
     */
    public function resumeSystem()
    {
        $this->autonomyService->resumeSystem(auth()->id());
        $this->operatorIntelligence->invalidateCache();

        $this->dashboard->appendAuditLog(
            'SYSTEM_RESUMED',
            'Autonomous operations RESUMED by user #' . auth()->id()
        );

        return redirect()->route('admin.governance.autonomy-panel')
            ->with('success', 'Otonom operasyonlar devam ettiriliyor.');
    }

    /**
     * Toggle dry-run mode.
     */
    public function toggleDryRun()
    {
        $currentValue = config('governance.dry_run', false);
        $newValue = !$currentValue;

        $this->updateEnvValue('AI_DRY_RUN', $newValue ? 'true' : 'false');
        $this->operatorIntelligence->invalidateCache();

        $this->dashboard->appendAuditLog(
            $newValue ? 'DRY_RUN_ENABLED' : 'DRY_RUN_DISABLED',
            'Dry-run mode ' . ($newValue ? 'enabled' : 'disabled') . ' by user #' . auth()->id()
        );

        return redirect()->route('admin.governance.autonomy-panel')
            ->with('success', 'Simülasyon modu ' . ($newValue ? 'AKTİF' : 'DEVRE DIŞI') . ' edildi.');
    }

    /**
     * Update action budget limits.
     */
    public function updateActionBudget(Request $request)
    {
        $validated = $request->validate([
            'max_actions_per_hour' => 'required|integer|min:1|max:100',
            'max_actions_per_day' => 'required|integer|min:1|max:1000',
        ]);

        $this->updateEnvValue('AI_MAX_ACTIONS_PER_HOUR', (string) $validated['max_actions_per_hour']);
        $this->updateEnvValue('AI_MAX_ACTIONS_PER_DAY', (string) $validated['max_actions_per_day']);
        $this->operatorIntelligence->invalidateCache();

        $this->dashboard->appendAuditLog(
            'ACTION_BUDGET_UPDATED',
            sprintf(
                'Action budget updated by user #%d: %d/hour, %d/day',
                auth()->id(),
                $validated['max_actions_per_hour'],
                $validated['max_actions_per_day']
            )
        );

        return redirect()->route('admin.governance.autonomy-panel')
            ->with('success', 'Aksiyon bütçesi güncellendi.');
    }

    // ─── SAB8: Decision → Action → Feedback Loop ─────────────

    /**
     * SAB8: Record action result for a decision.
     */
    public function recordResult(Request $request, GovernanceDecision $decision)
    {
        $validated = $request->validate([
            'success' => 'required|boolean',
            'changed_fields' => 'nullable|array',
            'result_summary' => 'nullable|string|max:500',
            'impact_score' => 'nullable|integer|min:-100|max:100',
        ]);

        $this->feedbackService->recordActionResult(
            $decision,
            (bool) $validated['success'],
            $validated['changed_fields'] ?? [],
            $validated['result_summary'] ?? null,
            $validated['impact_score'] ?? null,
        );

        return back()->with('success', 'Aksiyon sonucu kaydedildi.');
    }

    /**
     * SAB8: Add operator feedback to a decision.
     */
    public function addFeedback(Request $request, GovernanceDecision $decision)
    {
        $validated = $request->validate([
            'feedback_note' => 'required|string|max:500',
        ]);

        $decision->addFeedback($validated['feedback_note'], auth()->id());

        $this->dashboard->appendAuditLog(
            'FEEDBACK_ADDED',
            "Feedback added to decision [{$decision->finding_id}] by user #" . auth()->id()
        );

        return back()->with('success', 'Geri bildiriminiz kaydedildi.');
    }

    /**
     * SAB8: Action loop dashboard — stats, tabs, recent results.
     */
    public function actionDashboard(Request $request)
    {
        $period = $request->input('period', '30d');
        $tab = $request->input('tab', 'all');

        $stats = $this->feedbackService->getActionStats($period);
        $decisions = $this->feedbackService->getByTab($tab);
        $recentResults = $this->feedbackService->getRecentResults(10);

        return view('admin.governance.action-dashboard', compact(
            'stats', 'decisions', 'recentResults', 'period', 'tab'
        ));
    }

    /**
     * SAB8: Simulate action (dry-run for single decision).
     */
    public function simulateAction(GovernanceDecision $decision)
    {
        if (!in_array($decision->karar_durumu, ['pending', 'blocked'])) {
            return back()->with('error', 'Sadece bekleyen veya engellenmiş kararlar simüle edilebilir.');
        }

        if ($decision->hasResult()) {
            return back()->with('error', 'Bu karar zaten sonuçlandırılmış, tekrar simüle edilemez.');
        }

        $decision->addTimelineEvent('simulated', auth()->id(), 'Dry-run simulation by operator');

        $this->dashboard->appendAuditLog(
            'SIMULATED',
            "Decision simulated: [{$decision->finding_id}] by user #" . auth()->id()
        );

        return back()->with('success', 'Karar simüle edildi (dry-run).');
    }
}
