<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze;

use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\Detectors\OrphanReferenceDetector;
use PHPUnit\Framework\TestCase;

/**
 * Pack-T3: OrphanReferenceDetector golden cases + false-positive guards.
 *
 * Fixture approach: temp filesystem, no DB, no Laravel boot.
 * Fixture layout mirrors the real scan targets:
 *   {root}/app/Services/   — service classes under detection
 *   {root}/app/Http/        — caller files that may import services
 */
class OrphanReferenceDetectorTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir() . '/h7-orphan-fixture-' . uniqid('', true);
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
        $detector = new OrphanReferenceDetector();
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

    public function test_detects_service_class_with_no_callers(): void
    {
        $this->writeService('DeadService.php', [
            '<?php',
            'namespace App\\Services;',
            'class DeadService {',
            '    public function run(): void {}',
            '}',
        ]);

        $findings = $this->detect();

        $this->assertCount(1, $findings);
        $this->assertStringContainsString('DeadService', $findings[0]->title);
    }

    public function test_finding_summary_includes_fqcn_and_file_path(): void
    {
        $this->writeService('OrphanSvc.php', [
            '<?php',
            'namespace App\\Services;',
            'class OrphanSvc {}',
        ]);

        $findings = $this->detect();
        $this->assertCount(1, $findings);
        $this->assertStringContainsString('App\\Services\\OrphanSvc', $findings[0]->summary);
    }

    public function test_finding_slug_is_orphan(): void
    {
        $this->writeService('SlugSvc.php', [
            '<?php',
            'namespace App\\Services;',
            'class SlugSvc {}',
        ]);

        $arr = $this->detect()[0]->toArray();
        $this->assertSame('orphan', $arr['detector']);
    }

    public function test_advisory_only_never_autofix(): void
    {
        $this->writeService('AutoSvc.php', [
            '<?php',
            'namespace App\\Services;',
            'class AutoSvc {}',
        ]);

        $arr = $this->detect()[0]->toArray();
        $this->assertFalse($arr['autofix']);
    }

    public function test_multiple_orphan_services_produce_multiple_findings(): void
    {
        $this->writeService('OrphanA.php', [
            '<?php',
            'namespace App\\Services;',
            'class OrphanA {}',
        ]);
        $this->writeService('OrphanB.php', [
            '<?php',
            'namespace App\\Services;',
            'class OrphanB {}',
        ]);

        $findings = $this->detect();

        $this->assertGreaterThanOrEqual(2, count($findings));
        $titles = implode('|', array_map(fn ($f) => $f->title, $findings));
        $this->assertStringContainsString('OrphanA', $titles);
        $this->assertStringContainsString('OrphanB', $titles);
    }

    // ------------------------------------------------------------------
    // False-positive guards
    // ------------------------------------------------------------------

    public function test_false_positive_service_with_one_caller_not_flagged(): void
    {
        $this->writeService('UsedService.php', [
            '<?php',
            'namespace App\\Services;',
            'class UsedService {}',
        ]);

        $this->writeController('ActiveController.php', [
            '<?php',
            'namespace App\\Http\\Controllers;',
            'use App\\Services\\UsedService;',
            'class ActiveController {',
            '    public function __construct(UsedService $svc) {}',
            '}',
        ]);

        $this->assertCount(0, $this->detect(), 'Service with at least one caller must not be flagged');
    }

    public function test_false_positive_abstract_class_not_flagged(): void
    {
        // Abstract classes are not concrete services — should be excluded
        $this->writeService('AbstractBase.php', [
            '<?php',
            'namespace App\\Services;',
            'abstract class AbstractBase {',
            '    abstract public function run(): void;',
            '}',
        ]);

        $this->assertCount(0, $this->detect(), 'Abstract classes must not be flagged as orphans');
    }

    public function test_false_positive_interface_not_flagged(): void
    {
        $this->writeService('ServiceInterface.php', [
            '<?php',
            'namespace App\\Services;',
            'interface ServiceInterface {',
            '    public function run(): void;',
            '}',
        ]);

        $this->assertCount(0, $this->detect(), 'Interfaces must not be flagged as orphans');
    }

    public function test_false_positive_trait_not_flagged(): void
    {
        $this->writeService('HasCommonLogic.php', [
            '<?php',
            'namespace App\\Services;',
            'trait HasCommonLogic {',
            '    public function common(): void {}',
            '}',
        ]);

        $this->assertCount(0, $this->detect(), 'Traits must not be flagged as orphans');
    }

    public function test_false_positive_suffix_naming_conventions_not_flagged(): void
    {
        // Classes ending in Interface, Contract, Abstract are excluded by convention
        foreach (['MyInterface', 'MyContract', 'MyAbstract'] as $name) {
            $this->writeService("{$name}.php", [
                '<?php',
                'namespace App\\Services;',
                "class {$name} {}",
            ]);
        }

        $this->assertCount(0, $this->detect(), 'Convention-named abstract/interface/contract classes must not be flagged');
    }

    public function test_false_positive_zero_services_produces_zero_findings(): void
    {
        // Empty Services dir — no files at all
        $this->assertCount(0, $this->detect());
    }
}
