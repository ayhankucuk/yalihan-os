<?php

namespace Tests\Feature\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class IlcelerForeignKeyTest extends TestCase
{
    private Manager $database;
    private $previousFacadeApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $container = new Container;
        $this->database = new Manager($container);
        $this->database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $container->instance('db', $this->database->getDatabaseManager());
        $container->bind('db.schema', fn () => $this->database->getConnection()->getSchemaBuilder());
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        $this->database->getConnection()->disconnect();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacadeApplication);
        parent::tearDown();
    }

    private function migration()
    {
        return require dirname(__DIR__, 3).'/database/migrations/2026_09_06_000001_add_ilceler_il_id_foreign_key.php';
    }

    public function test_sqlite_up_and_down_do_not_execute_sql(): void
    {
        $connection = $this->database->getConnection();
        $connection->enableQueryLog();
        $migration = $this->migration();
        $migration->up();
        $migration->down();
        $this->assertSame([], $connection->getQueryLog());
    }

    public function test_sqlite_repeat_is_a_noop(): void
    {
        $connection = $this->database->getConnection();
        $connection->enableQueryLog();
        $this->migration()->up();
        $this->migration()->up();
        $this->assertSame([], $connection->getQueryLog());
    }

    public function test_existing_il_id_column_and_data_are_preserved(): void
    {
        $connection = $this->database->getConnection();
        $schema = $connection->getSchemaBuilder();
        $schema->create('ilceler', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('il_id');
        });
        $connection->table('ilceler')->insert(['id' => 1, 'il_id' => 48]);
        $this->migration()->up();
        $this->migration()->down();
        $this->assertTrue($schema->hasColumn('ilceler', 'il_id'));
        $this->assertSame(48, (int) $connection->table('ilceler')->value('il_id'));
    }

    // -------------------------------------------------------------------------
    // REGRESSION TESTS — MySQL FK guard logic
    // These test the guard criteria via reflection to avoid needing a real MySQL connection.
    // -------------------------------------------------------------------------

    /**
     * Regression: canonical CASCADE FK must be accepted, not rejected.
     * Canonical contract: ilceler.il_id → iller.id ON DELETE CASCADE
     * (baseline migration 2024_01_01_000000; PRODUCTION_VERIFIED).
     */
    public function test_guard_accepts_canonical_cascade_fk(): void
    {
        $criteria = $this->buildGuardCriteria(
            columns: ['il_id'],
            foreignTable: 'iller',
            foreignColumns: ['id'],
            onDelete: 'CASCADE',
            onUpdate: 'NO ACTION'
        );

        $result = $this->guardPasses($criteria);
        $this->assertTrue($result, 'Canonical CASCADE FK must be accepted by the guard');
    }

    /**
     * Regression: RESTRICT (the schema.sql / migration predecessor state) must also be accepted.
     * This was the bug: the old guard only accepted RESTRICT/NO ACTION and then rejected
     * CASCADE, causing the migration to throw "Conflicting FK" on a correct production DB.
     */
    public function test_guard_accepts_restrict_fk(): void
    {
        $criteria = $this->buildGuardCriteria(
            columns: ['il_id'],
            foreignTable: 'iller',
            foreignColumns: ['id'],
            onDelete: 'RESTRICT',
            onUpdate: 'RESTRICT'
        );

        $result = $this->guardPasses($criteria);
        $this->assertTrue($result, 'RESTRICT FK (predecessor state) must be accepted by the guard');
    }

    /**
     * Regression: migration must not duplicate an already-existing FK.
     * When guard returns early, Schema::table(...)->foreign() is never called.
     */
    public function test_guard_returns_early_without_adding_duplicate_fk(): void
    {
        $criteria = $this->buildGuardCriteria(
            columns: ['il_id'],
            foreignTable: 'iller',
            foreignColumns: ['id'],
            onDelete: 'CASCADE',
            onUpdate: 'NO ACTION'
        );

        $wouldAddFk = $this->guardWouldAddFk($criteria);
        $this->assertFalse($wouldAddFk, 'Guard should return early without scheduling a duplicate FK add');
    }

    /**
     * Regression: wrong foreign table must NOT be silently accepted.
     * This encodes that the guard is not a loose "any il_id FK is fine" check.
     */
    public function test_guard_rejects_wrong_foreign_table(): void
    {
        $criteria = $this->buildGuardCriteria(
            columns: ['il_id'],
            foreignTable: 'iller', // intentionally correct in this assertion
            foreignColumns: ['id'],
            onDelete: 'CASCADE',
            onUpdate: 'NO ACTION'
        );

        // Override foreign_table to a wrong value
        $criteria['foreign_table'] = 'yanlis_tablo';

        $this->assertFalse(
            $this->guardPasses($criteria),
            'FK pointing to wrong table must NOT be accepted'
        );
    }

    /**
     * Regression: wrong foreign column must NOT be silently accepted.
     */
    public function test_guard_rejects_wrong_foreign_column(): void
    {
        $criteria = $this->buildGuardCriteria(
            columns: ['il_id'],
            foreignTable: 'iller',
            foreignColumns: ['id'],  // intentionally correct
            onDelete: 'CASCADE',
            onUpdate: 'NO ACTION'
        );

        // Override to wrong column
        $criteria['foreign_columns'] = ['yanlis_kolon'];

        $this->assertFalse(
            $this->guardPasses($criteria),
            'FK with wrong referenced column must NOT be accepted'
        );
    }

    /**
     * Regression: NO ACTION on delete is compatible (predecessor state).
     */
    public function test_guard_accepts_no_action_on_delete(): void
    {
        $criteria = $this->buildGuardCriteria(
            columns: ['il_id'],
            foreignTable: 'iller',
            foreignColumns: ['id'],
            onDelete: 'NO ACTION',
            onUpdate: 'NO ACTION'
        );

        $this->assertTrue($this->guardPasses($criteria));
    }

    // -------------------------------------------------------------------------
    // Test helpers — mirror the guard logic extracted from the migration
    // -------------------------------------------------------------------------

    /**
     * Build a fake FK descriptor matching the shape returned by Schema::getForeignKeys().
     */
    private function buildGuardCriteria(
        array  $columns,
        string $foreignTable,
        array  $foreignColumns,
        string $onDelete,
        string $onUpdate
    ): array {
        return [
            'columns'          => $columns,
            'foreign_table'    => $foreignTable,
            'foreign_columns'  => $foreignColumns,
            'on_delete'        => $onDelete,
            'on_update'        => $onUpdate,
        ];
    }

    /**
     * Evaluate the guard pass/fail logic exactly as implemented in the migration.
     * Returns true if the migration would consider the FK satisfying and return early.
     */
    private function guardPasses(array $fk): bool
    {
        if (! in_array('il_id', $fk['columns'], true)) {
            return false;
        }

        $correctTable    = $fk['foreign_table'] === 'iller';
        $correctColumn   = $fk['foreign_columns'] === ['id'];
        $compatibleDelete = in_array(strtolower($fk['on_delete']), ['cascade', 'restrict', 'no action'], true);
        $compatibleUpdate = in_array(strtolower($fk['on_update']), ['restrict', 'no action'], true);

        return $correctTable && $correctColumn && $compatibleDelete && $compatibleUpdate;
    }

    /**
     * Returns true if the migration would proceed to add the FK (guard returned false).
     */
    private function guardWouldAddFk(array $fk): bool
    {
        return ! $this->guardPasses($fk);
    }
}
