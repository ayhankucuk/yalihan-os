<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * Patch C — PropertyHubController::search() Query Grouping Verification
 *
 * Bug: orWhere without grouping breaks aktiflik_durumu filter precedence.
 * SQL without fix: WHERE name LIKE '...' OR (slug LIKE '...' AND aktiflik_durumu = 1)
 * SQL with fix:    WHERE (name LIKE '...' OR slug LIKE '...') AND aktiflik_durumu = 1
 *
 * Pasif features must NEVER appear in results regardless of whether
 * the match comes from the name branch or the slug branch.
 */
class PropertyHubSearchQueryGroupingTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // Test 1: Source-level — aktiflik_durumu filter is outside the orWhere group
    // ─────────────────────────────────────────────────────────────────────────
    public function test_search_query_groups_name_and_slug_before_aktiflik_filter(): void
    {
        $source = file_get_contents(
            base_path('app/Services/PropertyHub/PropertyHubOrchestrator.php')
        );

        // Extract method body
        preg_match(
            '/public function searchFeaturesAndCategories\(.*?\{(.+?)(?=\n\s{4}\/\*\*|\n\s{4}public function)/s',
            $source,
            $matches
        );

        $this->assertNotEmpty($matches, 'Could not extract search() method body from PropertyHubController');

        $searchBody = $matches[1];

        // Must use closure-based WHERE group — not raw orWhere chain
        $this->assertStringContainsString(
            'where(function',
            $searchBody,
            'search() does not use where(function) grouping — aktiflik_durumu precedence bug still present'
        );

        // aktiflik_durumu must appear AFTER the closing of the grouped where
        $groupStart = strpos($searchBody, 'where(function');
        $afterGroup = substr($searchBody, $groupStart);

        // Find the closing of the grouped where block, then check aktiflik_durumu
        // Pattern: ->where('aktiflik_durumu' appears after the closure group
        $this->assertMatchesRegularExpression(
            '/where\(function.*?\}\).*?where\([\'"]aktiflik_durumu/s',
            $searchBody,
            'aktiflik_durumu filter is inside the orWhere group instead of after it — pasif features can leak via name match'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 2: The old unsafe orWhere chain no longer exists in search()
    // ─────────────────────────────────────────────────────────────────────────
    public function test_search_does_not_use_raw_orwhere_aktiflik_chain(): void
    {
        $source = file_get_contents(
            base_path('app/Services/PropertyHub/PropertyHubOrchestrator.php')
        );

        // Extract method body
        preg_match(
            '/public function searchFeaturesAndCategories\(.*?\{(.+?)(?=\n\s{4}\/\*\*|\n\s{4}public function)/s',
            $source,
            $matches
        );

        $this->assertNotEmpty($matches, 'Could not extract search() method body');

        $searchBody = $matches[1];

        // The old pattern: ->orWhere('slug'...) then ->where('aktiflik_durumu') on next line without grouping
        // Without the /s flag, . does not match \n, so .*? stays inside the same line.
        // \s* between the closing ) and ->where can still match the newline.
        // In the FIXED version, orWhere ends with ); not ) followed by \s*->where.
        $this->assertDoesNotMatchRegularExpression(
            '/orWhere\(.*?slug.*?\)\s*->where\([\'"]aktiflik_durumu/',
            $searchBody,
            'Old unsafe orWhere chain detected — aktiflik_durumu filter bypassed for name matches'
        );
    }
}
