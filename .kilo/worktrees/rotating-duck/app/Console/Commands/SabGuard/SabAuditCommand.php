<?php

namespace App\Console\Commands\SabGuard;

use Illuminate\Console\Command;
use App\Services\Sab\SabAutomationGuardService;
use Illuminate\Support\Facades\File;

class SabAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sab:audit {--o|output=docs/SAB_AUDIT_REPORT.md : Output path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🛡️ SAB Audit Generator: Runs all SAB Guards V3 and exports a full Markdown audit report.';

    protected SabAutomationGuardService $guardService;

    public function __construct(SabAutomationGuardService $guardService)
    {
        parent::__construct();
        $this->guardService = $guardService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("📄 Generating SAB V3 Audit Report...");

        $issues = $this->guardService->runAllGuards();
        $outputPath = base_path($this->option('output'));

        $report = $this->generateMarkdown($issues);

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $report);

        $this->info("✅ SAB V3 Audit Report successfully generated at: {$outputPath}");

        return 0;
    }

    private function generateMarkdown(array $issues): string
    {
        $date = now()->format('Y-m-d H:i:s');
        $failsCount = collect($issues)->filter(fn($i) => strtoupper($i['severity']) === 'FAIL' || strtoupper($i['severity']) === 'CRITICAL')->count();
        $total = count($issues);

        $md = "# 🛡️ SAB PRODUCTION AUDIT REPORT V3\n\n";
        $md .= "**Generated:** {$date}\n";
        $md .= "**Status:** " . ($failsCount === 0 ? ($total === 0 ? "PRODUCTION_READY ✅" : "PRODUCTION_READY_WITH_VERIFY_REQUIRED ⚠️") : "NOT_READY ❌") . "\n";
        $md .= "**Total Violations Detected:** {$total}\n\n";

        if ($total === 0) {
            $md .= "System is 100% compliant with SAB (Standart Uygulama Bloğu) Architecture. Zero drift detected.\n";
            return $md;
        }

        $md .= "## DRIFT DETECTED\n\n";

        $grouped = collect($issues)->groupBy('guard');

        foreach ($grouped as $guardName => $guardIssues) {
            $md .= "### {$guardName}\n";
            $md .= "| File | Line | Severity | Message | Required Fix |\n";
            $md .= "|------|------|----------|---------|--------------|\n";

            foreach ($guardIssues as $issue) {
                $fix = $issue['fix'] ?? 'Manual Review Required';
                $md .= "| `{$issue['file']}` | {$issue['line']} | **{$issue['severity']}** | {$issue['message']} | {$fix} |\n";
            }
            $md .= "\n";
        }

        return $md;
    }
}
