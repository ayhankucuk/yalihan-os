<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * Patch B — PropertyHub AI Helper Authority Bridge Verification
 *
 * Proves that:
 * 1. OllamaService is NOT directly injected in any of the 3 bridged AI methods
 * 2. YalihanCortex is declared in PropertyHubController (constructor DI)
 * 3. All 3 methods call $this->cortex->analyzePropertyGaps / extractFeaturesFromText / generateTemplateSuggestions
 * 4. All 3 methods include Context7-compliant telemetry fields (basarili, http_durum_kodu, duration_ms, istek_url)
 * 5. Response shapes include trace_id (behavior parity: same data shape as before, plus trace_id)
 * 6. The 503 + isHealthy() bypass path is removed from aiSuggestTemplate
 * 7. Inline mapAiFeaturesToDb duplication removed from aiSuggestTemplate
 * 8. ci-guard-ai-authority.sh passes (structural assertion)
 */
class PropertyHubAIAuthorityBridgeTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = file_get_contents(
            base_path('app/Http/Controllers/Admin/PropertyHubController.php')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 1: OllamaService no longer injected in the 3 bridged AI methods
    // ─────────────────────────────────────────────────────────────────────────
    public function test_ai_methods_do_not_inject_ollamaservice_as_parameter(): void
    {
        $bridgedMethods = [
            'aiAnalyzeGaps',
            'aiExtractFeatures',
            'aiSuggestTemplate',
        ];

        foreach ($bridgedMethods as $method) {
            preg_match(
                '/public function ' . $method . '\(([^)]*)\)/',
                $this->source,
                $matches
            );

            $this->assertNotEmpty($matches, "Could not find {$method}() signature in PropertyHubController");

            $signature = $matches[1];

            $this->assertStringNotContainsString(
                'OllamaService',
                $signature,
                "{$method}() still injects OllamaService as a parameter — AI authority bypass not closed"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 2: YalihanCortex is injected in the constructor
    // ─────────────────────────────────────────────────────────────────────────
    public function test_yalihan_cortex_injected_in_constructor(): void
    {
        $this->assertStringContainsString(
            'YalihanCortex',
            $this->source,
            'YalihanCortex import missing from PropertyHubController'
        );

        $this->assertStringContainsString(
            'private YalihanCortex $cortex',
            $this->source,
            'YalihanCortex constructor injection not found — cortex not wired'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 3: All 3 cortex capability calls are present
    // ─────────────────────────────────────────────────────────────────────────
    public function test_cortex_capability_calls_present(): void
    {
        $expectedCalls = [
            'cortex->analyzePropertyGaps('    => 'aiAnalyzeGaps',
            'cortex->extractFeaturesFromText(' => 'aiExtractFeatures',
            'cortex->generateTemplateSuggestions(' => 'aiSuggestTemplate',
        ];

        foreach ($expectedCalls as $call => $method) {
            $this->assertStringContainsString(
                $call,
                $this->source,
                "{$method}() does not call {$call} — YalihanCortex routing missing"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 4: Context7-compliant telemetry in all 3 methods
    // ─────────────────────────────────────────────────────────────────────────
    public function test_telemetry_fields_are_context7_compliant(): void
    {
        $requiredFields = [
            'basarili',
            'http_durum_kodu',
            'duration_ms',
            'istek_url',
            'trace_id',
        ];

        foreach ($requiredFields as $field) {
            $this->assertStringContainsString(
                "'$field'",
                $this->source,
                "Telemetry field '{$field}' missing from PropertyHubController AI methods — not Context7 compliant"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 5: isHealthy() check removed from aiSuggestTemplate
    // ─────────────────────────────────────────────────────────────────────────
    public function test_suggest_template_does_not_have_is_healthy_check(): void
    {
        preg_match(
            '/public function aiSuggestTemplate\(.*?\{(.+?)(?=\n\s{4}\/\*\*|\n\s{4}public function)/s',
            $this->source,
            $matches
        );

        $this->assertNotEmpty($matches, 'Could not extract aiSuggestTemplate() body');

        $body = $matches[1];

        $this->assertStringNotContainsString(
            'isHealthy',
            $body,
            'aiSuggestTemplate() still has isHealthy() check — old OllamaService health bypass path still present'
        );

        // The 503 response (AI service unavailable) should be gone
        $this->assertStringNotContainsString(
            '503',
            $body,
            'aiSuggestTemplate() still returns 503 — OllamaService health-check response not removed'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 6: Inline mapAiFeaturesToDb duplication removed from aiSuggestTemplate
    // ─────────────────────────────────────────────────────────────────────────
    public function test_suggest_template_does_not_duplicate_feature_mapping_logic(): void
    {
        preg_match(
            '/public function aiSuggestTemplate\(.*?\{(.+?)(?=\n\s{4}\/\*\*|\n\s{4}public function)/s',
            $this->source,
            $matches
        );

        $this->assertNotEmpty($matches, 'Could not extract aiSuggestTemplate() body');

        $body = $matches[1];

        // The inline foreach loop over $aiData['groups'] was the duplication — should be gone
        $this->assertStringNotContainsString(
            "foreach (\$aiData['groups']",
            $body,
            'aiSuggestTemplate() still has inline feature mapping loop — de-duplication not applied'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 7: trace_id included in each success response
    // ─────────────────────────────────────────────────────────────────────────
    public function test_success_responses_include_trace_id(): void
    {
        $methods = ['aiAnalyzeGaps', 'aiExtractFeatures', 'aiSuggestTemplate'];

        foreach ($methods as $method) {
            preg_match(
                '/public function ' . $method . '\(.*?\{(.+?)(?=\n\s{4}\/\*\*|\n\s{4}public function)/s',
                $this->source,
                $matches
            );

            $this->assertNotEmpty($matches, "Could not extract {$method}() body");

            $this->assertStringContainsString(
                'trace_id',
                $matches[1],
                "{$method}() does not include trace_id in response — observability requirement not met"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 8: ai-authority guard passes (structural)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_ai_authority_guard_script_passes(): void
    {
        $guardScript = base_path('scripts/guards/ci-guard-ai-authority.sh');

        $this->assertFileExists($guardScript, 'ci-guard-ai-authority.sh does not exist');

        exec("bash {$guardScript} 2>&1", $output, $exitCode);

        $this->assertEquals(
            0,
            $exitCode,
            "ci-guard-ai-authority.sh FAILED:\n" . implode("\n", $output)
        );
    }
}
