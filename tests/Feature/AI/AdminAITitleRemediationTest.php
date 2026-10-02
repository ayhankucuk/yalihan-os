<?php

namespace Tests\Feature\AI;

use App\Models\Ilan;
use App\Models\Role;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\AI\OllamaService;
use App\Services\AI\YalihanCortex;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAITitleRemediationTest extends TestCase
{
    protected Tenant $tenant;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai-cost-guard.enabled' => false]);
        config(['ups_ai.aktiflik_durumu' => true]);
        config(['ups_ai.assist_aktiflik_durumu' => true]);

        $this->tenant = Tenant::firstOrCreate(
            ['domain' => 'test-tenant.yalihan.local'],
            ['name' => 'Test Tenant', 'status' => 'active']
        );

        $this->adminUser = User::factory()->create([
            'email' => 'admin-ai-test-' . uniqid() . '@yalihan.com',
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);

        $this->assignAdminRole($this->adminUser);
    }

    private function assignAdminRole(User $user): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web']
        );

        DB::table('model_has_roles')->insertOrIgnore([
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }

    /**
     * R0: POST /admin/ai/title reaches the repaired path without undefined-method Error.
     * R1: Valid same-tenant request returns the endpoint's intended successful response contract.
     * R2: The title-generation execution is invoked exactly once.
     */
    public function test_r0_r1_r2_title_generation_success_contract(): void
    {
        $mockTitles = [
            'Bodrum Gümüşlükte Satılık Lüks Deniz Manzaralı Villa',
            'Gümüşlük Merkezde Özel Havuzlu Müstakil Villa',
            'Yalıkavak Yakını Panoramik Manzaralı Modern Villa',
        ];

        $this->mock(OllamaService::class, function ($mock) use ($mockTitles) {
            $mock->shouldReceive('generateTitle')
                ->once()
                ->andReturn($mockTitles);
        });

        $this->app->forgetInstance(YalihanCortex::class);

        $payload = [
            'kategori' => 'Konut',
            'il' => 'Muğla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Gümüşlük',
            'yayin_tipi_id' => 'Satılık',
            'ai_tone' => 'seo',
        ];

        $response = $this->actingAs($this->adminUser, 'web')
            ->postJson('/admin/ai/title', $payload);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('Başlık önerileri oluşturuldu', $data['message']);
        $this->assertArrayHasKey('data', $data);

        $innerData = $data['data'];
        $this->assertArrayHasKey('text', $innerData);
        $this->assertArrayHasKey('alternatives', $innerData);
        $this->assertArrayHasKey('variants', $innerData);
        $this->assertArrayHasKey('count', $innerData);
        $this->assertArrayHasKey('provider', $innerData);
        $this->assertArrayHasKey('model', $innerData);

        $this->assertEquals($mockTitles[0], $innerData['text']);
        $this->assertCount(3, $innerData['alternatives']);
        $this->assertCount(3, $innerData['variants']);
        $this->assertEquals(3, $innerData['count']);
    }

    /**
     * R3: Provider/service failure returns the CURRENT intended controlled failure contract
     * rather than an uncaught PHP Error.
     */
    public function test_r3_provider_failure_returns_controlled_error_contract(): void
    {
        $this->mock(OllamaService::class, function ($mock) {
            $mock->shouldReceive('generateTitle')
                ->once()
                ->andThrow(new \RuntimeException('Connection refused to AI provider'));
        });

        $this->app->forgetInstance(YalihanCortex::class);

        $payload = [
            'kategori' => 'Konut',
            'il' => 'Muğla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Gümüşlük',
            'ai_tone' => 'seo',
        ];

        $response = $this->actingAs($this->adminUser, 'web')
            ->postJson('/admin/ai/title', $payload);

        // Controlled error returns 500 JSON contract with success = false
        $response->assertStatus(500);

        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertEquals('Başlık oluşturulamadı', $data['message']);
        $this->assertNull($data['data']);
        $this->assertEquals('Başlık oluşturulamadı', $data['error']['message']);
    }

    /**
     * R4: No listing/database mutation occurs merely from requesting a title.
     */
    public function test_r4_no_listing_or_database_mutation_occurs(): void
    {
        $this->mock(OllamaService::class, function ($mock) {
            $mock->shouldReceive('generateTitle')
                ->once()
                ->andReturn(['Bodrum Satılık Villa Fırsatı']);
        });

        $this->app->forgetInstance(YalihanCortex::class);

        $ilanCountBefore = Ilan::count();
        $userCountBefore = User::count();

        $payload = [
            'kategori' => 'Konut',
            'il' => 'Muğla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Gümüşlük',
            'ai_tone' => 'seo',
        ];

        $response = $this->actingAs($this->adminUser, 'web')
            ->postJson('/admin/ai/title', $payload);

        $response->assertStatus(200);

        $this->assertEquals($ilanCountBefore, Ilan::count());
        $this->assertEquals($userCountBefore, User::count());
    }
}
