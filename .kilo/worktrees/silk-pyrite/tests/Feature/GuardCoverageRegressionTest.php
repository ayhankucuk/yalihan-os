<?php

namespace Tests\Feature;

use App\Support\AgentContext;
use App\Traits\GuardsAgentWrites;
use ReflectionClass;
use Tests\TestCase;

/**
 * GuardCoverageRegressionTest
 *
 * Ensures that ALL write services in the codebase use GuardsAgentWrites trait.
 * If a new service with write methods is added without the trait, this test FAILS.
 *
 * This is the CI safety net: "guard trait unutulursa test patlar"
 */
class GuardCoverageRegressionTest extends TestCase
{
    /**
     * Registry of all known write services that MUST have GuardsAgentWrites.
     * When you add a new write service, you MUST add it here — or the test fails.
     */
    private const GUARDED_SERVICES = [
        // === Original 15 (Session 5) ===
        \App\Services\Ilan\IlanCrudService::class,
        \App\Services\Admin\KisiManagerService::class,
        \App\Modules\Crm\Services\KisiService::class,
        \App\Modules\TakimYonetimi\Services\GorevService::class,
        \App\Modules\Finans\Services\FinansalIslemManager::class,
        \App\Services\Photo\PhotoService::class,
        \App\Services\Ilan\IlanPhotoService::class,
        \App\Services\Ilan\IlanKategoriService::class,
        \App\Services\Ilan\YazlikKiralamaService::class,
        \App\Services\Location\AdresLocationService::class,
        \App\Services\PropertyType\PropertyTypeService::class,
        \App\Services\Ups\UpsMasterTemplateService::class,
        \App\Services\Ups\UpsFeaturePackService::class,
        \App\Services\Wizard\WizardDraftService::class,
        \App\Services\Category\FieldDependencyService::class,
        // === Coverage Completion (Session 7) — 24 new services ===
        \App\Services\Ups\UpsVersioningService::class,
        \App\Services\Analysis\MarketAnalysisService::class,
        \App\Services\Category\FeatureCategoryBulkService::class,
        \App\Services\Intelligence\SuppressionService::class,
        \App\Services\Property\FeaturePackService::class,
        \App\Services\Property\PropertyBulkOperationsService::class,
        \App\Services\Kisi\BulkKisiService::class,
        \App\Services\LeadService::class,
        \App\Services\PropertyType\TemplateAssignmentService::class,
        \App\Services\PropertyType\FeatureAssignmentService::class,
        \App\Services\PropertyType\PropertyTypeBulkUpdateService::class,
        \App\Services\CRM\KisiScoringService::class,
        \App\Services\CRM\LeadScoringService::class,
        \App\Services\IlanReferansService::class,
        \App\Services\CalendarSyncService::class,
        \App\Services\Mobile\ProfileService::class,
        \App\Services\AI\LeadScoreCalculator::class,
        \App\Services\AI\YalihanCortex::class,
        \App\Services\AIDeal\DealTelemetryService::class,
        \App\Services\Portfolio\TapuMatchService::class,
        \App\Services\SiteService::class,
        \App\Services\ReservationService::class,
        \App\Services\Listing\YalihanLifecycle::class,
        \App\Services\YazlikKiralamaService::class,
        \App\Services\CRM\LeadAuthorityService::class,
        \App\Services\CRM\TalepAuthorityService::class,
        \App\Services\CRM\MatchingAuthorityService::class,
    ];

    /**
     * Test 1: Every service in the registry MUST use GuardsAgentWrites trait.
     */
    public function test_all_registered_write_services_use_guard_trait(): void
    {
        $missing = [];

        foreach (self::GUARDED_SERVICES as $serviceClass) {
            if (!class_exists($serviceClass)) {
                $missing[] = "{$serviceClass} — CLASS NOT FOUND";
                continue;
            }

            $usedTraits = class_uses_recursive($serviceClass);

            if (!isset($usedTraits[GuardsAgentWrites::class])) {
                $missing[] = $serviceClass;
            }
        }

        $this->assertEmpty(
            $missing,
            "These write services are missing GuardsAgentWrites trait:\n" . implode("\n", $missing)
        );
    }

