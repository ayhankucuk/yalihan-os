<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PropertyConfigVersion;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;
use Illuminate\Support\Str;

/**
 * Governance Bypass Test
 *
 * Verifies that manual database tampering triggers signature validation failure.
 *
 * Sprint 9.3 Security Layer
 * @group skip-until-migration-complete
 */
class GovernanceBypassTest extends TestCase
{

    /** @test */
    public function it_detects_manual_database_tampering()
    {
        // 1. Create ACTIVE snapshot
        $snapshotService = resolve(ConfigSnapshotService::class);
        $snapshot = $snapshotService->capture();
        $signature = ConfigSnapshotService::computeSignature($snapshot);

        $version = PropertyConfigVersion::create([
            'version_hash' => Str::random(64),
            'yonetim_durumu' => 'AKTIF',
            'description' => 'Test Active Version',
            'created_by' => 1,
            'snapshot_json' => $snapshot,
            'signature' => $signature,
        ]);

        // 2. Verify registry can load it
        $registry = resolve(ActiveConfigRegistry::class);
        $activeVersion = $registry->getActiveVersion();

        $this->assertNotNull($activeVersion);
        $this->assertEquals($version->id, $activeVersion->id);

        // 3. Tamper with database (bypass governance)
        $tamperedSnapshot = $snapshot;
        $tamperedSnapshot['meta']['timestamp'] = now()->addMinutes(10)->toIso8601String();

        $version->update([
            'snapshot_json' => $tamperedSnapshot,
            // Signature NOT updated → mismatch
        ]);

        // 4. Clear cache and try to load again
        app()->forgetInstance(ActiveConfigRegistry::class);

        // 5. Expect signature validation failure
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CONTEXT7 SECURITY ALERT');

        $newRegistry = resolve(ActiveConfigRegistry::class);
        $newRegistry->getActiveVersion(); // Should throw
    }

    /** @test */
    public function it_allows_valid_snapshot_with_correct_signature()
    {
        // 1. Create properly signed snapshot
        $snapshotService = resolve(ConfigSnapshotService::class);
        $snapshot = $snapshotService->capture();
        $signature = ConfigSnapshotService::computeSignature($snapshot);

        $version = PropertyConfigVersion::create([
            'version_hash' => Str::random(64),
            'yonetim_durumu' => 'AKTIF',
            'description' => 'Valid Active Version',
            'created_by' => 1,
            'snapshot_json' => $snapshot,
            'signature' => $signature,
        ]);

        // 2. Load via registry (should succeed)
        $registry = resolve(ActiveConfigRegistry::class);
        $activeVersion = $registry->getActiveVersion();

        $this->assertNotNull($activeVersion);
        $this->assertEquals($version->id, $activeVersion->id);
        $this->assertEquals($signature, $activeVersion->signature);
    }

    /** @test */
    public function it_rejects_snapshot_with_tampered_signature()
    {
        // 1. Create snapshot with WRONG signature
        $snapshotService = resolve(ConfigSnapshotService::class);
        $snapshot = $snapshotService->capture();

        $version = PropertyConfigVersion::create([
            'version_hash' => Str::random(64),
            'yonetim_durumu' => 'AKTIF',
            'description' => 'Tampered Signature Version',
            'created_by' => 1,
            'snapshot_json' => $snapshot,
            'signature' => 'FAKE_SIGNATURE_12345', // Invalid
        ]);

        // 2. Expect signature validation failure
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CONTEXT7 SECURITY ALERT');

        $registry = resolve(ActiveConfigRegistry::class);
        $registry->getActiveVersion();
    }
}
