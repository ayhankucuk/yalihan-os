<?php

namespace App\Console\Commands;

use App\Models\OpenClawAuditLog;
use App\Services\OpenClaw\OpenClawAuditService;
use Illuminate\Console\Command;

/**
 * OpenClawAuditReport — Consolidated audit & health report for OpenClaw.
 *
 * Single command that surfaces:
 * - Request summary (total, passed, blocked, violations)
 * - Block rate and anomaly indicators
 * - Top rejection reasons
 * - Write violations by service
 * - Excluded services registry review status
 * - Guard coverage summary
 *
 * Usage:
 *   php artisan openclaw:audit-report              # last 60 minutes
 *   php artisan openclaw:audit-report --window=1440 # last 24 hours
 *   php artisan openclaw:audit-report --full        # everything
 */
class OpenClawAuditReport extends Command
{
    protected $signature = 'openclaw:audit-report
                            {--window=60 : Analysis window in minutes}
                            {--full : Show all records regardless of window}';

    protected $description = 'Generate a consolidated OpenClaw audit & health report';

    public function handle(OpenClawAuditService $auditService): int
    {
        $window = (int) $this->option('window');
        $full = (bool) $this->option('full');

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');
        $this->info('       OpenClaw Audit & Health Report');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        // Section 1: System Status
        $this->sectionSystemStatus();

        // Section 2: Request Summary
        $this->sectionRequestSummary($auditService, $window, $full);

        // Section 3: Top Rejection Reasons
        $this->sectionRejectionReasons($window, $full);

        // Section 4: Write Violations by Service
        $this->sectionWriteViolations($auditService, $window);

        // Section 5: Excluded Services Registry
        $this->sectionExcludedServices();

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');

        return self::SUCCESS;
    }

    private function sectionSystemStatus(): void
    {
        $killSwitch = config('openclaw.enabled', false)
            ? '<fg=green>ENABLED</>'
            : '<fg=red>DISABLED (all agent requests blocked)</>';
        $this->components->twoColumnDetail(
            '<fg=yellow>Kill Switch</>',
            $killSwitch
        );

        $proposalMode = config('openclaw.proposal_only', true)
            ? '<fg=green>ON (mutations blocked)</>'
            : '<fg=red>OFF (direct mutations allowed)</>';
        $this->components->twoColumnDetail(
            '<fg=yellow>Proposal-Only Mode</>',
            $proposalMode
        );
        $this->components->twoColumnDetail('<fg=yellow>Allowed Routes</>', (string) count(config('openclaw.allowed_routes', [])));
        $this->components->twoColumnDetail('<fg=yellow>Forbidden Patterns</>', (string) count(config('openclaw.forbidden_route_patterns', [])));
        $this->components->twoColumnDetail('<fg=yellow>Allowed Scopes</>', implode(', ', config('openclaw.allowed_scopes', [])));
        $this->newLine();
    }

    private function sectionRequestSummary(OpenClawAuditService $auditService, int $window, bool $full): void
    {
        $this->components->info($full ? 'Request Summary (all time)' : "Request Summary (last {$window} min)");

        if ($full) {
            $total = OpenClawAuditLog::count();
            $passed = OpenClawAuditLog::passed()->count();
            $blocked = OpenClawAuditLog::blocked()->count();
            $violations = OpenClawAuditLog::violations()->count();
            $blockRate = $total > 0 ? round($blocked / $total * 100, 1) : 0;
        } else {
            $stats = $auditService->getWindowStats($window);
            $total = $stats['total_requests'];
            $passed = $total - $stats['blocked_count'];
            $blocked = $stats['blocked_count'];
            $violations = $stats['violation_count'];
            $blockRate = round($stats['block_rate'] * 100, 1);
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Requests', $total],
                ['Passed', $passed],
                ['Blocked', $blocked],
                ['Write Violations', $violations],
                ['Block Rate', "{$blockRate}%"],
            ]
        );

        // Health verdict
        if ($violations > 0) {
            $this->error("  ⚠ {$violations} write violation(s) detected — investigate immediately");
        } elseif ($blockRate > 50 && $total >= 5) {
            $this->warn("  ⚠ High block rate ({$blockRate}%) — may indicate misconfigured agents");
        } elseif ($total === 0) {
            $this->line('  ℹ No agent traffic in this window');
        } else {
            $this->info('  ✓ System healthy — no anomalies');
        }

        $this->newLine();
    }

    private function sectionRejectionReasons(int $window, bool $full): void
    {
        $this->components->info('Top Rejection Reasons');

        $query = OpenClawAuditLog::blocked()
            ->whereNotNull('rejection_reason')
            ->selectRaw('rejection_reason, COUNT(*) as hit_count')
            ->groupBy('rejection_reason')
            ->orderByDesc('hit_count')
            ->limit(10);

        if (!$full) {
            $query->recent($window);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->line('  No rejections in this window.');
        } else {
            $this->table(
                ['Reason', 'Count'],
                $rows->map(fn ($r) => [$r->rejection_reason, $r->hit_count])->toArray()
            );
        }

        $this->newLine();
    }

    private function sectionWriteViolations(OpenClawAuditService $auditService, int $window): void
    {
        $this->components->info('Write Violations by Service');

        $violations = $auditService->getViolationsByService($window);

        if (empty($violations)) {
            $this->line('  No write violations in this window.');
        } else {
            $this->table(
                ['Service', 'Method', 'Count'],
                array_map(fn ($v) => [
                    $v['service_class'] ?? '-',
                    $v['service_method'] ?? '-',
                    $v['violation_count'],
                ], $violations)
            );
        }

        $this->newLine();
    }

    private function sectionExcludedServices(): void
    {
        $this->components->info('Excluded Services Registry');

        $excludedServices = config('openclaw.excluded_services', []);

        if (empty($excludedServices)) {
            $this->warn('  No excluded services registry found in config/openclaw.php');
            return;
        }

        $rows = [];
        $staleCount = 0;
        $today = now();

        foreach ($excludedServices as $fqcn => $meta) {
            $reviewDate = $meta['review_date'] ?? 'unset';
            $isStale = false;

            if ($reviewDate !== 'unset') {
                try {
                    $isStale = $today->greaterThan(\Carbon\Carbon::parse($reviewDate));
                } catch (\Throwable) {
                    $isStale = true;
                }
            }

            if ($isStale) {
                $staleCount++;
            }

            $shortName = class_basename($fqcn);
            $rows[] = [
                $shortName,
                $meta['domain'] ?? '-',
                $meta['reason'] ?? '-',
                $isStale ? "<fg=red>{$reviewDate} (OVERDUE)</>" : "<fg=green>{$reviewDate}</>",
            ];
        }

        $this->table(
            ['Service', 'Domain', 'Reason', 'Review Date'],
            $rows
        );

        $this->components->twoColumnDetail('Total Excluded', (string) count($excludedServices));

        if ($staleCount > 0) {
            $this->error("  ⚠ {$staleCount} excluded service(s) overdue for review!");
        }

        if (count($excludedServices) >= 10) {
            $this->error('  ⚠ Excluded list ≥ 10 — architectural review required (guard bypass risk)');
        }

        $this->newLine();
    }
}
