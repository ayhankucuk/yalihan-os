<?php

namespace Tests\Feature\Frontend;

use App\Enums\TalepDurumu;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\Talep;
use App\Models\User;
use App\Services\CRM\KisiRegistrationService;
use App\Services\CRM\TalepAuthorityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FrontendContactFormTest — Feature / Integration Tests
 *
 * Task: WEB_PROPERTY_DETAIL_CRM_CONTACT_REMEDIATION_16
 *
 * Verifies the complete frontend contact form flow:
 *   1. Browser submits name/phone/message/ilan_id → validated correctly
 *   2. Tenant resolved from Ilan → no auth()->user() required
 *   3. Kisi created/updated via KisiRegistrationService (canonical path)
 *   4. Talep created via TalepAuthorityService (CORRECTION 2 — was direct Talep::create)
 *   5. Data written to correct tenant (not leaked to wrong tenant)
 *   6. Duplicate Kisi by phone → update, not create
 *   7. Ilan location fields propagated to Talep
 */
class FrontendContactFormTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $advisorA;
    private Ilan $ilanA;
    private Ilan $ilanB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'name' => 'Tenant A Emlak',
            'domain' => 'tenant-a.local',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'name' => 'Tenant B Emlak',
            'domain' => 'tenant-b.local',
            'status' => 'active',
        ]);

        $this->advisorA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Advisor A',
        ]);

        // Ilans created withoutGlobalScopes to bypass BelongsToTenant auto-tenant binding
        $this->ilanA = Ilan::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A Villa',
            'fiyat' => 5_000_000,
            'yayin_durumu' => 'yayinda',
            'ilan_no' => 'TA-' . uniqid(),
            'danisman_id' => $this->advisorA->id,
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
        ]);

        $this->ilanB = Ilan::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Tenant B Villa',
            'fiyat' => 3_000_000,
            'yayin_durumu' => 'yayinda',
            'ilan_no' => 'TB-' . uniqid(),
            'danisman_id' => null,
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
        ]);

        // Replace TalepAuthorityService with spy so we can assert on createTalep arguments
        // while still calling the real service (so Kisi + Talep are actually persisted).
        //
        // CRITICAL: Use instance() for BOTH bindings so the SAME object is returned.
        // Laravel treats 'TalepAuthorityService::class' and 'TalepAuthorityServiceSpy::class'
        // as separate bindings when only one is set via singleton().
        // Both must point to the same instance so lastCapturedData is shared.
        $spy = new TalepAuthorityServiceSpy($this->app->make(KisiRegistrationService::class));
        $this->app->instance(TalepAuthorityService::class, $spy);
        $this->app->instance(TalepAuthorityServiceSpy::class, $spy);
    }

    private function getTalepSpy(): TalepAuthorityServiceSpy
    {
        return $this->app->make(TalepAuthorityService::class);
    }

    /**
     * Submit the contact form with a proper HTTP Referer so redirect()->back() works.
     */
    private function submitContactForm(array $data, ?int $ilanId = null): \Illuminate\Testing\TestResponse
    {
        $ilanId ??= $this->ilanA->id;
        return $this->post(
            route('frontend.forms.contact.submit'),
            $data,
            ['HTTP_REFERER' => "http://localhost/ilan/{$ilanId}"]
        );
    }

    // -------------------------------------------------------------------------
    // TEST 1: Happy path — TalepAuthorityService called with canonical spillover format
    // -------------------------------------------------------------------------

    /** @test */
    public function talep_authority_service_receives_canonical_spillover_format(): void
    {
        $response = $this->submitContactForm([
            'ilan_id' => $this->ilanA->id,
            'name'    => 'Ahmet Yılmaz',
            'phone'   => '0532 123 45 67',
            'message' => 'Bu villa hakkında bilgi almak istiyorum.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $spy = $this->getTalepSpy();
        $data = $spy->lastCapturedData;

        $this->assertNotNull($data, 'TalepAuthorityService::createTalep should be called');
        $this->assertArrayHasKey('kisi_id', $data);
        $this->assertArrayHasKey('kisi_ad', $data);
        $this->assertArrayHasKey('kisi_soyad', $data);
        $this->assertArrayHasKey('kisi_telefon', $data);
        $this->assertArrayHasKey('ilan_id', $data);
        $this->assertEquals('Ahmet', $data['kisi_ad']);
        $this->assertEquals('Yılmaz', $data['kisi_soyad']);
        $this->assertEquals('05321234567', $data['kisi_telefon']); // normalized
        $this->assertEquals($this->ilanA->id, $data['ilan_id']);
    }

    // -------------------------------------------------------------------------
    // TEST 2: Tenant isolation — Kisi and Talep in correct tenant
    // -------------------------------------------------------------------------

    /** @test */
    public function kisi_and_talep_persisted_in_correct_tenant(): void
    {
        $response = $this->submitContactForm([
            'ilan_id' => $this->ilanA->id,
            'name'    => 'Elif Demir',
            'phone'   => '0533 987 65 43',
            'message' => 'Bodrum\'da arsa arıyorum.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $kisi = Kisi::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->where('telefon', '05339876543')
            ->first();
        $this->assertNotNull($kisi, 'Kisi should be created in tenant A');
        $this->assertEquals('Elif', $kisi->ad);
        $this->assertEquals('Demir', $kisi->soyad);

        $talep = Talep::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->orderBy('id')
            ->first();
        $this->assertNotNull($talep, 'Talep should be created in tenant A');
        $this->assertEquals($kisi->id, $talep->kisi_id);
        $this->assertEquals(TalepDurumu::AKTIF, $talep->talep_durumu);

        // Tenant B is empty
        $this->assertEquals(0, Kisi::withoutGlobalScopes()->where('tenant_id', $this->tenantB->id)->count());
        $this->assertEquals(0, Talep::withoutGlobalScopes()->where('tenant_id', $this->tenantB->id)->count());
    }

    // -------------------------------------------------------------------------
    // TEST 3: Tenant isolation — form on tenant B listing writes to tenant B
    // -------------------------------------------------------------------------

    /** @test */
    public function form_on_tenant_b_listing_writes_to_tenant_b_not_tenant_a(): void
    {
        $response = $this->submitContactForm([
            'ilan_id' => $this->ilanB->id,
            'name'    => 'Test User',
            'phone'   => '0555 123 45 67',
        ]);

        $response->assertRedirect();

        $kisi = Kisi::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantB->id)
            ->first();
        $this->assertNotNull($kisi, 'Kisi should be created in tenant B');

        $talep = Talep::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantB->id)
            ->orderBy('id')
            ->first();
        $this->assertNotNull($talep, 'Talep should be created in tenant B');
        $this->assertEquals($kisi->id, $talep->kisi_id);

        // Tenant A is empty
        $this->assertEquals(0, Kisi::withoutGlobalScopes()->where('tenant_id', $this->tenantA->id)->count());
        $this->assertEquals(0, Talep::withoutGlobalScopes()->where('tenant_id', $this->tenantA->id)->count());
    }

    // -------------------------------------------------------------------------
    // TEST 4: Duplicate Kisi by normalized phone — update not create
    // -------------------------------------------------------------------------

    /** @test */
    public function existing_kisi_by_phone_is_updated_not_duplicated(): void
    {
        // First submission creates Kisi
        $this->submitContactForm([
            'ilan_id' => $this->ilanA->id,
            'name'    => 'Yusuf Kaya',
            'phone'   => '0540 999 88 77',
            'message' => 'İlk mesaj.',
        ]);

        $spy = $this->getTalepSpy();
        $firstData = $spy->lastCapturedData;
        $kisiId1 = $firstData['kisi_id'] ?? null;

        // Second submission with same phone (different spacing) updates existing Kisi
        $this->submitContactForm([
            'ilan_id' => $this->ilanA->id,
            'name'    => 'Yusuf Kaya Updated',
            'phone'   => '05409998877', // same number, different format
            'message' => 'İkinci mesaj.',
        ]);

        $secondData = $spy->lastCapturedData;
        $kisiId2 = $secondData['kisi_id'] ?? null;

        $this->assertEquals($kisiId1, $kisiId2, 'Same phone should update, not create new Kisi');

        $kisiCount = Kisi::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->where('telefon', '05409998877')
            ->count();
        $this->assertEquals(1, $kisiCount, 'Should have exactly one Kisi for this phone');
    }

    // -------------------------------------------------------------------------
    // TEST 5: Blade field names mapped to CRM canonical names
    // -------------------------------------------------------------------------

    /** @test */
    public function blade_fields_mapped_to_crm_canonical_names(): void
    {
        $this->submitContactForm([
            'ilan_id' => $this->ilanA->id,
            'name'    => 'Mehmet Ali Yılmaz',
            'phone'   => '+90 532 000 11 22',
            'message' => 'Test mesajı.',
        ]);

        $spy = $this->getTalepSpy();
        $data = $spy->lastCapturedData;

        // Name split correctly
        $this->assertEquals('Mehmet', $data['kisi_ad']);
        $this->assertEquals('Ali Yılmaz', $data['kisi_soyad']);

        // Phone normalized
        $this->assertEquals('+905320001122', $data['kisi_telefon']);

        // Message preserved
        $this->assertStringContainsString('Test mesajı.', $data['notlar']);
    }

    // -------------------------------------------------------------------------
    // TEST 6: Validation — missing required fields
    // -------------------------------------------------------------------------

    /** @test */
    public function missing_required_fields_rejected(): void
    {
        // Missing name
        $r = $this->submitContactForm([
            'ilan_id' => $this->ilanA->id,
            'phone'   => '0532 123 45 67',
        ]);
        $r->assertSessionHasErrors('name');

        // Missing phone
        $r = $this->submitContactForm([
            'ilan_id' => $this->ilanA->id,
            'name'    => 'Ahmet',
        ]);
        $r->assertSessionHasErrors('phone');

        // Missing ilan_id
        $r = $this->submitContactForm([
            'name'  => 'Ahmet',
            'phone' => '0532 123 45 67',
        ]);
        $r->assertSessionHasErrors('ilan_id');

        // Invalid ilan_id
        $r = $this->submitContactForm([
            'ilan_id' => 99999,
            'name'    => 'Ahmet',
            'phone'   => '0532 123 45 67',
        ]);
        $r->assertSessionHasErrors('ilan_id');
    }

    // -------------------------------------------------------------------------
    // TEST 7: Single-part name handled correctly
    // -------------------------------------------------------------------------

    /** @test */
    public function single_part_name_handled(): void
    {
        $this->submitContactForm([
            'ilan_id' => $this->ilanA->id,
            'name'    => 'Fatma',
            'phone'   => '0532 000 00 01',
        ]);

        $spy = $this->getTalepSpy();
        $data = $spy->lastCapturedData;

        $this->assertEquals('Fatma', $data['kisi_ad']);
        $this->assertEquals('', $data['kisi_soyad']);
    }

    // -------------------------------------------------------------------------
    // TEST 8: Ilan location propagated to Talep
    // -------------------------------------------------------------------------

    /** @test */
    public function ilan_location_propagated_to_talep(): void
    {
        $ilanLoc = Ilan::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Location Test Villa',
            'fiyat' => 2_000_000,
            'yayin_durumu' => 'yayinda',
            'ilan_no' => 'LOC-' . uniqid(),
            'danisman_id' => $this->advisorA->id,
            'il_id' => 15,
            'ilce_id' => 192,
            'mahalle_id' => 1001,
        ]);

        $this->submitContactForm([
            'ilan_id' => $ilanLoc->id,
            'name'    => 'Location User',
            'phone'   => '0532 000 00 02',
        ]);

        $spy = $this->getTalepSpy();
        $data = $spy->lastCapturedData;

        $this->assertEquals(15, $data['il_id']);
        $this->assertEquals(192, $data['ilce_id']);
        $this->assertEquals($ilanLoc->id, $data['ilan_id']);
    }
}
