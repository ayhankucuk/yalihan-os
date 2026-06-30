<?php

namespace App\Console\Commands;

use App\Enums\IlanDurumu;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EnvDriftGuard extends Command
{
    protected $signature = 'system:env-drift-guard
        {--json : Output as JSON (CI-ready, includes GitHub Actions annotations)}
        {--strict : Treat warnings as failures (CI fail-fast mode)}
        {--fix : Run safe recovery actions (cache clear only, blocked in --strict mode)}
        {--role= : Execution role (ci|local|emergency). Auto-detected if omitted.}
        {--bypass-token= : Activate a pre-approved bypass contract by ID (e.g. BYP-2026-04-10-001)}
        {--policy-validate : Validate policy integrity only (lock + completeness), skip all checks}';

    protected $description = '🧿 Environment & Schema Drift Guard — governance contract v3.2';

    private array $checks = [];
    private int $failures = 0;
    private int $warnings = 0;
    private int $bypassed = 0;
    private array $policy = [];
    private array $fixActions = [];
    private string $resolvedRole = 'local';
    private ?array $activeBypass = null;

    /**
     * Guard Contract (v3.2.0):
     *  Policy SSOT: config/env-drift-guard.php
     *  ADR: docs/adr/2026-04-10-env-drift-guard-contract.md
     *  - Severity driven by config, not hardcode
     *  - --fix: safe cache-clear only (see config fix_allowed/fix_forbidden)
     *  - --strict: promotes all warnings → failures (CI enforcement)
     *  - --fix + --strict: REJECTED (CI must never auto-fix)
     *  - --role: ci|local|emergency (auto-detect from CI env vars)
     *  - --bypass-token: token-based bypass with contract file validation
     *  - --policy-validate: validate policy integrity only, skip checks
     *  - Policy lock: SHA-256 of config detects unauthorized changes
     *  - Non-bypassable checks: ALWAYS enforce regardless of token
     *  - READ-ONLY by default — check operations NEVER mutate
     */
    public function handle(): int
    {
        $this->policy = config('env-drift-guard', []);
        $isJson = $this->option('json');
        $isStrict = $this->option('strict');
        $isFix = $this->option('fix');
        $bypassToken = $this->option('bypass-token');
        $isPolicyValidate = $this->option('policy-validate');

        // ── Step 0: Resolve execution role ─────────────────────
        $this->resolvedRole = $this->resolveRole();
        $roleConfig = $this->policy['roles'][$this->resolvedRole] ?? [];

        // Role-based implicit strict
        if (!empty($roleConfig['implicit_strict'])) {
            $isStrict = true;
        }

        // ── Policy validate mode (early return) ───────────────
        if ($isPolicyValidate) {
            return $this->runPolicyValidate($isJson);
        }

        // ── Mutual exclusion: --fix + --strict ────────────────
        if ($isFix && $isStrict) {
            return $this->rejectWithError(
                '--fix and --strict cannot be used together. CI must fail-fast, never auto-fix.',
                '--fix and --strict are mutually exclusive',
                $isJson
            );
        }

        // ── Role-based fix blocking ───────────────────────────
        if ($isFix && isset($roleConfig['fix_allowed']) && $roleConfig['fix_allowed'] === false) {
            return $this->rejectWithError(
                "--fix is not allowed in '{$this->resolvedRole}' role.",
                "--fix blocked in {$this->resolvedRole} role",
                $isJson
            );
        }

        // ── Mutual exclusion: --fix + --bypass-token ──────────
        if ($isFix && $bypassToken !== null) {
            return $this->rejectWithError(
                '--fix cannot be used with --bypass-token. Bypass mode is read-only.',
                '--fix and --bypass-token are mutually exclusive',
                $isJson
            );
        }

        // ── Bypass token validation ───────────────────────────
        if ($bypassToken !== null) {
            // Role allows bypass?
            if (isset($roleConfig['bypass_allowed']) && $roleConfig['bypass_allowed'] === false) {
                return $this->rejectWithError(
                    "Bypass is not allowed in '{$this->resolvedRole}' role.",
                    "bypass blocked in {$this->resolvedRole} role",
                    $isJson
                );
            }

            $bypassResult = $this->loadAndValidateBypass($bypassToken, $isJson);
            if ($bypassResult !== null) {
                return $bypassResult; // Validation failed
            }
            // $this->activeBypass is now set
        }

        if (!$isJson) {
            $this->info('');
            $this->info('🧿  Env-Drift Guard v3.2 — Governance Contract Enforcement');
            $this->info('─────────────────────────────────────────');
            $this->info("   Role: {$this->resolvedRole}");
            if ($this->activeBypass) {
                $this->warn("   🎫 Bypass: {$this->activeBypass['bypass_id']} (checks: " . implode(', ', $this->activeBypass['checks']) . ')');
                $this->warn("      Expires: {$this->activeBypass['expires_at']}");
            }
            if ($isFix) {
                $this->warn('   Mode: FIX (safe cache operations only)');
            }
            $this->newLine();
        }

        // ── Step 1: Policy lock check ─────────────────────────
        $this->checkPolicyLock();

        // Run all checks (always read-only)
        $this->checkEnvTesting();
        $this->checkDbConnectivity();
        $this->checkSchemaFiles();
        $this->checkSchemaChecksum();
        $this->checkSchemaDiff();
        $this->checkFillableAlignment();
        $this->checkMigrationParity();
        $this->checkEnumDrift();
        $this->checkRelationIntegrity();
        $this->checkOrphanTables();

        // --fix: run safe recovery actions
        if ($isFix) {
            $this->runSafeFix();
        }

        // Strict mode: promote warnings to failures (bypassed checks are NOT promoted)
        if ($isStrict && $this->warnings > 0) {
            $this->failures += $this->warnings;
            $this->warnings = 0;
            foreach ($this->checks as &$check) {
                if ($check['durum'] === 'warn') {
                    $check['durum'] = 'fail';
                    $check['mesaj'] = '[strict] ' . $check['mesaj'];
                }
            }
            unset($check);
        }

        // Log bypass usage if any checks were bypassed
        if ($this->activeBypass && $this->bypassed > 0) {
            $this->logBypassUsage();
            if (!$isJson) {
                $this->newLine();
                $this->warn("🎫 BYPASS: {$this->bypassed} check(s) bypassed via {$this->activeBypass['bypass_id']}");
                $this->warn("   Reason: {$this->activeBypass['reason']}");
            }
        }

        // Output
        if ($isJson) {
            $this->outputJson($isFix);
        } else {
            $this->newLine();
            $this->info('─────────────────────────────────────────');
            if ($this->failures > 0) {
                $this->error("❌ {$this->failures} failure(s), {$this->warnings} warning(s)");
            } elseif ($this->warnings > 0) {
                $this->warn("⚠️  0 failures, {$this->warnings} warning(s)");
            } else {
                $this->info('✅ All environment & schema checks passed');
            }
        }

        return $this->failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ── Error Helper ────────────────────────────────────────────

    private function rejectWithError(string $message, string $annotation, bool $isJson): int
    {
        if ($isJson) {
            $this->line(json_encode([
                'guard' => 'env-drift-guard',
                'contract_version' => $this->policy['contract_version'] ?? '3.2.0',
                'basarili' => false,
                'hata_mesaji' => $message,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fwrite(STDERR, "::error title=env-drift-guard::{$annotation}\n");
        } else {
            $this->error("❌ {$message}");
        }
        return self::FAILURE;
    }

    // ── Policy Validate ─────────────────────────────────────────

    /**
     * --policy-validate: Check policy integrity without running drift checks.
     * Validates: config exists, lock integrity, check completeness, bypass file format.
     */
    private function runPolicyValidate(bool $isJson): int
    {
        $issues = [];
        $passes = [];

        // 1. Config file exists
        $configPath = config_path('env-drift-guard.php');
        if (File::exists($configPath)) {
            $passes[] = 'Config file exists';
        } else {
            $issues[] = 'config/env-drift-guard.php not found';
        }

        // 2. Contract version defined
        $version = $this->policy['contract_version'] ?? null;
        if ($version) {
            $passes[] = "Contract version: {$version}";
        } else {
            $issues[] = 'contract_version missing from config';
        }

        // 3. Policy lock integrity
        $lockConfig = $this->policy['policy_lock'] ?? [];
        if (!empty($lockConfig['enabled'])) {
            $lockfile = base_path($lockConfig['lockfile'] ?? '.sab/policy-lock.sha256');
            if (File::exists($configPath)) {
                $currentHash = hash_file('sha256', $configPath);
                if (File::exists($lockfile)) {
                    $storedHash = trim(File::get($lockfile));
                    if ($currentHash === $storedHash) {
                        $passes[] = "Policy lock intact: {$this->shortHash($currentHash)}";
                    } else {
                        $issues[] = "Policy lock BROKEN: {$this->shortHash($storedHash)} → {$this->shortHash($currentHash)}";
                    }
                } else {
                    $issues[] = 'Policy lock file not found (run guard once to create baseline)';
                }
            }
        } else {
            $issues[] = 'Policy lock is disabled';
        }

        // 4. Check definitions completeness
        $checks = $this->policy['checks'] ?? [];
        $requiredFields = ['severity', 'fixable', 'recoverable', 'bypassable', 'description'];
        foreach ($checks as $id => $check) {
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $check)) {
                    $issues[] = "Check '{$id}' missing required field: {$field}";
                }
            }
        }
        if (empty($issues)) {
            $passes[] = count($checks) . ' checks fully defined';
        }

        // 5. Non-bypassable and bypassable lists consistency
        $nonBypassable = $this->policy['non_bypassable_checks'] ?? [];
        $bypassable = $this->policy['bypassable_checks'] ?? [];
        $overlap = array_intersect($nonBypassable, $bypassable);
        if (!empty($overlap)) {
            $issues[] = 'Checks in both non_bypassable and bypassable: ' . implode(', ', $overlap);
        } else {
            $passes[] = 'Bypass lists are consistent (no overlap)';
        }

        // 6. Bypass contract file format
        $bypassFile = base_path($this->policy['bypass']['contract_file'] ?? 'storage/governance/env-drift-bypass.json');
        if (File::exists($bypassFile)) {
            $content = File::get($bypassFile);
            $contracts = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $issues[] = 'Bypass contract file is not valid JSON';
            } else {
                $passes[] = count($contracts) . ' bypass contract(s) found';
            }
        } else {
            $passes[] = 'No bypass contract file (none active)';
        }

        // Output
        if ($isJson) {
            $this->line(json_encode([
                'guard' => 'env-drift-guard',
                'contract_version' => $version ?? 'unknown',
                'mode' => 'policy-validate',
                'role' => $this->resolvedRole,
                'basarili' => empty($issues),
                'passes' => $passes,
                'issues' => $issues,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info('');
            $this->info('🧿  Policy Validate — Governance Integrity Check');
            $this->info('─────────────────────────────────────────');
            foreach ($passes as $pass) {
                $this->info("  ✅ {$pass}");
            }
            foreach ($issues as $issue) {
                $this->error("  ❌ {$issue}");
            }
            $this->newLine();
            if (empty($issues)) {
                $this->info('✅ Policy integrity validated');
            } else {
                $this->error('❌ ' . count($issues) . ' policy issue(s) found');
            }
        }

        return empty($issues) ? self::SUCCESS : self::FAILURE;
    }

    // ── Role Resolution ─────────────────────────────────────────

    /**
     * Resolve execution role from --role option or auto-detect from environment.
     * Priority: explicit --role > env auto-detect > 'local' default
     */
    private function resolveRole(): string
    {
        $explicit = $this->option('role');
        if ($explicit !== null) {
            $validRoles = array_keys($this->policy['roles'] ?? []);
            // Remove non-role keys
            $validRoles = array_filter($validRoles, fn($k) => $k !== 'auto_detect');
            if (!in_array($explicit, $validRoles, true)) {
                $this->warn("⚠️  Unknown role '{$explicit}', falling back to 'local'");
                return 'local';
            }
            return $explicit;
        }

        // Auto-detect from CI environment variables
        $autoDetect = $this->policy['roles']['auto_detect'] ?? true;
        if ($autoDetect) {
            if (
                getenv('GITHUB_ACTIONS') === 'true'
                || getenv('CI') === 'true'
                || getenv('GITLAB_CI') !== false
                || getenv('CIRCLECI') !== false
                || getenv('JENKINS_URL') !== false
            ) {
                return 'ci';
            }
        }

        return 'local';
    }

    // ── Bypass Contract System ───────────────────────────────────

    /**
     * Load and validate a bypass contract by token ID.
     * Reads from storage/governance/env-drift-bypass.json.
     * Returns exit code on failure, null on success (sets $this->activeBypass).
     */
    private function loadAndValidateBypass(string $token, bool $isJson): ?int
    {
        $bypassFile = base_path($this->policy['bypass']['contract_file'] ?? 'storage/governance/env-drift-bypass.json');

        // Contract file must exist
        if (!File::exists($bypassFile)) {
            $contractPath = $this->policy['bypass']['contract_file'] ?? 'storage/governance/env-drift-bypass.json';
            return $this->rejectWithError(
                "No bypass contract file found. Create: {$contractPath}",
                'bypass contract file missing',
                $isJson
            );
        }

        $content = File::get($bypassFile);
        $contracts = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->rejectWithError(
                'Bypass contract file is not valid JSON.',
                'bypass contract file invalid JSON',
                $isJson
            );
        }

        // Find contract by bypass_id
        $contract = null;
        foreach ($contracts as $c) {
            if (($c['bypass_id'] ?? '') === $token) {
                $contract = $c;
                break;
            }
        }

        if ($contract === null) {
            $this->logAuditEvent('bypass_token_not_found', ['token' => $token]);
            return $this->rejectWithError(
                "Bypass token '{$token}' not found in contract file.",
                "bypass token not found: {$token}",
                $isJson
            );
        }

        // Validate required fields
        $requiredFields = ['bypass_id', 'approved_by', 'reason', 'checks', 'expires_at'];
        foreach ($requiredFields as $field) {
            if (empty($contract[$field])) {
                return $this->rejectWithError(
                    "Bypass contract '{$token}' missing required field: {$field}",
                    "bypass contract incomplete: {$token}",
                    $isJson
                );
            }
        }

        // Validate expiry
        $expiresAt = strtotime($contract['expires_at']);
        if ($expiresAt === false || $expiresAt < time()) {
            $this->logAuditEvent('bypass_expired', ['token' => $token, 'expires_at' => $contract['expires_at']]);
            return $this->rejectWithError(
                "Bypass token '{$token}' has expired ({$contract['expires_at']}).",
                "bypass expired: {$token}",
                $isJson
            );
        }

        // Validate max duration (7 days from now is checked at creation time, but verify)
        $maxDays = $this->policy['bypass']['max_duration_days'] ?? 7;
        $createdAt = strtotime($contract['created_at'] ?? $contract['expires_at']);
        if ($expiresAt - $createdAt > ($maxDays * 86400)) {
            return $this->rejectWithError(
                "Bypass contract '{$token}' exceeds max duration ({$maxDays} days).",
                "bypass duration exceeded: {$token}",
                $isJson
            );
        }

        // Validate: no non-bypassable checks in contract
        $nonBypassable = $this->policy['non_bypassable_checks'] ?? [];
        $bypassableChecks = $this->policy['bypassable_checks'] ?? [];
        $invalidChecks = [];
        foreach ($contract['checks'] as $checkId) {
            if (in_array($checkId, $nonBypassable)) {
                $invalidChecks[] = "{$checkId} (non-bypassable)";
            } elseif (!in_array($checkId, $bypassableChecks)) {
                $invalidChecks[] = "{$checkId} (not in bypassable list)";
            }
        }

        if (!empty($invalidChecks)) {
            $this->logAuditEvent('bypass_non_bypassable_attempt', [
                'token' => $token,
                'invalid_checks' => $invalidChecks,
            ]);
            return $this->rejectWithError(
                "Bypass contract '{$token}' includes non-bypassable checks: " . implode(', ', $invalidChecks),
                "bypass includes non-bypassable checks: {$token}",
                $isJson
            );
        }

        $this->activeBypass = $contract;
        return null; // Validation passed
    }

    /**
     * Check if a specific check ID is actively bypassed.
     */
    private function isCheckBypassed(string $checkId): bool
    {
        if ($this->activeBypass === null) {
            return false;
        }
        return in_array($checkId, $this->activeBypass['checks'] ?? []);
    }

    /**
     * Log bypass usage to audit file and security log.
     */
    private function logBypassUsage(): void
    {
        $entry = sprintf(
            "[%s] BYPASS_USED token=%s role=%s bypassed=%d checks=%s reason=\"%s\" user=%s",
            date('Y-m-d H:i:s'),
            $this->activeBypass['bypass_id'],
            $this->resolvedRole,
            $this->bypassed,
            implode(',', $this->activeBypass['checks']),
            $this->activeBypass['reason'],
            get_current_user()
        );

        $auditFile = base_path($this->policy['bypass']['audit_file'] ?? 'storage/governance/env-drift-audit.log');
        File::ensureDirectoryExists(dirname($auditFile));
        File::append($auditFile, $entry . "\n");

        $logChannel = $this->policy['bypass']['log_channel'] ?? 'security';
        try {
            Log::channel($logChannel)->warning('BYPASS_USED', [
                'bypass_id' => $this->activeBypass['bypass_id'],
                'role' => $this->resolvedRole,
                'bypassed_count' => $this->bypassed,
                'checks' => $this->activeBypass['checks'],
                'reason' => $this->activeBypass['reason'],
                'expires_at' => $this->activeBypass['expires_at'],
                'user' => get_current_user(),
            ]);
        } catch (\Exception $e) {
            // Primary record is the audit file
        }
    }

    /**
     * Log a governance audit event (rejections, attempts, etc.)
     */
    private function logAuditEvent(string $event, array $context = []): void
    {
        $entry = sprintf(
            "[%s] %s role=%s user=%s %s",
            date('Y-m-d H:i:s'),
            strtoupper($event),
            $this->resolvedRole,
            get_current_user(),
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        $auditFile = base_path($this->policy['bypass']['audit_file'] ?? 'storage/governance/env-drift-audit.log');
        File::ensureDirectoryExists(dirname($auditFile));
        File::append($auditFile, $entry . "\n");
    }

    // ── Policy Lock ─────────────────────────────────────────────

    /**
     * Verify config/env-drift-guard.php has not been tampered with.
     * Stores SHA-256 checksum in .sab/policy-lock.sha256.
     * Detects: unauthorized severity loosening, boundary changes.
     */
    private function checkPolicyLock(): void
    {
        $lockConfig = $this->policy['policy_lock'] ?? [];
        if (empty($lockConfig['enabled'])) {
            return;
        }

        $configPath = config_path('env-drift-guard.php');
        if (!File::exists($configPath)) {
            $this->record('policy_lock', 'issue', 'config/env-drift-guard.php not found');
            return;
        }

        $lockfile = base_path($lockConfig['lockfile'] ?? '.sab/policy-lock.sha256');
        $currentHash = hash_file('sha256', $configPath);

        if (!File::exists($lockfile)) {
            // First run — store baseline
            File::ensureDirectoryExists(dirname($lockfile));
            File::put($lockfile, $currentHash);
            $this->record('policy_lock', 'pass', "Policy lock baseline recorded: {$this->shortHash($currentHash)}");
            return;
        }

        $storedHash = trim(File::get($lockfile));
        if ($currentHash === $storedHash) {
            $this->record('policy_lock', 'pass', "Policy lock intact: {$this->shortHash($currentHash)}");
        } else {
            $this->record('policy_lock', 'issue',
                "Policy config CHANGED: {$this->shortHash($storedHash)} → {$this->shortHash($currentHash)}. " .
                "If intentional, update {$lockConfig['lockfile']}"
            );
        }
    }

    // ── Recording ─────────────────────────────────────────────

    /**
     * Record a check result. Severity for issues driven by config policy.
     * If bypass is active for this check, durum becomes 'bypass' instead of fail/warn.
     */
    private function record(string $checkId, string $result, string $message): void
    {
        $checkConfig = $this->policy['checks'][$checkId] ?? [];
        $configSeverity = $checkConfig['severity'] ?? 'fail';
        $fixable = $checkConfig['fixable'] ?? false;
        $bypassable = $checkConfig['bypassable'] ?? false;

        if ($result === 'pass') {
            $durum = 'pass';
        } elseif ($this->isCheckBypassed($checkId)) {
            // Check is bypassed via token — record as bypass, don't count as failure
            $durum = 'bypass';
            $this->bypassed++;
        } else {
            $durum = $configSeverity;
        }

        $this->checks[] = [
            'check' => $checkId,
            'durum' => $durum,
            'severity' => $configSeverity,
            'mesaj' => $message,
            'fixable' => $fixable,
            'bypassable' => $bypassable,
        ];

        if ($durum === 'fail') {
            $this->failures++;
            if (!$this->option('json')) {
                $this->error("  ❌ {$message}");
            }
        } elseif ($durum === 'warn') {
            $this->warnings++;
            if (!$this->option('json')) {
                $this->warn("  ⚠️  {$message}");
            }
        } elseif ($durum === 'bypass') {
            if (!$this->option('json')) {
                $this->warn("  🎫 [bypass] {$message}");
            }
        } else {
            if (!$this->option('json')) {
                $this->info("  ✅ {$message}");
            }
        }
    }

    // ── Output ─────────────────────────────────────────────────

    private function outputJson(bool $fixMode): void
    {
        $payload = [
            'guard' => 'env-drift-guard',
            'contract_version' => $this->policy['contract_version'] ?? '3.2.0',
            'status' => $this->failures > 0 ? 'fail' : ($this->warnings > 0 ? 'warn' : ($this->bypassed > 0 ? 'bypass' : 'pass')),
            'role' => $this->resolvedRole,
            'mode' => $fixMode ? 'fix' : 'read-only',
            'policy' => [
                'locked' => !empty($this->policy['policy_lock']['enabled']),
                'strict' => (bool) $this->option('strict') || !empty(($this->policy['roles'][$this->resolvedRole] ?? [])['implicit_strict']),
                'override_used' => false,
                'bypass_used' => $this->activeBypass !== null && $this->bypassed > 0,
            ],
            'ozet' => [
                'toplam' => count($this->checks),
                'basarili' => count(array_filter($this->checks, fn($c) => $c['durum'] === 'pass')),
                'uyari' => $this->warnings,
                'hata' => $this->failures,
                'bypass' => $this->bypassed,
            ],
            'checks' => $this->checks,
            'basarili' => $this->failures === 0,
        ];

        if ($this->activeBypass !== null && $this->bypassed > 0) {
            $payload['bypass'] = [
                'id' => $this->activeBypass['bypass_id'],
                'checks' => $this->activeBypass['checks'],
                'approved_by' => $this->activeBypass['approved_by'],
                'reason' => $this->activeBypass['reason'],
                'expires_at' => $this->activeBypass['expires_at'],
                'ticket' => $this->activeBypass['ticket'] ?? null,
            ];
        }

        if ($fixMode && !empty($this->fixActions)) {
            $payload['fix_actions'] = $this->fixActions;
        }

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // GitHub Actions annotation format to stderr
        foreach ($this->checks as $check) {
            if ($check['durum'] === 'fail') {
                fwrite(STDERR, "::error title=env-drift-guard::{$check['check']}: {$check['mesaj']}\n");
            } elseif ($check['durum'] === 'warn') {
                fwrite(STDERR, "::warning title=env-drift-guard::{$check['check']}: {$check['mesaj']}\n");
            } elseif ($check['durum'] === 'bypass') {
                fwrite(STDERR, "::warning title=env-drift-guard::[bypass] {$check['check']}: {$check['mesaj']}\n");
            }
        }
    }

    // ── Checks ─────────────────────────────────────────────────

    private function checkEnvTesting(): void
    {
        $envPath = base_path('.env.testing');
        if (!File::exists($envPath)) {
            $this->record('env_testing', 'issue', '.env.testing file missing');
            return;
        }

        $content = File::get($envPath);
        $requiredKeys = $this->policy['required_env_keys']
            ?? ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'];

        $missingKeys = [];
        foreach ($requiredKeys as $key) {
            if (!preg_match("/^{$key}=/m", $content)) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            $this->record('env_testing', 'issue', 'Missing required keys in .env.testing: ' . implode(', ', $missingKeys));
        } else {
            $this->record('env_testing', 'pass', '.env.testing exists with all required DB keys');
        }
    }

    private function checkDbConnectivity(): void
    {
        $envPath = base_path('.env.testing');
        if (!File::exists($envPath)) {
            $this->record('db_connectivity', 'issue', '.env.testing missing — cannot verify DB connection');
            return;
        }

        $envVars = $this->parseEnvFile($envPath);
        $host = $envVars['DB_HOST'] ?? '127.0.0.1';
        $port = $envVars['DB_PORT'] ?? '3306';
        $database = $envVars['DB_DATABASE'] ?? '';
        $username = $envVars['DB_USERNAME'] ?? '';
        $password = $envVars['DB_PASSWORD'] ?? '';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            $this->record('db_connectivity', 'pass', "Connected to test database: {$database} ({$host}:{$port})");
        } catch (\Exception $e) {
            $this->record('db_connectivity', 'issue', "Cannot connect to test DB ({$host}:{$port}/{$database}): " . $e->getMessage());
        }
    }

    private function parseEnvFile(string $path): array
    {
        $vars = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (preg_match('/^([A-Z_]+)=(.*)$/', $line, $m)) {
                $vars[$m[1]] = trim($m[2], '"\'');
            }
        }
        return $vars;
    }

    private function checkSchemaFiles(): void
    {
        $mysqlSchema = database_path('schema/mysql-schema.sql');
        $testingSchema = database_path('schema/testing-schema.sql');

        if (!File::exists($mysqlSchema)) {
            $this->record('schema_mysql', 'issue', 'database/schema/mysql-schema.sql missing');
        } else {
            $this->record('schema_mysql', 'pass', 'mysql-schema.sql exists');
        }

        if (!File::exists($testingSchema)) {
            $this->record('schema_testing', 'issue', 'database/schema/testing-schema.sql missing (optional)');
        } else {
            $this->record('schema_testing', 'pass', 'testing-schema.sql exists');
        }
    }

    private function checkSchemaDiff(): void
    {
        $mysqlSchema = database_path('schema/mysql-schema.sql');
        $testingSchema = database_path('schema/testing-schema.sql');

        if (!File::exists($mysqlSchema) || !File::exists($testingSchema)) {
            return; // Already reported above
        }

        $criticalTables = $this->policy['critical_tables'] ?? ['ilanlar', 'kisiler', 'roles', 'users'];
        $drifts = [];

        foreach ($criticalTables as $table) {
            $mysqlCols = $this->extractColumnsFromSql(File::get($mysqlSchema), $table);
            $testingCols = $this->extractColumnsFromSql(File::get($testingSchema), $table);

            if (empty($mysqlCols) && empty($testingCols)) {
                continue;
            }

            $onlyInMysql = array_diff(array_keys($mysqlCols), array_keys($testingCols));
            $onlyInTesting = array_diff(array_keys($testingCols), array_keys($mysqlCols));

            if (!empty($onlyInMysql)) {
                $drifts[] = "{$table}: columns only in mysql-schema.sql: " . implode(', ', $onlyInMysql);
            }
            if (!empty($onlyInTesting)) {
                $drifts[] = "{$table}: columns only in testing-schema.sql: " . implode(', ', $onlyInTesting);
            }
        }

        if (!empty($drifts)) {
            $this->record('schema_diff', 'issue', "Schema drift between mysql-schema.sql and testing-schema.sql:\n  - " . implode("\n  - ", $drifts));
        } else {
            $this->record('schema_diff', 'pass', 'Critical tables in sync between schema files');
        }
    }

    private function checkFillableAlignment(): void
    {
        $criticalModels = $this->policy['critical_models'] ?? [\App\Models\Ilan::class => 'ilanlar'];

        $mysqlSchemaPath = database_path('schema/mysql-schema.sql');
        if (!File::exists($mysqlSchemaPath)) {
            return;
        }

        $schemaSql = File::get($mysqlSchemaPath);
        $drifts = [];

        foreach ($criticalModels as $modelClass => $tableName) {
            if (!class_exists($modelClass)) {
                continue;
            }

            $model = new $modelClass();
            $fillable = $model->getFillable();
            $dbCols = array_keys($this->extractColumnsFromSql($schemaSql, $tableName));

            if (empty($dbCols)) {
                continue;
            }

            // Auto-managed columns that should NOT be in $fillable
            $autoManaged = ['id', 'created_at', 'updated_at', 'deleted_at'];
            $dbColsForComparison = array_diff($dbCols, $autoManaged);

            // Ghost fields: in $fillable but NOT in DB
            $ghosts = array_diff($fillable, $dbCols);
            foreach ($ghosts as $ghost) {
                $drifts[] = "{$tableName}.{$ghost}: in \$fillable but missing from DB schema (ghost field)";
            }

            // Missing from $fillable: in DB but not in $fillable (warn for non-auto-managed)
            // This is informational only — not all DB columns need to be in $fillable
        }

        if (!empty($drifts)) {
            $this->record('fillable_alignment', 'issue', "Model ↔ DB drift:\n  - " . implode("\n  - ", $drifts));
        } else {
            $this->record('fillable_alignment', 'pass', 'Critical model $fillable aligned with DB schema');
        }
    }

    /**
     * Step 6: Verify that all migrations produce columns present in mysql-schema.sql.
     * Catches: future dev adding migration that diverges from SSOT.
     */
    private function checkMigrationParity(): void
    {
        $mysqlSchemaPath = database_path('schema/mysql-schema.sql');
        $migrationsPath = database_path('migrations');

        if (!File::exists($mysqlSchemaPath) || !File::isDirectory($migrationsPath)) {
            return;
        }

        $schemaSql = File::get($mysqlSchemaPath);
        $schemaTables = $this->extractAllTableNames($schemaSql);

        $drifts = [];
        $migrationFiles = File::glob($migrationsPath . '/*.php');

        foreach ($migrationFiles as $file) {
            $content = File::get($file);
            $basename = basename($file);

            // Detect Schema::create('table_name', ...) — new table creation
            if (preg_match_all("/Schema::create\(\s*['\"](\w+)['\"]/", $content, $matches)) {
                foreach ($matches[1] as $table) {
                    if (!in_array($table, $schemaTables) && !$this->isDropMigration($content, $table)) {
                        $drifts[] = "{$basename}: creates table '{$table}' not in mysql-schema.sql";
                    }
                }
            }

            // Detect Schema::table('xxx', fn => $table->addColumn) — column additions
            if (preg_match_all("/Schema::table\(\s*['\"](\w+)['\"]/", $content, $tableMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($tableMatches[1] as [$table, $offset]) {
                    if (in_array($table, $schemaTables)) {
                        // Table exists in schema — columns should be there too
                        $schemaCols = $this->extractColumnsFromSql($schemaSql, $table);
                        $migrationCols = $this->extractColumnsFromMigrationScoped($content, $table, $offset);

                        foreach ($migrationCols as $col) {
                            if (!isset($schemaCols[$col]) && !$this->isDropColumn($content, $col)) {
                                $drifts[] = "{$basename}: adds column '{$table}.{$col}' not in mysql-schema.sql";
                            }
                        }
                    }
                }
            }
        }

        if (!empty($drifts)) {
            $this->record('migration_parity', 'issue', "Migration ↔ SSOT drift:\n  - " . implode("\n  - ", $drifts));
        } else {
            $this->record('migration_parity', 'pass', 'All migrations aligned with mysql-schema.sql (SSOT)');
        }
    }

    /**
     * Step 7: Verify canonical enum values match what code actually uses.
     * Catches: code writing IlanDurumu::YAYINDA->value when enum defines 'yayinda'.
     */
    private function checkEnumDrift(): void
    {
        $enumChecks = $this->policy['enum_checks'] ?? [
            [
                'enum' => \App\Enums\IlanDurumu::class,
                'field' => 'yayin_durumu',
                'scan_dirs' => ['app/Http/Controllers', 'app/Services', 'app/Modules'],
                'forbidden_values' => [IlanDurumu::YAYINDA->value, 'Taslak', 'Pasif', 'Beklemede', 'Active', 'Draft', 'Inactive'],
            ],
            [
                'enum' => \App\Enums\AktiflikDurumu::class,
                'field' => 'aktiflik_durumu',
                'scan_dirs' => ['app/Http/Controllers', 'app/Services', 'app/Modules'],
                'forbidden_values' => ['active', 'inactive', 'enabled', 'disabled'],
            ],
        ];

        $drifts = [];

        foreach ($enumChecks as $check) {
            if (!class_exists($check['enum'])) {
                continue;
            }

            // Get canonical values from enum
            $canonicalValues = array_map(
                fn($case) => $case->value,
                $check['enum']::cases()
            );

            // Scan for forbidden (legacy) value usage
            foreach ($check['scan_dirs'] as $dir) {
                $dirPath = base_path($dir);
                if (!File::isDirectory($dirPath)) {
                    continue;
                }

                $files = File::allFiles($dirPath);
                foreach ($files as $file) {
                    if ($file->getExtension() !== 'php') {
                        continue;
                    }

                    $content = File::get($file->getPathname());
                    $relativePath = str_replace(base_path() . '/', '', $file->getPathname());

                    foreach ($check['forbidden_values'] as $forbidden) {
                        // Match field => value or ->where(field, value) patterns in source
                        $pattern = "/['\"]" . preg_quote($check['field'], '/') . "['\"]\s*[=>,]+\s*['\"]" . preg_quote($forbidden, '/') . "['\"]/i";
                        if (preg_match($pattern, $content)) {
                            $drifts[] = "{$relativePath}: uses legacy value '{$forbidden}' for {$check['field']} (canonical: " . implode('|', $canonicalValues) . ")";
                        }
                    }
                }
            }
        }

        if (!empty($drifts)) {
            $this->record('enum_drift', 'issue', "Enum value drift detected:\n  - " . implode("\n  - ", $drifts));
        } else {
            $this->record('enum_drift', 'pass', 'Canonical enum values consistent with codebase usage');
        }
    }

    /**
     * Step 8: SSOT checksum — detect unauthorized changes to mysql-schema.sql.
     * Stores checksum in .sab/schema-checksum.sha256, compares on each run.
     */
    private function checkSchemaChecksum(): void
    {
        $schemaPath = database_path('schema/mysql-schema.sql');
        if (!File::exists($schemaPath)) {
            return; // Already caught by checkSchemaFiles
        }

        $checksumFile = base_path('.sab/schema-checksum.sha256');
        $currentHash = hash_file('sha256', $schemaPath);

        if (!File::exists($checksumFile)) {
            // First run — store baseline (this is the only write: a checksum file)
            File::ensureDirectoryExists(dirname($checksumFile));
            File::put($checksumFile, $currentHash);
            $this->record('schema_checksum', 'pass', "SSOT checksum baseline recorded: {$this->shortHash($currentHash)}");
            return;
        }

        $storedHash = trim(File::get($checksumFile));
        if ($currentHash === $storedHash) {
            $this->record('schema_checksum', 'pass', "SSOT checksum intact: {$this->shortHash($currentHash)}");
        } else {
            $this->record('schema_checksum', 'issue',
                "SSOT checksum CHANGED: {$this->shortHash($storedHash)} → {$this->shortHash($currentHash)}. " .
                "If intentional, update .sab/schema-checksum.sha256"
            );
        }
    }

    /**
     * Step 9: Relation integrity — verify FK references point to existing tables/columns.
     * Catches: FK referencing a dropped table, or column renamed without FK update.
     */
    private function checkRelationIntegrity(): void
    {
        $schemaPath = database_path('schema/mysql-schema.sql');
        if (!File::exists($schemaPath)) {
            return;
        }

        $schemaSql = File::get($schemaPath);
        $allTables = $this->extractAllTableNames($schemaSql);
        $drifts = [];

        // Extract all FK constraints: FOREIGN KEY (`col`) REFERENCES `table` (`col`)
        if (preg_match_all(
            '/CONSTRAINT\s+`(\w+)`\s+FOREIGN KEY\s+\(`(\w+)`\)\s+REFERENCES\s+`(\w+)`\s+\(`(\w+)`\)/',
            $schemaSql,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $fk) {
                $constraintName = $fk[1];
                $referencedTable = $fk[3];
                $referencedCol = $fk[4];

                // Check: does the referenced table exist in schema?
                if (!in_array($referencedTable, $allTables)) {
                    $drifts[] = "FK {$constraintName}: references non-existent table '{$referencedTable}'";
                    continue;
                }

                // Check: does the referenced column exist in that table?
                $tableCols = $this->extractColumnsFromSql($schemaSql, $referencedTable);
                if (!isset($tableCols[$referencedCol])) {
                    $drifts[] = "FK {$constraintName}: references non-existent column '{$referencedTable}.{$referencedCol}'";
                }
            }
        }

        if (!empty($drifts)) {
            $this->record('relation_integrity', 'issue', "FK relation drift:\n  - " . implode("\n  - ", $drifts));
        } else {
            $this->record('relation_integrity', 'pass', 'All FK constraints reference valid tables and columns');
        }
    }

    /**
     * Step 10: Orphan table detection — tables in DB but NOT in mysql-schema.sql SSOT.
     * Catches: manually created tables, test remnants, forgotten experiments.
     */
    private function checkOrphanTables(): void
    {
        $schemaPath = database_path('schema/mysql-schema.sql');
        if (!File::exists($schemaPath)) {
            return;
        }

        // We need a live DB connection for this check
        $envPath = base_path('.env.testing');
        if (!File::exists($envPath)) {
            return;
        }

        $envVars = $this->parseEnvFile($envPath);
        $host = $envVars['DB_HOST'] ?? '127.0.0.1';
        $port = $envVars['DB_PORT'] ?? '3306';
        $database = $envVars['DB_DATABASE'] ?? '';
        $username = $envVars['DB_USERNAME'] ?? '';
        $password = $envVars['DB_PASSWORD'] ?? '';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new \PDO($dsn, $username, $password, [\PDO::ATTR_TIMEOUT => 5]);
        } catch (\Exception $e) {
            return; // DB unreachable already caught by checkDbConnectivity
        }

        $schemaSql = File::get($schemaPath);
        $ssotTables = $this->extractAllTableNames($schemaSql);

        // Get live DB tables
        $stmt = $pdo->query('SHOW TABLES');
        $dbTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // System/framework tables to ignore
        $defaultIgnore = [
            'migrations', 'failed_jobs', 'personal_access_tokens',
            'telescope_entries', 'telescope_entries_tags', 'telescope_monitoring',
        ];
        $ignoreTables = $this->policy['orphan_ignore_tables'] ?? $defaultIgnore;

        $orphans = [];
        foreach ($dbTables as $dbTable) {
            if (!in_array($dbTable, $ssotTables) && !in_array($dbTable, $ignoreTables)) {
                $orphans[] = $dbTable;
            }
        }

        if (!empty($orphans)) {
            $this->record('orphan_tables', 'issue', "Tables in DB but missing from SSOT:\n  - " . implode("\n  - ", $orphans));
        } else {
            $this->record('orphan_tables', 'pass', 'All DB tables accounted for in mysql-schema.sql');
        }
    }

    // ── Helpers ────────────────────────────────────────────────

    private function shortHash(string $hash): string
    {
        return substr($hash, 0, 12);
    }

    private function extractAllTableNames(string $sql): array
    {
        preg_match_all('/CREATE TABLE\s+`(\w+)`/', $sql, $matches);
        return $matches[1] ?? [];
    }

    private function extractColumnsFromMigrationScoped(string $content, string $table, int $offset): array
    {
        // Extract the closure block following Schema::table('table', function (...) { ... });
        $rest = substr($content, $offset);

        // Find the function/closure body by matching balanced braces
        $braceCount = 0;
        $inBody = false;
        $body = '';
        for ($i = 0, $len = strlen($rest); $i < $len; $i++) {
            if ($rest[$i] === '{') {
                $braceCount++;
                $inBody = true;
            }
            if ($inBody) {
                $body .= $rest[$i];
            }
            if ($rest[$i] === '}') {
                $braceCount--;
                if ($inBody && $braceCount === 0) {
                    break;
                }
            }
        }

        $columns = [];
        if (preg_match_all('/\$table->\w+\(\s*[\'"](\w+)[\'"]/', $body, $matches)) {
            $columns = array_unique($matches[1]);
        }
        return $columns;
    }

    private function isDropMigration(string $content, string $table): bool
    {
        return (bool) preg_match("/Schema::drop(IfExists)?\(\s*['\"]" . preg_quote($table) . "['\"]/", $content);
    }

    private function isDropColumn(string $content, string $column): bool
    {
        return (bool) preg_match("/dropColumn\(\s*['\"]" . preg_quote($column) . "['\"]/", $content);
    }

    /**
     * Extract column names from a CREATE TABLE statement in a SQL dump.
     */
    private function extractColumnsFromSql(string $sql, string $table): array
    {
        // Match CREATE TABLE `table_name` ( ... ) block
        $pattern = '/CREATE TABLE\s+`' . preg_quote($table, '/') . '`\s*\((.*?)\)\s*(ENGINE|;)/s';
        if (!preg_match($pattern, $sql, $match)) {
            return [];
        }

        $body = $match[1];
        $columns = [];

        // Extract column definitions (lines starting with backtick-quoted name)
        foreach (explode("\n", $body) as $line) {
            $line = trim($line);
            if (preg_match('/^`(\w+)`\s+(.+?)(?:,\s*)?$/', $line, $colMatch)) {
                $columns[$colMatch[1]] = $colMatch[2];
            }
        }

        return $columns;
    }

    // ── Fix ─────────────────────────────────────────────────────

    /**
     * Run safe recovery actions. ONLY operations listed in config fix_allowed.
     * NEVER mutates DB, env files, model code, or schema files.
     */
    private function runSafeFix(): void
    {
        $allowed = $this->policy['fix_allowed'] ?? [];

        if (!$this->option('json')) {
            $this->newLine();
            $this->info('🔧 Running safe fix operations...');
        }

        $fixMap = [
            'config_cache_clear' => ['config:clear', 'Config cache cleared'],
            'route_cache_clear'  => ['route:clear', 'Route cache cleared'],
            'view_cache_clear'   => ['view:clear', 'View cache cleared'],
        ];

        foreach ($fixMap as $key => [$command, $label]) {
            if (in_array($key, $allowed)) {
                try {
                    Artisan::call($command);
                    $this->fixActions[] = ['action' => $key, 'basarili' => true, 'mesaj' => $label];
                    if (!$this->option('json')) {
                        $this->info("  🔧 {$label}");
                    }
                } catch (\Exception $e) {
                    $this->fixActions[] = ['action' => $key, 'basarili' => false, 'mesaj' => $e->getMessage()];
                    if (!$this->option('json')) {
                        $this->warn("  ⚠️  Fix failed: {$key} — {$e->getMessage()}");
                    }
                }
            }
        }
    }
}
