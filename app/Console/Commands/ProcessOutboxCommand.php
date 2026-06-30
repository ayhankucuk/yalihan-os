<?php

namespace App\Console\Commands;

use App\Helpers\EventHelper;
use App\Models\OutboxEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class ProcessOutboxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outbox:process 
                            {--once : Run once and exit} 
                            {--limit=50 : Number of entries to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🛡️ Process pending transactional outbox entries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $once = $this->option('once');
        $limit = (int) $this->option('limit');

        Log::info("🚀 Outbox worker starting...", ['once' => $once, 'limit' => $limit]);

        do {
            $entries = OutboxEntry::whereIn('yayin_durumu', ['PENDING', 'FAILED'])
                ->where('attempts', '<', 5)
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get();

            if ($entries->isEmpty()) {
                if ($once) {
                    break;
                }
                // Sleep for 1 second before checking again
                sleep(1);
                continue;
            }

            foreach ($entries as $entry) {
                $this->processEntry($entry);
            }

        } while (!$once);

        Log::info("🏁 Outbox worker finished.");
        return 0;
    }

    /**
     * Process a single outbox entry.
     */
    private function processEntry(OutboxEntry $entry): void
    {
        $entry->yayin_durumu = 'PROCESSING';
        $entry->attempts += 1;
        $entry->save();

        try {
            $eventClass = EventHelper::getClass($entry->event_key);
            if (!$eventClass) {
                throw new \Exception("Event class not found for key: {$entry->event_key}");
            }

            $payload = $entry->payload;
            $ref = new \ReflectionClass($eventClass);
            $constructor = $ref->getConstructor();
            $args = [];

            if ($constructor) {
                $params = $constructor->getParameters();
                foreach ($params as $param) {
                    $name = $param->getName();
                    $type = $param->getType();
                    $value = $payload[$name] ?? null;

                    if ($type && !$type->isBuiltin()) {
                        $className = $type->getName();
                        if (is_subclass_of($className, \Illuminate\Database\Eloquent\Model::class)) {
                            // If the payload value is an array or object containing the ID, extract it
                            if (is_array($value) && isset($value['id'])) {
                                $id = $value['id'];
                            } else {
                                $id = $value;
                            }

                            if ($id === null) {
                                throw new \Exception("Model ID for parameter '{$name}' of type '{$className}' is null.");
                            }

                            // Fetch the model
                            $value = $className::findOrFail($id);
                        }
                    }
                    $args[] = $value;
                }
            }

            // Create and dispatch the event
            $eventInstance = new $eventClass(...$args);
            Event::dispatch($eventInstance);

            $entry->yayin_durumu = 'COMPLETED';
            $entry->error_message = null;
            $entry->processed_at = now();
            $entry->save();

            Log::debug("✅ Outbox entry processed successfully", ['id' => $entry->id, 'event' => $entry->event_key]);

        } catch (\Throwable $e) {
            $entry->yayin_durumu = $entry->attempts >= 5 ? 'DEAD_LETTER' : 'FAILED';
            $entry->error_message = $e->getMessage() . "\n" . $e->getTraceAsString();
            $entry->save();

            Log::error("❌ Failed to process outbox entry", [
                'id' => $entry->id,
                'event' => $entry->event_key,
                'attempt' => $entry->attempts,
                'yayin_durumu' => $entry->yayin_durumu,
                'error' => $e->getMessage()
            ]);
        }
    }
}
