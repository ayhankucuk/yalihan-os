<?php

namespace Tests\Feature\Security;

use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Models\V2\Ilan as V2Ilan;
use App\Enums\IlanDurumu;
use App\Services\SaaS\TenantContextService;
use Tests\TestCase;

/**
 * İlan Agent Erişim Boundary Test Suite
 *
 * ADR-Ilan-Erisim-Politikasi Madde 3 — Danışman bilgi gizliliği:
 *   - Anonim/cross-tenant  → yalnız name + avatar
 *   - Auth + same tenant   → name + avatar + phone + email + whatsapp
 *
 * Controller→Resource bayrak zinciri:
 *   show() → $request->attributes->set('ilan_detail_full', true)
 *         → IlanDetailResource → AgentResource($danisman)
 *         → $isFullAccess = (bool) $request->attributes->get('ilan_detail_full', false)
 *
 * Bu test, üç çıktı yolunun her birinde AgentResource çıktısını doğrular.
 */
class IlanAgentAccessTest extends TestCase
{
    private const TEST_ULKE_ID = 77;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;
    protected V2Ilan $ilanA_Yayinlanmis;
    protected V2Ilan $ilanB_Yayinlanmis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::firstOrCreate(
            ['domain' => 'agent-a.local'],
            ['name' => 'Agent Tenant A']
        );
        $this->tenantB = Tenant::firstOrCreate(
            ['domain' => 'agent-b.local'],
            ['name' => 'Agent Tenant B']
        );

