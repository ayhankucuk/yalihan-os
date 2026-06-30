<?php

namespace App\Services\Governance\AuthorityMap;

use Illuminate\Support\Facades\File;

class AuthorityMapWriter
{
    private string $outputPath;

    public function __construct(string $outputPath = 'docs/authority-map.md')
    {
        $this->outputPath = base_path($outputPath);
    }

    /**
     * Checks if the generated content is matching the existing file content.
     * Ignores the 'Generated At:' line.
     *
     * @param string $newContent
     * @return bool True if contents match (no drift)
     */
    public function check(string $newContent): bool
    {
        if (!File::exists($this->outputPath)) {
            return false;
        }

        $existingContent = File::get($this->outputPath);

        $normalizedNew = $this->normalizeContent($newContent);
        $normalizedExisting = $this->normalizeContent($existingContent);

        return $normalizedNew === $normalizedExisting;
    }

    /**
     * Writes the new content to disk.
     *
     * @param string $newContent
     * @return void
     */
    public function write(string $newContent): void
    {
        File::ensureDirectoryExists(dirname($this->outputPath));
        File::put($this->outputPath, $newContent);
    }

    /**
     * Normalizes content. Removes 'Generated At: ...' line to compare deterministically.
     *
     * @param string $content
     * @return string
     */
    private function normalizeContent(string $content): string
    {
        // Strip out the exact Generated At line
        $content = preg_replace('/^\> \*\s+\*\*Generated At:\*\*.*$/m', '', $content);
        
        // Trim whitespace/newlines
        return trim($content);
    }
}
