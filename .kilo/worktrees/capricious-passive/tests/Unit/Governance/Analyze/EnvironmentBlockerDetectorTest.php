<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze;

use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\Detectors\EnvironmentBlockerDetector;
use PHPUnit\Framework\TestCase;

/**
 * Pack-T4: EnvironmentBlockerDetector golden cases + false-positive guards.
 *
 * Fixture approach: temp filesystem, no DB, no Laravel boot.
 * Scan root: {fixtureRoot}/app/  (mirrors real layout)
 * Outside-app paths: {fixtureRoot}/bootstrap/ etc. — naturally excluded.
 */
class EnvironmentBlockerDetectorTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir() . '/h7-env-fixture-' . uniqid('', true);
        mkdir($this->fixtureRoot . '/app/Services', 0777, true);
        mkdir($this->fixtureRoot . '/app/Http/Controllers', 0777, true);
        mkdir($this->fixtureRoot . '/bootstrap', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->fixtureRoot);
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function writeApp(string $relPath, array $lines): void
    {
        $abs = $this->fixtureRoot . '/app/' . $relPath;
        $dir = dirname($abs);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($abs, implode("\n", $lines) . "\n");
    }

    private function writeOutside(string $relPath, array $lines): void
    {
        $abs = $this->fixtureRoot . '/' . $relPath;
        $dir = dirname($abs);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($abs, implode("\n", $lines) . "\n");
    }

    /** @return list<\App\Support\Governance\Analyze\Finding> */
    private function detect(): array
    {
        $detector = new EnvironmentBlockerDetector();
        $context = new AnalysisContext(repoRoot: $this->fixtureRoot);

        return $detector->detect($context);
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    // ------------------------------------------------------------------
    // T4.1 — Golden case: env() outside config/ is detected
    // ------------------------------------------------------------------

    public function test_detects_env_call_in_service_class(): void
    {
        $this->writeApp('Services/PaymentService.php', [
            '<?php',
            'namespace App\\Services;',
            'class PaymentService {',
            "    public function key(): string { return env('STRIPE_KEY'); }",
            '}',
        ]);

        $findings = $this->detect();

        $this->assertCount(1, $findings);
        $this->assertStringContainsStringIgnoringCase('env(', strtolower($findings[0]->title) . strtolower($findings[0]->summary));
    }

    // ------------------------------------------------------------------
    // T4.2 — Golden case: multiple env() lines → single finding, multiple evidence
    // ------------------------------------------------------------------

    public function test_multiple_env_calls_in_one_file_produce_single_finding_with_multiple_evidence(): void
    {
        $this->writeApp('Services/MultiEnvService.php', [
            '<?php',
            'namespace App\\Services;',
            'class MultiEnvService {',
            "    public function a(): string { return env('KEY_A'); }",
            "    public function b(): string { return env('KEY_B'); }",
            "    public function c(): string { return env('KEY_C'); }",
            '}',
        ]);

        $findings = $this->detect();

        $this->assertCount(1, $findings);
        $this->assertCount(3, $findings[0]->evidence);
    }

    // ------------------------------------------------------------------
    // T4.3 — Golden case: multiple files with env() → multiple findings
    // ------------------------------------------------------------------

    public function test_multiple_files_with_env_produce_multiple_findings(): void
    {
        $this->writeApp('Services/ServiceA.php', [
            '<?php',
            'namespace App\\Services;',
            'class ServiceA {',
            "    public function a(): string { return env('A'); }",
            '}',
        ]);

        $this->writeApp('Http/Controllers/CtrlB.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'class CtrlB {',
            "    public function b(): string { return env('B'); }",
            '}',
        ]);

        $findings = $this->detect();

        $this->assertGreaterThanOrEqual(2, count($findings));
        $titles = implode('|', array_map(fn ($f) => $f->title, $findings));
        $this->assertStringContainsString('ServiceA', $titles);
        $this->assertStringContainsString('CtrlB', $titles);
    }

    // ------------------------------------------------------------------
    // T4.4 — Golden case: slug and autofix contract
    // ------------------------------------------------------------------

    public function test_finding_slug_is_env_blocker_and_autofix_is_false(): void
    {
        $this->writeApp('Services/SlugTestSvc.php', [
            '<?php',
            'namespace App\\Services;',
            'class SlugTestSvc {',
            "    public function k(): string { return env('K'); }",
            '}',
        ]);

        $arr = $this->detect()[0]->toArray();

        $this->assertSame('env-blocker', $arr['detector']);
        $this->assertFalse($arr['autofix']);
    }

    // ------------------------------------------------------------------
    // T4.5 — FP guard: files outside app/ are not scanned
    // ------------------------------------------------------------------

    public function test_false_positive_env_call_outside_app_dir_not_flagged(): void
    {
        // bootstrap/helpers.php is at project root level, NOT inside app/
        // The detector only scans app/ — this file must never produce a finding.
        $this->writeOutside('bootstrap/helpers.php', [
            '<?php',
            "return env('APP_KEY');",
        ]);

        $this->assertCount(0, $this->detect(), 'Files outside app/ must not be flagged');
    }

    // ------------------------------------------------------------------
    // T4.6 — FP guard: comment-only lines with env() are skipped
    // ------------------------------------------------------------------

    public function test_false_positive_comment_line_env_not_flagged(): void
    {
        $this->writeApp('Services/CommentOnly.php', [
            '<?php',
            'namespace App\\Services;',
            'class CommentOnly {',
            "    // Old: \$key = env('STRIPE_KEY'); — do not use",
            '    public function key(): string { return config(\'services.stripe.key\'); }',
            '}',
        ]);

        $this->assertCount(0, $this->detect(), 'Comment-only lines with env() must not be flagged');
    }

    // ------------------------------------------------------------------
    // T4.7 — FP guard: file using config() instead of env() is not flagged
    // ------------------------------------------------------------------

    public function test_false_positive_config_only_service_not_flagged(): void
    {
        $this->writeApp('Services/ConfigOnlyService.php', [
            '<?php',
            'namespace App\\Services;',
            'class ConfigOnlyService {',
            "    public function key(): string { return config('services.stripe.key'); }",
            '}',
        ]);

        $this->assertCount(0, $this->detect(), 'Services using config() must not be flagged');
    }

    // ------------------------------------------------------------------
    // T4.8 — FP guard: empty app/Services/ produces zero findings
    // ------------------------------------------------------------------

    public function test_false_positive_empty_app_dir_produces_zero_findings(): void
    {
        // Only the dirs were created in setUp(), no files written
        $this->assertCount(0, $this->detect());
    }

    // ------------------------------------------------------------------
    // T4.9 — FP guard: Governance/Analyze namespace files are skipped
    // ------------------------------------------------------------------

    public function test_false_positive_governance_analyze_namespace_skipped(): void
    {
        // Place a file inside app/Support/Governance/Analyze/ — self-protect must fire
        $dir = $this->fixtureRoot . '/app/Support/Governance/Analyze';
        mkdir($dir, 0777, true);

        file_put_contents($dir . '/SomeHelper.php', implode("\n", [
            '<?php',
            'namespace App\\Support\\Governance\\Analyze;',
            'class SomeHelper {',
            "    public function read(): string { return env('APP_DEBUG'); }",
            '}',
        ]) . "\n");

        $this->assertCount(0, $this->detect(), 'Governance/Analyze namespace must be skipped (self-protect)');
    }
}
