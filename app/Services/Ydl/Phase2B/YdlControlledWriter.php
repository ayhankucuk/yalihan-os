<?php

namespace App\Services\Ydl\Phase2B;

use App\DTOs\Ydl\Events\YdlEvent;
use App\Services\Ydl\Patchers\YdlPatch;
use App\Services\Ydl\YdlEventLog;

/**
 * YdlControlledWriter — Phase 2B write executor.
 *
 * Writes patches ONLY after YdlWriteGuard::authorize() has passed all four gates.
 * Implements rollback on failure: if any patch write fails, revert all prior writes.
 *
 * Idempotency contract:
 *   - Safe to call multiple times with the same event
 *   - If files already match planned content, skips silently
 *   - Never writes to non-whitelisted paths
 */
class YdlControlledWriter
{
    private FileHashStore $hashStore;
    private YdlEventLog $eventLog;
    private string $basePath;

    /** @var array<string, string> Paths modified in current write session (for rollback). */
    private array $writtenPaths = [];

    public function __construct(
        ?FileHashStore $hashStore = null,
        ?YdlEventLog $eventLog = null,
        ?string $basePath = null
    ) {
        $this->hashStore = $hashStore ?? new FileHashStore();
        $this->eventLog = $eventLog ?? new YdlEventLog();
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Apply whitelisted patches idempotently.
     *
     * @param YdlPatch[] $patches
     * @return array{applied: int, skipped: int, failed: int, errors: array, event_id: string}
     */
    public function apply(array $patches, YdlEvent $event): array
    {
        $applied = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $this->writtenPaths = [];

        foreach ($patches as $patch) {
            if ($patch->isNoOp()) {
                $skipped++;
                continue;
            }

            $result = $this->applyPatch($patch);

            if ($result['status'] === 'applied') {
                $applied++;
            } elseif ($result['status'] === 'skipped') {
                $skipped++;
            } else {
                $failed++;
                $errors[] = $result['error'];

                // Rollback all prior writes in this session
                $this->rollback();
                break;
            }
        }

        // Log event to event log only if ALL patches applied (or all were already no-ops)
        if ($failed === 0) {
            $this->eventLog->append($event);
        }

        return [
            'applied' => $applied,
            'skipped' => $skipped,
            'failed'  => $failed,
            'errors'  => $errors,
            'event_id' => $event->eventId,
        ];
    }

    /**
     * Check what would change without actually writing.
     *
     * @param YdlPatch[] $patches
     * @return array{would_change: array, noop: array, total: int}
     */
    public function dryRun(array $patches): array
    {
        $wouldChange = [];
        $noop = [];

        foreach ($patches as $patch) {
            if ($patch->isNoOp()) {
                $noop[] = $patch->toArray();
            } else {
                $wouldChange[] = $patch->toArray();
            }
        }

        return [
            'would_change' => $wouldChange,
            'noop' => $noop,
            'total' => count($patches),
        ];
    }

    /**
     * @return array{status: 'applied'|'skipped'|'failed', error?: string}
     */
    private function applyPatch(YdlPatch $patch): array
    {
        $fullPath = $this->resolvePath($patch->target);

        // Idempotency: if file already has planned content, skip
        if (file_exists($fullPath)) {
            $currentHash = md5_file($fullPath);
            if ($currentHash === $patch->plannedHash) {
                return ['status' => 'skipped', 'reason' => 'content already matches planned hash'];
            }
        }

        // Ensure parent directory exists
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                return ['status' => 'failed', 'error' => "mkdir failed for: {$dir}"];
            }
        }

        // Write content
        $written = file_put_contents($fullPath, $patch->newContent);
        if ($written === false) {
            return ['status' => 'failed', 'error' => "file_put_contents failed for: {$patch->target}"];
        }

        // Post-write verification
        $verify = $this->hashStore->verifyPostApply($patch->target, $patch->plannedHash);
        if (!$verify['valid']) {
            // Rollback immediately
            unlink($fullPath);
            return ['status' => 'failed', 'error' => $verify['reason']];
        }

        $this->writtenPaths[] = $patch->target;
        return ['status' => 'applied'];
    }

    /**
     * Rollback all writes performed in the current session.
     */
    private function rollback(): void
    {
        foreach (array_reverse($this->writtenPaths) as $path) {
            $fullPath = $this->resolvePath($path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            $this->hashStore->invalidate($path);
        }
        $this->writtenPaths = [];
    }

    private function resolvePath(string $relativePath): string
    {
        return $this->basePath . '/' . ltrim($relativePath, '/');
    }
}
