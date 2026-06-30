<?php

namespace App\Services\Governance;

use Illuminate\Support\Facades\Log;
use App\Services\Logging\LogService;

class GovernanceDashboardService
{
    private string $sabRoot;
    private string $logsRoot;

    public function __construct()
    {
        $this->sabRoot = base_path('.sab');
        $this->logsRoot = base_path('logs');
    }

    // ──────────────────────────────────────────────
    // A. Pending Proposals
    // ──────────────────────────────────────────────

    /**
     * Create a new SAB proposal JSON file
     *
     * @param string $target Dot-separated authority path (e.g., "governance.feature_health")
     * @param string $action One of: append, update, merge
     * @param mixed $value Any JSON-serializable value
     * @param array $meta Optional metadata: reason, risk, rule, engine
     * @return string|null Created proposal filename, or null on failure
     */
    public function createProposal(string $target, string $action, mixed $value, array $meta = []): ?string
    {
        $allowedActions = ['append', 'update', 'merge'];
        if (!in_array($action, $allowedActions, true)) {
            Log::warning('GovernanceDashboard: invalid proposal action', ['action' => $action]);
            return null;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*$/', $target)) {
            Log::warning('GovernanceDashboard: invalid proposal target', ['target' => $target]);
            return null;
        }

        $dir = $this->sabRoot . '/proposals';
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                Log::error('GovernanceDashboard: cannot create proposals directory');
                return null;
            }
        }

        $timestamp = date('Ymd-His');
        $pid = getmypid() ?: random_int(1000, 99999);
        $filename = "proposal-ups-{$timestamp}-{$pid}.json";

        $proposal = [
            'target' => $target,
            'action' => $action,
            'value' => $value,
            '_meta' => array_merge([
                'reason' => $meta['reason'] ?? 'UPS Feature Health violation',
                'risk' => $meta['risk'] ?? 'low',
                'rule' => $meta['rule'] ?? 'ups_feature_health',
                'engine' => 'ups-governance-ui',
                'decided_at' => date('Y-m-d H:i:s'),
            ], $meta),
        ];

        $path = $dir . '/' . $filename;
        $result = file_put_contents($path, json_encode($proposal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($result === false) {
            Log::error('GovernanceDashboard: failed to write proposal', ['path' => $path]);
            return null;
        }

        Log::info('GovernanceDashboard: proposal created', [
            'filename' => $filename,
            'target' => $target,
            'action' => $action,
        ]);

        return $filename;
    }

    public function getPendingProposals(array $filters = []): array
    {
        $dir = $this->sabRoot . '/proposals';
        return $this->parseProposalDirectory($dir, $filters, 'pending');
    }

    public function getPendingCount(): int
    {
        $dir = $this->sabRoot . '/proposals';
        if (!is_dir($dir)) {
            return 0;
        }

        return count(glob($dir . '/*.json') ?: []);
    }

    // ──────────────────────────────────────────────
    // B. Applied History
    // ──────────────────────────────────────────────

    public function getAppliedHistory(array $filters = [], int $limit = 50): array
    {
        $dir = $this->sabRoot . '/history/proposals';
        $proposals = $this->parseProposalDirectory($dir, $filters, 'applied');

        usort($proposals, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        return array_slice($proposals, 0, $limit);
    }

    public function getAppliedCount(): int
    {
        $dir = $this->sabRoot . '/history/proposals';
        if (!is_dir($dir)) {
            return 0;
        }

        return count(glob($dir . '/*.json') ?: []);
    }

    public function getAppliedTodayCount(): int
    {
        $dir = $this->sabRoot . '/history/proposals';
        if (!is_dir($dir)) {
            return 0;
        }

        $todayStart = strtotime('today');
        $count = 0;
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            if (filemtime($file) >= $todayStart) {
                $count++;
            }
        }

        return $count;
    }

    // ──────────────────────────────────────────────
    // C. Audit Timeline
    // ──────────────────────────────────────────────

    /**
     * Append an entry to the SAB audit log
     *
     * @param string $level SUCCESS, DECISION, BLOCKED, REJECTED, AUTO_RUN, SCAN
     * @param string $message Human-readable audit message
     */
    public function appendAuditLog(string $level, string $message): void
    {
        $file = $this->sabRoot . '/history/audit.log';
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $entry = sprintf("[%s][%s] %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message);
        file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }

    public function getAuditTimeline(int $limit = 100): array
    {
        $file = $this->sabRoot . '/history/audit.log';
        if (!is_file($file) || !is_readable($file)) {
            return [];
        }

        try {
            $lines = $this->tailFile($file, $limit);
            $entries = [];

            foreach (array_reverse($lines) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $entries[] = $this->parseAuditLine($line);
            }

            return $entries;
        } catch (\Throwable $e) {
            LogService::error('GovernanceDashboard: audit.log parse error', [
                'file' => $file,
            ], $e);
            return [];
        }
    }

    // ──────────────────────────────────────────────
    // D. Authority Snapshot
    // ──────────────────────────────────────────────

    public function getAuthoritySummary(): array
    {
        $paths = [
            $this->sabRoot . '/authority/authority.json',
            $this->sabRoot . '/latest/authority.json',
            $this->sabRoot . '/authority.json',
        ];

        foreach ($paths as $path) {
            if (is_file($path) && is_readable($path)) {
                return $this->parseAuthority($path);
            }
        }

        return [
            'aktiflik_durumu' => 'missing',
            'path' => null,
            'last_modified' => null,
            'size_bytes' => 0,
            'version' => null,
            'top_level_keys' => [],
            'key_count' => 0,
            'project_name' => null,
            'enforcement_level' => null,
        ];
    }

    // ──────────────────────────────────────────────
    // E. System Health
    // ──────────────────────────────────────────────

    public function getSystemHealth(): array
    {
        return [
            'watcher' => $this->getWatcherHealth(),
            'pipeline' => $this->getPipelineHealth(),
            'sync' => $this->getSyncHealth(),
            'proposals' => [
                'pending_count' => $this->getPendingCount(),
                'applied_count' => $this->getAppliedCount(),
            ],
            'audit_log' => $this->getFileHealth($this->sabRoot . '/history/audit.log'),
            'authority' => $this->getAuthorityHealth(),
            'decisions_log' => $this->getFileHealth($this->sabRoot . '/history/decisions.log'),
            'openclaw' => $this->getOpenClawStats(),
        ];
    }

    /**
     * Get OpenClaw (Agent Governance) operational metrics.
     */
    public function getOpenClawStats(): array
    {
        try {
            $auditService = app(\App\Services\OpenClaw\OpenClawAuditService::class);
            $window24h = 1440; // 24 hours
            
            $stats = $auditService->getWindowStats($window24h);
            
            $excludedServices = config('openclaw.excluded_services', []);
            $staleCount = 0;
            $today = now();
            
            foreach ($excludedServices as $fqcn => $meta) {
                $reviewDate = $meta['review_date'] ?? 'unset';
                if ($reviewDate !== 'unset') {
                    try {
                        if ($today->greaterThan(\Illuminate\Support\Carbon::parse($reviewDate))) {
                            $staleCount++;
                        }
                    } catch (\Throwable) {
            \Illuminate\Support\Facades\Log::error("Silent catch: " . $e->getMessage());
                        $staleCount++;
                    }
                }
            }
            
            return [
                'enabled' => (bool) config('openclaw.enabled', false),
                'proposal_only' => (bool) config('openclaw.proposal_only', true),
                'stats_24h' => $stats,
                'excluded_count' => count($excludedServices),
                'stale_excluded_count' => $staleCount,
            ];
        } catch (\Throwable $e) {
            LogService::error('GovernanceDashboard: OpenClaw stats error', [], $e);
            return [
                'enabled' => false,
                'proposal_only' => true,
                'stats_24h' => ['total_requests' => 0, 'blocked_count' => 0, 'violation_count' => 0, 'block_rate' => 0],
                'excluded_count' => 0,
                'stale_excluded_count' => 0,
                'error' => true,
            ];
        }
    }


    // ──────────────────────────────────────────────
    // F. Overview Cards
    // ──────────────────────────────────────────────

    public function getOverview(): array
    {
        $authority = $this->getAuthoritySummary();
        $watcherHealth = $this->getWatcherHealth();
        $pipelineHealth = $this->getPipelineHealth();
        $syncHealth = $this->getSyncHealth();

        return [
            'pending_count' => $this->getPendingCount(),
            'applied_today' => $this->getAppliedTodayCount(),
            'last_pipeline_success' => $pipelineHealth['last_success_at'],
            'last_sync' => $syncHealth['last_sync_at'],
            'authority_durumu' => $authority['aktiflik_durumu'],
            'watcher_durumu' => $watcherHealth['aktiflik_durumu'],
        ];
    }

    // ──────────────────────────────────────────────
    //  PRIVATE: Proposal Parser
    // ──────────────────────────────────────────────

    private function parseProposalDirectory(string $dir, array $filters, string $defaultResult): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.json') ?: [];
        $proposals = [];

        foreach ($files as $file) {
            $proposal = $this->parseProposalFile($file, $defaultResult);
            if ($proposal === null) {
                continue;
            }

            if ($this->matchesFilters($proposal, $filters)) {
                $proposals[] = $proposal;
            }
        }

        return $proposals;
    }

    private function parseProposalFile(string $file, string $defaultResult): ?array
    {
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $filename = basename($file, '.json');
        $content = file_get_contents($file);

        if ($content === false) {
            return [
                'id' => $filename,
                'target' => '—',
                'action' => '—',
                'value' => null,
                'reason' => '—',
                'engine' => 'unknown',
                'risk' => 'n/a',
                'rule' => null,
                'decided_at' => null,
                'timestamp' => filemtime($file),
                'result' => $defaultResult,
                'parse_error' => true,
            ];
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            return [
                'id' => $filename,
                'target' => '—',
                'action' => '—',
                'value' => null,
                'reason' => '—',
                'engine' => 'unknown',
                'risk' => 'n/a',
                'rule' => null,
                'decided_at' => null,
                'timestamp' => filemtime($file),
                'result' => $defaultResult,
                'parse_error' => true,
            ];
        }

        $meta = $data['_meta'] ?? $data['meta'] ?? [];

        return [
            'id' => $filename,
            'target' => $data['target'] ?? '—',
            'action' => $data['action'] ?? '—',
            'value' => $data['value'] ?? null,
            'reason' => $meta['reason'] ?? $data['reason'] ?? '—',
            'engine' => $meta['engine'] ?? 'manual',
            'risk' => $meta['risk'] ?? 'n/a',
            'rule' => $meta['rule'] ?? null,
            'decided_at' => $meta['decided_at'] ?? $data['decided_at'] ?? null,
            'timestamp' => filemtime($file),
            'result' => $defaultResult,
            'parse_error' => false,
        ];
    }

    private function matchesFilters(array $proposal, array $filters): bool
    {
        if (!empty($filters['target']) && stripos($proposal['target'], $filters['target']) === false) {
            return false;
        }

        if (!empty($filters['action']) && $proposal['action'] !== $filters['action']) {
            return false;
        }

        if (!empty($filters['engine']) && $proposal['engine'] !== $filters['engine']) {
            return false;
        }

        if (!empty($filters['search'])) {
            $needle = strtolower($filters['search']);
            $haystack = strtolower(implode(' ', [
                $proposal['id'],
                $proposal['target'],
                $proposal['action'],
                $proposal['reason'],
                $proposal['engine'],
            ]));
            if (strpos($haystack, $needle) === false) {
                return false;
            }
        }

        return true;
    }

    // ──────────────────────────────────────────────
    //  PRIVATE: Audit Log Parser
    // ──────────────────────────────────────────────

    private function parseAuditLine(string $line): array
    {
        // Pattern: [2026-04-03 20:07:35][SUCCESS] Message
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]\[([A-Z]+)\]\s*(.*)$/';

        if (preg_match($pattern, $line, $matches)) {
            $proposalId = null;
            if (preg_match('/proposal[- ]([a-zA-Z0-9_-]+)/', $matches[3], $pidMatch)) {
                $proposalId = $pidMatch[1];
            }

            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => $matches[3],
                'proposal_id' => $proposalId,
                'raw' => false,
            ];
        }

        // Fallback: unparseable line
        return [
            'timestamp' => null,
            'level' => 'RAW',
            'message' => $line,
            'proposal_id' => null,
            'raw' => true,
        ];
    }

    // ──────────────────────────────────────────────
    //  PRIVATE: Authority Parser
    // ──────────────────────────────────────────────

    private function parseAuthority(string $path): array
    {
        try {
            $content = file_get_contents($path);
            if ($content === false) {
                return [
                    'aktiflik_durumu' => 'unreadable',
                    'path' => $path,
                    'last_modified' => filemtime($path),
                    'size_bytes' => 0,
                    'version' => null,
                    'top_level_keys' => [],
                    'key_count' => 0,
                    'project_name' => null,
                    'enforcement_level' => null,
                ];
            }

            $data = json_decode($content, true);
            if (!is_array($data)) {
                return [
                    'aktiflik_durumu' => 'malformed',
                    'path' => $path,
                    'last_modified' => filemtime($path),
                    'size_bytes' => strlen($content),
                    'version' => null,
                    'top_level_keys' => [],
                    'key_count' => 0,
                    'project_name' => null,
                    'enforcement_level' => null,
                ];
            }

            return [
                'aktiflik_durumu' => 'healthy',
                'path' => $path,
                'last_modified' => filemtime($path),
                'size_bytes' => strlen($content),
                'version' => $data['version'] ?? null,
                'top_level_keys' => array_keys($data),
                'key_count' => count($data),
                'project_name' => $data['project']['name'] ?? null,
                'enforcement_level' => $data['context7_standards']['enforcement_level'] ?? null,
            ];
        } catch (\Throwable $e) {
            LogService::error('GovernanceDashboard: authority parse error', [
                'path' => $path,
            ], $e);
            return [
                'aktiflik_durumu' => 'error',
                'path' => $path,
                'last_modified' => null,
                'size_bytes' => 0,
                'version' => null,
                'top_level_keys' => [],
                'key_count' => 0,
                'project_name' => null,
                'enforcement_level' => null,
            ];
        }
    }

    // ──────────────────────────────────────────────
    //  PRIVATE: Health Checks
    // ──────────────────────────────────────────────

    private function getWatcherHealth(): array
    {
        $logFile = $this->logsRoot . '/sab-watch.log';
        if (!is_file($logFile)) {
            return [
                'aktiflik_durumu' => 'unknown',
                'last_event_at' => null,
                'last_event' => null,
            ];
        }

        $lastLines = $this->tailFile($logFile, 10);
        $lastEventTime = null;
        $lastEvent = null;

        foreach (array_reverse($lastLines) as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]\[WATCH\]/', $line, $m)) {
                $lastEventTime = $m[1];
                $lastEvent = trim($line);
                break;
            }
        }

        // Heuristic: if last event was within 5 minutes, consider running
        $isRecent = false;
        if ($lastEventTime) {
            $diff = time() - strtotime($lastEventTime);
            $isRecent = $diff < 300;
        }

        return [
            'aktiflik_durumu' => $isRecent ? 'running' : 'stopped',
            'last_event_at' => $lastEventTime,
            'last_event' => $lastEvent,
        ];
    }

    private function getPipelineHealth(): array
    {
        $logFile = $this->logsRoot . '/sab-watch.log';
        if (!is_file($logFile)) {
            return [
                'last_success_at' => null,
                'last_event' => null,
            ];
        }

        $lines = $this->tailFile($logFile, 50);
        foreach (array_reverse($lines) as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]\[WATCH\].*Pipeline success/', $line, $m)) {
                return [
                    'last_success_at' => $m[1],
                    'last_event' => trim($line),
                ];
            }
        }

        return [
            'last_success_at' => null,
            'last_event' => null,
        ];
    }

    private function getSyncHealth(): array
    {
        $logFile = $this->logsRoot . '/drive-sync.log';
        if (!is_file($logFile)) {
            return [
                'last_sync_at' => null,
                'last_event' => null,
            ];
        }

        $lines = $this->tailFile($logFile, 20);
        foreach (array_reverse($lines) as $line) {
            if (preg_match('/^\[SYNC\]\[DONE\]\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $line, $m)) {
                return [
                    'last_sync_at' => $m[1],
                    'last_event' => trim($line),
                ];
            }
        }

        return [
            'last_sync_at' => null,
            'last_event' => null,
        ];
    }

    private function getFileHealth(string $path): array
    {
        if (!is_file($path)) {
            return ['aktiflik_durumu' => 'missing', 'size_bytes' => 0, 'last_modified' => null];
        }

        return [
            'aktiflik_durumu' => is_readable($path) ? 'healthy' : 'unreadable',
            'size_bytes' => filesize($path),
            'last_modified' => filemtime($path),
        ];
    }

    private function getAuthorityHealth(): array
    {
        $summary = $this->getAuthoritySummary();
        return [
            'aktiflik_durumu' => $summary['aktiflik_durumu'],
            'version' => $summary['version'],
            'last_modified' => $summary['last_modified'],
        ];
    }

    // ──────────────────────────────────────────────
    //  PRIVATE: Utility
    // ──────────────────────────────────────────────

    private function tailFile(string $path, int $lines): array
    {
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        try {
            $file = new \SplFileObject($path, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();

            $start = max(0, $totalLines - $lines);
            $result = [];

            $file->seek($start);
            while (!$file->eof()) {
                $line = $file->current();
                if (is_string($line) && trim($line) !== '') {
                    $result[] = trim($line);
                }
                $file->next();
            }

            return $result;
        } catch (\Throwable $e) {
            LogService::error('GovernanceDashboard: tailFile error', [
                'path' => $path,
            ], $e);
            return [];
        }
    }
}
