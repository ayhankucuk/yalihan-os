<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze;

use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\Detectors\Context7ForbiddenFieldDetector;
use App\Support\Governance\Analyze\Enums\FindingType;
use App\Support\Governance\Analyze\Enums\RiskLevel;
use PHPUnit\Framework\TestCase;

class Context7ForbiddenFieldDetectorTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir() . '/h7-c7-fixture-' . uniqid('', true);
        mkdir($this->fixtureRoot . '/app/Services', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->fixtureRoot);
        parent::tearDown();
    }

    public function test_detects_forbidden_is_active_in_where(): void
    {
        $this->writeService('Demo.php', [
            '<?php',
            'class Demo {',
            '    public function q() {',
            "        return Foo::query()->where('is_active', 1)->get();",
            '    }',
            '}',
        ]);

        $findings = $this->detect();

        $this->assertCount(1, $findings);
        $f = $findings[0];
        $this->assertSame(FindingType::CONTEXT7_VIOLATION, $f->tur);
        $this->assertSame(RiskLevel::HIGH, $f->risk);
        $this->assertStringContainsString('is_active', $f->title);
        $this->assertStringContainsString('aktiflik_durumu', $f->summary);
    }

    public function test_does_not_flag_canonical_fields(): void
    {
        $this->writeService('Canonical.php', [
            '<?php',
            'class Canonical {',
            '    public function q() {',
            "        return Foo::query()->where('yayin_durumu', 'Aktif')->get();",
            '    }',
            '}',
        ]);

        $this->assertCount(0, $this->detect());
    }

    public function test_skips_self_governance_analyze_namespace(): void
    {
        mkdir($this->fixtureRoot . '/app/Support/Governance/Analyze', 0777, true);
        file_put_contents(
            $this->fixtureRoot . '/app/Support/Governance/Analyze/SelfRef.php',
            "<?php\nclass X { public function f(){ return Foo::where('is_active', 1); } }\n"
        );

        $this->assertCount(0, $this->detect());
    }

    public function test_advisory_only_never_autofix(): void
    {
        $this->writeService('Flag.php', [
            '<?php',
            "Foo::query()->where('is_active', 1);",
        ]);

        $arr = $this->detect()[0]->toArray();
        $this->assertFalse($arr['autofix']);
    }

    // ------------------------------------------------------------------
    // Pack-T2: Golden cases — real-world patterns
    // ------------------------------------------------------------------

    public function test_detects_forbidden_field_in_create_array(): void
    {
        $this->writeService('Create.php', [
            '<?php',
            'class Creator {',
            '    public function store(array $data) {',
            "        return Ilan::create(['status' => 'active', 'baslik' => 'Test']);",
            '    }',
            '}',
        ]);

        $findings = $this->detect();

        $this->assertGreaterThanOrEqual(1, count($findings));
        $titles = array_map(fn ($f) => $f->title, $findings);
        $this->assertTrue(
            (bool) array_filter($titles, fn ($t) => str_contains(strtolower($t), 'stat')),
            'A finding for status in ->create() must be reported'
        );
    }

    public function test_detects_forbidden_field_in_update_array(): void
    {
        $this->writeService('Update.php', [
            '<?php',
            'class Updater {',
            '    public function update($model) {',
            "        \$model->update(['active' => 1, 'baslik' => 'Yeni']);",
            '    }',
            '}',
        ]);

        $findings = $this->detect();

        $this->assertGreaterThanOrEqual(1, count($findings));
        $titles = array_map(fn ($f) => $f->title, $findings);
        $this->assertTrue(
            (bool) array_filter($titles, fn ($t) => str_contains(strtolower($t), 'activ')),
            'A finding for active in ->update() must be reported'
        );
    }

    public function test_detects_featured_image_in_where(): void
    {
        $this->writeService('Featured.php', [
            '<?php',
            "Ilan::where('featured_image', '!=', null)->get();",
        ]);

        $findings = $this->detect();
        $this->assertGreaterThanOrEqual(1, count($findings));
        $titles = array_map(fn ($f) => $f->title, $findings);
        $this->assertTrue(
            (bool) array_filter($titles, fn ($t) => str_contains(strtolower($t), 'featured_image')),
            'Finding for featured_image must be present'
        );
        // Canonical replacement must be in summary
        $summaries = array_map(fn ($f) => $f->summary, $findings);
        $this->assertTrue(
            (bool) array_filter($summaries, fn ($s) => str_contains($s, 'kapak_resmi')),
            'Summary must suggest kapak_resmi as canonical replacement'
        );
    }

    public function test_multiple_forbidden_fields_in_one_file_produce_multiple_findings(): void
    {
        $this->writeService('Multi.php', [
            '<?php',
            'class Multi {',
            '    public function run() {',
            "        Ilan::where('is_active', 1);",
            "        Kisi::where('city', 'Bodrum');",
            '    }',
            '}',
        ]);

        $findings = $this->detect();

        // At least two distinct findings for two distinct forbidden fields
        $this->assertGreaterThanOrEqual(2, count($findings));
        $titles = implode('|', array_map(fn ($f) => $f->title, $findings));
        $this->assertTrue(str_contains(strtolower($titles), 'activ'), 'is_active finding expected');
        $this->assertTrue(str_contains(strtolower($titles), 'cit'), 'city finding expected');
    }

    public function test_finding_summary_includes_canonical_replacement(): void
    {
        $this->writeService('Canonical.php', [
            '<?php',
            "Ilan::where('is_active', 1);",
        ]);

        $findings = $this->detect();
        $this->assertCount(1, $findings);
        $this->assertStringContainsString(
            'aktiflik_durumu',
            $findings[0]->summary,
            'Summary must name the canonical replacement'
        );
    }

    public function test_finding_detector_slug_is_context7(): void
    {
        $this->writeService('Slug.php', [
            '<?php',
            "Foo::where('is_active', 1);",
        ]);

        $arr = $this->detect()[0]->toArray();
        $this->assertSame('context7', $arr['detector']);
    }

    // ------------------------------------------------------------------
    // Pack-T2: False-positive guard
    // ------------------------------------------------------------------

    public function test_false_positive_field_name_in_comment_only(): void
    {
        $this->writeService('CommentOnly.php', [
            '<?php',
            '// This used to use the status field but now uses yayin_durumu',
            '// is_active was also removed',
            'class Clean {',
            '    public function q() {',
            "        return Ilan::where('yayin_durumu', 'Aktif')->get();",
            '    }',
            '}',
        ]);

        // Comments alone must not produce findings
        $this->assertCount(0, $this->detect(), 'Field names in comments must not trigger findings');
    }

    public function test_false_positive_canonical_create_not_flagged(): void
    {
        $this->writeService('CanonicalCreate.php', [
            '<?php',
            'class Maker {',
            '    public function store() {',
            "        return Ilan::create(['yayin_durumu' => 'Aktif', 'aktiflik_durumu' => 1]);",
            '    }',
            '}',
        ]);

        $this->assertCount(0, $this->detect(), 'Canonical fields in ->create() must not be flagged');
    }

    public function test_false_positive_lat_lng_canonical_not_flagged(): void
    {
        $this->writeService('LatLng.php', [
            '<?php',
            "Lokasyon::where('lat', 36.85)->where('lng', 27.42)->first();",
        ]);

        $this->assertCount(0, $this->detect(), 'Canonical lat/lng fields must not be flagged');
    }

    /** @return list<\App\Support\Governance\Analyze\Finding> */
    private function detect(): array
    {
        $detector = new Context7ForbiddenFieldDetector();
        $context = new AnalysisContext(repoRoot: $this->fixtureRoot);

        return $detector->detect($context);
    }

    /** @param list<string> $lines */
    private function writeService(string $filename, array $lines): void
    {
        file_put_contents(
            $this->fixtureRoot . '/app/Services/' . $filename,
            implode("\n", $lines) . "\n"
        );
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
