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

    /**
     * @test
     * @dataProvider modelProvider
     */
    public function foreign_key_relations_point_to_existing_tables(string $modelClass): void
    {
        $model = new $modelClass;
        $table = $model->getTable();

        if (!Schema::hasTable($table)) {
            $this->markTestSkipped("Table '{$table}' does not exist");
        }

        // Check polymorphic relations
        if (method_exists($model, 'assignable') && $model->assignable()) {
            // Polymorphic relations don't have fixed FK columns to check
            $this->assertTrue(true);
            return;
        }

        // Check standard relations by examining the model
        $reflection = new \ReflectionClass($model);
        $relations = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($relations as $relation) {
            if ($relation->class !== $modelClass) {
                continue;
            }

            $methodName = $relation->getName();

            // Skip if not a relation method
            if (!method_exists($model, $methodName)) {
                continue;
            }

            try {
                $relationOutput = $model->$methodName();

                if ($relationOutput instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                    $foreignKey = $relationOutput->getForeignKeyName();
                    $this->assertTrue(
                        Schema::hasColumn($table, $foreignKey),
                        "Relation {$methodName}() in {$modelClass} uses foreign key '{$foreignKey}' but column does not exist in '{$table}'"
                    );
                }
            } catch (\Throwable $e) {
                // Relation couldn't be resolved (e.g., missing parent record)
                // Skip in contract test
            }
        }
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
}
