<?php

namespace App\Services\Ydl\Phase2B;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

/**
 * FileHashStore — Phase 2B Certification Gate 4/4.
 *
 * Tracks pre-patch file hashes to enable:
 *   - Pre-write verification: file hasn't changed since patch was planned
 *   - Post-write verification: written content matches planned content
 *   - Rollback on conflict
 *
 * Uses Laravel Cache as backing store (can be swapped for JSON file).
 * Hash key format: "ydl:hash:{relative_path}"
 */
class FileHashStore
{
    private string $basePath;
    private string $prefix = 'ydl:hash:';

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Store the current (pre-patch) hash of a file.
     * Call BEFORE applying a patch.
     */
    public function snapshot(string $relativePath): string
    {
        $fullPath = $this->resolvePath($relativePath);

        if (!file_exists($fullPath)) {
            $hash = md5('');
            $this->put($relativePath, $hash);
            return $hash;
        }

        $hash = md5_file($fullPath);
        $this->put($relativePath, $hash);
        return $hash;
    }

    /**
     * Verify the stored snapshot hash matches the current file hash.
     * Call BEFORE applying patch to detect concurrent changes.
     *
     * @return array{valid: bool, reason: string}
     */
    public function verifyPreApply(string $relativePath, string $expectedHash): array
    {
        $currentHash = $this->currentHash($relativePath);

        if ($currentHash !== $expectedHash) {
            return [
                'valid' => false,
                'reason' => "HASH_MISMATCH: {$relativePath} changed since patch was planned "
                    . "(expected: " . substr($expectedHash, 0, 8) . "..., "
                    . "current: " . substr($currentHash, 0, 8) . "...)",
            ];
        }

        return ['valid' => true, 'reason' => 'Hash verified'];
    }

    /**
     * Verify the written content matches the planned hash.
     * Call AFTER applying patch to confirm write fidelity.
     *
     * @return array{valid: bool, reason: string}
     */
    public function verifyPostApply(string $relativePath, string $plannedHash): array
    {
        $writtenHash = $this->currentHash($relativePath);

        if ($writtenHash !== $plannedHash) {
            return [
                'valid' => false,
                'reason' => "WRITE_VERIFY_FAILED: {$relativePath} content hash mismatch "
                    . "(planned: " . substr($plannedHash, 0, 8) . "..., "
                    . "written: " . substr($writtenHash, 0, 8) . "...)",
            ];
        }

        return ['valid' => true, 'reason' => 'Write verified'];
    }

    /**
     * Invalidate the stored snapshot after a successful patch.
     */
    public function invalidate(string $relativePath): void
    {
        Cache::forget($this->prefix . $relativePath);
    }

    /**
     * Get all tracked file hashes (for audit/debug).
     */
    public function allSnapshots(): array
    {
        // Scan cache for ydl:hash: prefix entries
        $snapshots = [];
        foreach (Cache::getStore()->keys($this->prefix . '*') as $key) {
            $relativePath = substr($key, strlen($this->prefix));
            $snapshots[$relativePath] = Cache::get($key);
        }
        return $snapshots;
    }

    private function put(string $relativePath, string $hash): void
    {
        Cache::put($this->prefix . $relativePath, $hash, now()->addDays(30));
    }

    private function currentHash(string $relativePath): string
    {
        $fullPath = $this->resolvePath($relativePath);

        if (!file_exists($fullPath)) {
            return md5('');
        }

        return md5_file($fullPath);
    }

    private function resolvePath(string $relativePath): string
    {
        return $this->basePath . '/' . ltrim($relativePath, '/');
    }
}
