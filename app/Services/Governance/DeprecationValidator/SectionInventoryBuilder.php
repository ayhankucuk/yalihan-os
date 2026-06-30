<?php

namespace App\Services\Governance\DeprecationValidator;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Builds a section inventory from a Markdown archive file.
 *
 * Responsible for:
 * - Markdown heading extraction (## and ### levels)
 * - Section ID assignment (prefix-NN format)
 * - Line range calculation per section
 * - Content hash generation for duplicate detection
 */
class SectionInventoryBuilder
{
    /**
     * Build section inventory from archive file content.
     *
     * @param string $archivePath Relative path from base_path()
     * @param string $idPrefix Prefix for section IDs (e.g., 'GPB')
     * @return array<int, array{id: string, title: string, start_line: int, end_line: int, level: int, content_hash: string}>
     * @throws RuntimeException
     */
    public function build(string $archivePath, string $idPrefix = 'GPB'): array
    {
        $resolvedPath = base_path($archivePath);

        if (!File::exists($resolvedPath)) {
            throw new RuntimeException("Archive file not found at: {$resolvedPath}");
        }

        $content = File::get($resolvedPath);
        $lines = explode("\n", $content);

        return $this->extractSections($lines, $idPrefix);
    }

    /**
     * Extract sections from parsed lines.
     *
     * @param array $lines
     * @param string $idPrefix
     * @return array
     */
    private function extractSections(array $lines, string $idPrefix): array
    {
        $sections = [];
        $sectionStarts = [];
        $totalLines = count($lines);

        // Skip frontmatter
        $inFrontmatter = false;
        $frontmatterEnd = 0;

        for ($i = 0; $i < $totalLines; $i++) {
            $line = trim($lines[$i]);

            if ($i === 0 && $line === '---') {
                $inFrontmatter = true;
                continue;
            }

            if ($inFrontmatter && $line === '---') {
                $inFrontmatter = false;
                $frontmatterEnd = $i;
                continue;
            }

            if ($inFrontmatter) {
                continue;
            }

            // Detect ## headings (level 2) — primary section boundaries
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]);
                $title = trim($matches[2]);

                // Skip the top-level # heading (document title)
                // Only track ## and ### as section boundaries
                if ($level === 2) {
                    $sectionStarts[] = [
                        'line' => $i + 1, // 1-indexed
                        'title' => $this->cleanTitle($title),
                        'level' => $level,
                    ];
                }
            }
        }

        // Build sections with end line calculation
        $counter = 1;
        foreach ($sectionStarts as $idx => $start) {
            $endLine = isset($sectionStarts[$idx + 1])
                ? $sectionStarts[$idx + 1]['line'] - 1
                : $totalLines;

            // Calculate content hash for duplicate detection
            $sectionContent = implode("\n", array_slice(
                $lines,
                $start['line'] - 1,
                $endLine - $start['line'] + 1
            ));

            $sections[] = [
                'id' => sprintf('%s-%02d', $idPrefix, $counter),
                'title' => $start['title'],
                'start_line' => $start['line'],
                'end_line' => $endLine,
                'level' => $start['level'],
                'content_hash' => md5($sectionContent),
            ];

            $counter++;
        }

        return $sections;
    }

    /**
     * Clean heading title by removing emoji and extra formatting.
     *
     * @param string $title
     * @return string
     */
    private function cleanTitle(string $title): string
    {
        // Remove common emoji patterns
        $title = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $title);
        // Remove special characters like 🛡️ 🔒 🏗 etc.
        $title = preg_replace('/[\x{2600}-\x{27BF}]/u', '', $title);
        // Remove variation selectors
        $title = preg_replace('/[\x{FE00}-\x{FE0F}]/u', '', $title);

        return trim($title);
    }
}
