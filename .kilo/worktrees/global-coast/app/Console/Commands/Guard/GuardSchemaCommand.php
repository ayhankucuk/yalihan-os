<?php

namespace App\Console\Commands\Guard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GuardSchemaCommand extends Command
{
    protected $signature = 'guard:schema';
    protected $description = '🛡️ Schema Drift Detector: Verifies Model $fillable fields match actual DB columns.';

    /**
     * Critical models to validate against their tables.
     */
    protected array $modelMap = [
        \App\Models\V2\Ilan::class => 'ilanlar',
        \App\Models\V2\User::class => 'users',
    ];

    public function handle(): int
    {
        $this->info('🛡️ Schema Drift Detector — Scanning model/DB alignment...');
        $this->newLine();

        $driftFound = false;
        $report = [];

        foreach ($this->modelMap as $modelClass => $tableName) {
            if (!class_exists($modelClass)) {
                $this->warn("⚠️  Model {$modelClass} not found, skipping.");
                continue;
            }

            if (!Schema::hasTable($tableName)) {
                $this->warn("⚠️  Table {$tableName} not found, skipping.");
                continue;
            }

            $model = new $modelClass;
            $fillable = $model->getFillable();
            $dbColumns = Schema::getColumnListing($tableName);

            // System columns to exclude from comparison
            $systemColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token'];
            $dbUserColumns = array_diff($dbColumns, $systemColumns);

            $missingInDb = array_diff($fillable, $dbColumns);
            $extraInDb = array_diff($dbUserColumns, $fillable);

            $modelBasename = class_basename($modelClass);

            if (!empty($missingInDb)) {
                $driftFound = true;
                $this->error("❌ {$modelBasename}: fillable fields missing in DB: " . implode(', ', $missingInDb));
                $report[] = [
                    'model' => $modelBasename,
                    'issue' => 'MISSING_IN_DB',
                    'fields' => $missingInDb,
                ];
            }

            if (!empty($extraInDb)) {
                // Extra DB columns are warnings, not failures (could be legacy)
                $this->warn("⚠️  {$modelBasename}: DB columns not in \$fillable: " . implode(', ', array_slice($extraInDb, 0, 10)));
                if (count($extraInDb) > 10) {
                    $this->warn("   ... and " . (count($extraInDb) - 10) . " more");
                }
            }

            if (empty($missingInDb) && empty($extraInDb)) {
                $this->info("✅ {$modelBasename}: Schema aligned.");
            }
        }

        $this->newLine();

        if ($driftFound) {
            $this->error('❌ SCHEMA DRIFT DETECTED — Fix fillable/migration alignment before deploy.');
            return 1;
        }

        $this->info('✅ SCHEMA DRIFT: PASS — All models aligned with DB.');
        return 0;
    }
}
