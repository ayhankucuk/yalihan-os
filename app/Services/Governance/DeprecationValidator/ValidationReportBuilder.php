<?php

namespace App\Services\Governance\DeprecationValidator;

/**
 * Builds the final Coverage Validation Report from component results.
 *
 * Responsible for:
 * - Summary metrics calculation (total, moved_full, missing, etc.)
 * - Archive metadata validation results aggregation
 * - AI context isolation verification
 * - Final closure rule evaluation (MISSING=0, Archive isolation=PASS, Target role=PASS)
 * - PASS / PARTIAL / FAIL decision
 */
class ValidationReportBuilder
{
    /**
     * Build the complete validation report.
     *
     * @param array $archiveMetadataResult Output from ArchiveMetadataReader::read()
     * @param array $sectionInventory Output from SectionInventoryBuilder::build()
     * @param array $mappingValidation Output from TargetMappingValidator::validate()
     * @param string $archivePath Original archive file path
     * @param bool $strict If true, PARTIAL results in FAIL
     * @return array
     */
    public function build(
        array $archiveMetadataResult,
        array $sectionInventory,
        array $mappingValidation,
        string $archivePath,
        bool $strict = false
    ): array {
        $summary = $this->buildSummary($mappingValidation);
        $archiveValidation = $this->buildArchiveValidation($archiveMetadataResult);
        $contextIsolation = $this->buildContextIsolation($archiveMetadataResult, $archivePath);
        $finalDecision = $this->evaluateClosureRule($summary, $archiveValidation, $contextIsolation, $strict);

        return [
            'subject' => $archivePath,
            'validated_at' => now()->toIso8601String(),
            'summary' => $summary,
            'section_inventory' => $sectionInventory,
            'section_mapping' => $mappingValidation,
            'archive_validation' => $archiveValidation,
            'context_isolation' => $contextIsolation,
            'final_decision' => $finalDecision,
            'strict_mode' => $strict,
        ];
    }

    /**
     * Build summary metrics from mapping validation results.
     *
     * @param array $mappingValidation
     * @return array
     */
    private function buildSummary(array $mappingValidation): array
    {
        $counts = [
            'total' => count($mappingValidation),
            'moved_full' => 0,
            'moved_partial' => 0,
            'archived_only' => 0,
            'dropped_approved' => 0,
            'missing' => 0,
        ];

        foreach ($mappingValidation as $result) {
            $status = $result['coverage_status'] ?? TargetMappingValidator::STATUS_MISSING;

            match ($status) {
                TargetMappingValidator::STATUS_MOVED_FULL => $counts['moved_full']++,
                TargetMappingValidator::STATUS_MOVED_PARTIAL => $counts['moved_partial']++,
                TargetMappingValidator::STATUS_ARCHIVED_ONLY => $counts['archived_only']++,
                TargetMappingValidator::STATUS_DROPPED_APPROVED => $counts['dropped_approved']++,
                default => $counts['missing']++,
            };
        }

        return $counts;
    }

    /**
     * Build archive metadata validation results.
     *
     * @param array $archiveMetadataResult
     * @return array
     */
    private function buildArchiveValidation(array $archiveMetadataResult): array
    {
        $metadata = $archiveMetadataResult['metadata'] ?? [];

        return [
            'deprecated_metadata' => isset($metadata['deprecated']) && $metadata['deprecated'] === true
                ? 'PASS' : 'FAIL',
            'excluded_from_ai_context' => isset($metadata['excluded_from_ai_context']) && $metadata['excluded_from_ai_context'] === true
                ? 'PASS' : 'FAIL',
            'archived_from_lineage' => !empty($metadata['archived_from'])
                ? 'PASS' : 'FAIL',
            'archive_reason_present' => !empty($metadata['reason'])
                ? 'PASS' : 'FAIL',
            'usage_reference_only' => (isset($metadata['usage']) && $metadata['usage'] === 'reference-only')
                ? 'PASS' : 'WARN',
            'replaced_by_present' => !empty($metadata['replaced_by'])
                ? 'PASS' : 'WARN',
            'overall' => $archiveMetadataResult['valid'] ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Build AI context isolation check.
     *
     * @param array $archiveMetadataResult
     * @param string $archivePath
     * @return array
     */
    private function buildContextIsolation(array $archiveMetadataResult, string $archivePath): array
    {
        $metadata = $archiveMetadataResult['metadata'] ?? [];

        // Check 1: Archive is in legacy/ directory (not in active context)
        $inLegacyDir = str_contains($archivePath, '/legacy/');

        // Check 2: Not in .ai/context/ (active AI context directory)
        $notInActiveContext = !str_starts_with($archivePath, '.ai/context/');

        // Check 3: excluded_from_ai_context metadata
        $excludedMeta = isset($metadata['excluded_from_ai_context']) && $metadata['excluded_from_ai_context'] === true;

        // Check 4: deprecated flag
        $deprecatedFlag = isset($metadata['deprecated']) && $metadata['deprecated'] === true;

        $allPassed = $inLegacyDir && $notInActiveContext && ($excludedMeta || $deprecatedFlag);

        return [
            'in_legacy_directory' => $inLegacyDir ? 'PASS' : 'FAIL',
            'not_in_active_context' => $notInActiveContext ? 'PASS' : 'FAIL',
            'excluded_metadata' => $excludedMeta ? 'PASS' : 'WARN',
            'deprecated_flag' => $deprecatedFlag ? 'PASS' : 'FAIL',
            'overall' => $allPassed ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Evaluate the Final Closure Rule.
     *
     * Three conditions must be met simultaneously:
     * 1. MISSING = 0
     * 2. Archive isolation = PASS
     * 3. Target role validation = PASS (all sections)
     *
     * @param array $summary
     * @param array $archiveValidation
     * @param array $contextIsolation
     * @param bool $strict
     * @return array
     */
    private function evaluateClosureRule(
        array $summary,
        array $archiveValidation,
        array $contextIsolation,
        bool $strict
    ): array {
        $missingZero = $summary['missing'] === 0;
        $archivePass = $archiveValidation['overall'] === 'PASS';
        $isolationPass = $contextIsolation['overall'] === 'PASS';
        $noPartial = $summary['moved_partial'] === 0;

        // Determine decision
        if ($missingZero && $archivePass && $isolationPass) {
            if ($strict && !$noPartial) {
                $decision = 'FAIL';
                $reason = 'Strict mode: MOVED_PARTIAL sections exist';
            } else {
                $decision = $noPartial ? 'PASS' : 'PARTIAL';
                $reason = $noPartial
                    ? 'Migration güvenli tamamlandı'
                    : 'Ek taşıma veya düzeltme gerekli (partial moves)';
            }
        } else {
            $decision = 'FAIL';
            $reasons = [];
            if (!$missingZero) {
                $reasons[] = "MISSING sections: {$summary['missing']}";
            }
            if (!$archivePass) {
                $reasons[] = 'Archive metadata validation failed';
            }
            if (!$isolationPass) {
                $reasons[] = 'AI context isolation failed';
            }
            $reason = implode('; ', $reasons);
        }

        return [
            'decision' => $decision,
            'reason' => $reason,
            'criteria' => [
                'missing_zero' => $missingZero ? 'PASS' : 'FAIL',
                'archive_isolation' => $archivePass ? 'PASS' : 'FAIL',
                'context_isolation' => $isolationPass ? 'PASS' : 'FAIL',
                'no_partial' => $noPartial ? 'PASS' : 'WARN',
            ],
        ];
    }
}
