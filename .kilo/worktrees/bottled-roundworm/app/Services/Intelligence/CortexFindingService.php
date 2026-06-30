<?php

namespace App\Services\Intelligence;

use App\DTOs\CortexFinding;
use App\Enums\FindingDecision;
use App\Enums\FindingSeverity;
use App\Services\Ups\UpsFeatureGovernanceService;
use Illuminate\Support\Facades\Log;

/**
 * CortexFindingService — SAB2/SAB3 Decision Engine
 *
 * Collects and normalizes findings from all sources:
 * - UPS Feature Health (orphaned, archived-but-assigned, inactive-but-assigned)
 * - Authority drift (schema mismatches)
 * - Template integrity
 *
 * SAB3: Each finding includes explanation, signals, and confidence scoring.
 */
class CortexFindingService
{
    public function __construct(
        private UpsFeatureGovernanceService $upsGovernance,
    ) {}

    /**
     * Collect all findings from registered sources
     *
     * @return CortexFinding[]
     */
    public function collectAll(): array
    {
        $findings = [];

        $sources = [
            'ups_health' => fn () => $this->collectUpsHealthFindings(),
            'authority_drift' => fn () => $this->collectAuthorityDriftFindings(),
            'template_integrity' => fn () => $this->collectTemplateIntegrityFindings(),
        ];

        foreach ($sources as $sourceName => $collector) {
            try {
                $sourceFindings = $collector();
                $findings = array_merge($findings, $sourceFindings);
            } catch (\Throwable $e) {
                Log::warning('CortexFindingService: source failed', [
                    'source' => $sourceName,
                    'hata_mesaji' => $e->getMessage(),
                ]);
            }
        }

        return $findings;
    }

    /**
     * Collect findings from a specific source
     *
     * @return CortexFinding[]
     */
    public function collectFrom(string $source): array
    {
        return match ($source) {
            'ups_health' => $this->collectUpsHealthFindings(),
            'authority_drift' => $this->collectAuthorityDriftFindings(),
            'template_integrity' => $this->collectTemplateIntegrityFindings(),
            default => [],
        };
    }

    /**
     * UPS Feature Health findings
     *
     * @return CortexFinding[]
     */
    private function collectUpsHealthFindings(): array
    {
        $summary = $this->upsGovernance->getSummaryReport();
        $findings = [];

        if ($summary['orphaned_count'] > 0) {
            $count = $summary['orphaned_count'];
            $findings[] = CortexFinding::create([
                'source' => 'ups',
                'domain' => 'feature_health',
                'severity' => FindingSeverity::LOW,
                'title' => "{$count} özellik hiçbir template'e atanmamış (orphan)",
                'reason' => "Orphan özellikler gereksiz şişkinliğe neden olur. İnceleme ile temizlenebilir.",
                'target' => 'governance.feature_health',
                'recommended_action' => 'review_orphaned',
                'risk' => 'low',
                'decision' => FindingDecision::AUTO_RUN,
                'meta' => ['count' => $count],
                'explanation_summary' => "{$count} özellik hiçbir template'e atanmamış. Bunlar sistem şişkinliğine neden oluyor ve temizlenebilir.",
                'signals' => ['orphaned_features_detected', 'no_template_reference'],
                'confidence' => $count <= 5 ? 0.9 : ($count <= 20 ? 0.75 : 0.6),
                'rule_name' => 'ups_orphan_detection',
            ]);
        }

        if ($summary['archived_but_assigned'] > 0) {
            $count = $summary['archived_but_assigned'];
            $findings[] = CortexFinding::create([
                'source' => 'ups',
                'domain' => 'feature_health',
                'severity' => FindingSeverity::MEDIUM,
                'title' => "{$count} arşivlenmiş özellik hala atanmış",
                'reason' => "Arşivlenmiş özellikler template'lere atanmış durumda. Atama kaldırılmalı.",
                'target' => 'governance.feature_health',
                'recommended_action' => 'deassign_archived',
                'risk' => 'medium',
                'decision' => FindingDecision::NEEDS_REVIEW,
                'meta' => ['count' => $count],
                'explanation_summary' => "{$count} arşivlenmiş özellik hala aktif template'lere atanmış. Bu durum wizard'da tutarsızlık yaratabilir.",
                'signals' => ['archived_features_assigned', 'template_inconsistency'],
                'confidence' => 0.85,
                'rule_name' => 'ups_archived_assignment',
            ]);
        }

        if ($summary['inactive_but_assigned'] > 0) {
            $count = $summary['inactive_but_assigned'];
            $findings[] = CortexFinding::create([
                'source' => 'ups',
                'domain' => 'feature_health',
                'severity' => FindingSeverity::MEDIUM,
                'title' => "{$count} inaktif özellik hala atanmış",
                'reason' => "İnaktif özellikler wizard'da görünmemeli ama template'e atanmış.",
                'target' => 'governance.feature_health',
                'recommended_action' => 'review_inactive_assigned',
                'risk' => 'medium',
                'decision' => FindingDecision::NEEDS_REVIEW,
                'meta' => ['count' => $count],
                'explanation_summary' => "{$count} inaktif özellik wizard'da görünmemesine rağmen template'e atanmış. Bu atamalar kaldırılmalı veya özellik yeniden aktifleştirilmeli.",
                'signals' => ['inactive_features_assigned', 'wizard_visibility_mismatch'],
                'confidence' => 0.80,
                'rule_name' => 'ups_inactive_assignment',
            ]);
        }

        if ($summary['deprecated_assigned'] > 0) {
            $count = $summary['deprecated_assigned'];
            $findings[] = CortexFinding::create([
                'source' => 'ups',
                'domain' => 'feature_health',
                'severity' => FindingSeverity::LOW,
                'title' => "{$count} kullanımdan kaldırılmış özellik hala atanmış",
                'reason' => "Deprecated özellikler ileride kaldırılacak. Planlı geçiş yapılmalı.",
                'target' => 'governance.feature_health',
                'recommended_action' => 'plan_deprecated_migration',
                'risk' => 'low',
                'decision' => FindingDecision::AUTO_RUN,
                'meta' => ['count' => $count],
                'explanation_summary' => "{$count} kullanımdan kaldırılmış özellik hala atanmış durumda. Planlı geçiş ile kaldırılmalı.",
                'signals' => ['deprecated_features_assigned', 'migration_needed'],
                'confidence' => 0.90,
                'rule_name' => 'ups_deprecated_assignment',
            ]);
        }

        return $findings;
    }

