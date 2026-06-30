<?php

namespace Tests\Feature;

use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Models\IlanKategori;
use App\Models\PropertyConfigVersion;
use App\Models\RuleDefinition;
use App\Models\UpsTemplate;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;
use Tests\TestCase;

use Tests\Support\ResetsGovernanceState;

/**
 * Pre-existing: requires full DB/app stack unavailable in standard CI.
 *
 * @group skip-until-migration-complete
 */
class UpsDeterminismTest extends TestCase
{
    use ResetsGovernanceState;

    protected function setUp(): void
    {
        parent::setUp();
        // Force reset registry for EACH iteration in some tests if needed,
        // but trait handles it for EACH test method.
        // Setup base data
        $user = User::factory()->create(['name' => 'Admin User']);
        $this->actingAs($user);

        // 1. Create Base Version first
        $version = PropertyConfigVersion::create([
            'version_hash' => 'v1_baseline',
            'yonetim_durumu' => VersionStateMachine::DURUM_TASLAK,
            'is_immutable' => false,
            'snapshot_json' => ['rules' => [], 'templates' => [], 'assignments' => []],
            'signature' => 'dummy_initial'
        ]);

        $kategori = IlanKategori::create([
            'name' => 'Test Kategori',
            'seviye' => 0,
            'aktiflik_durumu' => true
        ]);

        $yayinTipi = YayinTipiSablonu::create([
            'ad' => 'Test Yayin Tipi',
            'slug' => 'test-yayin-tipi',
            'aktiflik_durumu' => true
        ]);

        UpsTemplate::create([
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id,
            'yayin_tipi_sablonu_id' => $yayinTipi->id,
            'template_json' => ['foo' => 'bar'],
            'template_hash' => md5(json_encode(['foo' => 'bar']))
        ]);

        RuleDefinition::create([
            'version_id' => $version->id,
            'name' => 'Test Rule',
            'rule_type' => 'WHITELIST',
            'rule_config' => [
                'priority' => 1,
                'actions' => ['assign_template' => 123]
            ],
            'aktif' => true
        ]);

        // 2. Capture Real Snapshot and Update Version
        $snapshotService = new ConfigSnapshotService();
        $snapshot = $snapshotService->capture();

        $version->update([
            'snapshot_json' => $snapshot,
            'signature' => $snapshotService->calculateSignature($snapshot),
            'yonetim_durumu' => VersionStateMachine::DURUM_AKTIF
        ]);

        resolve(ActiveConfigRegistry::class)->clear();
    }

    /**
     * Test determinism across 100 iterations
     *
     * @group slow
     */
    public function test_health_matrix_determinism_at_scale()
    {
        $this->withoutMiddleware();
        $iterations = 1000; // Strict Protocol Compliance
        $signatures = [];

        for ($i = 0; $i < $iterations; $i++) {
            $response = $this->get('/admin/ups/health');
            if (!$response->isSuccessful()) {
                $content = $response->getContent();
                throw new \Exception("Determinism test failed at iteration {$i} with error. Content: " . substr($content, 0, 500));
            }
            $response->assertOk();

            $healthMatrix = $response->viewData('healthMatrix');
            $heatmapData = $response->viewData('heatmapData');
            $stats = $response->viewData('stats');

            // Create a deterministic signature of the response data
            // We strip any timestamps if they exist (though they shouldn't be in these arrays)
            $currentSignature = md5(json_encode([
                'healthMatrix' => $healthMatrix,
                'heatmapData' => $heatmapData,
                'stats' => $stats
            ]));

            $signatures[] = $currentSignature;
        }

        $uniqueSignatures = array_unique($signatures);

        $this->assertCount(1, $uniqueSignatures, 'Health Matrix output MUST be deterministic across iterations. Found multiple signatures: ' . implode(', ', $uniqueSignatures));
    }

    /**
     * Verify that the Engine uses the Governed Registry (Snapshot)
     */
    public function test_engine_resolution_uses_governed_registry()
    {
        // 1. Resolve Orchestrator from Container (w/ Real GovernedRuleRegistry)
        $orchestrator = resolve(\App\Modules\GovernanceCore\Core\EngineOrchestrator::class);

        // 2. Create Context
        $context = \App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext::create(
            categoryId: \App\Models\IlanKategori::first()->id,
            publishTypeId: \App\Models\YayinTipiSablonu::first()->id
        );

        // 3. Resolve
        $result = $orchestrator->resolve($context);

        // 4. Assert
        $this->assertInstanceOf(\App\Domain\PropertyHub\Resolution\DTOs\ResolutionResult::class, $result);

        // Check if rules were applied.
        // We added a WHITELIST rule in setUp with priority 1.
        // The Engine trace should reflect this or the result/meta should show rules evaluated.

        // Since our basic Engine implementation might not expose "rules applied" easily in output structure
        // without inspecting trace, we check trace.
        $this->assertNotEmpty($result->trace);

        // The trace should contain "Loaded 1 rules from registry" (since we created 1 rule in setUp)
        // Adjust expectation based on setup:
        // RuleDefinition::create(['version_id' => $version->id, ... 'active' => true]);

        // Find trace entry about loaded rules
        $loadedRulesEntry = collect($result->trace)->first(fn($t) => str_contains($t, 'Loaded'));
        $this->assertStringContainsString('Loaded 1 rules', $loadedRulesEntry, 'Engine should load 1 rule from snapshot');
    }
}
