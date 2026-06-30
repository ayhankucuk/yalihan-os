<?php

namespace App\Console\Commands\Sab\Context;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * 🛰️ SAB Context Integrity Check
 * 
 * Verifies the health of the projection pipeline.
 */
class SabContextCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sab:context:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🛰️ Verify the integrity of the SAB Documentation Projection Pipeline';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("🛰️ Running SAB Context Pipeline Integrity Check...\n");
        $issues = 0;

        // 1. Check event log
        $logPath = base_path('.ai/events/sab-events.jsonl');
        if (!File::exists($logPath)) {
            $this->error("[FAIL] Event log missing at $logPath");
            $issues++;
        } else {
            $this->info("[OK] Event log found.");
            
            // Check if log is empty
            if (empty(trim(File::get($logPath)))) {
                $this->warn("[WARN] Event log is empty.");
            }
        }

        // 2. Check generated files exist
        $generatedFiles = [
            '.ai/context/current-state.generated.md',
            '.ai/context/governance-delta.generated.md',
            '.ai/memory/latest-status.md'
        ];

        foreach ($generatedFiles as $file) {
            if (!File::exists(base_path($file))) {
                $this->error("[FAIL] Generated file missing: $file");
                $issues++;
            } else {
                $this->info("[OK] Generated file exists: $file");
            }
        }

        // 3. Stale check
        if (File::exists($logPath)) {
            $logMtime = File::lastModified($logPath);
            foreach ($generatedFiles as $file) {
                $fullPath = base_path($file);
                if (File::exists($fullPath) && File::lastModified($fullPath) < $logMtime) {
                    $this->warn("[STALE] $file is older than the event log. Suggestion: Run `php artisan sab:context:project`.");
                }
            }
        }

        // 4. Permission check (simplified)
        foreach (array_merge([$logPath], array_map(fn($f) => base_path($f), $generatedFiles)) as $path) {
            if (File::exists($path) && !is_writable($path)) {
                $this->error("[FAIL] File is not writable: " . basename($path));
                $issues++;
            }
        }

        $this->line("");

        if ($issues === 0) {
            $this->info("✅ SAB Context Pipeline is healthy.");
            return 0;
        }

        $this->error("❌ SAB Context Pipeline found $issues critical issues.");
        return 1;
    }
}
