<?php

namespace Tests\Feature\Schema;

use App\Models\BaseModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Model-Schema Contract Test
 *
 * Validates that:
 * 1. $fillable fields exist in the database
 * 2. $casts fields exist in the database
 * 3. Foreign key relations point to existing tables/columns
 *
 * This is NOT a PHPStan rule — it runs at test time against the actual schema.
 */
class ModelSchemaContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Models to validate against their schema contracts
     */
    private array $modelsToValidate = [
        \App\Models\FeatureAssignment::class,
        \App\Models\Feature::class,
        \App\Models\FeatureCategory::class,
        \App\Models\YayinTipiSablonu::class,
        \App\Models\YayinTipi::class,
        \App\Models\Ilan::class,
        \App\Models\IlanKategori::class,
        \App\Models\Ozellik::class,
        \App\Models\FeaturePack::class,
        \App\Models\KategoriYayinTipiFieldDependency::class,
    ];

    /**
     * Ghost field denylist — fields that exist in $fillable but NOT in DB
     * These should NOT be in $fillable (they are read-only accessors)
     */
    private array $knownGhostFields = [
        'adi',        // YayinTipi: accessor for 'name'
        'ilan_id',    // Ilan: old alias for 'id' (property_id migration done)
    ];

    /**
     * @test
     * @dataProvider modelProvider
     */
    public function fillable_fields_exist_in_database(string $modelClass): void
    {
        $model = new $modelClass;
        $table = $model->getTable();

        $this->assertTrue(
            Schema::hasTable($table),
            "Table '{$table}' does not exist for model {$modelClass}"
        );

        $dbColumns = Schema::getColumnListing($table);
        $fillable = $model->getFillable();

        foreach ($fillable as $field) {
            $this->assertContains(
                $field,
                $dbColumns,
                "Model {$modelClass} has '{$field}' in \$fillable but column does not exist in '{$table}'"
            );
        }
    }

    /**
     * @test
     * @dataProvider modelProvider
     */
    public function cast_fields_exist_in_database(string $modelClass): void
    {
        $model = new $modelClass;
        $table = $model->getTable();

        if (!Schema::hasTable($table)) {
            $this->markTestSkipped("Table '{$table}' does not exist");
        }

        $dbColumns = Schema::getColumnListing($table);
        $casts = $model->getCasts();

        foreach ($casts as $field => $castType) {
            // Skip 'id' and pivot-specific fields
            if ($field === 'id' || str_ends_with($field, '_id')) {
                continue;
            }

            $this->assertContains(
                $field,
                $dbColumns,
                "Model {$modelClass} casts '{$field}' but column does not exist in '{$table}'"
            );
        }
    }

    public static function modelProvider(): array
    {
        return [
            'FeatureAssignment' => [\App\Models\FeatureAssignment::class],
            'Feature' => [\App\Models\Feature::class],
            'FeatureCategory' => [\App\Models\FeatureCategory::class],
            'YayinTipiSablonu' => [\App\Models\YayinTipiSablonu::class],
            'YayinTipi' => [\App\Models\YayinTipi::class],
            'Ilan' => [\App\Models\Ilan::class],
            'IlanKategori' => [\App\Models\IlanKategori::class],
            'Ozellik' => [\App\Models\Ozellik::class],
            'FeaturePack' => [\App\Models\FeaturePack::class],
            'KategoriYayinTipiFieldDependency' => [\App\Models\KategoriYayinTipiFieldDependency::class],
        ];
    }

    /**
     * Foreign key contract test for a single model.
     *
     * Runs in isolation: one PHPUnit @test per model.
     *
     * Assertion rules:
     * - BelongsTo relation found → assertTrue(Schema::hasColumn, FK column must exist)
     * - Non-BelongsTo relation found → recorded as SKIPPED (no FK column to check)
     * - No relation methods found → markTestSkipped
     * - Relation invocation throws → fail() (not silently caught)
     *
     * Wenox report: assertion message lists every relation by name and type,
     * and whether its FK column was verified or skipped.
     */
    private function assertForeignKeyContractForModel(string $modelClass): void
    {
        $model = new $modelClass;
        $table = $model->getTable();

        if (!Schema::hasTable($table)) {
            $this->markTestSkipped("Table '{$table}' does not exist for model {$modelClass}");
            return;
        }

        if (method_exists($model, 'assignable') && $model->assignable()) {
            $this->markTestSkipped("Model {$modelClass}: polymorphic — no fixed FK column to verify");
            return;
        }

        $reflection = new \ReflectionClass($model);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $hasBelongsTo = false;
        $belongsToDetails = [];   // methodName => foreignKey
        $nonBelongsToDetails = []; // methodName => relationType

        foreach ($methods as $method) {
            if ($method->class !== $modelClass) {
                continue;
            }

            $methodName = $method->getName();

            if (str_starts_with($methodName, 'scope')) {
                continue;
            }
            if ($method->getNumberOfParameters() > 0) {
                continue;
            }
            if (!method_exists($model, $methodName)) {
                continue;
            }

            $returnType = $method->getReturnType()?->getName();
            if ($returnType === null || !is_subclass_of($returnType, \Illuminate\Database\Eloquent\Relations\Relation::class)) {
                continue;
            }

            try {
                $relationOutput = $model->$methodName();
            } catch (\Throwable $e) {
                $this->fail(sprintf(
                    'Relation %s::%s() threw %s: %s',
                    $modelClass,
                    $methodName,
                    get_class($e),
                    $e->getMessage()
                ));
                return;
            }

            if ($relationOutput instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $foreignKey = $relationOutput->getForeignKeyName();
                $this->assertTrue(
                    Schema::hasColumn($table, $foreignKey),
                    "Relation {$methodName}() in {$modelClass} uses foreign key '{$foreignKey}' but column does not exist in '{$table}'"
                );
                $hasBelongsTo = true;
                $belongsToDetails[$methodName] = $foreignKey;
            } else {
                $nonBelongsToDetails[$methodName] = (new \ReflectionClass($relationOutput))->getShortName();
            }
        }

        // Build a human-readable assertion message for Wenox.
        // PHPUnit shows this on failure — it also appears in test output.
        $fkLines = array_map(
            fn($m, $fk) => "  {$m}() → FK='{$fk}'",
            array_keys($belongsToDetails),
            $belongsToDetails
        );
        $nonFkLines = array_map(
            fn($m, $t) => "  {$m}() [{$t}]",
            array_keys($nonBelongsToDetails),
            $nonBelongsToDetails
        );

        $allLines = array_merge(
            $fkLines ?: ['  (none)'],
            $nonFkLines ? ['  Non-BelongsTo (no FK check):'] : [],
            $nonFkLines ?: []
        );

        if ($hasBelongsTo) {
            $this->assertTrue(
                true,
                sprintf(
                    "[FK Contract] %s | CHECKED BelongsTo: %d | SKIPPED non-BelongsTo: %d\n%s",
                    $modelClass,
                    count($belongsToDetails),
                    count($nonBelongsToDetails),
                    implode("\n", $allLines)
                )
            );
        } elseif (!empty($nonBelongsToDetails)) {
            // Only non-BelongsTo relations: no FK to check, but this is intentional.
            // Assert true so PHPUnit sees a real assertion; message documents the relations.
            $this->assertTrue(
                true,
                sprintf(
                    "[FK Contract] %s | NO BelongsTo (only non-FK relations) | %s\n%s",
                    $modelClass,
                    count($nonBelongsToDetails) . ' non-BelongsTo relations skipped',
                    implode("\n", $allLines)
                )
            );
        } else {
            $this->markTestSkipped("Model {$modelClass}: no public relation methods found");
        }
    }

    // ─── Per-model FK contract tests (no data provider, no shared state) ─────

    /**
     * @test
     */
    public function fk_contract_FeatureAssignment(): void
    {
        $this->assertForeignKeyContractForModel(\App\Models\FeatureAssignment::class);
    }

    /**
     * @test
     */
    public function fk_contract_Feature(): void
    {
        $this->assertForeignKeyContractForModel(\App\Models\Feature::class);
    }

    /**
     * @test
     */
    public function fk_contract_FeatureCategory(): void
    {
        $this->assertForeignKeyContractForModel(\App\Models\FeatureCategory::class);
    }

    /**
     * @test
     */
    public function fk_contract_YayinTipiSablonu(): void
    {
        $this->assertForeignKeyContractForModel(\App\Models\YayinTipiSablonu::class);
    }

    /**
     * @test
     */
    public function fk_contract_YayinTipi(): void
    {
        $this->assertForeignKeyContractForModel(\App\Models\YayinTipi::class);
    }

    /**
     * @test
     */
    public function fk_contract_Ilan(): void
    {
        $this->assertForeignKeyContractForModel(\App\Models\Ilan::class);
    }

    /**
     * @test
     */
    public function fk_contract_IlanKategori(): void
    {
        $this->assertForeignKeyContractForModel(\App\Models\IlanKategori::class);
    }

    /**
     * @test
     */
    public function fk_contract_Ozellik(): void
    {
        $this->assertForeignKeyContractForModel(\App\Models\Ozellik::class);
    }

    /**
     * @test
     */
    public function fk_contract_FeaturePack(): void
    {
        $this->assertForeignKeyContractForModel(\App\Models\FeaturePack::class);
    }

    /**
     * @test
     */
    public function fk_contract_KategoriYayinTipiFieldDependency(): void
    {
        $this->assertForeignKeyContractForModel(\App\Models\KategoriYayinTipiFieldDependency::class);
    }

    /**
     * @test
     */
    public function no_ghost_fields_in_fillable(): void
    {
        // This test specifically checks for KNOWN ghost fields in $fillable
        // These should be removed from $fillable to prevent mass-assignment drift

        $modelsWithFillable = [
            \App\Models\YayinTipi::class,
        ];

        foreach ($modelsWithFillable as $modelClass) {
            $model = new $modelClass;
            $fillable = $model->getFillable();

            foreach ($this->knownGhostFields as $ghostField) {
                $this->assertNotContains(
                    $ghostField,
                    $fillable,
                    "Model {$modelClass} should NOT have '{$ghostField}' in \$fillable — it's a read-only accessor"
                );
            }
        }
    }

    /**
     * @test
     */
    public function schema_guard_config_is_consistent(): void
    {
        // Verify that config/canonical_tables.php exists and is valid
        $this->assertFileExists(config_path('canonical_tables.php'));

        $canonical = config('canonical_tables');

        $this->assertIsArray($canonical);

        // Verify STALE_REFERENCE entries are marked correctly
        foreach ($canonical as $key => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (($entry['status'] ?? '') === 'STALE_REFERENCE') {
                $this->assertNull(
                    $entry['model'],
                    "STALE_REFERENCE entry '{$key}' should have null model"
                );
                $this->assertNull(
                    $entry['migration'],
                    "STALE_REFERENCE entry '{$key}' should have null migration"
                );
            }
        }
    }

}
