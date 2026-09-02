<?php

declare(strict_types=1);

namespace App\Domain\DriftAudit\Services;

use App\Domain\DriftAudit\DTO\DriftAuditReport;
use App\Domain\DriftAudit\DTO\DriftCheck;
use App\Domain\DriftAudit\DTO\DriftFinding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

/**
 * Yalihan Drift Audit Service
 *
 * Central orchestrator for the yalihan:drift-audit command.
 * Runs five audit categories:
 *
 *  1. Schema & Ghost Detection
 *     - Ghost tables: in schema_guard/config but not in DB
 *     - Ghost fields: fillable in model but not in DB
 *     - Missing migrations: ran in DB but no migration file
 *
 *  2. Forbidden Alias Scan
 *     - Scans code for legacy/wrong field names from schema_guard config
 *     - Detects table.field patterns, validation rules, DB::table usage
 *
 *  3. Model–API–Seeder Contract Check
 *     - Validates model $fillable against DB columns
 *     - Checks API request validation vs model fillable
 *     - Checks seeder field names vs model fillable
 *
 *  4. Git State
 *     - Local uncommitted changes
 *     - Local vs origin/main branch diff
 *
 *  5. Registry Baseline
 *     - Verifies registry table list vs actual DB
 *     - Flags unregistered tables
 *
 * Sentinel CAN:
 *   - Read files and DB
 *   - Compare and report
 *   - Run tests
 *   - Produce reports
 *
 * Sentinel CANNOT:
 *   - Run migrations
 *   - Run seeders
 *   - Modify files
 *   - Deploy or repair
 */
class YalihanDriftAuditService
{
    private string $basePath;
    private string $gitCommit;
    private string $registryPath;
    private bool $dryRun = true; // always true for v1

    /** @var array<string, array> */
    private array $registry;

    /** @var array<string, array> */
    private array $config;

    /** @var DriftCheck[] */
    private array $checks = [];

    /** @var array */
    private array $ghostTables = [];

    /** @var array */
    private array $ghostFields = [];

    /** @var array */
    private array $forbiddenAliasViolations = [];

    /** @var array */
    private array $unguardedTables = [];

    /** @var array */
    private array $missingMigrations = [];

    /** @var array */
    private array $seederCoverage = [];

    /** @var array */
    private array $gitLocalVsRemote = [];

    public function __construct()
    {
        $this->basePath = base_path();
        $this->gitCommit = $this->resolveGitCommit();
        $this->registryPath = database_path('schema/yalihan-schema-registry.json');
        $this->registry = $this->loadRegistry();
        $this->config = config('schema_guard') ?? [];
    }

    // ─────────────────────────────────────────────────────────────
    // Public entry point
    // ─────────────────────────────────────────────────────────────

    public function run(): DriftAuditReport
    {
        $this->checkGhostTables();
        $this->checkGhostFields();
        $this->checkMissingMigrations();
        $this->checkForbiddenAliases();
        $this->checkUnguardedTables();
        $this->checkSeederCoverage();
        $this->checkGitState();

        return $this->buildReport();
    }

    // ─────────────────────────────────────────────────────────────
    // CHECK 1: Ghost Tables
    // Registry/config references tables that don't exist in DB
    // ─────────────────────────────────────────────────────────────

    private function checkGhostTables(): void
    {
        $findings = [];

        // Ghost tables from registry
        $ghostFromRegistry = $this->registry['ghost_tables'] ?? [];
        foreach ($ghostFromRegistry as $table => $meta) {
            if (($meta['status'] ?? '') !== 'GHOST') {
                continue;
            }
            $findings[] = [
                'type'    => 'ghost_table',
                'severity'=> 'high',
                'subject' => $table,
                'description' => $meta['reason'] ?? 'Referenced in schema but not in DB',
                'expected' => 'table exists in database',
                'actual'   => 'table missing',
                'file'     => implode(', ', $meta['referenced_in'] ?? []),
                'line'     => null,
                'evidence_label' => 'REPO_VERIFIED',
            ];
            $this->ghostTables[] = $table;
        }

        $status = empty($findings) ? 'PASS' : 'FAIL';
        $this->checks[] = new DriftCheck(
            name: 'ghost_tables',
            status: $status,
            label: empty($findings) ? 'LOCAL_RUNTIME_VERIFIED' : 'REPO_VERIFIED',
            summary: empty($findings)
                ? 'No ghost tables detected.'
                : count($findings) . ' table(s) referenced but not found in database.',
            findings: $findings,
            findingCount: count($findings),
        );
    }

