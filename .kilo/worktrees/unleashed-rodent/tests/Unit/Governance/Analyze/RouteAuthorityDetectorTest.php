<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze;

use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\Detectors\RouteAuthorityDetector;
use App\Support\Governance\Analyze\Enums\FindingType;
use App\Support\Governance\Analyze\Enums\RiskLevel;
use PHPUnit\Framework\TestCase;

/**
 * Fixture-based tests for the route authority detector. No framework boot, no DB.
 */
class RouteAuthorityDetectorTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir() . '/h7-route-fixture-' . uniqid('', true);
        mkdir($this->fixtureRoot . '/routes', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->fixtureRoot);
        parent::tearDown();
    }

    public function test_detects_duplicate_fully_qualified_name(): void
    {
        $this->writeRoute('web.php', [
            "Route::get('/', 'Controller@index')->name('home.index');",
            "Route::get('/alt', 'Controller@altIndex')->name('home.index');",
        ]);

        $findings = $this->detect();

        $this->assertCount(1, $findings);
        $f = $findings[0];
        $this->assertSame(FindingType::AUTHORITY_CONFLICT, $f->tur);
        $this->assertSame(RiskLevel::HIGH, $f->risk);
        $this->assertStringContainsString('home.index', $f->title);
        $this->assertCount(2, $f->evidence);
    }

    public function test_ignores_leaf_only_names(): void
    {
        $this->writeRoute('web.php', [
            "Route::get('/a', 'A@show')->name('show');",
            "Route::get('/b', 'B@show')->name('show');",
        ]);

        $this->assertCount(0, $this->detect());
    }

    public function test_ignores_trailing_dot_group_prefixes(): void
    {
        $this->writeRoute('admin.php', [
            "Route::prefix('x')->name('api.')->group(function(){});",
            "Route::prefix('y')->name('api.')->group(function(){});",
        ]);

        $this->assertCount(0, $this->detect());
    }

    public function test_single_declaration_produces_no_finding(): void
    {
        $this->writeRoute('web.php', [
            "Route::get('/solo', 'S@index')->name('solo.page');",
        ]);

        $this->assertCount(0, $this->detect());
    }

    public function test_advisory_only_autofix_false(): void
    {
        $this->writeRoute('web.php', [
            "Route::get('/a', 'A@i')->name('dup.route');",
            "Route::get('/b', 'B@i')->name('dup.route');",
        ]);

        $arr = $this->detect()[0]->toArray();
        $this->assertFalse($arr['autofix']);
        $this->assertSame('open', $arr['durum']);
    }

    // ------------------------------------------------------------------
    // Pack-T2: Golden cases
    // ------------------------------------------------------------------

    public function test_detects_cross_file_name_collision(): void
    {
        $this->writeRoute('web.php', [
            "Route::get('/home', 'HomeController@index')->name('portal.home');",
        ]);
        $this->writeRoute('api.php', [
            "Route::get('/home', 'ApiHomeController@index')->name('portal.home');",
        ]);

        $findings = $this->detect();

        $this->assertCount(1, $findings);
        $this->assertStringContainsString('portal.home', $findings[0]->title);
        // Evidence must reference both files
        $files = array_map(fn ($e) => $e->file, $findings[0]->evidence);
        $this->assertCount(2, $files);
        $this->assertTrue(
            (bool) array_filter($files, fn ($f) => str_contains($f, 'web.php')),
            'Evidence must include web.php'
        );
        $this->assertTrue(
            (bool) array_filter($files, fn ($f) => str_contains($f, 'api.php')),
            'Evidence must include api.php'
        );
    }

    public function test_evidence_count_matches_declaration_count(): void
    {
        // Three declarations of the same name → single finding with 3 evidence items
        $this->writeRoute('web.php', [
            "Route::get('/a', 'A@i')->name('triple.name');",
            "Route::get('/b', 'B@i')->name('triple.name');",
            "Route::get('/c', 'C@i')->name('triple.name');",
        ]);

        $findings = $this->detect();

        $this->assertCount(1, $findings);
        $this->assertCount(3, $findings[0]->evidence, 'One evidence item per declaration');
    }

    public function test_multiple_distinct_conflicts_produce_multiple_findings(): void
    {
        $this->writeRoute('web.php', [
            "Route::get('/x1', 'A@i')->name('conflict.alpha');",
            "Route::get('/x2', 'B@i')->name('conflict.alpha');",
            "Route::get('/y1', 'C@i')->name('conflict.beta');",
            "Route::get('/y2', 'D@i')->name('conflict.beta');",
        ]);

        $findings = $this->detect();

        $this->assertCount(2, $findings);
        $names = array_map(fn ($f) => $f->title, $findings);
        $this->assertTrue(
            (bool) array_filter($names, fn ($t) => str_contains($t, 'conflict.alpha')),
            'Finding for conflict.alpha expected'
        );
        $this->assertTrue(
            (bool) array_filter($names, fn ($t) => str_contains($t, 'conflict.beta')),
            'Finding for conflict.beta expected'
        );
    }

    public function test_ten_unique_names_produce_zero_findings(): void
    {
        $lines = [];
        for ($i = 1; $i <= 10; $i++) {
            $lines[] = "Route::get('/r{$i}', 'C@i')->name('unique.route.{$i}');";
        }
        $this->writeRoute('web.php', $lines);

        $this->assertCount(0, $this->detect(), '10 unique fully-qualified names must produce 0 findings');
    }

    public function test_finding_id_is_deterministic_for_same_input(): void
    {
        $lines = [
            "Route::get('/a', 'A@i')->name('det.route');",
            "Route::get('/b', 'B@i')->name('det.route');",
        ];
        $this->writeRoute('web.php', $lines);

        $first  = $this->detect()[0]->id;
        // Re-detect without changing input
        $second = $this->detect()[0]->id;

        $this->assertSame($first, $second, 'Finding ID must be deterministic for identical input');
    }

    public function test_finding_contains_slug_routes(): void
    {
        $this->writeRoute('web.php', [
            "Route::get('/a', 'A@i')->name('slug.check');",
            "Route::get('/b', 'B@i')->name('slug.check');",
        ]);

        $arr = $this->detect()[0]->toArray();
        $this->assertSame('routes', $arr['detector']);
    }

    /** @return list<\App\Support\Governance\Analyze\Finding> */
    private function detect(): array
    {
        $detector = new RouteAuthorityDetector();
        $context = new AnalysisContext(repoRoot: $this->fixtureRoot);

        return $detector->detect($context);
    }

    /** @param list<string> $lines */
    private function writeRoute(string $filename, array $lines): void
    {
        $body = "<?php\n" . implode("\n", $lines) . "\n";
        file_put_contents($this->fixtureRoot . '/routes/' . $filename, $body);
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