    /**
     * Test 2: Every guarded service actually blocks agent writes.
     * Activates AgentContext, then verifies blockAgentWrite() throws.
     */
    public function test_all_guarded_services_block_agent_writes(): void
    {
        AgentContext::activate('agent.read', 'regression-test', 'hash123');

        $failures = [];

        foreach (self::GUARDED_SERVICES as $serviceClass) {
            if (!class_exists($serviceClass)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($serviceClass);

                if (!$reflection->hasMethod('blockAgentWrite')) {
                    $failures[] = "{$serviceClass} — no blockAgentWrite method";
                    continue;
                }

                $method = $reflection->getMethod('blockAgentWrite');
                $method->setAccessible(true);

                // Create without constructor (avoids dependency issues)
                $instance = $reflection->newInstanceWithoutConstructor();

                $threw = false;
                try {
                    $method->invoke($instance, 'regressionTest');
                } catch (\App\Exceptions\AgentWriteViolationException $e) {
                    $threw = true;
                }

                if (!$threw) {
                    $failures[] = "{$serviceClass} — blockAgentWrite did NOT throw";
                }
            } catch (\Throwable $e) {
                $failures[] = "{$serviceClass} — " . $e->getMessage();
            }
        }

        AgentContext::reset();

        $this->assertEmpty(
            $failures,
            "These services failed agent write block test:\n" . implode("\n", $failures)
        );
    }

    /**
     * Test 3: Scan app/Services and app/Modules for write methods without guard.
     *
     * This scans PHP files for public methods named store/create/update/delete/destroy
     * that belong to classes NOT in the GUARDED_SERVICES registry.
     * If found, the developer forgot to add the service to the registry.
     *
     * STRICT MODE: Unregistered write SERVICES fail the test.
     * Controllers/Models are advisory (they are behind middleware).
     */
    public function test_no_unregistered_write_services_exist(): void
    {
        $directories = [
            base_path('app/Services'),
            base_path('app/Modules'),
        ];

        $writeMethodPatterns = [
            '/public\s+function\s+(store|create[A-Z]\w*|update[A-Z]\w*|delete[A-Z]\w*|destroy|upsert\w*|bulk[A-Z]\w*)\s*\(/m',
        ];

        // Known exclusions: deprecated, read-only, sealed, or controllers (behind middleware)
        $excludedClasses = [
            // Controllers — already behind middleware stack, not direct service calls
            'App\\Modules\\Auth\\Controllers\\AuthController',
            'App\\Modules\\Emlak\\Controllers\\ProjeController',
            'App\\Modules\\Emlak\\Controllers\\FeatureController',
            'App\\Modules\\Finans\\Controllers\\KomisyonController',
            'App\\Modules\\TakimYonetimi\\Controllers\\Admin\\ProjeController',
            'App\\Modules\\TakimYonetimi\\Controllers\\Admin\\GorevController',
            'App\\Modules\\TakimYonetimi\\Controllers\\Admin\\TakimController',
            'App\\Modules\\TakimYonetimi\\Controllers\\Admin\\TelegramBotController',
            'App\\Modules\\TakimYonetimi\\Controllers\\API\\TakimApiController',
            'App\\Modules\\TakimYonetimi\\Controllers\\API\\GorevApiController',
            'App\\Modules\\TakimYonetimi\\Controllers\\API\\ProjeApiController',
            // Models — write methods on models (updateLastLogin etc.) are not service layer
            'App\\Modules\\Auth\\Models\\User',
            // Deprecated/sealed services
            'App\\Services\\Ilan\\IlanReservationService',
            // Analytics — read-heavy, create methods are for filters/reports not domain writes
            'App\\Services\\Analytics\\AnalyticsDashboardService',
            'App\\Services\\Analytics\\AnalyticsReportsService',
            // Telegram bot settings — low risk edge case
            'App\\Modules\\TakimYonetimi\\Services\\TelegramBotService',
            // === Intentionally exempt (Session 7 classification) ===
            // FlexibleStorageManager — internal AI pattern storage (deprecated bookkeeping)
            'App\\Services\\FlexibleStorageManager',
            // UpsImportExportService — file storage deletion only, no domain entity write
            'App\\Services\\Ups\\UpsImportExportService',
            // QRCodeService — file storage + cache forget, no domain writes
            'App\\Services\\QRCodeService',
            // MarketingTemplateService — file storage only (Storage::put/delete), no DB writes
            'App\\Services\\Marketing\\MarketingTemplateService',
            // AICoreSystem — internal AI success metrics bookkeeping
            'App\\Services\\AICoreSystem',
            // IlanBulkService — delegates to already-guarded IlanCrudService
            'App\\Services\\Ilan\\IlanBulkService',
            // IlanService — sealed/deprecated, all write methods throw RuntimeException
            'App\\Services\\IlanService',
                    'App\\Services\\Ilan\\IlanService',
            // AIOrchestrator — returns unsaved model object (fill only), no DB write
            'App\\Services\\AI\\AIOrchestrator',
        ];

        $unregistered = [];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());

