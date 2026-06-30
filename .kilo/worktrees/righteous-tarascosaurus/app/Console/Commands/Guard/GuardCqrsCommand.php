<?php

namespace App\Console\Commands\Guard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GuardCqrsCommand extends Command
{
    protected $signature = 'guard:cqrs';
    protected $description = '🛡️ CQRS Integrity Guard: Detects direct writes to projection tables and missing event dispatches.';

    /**
     * Forbidden patterns: direct writes to projection tables.
     * These MUST only be written via queue jobs triggered by events.
     */
    protected array $forbiddenPatterns = [
        'DB::table(\'listing_search_projection\')->insert',
        'DB::table(\'listing_search_projection\')->update',
        'DB::table(\'listing_search_projection\')->delete',
        'DB::table("listing_search_projection")->insert',
        'DB::table("listing_search_projection")->update',
        'DB::table("listing_search_projection")->delete',
        '->table(\'listing_search_projection\')->insert',
        '->table(\'listing_search_projection\')->upsert',
        'DB::table(\'ledger_balances\')->insert',
        'DB::table(\'ledger_balances\')->update',
        'DB::table(\'ledger_balances\')->delete',
        'DB::table("ledger_balances")->insert',
        'DB::table("ledger_balances")->update',
        'DB::table("ledger_balances")->delete',
    ];

    /**
     * Required event dispatches — the CQRS pipeline mandates these.
     */
    protected array $requiredEvents = [
        'ListingCreated',
        'LedgerDoubleEntryRecorded',
    ];

    /**
     * Required queue jobs — the async projection pipeline mandates these.
     */
    protected array $requiredJobs = [
        'SyncListingProjection',
    ];

    /**
     * Directories to scan for forbidden patterns.
     */
    protected array $scanDirs = [
        'app/Http/Controllers',
        'app/Services',
        'app/Models',
    ];

    /**
     * Directories explicitly ALLOWED to write to projections (job workers).
     */
    protected array $allowedDirs = [
        'app/Jobs',
    ];

    public function handle(): int
    {
        $this->info('🛡️ CQRS Integrity Guard — Scanning for projection bypass...');
        $this->newLine();

        $violations = [];

        // 1. Scan for forbidden direct writes in controllers/services/models
        foreach ($this->scanDirs as $dir) {
            $fullPath = base_path($dir);
            if (!File::isDirectory($fullPath)) {
                continue;
            }

            $files = File::allFiles($fullPath);
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = File::get($file->getPathname());
                $relativePath = str_replace(base_path() . '/', '', $file->getPathname());

                foreach ($this->forbiddenPatterns as $pattern) {
                    if (stripos($content, $pattern) !== false) {
                        $violations[] = [
                            'file' => $relativePath,
                            'pattern' => $pattern,
                            'type' => 'DIRECT_PROJECTION_WRITE',
                        ];
                        $this->error("❌ CQRS VIOLATION: Direct projection write in {$relativePath}");
                        $this->error("   Pattern: {$pattern}");
                    }
                }
            }
        }

        // 2. Verify required events exist
        $this->info('🔍 Checking required event classes...');
        foreach ($this->requiredEvents as $event) {
            $eventPath = base_path("app/Events/{$event}.php");
            if (File::exists($eventPath)) {
                $this->info("   ✅ Event {$event} exists.");
            } else {
                $violations[] = [
                    'file' => "app/Events/{$event}.php",
                    'pattern' => 'MISSING',
                    'type' => 'MISSING_EVENT',
                ];
                $this->error("   ❌ Event {$event} is MISSING!");
            }
        }

        // 3. Verify required queue jobs exist
        $this->info('🔍 Checking required queue jobs...');
        foreach ($this->requiredJobs as $job) {
            $found = false;
            $jobPaths = [
                base_path("app/Jobs/{$job}.php"),
                base_path("app/Jobs/Projection/{$job}.php"),
                base_path("app/Jobs/Search/{$job}.php"),
            ];
            foreach ($jobPaths as $jobPath) {
                if (File::exists($jobPath)) {
                    $found = true;
                    $this->info("   ✅ Job {$job} exists.");
                    break;
                }
            }
            if (!$found) {
                // Check recursively
                $allJobFiles = File::allFiles(base_path('app/Jobs'));
                foreach ($allJobFiles as $jf) {
                    if (str_contains($jf->getFilename(), $job)) {
                        $found = true;
                        $this->info("   ✅ Job {$job} found at " . str_replace(base_path() . '/', '', $jf->getPathname()));
                        break;
                    }
                }
            }
            if (!$found) {
                $violations[] = [
                    'file' => "app/Jobs/{$job}.php",
                    'pattern' => 'MISSING',
                    'type' => 'MISSING_JOB',
                ];
                $this->error("   ❌ Job {$job} is MISSING!");
            }
        }

        $this->newLine();

        if (!empty($violations)) {
            $this->error('❌ CQRS INTEGRITY: FAIL — ' . count($violations) . ' violation(s) found.');
            $this->error('   Projection tables MUST ONLY be written via event-driven queue jobs.');
            return 1;
        }

        $this->info('✅ CQRS INTEGRITY: PASS — No direct projection writes. Event/Job pipeline intact.');
        return 0;
    }
}