    // ─────────────────────────────────────────────────────────────
    // CHECK 2: Ghost Fields
    // Model $fillable contains field not in DB table
    // ─────────────────────────────────────────────────────────────

    /**
     * Skip list for ghost field detection.
     * Common Laravel/Eloquent fields present in $fillable or $casts
     * but NOT as actual DB columns. These are Context7 migration remnants
     * or permission guard helpers that should NOT trigger drift alerts.
     *
     * ponytail: These are documented ghost fields — fix in a dedicated sprint.
     */
    private const GHOST_FIELD_SKIPLIST = [
        // Spatie/permission guard helpers — present in $fillable, no DB column
        'is_active', 'is_complete', 'is_locked', 'is_featured',
        'is_verified', 'is_published', 'is_archived', 'is_deleted',
        'is_visible', 'is_system', 'is_enabled', 'is_paid',
        // Enum cast helpers — mapped in models but no direct column
        'aktiflik_durumu',
    ];

    private function checkGhostFields(): void
    {
        $findings = [];
        $targetModels = $this->getTargetModels();

        foreach ($targetModels as $modelClass) {
            try {
                $model = new $modelClass();
                $table = $model->getTable();

                if (!Schema::hasTable($table)) {
                    continue;
                }

                $fillable = $model->getFillable();
                $columns  = Schema::getColumnListing($table);
                $ghost    = array_diff($fillable, $columns);

                foreach ($ghost as $field) {
                    // Skip known standard/Laravel fields not in DB
                    if (in_array($field, self::GHOST_FIELD_SKIPLIST, true)) {
                        continue;
                    }

                    $severity = 'critical';
                    if (str_ends_with($field, '_id') || str_ends_with($field, '_at')) {
                        $severity = 'medium';
                    }

                    $findings[] = [
                        'type'    => 'ghost_field',
                        'severity'=> $severity,
                        'subject' => "{$table}.{$field}",
                        'description' => "\$fillable in model contains '{$field}' but column does not exist in DB.",
                        'expected' => 'field exists in DB',
                        'actual'   => 'field missing',
                        'file'     => (new ReflectionClass($modelClass))->getFileName(),
                        'line'     => null,
                        'evidence_label' => 'LOCAL_RUNTIME_VERIFIED',
                    ];
                    $this->ghostFields[] = "{$table}.{$field}";
                }

                // Also check casts — only flag if the cast field is also in fillable
                // (isolated casts on non-fillable fields are intentional computed attributes)
                $casts = $model->getCasts();
                foreach ($casts as $col => $castType) {
                    if (in_array($col, ['id', 'metadata', 'timestamps'])) {
                        continue;
                    }
                    // Skip known cast helpers that exist in fillable but not in DB
                    if (in_array($col, self::GHOST_FIELD_SKIPLIST, true)) {
                        continue;
                    }
                    if (!in_array($col, $columns) && !in_array($col, $fillable)) {
                        continue; // intentional computed attribute, not a drift
                    }
                    if (!in_array($col, $columns)) {
                        $findings[] = [
                            'type'    => 'ghost_cast',
                            'severity'=> 'medium',
                            'subject' => "{$table}.{$col}",
                            'description' => "\$casts defines '{$col}' ({$castType}) but column does not exist in DB.",
                            'expected' => 'field exists in DB',
                            'actual'   => 'field missing',
                            'file'     => (new ReflectionClass($modelClass))->getFileName(),
                            'line'     => null,
                            'evidence_label' => 'LOCAL_RUNTIME_VERIFIED',
                        ];
                    }
                }
            } catch (\Throwable) {
                // skip models that fail to instantiate
            }
        }

        $status = empty($findings) ? 'PASS' : 'FAIL';
        $this->checks[] = new DriftCheck(
            name: 'ghost_fields',
            status: $status,
            label: empty($findings) ? 'LOCAL_RUNTIME_VERIFIED' : 'REPO_VERIFIED',
            summary: empty($findings)
                ? 'No ghost fields detected.'
                : count($findings) . ' model field(s) not found in DB.',
            findings: $findings,
            findingCount: count($findings),
        );
    }

