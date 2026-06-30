<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\TestFixtureHelper;

/**
 * TestCase
 * 
 * 🛡️ SAB §12: Test Bootstrap Authority
 * Stabilized: 2026-04-18
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions {
        beginDatabaseTransaction as protected baseBeginDatabaseTransaction;
    }
    use TestFixtureHelper; // 🛡️ Phase T3: Fixture Canonicalization

    protected static bool $schemaInitialized = false;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        $needsBootstrap = ! self::$schemaInitialized;

        parent::setUp();

        // 🛡️ Phase T2: Schema Authority Unification
        $connection = config('database.default');
        $isSqliteMemory = $connection === 'sqlite' && config('database.connections.sqlite.database') === ':memory:';

        if ($needsBootstrap || $isSqliteMemory) {
            $this->initializeTestDatabase();
            
            if (!$isSqliteMemory) {
                self::$schemaInitialized = true;
            }

            // DatabaseTransactions is hooked during parent::setUp().
            // On first test we skip transaction start until schema bootstrap is complete.
            $this->baseBeginDatabaseTransaction();
        }

        // 🛡️ Phase T1: Queue Stabilization
        // Fake ALL queues by default for speed.
        Queue::fake();
    }

    /**
     * Delay transaction lifecycle until schema authority is initialized.
     */
    protected function beginDatabaseTransaction()
    {
        if (! self::$schemaInitialized) {
            return;
        }

        $this->baseBeginDatabaseTransaction();
    }

    /**
     * Initialize the test database using the canonical schema dump.
     * 🛡️ Authority: database/schema/mysql-schema.sql
     */
    protected function initializeTestDatabase(): void
    {
        // 1. Forcefully disable Telescope for tests to prevent shutdown leaks
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        $connection = config('database.default');
        
        // 2. Force TCP connection (127.0.0.1) and test database context
        // This bypasses macOS sandbox issues with the mysql.sock file
        if ($connection !== 'sqlite') {
            config([
                "database.connections.{$connection}.host" => '127.0.0.1',
                "database.connections.{$connection}.database" => 'yalihanai_test',
            ]);
        }
        
        DB::purge($connection);
        DB::reconnect($connection);

        $database = config("database.connections.{$connection}.database");

        if ($database === 'yalihanai_v2_production' || config('app.env') === 'production') {
            throw new \RuntimeException("CRITICAL: Attempted to wipe production database during test bootstrap!");
        }

        $schemaPath = base_path('database/schema/mysql-schema.sql');
        if (! File::exists($schemaPath)) {
            $schemaPath = base_path('database/schema/testing-schema.sql');
        }

        if ($connection === 'sqlite') {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                file_put_contents('/tmp/bootstrap_error.log', $e->getMessage() . "\n" . $e->getTraceAsString());
                throw $e;
            }
            return;
        }

        try {
            echo "🛡️ Bootstrap: Nuking tables and views...\n";
            
            Schema::connection($connection)->disableForeignKeyConstraints();
            
            // Drop Views
            $views = Schema::connection($connection)->getViews();
            if (!empty($views)) {
                $viewNames = array_map(fn($v) => "`{$v['name']}`", $views);
                DB::connection($connection)->statement('DROP VIEW IF EXISTS ' . implode(', ', $viewNames));
            }

            // Drop Tables
            $tables = Schema::connection($connection)->getTables();
            if (!empty($tables)) {
                $tableNames = array_map(fn($t) => "`{$t['name']}`", $tables);
                // Bulk drop for speed
                DB::connection($connection)->statement('DROP TABLE IF EXISTS ' . implode(', ', $tableNames));
            }
            
            echo "🛡️ Bootstrap: Loading mysql-schema.sql...\n";
            DB::connection($connection)->unprepared(File::get($schemaPath));
            
            echo "🛡️ Bootstrap: Running migrations...\n";
            Artisan::call('migrate', ['--force' => true]);
            
            echo "🛡️ Bootstrap: Clearing cache...\n";
            Artisan::call('cache:clear');
            
            Schema::connection($connection)->enableForeignKeyConstraints();
            echo "✅ Bootstrap: Completed.\n";

        } catch (\Throwable $e) {
            // Enhanced logging for CI/Terminal debugging
            $errorMsg = "Failed to initialize test database: " . $e->getMessage();
            echo "\n" . str_repeat('=', 80) . "\n";
            echo "❌ CRITICAL: {$errorMsg}\n";
            echo "Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
            echo str_repeat('=', 80) . "\n";
            
            throw new \RuntimeException($errorMsg);
        }
    }

    protected function tearDown(): void
    {
        // 3. Ensure DB connection is closed at the end of every test class to prevent socket exhaustion
        DB::disconnect();
        parent::tearDown();
    }
}
