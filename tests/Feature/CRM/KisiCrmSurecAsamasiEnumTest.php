<?php

namespace Tests\Feature\CRM;

use App\Enums\KisiDurumu;
use App\Http\Middleware\RoleMiddleware;
use App\Models\Kisi;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Repositories\KisiRepository;
use App\Services\AI\YalihanCortex;
use App\Services\CRM\KisiScoringService;
use App\Services\CRMIntelligenceService;
use Tests\TestCase;

/**
 * 🛡️ CRM-02 KisiDurumu Convergence Remediation Test
 *
 * Validates canonical App\Enums\KisiDurumu authority for crm_surec_asamasi
 * across FormRequests, Controller, Repository, and Model persistence.
 */
class KisiCrmSurecAsamasiEnumTest extends TestCase
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
     * A. Create Kisi with each of the 7 canonical KisiDurumu values -> HTTP 200/302,
     * persisted to DB, read-back is instance of KisiDurumu.
     */
    public function test_a_create_kisi_with_each_of_the_seven_canonical_kisi_durumu_values(): void
    {
        $canonicalValues = ['potansiyel', 'ilgili', 'takipte', 'sicak', 'soguk', 'pasif', 'islemyapmis'];

        foreach ($canonicalValues as $index => $stage) {
            $email = "stage_{$stage}_{$index}@yalihan.test";

            $response = $this->actingAs($this->admin)->post(route('admin.kisiler.store'), [
                'ad' => 'Test',
                'soyad' => 'Stage_' . ucfirst($stage),
                'telefon' => '05551000' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'eposta' => $email,
                'kisi_tipi' => 'alici',
                'crm_surec_asamasi' => $stage,
                'aktiflik_durumu' => 1,
            ]);

            $this->assertTrue(
                in_array($response->getStatusCode(), [200, 302]),
                "Failed asserting HTTP 200/302 for canonical value '{$stage}', got status {$response->getStatusCode()}"
            );

            $this->assertDatabaseHas('kisiler', [
                'eposta' => $email,
                'crm_surec_asamasi' => $stage,
            ]);

            $kisi = Kisi::where('eposta', $email)->first();
            $this->assertNotNull($kisi, "Record for '{$stage}' was not found in DB.");
            $this->assertInstanceOf(KisiDurumu::class, $kisi->crm_surec_asamasi);
            $this->assertSame($stage, $kisi->crm_surec_asamasi->value);
        }
    }

    /**
     * B. Create Kisi omitting crm_surec_asamasi -> defaults to 'potansiyel', no crash.
     */
    public function test_b_create_kisi_omitting_crm_surec_asamasi_defaults_to_potansiyel(): void
    {
        $emailOmitted = 'omitted_stage@yalihan.test';

        $response = $this->actingAs($this->admin)->post(route('admin.kisiler.store'), [
            'ad' => 'Test',
            'soyad' => 'OmittedStage',
            'telefon' => '05559876543',
            'eposta' => $emailOmitted,
            'kisi_tipi' => 'alici',
            'aktiflik_durumu' => 1,
        ]);

        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 302]),
            "Failed asserting HTTP 200/302 when crm_surec_asamasi is omitted, got {$response->getStatusCode()}"
        );

        $this->assertDatabaseHas('kisiler', [
            'eposta' => $emailOmitted,
            'crm_surec_asamasi' => 'potansiyel',
        ]);

        $kisi = Kisi::where('eposta', $emailOmitted)->first();
        $this->assertNotNull($kisi);
        $this->assertInstanceOf(KisiDurumu::class, $kisi->crm_surec_asamasi);
        $this->assertSame(KisiDurumu::POTANSIYEL, $kisi->crm_surec_asamasi);
        $this->assertSame('potansiyel', $kisi->crm_surec_asamasi->value);

        // Also verify empty string input defaults to 'potansiyel'
        $emailEmpty = 'empty_stage@yalihan.test';
        $responseEmpty = $this->actingAs($this->admin)->post(route('admin.kisiler.store'), [
            'ad' => 'Test',
            'soyad' => 'EmptyStage',
            'telefon' => '05559876544',
            'eposta' => $emailEmpty,
            'kisi_tipi' => 'alici',
            'crm_surec_asamasi' => '',
            'aktiflik_durumu' => 1,
        ]);

        $this->assertTrue(
            in_array($responseEmpty->getStatusCode(), [200, 302]),
            "Failed asserting HTTP 200/302 when crm_surec_asamasi is empty string, got {$responseEmpty->getStatusCode()}"
        );

        $this->assertDatabaseHas('kisiler', [
            'eposta' => $emailEmpty,
            'crm_surec_asamasi' => 'potansiyel',
        ]);

        $kisiEmpty = Kisi::where('eposta', $emailEmpty)->first();
        $this->assertNotNull($kisiEmpty);
        $this->assertSame(KisiDurumu::POTANSIYEL, $kisiEmpty->crm_surec_asamasi);

        // 🛡️ Explicit Default Authority Verification:
        // 1. Application-level default: FormRequest prepareForValidation / validated explicitly sets 'potansiyel'
        $req = \App\Http\Requests\KisiStoreRequest::create('/admin/kisiler', 'POST', [
            'ad' => 'App',
            'soyad' => 'Default',
            'telefon' => '05559876545',
            'eposta' => 'app_default@yalihan.test',
            'kisi_tipi' => 'alici',
            'aktiflik_durumu' => 1,
        ]);
        $req->setContainer(app());
        $reflection = new \ReflectionClass($req);
        $prepareMethod = $reflection->getMethod('prepareForValidation');
        $prepareMethod->setAccessible(true);
        $prepareMethod->invoke($req);
        $validator = \Illuminate\Support\Facades\Validator::make($req->all(), $req->rules());
        $this->assertTrue($validator->passes());
        $req->setValidator($validator);
        $validatedData = $req->validated();
        $this->assertSame('potansiyel', $validatedData['crm_surec_asamasi'], 'Application layer FormRequest MUST default to potansiyel');

        // 2. Database-level default: Raw DB insert without crm_surec_asamasi column also receives 'potansiyel' from schema
        $rawId = \Illuminate\Support\Facades\DB::table('kisiler')->insertGetId([
            'tenant_id' => 1,
            'ad' => 'Raw',
            'soyad' => 'SchemaDefault',
            'telefon' => '05559876546',
            'eposta' => 'raw_schema@yalihan.test',
            'kisi_tipi' => 'alici',
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $rawRecord = Kisi::find($rawId);
        $this->assertNotNull($rawRecord);
        $this->assertSame(KisiDurumu::POTANSIYEL, $rawRecord->crm_surec_asamasi, 'Database schema default MUST match canonical KisiDurumu::POTANSIYEL');
    }

    /**
     * C. Update Kisi from 'potansiyel' to 'sicak' -> successfully persisted.
     */
    public function test_c_update_kisi_from_potansiyel_to_sicak_successfully_persisted(): void
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Update',
            'soyad' => 'Test',
            'eposta' => 'update_test@yalihan.test',
            'kisi_tipi' => 'alici',
            'crm_surec_asamasi' => KisiDurumu::POTANSIYEL,
            'danisman_id' => $this->admin->id,
            'aktiflik_durumu' => 1,
        ]);

        $this->assertSame(KisiDurumu::POTANSIYEL, $kisi->crm_surec_asamasi);

        $response = $this->actingAs($this->admin)->put(route('admin.kisiler.update', ['kisiId' => $kisi->id]), [
            'ad' => 'Update',
            'soyad' => 'Test',
            'eposta' => 'update_test@yalihan.test',
            'kisi_tipi' => 'alici',
            'crm_surec_asamasi' => 'sicak',
            'aktiflik_durumu' => 1,
        ]);

        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 302]),
            "Failed asserting HTTP 200/302 on update, got {$response->getStatusCode()}"
        );

        $kisi->refresh();
        $this->assertInstanceOf(KisiDurumu::class, $kisi->crm_surec_asamasi);
        $this->assertSame(KisiDurumu::SICAK, $kisi->crm_surec_asamasi);
        $this->assertSame('sicak', $kisi->crm_surec_asamasi->value);
    }

    /**
     * D. Submit invalid stage (e.g. 'yeni', 'invalid_stage') ->
     * Validation error (HTTP 422 / redirect with errors), NO ValueError, NO HTTP 500, NO DB mutation.
     */
    public function test_d_submit_invalid_stage_fails_closed_with_validation_error(): void
    {
        // 1. JSON POST with legacy/invalid stage 'yeni'
        $responseYeniJson = $this->actingAs($this->admin)->postJson(route('admin.kisiler.store'), [
            'ad' => 'Invalid',
            'soyad' => 'YeniJson',
            'eposta' => 'invalid_yeni_json@yalihan.test',
            'kisi_tipi' => 'alici',
            'crm_surec_asamasi' => 'yeni',
            'aktiflik_durumu' => 1,
        ]);

        $responseYeniJson->assertStatus(422);
        $responseYeniJson->assertJsonValidationErrors('crm_surec_asamasi');
        $this->assertDatabaseMissing('kisiler', ['eposta' => 'invalid_yeni_json@yalihan.test']);

        // 2. JSON POST with arbitrary invalid stage
        $responseUnknownJson = $this->actingAs($this->admin)->postJson(route('admin.kisiler.store'), [
            'ad' => 'Invalid',
            'soyad' => 'UnknownJson',
            'eposta' => 'invalid_unknown_json@yalihan.test',
            'kisi_tipi' => 'alici',
            'crm_surec_asamasi' => 'invalid_stage',
            'aktiflik_durumu' => 1,
        ]);

        $responseUnknownJson->assertStatus(422);
        $responseUnknownJson->assertJsonValidationErrors('crm_surec_asamasi');
        $this->assertDatabaseMissing('kisiler', ['eposta' => 'invalid_unknown_json@yalihan.test']);

        // 3. Web POST with invalid stage 'yeni' -> Redirects with session errors, NO HTTP 500
        $responseYeniWeb = $this->actingAs($this->admin)->post(route('admin.kisiler.store'), [
            'ad' => 'Invalid',
            'soyad' => 'YeniWeb',
            'eposta' => 'invalid_yeni_web@yalihan.test',
            'kisi_tipi' => 'alici',
            'crm_surec_asamasi' => 'yeni',
            'aktiflik_durumu' => 1,
        ]);

        $responseYeniWeb->assertStatus(302);
        $responseYeniWeb->assertSessionHasErrors('crm_surec_asamasi');
        $this->assertDatabaseMissing('kisiler', ['eposta' => 'invalid_yeni_web@yalihan.test']);

        // 4. JSON PUT on update with invalid stage
        $kisi = Kisi::factory()->create([
            'ad' => 'InvalidUpdate',
            'soyad' => 'Target',
            'eposta' => 'invalid_update_target@yalihan.test',
            'kisi_tipi' => 'alici',
            'crm_surec_asamasi' => KisiDurumu::POTANSIYEL,
            'danisman_id' => $this->admin->id,
            'aktiflik_durumu' => 1,
        ]);

        $responseUpdateJson = $this->actingAs($this->admin)->putJson(route('admin.kisiler.update', ['kisiId' => $kisi->id]), [
            'ad' => 'InvalidUpdate',
            'soyad' => 'Target',
            'eposta' => 'invalid_update_target@yalihan.test',
            'kisi_tipi' => 'alici',
            'crm_surec_asamasi' => 'yeni',
            'aktiflik_durumu' => 1,
        ]);

        $responseUpdateJson->assertStatus(422);
        $responseUpdateJson->assertJsonValidationErrors('crm_surec_asamasi');

        $kisi->refresh();
        $this->assertSame(KisiDurumu::POTANSIYEL, $kisi->crm_surec_asamasi);
    }

    /**
     * E. KisiRepository::getPipelineStages() executes without error and matches 'islemyapmis'.
     */
    public function test_e_kisi_repository_get_pipeline_stages_executes_and_matches_islemyapmis(): void
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Pipeline',
            'soyad' => 'IslemYapmis',
            'eposta' => 'pipeline_islemyapmis@yalihan.test',
            'crm_surec_asamasi' => KisiDurumu::ISLEMYAPMIS,
            'danisman_id' => $this->admin->id,
            'aktiflik_durumu' => 1,
        ]);

        $repository = app(KisiRepository::class);
        $stages = $repository->getPipelineStages($this->admin);

        $this->assertIsArray($stages);
        $this->assertArrayHasKey(1, $stages);
        $this->assertArrayHasKey(2, $stages);
        $this->assertArrayHasKey(3, $stages);
        $this->assertArrayHasKey(4, $stages);
        $this->assertArrayHasKey(5, $stages);

        $stage5 = $stages[5];
        $this->assertTrue(
            $stage5->contains('id', $kisi->id),
            "Expected pipeline stage 5 to contain Kisi #{$kisi->id} with status 'islemyapmis'."
        );
    }
}
