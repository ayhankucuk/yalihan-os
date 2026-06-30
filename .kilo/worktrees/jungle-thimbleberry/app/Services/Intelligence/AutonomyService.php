<?php

namespace App\Services\Intelligence;

use App\Models\GovernanceDecision;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AutonomyService — SAB6 Controlled Autonomy Engine
 *
 * Manages autonomy levels, action budgets, safe/blocked zones,
 * anomaly detection, dry-run simulation, and human interrupt.
 *
 * Autonomy Levels:
 *   0 → Manual only (all findings queued for review)
 *   1 → Suggest only (no auto-run, suggestions created)
 *   2 → Auto-run LOW risk only (default, safe start)
 *   3 → Auto-run LOW + MEDIUM risk
 *   4 → Full autonomy (restricted — HIGH still blocked)
 *
 * CRITICAL never auto-runs regardless of level.
 */
class AutonomyService
{
    private const CACHE_KEY_ACTIONS_HOUR = 'sab6:actions_this_hour';
    private const CACHE_KEY_ACTIONS_DAY = 'sab6:actions_today';
    private const CACHE_KEY_PAUSED = 'sab6:system_paused';
    private const CACHE_KEY_DRY_RUN_LOG = 'sab6:dry_run_log';

    /**
     * Check if autonomous action is allowed for given severity and domain.
     *
     * @return array{allowed: bool, reason: string, dry_run: bool}
     */
    public function canAutoRun(string $severity, string $domain, float $confidence = 1.0): array
    {
        // Check 1: System paused (human interrupt)
        if ($this->isSystemPaused()) {
            return ['allowed' => false, 'reason' => 'Sistem durduruldu (STOP AI aktif)', 'dry_run' => false];
        }

        // Check 2: Safe mode
        if (config('governance.safe_mode', false)) {
            return ['allowed' => false, 'reason' => 'Güvenli mod aktif', 'dry_run' => false];
        }

        // Check 3: Blocked zone
        if ($this->isBlockedZone($domain)) {
            return ['allowed' => false, 'reason' => "Engelli bölge: {$domain}", 'dry_run' => false];
        }

        // Check 4: Autonomy level check
        $level = $this->getAutonomyLevel();
        $levelCheck = $this->checkAutonomyLevel($level, $severity);
        if (!$levelCheck['allowed']) {
            return $levelCheck;
        }

        // Check 5: Safe zone check (level < 4 requires safe zone)
        if ($level < 4 && !$this->isSafeZone($domain)) {
            return ['allowed' => false, 'reason' => "Güvenli bölge dışı: {$domain} (seviye {$level} güvenli bölge gerektirir)", 'dry_run' => false];
        }

        // Check 6: Confidence minimum
        $minConfidence = config('governance.confidence_minimum', 0.5);
        if ($confidence < $minConfidence) {
            return ['allowed' => false, 'reason' => "Güven skoru düşük: {$confidence} < {$minConfidence}", 'dry_run' => false];
        }

        // Check 7: Action budget
        $budgetCheck = $this->checkActionBudget();
        if (!$budgetCheck['allowed']) {
            return $budgetCheck;
        }

        // Check 8: Anomaly detection
        $anomalyCheck = $this->checkForAnomalies();
        if (!$anomalyCheck['allowed']) {
            return $anomalyCheck;
        }

        // Check 9: Dry run mode
        $dryRun = config('governance.dry_run', false);

        return ['allowed' => true, 'reason' => 'Otonom çalışmaya izin verildi', 'dry_run' => $dryRun];
    }

    /**
     * Record an autonomous action (increment budget counters).
     */
    public function recordAction(): void
    {
        $hourKey = self::CACHE_KEY_ACTIONS_HOUR . ':' . now()->format('Y-m-d-H');
        $dayKey = self::CACHE_KEY_ACTIONS_DAY . ':' . now()->format('Y-m-d');

        Cache::increment($hourKey);
        Cache::put($hourKey, Cache::get($hourKey, 1), 3600);

        Cache::increment($dayKey);
        Cache::put($dayKey, Cache::get($dayKey, 1), 86400);
    }

    /**
     * Get current action counts.
     */
    public function getActionCounts(): array
    {
        $hourKey = self::CACHE_KEY_ACTIONS_HOUR . ':' . now()->format('Y-m-d-H');
        $dayKey = self::CACHE_KEY_ACTIONS_DAY . ':' . now()->format('Y-m-d');

        return [
            'this_hour' => (int) Cache::get($hourKey, 0),
            'today' => (int) Cache::get($dayKey, 0),
            'max_per_hour' => config('governance.max_actions_per_hour', 20),
            'max_per_day' => config('governance.max_actions_per_day', 200),
        ];
    }

    /**
     * Get current autonomy level.
     */
    public function getAutonomyLevel(): int
    {
        return config('governance.autonomy_level', 2);
    }

