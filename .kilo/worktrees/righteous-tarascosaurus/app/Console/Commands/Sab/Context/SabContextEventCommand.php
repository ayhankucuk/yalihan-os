<?php

namespace App\Console\Commands\Sab\Context;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * 🛰️ SAB Context Event Recorder
 * 
 * Part of the SAB Documentation Projection Pipeline.
 * Appends structured events to the append-only event log.
 */
class SabContextEventCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sab:context:event 
                            {--type= : Event type (remediation_completed, epic_status_change, gate_failed, etc.)} 
                            {--scope= : Affected scope (P1, P2, release, governance, etc.)} 
                            {--status= : Event status (done, started, blocked, sealed, etc.)} 
                            {--summary= : Brief summary of the event} 
                            {--source=manual : Source of the event (manual, quality-gate, sab:preflight, etc.)} 
                            {--affects=* : List of affected layers or components (can be used multiple times)} 
                            {--release-state=blocked : Current release state}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🛰️ Record a structured event for the Documentation Projection Pipeline';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logPath = base_path('.ai/events/sab-events.jsonl');

        if (!File::exists(dirname($logPath))) {
            File::makeDirectory(dirname($logPath), 0755, true);
        }

        $type = $this->option('type');
        if (!$type) {
            $this->error("Event type is required.");
            return 1;
        }

        $event = [
            'timestamp' => now()->toIso8601String(),
            'event_type' => $type,
            'scope' => $this->option('scope') ?? 'general',
            'event_status' => $this->option('status') ?? 'noted',
            'summary' => $this->option('summary') ?? 'No summary provided',
            'source' => $this->option('source'),
            'affects' => $this->option('affects') ?: ['unknown'],
            'release_state' => $this->option('release-state'),
        ];

        try {
            File::append($logPath, json_encode($event) . PHP_EOL);
            $this->info("✅ Event recorded: [" . strtoupper($type) . "] " . $event['summary']);
        } catch (\Exception $e) {
            $this->error("❌ Failed to record event: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