                // Skip interfaces, abstract classes, traits
                if (preg_match('/\b(interface|abstract\s+class|trait)\s+/', $content)) {
                    continue;
                }

                // Extract fully qualified class name
                $namespace = '';
                if (preg_match('/namespace\s+([\w\\\\]+);/', $content, $m)) {
                    $namespace = $m[1];
                }
                $className = '';
                if (preg_match('/class\s+(\w+)/', $content, $m)) {
                    $className = $m[1];
                }
                if (!$namespace || !$className) {
                    continue;
                }

                $fqcn = $namespace . '\\' . $className;

                // Skip if already in registry
                if (in_array($fqcn, self::GUARDED_SERVICES, true)) {
                    continue;
                }

                // Skip excluded classes
                if (in_array($fqcn, $excludedClasses, true)) {
                    continue;
                }

                // Skip if already has GuardsAgentWrites
                if (str_contains($content, 'GuardsAgentWrites')) {
                    continue;
                }

                // Check for write methods with actual Eloquent/DB write calls
                $hasWriteMethod = false;
                foreach ($writeMethodPatterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        // Verify the class actually performs DB writes (not just method naming)
                        if (preg_match('/(::|->)(create|save|update|delete|destroy|forceDelete|insert|statement)\s*\(/', $content)) {
                            $hasWriteMethod = true;
                            break;
                        }
                    }
                }

                if ($hasWriteMethod) {
                    // Extract the matching method names for the error message
                    $methods = [];
                    foreach ($writeMethodPatterns as $pattern) {
                        if (preg_match_all($pattern, $content, $matches)) {
                            $methods = array_merge($methods, $matches[1]);
                        }
                    }
                    $unregistered[] = "{$fqcn} — methods: " . implode(', ', array_unique($methods));
                }
            }
        }

        $this->assertEmpty(
            $unregistered,
            "UNREGISTERED write services found! Add GuardsAgentWrites trait + register in GUARDED_SERVICES constant,\n" .
            "or add to \$excludedClasses if intentionally exempt:\n" . implode("\n", $unregistered)
        );
    }

    /**
     * Test 4: Normal (non-agent) requests MUST NOT be blocked.
     */
    public function test_normal_requests_not_blocked_by_guard(): void
    {
        // Ensure AgentContext is NOT active
        AgentContext::reset();
        $this->assertFalse(AgentContext::isAgent());

        foreach (self::GUARDED_SERVICES as $serviceClass) {
            if (!class_exists($serviceClass)) {
                continue;
            }

            $reflection = new ReflectionClass($serviceClass);
            if (!$reflection->hasMethod('blockAgentWrite')) {
                continue;
            }

            $method = $reflection->getMethod('blockAgentWrite');
            $method->setAccessible(true);

            $instance = $reflection->newInstanceWithoutConstructor();

            // Should NOT throw — this is a normal request
            $threw = false;
            try {
                $method->invoke($instance, 'normalTest');
            } catch (\App\Exceptions\AgentWriteViolationException $e) {
                $threw = true;
            }

            $this->assertFalse(
                $threw,
                "{$serviceClass}::blockAgentWrite() blocked a normal (non-agent) request!"
            );
        }
    }
}
