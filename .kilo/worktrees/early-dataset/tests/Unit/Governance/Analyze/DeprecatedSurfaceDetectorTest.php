<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze;

use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\Detectors\DeprecatedSurfaceDetector;
use PHPUnit\Framework\TestCase;

/**
 * Pack-T3: DeprecatedSurfaceDetector golden cases + false-positive guards.
 *
 * Fixture approach: temp filesystem, no DB, no Laravel boot.
 * Pattern: write fixture files, run detect(), assert findings.
 */
class DeprecatedSurfaceDetectorTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir() . '/h7-dep-fixture-' . uniqid('', true);
        // Base dirs the detector scans
        mkdir($this->fixtureRoot . '/app/Services', 0777, true);
        mkdir($this->fixtureRoot . '/app/Http/Controllers', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->fixtureRoot);
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function writeService(string $filename, array $lines): void
    {
        file_put_contents(
            $this->fixtureRoot . '/app/Services/' . $filename,
            implode("\n", $lines) . "\n"
        );
    }

    private function writeController(string $filename, array $lines): void
    {
        file_put_contents(
            $this->fixtureRoot . '/app/Http/Controllers/' . $filename,
            implode("\n", $lines) . "\n"
        );
    }

    /** @return list<\App\Support\Governance\Analyze\Finding> */
    private function detect(): array
    {
        $detector = new DeprecatedSurfaceDetector();
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
    // Golden cases — true positives
    // ------------------------------------------------------------------

    public function test_detects_deprecated_class_with_one_caller(): void
    {
        $this->writeService('OldService.php', [
            '<?php',
            'namespace App\\Services;',
            '/** @deprecated Use NewService instead. */',
            'class OldService {',
            '    public function run(): void {}',
            '}',
        ]);

        $this->writeController('SomeController.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'use App\\Services\\OldService;',
            'class SomeController {',
            '    public function index(OldService $svc): void {}',
            '}',
        ]);

        $findings = $this->detect();

        $this->assertCount(1, $findings);
        $this->assertStringContainsString('OldService', $findings[0]->title);
    }

    public function test_finding_lists_all_callers_as_evidence(): void
    {
        $this->writeService('LegacyService.php', [
            '<?php',
            'namespace App\\Services;',
            '/** @deprecated */',
            'class LegacyService {}',
        ]);

        // Two callers
        $this->writeController('Alpha.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'use App\\Services\\LegacyService;',
            'class Alpha {}',
        ]);
        $this->writeController('Beta.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'use App\\Services\\LegacyService;',
            'class Beta {}',
        ]);

        $findings = $this->detect();

        $this->assertCount(1, $findings);
        // Evidence: 1 deprecated declaration + 2 callers = 3
        $this->assertCount(3, $findings[0]->evidence);
    }

    public function test_finding_caller_count_in_title(): void
    {
        $this->writeService('StaleService.php', [
            '<?php',
            'namespace App\\Services;',
            '/** @deprecated use StaleServiceV2 */',
            'class StaleService {}',
        ]);

        $this->writeController('Caller.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'use App\\Services\\StaleService;',
            'class Caller {}',
        ]);

        $this->assertStringContainsString('1', $this->detect()[0]->title);
    }

    public function test_finding_summary_contains_fqcn(): void
    {
        $this->writeService('DepServ.php', [
            '<?php',
            'namespace App\\Services;',
            '/** @deprecated */',
            'class DepServ {}',
        ]);

        $this->writeController('User.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'use App\\Services\\DepServ;',
            'class User {}',
        ]);

        $findings = $this->detect();
        $this->assertStringContainsString('App\\Services\\DepServ', $findings[0]->summary);
    }

    public function test_finding_slug_is_deprecated(): void
    {
        $this->writeService('DepSlug.php', [
            '<?php',
            'namespace App\\Services;',
            '/** @deprecated */',
            'class DepSlug {}',
        ]);

        $this->writeController('SlugCaller.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'use App\\Services\\DepSlug;',
            'class SlugCaller {}',
        ]);

        $arr = $this->detect()[0]->toArray();
        $this->assertSame('deprecated', $arr['detector']);
    }

    public function test_advisory_only_never_autofix(): void
    {
        $this->writeService('DepAuto.php', [
            '<?php',
            'namespace App\\Services;',
            '/** @deprecated */',
            'class DepAuto {}',
        ]);

        $this->writeController('AutoCaller.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'use App\\Services\\DepAuto;',
            'class AutoCaller {}',
        ]);

        $arr = $this->detect()[0]->toArray();
        $this->assertFalse($arr['autofix']);
    }

    // ------------------------------------------------------------------
    // False-positive guards
    // ------------------------------------------------------------------

    public function test_false_positive_deprecated_class_with_no_callers(): void
    {
        // Marked @deprecated but nobody imports it — no finding expected
        $this->writeService('UnusedDeprecated.php', [
            '<?php',
            'namespace App\\Services;',
            '/** @deprecated */',
            'class UnusedDeprecated {}',
        ]);

        $this->assertCount(0, $this->detect(), 'Deprecated class with no callers must not produce a finding');
    }

    public function test_false_positive_active_class_without_deprecated_tag(): void
    {
        // A class without @deprecated should never fire, even if imported
        $this->writeService('ActiveService.php', [
            '<?php',
            'namespace App\\Services;',
            'class ActiveService {}',
        ]);

        $this->writeController('ActiveCaller.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'use App\\Services\\ActiveService;',
            'class ActiveCaller {}',
        ]);

        $this->assertCount(0, $this->detect(), 'Non-deprecated class must not be flagged regardless of caller count');
    }

    public function test_false_positive_deprecated_in_comment_not_docblock(): void
    {
        // A plain comment (not a docblock) that doesn't contain the @deprecated token
        // must not trigger the detector — only @deprecated in actual docblocks matters.
        // This fixture has NO @deprecated token anywhere.
        $this->writeService('CommentDeprecated.php', [
            '<?php',
            'namespace App\\Services;',
            '// This was an old legacy style, scheduled for removal.',
            'class CommentDeprecated {}',
        ]);

        $this->writeController('CDepCaller.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'use App\\Services\\CommentDeprecated;',
            'class CDepCaller {}',
        ]);

        $this->assertCount(0, $this->detect(), 'Class without @deprecated token must not be flagged');
    }

    public function test_false_positive_self_import_not_counted_as_caller(): void
    {
        // A deprecated class that `use`s itself (edge case) must not count as caller
        $this->writeService('SelfRef.php', [
            '<?php',
            'namespace App\\Services;',
            'use App\\Services\\SelfRef;',
            '/** @deprecated */',
            'class SelfRef {}',
        ]);

        $this->assertCount(0, $this->detect(), 'Self-import must not count as an active caller');
    }

    public function test_false_positive_governance_analyze_namespace_skipped(): void
    {
        // Even if something in the Governance/Analyze/ directory imports the deprecated class,
        // it should be excluded from caller count
        mkdir($this->fixtureRoot . '/app/Support/Governance/Analyze', 0777, true);
        file_put_contents(
            $this->fixtureRoot . '/app/Support/Governance/Analyze/InternalRef.php',
            implode("\n", [
                '<?php',
                'namespace App\\Support\\Governance\\Analyze;',
                'use App\\Services\\GovernanceDeprecated;',
                'class InternalRef {}',
            ]) . "\n"
        );

        $this->writeService('GovernanceDeprecated.php', [
            '<?php',
            'namespace App\\Services;',
            '/** @deprecated */',
            'class GovernanceDeprecated {}',
        ]);

        $this->assertCount(0, $this->detect(), 'Governance/Analyze/ callers must not count as active callers');
    }
}
