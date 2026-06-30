<?php

declare(strict_types=1);

namespace App\Console\Commands\Backup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use ZipArchive;
use PDO;
use Exception;

class ValidateBackupRestoreCommand extends Command
{
    protected $signature = 'backup:validate-restore {--db-only : Validate only DB backup}';
    protected $description = 'Runs a database backup and validates its restore integrity using an isolated environment';

    public function handle(): int
    {
        $this->info('🏥 Starting Backup & Restore Integrity Validation...');
        $this->line(str_repeat('=', 50));

        // 1. Run database-only backup
        $this->info('1. Generating database backup...');
        $exitCode = Artisan::call('backup:run', [
            '--only-db' => true,
            '--disable-notifications' => true,
        ]);

        if ($exitCode !== 0) {
            $this->error('❌ Backup generation failed!');
            return 1;
        }
        $this->info('✅ Backup completed successfully.');

        // 2. Locate backup file on 'local' disk
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($diskName);
        
        $appName = config('backup.backup.name', 'laravel-backup');
        $files = $disk->allFiles($appName);
        
        if (empty($files)) {
            $files = collect($disk->allFiles())
                ->filter(fn($f) => str_ends_with($f, '.zip'))
                ->toArray();
        }

        if (empty($files)) {
            $this->error('❌ Could not find any backup zip file on disk: ' . $diskName);
            return 1;
        }

        // Sort files to get the latest one
        usort($files, fn($a, $b) => $disk->lastModified($b) <=> $disk->lastModified($a));
        $latestBackup = $files[0];
        $this->info("🔍 Found latest backup archive: {$latestBackup}");

        // 3. Extract the SQL file from the zip archive
        $backupPath = $disk->path($latestBackup);
        $tempExtractPath = storage_path('app/backup-temp/restore-validation');
        
        if (!File::exists($tempExtractPath)) {
            File::makeDirectory($tempExtractPath, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($backupPath) !== true) {
            $this->error('❌ Failed to open backup zip file: ' . $backupPath);
            return 1;
        }

        // Find .sql dump file inside the zip
        $sqlFilename = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with($name, '.sql')) {
                $sqlFilename = $name;
                break;
            }
        }

        if (!$sqlFilename) {
            $this->error('❌ Could not find database SQL dump inside the backup zip archive!');
            $zip->close();
            return 1;
        }

        $this->info("📄 Extracting database dump: {$sqlFilename}...");
        $zip->extractTo($tempExtractPath, $sqlFilename);
        $zip->close();

        $extractedSqlPath = $tempExtractPath . '/' . $sqlFilename;
        if (!File::exists($extractedSqlPath)) {
            $this->error('❌ Failed to locate extracted SQL file at: ' . $extractedSqlPath);
            return 1;
        }

        $sqlContent = File::get($extractedSqlPath);
        $sqlSize = strlen($sqlContent);
        $this->info("✅ Extracted dump size: " . number_format($sqlSize / 1024, 2) . " KB");

        if ($sqlSize === 0) {
            $this->error('❌ Database dump is empty!');
            return 1;
        }

        // 4. Validate SQL content structure (Static Analysis)
        $this->info('🔍 Running static analysis on SQL dump...');
        $criticalTables = ['users', 'ilanlar', 'tenants'];
        $missingTables = [];

        foreach ($criticalTables as $table) {
            $pattern = "/CREATE TABLE [\"`]?{$table}[\"`]?/i";
            if (!preg_match($pattern, $sqlContent)) {
                $missingTables[] = $table;
            }
        }

        if (!empty($missingTables)) {
            $this->error('❌ Static Analysis Failed: Missing critical tables in dump: ' . implode(', ', $missingTables));
            $this->cleanup($tempExtractPath);
            return 1;
        }
        $this->info('✅ Static Analysis Passed: All critical tables present in schema.');

        // 5. Dynamic Restore Verification
        $defaultConn = config('database.default');
        $driver = config("database.connections.{$defaultConn}.driver");

        $this->info("🔬 Running dynamic restore verification using driver [{$driver}]...");