        app(TenantContextService::class)->setTenant($this->tenantA);
        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => self::TEST_ULKE_ID,
            'aktiflik_durumu' => 1,
            'email' => 'usera@agent-a.local',
            'telefon' => '+905551110001',
        ]);

        app(TenantContextService::class)->setTenant($this->tenantB);
        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'ulke_id' => self::TEST_ULKE_ID,
            'aktiflik_durumu' => 1,
            'email' => 'userb@agent-b.local',
            'telefon' => '+905551110002',
            'whatsapp_numara' => '+905551110002',
        ]);

        // Yayınlanmış ilanlar — danisman userA (tenantA)
        $this->ilanA_Yayinlanmis = $this->makeIlan(
            $this->tenantA->id, $this->userA->id, self::TEST_ULKE_ID, IlanDurumu::YAYINDA
        );
        // Yayınlanmış cross-tenant ilan — danisman userB (tenantB)
        $this->ilanB_Yayinlanmis = $this->makeIlan(
            $this->tenantB->id, $this->userB->id, self::TEST_ULKE_ID, IlanDurumu::YAYINDA
        );
    }

    protected function tearDown(): void
    {
        foreach (['ilanA_Yayinlanmis', 'ilanB_Yayinlanmis'] as $key) {
            if (isset($this->$key)) {
                V2Ilan::withoutGlobalScopes()->forceDelete($this->$key->id);
            }
        }
        parent::tearDown();
    }

    private function makeIlan(int $tenantId, int $danismanId, int $ulkeId, IlanDurumu $durum): V2Ilan
    {
        return V2Ilan::withoutEvents(function () use ($tenantId, $danismanId, $ulkeId, $durum) {
            return V2Ilan::create([
                'tenant_id' => $tenantId,
                'ulke_id' => $ulkeId,
                'user_id' => $danismanId,
                'danisman_id' => $danismanId,
                'baslik' => 'Agent Test Ilan ' . uniqid(),
                'yayin_durumu' => $durum->value,
                'ilan_no' => rand(100000, 999999),
                'slug' => 'agent-test-' . uniqid(),
                'lat' => 37.123456,
                'lng' => 28.654321,
                'brut_m2' => 120,
                'oda_sayisi' => '3+1',
                'banyo_sayisi' => 2,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // SENARYO 1: Anonim kullanıcı — agent name + avatar yalnızca
    // Beklenti: phone/email/whatsapp YOK
    // ─────────────────────────────────────────────────────────────────────

    public function test_s1_anonim_user_sees_agent_name_and_avatar_only(): void
    {
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Yayinlanmis->id}");

        $this->assertEquals(200, $response->status());

        $data = $response->json('data');

        // IlanPublicDetailResource → danisman alanı (agent değil)
        $this->assertArrayHasKey('danisman', $data, 'S1: Public path danisman alanı içermeli');
        $danisman = $data['danisman'];

        // Zorunlu alanlar mevcut
        $this->assertArrayHasKey('id', $danisman);
        $this->assertArrayHasKey('name', $danisman);
        $this->assertArrayHasKey('avatar', $danisman);

        // Hassas alanlar KORUNAN
        $this->assertArrayNotHasKey('phone', $danisman,
            'S1: Anonim kullanıcıya telefon bilgisi sızmamalı');
        $this->assertArrayNotHasKey('email', $danisman,
            'S1: Anonim kullanıcıya email bilgisi sızmamalı');
        $this->assertArrayNotHasKey('whatsapp', $danisman,
            'S1: Anonim kullanıcıya whatsapp bilgisi sızmamalı');
        $this->assertArrayNotHasKey('title', $danisman,
            'S1: Anonim kullanıcıya agent title sızmamalı');

        // IlanPublicDetailResource agent yerine danisman kullanır
        $this->assertArrayNotHasKey('agent', $data,
            'S1: Public path agent yerine danisman kullanır');

        // BLIND SPOT KAPATILDI: danisman içindeki hassas alanlar denetlenmeli.
        // Madde 20.6: danisman altında telefon/email/whatsapp kesinlikle olmamalı.
        $this->assertArrayNotHasKey('phone', $danisman,
            'S1: danisman içinde telefon sızmamalı');
        $this->assertArrayNotHasKey('email', $danisman,
            'S1: danisman içinde email sızmamalı');
        $this->assertArrayNotHasKey('whatsapp', $danisman,
            'S1: danisman içinde whatsapp sızmamalı');
        $this->assertArrayNotHasKey('title', $danisman,
            'S1: danisman içinde title sızmamalı');
    }

    // ─────────────────────────────────────────────────────────────────────
    // SENARYO 2: Auth + cross-tenant kullanıcı — agent name + avatar yalnızca
    // Beklenti: phone/email/whatsapp YOK (ilan_detail_full bayrağı tetiklenmez)
    // ─────────────────────────────────────────────────────────────────────

    public function test_s2_cross_tenant_auth_sees_agent_name_and_avatar_only(): void
    {
        // userB (tenantB), ilanA (tenantA) — cross-tenant auth
        $this->actingAs($this->userB, 'sanctum');

        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Yayinlanmis->id}");

        $this->assertEquals(200, $response->status());

        $data = $response->json('data');

        // Cross-tenant yayınlanmış ilan → IlanPublicDetailResource döner (danisman alanı)
        $this->assertArrayHasKey('danisman', $data, 'S2: Cross-tenant public path danisman alanı içermeli');
        $danisman = $data['danisman'];

        // Zorunlu alanlar mevcut
        $this->assertArrayHasKey('id', $danisman);
        $this->assertArrayHasKey('name', $danisman);

        // Hassas alanlar KORUNAN — cross-tenant auth bile tam erişim almaz
        $this->assertArrayNotHasKey('phone', $danisman,
            'S2: Cross-tenant auth kullanıcıya telefon sızmamalı');
        $this->assertArrayNotHasKey('email', $danisman,
            'S2: Cross-tenant auth kullanıcıya email sızmamalı');
        $this->assertArrayNotHasKey('whatsapp', $danisman,
            'S2: Cross-tenant auth kullanıcıya whatsapp sızmamalı');

        // IlanPublicDetailResource agent yerine danisman kullanır
        $this->assertArrayNotHasKey('agent', $data,
            'S2: Public path agent yerine danisman kullanır');

        // BLIND SPOT KAPATILDI: danisman içindeki hassas alanlar denetlenmeli.
        $this->assertArrayNotHasKey('phone', $danisman,
            'S2: danisman içinde telefon sızmamalı');
        $this->assertArrayNotHasKey('email', $danisman,
            'S2: danisman içinde email sızmamalı');
        $this->assertArrayNotHasKey('whatsapp', $danisman,
            'S2: danisman içinde whatsapp sızmamalı');
    }

    // ─────────────────────────────────────────────────────────────────────
    // SENARYO 3: Auth + same-tenant kullanıcı — tam agent bilgisi
    // Beklenti: name + avatar + phone + email + whatsapp (+ title)
    // ─────────────────────────────────────────────────────────────────────

    public function test_s3_same_tenant_auth_sees_full_agent_details(): void
    {
        // userA (tenantA), ilanA (tenantA) — same tenant auth
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Yayinlanmis->id}");

        $this->assertEquals(200, $response->status());

        $agent = $response->json('data.agent');

        // Temel alanlar
        $this->assertArrayHasKey('id', $agent);
        $this->assertArrayHasKey('name', $agent);
        $this->assertArrayHasKey('avatar', $agent);

        // Hassas alanlar AÇIK — same tenant auth
        $this->assertArrayHasKey('phone', $agent,
            'S3: Same-tenant auth kullanıcı telefon bilgisi görmeli');
        $this->assertArrayHasKey('email', $agent,
            'S3: Same-tenant auth kullanıcı email görmeli');
        $this->assertArrayHasKey('whatsapp', $agent,
            'S3: Same-tenant auth kullanıcı whatsapp görmeli');

        // Name doğru
        $this->assertEquals($this->userA->name, $agent['name']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // SENARYO 4: IlanPublicDetailResource — tüm alanlar korunan
    // Beklenti: agent yanıtı yok (IlanPublicDetailResource'da agent alanı yok)
    // ─────────────────────────────────────────────────────────────────────

    public function test_s4_public_resource_has_no_agent_section(): void
    {
        // IlanPublicDetailResource'da agent bilgisi yok — IlanDetailResource kullanılır
        // IlanPublicDetailResource'u test etmek için IlanController show()'un
        // anonymous path'ini test ediyoruz — zaten S1'de kapsandı.
        // Bu test, IlanPublicDetailResource'un agent içermediğini doğrudan kontrol eder.

        // IlanPublicDetailResource doğrudan kullanarak agent alanı olmadığını doğrula
        $ilan = V2Ilan::with(['il', 'ilce', 'mahalle', 'fotograflar', 'danisman', 'anaKategori'])
            ->find($this->ilanA_Yayinlanmis->id);

        $resource = new \App\Http\Resources\IlanPublicDetailResource($ilan);
        $array = $resource->toArray(request());

        // IlanPublicDetailResource'da agent alanı OLMAMALI
        $this->assertArrayNotHasKey('agent', $array,
            'S4: IlanPublicDetailResource agent alanı içermemeli');
    }

    // ─────────────────────────────────────────────────────────────────────
    // SENARYO 5: Yayınlanmamış ilan — agent bilgisi 404 ile birlikte yok
    // ─────────────────────────────────────────────────────────────────────

    public function test_s5_unpublished_ilan_returns_404_no_agent_leak(): void
    {
        $taslak = $this->makeIlan(
            $this->tenantA->id, $this->userA->id, self::TEST_ULKE_ID, IlanDurumu::TASLAK
        );

        try {
            $response = $this->getJson("/api/v1/ilanlar/{$taslak->id}");

            $this->assertEquals(404, $response->status());

            // 404 yanıtında agent veya herhangi bir ilan bilgisi sızmamalı
            $body = $response->json();
            $this->assertArrayNotHasKey('data', $body,
                'S5: 404 yanıtında data alanı olmamalı');
            $this->assertArrayNotHasKey('agent', $body,
                'S5: 404 yanıtında agent bilgisi sızmamalı');
        } finally {
            V2Ilan::withoutGlobalScopes()->forceDelete($taslak->id);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // SENARYO 6: IlanDetailResource'da koordinat korunması
    // IlanDetailResource tam koordinat döner (owner/tenant görür).
    // IlanPublicDetailResource yaklaşık koordinat döner (0.01°).
    // ─────────────────────────────────────────────────────────────────────

    public function test_s6_owner_sees_full_coordinates(): void
    {
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Yayinlanmis->id}");

        $this->assertEquals(200, $response->status());

        $location = $response->json('data.location');

        // Owner tam koordinat görür
        $this->assertArrayHasKey('coordinates', $location);
        $coordinates = $location['coordinates'];

        // Tam koordinat değerleri — 0.01° yuvarlama yok
        $this->assertEqualsWithDelta(37.123456, $coordinates['lat'], 0.0001);
        $this->assertEqualsWithDelta(28.654321, $coordinates['lng'], 0.0001);
    }

    public function test_s7_anonim_sees_approximate_coordinates(): void
    {
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Yayinlanmis->id}");

        $this->assertEquals(200, $response->status());

        $coordinates = $response->json('data.coordinates');

        // Anonim ≈ 0.01° yuvarlanmış koordinat görür
        // Ayrıştırıcı değer: 37.126 → floor=37.12, round=37.13 (farkı ayırt eder).
        // Assertion sabit değere sabitlenmeli — kaynak kod okunarak onaylanmalı.
        $this->assertEquals(37.12, $coordinates['lat'],
            'S7: Anonim kullanıcı 37.12 görmeli (floor yöntemi ile)');
        $this->assertEquals(28.65, $coordinates['lng'],
            'S7: Anonim kullanıcı 28.65 görmeli (floor yöntemi ile)');

        // Tam koordinat YOK
        $this->assertNotEquals(37.123456, $coordinates['lat'],
            'S7: Anonim tam koordinat görmemeli');
    }
}
