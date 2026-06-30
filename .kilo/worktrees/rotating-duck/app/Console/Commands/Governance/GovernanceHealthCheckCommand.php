<?php

namespace App\Console\Commands\Governance;

use App\Governance\Alerting\GovernanceAlerter;
use App\Governance\Analytics\GovernanceAnalytics;
use App\Governance\Metrics\GovernanceMetrics;
use Illuminate\Console\Command;

/**
 * Phase 4C — Governance Health Check Command
 *
 * Terminal üzerinden sistemin yönetişim sağlığını görüntüler.
 */
class GovernanceHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'governance:health-check {--json : JSON çıktısı üretir}';

    /**
     * The console command description.
     */
    protected $description = 'Yalıhan AI Yönetişim (Governance) sağlık durumunu görüntüler';

    protected GovernanceAnalytics $analytics;
    protected GovernanceAlerter $alerter;

    public function __construct(GovernanceAnalytics $analytics, GovernanceAlerter $alerter)
    {
        parent::__construct();
        $this->analytics = $analytics;
        $this->alerter = $alerter;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $health = GovernanceMetrics::getHealthScore();
            $drift = $this->analytics->detectDrift();
            $anomalies = $this->analytics->detectAnomalies();
            $alerts = $this->alerter->getActiveAlerts();

            if ($this->option('json')) {
                $this->line(json_encode([
                    'health'    => $health,
                    'drift'     => $drift,
                    'anomalies' => $anomalies,
                    'alerts'    => $alerts,
                    'timestamp' => now()->toIso8601String(),
                ], JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }

            $this->renderTerminalOutput($health, $drift, $anomalies, $alerts);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Governance health check başarısız: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Render a beautiful terminal table report.
     */
    private function renderTerminalOutput(array $health, array $drift, array $anomalies, array $alerts): void
    {
        $this->line('');
        $this->line('<fg=cyan;options=bold>=== YALIHAN AI GOVERNANCE HEALTH CHECK (' . now()->toIso8601String() . ') ===</>');
        $this->line('');

        // 1. Overall Score
        $score = $health['overall'] ?? 0;
        $color = $score >= 95 ? 'info' : ($score >= 85 ? 'comment' : 'error');
        
        $this->line("Overall Score : <$color>{$score} / 100</$color>");
        $this->line("Trend         : " . ($health['trend'] ?? 'stable'));
        $this->line('');

        // 2. Breakdown Table
        $this->info('BREAKDOWN:');
        foreach ($health['breakdown'] ?? [] as $key => $val) {
            $this->line("  " . str_pad($key, 22) . ": " . $val);
        }
        $this->line('');

        // 3. Drift Detection
        $this->info('DRIFT DETECTION:');
        $driftStatus = $drift['has_drift'] ? '<fg=red>EVET (KRİTİK)</>' : '<fg=green>HAYIR</>';
        $this->line("  Has Drift    : " . $driftStatus);
        $this->line("  Drift        : " . ($drift['drift_percentage'] ?? 0) . "%");
        $this->line("  Threshold    : " . ($drift['threshold'] ?? 10) . "%");
        $this->line('');

        // 4. Anomalies
        $this->info('ANOMALIES:');
        if (empty($anomalies)) {
            $this->line('  Tespit edilmedi');
        } else {
            foreach ($anomalies as $anomaly) {
                $sev = strtoupper($anomaly['severity'] ?? 'LOW');
                $this->line("  [<fg=yellow>$sev</>] " . ($anomaly['message'] ?? 'Bilinmeyen anomali'));
            }
        }
        $this->line('');

        // 5. Active Alerts
        $alertCount = count($alerts);
        $alertColor = $alertCount > 0 ? 'error' : 'info';
        $this->line("ACTIVE ALERTS: <$alertColor>{$alertCount} aktif alarm</$alertColor>");
        $this->line('');
    }
}