    /**
     * Get autonomy level label.
     */
    public function getAutonomyLevelLabel(int $level): string
    {
        return match ($level) {
            0 => 'Manuel (Sadece İnsan)',
            1 => 'Sadece Öneri',
            2 => 'Düşük Risk Otonom',
            3 => 'Düşük + Orta Risk Otonom',
            4 => 'Tam Otonom (Kısıtlı)',
            default => 'Bilinmiyor',
        };
    }

    /**
     * Check autonomy level against severity.
     */
    private function checkAutonomyLevel(int $level, string $severity): array
    {
        // Level 0: nothing auto-runs
        if ($level === 0) {
            return ['allowed' => false, 'reason' => 'Otonom seviye 0: sadece manuel işlem', 'dry_run' => false];
        }

        // Level 1: suggest only, no auto-run
        if ($level === 1) {
            return ['allowed' => false, 'reason' => 'Otonom seviye 1: sadece öneri modu', 'dry_run' => false];
        }

        // CRITICAL never auto-runs
        if ($severity === 'critical') {
            return ['allowed' => false, 'reason' => 'Kritik ciddiyet: asla otomatik çalışmaz', 'dry_run' => false];
        }

        // HIGH always needs review (even at level 4)
        if ($severity === 'high') {
            return ['allowed' => false, 'reason' => 'Yüksek ciddiyet: insan onayı gerekli', 'dry_run' => false];
        }

        // Level 2: only LOW
        if ($level === 2 && $severity !== 'low') {
            return ['allowed' => false, 'reason' => "Otonom seviye 2: sadece düşük risk (mevcut: {$severity})", 'dry_run' => false];
        }

        // Level 3+: LOW and MEDIUM
        if ($level >= 3 && in_array($severity, ['low', 'medium'])) {
            return ['allowed' => true, 'reason' => 'Otonom seviye izin verdi', 'dry_run' => false];
        }

        // Level 2 + LOW
        if ($level === 2 && $severity === 'low') {
            return ['allowed' => true, 'reason' => 'Otonom seviye 2: düşük risk izinli', 'dry_run' => false];
        }

        return ['allowed' => false, 'reason' => 'Otonom seviye yetersiz', 'dry_run' => false];
    }

    /**
     * Check if action budget allows more actions.
     */
    private function checkActionBudget(): array
    {
        $counts = $this->getActionCounts();

        if ($counts['this_hour'] >= $counts['max_per_hour']) {
            $this->triggerBudgetExceeded('hourly', $counts);
            return ['allowed' => false, 'reason' => "Saatlik aksiyon limiti aşıldı: {$counts['this_hour']}/{$counts['max_per_hour']}", 'dry_run' => false];
        }

        if ($counts['today'] >= $counts['max_per_day']) {
            $this->triggerBudgetExceeded('daily', $counts);
            return ['allowed' => false, 'reason' => "Günlük aksiyon limiti aşıldı: {$counts['today']}/{$counts['max_per_day']}", 'dry_run' => false];
        }

        return ['allowed' => true, 'reason' => 'Bütçe uygun', 'dry_run' => false];
    }

    /**
     * Check for anomalies (too many rollbacks, failures, error spikes).
     */
    private function checkForAnomalies(): array
    {
        $thresholds = config('governance.anomaly', []);
        $oneHourAgo = now()->subHour();

        // Rollback spike
        $rollbacksThisHour = GovernanceDecision::where('karar_durumu', 'rolled_back')
            ->where('updated_at', '>=', $oneHourAgo)
            ->count();

        $maxRollbacks = $thresholds['max_rollbacks_per_hour'] ?? 5;
        if ($rollbacksThisHour >= $maxRollbacks) {
            $this->triggerAnomaly('rollback_spike', $rollbacksThisHour, $maxRollbacks);
            return ['allowed' => false, 'reason' => "Anomali: geri alma patlaması ({$rollbacksThisHour} geri alma/saat)", 'dry_run' => false];
        }

        // Failure spike
        $failuresThisHour = GovernanceDecision::where('karar_durumu', 'failed')
            ->where('updated_at', '>=', $oneHourAgo)
            ->count();

        $maxFailures = $thresholds['max_failures_per_hour'] ?? 10;
        if ($failuresThisHour >= $maxFailures) {
            $this->triggerAnomaly('failure_spike', $failuresThisHour, $maxFailures);
            return ['allowed' => false, 'reason' => "Anomali: başarısızlık patlaması ({$failuresThisHour} hata/saat)", 'dry_run' => false];
        }

        // Error rate check
        $totalThisHour = GovernanceDecision::where('karar_tarihi', '>=', $oneHourAgo)->count();
        if ($totalThisHour > 10) {
            $errorRate = $failuresThisHour / $totalThisHour;
            $maxErrorRate = $thresholds['error_rate_threshold'] ?? 0.15;
            if ($errorRate >= $maxErrorRate) {
                $this->triggerAnomaly('error_rate_high', round($errorRate * 100, 1), round($maxErrorRate * 100, 1));
                return ['allowed' => false, 'reason' => "Anomali: hata oranı yüksek (%{" . round($errorRate * 100) . "})", 'dry_run' => false];
            }
        }

        return ['allowed' => true, 'reason' => 'Anomali yok', 'dry_run' => false];
    }

