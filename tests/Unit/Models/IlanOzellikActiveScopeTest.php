<?php

namespace Tests\Unit\Models;

use App\Enums\AktiflikDurumu;
use App\Models\IlanOzellik;
use Tests\TestCase;

class IlanOzellikActiveScopeTest extends TestCase
{
    /**
     * Test that scopeAktif queries the canonical aktiflik_durumu column and not is_active.
     */
    public function test_scope_aktif_targets_canonical_aktiflik_durumu_column(): void
    {
        $query = IlanOzellik::query()->aktif();

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertMatchesRegularExpression('/[`"]?aktiflik_durumu[`"]?\s*=\s*\?/', $sql);
        $this->assertStringNotContainsString('is_active', $sql);
        $this->assertContains(AktiflikDurumu::AKTIF->value, $bindings);
    }
}
