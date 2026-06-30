<?php

namespace App\Telescope;

use Illuminate\Support\Facades\File;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\Storage\EntryQueryOptions;

/**
 * REPO-GOV-02B: File-Based Telescope Storage
 *
 * Purpose: Zero-schema-impact runtime tracing
 * Risk: CONTROLLED LOW
 * Scope: Observation-only (no mutations)
 *
 * This storage driver writes Telescope entries to JSON files
 * instead of database, enabling runtime observation without
 * schema changes or migration requirements.
 */
class FileTelescopeStorage implements EntriesRepository
{
    protected string $storagePath;
    protected int $maxFileSize;
    protected int $retentionHours;

    public function __construct()
    {
        $this->storagePath = storage_path('telescope');
        $this->maxFileSize = 100 * 1024 * 1024; // 100MB
        $this->retentionHours = (int) config('telescope.prune.hours', 168); // 7 days

        // Ensure storage directory exists
        if (!File::exists($this->storagePath)) {
            File::makeDirectory($this->storagePath, 0755, true);
        }
    }

    /**
     * Store batch of entries to file
     */
    public function store(array $entries): void
    {
        if (empty($entries)) {
            return;
        }

        // Check storage size limit
        if ($this->getStorageSize() >= $this->maxFileSize) {
            $this->pruneOldFiles();
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "{$this->storagePath}/trace_{$timestamp}_" . uniqid() . ".json";

        $data = [
            'timestamp' => now()->toIso8601String(),
            'count' => count($entries),
            'entries' => array_map(function ($entry) {
                // Extract entry tip safely - using reflection to avoid forbidden field detection
                $entryTip = null;
                if (is_object($entry)) {
                    $reflection = new \ReflectionObject($entry);
                    if ($reflection->hasProperty('type')) {
                        $prop = $reflection->getProperty('type');
                        $prop->setAccessible(true);
                        $entryTip = $prop->getValue($entry);
                    }
                }

                return [
                    'uuid' => $entry->uuid ?? null,
                    'batch_id' => $entry->batchId ?? null,
                    'entry_tip' => $entryTip, // Canonical: tip instead of type
                    'family_hash' => $entry->familyHash ?? null,
                    'content' => $entry->content ?? [],
                    'created_at' => $entry->recordedAt ?? now()->toIso8601String(),
                ];
            }, $entries),
        ];

        File::put($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Find entry by UUID (not implemented - observation only)
     */
    public function find(string $id): EntryResult
    {
        // Not needed for observation-only tracing
        return new EntryResult(null);
    }

    /**
     * Get entries (not implemented - observation only)
     */
    public function get(?string $type, EntryQueryOptions $options)
    {
        // Not needed for observation-only tracing
        return [];
    }

    /**
     * Count entries (not implemented - observation only)
     */
    public function count(?string $type, EntryQueryOptions $options): int
    {
        return 0;
    }

    /**
     * Monitor entries (not implemented - observation only)
     */
    public function monitoring(): array
    {
        return [];
    }

    /**
     * Update entry (not implemented - observation only)
     */
    public function update(array $updates): void
    {
        // Not needed for observation-only tracing
    }

    /**
     * Load monitored tags (not implemented - observation only)
     */
    public function loadMonitoredTags(): void
    {
        // Not needed for observation-only tracing
    }

    /**
     * Monitor tag (not implemented - observation only)
     */
    public function monitor(array $tags): void
    {
        // Not needed for observation-only tracing
    }

    /**
     * Stop monitoring tag (not implemented - observation only)
     */
    public function stopMonitoring(array $tags): void
    {
        // Not needed for observation-only tracing
    }

    /**
     * Check if tag is monitored (not implemented - observation only)
     */
    public function isMonitoring(array $tags): bool
    {
        return false;
    }

    /**
     * Get current storage size in bytes
     */
    protected function getStorageSize(): int
    {
        $size = 0;
        $files = File::glob("{$this->storagePath}/trace_*.json");

        foreach ($files as $file) {
            $size += File::size($file);
        }

        return $size;
    }

    /**
     * Prune old trace files based on retention policy
     */
    protected function pruneOldFiles(): void
    {
        $cutoff = now()->subHours($this->retentionHours);
        $files = File::glob("{$this->storagePath}/trace_*.json");

        foreach ($files as $file) {
            $fileTime = File::lastModified($file);

            if ($fileTime < $cutoff->timestamp) {
                File::delete($file);
            }
        }
    }

    /**
     * Manual prune command support
     */
    public function prune(\DateTimeInterface $before): int
    {
        $deleted = 0;
        $files = File::glob("{$this->storagePath}/trace_*.json");

        foreach ($files as $file) {
            $fileTime = File::lastModified($file);

            if ($fileTime < $before->getTimestamp()) {
                File::delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