    /**
     * Check if domain is in safe zones.
     */
    public function isSafeZone(string $domain): bool
    {
        $safeZones = config('governance.safe_zones', []);

        foreach ($safeZones as $zone) {
            if (str_contains(strtolower($domain), strtolower($zone))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if domain is in blocked zones.
     */
    public function isBlockedZone(string $domain): bool
    {
        $blockedZones = config('governance.blocked_zones', []);

        foreach ($blockedZones as $zone) {
            if (str_contains(strtolower($domain), strtolower($zone))) {
                return true;
            }
        }

        return false;
    }

    // ─── System Pause (Human Interrupt) ────────────────────────

    /**
     * Pause the entire autonomous system (STOP AI).
     */
    public function pauseSystem(int $userId): void
    {
        Cache::put(self::CACHE_KEY_PAUSED, [
            'paused_at' => now()->toIso8601String(),
            'paused_by' => $userId,
        ], 86400); // 24 hours max

        Log::channel('security')->critical('SAB6: System PAUSED by operator', [
            'user_id' => $userId,
        ]);
    }

    /**
     * Resume the system.
     */
    public function resumeSystem(int $userId): void
    {
        Cache::forget(self::CACHE_KEY_PAUSED);

        Log::channel('security')->info('SAB6: System RESUMED by operator', [
            'user_id' => $userId,
        ]);
    }

    /**
     * Check if system is paused.
     */
    public function isSystemPaused(): bool
    {
        return Cache::has(self::CACHE_KEY_PAUSED);
    }

    /**
     * Get pause info if paused.
     */
    public function getPauseInfo(): ?array
    {
        return Cache::get(self::CACHE_KEY_PAUSED);
    }

    // ─── Dry Run ───────────────────────────────────────────────

    /**
     * Log a dry-run simulation result (not actually applied).
     */
    public function logDryRun(string $findingId, string $title, string $action, array $impact): void
    {
        $key = self::CACHE_KEY_DRY_RUN_LOG . ':' . now()->format('Y-m-d');
        $log = Cache::get($key, []);
        $log[] = [
            'finding_id' => $findingId,
            'title' => $title,
            'action' => $action,
            'impact' => $impact,
            'simulated_at' => now()->toIso8601String(),
        ];
        Cache::put($key, $log, 86400);
    }

    /**
     * Get today's dry run log.
     */
    public function getDryRunLog(): array
    {
        return Cache::get(self::CACHE_KEY_DRY_RUN_LOG . ':' . now()->format('Y-m-d'), []);
    }

    // ─── Anomaly Triggers ──────────────────────────────────────

    private function triggerAnomaly(string $type, $current, $threshold): void
    {
        // Auto-pause on anomaly
        $this->pauseSystem(0); // 0 = system-triggered

        Log::channel('security')->critical('SAB6: Anomaly detected — system auto-paused', [
            'anomaly_type' => $type,
            'current_value' => $current,
            'threshold' => $threshold,
        ]);
    }

    private function triggerBudgetExceeded(string $type, array $counts): void
    {
        Log::channel('security')->warning('SAB6: Action budget exceeded', [
            'budget_type' => $type,
            'counts' => $counts,
        ]);
    }

    // ─── Full Status (for UI) ──────────────────────────────────

    /**
     * Get full autonomy status for the UI panel.
     */
    public function getAutonomyStatus(): array
    {
        $level = $this->getAutonomyLevel();
        $counts = $this->getActionCounts();
        $paused = $this->isSystemPaused();
        $pauseInfo = $this->getPauseInfo();
        $dryRun = config('governance.dry_run', false);

        // Recent anomalies (last hour)
        $oneHourAgo = now()->subHour();
        $rollbacksThisHour = GovernanceDecision::where('karar_durumu', 'rolled_back')
            ->where('updated_at', '>=', $oneHourAgo)
            ->count();
        $failuresThisHour = GovernanceDecision::where('karar_durumu', 'failed')
            ->where('updated_at', '>=', $oneHourAgo)
            ->count();

        return [
            'autonomy_level' => $level,
            'autonomy_label' => $this->getAutonomyLevelLabel($level),
            'system_paused' => $paused,
            'pause_info' => $pauseInfo,
            'dry_run' => $dryRun,
            'actions' => $counts,
            'safe_zones' => config('governance.safe_zones', []),
            'blocked_zones' => config('governance.blocked_zones', []),
            'anomaly' => [
                'rollbacks_this_hour' => $rollbacksThisHour,
                'failures_this_hour' => $failuresThisHour,
                'max_rollbacks_per_hour' => config('governance.anomaly.max_rollbacks_per_hour', 5),
                'max_failures_per_hour' => config('governance.anomaly.max_failures_per_hour', 10),
                'error_rate_threshold' => config('governance.anomaly.error_rate_threshold', 0.15),
            ],
            'dry_run_log_count' => count($this->getDryRunLog()),
        ];
    }
}
