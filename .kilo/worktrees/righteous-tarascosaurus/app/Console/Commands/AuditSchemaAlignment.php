<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditSchemaAlignment extends Command
{
    protected $signature = 'system:audit-schema-alignment
        {--table=* : Audit specific table(s) only}
        {--json : Output as JSON}';

    protected $description = '🔍 Schema Guard: Audit DB ↔ Model ↔ Code alignment for guarded tables';

    private array $results = [];

    /**
     * Tables to audit: table_name => model_class
     */
    private array $guardedTables = [
        'property_features'            => \App\Models\PropertyHub\PropertyFeature::class,
        'property_templates'           => \App\Models\PropertyHub\PropertyTemplate::class,
        'template_feature_assignments' => \App\Models\TemplateFeatureAssignment::class,
        'fx_rates'                     => \App\Models\ExchangeRate::class,
        'yayin_tipi_sablonlari'        => \App\Models\YayinTipiSablonu::class,
        'ilanlar'                      => \App\Models\Ilan::class,
    ];

    public function handle(): int
    {
        $this->info('');
        $this->info('🔍 Schema Guard — Alignment Audit');
        $this->info('─────────────────────────────────────────');
        $this->newLine();

        $tableFilter = $this->option('table');
        $tablesToAudit = $this->guardedTables;

        if (!empty($tableFilter)) {
            $tablesToAudit = array_filter($tablesToAudit, fn($_, $t) => in_array($t, $tableFilter), ARRAY_FILTER_USE_BOTH);
        }

        foreach ($tablesToAudit as $table => $modelClass) {
            $this->auditTable($table, $modelClass);
        }

        // Check forbidden alias violations from config
        $this->auditForbiddenAliases();

        if ($this->option('json')) {
            $this->line(json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->renderReport();
        }

        $hasIssues = collect($this->results)->contains(fn($r) => !empty($r['issues']));

        return $hasIssues ? self::FAILURE : self::SUCCESS;
    }

    private function auditTable(string $table, string $modelClass): void
    {
        $result = [
            'table'  => $table,
            'model'  => $modelClass,
            'status' => 'OK',
            'issues' => [],
            'info'   => [],
        ];

        // 1. Check table exists
        if (!Schema::hasTable($table)) {
            $result['status'] = 'MISSING';
            $result['issues'][] = [
                'type'    => 'table_missing',
                'message' => "Table '{$table}' does not exist in database.",
            ];
            $this->results[$table] = $result;
            return;
        }

        // 2. Get actual DB columns
        $dbColumns = Schema::getColumnListing($table);

        // 3. Check model exists and get fillable
        if (!class_exists($modelClass)) {
            $result['status'] = 'ERROR';
            $result['issues'][] = [
                'type'    => 'model_missing',
                'message' => "Model class '{$modelClass}' not found.",
            ];
            $this->results[$table] = $result;
            return;
        }

        $model = new $modelClass;

        // 4. Compare fillable vs DB columns
        $fillable = $model->getFillable();
        $guarded = $model->getGuarded();

        if (!empty($fillable)) {
            $fillableNotInDb = array_diff($fillable, $dbColumns);
            foreach ($fillableNotInDb as $col) {
                $result['status'] = 'DRIFT';
                $result['issues'][] = [
                    'type'    => 'fillable_ghost',
                    'message' => "Model \$fillable contains '{$col}' but column does not exist in DB.",
                    'field'   => $col,
                ];
            }
        }

        // 5. Check casts for non-existent columns
        $casts = $model->getCasts();
        foreach ($casts as $col => $castType) {
            if ($col === 'id') {
                continue;
            }
            if (!in_array($col, $dbColumns)) {
                $result['status'] = 'DRIFT';
                $result['issues'][] = [
                    'type'    => 'cast_ghost',
                    'message' => "Model \$casts defines '{$col}' ({$castType}) but column does not exist in DB.",
                    'field'   => $col,
                ];
            }
        }

        // 6. Check table name matches model
        if ($model->getTable() !== $table) {
            $result['issues'][] = [
                'type'    => 'table_mismatch',
                'message' => "Model->getTable() returns '{$model->getTable()}' but expected '{$table}'.",
            ];
            $result['status'] = 'DRIFT';
        }

        // 7. Record column count for info
        $result['info'] = [
            'db_columns'     => count($dbColumns),
            'fillable_count' => count($fillable),
            'cast_count'     => count($casts),
        ];

        if (empty($result['issues'])) {
            $result['status'] = 'OK';
        }

        $this->results[$table] = $result;
    }

    private function auditForbiddenAliases(): void
    {
        $config = config('schema_guard');
        if (!$config || empty($config['forbidden_aliases'])) {
            return;
        }

        $aliasReport = [
            'table'  => '_forbidden_aliases',
            'model'  => 'config/schema_guard.php',
            'status' => 'OK',
            'issues' => [],
            'info'   => [],
        ];

        $domainNotes = $config['domain_notes'] ?? [];
        $aliasCount = count($config['forbidden_aliases']);
        $bySeverity = ['critical' => 0, 'high' => 0, 'medium' => 0];

        foreach ($config['forbidden_aliases'] as $alias) {
            $bySeverity[$alias['severity']] = ($bySeverity[$alias['severity']] ?? 0) + 1;

            // Verify the correct column actually exists in DB
            $table = $alias['table'];
            if (Schema::hasTable($table) && $alias['correct_field']) {
                if (!Schema::hasColumn($table, $alias['correct_field'])) {
                    $aliasReport['status'] = 'ERROR';
                    $aliasReport['issues'][] = [
                        'type'    => 'canonical_missing',
                        'message' => "Table '{$table}' should have canonical column '{$alias['correct_field']}' but it doesn't exist!",
                    ];
                }
            }
        }

        $aliasReport['info'] = [
            'total_rules' => $aliasCount,
            'by_severity' => $bySeverity,
            'domain_contexts' => count($domainNotes),
        ];

        $this->results['_forbidden_aliases'] = $aliasReport;
    }

    private function renderReport(): void
    {
        $totalIssues = 0;

        foreach ($this->results as $key => $result) {
            if ($key === '_forbidden_aliases') {
                continue; // Render separately
            }

            $icon = match ($result['status']) {
                'OK'      => '✅',
                'DRIFT'   => '🔴',
                'MISSING' => '⚫',
                'ERROR'   => '❌',
                default   => '❓',
            };

            $this->info("{$icon} {$result['table']} ({$result['model']})");

            if (!empty($result['info'])) {
                $info = $result['info'];
                $this->line("   DB columns: {$info['db_columns']} | Fillable: {$info['fillable_count']} | Casts: {$info['cast_count']}");
            }

            if (!empty($result['issues'])) {
                foreach ($result['issues'] as $issue) {
                    $this->warn("   ⚠️  [{$issue['type']}] {$issue['message']}");
                    $totalIssues++;
                }
            }

            $this->newLine();
        }

        // Forbidden alias summary
        if (isset($this->results['_forbidden_aliases'])) {
            $fa = $this->results['_forbidden_aliases'];
            $this->info('📋 Forbidden Alias Registry');
            $this->line("   Total rules: {$fa['info']['total_rules']}");
            foreach ($fa['info']['by_severity'] as $sev => $count) {
                $icon = match ($sev) {
                    'critical' => '🔴',
                    'high'     => '🟠',
                    'medium'   => '🟡',
                    default    => '⚪',
                };
                $this->line("   {$icon} {$sev}: {$count}");
            }

            if (!empty($fa['issues'])) {
                foreach ($fa['issues'] as $issue) {
                    $this->error("   ❌ [{$issue['type']}] {$issue['message']}");
                    $totalIssues++;
                }
            }

            $this->newLine();
        }

        // Final summary
        $this->info('─────────────────────────────────────────');
        if ($totalIssues === 0) {
            $this->info('✅ All guarded tables are aligned. No schema drift.');
        } else {
            $this->error("❌ {$totalIssues} alignment issue(s) require attention.");
        }
    }
}
