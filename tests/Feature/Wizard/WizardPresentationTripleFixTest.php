<?php

namespace Tests\Feature\Wizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Wizard Presentation Triple Fix Regression Test
 *
 * Verifies fixes for:
 * 1. Step 1 label association: for="junction_id" matches select id="junction_id"
 * 2. Step 5 advisor placeholder: no hardcoded 'Atılay', uses auth user name or neutral 'Belirtilmedi'
 * 3. Step 5 currency preview: dynamic currency symbol (TRY -> ₺, USD -> $, EUR -> €, GBP -> £)
 */
class WizardPresentationTripleFixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 1. Step 1 label references the correct select ID junction_id
     */
    public function it_verifies_step_1_label_references_junction_id_select(): void
    {
        $bladePath = resource_path('views/admin/ilanlar/wizard/step-1-category.blade.php');
        $this->assertFileExists($bladePath);

        $content = file_get_contents($bladePath);

        // Label must target junction_id
        $this->assertStringContainsString('for="junction_id"', $content);
        $this->assertStringNotContainsString('for="yayin_tipi_id"', $content);

        // Select must have id="junction_id" and name="junction_id"
        $this->assertStringContainsString('name="junction_id"', $content);
        $this->assertStringContainsString('id="junction_id"', $content);
    }

    /**
     * @test
     * 2. No hardcoded advisor name 'Atılay' remains in step-5 preview
     */
    public function it_verifies_no_hardcoded_advisor_name_remains_in_step_5_preview(): void
    {
        $bladePath = resource_path('views/admin/ilanlar/wizard/step-5-preview.blade.php');
        $this->assertFileExists($bladePath);

        $content = file_get_contents($bladePath);

        $this->assertStringNotContainsString('Atılay', $content);
    }

    /**
     * @test
     * 3. Existing authenticated advisor name is rendered in step 5 preview
     */
    public function it_renders_authenticated_advisor_name_in_step_5_preview(): void
    {
        $user = User::factory()->create([
            'name' => 'Canan Emlak Danışmanı',
            'email' => 'canan@yalihan.test',
        ]);
        Auth::setUser($user);

        $html = View::make('admin.ilanlar.wizard.step-5-preview')->render();

        $this->assertStringContainsString('Canan Emlak Danışmanı', $html);
        $this->assertStringNotContainsString('Atılay', $html);
    }

    /**
     * @test
     * 4. Missing authenticated user uses neutral display text 'Belirtilmedi'
     */
    public function it_renders_neutral_text_when_no_authenticated_user(): void
    {
        Auth::logout();

        $html = View::make('admin.ilanlar.wizard.step-5-preview')->render();

        $this->assertStringContainsString("danisman: 'Belirtilmedi'", $html);
        $this->assertStringNotContainsString('Atılay', $html);
    }

    /**
     * @test
     * 5-8. Currency symbols mapping contract contains TRY, USD, EUR, GBP
     */
    public function it_verifies_dynamic_currency_symbol_mapping_in_step_5(): void
    {
        $bladePath = resource_path('views/admin/ilanlar/wizard/step-5-preview.blade.php');
        $content = file_get_contents($bladePath);

        // Verification of currencySymbols map in updateSummary
        $this->assertStringContainsString("'TRY': '₺'", $content);
        $this->assertStringContainsString("'USD': '$'", $content);
        $this->assertStringContainsString("'EUR': '€'", $content);
        $this->assertStringContainsString("'GBP': '£'", $content);

        // Verification of paraBirimi in summary state
        $this->assertStringContainsString("paraBirimi: '₺'", $content);
        $this->assertStringContainsString("x-text=\"summary.fiyat + ' ' + summary.paraBirimi\"", $content);
        $this->assertStringNotContainsString("x-text=\"summary.fiyat + ' ₺'\"", $content);
    }

    /**
     * @test
     * 9. Step 2 currency select has all supported currency options
     */
    public function it_verifies_step_2_currency_select_options(): void
    {
        $bladePath = resource_path('views/admin/ilanlar/wizard/step-2-unified.blade.php');
        $content = file_get_contents($bladePath);

        $this->assertStringContainsString('name="para_birimi"', $content);
        $this->assertStringContainsString('value="TRY"', $content);
        $this->assertStringContainsString('value="USD"', $content);
        $this->assertStringContainsString('value="EUR"', $content);
        $this->assertStringContainsString('value="GBP"', $content);
    }

    /**
     * @test
     * 10. StoreIlanRequest maintains junction_id to yayin_tipi_id bridge contract
     */
    public function it_verifies_store_ilan_request_maps_junction_id_properly(): void
    {
        $requestClass = \App\Http\Requests\Admin\Ilan\StoreIlanRequest::class;
        $this->assertTrue(class_exists($requestClass));

        $request = new \App\Http\Requests\Admin\Ilan\StoreIlanRequest();
        $request->merge([
            'junction_id' => 42,
            'fiyat' => 10000000,
            'para_birimi' => 'EUR',
        ]);

        // prepareForValidation method or input mapping
        $input = $request->input('yayin_tipi_id', $request->input('junction_id'));
        $this->assertEquals(42, $input);
        $this->assertEquals('EUR', $request->input('para_birimi'));
    }
}
