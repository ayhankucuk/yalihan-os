<?php

namespace Tests\Feature\CRM;

use App\Http\Middleware\RoleMiddleware;
use App\Http\Requests\KisiStoreRequest;
use App\Http\Requests\KisiUpdateRequest;
use App\Models\Kisi;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Modules\Crm\Services\KisiService;
use App\Services\AI\YalihanCortex;
use App\Services\CRM\KisiRegistrationService;
use App\Services\CRM\KisiScoringService;
use App\Services\CRMIntelligenceService;
use App\Services\Kisi\BulkKisiService;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class KisiEmailContractRemediationTest extends TestCase
{
    protected User $admin;

    protected ?Role $adminRole = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([RoleMiddleware::class]);

        // Mock AI & Scoring services to avoid external telemetry during tests
        $this->mock(YalihanCortex::class, function ($mock) {
            $mock->shouldReceive('requestCustomerRecommendations')->andReturn([]);
            $mock->shouldReceive('requestCustomerAiEnrichment')->andReturn([]);
        });
        $this->mock(CRMIntelligenceService::class, function ($mock) {
            $mock->shouldReceive('calculateLeadPriority')->andReturn(50);
            $mock->shouldReceive('getRecommendedListings')->andReturn([]);
        });
        $this->mock(KisiScoringService::class, function ($mock) {
            $mock->shouldReceive('performAudit')->andReturn([]);
        });

        $this->adminRole = Role::where('name', 'admin')->first();
        if (! $this->adminRole) {
            $this->adminRole = new Role;
            $this->adminRole->name = 'admin';
            $this->adminRole->save();
        }

        $this->admin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'name' => 'Admin User',
        ]);
    }

    /**
     * A. CREATE: Input eposta -> persisted kisiler.eposta exact value -> read-back exact value
     */
    public function test_create_with_canonical_eposta_persists_and_reads_back(): void
    {
        // 1. Boundary FormRequest validation & normalization
        $request = new KisiStoreRequest;
        $rawInput = [
            'ad' => 'Mehmet',
            'soyad' => 'Demir',
            'telefon' => '05551112233',
            'eposta' => 'mehmet.canonical@yalihan.test',
            'kisi_tipi' => 'alici',
            'crm_surec_asamasi' => 'potansiyel',
            'aktiflik_durumu' => 1,
        ];
        $validator = Validator::make($rawInput, $request->rules());
        $this->assertTrue($validator->passes(), 'Validator should pass for canonical eposta.');

        // 2. Service-level registration (omitting uncastable crm_surec_asamasi per instruction)
        $registrationService = app(KisiRegistrationService::class);
        $kisi = $registrationService->register([
            'tenant_id' => $this->getDefaultTenantId(),
            'ad' => 'Mehmet',
            'soyad' => 'Demir',
            'telefon' => '05551112233',
            'eposta' => 'mehmet.canonical@yalihan.test',
            'kisi_tipi' => 'alici',
            'aktiflik_durumu' => 1,
        ], $this->admin->id);

        $this->assertDatabaseHas('kisiler', [
            'id' => $kisi->id,
            'ad' => 'Mehmet',
            'soyad' => 'Demir',
            'eposta' => 'mehmet.canonical@yalihan.test',
        ]);

        $persisted = Kisi::find($kisi->id);
        $this->assertNotNull($persisted);
        $this->assertSame('mehmet.canonical@yalihan.test', $persisted->eposta);
    }

    /**
     * A. CREATE (boundary normalization): Input email -> normalized to eposta -> persisted and reads back
     */
    public function test_create_with_boundary_email_normalizes_to_eposta(): void
    {
        // 1. Boundary FormRequest: input has "email"
        $request = KisiStoreRequest::create('/admin/kisiler', 'POST', [
            'ad' => 'Ayse',
            'soyad' => 'Kaya',
            'telefon' => '05552223344',
            'email' => 'ayse.boundary@yalihan.test',
            'kisi_tipi' => 'satici',
            'crm_surec_asamasi' => 'potansiyel',
            'aktiflik_durumu' => 1,
        ]);
        $request->setContainer($this->app);

        // Call prepareForValidation via reflection or by triggering validation
        $reflection = new \ReflectionClass($request);
        $prepareMethod = $reflection->getMethod('prepareForValidation');
        $prepareMethod->setAccessible(true);
        $prepareMethod->invoke($request);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertTrue($validator->passes(), 'Validator should pass with normalized eposta from email.');

        $request->setValidator($validator);
        $validated = $request->validated();

        $this->assertArrayHasKey('eposta', $validated);
        $this->assertArrayNotHasKey('email', $validated);
        $this->assertSame('ayse.boundary@yalihan.test', $validated['eposta']);

        // 2. Service registration with normalized data
        $registrationService = app(KisiRegistrationService::class);
        $registrationPayload = $validated;
        unset($registrationPayload['crm_surec_asamasi']); // omit per task instruction
        $registrationPayload['tenant_id'] = $this->getDefaultTenantId();

        $kisi = $registrationService->register($registrationPayload, $this->admin->id);

        $this->assertDatabaseHas('kisiler', [
            'id' => $kisi->id,
            'ad' => 'Ayse',
            'soyad' => 'Kaya',
            'eposta' => 'ayse.boundary@yalihan.test',
        ]);

        $persisted = Kisi::find($kisi->id);
        $this->assertSame('ayse.boundary@yalihan.test', $persisted->eposta);
    }

    /**
     * B. UPDATE: Change eposta/email -> eposta changes correctly in DB
     */
    public function test_update_changes_eposta_correctly(): void
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Can',
            'soyad' => 'Yilmaz',
            'eposta' => 'can.old@yalihan.test',
            'danisman_id' => $this->admin->id,
            'aktiflik_durumu' => 1,
        ]);

        $registrationService = app(KisiRegistrationService::class);

        // 1. Update using canonical eposta
        $updateRequest1 = KisiUpdateRequest::create("/admin/kisiler/{$kisi->id}", 'PUT', [
            'ad' => 'Can',
            'soyad' => 'Yilmaz',
            'eposta' => 'can.new1@yalihan.test',
            'crm_surec_asamasi' => 'potansiyel',
            'aktiflik_durumu' => 1,
        ]);
        $updateRequest1->setContainer($this->app);
        $updateRequest1->setRouteResolver(function () use ($kisi) {
            $route = new Route('PUT', 'admin/kisiler/{kisiId}', []);
            $route->parameters = ['kisiId' => $kisi->id];

            return $route;
        });

        $reflection = new \ReflectionClass($updateRequest1);
        $prepareMethod = $reflection->getMethod('prepareForValidation');
        $prepareMethod->setAccessible(true);
        $prepareMethod->invoke($updateRequest1);

        $validator1 = Validator::make($updateRequest1->all(), $updateRequest1->rules());
        $this->assertTrue($validator1->passes());
        $updateRequest1->setValidator($validator1);
        $validated1 = $updateRequest1->validated();

        $this->assertSame('can.new1@yalihan.test', $validated1['eposta']);
        $this->assertArrayNotHasKey('email', $validated1);

        unset($validated1['crm_surec_asamasi']);
        $registrationService->update($kisi, $validated1);

        $kisi->refresh();
        $this->assertSame('can.new1@yalihan.test', $kisi->eposta);

        // 2. Update using boundary email
        $updateRequest2 = KisiUpdateRequest::create("/admin/kisiler/{$kisi->id}", 'PUT', [
            'ad' => 'Can',
            'soyad' => 'Yilmaz',
            'email' => 'can.new2@yalihan.test',
            'crm_surec_asamasi' => 'potansiyel',
            'aktiflik_durumu' => 1,
        ]);
        $updateRequest2->setContainer($this->app);
        $updateRequest2->setRouteResolver(function () use ($kisi) {
            $route = new Route('PUT', 'admin/kisiler/{kisiId}', []);
            $route->parameters = ['kisiId' => $kisi->id];

            return $route;
        });

        $prepareMethod->invoke($updateRequest2);
        $validator2 = Validator::make($updateRequest2->all(), $updateRequest2->rules());
        $this->assertTrue($validator2->passes());
        $updateRequest2->setValidator($validator2);
        $validated2 = $updateRequest2->validated();

        $this->assertSame('can.new2@yalihan.test', $validated2['eposta']);
        $this->assertArrayNotHasKey('email', $validated2);

        unset($validated2['crm_surec_asamasi']);
        $registrationService->update($kisi, $validated2);

        $kisi->refresh();
        $this->assertSame('can.new2@yalihan.test', $kisi->eposta);
    }

    /**
     * C. PARTIAL UPDATE: Omit eposta/email -> existing eposta preserved
     */
    public function test_partial_update_preserves_existing_eposta(): void
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Zeynep',
            'soyad' => 'Ak',
            'eposta' => 'zeynep.preserved@yalihan.test',
            'danisman_id' => $this->admin->id,
            'aktiflik_durumu' => 1,
        ]);

        $registrationService = app(KisiRegistrationService::class);

        // Update omitting eposta/email
        $partialPayload = [
            'ad' => 'Zeynep Updated',
            'soyad' => 'Ak Updated',
        ];

        $registrationService->update($kisi, $partialPayload);

        $kisi->refresh();
        $this->assertSame('Zeynep Updated', $kisi->ad);
        $this->assertSame('zeynep.preserved@yalihan.test', $kisi->eposta);
    }

    /**
     * D. BULK QUERY: Bulk person lookup/create with eposta/email data via BulkKisiService
     */
    public function test_bulk_kisi_service_handles_eposta_and_email_without_sql_error(): void
    {
        $bulkService = app(BulkKisiService::class);

        $bulkData = [
            [
                'ad' => 'BulkOne',
                'soyad' => 'Test',
                'eposta' => 'bulk1@yalihan.test',
                'kisi_tipi' => 'alici',
                'aktiflik_durumu' => true,
            ],
            [
                'ad' => 'BulkTwo',
                'soyad' => 'Test',
                'email' => 'bulk2@yalihan.test',
                'kisi_tipi' => 'satici',
                'aktiflik_durumu' => true,
            ],
        ];

        $result = $bulkService->bulkCreate($bulkData, $this->admin->id);

        $this->assertCount(2, $result['created']);
        $this->assertEmpty($result['errors']);

        $this->assertDatabaseHas('kisiler', ['eposta' => 'bulk1@yalihan.test']);
        $this->assertDatabaseHas('kisiler', ['eposta' => 'bulk2@yalihan.test']);

        // Test export query
        $exportCollection = $bulkService->getExportData();
        $this->assertTrue($exportCollection->isNotEmpty());
        $this->assertNotNull($exportCollection->first()->eposta);
    }

    /**
     * D. CRM KisiService: createKisi and getAllKisiler
     */
    public function test_crm_kisi_service_handles_eposta_and_email_without_sql_error(): void
    {
        $kisiService = app(KisiService::class);

        // 1. Create with eposta
        $kisi1 = $kisiService->createKisi([
            'ad' => 'ServiceOne',
            'soyad' => 'Test',
            'eposta' => 'service1@yalihan.test',
            'kisi_tipi' => 'alici',
            'tenant_id' => $this->getDefaultTenantId(),
        ]);
        $this->assertSame('service1@yalihan.test', $kisi1->eposta);

        // 2. Create with boundary email
        $kisi2 = $kisiService->createKisi([
            'ad' => 'ServiceTwo',
            'soyad' => 'Test',
            'email' => 'service2@yalihan.test',
            'kisi_tipi' => 'satici',
            'tenant_id' => $this->getDefaultTenantId(),
        ]);
        $this->assertSame('service2@yalihan.test', $kisi2->eposta);

        // 3. Search query through getAllKisiler
        $this->actingAs($this->admin);
        $searchResult = $kisiService->getAllKisiler(['search' => 'service1@yalihan.test']);
        $this->assertNotEmpty($searchResult);
    }

    /**
     * E. LEGACY COLUMN GUARD: Verify no active SQL query in the repaired paths targets nonexistent kisiler.email
     */
    public function test_legacy_column_guard_ensures_no_query_targets_kisiler_email(): void
    {
        $executedQueries = [];
        DB::listen(function ($query) use (&$executedQueries) {
            $executedQueries[] = $query->sql;
        });

        // 1. FormRequest unique validation on eposta
        $storeReq = new KisiStoreRequest;
        Validator::make([
            'ad' => 'Guard',
            'soyad' => 'Test',
            'eposta' => 'guard@yalihan.test',
        ], $storeReq->rules())->passes();

        // 2. RegistrationService register
        $regService = app(KisiRegistrationService::class);
        $kisi = $regService->register([
            'tenant_id' => $this->getDefaultTenantId(),
            'ad' => 'Guard',
            'soyad' => 'Test',
            'eposta' => 'guard@yalihan.test',
            'kisi_tipi' => 'alici',
            'aktiflik_durumu' => 1,
        ], $this->admin->id);

        // 3. RegistrationService update
        $regService->update($kisi, [
            'ad' => 'Guard Updated',
            'eposta' => 'guard.updated@yalihan.test',
        ]);

        // 4. BulkKisiService operations
        $bulkService = app(BulkKisiService::class);
        $bulkService->bulkCreate([[
            'ad' => 'GuardBulk',
            'soyad' => 'Test',
            'email' => 'guardbulk@yalihan.test',
            'kisi_tipi' => 'alici',
            'aktiflik_durumu' => true,
        ]], $this->admin->id);
        $bulkService->getExportData();

        // 5. CRM KisiService operations
        $kisiService = app(KisiService::class);
        $this->actingAs($this->admin);
        $kisiService->getAllKisiler(['search' => 'guard']);

        // Inspect all queries to confirm no SQL mentions kisiler.email
        foreach ($executedQueries as $sql) {
            $sqlLower = strtolower($sql);
            $this->assertFalse(
                str_contains($sqlLower, 'kisiler.email') ||
                str_contains($sqlLower, '`kisiler`.`email`') ||
                str_contains($sqlLower, '"kisiler"."email"') ||
                (str_contains($sqlLower, 'from `kisiler`') && str_contains($sqlLower, '`email`')) ||
                (str_contains($sqlLower, 'from "kisiler"') && str_contains($sqlLower, '"email"')),
                "Unexpected SQL referencing nonexistent kisiler.email: {$sql}"
            );
        }
    }

    /**
     * READBACK PROOF: Views render eposta correctly
     */
    public function test_readback_views_render_canonical_eposta(): void
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Readback',
            'soyad' => 'Person',
            'eposta' => 'readback.test@yalihan.test',
            'danisman_id' => $this->admin->id,
            'aktiflik_durumu' => 1,
        ]);

        // Index view
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.kisiler.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('readback.test@yalihan.test');

        // Show view
        $showResponse = $this->actingAs($this->admin)->get(route('admin.kisiler.show', ['kisiId' => $kisi->id]));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('readback.test@yalihan.test');

        // Edit view
        $editResponse = $this->actingAs($this->admin)->get(route('admin.kisiler.edit', ['kisiId' => $kisi->id]));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('readback.test@yalihan.test');
    }
}