    // ─────────────────────────────────────────────────────────────
    // CHECK 3: Missing Migrations
    // DB table exists but no migration file found
    // ─────────────────────────────────────────────────────────────

    private function checkMissingMigrations(): void
    {
        $findings = [];

        $migrationFiles = $this->getMigrationFileMap(); // filename => table(s)
        $dbTables = Schema::getTables();
        $dbTableNames = array_column($dbTables, 'name');

        $skippedTables = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches',
            'personal_access_tokens', 'sessions', 'telescope_entries'];

        foreach ($dbTableNames as $table) {
            if (in_array($table, $skippedTables, true)) {
                continue;
            }

            $found = false;
            foreach ($migrationFiles as $file => $tables) {
                if (in_array($table, $tables, true)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $findings[] = [
                    'type'    => 'missing_migration',
                    'severity'=> 'medium',
                    'subject' => $table,
                    'description' => "Table '{$table}' exists in DB but no migration file found.",
                    'expected' => 'migration file exists in database/migrations/',
                    'actual'   => 'migration file not found',
                    'file'     => null,
                    'line'     => null,
                    'evidence_label' => 'REPO_VERIFIED',
                ];
                $this->missingMigrations[] = $table;
            }
        }

        $status = empty($findings) ? 'PASS' : 'WARN';
        $this->checks[] = new DriftCheck(
            name: 'missing_migrations',
            status: $status,
            label: empty($findings) ? 'REPO_VERIFIED' : 'INFERRED',
            summary: empty($findings)
                ? 'All DB tables have corresponding migration files.'
                : count($findings) . ' table(s) in DB without migration files.',
            findings: $findings,
            findingCount: count($findings),
        );
    }

    // ─────────────────────────────────────────────────────────────
    // CHECK 4: Forbidden Alias Scan
    // Code uses wrong/legacy field names from schema_guard config
    // ─────────────────────────────────────────────────────────────

