<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Services;

use App\Domain\PropertyHub\Infrastructure\YayinTipiSablonuSnapshotProvider;
use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Services\AutonomousDriftResponder;

/**
 * Versioned Drift Detection Service
 *
 * #58 Canonical resolution: Versiyon bazlı drift tespiti için kanonik implementasyon.
 * (Core\DriftDetectionService sistemin anlık durumunu tarar;
 *  bu servis belirli bir PropertyConfigVersion'ı karşılaştırır — PredictiveDriftAnalyzer için)
 *
 * Deterministically compares the database state against a versioned snapshot.
 * Identifies 3 types of drift:
 * A: Value Drift (DB differs from Snapshot)
 * B: Shadow Missing (Snapshot has it, DB doesn't)
 * C: Ungoverned (DB has it, Snapshot doesn't)
 */
class VersionedDriftDetectionService
{
    public function __construct(
        private readonly AutonomousDriftResponder $responder,
        private readonly YayinTipiSablonuSnapshotProvider $snapshotProvider
    ) {}
    /**
     * Compare a version against current database state for a specific tenant.
     */
    public function detect(PropertyConfigVersion $version, bool $otomatikYanit = true): array
    {
        $tenantId = $version->tenant_id;
        $snapshot = $version->snapshot_json ?? [];
        $templates = $snapshot['templates'] ?? [];
        $results = [
            'drifts' => [],
            'shadow_missing' => [],
            'ungoverned' => [],
        ];

        // 1. Fetch current DB state via canonical snapshot provider
        $dbTemplates = $this->snapshotProvider->getForTenant($tenantId);


        // 2. Scenario A & B: Check Snapshot items against DB
        foreach ($templates as $template) {
            $id = $template['id'];

            if (!$dbTemplates->has($id)) {
                // Scenario B: Shadow Missing (Snapshot knows it, DB lost it)
                $results['shadow_missing'][] = $id;
                continue;
            }

            $dbItem = (array) $dbTemplates->get($id);
            if ($this->hasDrift($template, $dbItem)) {
                // Scenario A: Value Drift
                $results['drifts'][] = [
                    'id' => $id,
                    'expected' => $template,
                    'actual' => $dbItem
                ];
            }
        }

        // 3. Scenario C: Ungoverned (DB has records not tracked by Snapshot)
        $snapshotIds = collect($templates)->pluck('id')->toArray();
        foreach ($dbTemplates as $id => $item) {
            if (!in_array($id, $snapshotIds)) {
                $results['ungoverned'][] = $id;
            }
        }

        // 4. Autonomous Response Cycle (optional for deterministic read-only flows)
        if ($otomatikYanit) {
            $this->responder->respond($version, $results);
        }

        return $results;
    }

    public function detectPassive(PropertyConfigVersion $version): array
    {
        return $this->detect($version, false);
    }

    public function detectActive(PropertyConfigVersion $version): array
    {
        return $this->detect($version, true);
    }

    /**
     * Canonical value drift check.
     *
     * DB::table() raw query bypass eder Eloquent cast'larini.
     * Bu nedenle tipler alan bazinda normalize edilir:
     * - aktiflik_durumu, display_order -> canonicalizeInt()
     * - ad, aciklama                  -> canonicalizeString()
     */
    private function hasDrift(array $snapshot, array $db): bool
    {
        foreach (['aktiflik_durumu', 'display_order'] as $key) {
            if ($this->canonicalizeInt($snapshot[$key] ?? null)
                !== $this->canonicalizeInt($db[$key] ?? null)) {
                return true;
            }
        }

        foreach (['ad', 'aciklama'] as $key) {
            if ($this->canonicalizeString($snapshot[$key] ?? null)
                !== $this->canonicalizeString($db[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize integer/boolean fields from raw DB output.
     * null ve bos string esit kabul edilir (DB NULL vs PHP null).
     */
    private function canonicalizeInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    /**
     * Normalize string fields from raw DB output.
     * null ve bos string esit kabul edilir (DB NULL vs PHP null).
     */
    private function canonicalizeString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return trim((string) $value);
    }
}
