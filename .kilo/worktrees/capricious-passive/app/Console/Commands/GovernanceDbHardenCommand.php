<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GovernanceDbHardenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gov:db:harden {--user= : The app database user to restrict (e.g. app_user)}
                                          {--db= : The database name (e.g. yalihanai_v2_production)}
                                          {--apply : Attempt to apply directly via current DB connection (Requires root/admin privileges)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SAB v3 DLP: Generates or applies SQL to revoke destructive privileges (DROP, TRUNCATE) from the application DB user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = $this->option('user') ?: config('governance.db_harden.default_user', 'root');
        $db = $this->option('db') ?: config('governance.db_harden.default_database', 'yalihanai_v2_production');

        $this->info("🛡️ SAB v3 DLP: Database Privilege Hardening");
        $this->line("Target Database : <comment>{$db}</comment>");
        $this->line("Target User     : <comment>{$user}</comment>\n");

        if ($user === 'root' && config('app.env') === 'production') {
            $this->warn("WARNING: You are using 'root' as the application database user in production!");
            $this->warn("It is highly recommended to use a dedicated, restricted user instead.");
        }

        $sqlCommands = [
            "REVOKE DROP ON `{$db}`.* FROM '{$user}'@'localhost';",
            "REVOKE DROP ON `{$db}`.* FROM '{$user}'@'%';",
            // Note: TRUNCATE is technically a DROP privilege conceptually in MySQL,
            // but explicitly revoking DROP covers TRUNCATE for tables.
            "FLUSH PRIVILEGES;"
        ];

        if ($this->option('apply')) {
            if (!$this->confirm('Are you sure you want to attempt applying these restrictions now? (Requires DBA privileges)')) {
                return;
            }

            try {
                foreach ($sqlCommands as $sql) {
                    \Illuminate\Support\Facades\DB::statement($sql);
                }
                $this->info("\n✅ Privileges successfully revoked via direct connection!");
            } catch (\Exception $e) {
                $this->error("\n❌ Failed to apply. You probably don't have Grant/Revoke (root) privileges on the current connection.");
                $this->error("Error: " . $e->getMessage());
                $this->line("\nPlease run the following SQL commands manually as root/DBA:");
                $this->displaySql($sqlCommands);
            }
        } else {
            $this->info("To harden the database, please execute the following SQL commands as root/DBA:\n");
            $this->displaySql($sqlCommands);
            $this->line("\nOr run this command with the <info>--apply</info> flag if your DB_USERNAME has DBA rights.");
        }
    }

    private function displaySql(array $commands): void
    {
        foreach ($commands as $sql) {
            $this->line("<fg=cyan>{$sql}</>");
        }
    }
}
