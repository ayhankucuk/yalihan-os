<?php

namespace Tests\Feature\Domain\PropertyHub;

use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Models\Feature;
use App\Models\PropertyConfigVersion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * @group property-hub
 * @group skip-until-migration-complete
 */
class ArchitectureComplianceTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        // Clear governance caches
        Cache::forget('governance.active_version');
        ActiveConfigRegistry::clearStaticState();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /**
     * Pillar 3: AI Generation must require an Active Snapshot.
     */
    public function test_ai_suggest_template_requires_active_snapshot()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. No Active Version -> Expect 422
        $response = $this->postJson(route('admin.property-hub.templates.ai-suggest'), [
            'category_name' => 'Villa',
            'description' => 'Luxury villa with pool'
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'AI Üretimi için Aktif Konfigürasyon bulunamadı. Lütfen önce bir versiyonu aktif edin.');
    }

    /**
     * Pillar 3: AI Generation works with Active Snapshot.
     */
    public function test_ai_suggest_template_works_with_active_snapshot()
    {
        // 1. Setup Active Version with Features
        $feature = Feature::factory()->create(['name' => 'Havuz', 'aktiflik_durumu' => true]);

        $snapshot = [
            'meta' => ['timestamp' => now()->toIso8601String()],
            'rules' => [],
            'templates' => [],
            'master_templates' => [],
            'features' => [
                [
                    'id' => $feature->id,
                    'name' => 'Havuz',
                    'slug' => 'havuz',
                    'type' => 'checkbox',
                    'is_active' => true,
                    // other fields optional for this test
                ]
            ]
        ];

        $version = PropertyConfigVersion::create([
            'tenant_id' => 'SYSTEM',
            'version_hash' => 'hash_123',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => $snapshot,
            'signature' => \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature($snapshot),
            'applied_at' => now(),
        ]);

        // Mock AI Service to avoid external call
        $this->mock(\App\Services\AI\OllamaService::class, function ($mock) {
            $mock->shouldReceive('isHealthy')->andReturn(true);
            $mock->shouldReceive('generateTemplateSuggestions')->andReturn([
                'groups' => [
                    [
                        'name' => 'General',
                        'features' => [
                            ['name' => 'Havuz', 'type' => 'checkbox']
                        ]
                    ]
                ]
            ]);
        });

        $user = User::factory()->create();
        $this->actingAs($user);

        // Force tenant ID to match
        Config::set('app.tenant_id', 'SYSTEM');

        $response = $this->postJson(route('admin.property-hub.templates.ai-suggest'), [
            'category_name' => 'Villa',
            'description' => 'Luxury villa'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.groups.0.features.0.exists', true)
            ->assertJsonPath('data.groups.0.features.0.id', $feature->id);
    }

    /**
     * Pillar 5: Tenant Isolation.
     */
    public function test_active_config_is_tenant_isolated()
    {
        // Tenant A Version
        PropertyConfigVersion::create([
            'tenant_id' => 'TENANT_A',
            'version_hash' => 'hash_A',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => ['tenant' => 'A'],
            'signature' => 'sig_A', // Mock signature for simplicity, registry will verify
        ]);

        // Tenant B Version
        PropertyConfigVersion::create([
            'tenant_id' => 'TENANT_B',
            'version_hash' => 'hash_B',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => ['tenant' => 'B'],
            'signature' => 'sig_B',
        ]);

        // Mock Integrity Check to bypass signature validation for this test
        $registry = $this->app->make(ActiveConfigRegistry::class);
        // We can't easily mock private method, but we can mock ConfigSnapshotService::computeSignature
        // OR ensure our signatures are valid.
        // Let's rely on valid signatures in setup if possible.
        // For this test, I'll Mock the ActiveConfigRegistry->verifyIntegrity method? No it's not macroable.
        // I will just let it fail if signature is wrong.

        // Let's create proper versions with util
        $service = new \App\Modules\GovernanceCore\Core\ConfigSnapshotService();

        $snapA = ['meta' => [], 'rules' => [], 'templates' => [], 'master_templates' => [], 'features' => []];
        $sigA = $service->calculateSignature($snapA);

        PropertyConfigVersion::where('tenant_id', 'TENANT_A')->update(['snapshot_json' => $snapA, 'signature' => $sigA]);

        $snapB = ['meta' => [], 'rules' => [], 'templates' => [], 'master_templates' => [], 'features' => [['id'=>1]]]; // Slight diff
        $sigB = $service->calculateSignature($snapB);

        PropertyConfigVersion::where('tenant_id', 'TENANT_B')->update(['snapshot_json' => $snapB, 'signature' => $sigB]);

        // Test Tenant A
        Config::set('app.tenant_id', 'TENANT_A');
        // Clear cache
        Cache::forget('gov_v2:TENANT_A:active_version');

        $versionA = $registry->getActiveVersion();
        $this->assertEquals('TENANT_A', $versionA->tenant_id);

        // Test Tenant B
        Config::set('app.tenant_id', 'TENANT_B');
        Cache::forget('gov_v2:TENANT_B:active_version');

        $versionB = $registry->getActiveVersion();
        $this->assertEquals('TENANT_B', $versionB->tenant_id);

        // Assert they are different
        $this->assertNotEquals($versionA->id, $versionB->id);
    }
}
