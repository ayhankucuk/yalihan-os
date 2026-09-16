<?php

namespace Tests\Feature\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

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
}
