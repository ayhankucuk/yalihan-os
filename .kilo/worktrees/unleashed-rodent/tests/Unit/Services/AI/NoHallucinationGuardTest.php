<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\NoHallucinationGuard;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 * Ghost class: App\Services\AI\NoHallucinationGuard henüz implement edilmedi.
 */
class NoHallucinationGuardTest extends TestCase
{
    protected NoHallucinationGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new NoHallucinationGuard();
    }

    public function test_removes_hallucinated_ozel_plaj_when_not_in_data()
    {
        $content = 'Bu villa özel plajı ile dikkat çekiyor.';
        $data = [
            'havuz_deniz' => [],
        ];

        $result = $this->guard->guard($content, $data);

        $this->assertStringNotContainsString('özel plaj', $result['content']);
        $this->assertNotEmpty($result['guard_actions']);
    }

    public function test_keeps_ozel_plaj_when_in_data()
    {
        $content = 'Bu villa özel plajı ile dikkat çekiyor.';
        $data = [
            'havuz_deniz' => [
                'ozel_plaj' => true,
            ],
        ];

        $result = $this->guard->guard($content, $data);

        $this->assertStringContainsString('özel plaj', $result['content']);
        $this->assertEmpty($result['guard_actions']);
    }

    public function test_removes_denize_sifir_when_not_in_data()
    {
        $content = 'Denize sıfır konumda yer alan villa.';
        $data = [
            'havuz_deniz' => [],
        ];

        $result = $this->guard->guard($content, $data);

        $this->assertStringNotContainsString('denize sıfır', strtolower($result['content']));
    }

    public function test_removes_null_strings()
    {
        $content = 'Bu villa null özellikleri ile dikkat çekiyor.';
        $data = [];

        $result = $this->guard->guard($content, $data);

        $this->assertStringNotContainsString('null', strtolower($result['content']));
    }

    public function test_removes_undefined_strings()
    {
        $content = 'Bu villa undefined özellikleri ile dikkat çekiyor.';
        $data = [];

        $result = $this->guard->guard($content, $data);

        $this->assertStringNotContainsString('undefined', strtolower($result['content']));
    }

    public function test_removes_isitmali_havuz_when_not_in_data()
    {
        $content = 'Isıtmalı havuzu olan villa.';
        $data = [
            'havuz_deniz' => [
                'ozel_havuz' => true,
            ],
        ];

        $result = $this->guard->guard($content, $data);

        $this->assertStringNotContainsString('ısıtmalı havuz', strtolower($result['content']));
    }

    public function test_keeps_isitmali_havuz_when_in_data()
    {
        $content = 'Isıtmalı havuzu olan villa.';
        $data = [
            'havuz_deniz' => [
                'ozel_havuz' => true,
                'isitmali_havuz' => true,
            ],
        ];

        $result = $this->guard->guard($content, $data);

        $lowerContent = mb_strtolower($result['content'], 'UTF-8');
        $this->assertTrue(
            str_contains($lowerContent, 'ısıtmalı') ||
            str_contains($lowerContent, 'isitmali') ||
            str_contains($lowerContent, 'havuz')
        );
    }

    public function test_removes_depozito_when_not_in_data()
    {
        $content = 'Depozito gerektiren villa.';
        $data = [];

        $result = $this->guard->guard($content, $data);

        $this->assertStringNotContainsString('depozito', strtolower($result['content']));
    }

    public function test_normalizes_whitespace()
    {
        $content = 'Bu villa    çok    güzel.';
        $data = [];

        $result = $this->guard->guard($content, $data);

        $this->assertStringNotContainsString('   ', $result['content']);
    }

    public function test_returns_guard_actions()
    {
        $content = 'Özel plaj ve denize sıfır villa.';
        $data = [];

        $result = $this->guard->guard($content, $data);

        $this->assertIsArray($result['guard_actions']);
        $this->assertGreaterThan(0, count($result['guard_actions']));
    }
}