    /**
     * Authority drift findings — check .sab/authority.json vs config/authority.json
     *
     * @return CortexFinding[]
     */
    private function collectAuthorityDriftFindings(): array
    {
        $findings = [];

        $sabAuthority = base_path('.sab/authority.json');
        $configAuthority = config_path('authority.json');

        if (!is_file($sabAuthority) || !is_file($configAuthority)) {
            return $findings;
        }

        $sabData = json_decode(file_get_contents($sabAuthority), true);
        $configData = json_decode(file_get_contents($configAuthority), true);

        if (!is_array($sabData) || !is_array($configData)) {
            return $findings;
        }

        $sabKeys = $this->flattenKeys($sabData);
        $configKeys = $this->flattenKeys($configData);

        $driftKeys = array_diff($sabKeys, $configKeys);
        $missingKeys = array_diff($configKeys, $sabKeys);

        if (count($driftKeys) > 0) {
            $keyCount = count($driftKeys);
            $findings[] = CortexFinding::create([
                'source' => 'authority',
                'domain' => 'schema_drift',
                'severity' => FindingSeverity::HIGH,
                'title' => $keyCount . " authority anahtarı SAB'da var ama config'de yok",
                'reason' => "SAB authority.json ile config/authority.json arasında drift tespit edildi.",
                'target' => 'authority.schema_sync',
                'recommended_action' => 'sync_authority',
                'risk' => 'high',
                'decision' => FindingDecision::BLOCKED,
                'meta' => ['drift_keys' => array_values(array_slice($driftKeys, 0, 20))],
                'explanation_summary' => "SAB authority.json'da {$keyCount} anahtar var ama config/authority.json'da yok. Bu bir schema drift durumu ve veri bütünlüğü riski oluşturuyor.",
                'signals' => ['authority_schema_drift', 'config_mismatch', 'data_integrity_risk'],
                'confidence' => $keyCount <= 3 ? 0.95 : 0.85,
                'rule_name' => 'authority_drift_detection',
            ]);
        }

        if (count($missingKeys) > 0) {
            $keyCount = count($missingKeys);
            $findings[] = CortexFinding::create([
                'source' => 'authority',
                'domain' => 'schema_drift',
                'severity' => FindingSeverity::MEDIUM,
                'title' => $keyCount . " authority anahtarı config'de var ama SAB'da yok",
                'reason' => "Config authority.json'da tanımlı anahtarlar SAB'da eksik.",
                'target' => 'authority.schema_sync',
                'recommended_action' => 'update_sab_authority',
                'risk' => 'medium',
                'decision' => FindingDecision::NEEDS_REVIEW,
                'meta' => ['missing_keys' => array_values(array_slice($missingKeys, 0, 20))],
                'explanation_summary' => "Config'de tanımlı {$keyCount} anahtar SAB'da eksik. SAB authority dosyası güncellenmelidir.",
                'signals' => ['sab_missing_keys', 'config_sab_desync'],
                'confidence' => 0.80,
                'rule_name' => 'authority_missing_keys',
            ]);
        }

        return $findings;
    }

    /**
     * Template integrity findings — check for broken schema assignments
     *
     * @return CortexFinding[]
     */
    private function collectTemplateIntegrityFindings(): array
    {
        $findings = [];

        try {
            $brokenAssignments = \App\Models\FeatureAssignment::query()
                ->whereDoesntHave('feature')
                ->count();

            if ($brokenAssignments > 0) {
                $findings[] = CortexFinding::create([
                    'source' => 'template',
                    'domain' => 'integrity',
                    'severity' => FindingSeverity::HIGH,
                    'title' => "{$brokenAssignments} kırık özellik ataması (feature silinmiş)",
                    'reason' => "Feature assignment kayıtları silinen feature'lara referans veriyor.",
                    'target' => 'template.feature_assignments',
                    'recommended_action' => 'cleanup_broken_assignments',
                    'risk' => 'high',
                    'decision' => FindingDecision::BLOCKED,
                    'meta' => ['count' => $brokenAssignments],
                    'explanation_summary' => "{$brokenAssignments} feature assignment kaydı silinmiş feature'lara referans veriyor. Bu kırık referanslar wizard ve template sistemini bozabilir.",
                    'signals' => ['broken_feature_references', 'template_integrity_violation', 'orphaned_assignments'],
                    'confidence' => $brokenAssignments <= 5 ? 0.95 : 0.85,
                    'rule_name' => 'template_broken_refs',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('CortexFindingService: template integrity check failed', [
                'hata_mesaji' => $e->getMessage(),
            ]);
        }

        return $findings;
    }

    /**
     * Flatten nested array keys with dot notation
     */
    private function flattenKeys(array $data, string $prefix = ''): array
    {
        $keys = [];
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            $keys[] = $fullKey;
            if (is_array($value) && !array_is_list($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $fullKey));
            }
        }
        return $keys;
    }
}
