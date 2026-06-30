<?php

namespace Tests\Support;

use App\Services\Category\FieldDependencyService;

/**
 * Testable Field Dependency Service
 *
 * Context7: C7-TEST-SUPPORT-2025-12-23
 *
 * Extends FieldDependencyService to expose private methods for testing.
 * Allows injection of mock dependency graphs without database.
 *
 * @package Tests\Support
 */
class TestableFieldDependencyService extends FieldDependencyService
{
    /**
     * Mock dependency graph for testing
     *
     * @var array|null
     */
    private ?array $mockGraph = null;

    /**
     * Set mock dependency graph
     *
     * @param array $graph
     * @return void
     */
    public function setMockGraph(array $graph): void
    {
        $this->mockGraph = $graph;
    }

    /**
     * Override buildDependencyGraph for testing
     *
     * Returns mock graph if set, otherwise calls parent
     *
     * @param string $kategoriSlug
     * @param string|null $yayinTipi
     * @return array
     */
    protected function buildDependencyGraph(string $kategoriSlug, ?string $yayinTipi = null): array
    {
        if ($this->mockGraph !== null) {
            return $this->mockGraph;
        }

        return parent::buildDependencyGraph($kategoriSlug, $yayinTipi);
    }
}
