<?php

namespace App\Services\Governance\DeprecationValidator;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Validates section-to-target file mappings for deprecation migrations.
 *
 * Responsible for:
 * - Loading and parsing .mapping.json configuration
 * - Target file existence verification
 * - Key phrase content matching (fuzzy)
 * - Coverage status determination (MOVED_FULL, MOVED_PARTIAL, ARCHIVED_ONLY, MISSING)
 * - Target role validation (correct projection layer)
 */
class TargetMappingValidator
{
    /**
     * Valid coverage status values.
     */
    public const STATUS_MOVED_FULL = 'MOVED_FULL';
    public const STATUS_MOVED_PARTIAL = 'MOVED_PARTIAL';
    public const STATUS_ARCHIVED_ONLY = 'ARCHIVED_ONLY';
    public const STATUS_DROPPED_APPROVED = 'DROPPED_APPROVED';
    public const STATUS_MISSING = 'MISSING';

    /**
     * Expected layer-to-directory mapping for role validation.
     */
    private const ROLE_MAP = [
        'context' => '.ai/context/',
        'memory' => '.ai/memory/',
        'report' => 'docs/reports/',
        'governance' => 'GOVERNANCE.md',
        'authority' => '.sab/',
        'agent_rule' => 'proje.md',
        'archive' => '.ai/memory/legacy/',
    ];

    /**
     * Load mapping configuration from JSON file.
     *
     * @param string $mappingPath Relative path from base_path()
     * @return array
     * @throws RuntimeException
     */
    public function loadMapping(string $mappingPath): array
    {
        $resolvedPath = base_path($mappingPath);

        if (!File::exists($resolvedPath)) {
            throw new RuntimeException("Mapping file not found at: {$resolvedPath}");
        }

        $content = File::get($resolvedPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Mapping file JSON is invalid: " . json_last_error_msg());
        }

        if (!isset($data['sections']) || !is_array($data['sections'])) {
            throw new RuntimeException("Mapping file must contain a 'sections' array");
        }

        return $data;
    }

    /**
     * Validate all section mappings against the filesystem.
     *
     * @param array $mapping The parsed mapping configuration
     * @return array<int, array{id: string, title: string, decision: string, targets: array, coverage_status: string, target_role_valid: bool, notes: string}>
     */
    public function validate(array $mapping): array
    {
        $results = [];

        foreach ($mapping['sections'] as $section) {
            $results[] = $this->validateSection($section);
        }

        return $results;
    }

    /**
     * Validate a single section mapping.
     *
     * @param array $section
     * @return array
     */
    private function validateSection(array $section): array
    {
        $id = $section['id'] ?? 'UNKNOWN';
        $title = $section['title'] ?? 'Untitled';
        $decision = $section['decision'] ?? self::STATUS_MISSING;
        $targets = $section['targets'] ?? [];
        $keyPhrases = $section['key_phrases'] ?? [];
        $notes = [];

        // ARCHIVED_ONLY sections have no targets to check
        if ($decision === self::STATUS_ARCHIVED_ONLY) {
            return [
                'id' => $id,
                'title' => $title,
                'decision' => $decision,
                'targets' => $targets,
                'coverage_status' => self::STATUS_ARCHIVED_ONLY,
                'target_role_valid' => true,
                'notes' => 'Archive-only section — no target validation required',
            ];
        }

        // DROPPED_APPROVED sections are intentionally removed
        if ($decision === self::STATUS_DROPPED_APPROVED) {
            return [
                'id' => $id,
                'title' => $title,
                'decision' => $decision,
                'targets' => [],
                'coverage_status' => self::STATUS_DROPPED_APPROVED,
                'target_role_valid' => true,
                'notes' => 'Intentionally dropped — approved removal',
            ];
        }

        // Validate targets exist and contain key phrases
        if (empty($targets)) {
            return [
                'id' => $id,
                'title' => $title,
                'decision' => $decision,
                'targets' => $targets,
                'coverage_status' => self::STATUS_MISSING,
                'target_role_valid' => false,
                'notes' => 'No target files specified but decision is not ARCHIVED_ONLY',
            ];
        }

        $allTargetsExist = true;
        $targetNotes = [];
        $roleValid = true;

        // Collect ALL target file contents for union-based phrase matching
        $combinedContent = '';

        foreach ($targets as $target) {
            $resolvedTarget = base_path($target);

            // Check file existence
            if (!File::exists($resolvedTarget)) {
                $allTargetsExist = false;
                $targetNotes[] = "MISSING: {$target} does not exist";
                continue;
            }

            $combinedContent .= "\n" . File::get($resolvedTarget);

            // Validate target role (correct projection layer)
            if (!$this->isCorrectLayer($target, $section['expected_layer'] ?? null)) {
                $roleValid = false;
                $targetNotes[] = "ROLE_MISMATCH: {$target} may not be the correct projection layer";
            }
        }

        // Key phrase matching: union across ALL targets
        // A phrase found in ANY target counts as found
        $allKeyPhrasesFound = true;
        if (!empty($keyPhrases) && $allTargetsExist) {
            $foundCount = 0;

            foreach ($keyPhrases as $phrase) {
                if (stripos($combinedContent, $phrase) !== false) {
                    $foundCount++;
                }
            }

            $phraseRatio = count($keyPhrases) > 0
                ? $foundCount / count($keyPhrases)
                : 1.0;

            if ($phraseRatio < 0.5) {
                $allKeyPhrasesFound = false;
                $targetNotes[] = "PARTIAL: only {$foundCount}/" . count($keyPhrases) . " key phrases found across " . count($targets) . " target(s)";
            }
        }

        // Determine coverage status
        $coverageStatus = self::STATUS_MISSING;
        if ($allTargetsExist && $allKeyPhrasesFound) {
            $coverageStatus = self::STATUS_MOVED_FULL;
        } elseif ($allTargetsExist) {
            $coverageStatus = self::STATUS_MOVED_PARTIAL;
        }

        return [
            'id' => $id,
            'title' => $title,
            'decision' => $decision,
            'targets' => $targets,
            'coverage_status' => $coverageStatus,
            'target_role_valid' => $roleValid,
            'notes' => !empty($targetNotes) ? implode('; ', $targetNotes) : 'Validated OK',
        ];
    }

    /**
     * Check if a target file is in the correct projection layer.
     *
     * @param string $targetPath
     * @param string|null $expectedLayer
     * @return bool
     */
    private function isCorrectLayer(string $targetPath, ?string $expectedLayer): bool
    {
        if ($expectedLayer === null) {
            return true; // No expectation set — skip validation
        }

        $expectedPrefix = self::ROLE_MAP[$expectedLayer] ?? null;

        if ($expectedPrefix === null) {
            return true; // Unknown layer — skip validation
        }

        return str_contains($targetPath, $expectedPrefix);
    }
}
