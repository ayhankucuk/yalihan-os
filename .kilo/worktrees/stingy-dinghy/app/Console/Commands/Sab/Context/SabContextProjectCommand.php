<?php

namespace App\Console\Commands\Sab\Context;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * 🛰️ SAB Context Projection Builder
 * 
 * Rebuilds generated context and memory files based on the event log.
 */
class SabContextProjectCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sab:context:project {--dry-run : Only show what would be written}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🛰️ Rebuild AI context and memory projections from the event log';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logPath = base_path('.ai/events/sab-events.jsonl');
        
        if (!File::exists($logPath)) {
            $this->error("Event log not found at $logPath");
            return 1;
        }

        $logContent = File::get($logPath);
        $lines = explode(PHP_EOL, trim($logContent));
        
        $events = collect($lines)
            ->filter()
            ->map(function($line) {
                return json_decode($line, true);
            })
            ->sortBy('timestamp');

        if ($events->isEmpty()) {
            $this->warn("No events found in log.");
            return 0;
        }

        $this->buildLatestStatus($events);
        $this->buildCurrentState($events);
        $this->buildGovernanceDelta($events);

        $this->info("\n✅ SAB Documentation Projections updated successfully.");
        return 0;
    }

    protected function buildLatestStatus($events)
    {
        $lastGoalEvent = $events->where('type', 'epic_status_change')->last();
        $goal = $lastGoalEvent['scope'] ?? 'Unknown';
        $status = $lastGoalEvent['status'] ?? 'UNKNOWN';
        
        $recentMilestones = $events->reverse()->take(10)->map(function($e) {
            $time = substr($e['timestamp'], 0, 16);
            $time = str_replace('T', ' ', $time);
            return "- [$time] {$e['summary']}";
        })->implode(PHP_EOL);

        $content = "# Latest Status" . PHP_EOL . PHP_EOL;
        $content .= "**Current Goal**: $goal" . PHP_EOL;
        $content .= "**Status**: $status" . PHP_EOL . PHP_EOL;
        $content .= "## Recent Milestones" . PHP_EOL;
        $content .= $recentMilestones . PHP_EOL . PHP_EOL;
        $content .= "## Next Actions" . PHP_EOL;
        $content .= "1. Review generated projections." . PHP_EOL;
        $content .= "2. Mark tasks as completed." . PHP_EOL;

        $this->writeFile(base_path('.ai/memory/latest-status.md'), $content);
    }

    protected function buildCurrentState($events)
    {
        $lastEvent = $events->last();
        $timestamp = $lastEvent['timestamp'];
        $releaseState = $lastEvent['release_state'] ?? 'unknown';
        
        $blockedBy = $events->where('type', 'gate_failed')->last();
        $blockedStr = $blockedBy ? "{$blockedBy['summary']} (source: {$blockedBy['source']})" : "No active blocks";

        $activeEpics = $events->where('type', 'epic_status_change')
            ->groupBy('scope')
            ->map(fn($g) => $g->last())
            ->filter(fn($e) => in_array($e['status'], ['started', 'in_progress']));

        $completedEpics = $events->where('type', 'epic_status_change')
            ->groupBy('scope')
            ->map(fn($g) => $g->last())
            ->filter(fn($e) => in_array($e['status'], ['done', 'sealed', 'completed']));

        $content = "<!-- " . PHP_EOL;
        $content .= "  DO NOT EDIT MANUALLY. " . PHP_EOL;
        $content .= "  This is a generated projection created by SAB Pipeline." . PHP_EOL;
        $content .= "  Run `php artisan sab:context:project` to update." . PHP_EOL;
        $content .= "-->" . PHP_EOL . PHP_EOL;
        $content .= "# Current State" . PHP_EOL . PHP_EOL;
        $content .= "**Last Generated**: $timestamp" . PHP_EOL;
        $content .= "**Release State**: $releaseState" . PHP_EOL;
        $content .= "**Blocked By**: $blockedStr" . PHP_EOL . PHP_EOL;

        $content .= "## Active Epics" . PHP_EOL;
        if ($activeEpics->isEmpty()) {
            $content .= "- None" . PHP_EOL;
        } else {
            foreach ($activeEpics as $epic) {
                $content .= "- **{$epic['scope']}**: {$epic['status']} ({$epic['summary']})" . PHP_EOL;
            }
        }

        $content .= PHP_EOL . "## Completed Epics (Recent)" . PHP_EOL;
        if ($completedEpics->isEmpty()) {
            $content .= "- None" . PHP_EOL;
        } else {
            foreach ($completedEpics as $epic) {
                $content .= "- **{$epic['scope']}**: {$epic['status']} ({$epic['summary']})" . PHP_EOL;
            }
        }

        $this->writeFile(base_path('.ai/context/current-state.generated.md'), $content);
    }

    protected function buildGovernanceDelta($events)
    {
        $govEvents = $events->filter(function($e) {
            $affects = $e['affects'] ?? [];
            return in_array('governance', $affects) || $e['scope'] === 'governance';
        })->reverse()->take(5);

        $content = "<!-- " . PHP_EOL;
        $content .= "  DO NOT EDIT MANUALLY. " . PHP_EOL;
        $content .= "  This is a generated projection created by SAB Pipeline." . PHP_EOL;
        $content .= "  Run `php artisan sab:context:project` to update." . PHP_EOL;
        $content .= "-->" . PHP_EOL . PHP_EOL;
        $content .= "# Governance Delta" . PHP_EOL . PHP_EOL;
        $content .= "**Last Snapshot**: " . now()->toIso8601String() . PHP_EOL . PHP_EOL;
        $content .= "## Recent Changes" . PHP_EOL;
        
        if ($govEvents->isEmpty()) {
            $content .= "- No recent governance events recorded." . PHP_EOL;
        } else {
            foreach ($govEvents as $event) {
                $content .= "- **{$event['scope']}**: {$event['summary']}" . PHP_EOL;
            }
        }

        $content .= PHP_EOL . "## Impact Analysis" . PHP_EOL;
        $content .= "- **Quality Gate**: " . ($events->where('type', 'gate_failed')->last() ? "Blocked" : "Passing") . PHP_EOL;
        $content .= "- **Release Readiness**: " . ($events->last()['release_state'] ?? 'unknown') . PHP_EOL;

        $this->writeFile(base_path('.ai/context/governance-delta.generated.md'), $content);
    }

    protected function writeFile($path, $content)
    {
        if ($this->option('dry-run')) {
            $this->line("\n[DRY RUN] Would write to $path:");
            $this->line("--------------------------------------------------");
            $this->line($content);
            $this->line("--------------------------------------------------");
            return;
        }

        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        File::put($path, $content);
        $this->info("Generated: " . basename($path));
    }
}
