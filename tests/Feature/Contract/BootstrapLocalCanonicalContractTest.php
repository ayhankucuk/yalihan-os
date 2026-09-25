<?php

declare(strict_types=1);

namespace Tests\Feature\Contract;

use Tests\TestCase;

/**
 * BootstrapLocalCanonicalContractTest
 *
 * Validates the safety, authority, lineage, secret, and invariance
 * contracts of the canonical reproducible bootstrap runner.
 *
 * @see scripts/tools/bootstrap-local-canonical.sh
 */
class BootstrapLocalCanonicalContractTest extends TestCase
{
    private string $scriptPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scriptPath = base_path('scripts/tools/bootstrap-local-canonical.sh');
        $this->assertFileExists($this->scriptPath, 'bootstrap-local-canonical.sh must exist.');
    }

    /**
     * 1. Rejects missing target-db argument.
     */
    public function test_missing_target_db_argument_is_rejected(): void
    {
        $output = [];
        $returnCode = 0;
        exec("ADMIN_LOCAL_PASSWORD=test_secret {$this->scriptPath} 2>&1", $output, $returnCode);

        $this->assertNotEquals(0, $returnCode, 'Runner must fail when --target-db is missing.');
        $this->assertStringContainsString('--target-db=<db_name> is required', implode("\n", $output));
    }

    /**
     * 2. Rejects forbidden and production database names.
     */
    public function test_forbidden_database_names_are_rejected(): void
    {
        $forbiddenNames = [
            'yalihanai_v2_production',
            'yalihanai_production',
            'production',
            'prod',
            'master',
        ];

        foreach ($forbiddenNames as $name) {
            $output = [];
            $returnCode = 0;
            exec("ADMIN_LOCAL_PASSWORD=test_secret {$this->scriptPath} --target-db={$name} 2>&1", $output, $returnCode);

            $this->assertNotEquals(0, $returnCode, "Runner must reject forbidden database name: {$name}");
            $this->assertStringContainsString("Target database '{$name}' is forbidden", implode("\n", $output));
        }
    }

    /**
     * 3. Rejects the current canonical working database name (no in-place overwrite).
     */
    public function test_current_canonical_db_name_is_rejected(): void
    {
        $output = [];
        $returnCode = 0;
        exec("ADMIN_LOCAL_PASSWORD=test_secret {$this->scriptPath} --target-db=yalihanai_local_canonical 2>&1", $output, $returnCode);

        $this->assertNotEquals(0, $returnCode, 'Runner must reject modifying the canonical working database in place.');
        $this->assertStringContainsString("Target database 'yalihanai_local_canonical' is forbidden", implode("\n", $output));
    }

    /**
     * 4. Rejects missing ADMIN_LOCAL_PASSWORD environment variable.
     */
    public function test_missing_admin_local_password_is_rejected(): void
    {
        $output = [];
        $returnCode = 0;
        $path = escapeshellarg(getenv('PATH') ?: '');
        $home = escapeshellarg(getenv('HOME') ?: '');
        exec("env -i PATH={$path} HOME={$home} {$this->scriptPath} --target-db=yalihanai_test_tmp 2>&1", $output, $returnCode);

        $this->assertNotEquals(0, $returnCode, 'Runner must fail closed when ADMIN_LOCAL_PASSWORD is not set.');
        $this->assertStringContainsString('ADMIN_LOCAL_PASSWORD environment variable is missing or blank', implode("\n", $output));
    }

    /**
     * 5. Rejects blank or whitespace-only ADMIN_LOCAL_PASSWORD.
     */
    public function test_blank_admin_local_password_is_rejected(): void
    {
        $output = [];
        $returnCode = 0;
        $path = escapeshellarg(getenv('PATH') ?: '');
        $home = escapeshellarg(getenv('HOME') ?: '');
        exec("env -i PATH={$path} HOME={$home} ADMIN_LOCAL_PASSWORD=\"   \" {$this->scriptPath} --target-db=yalihanai_test_tmp 2>&1", $output, $returnCode);

        $this->assertNotEquals(0, $returnCode, 'Runner must fail closed when ADMIN_LOCAL_PASSWORD is whitespace.');
        $this->assertStringContainsString('ADMIN_LOCAL_PASSWORD environment variable is missing or blank', implode("\n", $output));
    }

    /**
     * 6. Unbounded 'php artisan migrate' is absent from script.
     */
    public function test_unbounded_php_artisan_migrate_is_absent(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertNotFalse($content);

        // Strip comments
        $lines = explode("\n", $content);
        $codeLines = array_filter($lines, fn($l) => !str_starts_with(trim($l), '#'));
        $code = implode("\n", $codeLines);

        $this->assertStringNotContainsString('php artisan migrate', $code);
    }

    /**
     * 7. Automatic 'php artisan migrate --path' is absent from Bootstrap V1.
     */
    public function test_migrate_with_path_is_absent_in_v1(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertNotFalse($content);

        $this->assertStringNotContainsString(
            'php artisan migrate --path',
            $content,
            'Bootstrap V1 must not automatically execute post-baseline migrations.'
        );
    }

    /**
     * 8. Replay of historical pre-checkpoint migrations is impossible (fails closed if post-baseline count != 0).
     */
    public function test_post_baseline_migration_presence_fails_closed(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertNotFalse($content);

        $this->assertStringContainsString(
            'BLOCKED_POST_BASELINE_MIGRATIONS_REQUIRE_EXECUTION_PLAN',
            $content,
            'Runner must fail closed with BLOCKED_POST_BASELINE_MIGRATIONS_REQUIRE_EXECUTION_PLAN if post-baseline count > 0.'
        );
    }

    /**
     * 9. Excludes local-dev and demo seeders (Danisman, Musteri, BodrumPoi).
     */
    public function test_local_dev_and_demo_seeders_are_excluded(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertNotFalse($content);

        $this->assertStringNotContainsString('DanismanSeeder', $content);
        $this->assertStringNotContainsString('MusteriSeeder', $content);
        $this->assertStringNotContainsString('BodrumPoiSeeder', $content);
        $this->assertStringNotContainsString('OzellikKategoriSeeder', $content);
        $this->assertStringNotContainsString('PropertyHubOzelliklerSeeder', $content);
    }

    /**
     * 10. Canonical seeder order is strictly deterministic and contains all 12 canonical seeders.
     */
    public function test_canonical_seeder_order_is_deterministic(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertNotFalse($content);

        $expectedSeeders = [
            'TenantBaselineSeeder',
            'RoleSeeder',
            'TurkiyeLocationSeeder',
            'IlanKategoriSeeder',
            'YayinTipiSeeder',
            'KategoriYayinTipiPivotSeeder',
            'FeatureAssignmentSeeder',
            'ArsaIsyeriFeatureAssignmentSeeder',
            'CategoryFeatureMatrixSeeder',
            'SmartFormsCanonicalSeeder',
            'ExpenseItemSeeder',
            'AdminUserSeeder',
        ];

        $lastPos = -1;
        foreach ($expectedSeeders as $seeder) {
            $pos = strpos($content, $seeder);
            $this->assertNotFalse($pos, "Runner must include canonical seeder: {$seeder}");
            $this->assertGreaterThan($lastPos, $pos, "Canonical seeder {$seeder} must follow previous seeder in dependency order.");
            $lastPos = $pos;
        }
    }

    /**
     * 11. Secrets are never printed, logged, or written to .env.
     */
    public function test_credential_is_never_printed_or_persisted(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertNotFalse($content);

        $this->assertStringNotContainsString('echo $ADMIN_LOCAL_PASSWORD', $content);
        $this->assertStringNotContainsString('echo "${ADMIN_LOCAL_PASSWORD}"', $content);
        $this->assertStringNotContainsString('--password=$ADMIN_LOCAL_PASSWORD', $content);
        $this->assertStringNotContainsString('>> .env', $content);
        $this->assertStringNotContainsString('> .env', $content);
    }

    /**
     * 12. Remote database host is rejected.
     */
    public function test_remote_db_host_is_rejected(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertNotFalse($content);

        $this->assertStringContainsString('Database host', $content);
        $this->assertStringContainsString('is not local', $content);
    }

    /**
     * 13. Populated database target is rejected without force.
     */
    public function test_populated_target_database_is_rejected(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertNotFalse($content);

        $this->assertStringContainsString('Bootstrap V1 requires a NEW or EMPTY database', $content);
        $this->assertStringNotContainsString('--force-rebuild', $content);
    }

    /**
     * 14. Structural baseline invariants are verified.
     */
    public function test_structural_baseline_invariants_are_verified(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertNotFalse($content);

        $this->assertStringContainsString('users', $content);
        $this->assertStringContainsString('komisyonlar', $content);
        $this->assertStringContainsString('leads', $content);
        $this->assertStringContainsString('talepler', $content);
        $this->assertStringContainsString('ilan_fotograflari', $content);
        $this->assertStringContainsString('property_reservations', $content);
        $this->assertStringContainsString('emlak_projeleri', $content);
        $this->assertStringContainsString('projeler', $content);
    }

    /**
     * 15. BOOTSTRAP_VERIFIED status is emitted only after all invariants pass.
     */
    public function test_bootstrap_verified_status_is_emitted(): void
    {
        $content = file_get_contents($this->scriptPath);
        $this->assertNotFalse($content);

        $this->assertStringContainsString('STATUS: BOOTSTRAP_VERIFIED', $content);
    }
}
