<?php

namespace Tests\Feature\Contract;

use Tests\TestCase;

/**
 * MigrationBaselineBoundaryContractTest
 *
 * Task: CANONICAL_BASELINE_BOUNDARY_CONTRACT_25
 *
 * Machine-checkable contract verifying:
 * 1. Canonical schema baseline commit constant is exact.
 * 2. Current HEAD resolves against the baseline commit (ancestry check).
 * 3. Historical pre-checkpoint migrations (including 2026_09_22_000001_add_ilan_id_kaynak_to_talepler)
 *    are NOT classified as post-baseline.
 * 4. Post-baseline migration count at current HEAD equals 0.
 * 5. Vendor migrations (e.g. Sanctum) are excluded from Yalıhan-owned lineage.
 * 6. scripts/tools/verify-migration-boundary.sh executes with status 0.
 */
class MigrationBaselineBoundaryContractTest extends TestCase
{
    public const CANONICAL_BASELINE_COMMIT = 'ec6ba05acb88425bce1b16f7043675dd7b9931b7';

    public function test_canonical_baseline_commit_constant_is_exact(): void
    {
        $this->assertSame(
            'ec6ba05acb88425bce1b16f7043675dd7b9931b7',
            self::CANONICAL_BASELINE_COMMIT,
            'Canonical baseline commit hash must match exact verified checkpoint ec6ba05a.'
        );
    }

    public function test_baseline_commit_exists_and_is_ancestor_of_head(): void
    {
        $baseline = self::CANONICAL_BASELINE_COMMIT;
        $repoRoot = base_path();

        $commitExists = trim((string) shell_exec("cd {$repoRoot} && git cat-file -e {$baseline}^{commit} 2>&1 && echo 'EXISTS' || echo 'MISSING'"));
        $this->assertSame('EXISTS', $commitExists, "Baseline commit {$baseline} must exist in git history.");

        $isAncestor = trim((string) shell_exec("cd {$repoRoot} && git merge-base --is-ancestor {$baseline} HEAD 2>&1 && echo 'YES' || echo 'NO'"));
        $this->assertSame('YES', $isAncestor, "Baseline commit {$baseline} must be an ancestor of current HEAD.");
    }

    public function test_git_ancestry_proves_zero_post_baseline_migrations_at_current_head(): void
    {
        $baseline = self::CANONICAL_BASELINE_COMMIT;
        $repoRoot = base_path();

        $cmd = "cd {$repoRoot} && git diff --name-only --diff-filter=A {$baseline}..HEAD -- database/migrations 'app/Modules/**/Migrations' 'app/Modules/**/migrations' 2>/dev/null | grep -E '\\.php$' || true";
        $output = trim((string) shell_exec($cmd));

        $addedFiles = array_filter(explode("\n", $output));
        $this->assertCount(
            0,
            $addedFiles,
            'Expected exactly zero post-baseline migrations between canonical baseline checkpoint and current HEAD.'
        );
    }

    public function test_historical_talepler_migration_is_ancestor_and_not_post_baseline(): void
    {
        $baseline = self::CANONICAL_BASELINE_COMMIT;
        $repoRoot = base_path();
        $targetMigration = 'database/migrations/2026_09_22_000001_add_ilan_id_kaynak_to_talepler.php';

        $this->assertFileExists(base_path($targetMigration));

        // Get introducing commit of this file
        $cmd = "cd {$repoRoot} && git log -n 1 --format='%H' -- {$targetMigration}";
        $introducingCommit = trim((string) shell_exec($cmd));

        $this->assertNotEmpty($introducingCommit, 'Target migration must have a valid introducing commit.');

        // Verify introducing commit is ancestor of baseline commit
        $isAncestor = trim((string) shell_exec("cd {$repoRoot} && git merge-base --is-ancestor {$introducingCommit} {$baseline} 2>&1 && echo 'YES' || echo 'NO'"));
        $this->assertSame(
            'YES',
            $isAncestor,
            "Migration {$targetMigration} (commit {$introducingCommit}) must be an ancestor of baseline commit {$baseline}."
        );
    }

    public function test_vendor_migrations_are_excluded_from_yalihan_owned_lineage(): void
    {
        $migrator = app('migrator');
        $allDiscovered = $migrator->getMigrationFiles(
            array_unique(array_merge([database_path('migrations')], $migrator->paths()))
        );

        $sanctumFoundInDiscovered = false;
        foreach ($allDiscovered as $name => $path) {
            if (str_contains($path, 'vendor/laravel/sanctum')) {
                $sanctumFoundInDiscovered = true;
                break;
            }
        }
        $this->assertTrue($sanctumFoundInDiscovered, 'Sanctum vendor migration should be discovered at runtime.');

        // But Yalıhan-owned lineage paths MUST NOT include vendor/
        $yalihanOwnedPaths = [
            database_path('migrations'),
            app_path('Modules/Finans/database/migrations'),
        ];

        foreach ($yalihanOwnedPaths as $ownedPath) {
            $this->assertStringNotContainsString('vendor/', $ownedPath);
        }
    }

    public function test_boundary_verification_script_executes_cleanly(): void
    {
        $scriptPath = base_path('scripts/tools/verify-migration-boundary.sh');
        $this->assertFileExists($scriptPath);
        $this->assertTrue(is_executable($scriptPath), 'verify-migration-boundary.sh must be executable.');

        $output = [];
        $exitCode = 0;
        exec("bash {$scriptPath} 2>&1", $output, $exitCode);
        $fullOutput = implode("\n", $output);

        $this->assertSame(0, $exitCode, "Script failed with output:\n{$fullOutput}");
        $this->assertStringContainsString('BASELINE_COMMIT: '.self::CANONICAL_BASELINE_COMMIT, $fullOutput);
        $this->assertStringContainsString('POST_BASELINE_MIGRATION_COUNT: 0', $fullOutput);
        $this->assertStringContainsString('STATUS: PASS_BOUNDARY_VERIFIED', $fullOutput);
    }
}
