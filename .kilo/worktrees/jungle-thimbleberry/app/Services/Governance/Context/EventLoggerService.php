<?php

namespace App\Services\Governance\Context;

use Illuminate\Support\Facades\File;

class EventLoggerService
{
    /**
     * @var string
     */
    protected $logFilePath;

    public function __construct()
    {
        $this->logFilePath = base_path('.ai/events/sab-events.jsonl');
        
        $this->ensureDirectoryExists();
    }

    /**
     * Log a new event to the JSONL file (append-only)
     *
     * @param string $eventType
     * @param string $scope
     * @param string $eventStatus
     * @param string $source
     * @param string $summary
     * @param string|null $releaseState
     * @return array The recorded event
     */
    public function logEvent(string $eventType, string $scope, string $eventStatus, string $source, string $summary, ?string $releaseState = null): array
    {
        $event = [
            'timestamp' => now()->toIso8601String(),
            'event_type' => $eventType,
            'scope' => $scope,
            'event_status' => $eventStatus,
            'source' => $source,
            'summary' => $summary,
        ];

        if ($releaseState) {
            $event['release_state'] = $releaseState;
        }

        $jsonLine = json_encode($event, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        File::append($this->logFilePath, $jsonLine);

        return $event;
    }

    /**
     * Get all events from the log file
     *
     * @return array
     */
    public function getEvents(): array
    {
        if (!File::exists($this->logFilePath)) {
            return [];
        }

        $lines = file($this->logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $events = [];

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if ($decoded) {
                $events[] = $decoded;
            }
        }

        return $events;
    }

    /**
     * Ensure the target directory exists
     */
    protected function ensureDirectoryExists(): void
    {
        $directory = dirname($this->logFilePath);
        
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        if (!File::exists($this->logFilePath)) {
            File::put($this->logFilePath, '');
        }
    }
}