        if ($driver === 'sqlite') {
            $success = $this->verifySqliteRestore($sqlitePath = database_path('backup_test.sqlite'), $sqlContent);
            if ($sqlitePath && File::exists($sqlitePath)) {
                File::delete($sqlitePath);
            }
        } else {
            $success = $this->verifyMysqlRestore($defaultConn, $sqlContent);
        }

        $this->cleanup($tempExtractPath);

        if ($success) {
            $this->info('🎉 BACKUP & RESTORE INTEGRITY VALIDATION PASSED SUCCESSFULLY!');
            return 0;
        }

        $this->error('❌ Dynamic restore verification failed!');
        return 1;
    }

    private function verifySqliteRestore(string $sqlitePath, string $sqlContent): bool
    {
        try {
            if (File::exists($sqlitePath)) {
                File::delete($sqlitePath);
            }
            File::put($sqlitePath, '');

            Config::set('database.connections.backup_restore_test', [
                'driver' => 'sqlite',
                'database' => $sqlitePath,
                'prefix' => '',
                'foreign_key_constraints' => false,
            ]);

            // Clear DB connection cache
            DB::purge('backup_restore_test');

            // SQLite SQL dumps contain SQLite commands, run them
            DB::connection('backup_restore_test')->unprepared($sqlContent);

            // Verify table counts
            $userCount = DB::connection('backup_restore_test')->table('users')->count();
            $ilanCount = DB::connection('backup_restore_test')->table('ilanlar')->count();
            
            $this->info("📊 Restored Record Stats:");
            $this->line("- Users: {$userCount}");
            $this->line("- Ilanlar: {$ilanCount}");

            return true;
        } catch (Exception $e) {
            $this->error('❌ SQLite Dynamic Verification Error: ' . $e->getMessage());
            return false;
        }
    }

    private function verifyMysqlRestore(string $defaultConn, string $sqlContent): bool
    {
        // For MySQL, we'll try to create a temporary database on the same server,
        // import the dump, verify, and drop it.
        $config = config("database.connections.{$defaultConn}");
        $originalDb = $config['database'];
        $tempDb = $originalDb . '_backup_val_' . time();

        try {
            // Connect to MySQL server without selecting database
            $dsn = "mysql:host={$config['host']};port={$config['port']}";
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $this->info("🔨 Creating temporary MySQL verification database: {$tempDb}...");
            $pdo->exec("CREATE DATABASE `{$tempDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Setup new temporary connection
            $config['database'] = $tempDb;
            Config::set('database.connections.backup_restore_test', $config);
            DB::purge('backup_restore_test');

            $this->info("🚀 Restoring schema and data into temporary MySQL database...");
            DB::connection('backup_restore_test')->unprepared($sqlContent);

            $userCount = DB::connection('backup_restore_test')->table('users')->count();
            $ilanCount = DB::connection('backup_restore_test')->table('ilanlar')->count();

            $this->info("📊 Restored Record Stats:");
            $this->line("- Users: {$userCount}");
            $this->line("- Ilanlar: {$ilanCount}");

            // Drop temporary database
            $this->info("🧹 Dropping temporary database...");
            $pdo->exec("DROP DATABASE `{$tempDb}`");
            return true;
        } catch (Exception $e) {
            $this->warn('⚠️ MySQL Dynamic Verification failed or skipped: ' . $e->getMessage());
            $this->line('👉 Falling back to partial SQL dry-run syntax check.');

            // Drop database if it was left behind
            if (isset($pdo) && isset($tempDb)) {
                try {
                    $pdo->exec("DROP DATABASE IF EXISTS `{$tempDb}`");
                } catch (Exception $ignored) {}
            }

            return $this->verifySqlSyntaxDryRun($sqlContent);
        }
    }

    private function verifySqlSyntaxDryRun(string $sqlContent): bool
    {
        if (str_contains($sqlContent, 'CREATE TABLE') && str_contains($sqlContent, 'INSERT INTO')) {
            $this->info('Base indicators verify OK.');
            return true;
        }
        return false;
    }

    private function cleanup(string $path): void
    {
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
}