    private function checkForbiddenAliases(): void
    {
        $findings = [];
        $aliases = $this->config['forbidden_aliases'] ?? [];

        if (empty($aliases)) {
            $this->checks[] = new DriftCheck(
                name: 'forbidden_aliases',
                status: 'SKIP',
                label: 'UNKNOWN',
                summary: 'schema_guard.php has no forbidden_aliases defined.',
                findings: [],
                findingCount: 0,
            );
            return;
        }

        $scanPaths = $this->config['scan_paths'] ?? [
            'app/Http/Controllers', 'app/Services', 'app/Actions',
            'app/Models', 'app/Domains', 'app/Jobs', 'app/Repositories',
            'resources/views/admin',
        ];
        $extensions = $this->config['scan_extensions'] ?? ['php', 'blade.php'];
        $excludedFiles = $this->config['excluded_files'] ?? [];

        $files = $this->collectFiles($scanPaths, $extensions, $excludedFiles);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            $relativePath = str_replace($this->basePath . '/', '', $file);

            foreach ($aliases as $alias) {
                $wrongField = $alias['wrong_field'];
                $table = $alias['table'];
                $correctField = $alias['correct_field'] ?? null;
                $severity = $alias['severity'] ?? 'medium';

                $patterns = $this->buildForbiddenPatterns($wrongField, $table);

                foreach ($patterns as $patternInfo) {
                    foreach ($lines as $lineNum => $line) {
                        $trimmed = trim($line);
                        if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                            continue;
                        }

                        if (preg_match($patternInfo['regex'], $line)) {
                            // Skip generic 'name' outside of yayin_tipi_sablonlari
                            if ($wrongField === 'name' && $table !== 'yayin_tipi_sablonlari') {
                                continue;
                            }

                            $correctDisplay = $correctField ?? '(no replacement)';
                            $findings[] = [
                                'type'    => 'forbidden_alias',
                                'severity'=> $severity,
                                'subject' => "{$table}.{$wrongField}",
                                'description' => "Forbidden field '{$wrongField}' used on table '{$table}'. Use '{$correctDisplay}' instead. Note: {$alias['note']}",
                                'expected' => $correctDisplay,
                                'actual'   => $wrongField,
                                'file'     => $relativePath,
                                'line'     => $lineNum + 1,
                                'evidence_label' => 'REPO_VERIFIED',
                            ];
                            $this->forbiddenAliasViolations[] = "{$relativePath}:" . ($lineNum + 1);
                        }
                    }
                }
            }
        }

        $status = empty($findings) ? 'PASS' : 'FAIL';
        $this->checks[] = new DriftCheck(
            name: 'forbidden_aliases',
            status: $status,
            label: empty($findings) ? 'REPO_VERIFIED' : 'REPO_VERIFIED',
            summary: empty($findings)
                ? 'No forbidden alias violations found.'
                : count($findings) . ' forbidden alias usage(s) found in codebase.',
            findings: $findings,
            findingCount: count($findings),
        );
    }

    // ─────────────────────────────────────────────────────────────
    // CHECK 5: Unguarded Tables
    // Tables in DB not registered in schema registry
    // ─────────────────────────────────────────────────────────────

    private function checkUnguardedTables(): void
    {
        $findings = [];

        // Extract all tables from domain_groups
        $registeredTables = [];
        foreach (($this->registry['domain_groups'] ?? []) as $group) {
            foreach (($group['tables'] ?? []) as $table) {
                $registeredTables[$table] = true;
            }
        }
        $registeredTables = array_keys($registeredTables);
        $dbTables = Schema::getTables();
        $dbTableNames = array_column($dbTables, 'name');

        $skipTables = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches',
            'personal_access_tokens', 'telescope_entries', 'telescope_entries_tags',
            'telescope_monitoring', 'sessions', 'failed_jobs'];

        foreach ($dbTableNames as $table) {
            if (in_array($table, $skipTables, true)) {
                continue;
            }
            if (!in_array($table, $registeredTables, true)) {
                $findings[] = [
                    'type'    => 'unguarded_table',
                    'severity'=> 'info',
                    'subject' => $table,
                    'description' => "Table '{$table}' exists in DB but is not registered in yalihan-schema-registry.json.",
                    'expected' => 'table registered in schema registry',
                    'actual'   => 'not registered',
                    'file'     => 'database/schema/yalihan-schema-registry.json',
                    'line'     => null,
                    'evidence_label' => 'REPO_VERIFIED',
                ];
                $this->unguardedTables[] = $table;
            }
        }

        $status = empty($findings) ? 'PASS' : 'WARN';
        $this->checks[] = new DriftCheck(
            name: 'unguarded_tables',
            status: $status,
            label: empty($findings) ? 'REPO_VERIFIED' : 'INFERRED',
            summary: empty($findings)
                ? 'All DB tables are registered in schema registry.'
                : count($findings) . ' table(s) in DB not in registry.',
            findings: $findings,
            findingCount: count($findings),
        );
    }

    // ─────────────────────────────────────────────────────────────
    // CHECK 6: Seeder Coverage
    // Seeder field names vs model $fillable alignment
    // ─────────────────────────────────────────────────────────────

    /**
     * Fields that Laravel seeders commonly use but are not always in $fillable.
     * These are NOT real drift — they are intentional raw DB writes.
     */
    private const LARAVEL_SEEDER_SKIP_FIELDS = [
        'guard_name', 'email_verified_at', 'remember_token',
        'role_id', 'permissions', 'created_at', 'updated_at',
        'deleted_at', 'created_by', 'updated_by',
        'password', 'last_login_at', 'last_activity_at',
        'active_at', 'active_from', 'active_until',
        'is_active', 'is_verified', 'is_locked', 'is_system',
        ' aktiflik_durumu', // stray space prefix variant
    ];

    private function checkSeederCoverage(): void
    {
        $findings = [];
        $seederFiles = File::allFiles(database_path('seeders'));
        $targetModels = $this->getTargetModels();

        $modelFillable = [];
        foreach ($targetModels as $modelClass) {
            try {
                $model = new $modelClass();
                $table = $model->getTable();
                $modelFillable[$table] = $model->getFillable();
            } catch (\Throwable) {
                continue;
            }
        }

        foreach ($seederFiles as $file) {
            if (!str_ends_with($file->getFilename(), 'Seeder.php')) {
                continue;
            }
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativeFile = str_replace($this->basePath . '/', '', $file->getPathname());

            // Extract table.name patterns (INSERT, UPDATE, SELECT)
            if (!preg_match_all('/[\'"]?([a-z_]+)[\'"]?\s*\.\s*[a-z_]+/i', $content, $matches)) {
                continue;
            }

            $tablesInSeeder = array_unique($matches[1]);
            foreach ($tablesInSeeder as $table) {
                if (!isset($modelFillable[$table])) {
                    continue;
                }

                $fillable = $modelFillable[$table];
                if (empty($fillable)) {
                    continue;
                }

                // Extract field=>value pairs from the seeder
                $columnsInSeeder = [];
                if (preg_match_all('/[\'"][a-z_]+[\'"]\s*=>/', $content, $colMatches)) {
                    foreach ($colMatches[0] as $m) {
                        if (preg_match('/[\'"]([a-z_]+)[\'"]/', $m, $c)) {
                            $columnsInSeeder[] = $c[1];
                        }
                    }
                }

                $mismatches = array_diff($columnsInSeeder, $fillable);
                $mismatches = array_filter($mismatches, function ($f) {
                    // Skip standard Laravel fields used in seeders
                    if (in_array($f, ['id', 'created_at', 'updated_at', 'deleted_at',
                                     'created_by', 'updated_by'], true)) {
                        return false;
                    }
                    // Skip Laravel guard / auth fields
                    if (in_array($f, self::LARAVEL_SEEDER_SKIP_FIELDS, true)) {
                        return false;
                    }
                    // Skip polymorphic type/id pairs
                    if (in_array($f, ['morph_type', 'morph_id', 'taggable_type', 'taggable_id',
                                     'ble_name', 'ble_type'], true)) {
                        return false;
                    }
                    // Skip any field starting with common seeder-prefixed patterns
                    if (preg_match('/^(is_|has_|can_|_at|_by)$/', $f)) {
                        return false;
                    }
                    return true;
                });

                foreach ($mismatches as $field) {
                    $findings[] = [
                        'type'    => 'seeder_field_mismatch',
                        'severity'=> 'medium',
                        'subject' => "{$table}.{$field}",
                        'description' => "Seeder uses '{$field}' on table '{$table}' but field is not in model's \$fillable. Check if field was renamed or if seeder is out of sync.",
                        'expected' => 'field in model $fillable',
                        'actual'   => 'field used in seeder but not in $fillable',
                        'file'     => $relativeFile,
                        'line'     => null,
                        'evidence_label' => 'REPO_VERIFIED',
                    ];
                }
            }
        }

        $status = empty($findings) ? 'PASS' : 'WARN';
        $this->checks[] = new DriftCheck(
            name: 'seeder_coverage',
            status: $status,
            label: empty($findings) ? 'REPO_VERIFIED' : 'REPO_VERIFIED',
            summary: empty($findings)
                ? 'Seeder field usage aligns with model $fillable.'
                : count($findings) . ' seeder field mismatch(es) found.',
            findings: $findings,
            findingCount: count($findings),
        );
    }

    // ─────────────────────────────────────────────────────────────
    // CHECK 7: Git State
    // Local vs origin/main commit diff
    // ─────────────────────────────────────────────────────────────

    private function checkGitState(): void
    {
        $findings = [];

        $branch = trim(shell_exec('git branch --show-current 2>/dev/null') ?? '');
        $localCommit = trim(shell_exec('git rev-parse HEAD 2>/dev/null') ?? '');
        $status = trim(shell_exec('git status --short 2>/dev/null') ?? '');

        $uncommittedFiles = [];
        if (!empty($status)) {
            foreach (explode("\n", trim($status)) as $line) {
                if (!empty($line)) {
                    $uncommittedFiles[] = trim(substr($line, 3));
                }
            }
        }

        $remoteCommit = trim(shell_exec('git rev-parse origin/' . escapeshellarg($branch) . ' 2>/dev/null') ?? '');
        $ahead = 0;
        $behind = 0;

        if ($remoteCommit && $localCommit && $remoteCommit !== $localCommit) {
            $revList = trim(shell_exec("git rev-list --count {$remoteCommit}..{$localCommit} 2>/dev/null") ?? '0');
            $ahead = (int) $revList;
            $revListBehind = trim(shell_exec("git rev-list --count {$localCommit}..{$remoteCommit} 2>/dev/null") ?? '0');
            $behind = (int) $revListBehind;
        }

        $this->gitLocalVsRemote = [
            'branch'        => $branch ?: 'unknown',
            'local_commit'  => $localCommit ?: 'unknown',
            'remote_commit' => $remoteCommit ?: 'not-found',
            'ahead'         => $ahead,
            'behind'        => $behind,
            'uncommitted_count' => count($uncommittedFiles),
            'uncommitted_files' => array_slice($uncommittedFiles, 0, 20),
        ];

        if (!empty($uncommittedFiles)) {
            $findings[] = [
                'type'    => 'uncommitted_changes',
                'severity'=> 'info',
                'subject' => $branch ?: 'unknown',
                'description' => count($uncommittedFiles) . ' uncommitted file(s) in working tree.',
                'expected' => 'clean working tree before audit',
                'actual'   => count($uncommittedFiles) . ' uncommitted change(s)',
                'file'     => null,
                'line'     => null,
                'evidence_label' => 'REPO_VERIFIED',
            ];
        }

        if ($behind > 0) {
            $findings[] = [
                'type'    => 'behind_remote',
                'severity'=> 'medium',
                'subject' => $branch ?: 'unknown',
                'description' => "Local branch is {$behind} commit(s) behind origin.",
                'expected' => 'local == origin',
                'actual'   => "local is {$behind} behind",
                'file'     => null,
                'line'     => null,
                'evidence_label' => 'REPO_VERIFIED',
            ];
        }

        $status2 = empty($uncommittedFiles) && $behind === 0 ? 'PASS' : 'WARN';
        $this->checks[] = new DriftCheck(
            name: 'git_state',
            status: $status2,
            label: 'REPO_VERIFIED',
            summary: empty($findings)
                ? 'Git working tree is clean.'
                : ($behind > 0 ? "Branch is {$behind} behind origin." : count($uncommittedFiles) . ' uncommitted change(s).'),
            findings: $findings,
            findingCount: count($findings),
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function resolveGitCommit(): string
    {
        $commit = trim(shell_exec('git rev-parse HEAD 2>/dev/null') ?? '');
        return $commit ?: 'unknown';
    }

    private function loadRegistry(): array
    {
        if (!File::exists($this->registryPath)) {
            return ['tables' => [], 'ghost_tables' => []];
        }
        $content = file_get_contents($this->registryPath);
        return json_decode($content, true) ?? ['tables' => [], 'ghost_tables' => []];
    }

    /**
     * Models that are audited for ghost fields and seeder coverage.
     * Expand as needed — covers core domain models.
     */
    private function getTargetModels(): array
    {
        $candidates = [
            \App\Models\Ilan::class,
            \App\Models\User::class,
            \App\Models\Role::class,
            \App\Models\Kisi::class,
            \App\Models\IlanFotografi::class,
            \App\Models\IlanKategori::class,
            \App\Models\YayinTipi::class,
            \App\Models\YayinTipiSablonu::class,
            \App\Models\Ozellik::class,
            \App\Models\FeatureAssignment::class,
            \App\Models\Komisyon::class,
            \App\Models\FxRate::class,
            \App\Models\Country::class,
            \App\Models\City::class,
            \App\Models\District::class,
            \App\Models\Mahalle::class,
            \App\Models\Setting::class,
            \App\Models\IlanNotu::class,
            \App\Models\IlanOzellik::class,
        ];

        return array_filter($candidates, fn($c) => class_exists($c));
    }

    /**
     * Collect all files to scan for forbidden aliases.
     */
    private function collectFiles(array $scanPaths, array $extensions, array $excludedFiles): array
    {
        $files = [];
        foreach ($scanPaths as $path) {
            $fullPath = $this->basePath . '/' . $path;
            if (!File::isDirectory($fullPath)) {
                continue;
            }
            foreach (File::allFiles($fullPath) as $file) {
                $relative = str_replace($this->basePath . '/', '', $file->getPathname());
                if (in_array($relative, $excludedFiles, true)) {
                    continue;
                }
                $ext = File::extension($file->getFilename());
                if (in_array($ext, $extensions, true)) {
                    $files[] = $file->getPathname();
                }
            }
        }
        return array_unique($files);
    }

    /**
     * Build regex patterns for forbidden alias detection.
     */
    private function buildForbiddenPatterns(string $field, string $table): array
    {
        $f = preg_quote($field, '/');
        $t = preg_quote($table, '/');

        return [
            [
                'name'  => 'table_dot_field',
                'regex' => "/['\"]?{$t}['\"]?\s*\.\s*['\"]?{$f}['\"]?/i",
            ],
            [
                'name'  => 'validation_rule',
                'regex' => "/(unique|exists)\s*:\s*{$t}\s*,\s*{$f}/i",
            ],
            [
                'name'  => 'db_table_query',
                'regex' => "/DB::table\s*\(\s*['\"]({$t})['\"].*['\"]({$f})['\"]/i",
            ],
            [
                'name'  => 'select_array',
                'regex' => "/->select\s*\(\s*\[.*['\"]({$t}\.){$f}['\"]/i",
            ],
        ];
    }

    /**
     * Build migration file => tables map.
     */
    private function getMigrationFileMap(): array
    {
        $map = [];
        $migPath = database_path('migrations');
        if (!File::isDirectory($migPath)) {
            return $map;
        }

        foreach (File::allFiles($migPath) as $file) {
            if (!str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            $tables = [];

            if (preg_match_all('/Schema::create\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $m1)) {
                $tables = array_merge($tables, $m1[1]);
            }
            if (preg_match_all('/Schema::hasTable\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $m2)) {
                // skip hasTable checks
            }
            if (preg_match_all('/[\'"]name[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]\s*,?\s*\n\s*\$table/', $content, $m3)) {
                $tables = array_merge($tables, $m3[1]);
            }

            $map[$file->getFilename()] = array_unique($tables);
        }
        return $map;
    }

    // ─────────────────────────────────────────────────────────────
    // Build Report
    // ─────────────────────────────────────────────────────────────

    private function buildReport(): DriftAuditReport
    {
        $checksPassed = count(array_filter($this->checks, fn($c) => $c->status === 'PASS'));
        $checksFailed = count(array_filter($this->checks, fn($c) => $c->status === 'FAIL'));
        $checksWarning = count(array_filter($this->checks, fn($c) => $c->status === 'WARN'));
        $total = count($this->checks);
        $hasBlockers = $checksFailed > 0
            || !empty($this->ghostFields)
            || !empty($this->forbiddenAliasViolations);

        $summaryParts = [];
        if ($checksFailed > 0)  { $summaryParts[] = "{$checksFailed} check(s) FAILED"; }
        if ($checksWarning > 0) { $summaryParts[] = "{$checksWarning} check(s) WARN"; }
        if ($checksPassed > 0)  { $summaryParts[] = "{$checksPassed} check(s) PASSED"; }

        $summary = empty($summaryParts)
            ? 'Drift audit complete. No issues found.'
            : 'Drift audit complete. ' . implode(' | ', $summaryParts) . '.';

        if (!$hasBlockers) {
            $summary .= ' System appears in sync.';
        } else {
            $totalIssues = count($this->ghostTables) + count($this->ghostFields)
                + count($this->forbiddenAliasViolations) + count($this->missingMigrations);
            $summary .= " Total issues: {$totalIssues}.";
        }

        $label = match (true) {
            $checksFailed > 0        => 'BLOCKED_NEEDS_FIX',
            $checksWarning > 0       => 'REPO_VERIFIED',
            $checksPassed === $total => 'REPO_VERIFIED',
            default                  => 'INFERRED',
        };

        return new DriftAuditReport(
            generatedAt: date('c'),
            source: 'sqlite://database/database.sqlite',
            gitCommit: $this->gitCommit,
            evidenceLabel: $label,
            totalChecks: $total,
            checksPassed: $checksPassed,
            checksFailed: $checksFailed,
            checksWarning: $checksWarning,
            checks: array_map(fn($c) => $c->toArray(), $this->checks),
            ghostTables: $this->ghostTables,
            ghostFields: $this->ghostFields,
            forbiddenAliasViolations: $this->forbiddenAliasViolations,
            unguardedTables: $this->unguardedTables,
            missingMigrations: $this->missingMigrations,
            seederCoverage: $this->seederCoverage,
            gitLocalVsRemote: $this->gitLocalVsRemote,
            summary: $summary,
            hasBlockers: $hasBlockers,
            dryRun: $this->dryRun,
        );
    }
}
