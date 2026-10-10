<?php

namespace Tests\Feature\Frontend;

use App\Models\Ilan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property Card Area Metric Display Test
 *
 * Verifies the canonical area fallback contract:
 * net_m2 -> brut_m2 -> alan_m2
 */
class PropertyCardAreaMetricDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $view = 'components.property-card';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = \App\Models\User::factory()->create();
    }

    /**
     * @test
     * Residential with net_m2 — should display net_m2
     */
    public function it_displays_net_m2_when_available(): void
    {
        $ilan = Ilan::factory()->create([
            'net_m2' => 120,
            'brut_m2' => 150,
            'alan_m2' => null,
        ]);

        $html = \Illuminate\Support\Facades\View::make($this->view, ['ilan' => $ilan])->render();

        $this->assertStringContainsString('120 m²', $html);
        $this->assertStringNotContainsString('150 m²', $html);
    }

    /**
     * @test
     * Residential with brut_m2 only — should display brut_m2 as fallback
     */
    public function it_displays_brut_m2_when_net_m2_is_null(): void
    {
        $ilan = Ilan::factory()->create([
            'net_m2' => null,
            'brut_m2' => 150,
            'alan_m2' => null,
        ]);

        $html = \Illuminate\Support\Facades\View::make($this->view, ['ilan' => $ilan])->render();

        $this->assertStringContainsString('150 m²', $html);
    }

    /**
     * @test
     * Land with alan_m2 only — should display alan_m2 as final fallback
     */
    public function it_displays_alan_m2_when_net_and_brut_are_null(): void
    {
        $ilan = Ilan::factory()->create([
            'net_m2' => null,
            'brut_m2' => null,
            'alan_m2' => 500,
        ]);

        $html = \Illuminate\Support\Facades\View::make($this->view, ['ilan' => $ilan])->render();

        $this->assertStringContainsString('500 m²', $html);
    }

    /**
     * @test
     * Commercial property with applicable area
     */
    public function it_displays_area_for_commercial_property(): void
    {
        $ilan = Ilan::factory()->create([
            'net_m2' => 300,
            'brut_m2' => 350,
            'alan_m2' => null,
        ]);

        $html = \Illuminate\Support\Facades\View::make($this->view, ['ilan' => $ilan])->render();

        $this->assertStringContainsString('300 m²', $html);
    }

    /**
     * @test
     * All area fields null — should NOT display area metric
     */
    public function it_does_not_display_area_when_all_fields_are_null(): void
    {
        $ilan = Ilan::factory()->create([
            'net_m2' => null,
            'brut_m2' => null,
            'alan_m2' => null,
        ]);

        $html = \Illuminate\Support\Facades\View::make($this->view, ['ilan' => $ilan])->render();

        $this->assertStringNotContainsString('m²', $html);
        $this->assertStringNotContainsString('m2', $html);
    }

    /**
     * @test
     * Zero values — should NOT display (invalid)
     */
    public function it_does_not_display_zero_area_values(): void
    {
        $ilan = Ilan::factory()->create([
            'net_m2' => 0,
            'brut_m2' => 0,
            'alan_m2' => 0,
        ]);

        $html = \Illuminate\Support\Facades\View::make($this->view, ['ilan' => $ilan])->render();

        $this->assertStringNotContainsString('0 m²', $html);
    }

    /**
     * @test
     * Null ilan — should not throw error
     */
    public function it_handles_null_ilan_without_error(): void
    {
        $html = \Illuminate\Support\Facades\View::make($this->view, ['ilan' => null])->render();

        $this->assertStringNotContainsString('m²', $html);
    }

    /**
     * @test
     * Area value formatting — should be comma-separated thousands
     */
    public function it_formats_large_area_values_correctly(): void
    {
        $ilan = Ilan::factory()->create([
            'net_m2' => 1500,
            'brut_m2' => null,
            'alan_m2' => null,
        ]);

        $html = \Illuminate\Support\Facades\View::make($this->view, ['ilan' => $ilan])->render();

        $this->assertStringContainsString('1,500 m²', $html);
    }

    /**
     * @test
     * Existing property card renders without breaking
     */
    public function it_renders_property_card_completely(): void
    {
        $ilan = Ilan::factory()->create([
            'baslik' => 'Test Villa Bodrum',
            'fiyat' => 5000000,
            'net_m2' => 200,
            'oda_sayisi' => 4,
        ]);

        $html = \Illuminate\Support\Facades\View::make($this->view, ['ilan' => $ilan])->render();

        $this->assertStringContainsString('Test Villa Bodrum', $html);
        $this->assertStringContainsString('5.000.000', $html);
        $this->assertStringContainsString('200 m²', $html);
        // Room count renders with "Oda" suffix
        $this->assertMatchesRegularExpression('/\d+\s*Oda/', $html);
    }
}
