<?php

declare(strict_types=1);

namespace App\Console\Commands\Bekci;

use App\Services\Governance\TenantIsolationAuditService;
use Illuminate\Console\Command;

/**
 * Yalıhan Bekçi: report-only tenant isolation source audit.
 */
final class TenantIsolationAuditCommand extends Command
{
    protected $signature = 'bekci:tenant-audit
        {--strict : Return failure when blocking findings exist}
        {--json : Print the machine-readable report only}';

    protected $description = 'Yalıhan Bekçi: tenant isolation source audit (read-only)';

    public function handle(TenantIsolationAuditService $audit): int
    {
        $report = $audit->audit();

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info('🛡️ Yalıhan Bekçi: Tenant isolation audit (source-read-only)');
            $this->line(sprintf('Models scanned: %d', $report['models_scanned']));
            $this->line(sprintf('Migration tables scanned: %d', $report['migration_tables_scanned']));
            $this->line(sprintf('Tenant columns detected: %d', $report['tenant_tables_detected']));
            $this->line(sprintf('Blocking findings: %d', $report['blocking_count']));
            $this->line(sprintf('Review findings: %d', $report['review_count']));
            $this->comment('Production verification: NO — this command does not connect to a database.');

            foreach ($report['findings'] as $finding) {
                $line = sprintf(
                    '[%s] %s: %s (%s)',
                    strtoupper($finding['severity']),
                    $finding['rule'],
                    $finding['table'] ?? '?',
                    $finding['file']
                );

                $finding['severity'] === 'blocking'
                    ? $this->error($line)
                    : $this->warn($line);
            }
        }

        if ($this->option('strict') && $report['blocking_count'] > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
