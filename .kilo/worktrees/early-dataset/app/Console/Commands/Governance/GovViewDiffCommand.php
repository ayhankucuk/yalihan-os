<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use App\Contracts\Governance\GovernanceReadServiceInterface;
use App\Contracts\Governance\TelemetryPublisherInterface;
use App\Enums\Governance\GovernanceTelemetryEvent;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\Table;

class GovViewDiffCommand extends Command
{
    protected $signature = 'gov:view-diff {entityType} {entityId}';
    protected $description = 'Displays purely read-only Diff projection for a governed entity (Draft vs Published)';

    public function handle(GovernanceReadServiceInterface $readService, TelemetryPublisherInterface $telemetry): int
    {
        $entityType = $this->argument('entityType');
        $entityId   = $this->argument('entityId');
        
        $correlationId = 'cli-' . Str::uuid()->toString();

        $this->getOutput()->writeln("<fg=gray>Fetching Projection for [{$entityType}:{$entityId}]...</>");

        $startTime = microtime(true);
        try {
            $projection = $readService->getDiff($entityType, $entityId);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
        } catch (\Throwable $e) {
            $this->error("Failed to fetch projection: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->getOutput()->writeln('');
        $this->getOutput()->writeln("<bg=blue;fg=white> 🚀 GOVERNANCE DIFF VIEWER (READ-ONLY) </>");
        $this->getOutput()->writeln("  Entity : <fg=yellow>{$projection->entityType}</> ID: <fg=yellow>{$projection->entityId}</>");
        $this->getOutput()->writeln("  State  : <fg=cyan>{$projection->currentState->value}</>");
        
        $canPublishStr = $projection->canPublish ? "<bg=green;fg=white> TRUE </>" : "<bg=red;fg=white> FALSE </>";
        $this->getOutput()->writeln("  Publish Eligibility: {$canPublishStr}");
        $this->getOutput()->writeln("  Telemetry Duration : <fg=gray>{$duration} ms</>");
        $this->getOutput()->writeln("  Session Tracing ID : <fg=gray>{$correlationId}</>");
        $this->getOutput()->writeln('');

        if (empty($projection->changes)) {
            $this->warn('🚀 No changes detected (Identical payloads).');
        } else {
            $table = new Table($this->getOutput());
            $table->setHeaders(['Path (Dot Notation)', 'Type', 'Old Value', 'New Value']);

            foreach ($projection->changes as $path => $diff) {
                $type = $diff['type'];
                
                $oldJson = $diff['old'] !== null ? json_encode($diff['old'], JSON_UNESCAPED_UNICODE) : 'null';
                $newJson = $diff['new'] !== null ? json_encode($diff['new'], JSON_UNESCAPED_UNICODE) : 'null';

                $typeCol = match($type) {
                    'added'   => "<fg=green>+ ADDED</>",
                    'removed' => "<fg=red>- REMOVED</>",
                    'changed' => "<fg=yellow>~ CHANGED</>",
                    default   => $type,
                };

                $table->addRow([$path, $typeCol, $oldJson, $newJson]);
            }

            $table->render();
        }

        $this->getOutput()->writeln('');

        // Emit Telemetry
        $telemetry->publish(
            GovernanceTelemetryEvent::DIFF_VIEWED,
            $correlationId,
            $entityType,
            $entityId,
            $duration,
            ['changes_count' => count($projection->changes)]
        );

        return Command::SUCCESS;
    }
}
