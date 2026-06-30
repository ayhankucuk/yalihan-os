<?php

namespace App\Services\Ups;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\UpsTemplate;
use App\Models\TemplateChangeLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * 🏥 UPS Health Optimizer Service
 *
 * Responsibility: Synchronizes the Category x Publication Type matrix with real UpsTemplates.
 * Ensures the system moves from "Empty/Demo" to "Populated/SSOT".
 *
 * Context7 Compliant: ✅
 */
class UpsHealthOptimizerService
{
    /**
     * Optimize all missing nodes in the matrix
     *
     * @return array Summary of actions taken
     */
    public function optimizeAll(): array
    {
        $registry = resolve(\App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry::class);

        // ✅ SAB: Hard Lock Protection (@rules 8)
        if ($registry->isSystemCompromised()) {
            throw new \App\Exceptions\CriticalGovernanceException("CONTEXT7 HARD LOCK: System is compromised. Optimization disabled.");
        }

        $activeVersion = $registry->getActiveVersion();
        $snapshotService = resolve(\App\Modules\GovernanceCore\Core\ConfigSnapshotService::class);

        // 1. Identify missing nodes
        $categories = IlanKategori::active()->with('yayinTipleri')->get();
        $allTemplates = UpsTemplate::active()->get()->groupBy(fn($t) => $t['kategori_id'] . '-' . $t['yayin_tipi_id']);

        $missingNodes = [];
        foreach ($categories as $category) {
            foreach ($category->yayinTipleri as $yayinTipi) {
                $lookupKey = $category->id . '-' . $yayinTipi->id;
                if (!$allTemplates->has($lookupKey)) {
                    $missingNodes[] = [
                        'category' => $category,
                        'yayinTipi' => $yayinTipi,
                    ];
                }
            }
        }

        if (empty($missingNodes)) {
            return [
                'total_nodes' => count($allTemplates),
                'created' => 0,
                'status' => 'NO_CHANGES_NEEDED',
            ];
        }

        // 2. Create DRAFT version proposal
        return DB::transaction(function () use ($activeVersion, $missingNodes, $snapshotService, $allTemplates) {
            $version = \App\Models\PropertyConfigVersion::create([
                'version_hash' => Str::random(64),
                'yonetim_durumu' => \App\Modules\GovernanceCore\Core\VersionStateMachine::DURUM_TASLAK,
                'description' => 'Matrix Optimizer Proposal: Auto-detect missing nodes (' . count($missingNodes) . ')',
                'parent_version_hash' => $activeVersion?->version_hash,
                'created_by' => Auth::id() ?? 1,
            ]);

            // 3. Build Proposed Snapshot
            // Start with current active snapshot if available, otherwise initialized structure matches ConfigSnapshotService
            $baseSnapshot = $activeVersion->snapshot_json ?? $snapshotService->capture();

            $newSnapshot = $baseSnapshot;
            // Ensure templates array exists
            if (!isset($newSnapshot['templates'])) {
                $newSnapshot['templates'] = [];
            }

            foreach ($missingNodes as $node) {
                $category = $node['category'];
                $yayinTipi = $node['yayinTipi'];

                $newSnapshot['templates'][] = [
                    'id' => null, // New template (Validation checks for null ID to know it's a draft item)
                    'name' => "{$category->name} - {$yayinTipi->ad}", // Add name for UI display
                    'kategori_id' => $category->id,
                    'yayin_tipi_id' => $yayinTipi->id,
                    'yayin_tipi_sablonu_id' => $yayinTipi->id,
                    // 'template_json' key is not unused in UpsTemplate model but useful for draft preview?
                    // Actually ConfigSnapshotService captures model attributes.
                    // UpsTemplate doesn't have template_json column in V3?
                    // Let's stick to standard attributes captured by ConfigSnapshotService.
                    'aktiflik_durumu' => true,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                    '_is_draft' => true, // Marker for UI
                ];
            }

            // Update timestamp
            $newSnapshot['meta']['timestamp'] = now()->toIso8601String();

            // 4. Seal the DRAFT snapshot using SSOT calculation
            $signature = \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature($newSnapshot);

            $version->update([
                'snapshot_json' => $newSnapshot,
                'signature' => $signature,
            ]);

            return [
                'total_nodes' => count($allTemplates) + count($missingNodes),
                'created' => count($missingNodes),
                'version_id' => $version->id,
                'status' => 'DRAFT_PROMOTED',
            ];
        });
    }

    /**
     * Get default template JSON based on master configuration
     */
    protected function getDefaultTemplateJson(YayinTipiSablonu $yayinTipi): array
    {
        return [
            'master_ref' => $yayinTipi->slug,
            'ui_ipuclari' => [
                'placeholder' => "{$yayinTipi->ad} için ilan girişi...",
                'label_override' => null,
            ],
            'validation_rules' => [],
            'auto_created' => true,
            'optimized_at' => now()->toISOString(),
        ];
    }
}
