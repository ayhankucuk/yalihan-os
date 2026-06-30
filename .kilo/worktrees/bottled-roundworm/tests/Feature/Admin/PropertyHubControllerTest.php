<?php

namespace Tests\Feature\Admin;

use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Models\AltKategoriYayinTipi;
use App\Models\YayinTipiSablonu;
use App\Models\User;
use App\Services\PropertyType\PropertyTemplateGeneratorService;
use App\Application\AI\Actions\GeneratePropertyTemplateAction;
use App\Application\AI\DTOs\CortexResponseData;
use Tests\TestCase;

/**
 * Property Hub Controller Tests
 *
 * Includes:
 * - Dashboard / CRUD / pack / analytics view tests
 * - AI Template Contract Freeze tests (HTTP code matrix)
 *
 * Context7 Compliance:
 * - aktiflik_durumu used instead of forbidden terms
 * - display_order used instead of forbidden terms
 *
 * @group property-hub
 * @group skip-until-migration-complete
 */
class PropertyHubControllerTest extends TestCase
{

    protected User $user;
    protected Feature $feature;
    protected FeatureCategory $category;
    protected IlanKategori $ilanKategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->admin()->create();

        $this->category = FeatureCategory::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        $this->feature = Feature::factory()->create([
            'name' => 'Test Feature',
            'slug' => 'test-feature',
            'feature_category_id' => $this->category->id,
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        $this->ilanKategori = IlanKategori::factory()->create([
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);
    }

    // ==================== Dashboard Tests ====================

    public function test_dashboard_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.property-hub.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.property-hub.index');
    }

    // ==================== Feature CRUD Tests ====================

    public function test_features_index_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.property-hub.features.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.property-hub.features.index');
    }

