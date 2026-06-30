<?php

namespace App\Services\Governance\DeprecationValidator;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Reads and validates YAML frontmatter metadata from archived legacy files.
 *
 * Responsible for:
 * - YAML frontmatter extraction from Markdown archive files
 * - Required field presence check (deprecated, archived_from, archived_at, reason)
 * - Boolean field type validation
 * - Optional field detection (usage, excluded_from_ai_context, replaced_by)
 */
class ArchiveMetadataReader
{
    /**
     * Required YAML frontmatter fields for a valid archive file.
     */
    private const REQUIRED_FIELDS = [
        'deprecated',
        'archived_from',
        'archived_at',
        'reason',
    ];

    /**
     * Optional but recommended fields.
     */
    private const RECOMMENDED_FIELDS = [
        'usage',
        'excluded_from_ai_context',
        'replaced_by',
    ];

    /**
     * Read and validate the archive file's YAML frontmatter.
     *
     * @param string $archivePath Relative path from base_path()
     * @return array{metadata: array, warnings: array, valid: bool}
     * @throws RuntimeException
     */
    public function read(string $archivePath): array
    {
        $resolvedPath = base_path($archivePath);

        if (!File::exists($resolvedPath)) {
            throw new RuntimeException("Archive file not found at: {$resolvedPath}");
        }

        $content = File::get($resolvedPath);
        $frontmatter = $this->extractFrontmatter($content);

        if (empty($frontmatter)) {
            return [
                'metadata' => [],
                'warnings' => ['No YAML frontmatter found in archive file'],
                'valid' => false,
            ];
        }

        return $this->validate($frontmatter);
    }

    /**
     * Extract YAML frontmatter from Markdown content.
     * Frontmatter is delimited by --- at start and end.
     *
     * @param string $content
     * @return array
     */
    private function extractFrontmatter(string $content): array
    {
        // Match YAML frontmatter between --- delimiters
        if (!preg_match('/\A---\s*\n(.*?)\n---/s', $content, $matches)) {
            return [];
        }

        $yaml = $matches[1];
        $parsed = [];

        // Simple YAML key: value parser (no nested structures needed)
        foreach (explode("\n", $yaml) as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $colonPos = strpos($line, ':');
            if ($colonPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $colonPos));
            $value = trim(substr($line, $colonPos + 1));

            // Handle boolean values
            if (in_array(strtolower($value), ['true', 'yes'])) {
                $value = true;
            } elseif (in_array(strtolower($value), ['false', 'no'])) {
                $value = false;
            }

            // Remove surrounding quotes
            if (is_string($value)) {
                $value = trim($value, '"\'');
            }

            $parsed[$key] = $value;
        }

        return $parsed;
    }

    /**
     * Validate frontmatter against required and recommended fields.
     *
     * @param array $metadata
     * @return array{metadata: array, warnings: array, valid: bool}
     */
    private function validate(array $metadata): array
    {
        $warnings = [];
        $valid = true;

        // Check required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $metadata)) {
                $warnings[] = "FAIL: Required field '{$field}' is missing from archive frontmatter";
                $valid = false;
            }
        }

        // Validate deprecated must be true
        if (isset($metadata['deprecated']) && $metadata['deprecated'] !== true) {
            $warnings[] = "FAIL: 'deprecated' must be true, got: " . var_export($metadata['deprecated'], true);
            $valid = false;
        }

        // Check recommended fields
        foreach (self::RECOMMENDED_FIELDS as $field) {
            if (!array_key_exists($field, $metadata)) {
                $warnings[] = "WARN: Recommended field '{$field}' is missing (implicit only)";
            }
        }

        // Validate excluded_from_ai_context should be true
        if (isset($metadata['excluded_from_ai_context']) && $metadata['excluded_from_ai_context'] !== true) {
            $warnings[] = "FAIL: 'excluded_from_ai_context' must be true for archived files";
            $valid = false;
        }

        // Validate usage should be reference-only
        if (isset($metadata['usage']) && $metadata['usage'] !== 'reference-only') {
            $warnings[] = "WARN: 'usage' is '{$metadata['usage']}', expected 'reference-only'";
        }

        return [
            'metadata' => $metadata,
            'warnings' => $warnings,
            'valid' => $valid,
        ];
    }
}
