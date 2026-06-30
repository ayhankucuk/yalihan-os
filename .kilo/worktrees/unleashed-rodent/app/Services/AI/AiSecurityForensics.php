<?php

namespace App\Services\AI;

use App\Models\AiSecurityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class AiSecurityForensics
 *
 * SAB Phase 14 Sprint 1: Hash Chain Forensic Logging
 * Provides tamper-proof audit trail for AI security events using SHA-256 hash chains.
 * Each log entry is cryptographically linked to the previous entry.
 *
 * @package App\Services\AI
 */
class AiSecurityForensics
{
    /**
     * Log security event with hash chain integrity
     *
     * Hash Chain Structure:
     * - current_hash = SHA256(id + event_type + user_id + context + previous_hash + created_at)
     * - First entry: previous_hash = null
     * - Subsequent entries: previous_hash = previous entry's current_hash
     *
     * @param string $eventType 'prompt_injection', 'sql_injection', 'spam_detected', 'high_anomaly_score', etc.
     * @param int $userId
     * @param array $context Event-specific data
     * @return AiSecurityLog
     */
    public function logSecurityEvent(string $eventType, int $userId, array $context): AiSecurityLog
    {
        // Get last hash for chain linking
        $previousHash = $this->getLastHash();

        // Create log entry (without hash first)
        $log = new AiSecurityLog();
        $log->event_type = $eventType;
        $log->user_id = $userId;
        $log->context = $context;
        $log->previous_hash = $previousHash;
        $log->save();

        // Calculate current hash (now that we have ID and created_at)
        $currentHash = $this->calculateHash([
            'id' => $log->id,
            'event_type' => $log->event_type,
            'user_id' => $log->user_id,
            'context' => json_encode($log->context),
            'previous_hash' => $log->previous_hash,
            'created_at' => $log->created_at->toIso8601String(),
        ]);

        // Update with calculated hash
        $log->current_hash = $currentHash;
        $log->save();

        Log::info('AI Security Event Logged', [
            'event_type' => $eventType,
            'user_id' => $userId,
            'log_id' => $log->id,
            'hash' => $currentHash,
        ]);

        return $log;
    }

    /**
     * Verify hash chain integrity
     *
     * Recalculates hash for each entry and compares with stored hash.
     * If mismatch found, tamper detected.
     *
     * @return array ['valid' => bool, 'broken_at' => int|null, 'total_checked' => int]
     */
    public function verifyHashChain(): array
    {
        $logs = AiSecurityLog::orderBy('id')->get();
        $totalChecked = 0;
        $brokenAt = null;

        foreach ($logs as $log) {
            $totalChecked++;

            // Recalculate hash
            $expectedHash = $this->calculateHash([
                'id' => $log->id,
                'event_type' => $log->event_type,
                'user_id' => $log->user_id,
                'context' => json_encode($log->context),
                'previous_hash' => $log->previous_hash,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

            // Compare with stored hash
            if ($expectedHash !== $log->current_hash) {
                $brokenAt = $log->id;

                Log::critical('AI Security: Hash chain integrity violation detected', [
                    'log_id' => $log->id,
                    'expected_hash' => $expectedHash,
                    'stored_hash' => $log->current_hash,
                ]);

                break;
            }
        }

        return [
            'valid' => $brokenAt === null,
            'broken_at' => $brokenAt,
            'total_checked' => $totalChecked,
        ];
    }

    /**
     * Get the last log entry's hash for chain linking
     *
     * @return string|null
     */
    protected function getLastHash(): ?string
    {
        $lastLog = AiSecurityLog::orderBy('id', 'desc')->first();

        return $lastLog?->current_hash;
    }

    /**
     * Calculate SHA-256 hash for log entry
     *
     * @param array $data
     * @return string
     */
    protected function calculateHash(array $data): string
    {
        // Concatenate all fields in deterministic order
        $payload = implode('|', [
            $data['id'] ?? '',
            $data['event_type'] ?? '',
            $data['user_id'] ?? '',
            $data['context'] ?? '',
            $data['previous_hash'] ?? '',
            $data['created_at'] ?? '',
        ]);

        return hash('sha256', $payload);
    }
}