    public function test_feature_create_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.property-hub.features.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.property-hub.features.create');
    }

    public function test_feature_edit_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.property-hub.features.edit', $this->feature));

        $response->assertStatus(200);
        $response->assertViewIs('admin.property-hub.features.edit');
    }

    // ==================== Template Tests ====================

    public function test_templates_index_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.property-hub.templates.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.property-hub.templates.index');
    }

    public function test_template_edit_page_loads(): void
    {
        $yayinTipi = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik'],
            ['ad' => 'Satılık', 'aktiflik_durumu' => true]
        );

        $response = $this->actingAs($this->user)
            ->withoutExceptionHandling()
            ->get(route('admin.property-hub.templates.edit', [
                'kategori_id' => $this->ilanKategori->id,
                'yayin_tipi_id' => $yayinTipi->id,
            ]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.property-hub.templates.edit');
    }

    // ==================== Pack Tests ====================

    public function test_packs_index_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.property-hub.packs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.property-hub.packs.index');
    }

    // ==================== Analytics Tests ====================

    public function test_analytics_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.property-hub.analytics.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.property-hub.analytics.index');
    }

    // ==================== Context7 Compliance Tests ====================

    public function test_feature_model_uses_context7_fields(): void
    {
        $this->assertNotNull($this->feature->aktiflik_durumu, 'aktiflik_durumu field should exist');
        $this->assertNotNull($this->feature->display_order, 'display_order field should exist');
    }

    public function test_feature_pack_uses_context7_fields(): void
    {
        $pack = FeaturePack::create([
            'name' => 'Test Pack',
            'slug' => 'test-pack-context7',
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        $this->assertNotNull($pack->aktiflik_durumu, 'aktiflik_durumu field should exist');
    }

    public function test_kategori_uses_context7_fields(): void
    {
        $this->assertNotNull($this->ilanKategori->aktiflik_durumu, 'aktiflik_durumu field should exist');
        $this->assertNotNull($this->ilanKategori->display_order, 'display_order field should exist');
    }

    // ==================== AI Contract Freeze Tests (PH-AI-TEMPLATE) ====================

    /**
     * VALIDATION_FAILED → 422
     * Missing alt_kategori_id in request body
     */
    public function test_ai_generate_returns_422_for_missing_alt_kategori_id(): void
    {
        $template = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik-val'],
            ['ad' => 'Satılık', 'aktiflik_durumu' => true],
        );

        // POST without alt_kategori_id
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.property-hub.templates.ai-generate', ['templateId' => $template->id]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['code' => 'VALIDATION_FAILED']);
        $this->assertErrorShape($response);
    }

    /**
     * VALIDATION_FAILED → 422
     * alt_kategori_id is not an integer
     */
    public function test_ai_generate_returns_422_for_invalid_alt_kategori_id(): void
    {
        $template = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik-val2'],
            ['ad' => 'Satılık', 'aktiflik_durumu' => true],
        );

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.property-hub.templates.ai-generate', ['templateId' => $template->id]), [
                'alt_kategori_id' => 'abc',
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['code' => 'VALIDATION_FAILED']);
        $this->assertErrorShape($response);
    }

    /**
     * PIVOT_NOT_FOUND → 422
     * Body valid but no active junction exists for the (alt_kategori_id, yayin_tipi_id) pair
     */
    public function test_ai_generate_returns_422_for_missing_pivot(): void
    {
        $template = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik-orphan'],
            ['ad' => 'Satılık (Orphan)', 'aktiflik_durumu' => true],
        );

        $altKategori = IlanKategori::factory()->create([
            'name' => 'Pivot Yok Kat',
            'slug' => 'pivot-yok-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        // No AltKategoriYayinTipi record for this pair

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.property-hub.templates.ai-generate', ['templateId' => $template->id]), [
                'alt_kategori_id' => $altKategori->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['code' => 'PIVOT_NOT_FOUND']);
        $this->assertErrorShape($response);
    }

    /**
     * AI_PROVIDER_FAILED → 502
     * AI service throws an exception
     */
    public function test_ai_generate_returns_502_for_ai_provider_failure(): void
    {
        $template = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik-fail'],
            ['ad' => 'Satılık', 'aktiflik_durumu' => true],
        );

        $altKategori = IlanKategori::factory()->create([
            'name' => 'AI Fail Kat',
            'slug' => 'ai-fail-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        AltKategoriYayinTipi::create([
            'alt_kategori_id' => $altKategori->id,
            'yayin_tipi_id' => $template->id,
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        // Mock AI service to throw exception
        $this->mock(\App\Services\AI\PropertyAIService::class, function ($mock) {
            $mock->shouldReceive('generateTemplate')->andThrow(new \RuntimeException('AI timeout'));
        });

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.property-hub.templates.ai-generate', ['templateId' => $template->id]), [
                'alt_kategori_id' => $altKategori->id,
            ]);

        $response->assertStatus(502);
        $response->assertJsonFragment(['code' => 'AI_PROVIDER_FAILED']);
        $this->assertErrorShape($response);
    }

    /**
     * \Throwable (TypeError/Error) → 502 JSON (not HTML 500)
     * Proves catch(\Throwable) covers PHP \Error types that bypass catch(\Exception)
     */
    public function test_ai_generate_returns_502_for_php_error_throwable(): void
    {
        $template = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik-error'],
            ['ad' => 'Satılık Error', 'aktiflik_durumu' => true],
        );

        $altKategori = IlanKategori::factory()->create([
            'name' => 'TypeError Kat',
            'slug' => 'typeerror-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        AltKategoriYayinTipi::create([
            'alt_kategori_id' => $altKategori->id,
            'yayin_tipi_id' => $template->id,
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        // Mock AI service to throw \TypeError (extends \Error, NOT \Exception)
        $this->mock(\App\Services\AI\PropertyAIService::class, function ($mock) {
            $mock->shouldReceive('generateTemplate')->andThrow(new \TypeError('Cannot access property on null'));
        });

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.property-hub.templates.ai-generate', ['templateId' => $template->id]), [
                'alt_kategori_id' => $altKategori->id,
            ]);

        // Key assertion: \TypeError (a \Error) must return JSON 502, NOT an HTML 500
        $response->assertStatus(502);
        $response->assertJsonFragment(['code' => 'AI_PROVIDER_FAILED']);
        $this->assertErrorShape($response);
        $this->assertFalse($response->json('success'));
    }

    /**
     * PIVOT_NOT_FOUND → 422 (UPS null case)
     * Pivot exists but AI service returns null (no UPS template for combination)
     */
    public function test_ai_generate_returns_422_when_ups_returns_null(): void
    {
        $template = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik-null'],
            ['ad' => 'Test Yayın', 'aktiflik_durumu' => true],
        );

        $altKategori = IlanKategori::factory()->create([
            'name' => 'Null UPS Kat',
            'slug' => 'null-ups-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        AltKategoriYayinTipi::create([
            'alt_kategori_id' => $altKategori->id,
            'yayin_tipi_id' => $template->id,
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        // Mock the AI service to return failure (no match)
        $this->mock(\App\Services\AI\PropertyAIService::class, function ($mock) {
            $mock->shouldReceive('generateTemplate')->andReturn(new CortexResponseData(
                success: false,
                errorCode: 'PIVOT_NOT_FOUND',
                errorMessage: 'No template found'
            ));
        });

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.property-hub.templates.ai-generate', ['templateId' => $template->id]), [
                'alt_kategori_id' => $altKategori->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['code' => 'PIVOT_NOT_FOUND']);
        $this->assertErrorShape($response);
    }

    /**
     * SUCCESS → 200
     * Full happy path with mocked AI service
     */
    public function test_ai_generate_returns_200_on_success(): void
    {
        $template = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik-happy'],
            ['ad' => 'Satılık', 'aktiflik_durumu' => true],
        );

        $altKategori = IlanKategori::factory()->create([
            'name' => 'Daire',
            'slug' => 'daire-test-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        AltKategoriYayinTipi::create([
            'alt_kategori_id' => $altKategori->id,
            'yayin_tipi_id' => $template->id,
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        // Mock AI service returns template data
        $this->mock(\App\Services\AI\PropertyAIService::class, function ($mock) {
            $mock->shouldReceive('generateTemplate')->andReturn(new CortexResponseData(
                success: true,
                output: [
                    'kombinasyon' => ['kategori' => 'Konut', 'yayin_tipi' => 'Satilik', 'alt_tur' => 'Daire'],
                    'zorunlu_alanlar' => ['fiyat', 'oda_sayisi'],
                ],
                traceId: 'test-trace'
            ));
        });

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.property-hub.templates.ai-generate', ['templateId' => $template->id]), [
                'alt_kategori_id' => $altKategori->id,
            ]);

        $response->assertStatus(200);
        $this->assertSuccessShape($response);
        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('data'));
    }

    /**
     * Success shape (Phase 36): { success, data, meta:{timestamp, trace_id}, error }
     */
    private function assertSuccessShape($response): void
    {
        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => ['timestamp', 'trace_id'],
            'error',
        ]);
        $json = $response->json();
        $this->assertTrue($json['success'], 'Success response must have success=true');
        $this->assertNull($json['error'], 'Success response must have error=null');
    }

    /**
     * Error shape (Phase 36): { success, data, meta:{timestamp, trace_id}, error:{code, message} }
     */
    private function assertErrorShape($response): void
    {
        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => ['timestamp', 'trace_id'],
            'error' => ['code', 'message'],
        ]);
        $json = $response->json();
        $this->assertFalse($json['success'], 'Error response must have success=false');
        $this->assertNull($json['data'], 'Error response must have data=null');
        $this->assertIsArray($json['error'], 'Error response must have error object');
        $this->assertNotEmpty($json['error']['code'], 'Error must have error.code');
    }
}
