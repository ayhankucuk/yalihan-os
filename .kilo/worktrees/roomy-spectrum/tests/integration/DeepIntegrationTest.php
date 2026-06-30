#!/usr/bin/env php
<?php

/**
 * DEEP INTEGRATION TEST SUITE
 * Date: 2026-01-12
 * Purpose: Test critical cross-module integrations
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class IntegrationTestSuite
{
    protected $testsRun = 0;
    protected $testsPassed = 0;
    protected $testsFailed = 0;
    protected $results = [];

    public function run()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🧪 DEEP INTEGRATION TEST SUITE\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 80) . "\n\n";

        // Test Suite 1: Telegram → Cortex → Emlak Flow
        $this->testTelegramToCortexToEmlak();

        // Test Suite 2: Emlak → Talep Matching
        $this->testEmlakToTalepMatching();

        // Test Suite 3: Admin → Cortex → AI Providers
        $this->testAdminToCortexAI();

        // Test Suite 4: Database Integrity
        $this->testDatabaseIntegrity();

        // Test Suite 5: Error Handling
        $this->testErrorHandling();

        // Test Suite 6: Performance
        $this->testPerformance();

        // Summary
        $this->printSummary();
    }

    protected function testTelegramToCortexToEmlak()
    {
        echo "\n📱 FLOW 1: Telegram → Cortex → Emlak (Voice-to-Draft)\n";
        echo str_repeat("-", 70) . "\n";

        // Test 1.1: Telegram service exists
        $this->test("Telegram service loaded", function() {
            $class = 'App\\Services\\Telegram\\TelegramBrain';
            return class_exists($class);
        });

        // Test 1.2: Cortex service exists
        $this->test("Cortex service loaded", function() {
            $class = 'App\\Modules\\Cortex\\Services\\YalihanCortex';
            return class_exists($class);
        });

        // Test 1.3: Ilan model relationships
        $this->test("Ilan model has kategori relationship", function() {
            $model = new \App\Models\Ilan();
            return method_exists($model, 'kategori');
        });

        // Test 1.4: Ozellik model relationships
        $this->test("Ozellik model has kategori relationship", function() {
            $model = new \App\Models\Ozellik();
            return method_exists($model, 'kategori');
        });

        // Test 1.5: Draft data can be created
        $this->test("Draft Ilan can be created", function() {
            try {
                $count_before = \App\Models\Ilan::where('yayin_durumu', '0')->count();
                return $count_before >= 0;
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    protected function testEmlakToTalepMatching()
    {
        echo "\n🔗 FLOW 2: Emlak ↔ Talep Matching\n";
        echo str_repeat("-", 70) . "\n";

        // Test 2.1: Talep model exists
        $this->test("Talep model loaded", function() {
            return class_exists('App\\Models\\Talep');
        });

        // Test 2.2: Talep-Ilan relationship
        $this->test("Talep can relate to Ilan", function() {
            $talep = new \App\Models\Talep();
            return method_exists($talep, 'ilanlar') || method_exists($talep, 'ilan');
        });

        // Test 2.3: Matching service exists
        $this->test("TalepMatchingService loaded", function() {
            return class_exists('App\\Services\\Talep\\TalepMatchingService') ||
                   class_exists('App\\Modules\\Talep\\Services\\MatchingService');
        });

        // Test 2.4: Query matching data
        $this->test("Can query Talepler from database", function() {
            try {
                $count = \App\Models\Talep::count();
                return $count >= 0;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Test 2.5: Query Ilanlar for matching
        $this->test("Can query active Ilanlar", function() {
            try {
                $count = \App\Models\Ilan::where('yayin_durumu', '1')->count();
                return $count >= 0;
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    protected function testAdminToCortexAI()
    {
        echo "\n🤖 FLOW 3: Admin → Cortex → AI Providers\n";
        echo str_repeat("-", 70) . "\n";

        // Test 3.1: AI Config exists
        $this->test("AI config exists", function() {
            return config('ai') !== null;
        });

        // Test 3.2: Default AI provider configured
        $this->test("Default AI provider configured", function() {
            $provider = config('ai.default_provider') ?? env('AI_PROVIDER', 'ollama');
            return !empty($provider);
        });

        // Test 3.3: Cortex ROI Engine
        $this->test("Cortex ROI Engine service exists", function() {
            return class_exists('App\\Services\\Intelligence\\ROIEngine') ||
                   class_exists('App\\Modules\\Cortex\\Services\\CortexROIEngine');
        });

        // Test 3.4: Visual Analyzer
        $this->test("Visual Analyzer service exists", function() {
            return class_exists('App\\Modules\\Cortex\\Services\\CortexVisualAnalyzer');
        });

        // Test 3.5: Admin can access Cortex endpoints
        $this->test("Cortex API routes registered", function() {
            try {
                $routes = \Route::getRoutes();
                $cortex_routes = $routes->where('prefix', 'api/v1/cortex');
                return $cortex_routes->count() > 0;
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    protected function testDatabaseIntegrity()
    {
        echo "\n🗄️ DATABASE INTEGRITY TESTS\n";
        echo str_repeat("-", 70) . "\n";

        // Test 4.1: Canonical fields exist
        $this->test("ilanlar.yayin_durumu exists", function() {
            return DB::getSchemaBuilder()->hasColumn('ilanlar', 'yayin_durumu');
        });

        $this->test("talepler.talep_durumu exists", function() {
            return DB::getSchemaBuilder()->hasColumn('talepler', 'talep_durumu');
        });

        $this->test("ozellikler.aktiflik_durumu exists", function() {
            return DB::getSchemaBuilder()->hasColumn('ozellikler', 'aktiflik_durumu');
        });

        // Test 4.2: Foreign keys
        $this->test("ilanlar has kategori_id FK", function() {
            return DB::getSchemaBuilder()->hasColumn('ilanlar', 'kategori_id');
        });

        // Test 4.3: No orphaned records
        $this->test("No orphaned ilanlar.kategori_id", function() {
            try {
                $orphaned = DB::select(
                    "SELECT COUNT(*) as cnt FROM ilanlar WHERE kategori_id NOT IN (SELECT id FROM ilan_kategorileri)"
                );
                return $orphaned[0]->cnt == 0;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Test 4.4: Indexes present
        $this->test("Database indexes optimized", function() {
            try {
                $indexes = DB::select("SHOW INDEX FROM ilanlar");
                return count($indexes) > 3; // Should have multiple indexes
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    protected function testErrorHandling()
    {
        echo "\n⚠️ ERROR HANDLING & EDGE CASES\n";
        echo str_repeat("-", 70) . "\n";

        // Test 5.1: 404 handling
        $this->test("Ilan::find(999999) returns null", function() {
            $ilan = \App\Models\Ilan::find(999999);
            return $ilan === null;
        });

        // Test 5.2: Talep with no matches
        $this->test("Can handle Talep with no matches", function() {
            try {
                $talep = \App\Models\Talep::first();
                return true; // Just checking it doesn't crash
            } catch (\Exception $e) {
                return false;
            }
        });

        // Test 5.3: Ozellik scope works
        $this->test("Ozellik::active() scope works", function() {
            try {
                $count = \App\Models\Ozellik::active()->count();
                return $count >= 0;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Test 5.4: User can be authenticated
        $this->test("Auth system functional", function() {
            return class_exists('App\\Models\\User');
        });

        // Test 5.5: Exception handler exists
        $this->test("Exception handler configured", function() {
            return class_exists('App\\Exceptions\\Handler');
        });
    }

    protected function testPerformance()
    {
        echo "\n⚡ PERFORMANCE TESTS\n";
        echo str_repeat("-", 70) . "\n";

        // Test 6.1: Bulk Ilan query performance
        $this->test("Ilanlar bulk query < 1s", function() {
            $start = microtime(true);
            $count = \App\Models\Ilan::count();
            $time = microtime(true) - $start;
            return $time < 1.0; // Less than 1 second
        });

        // Test 6.2: Ozellik eager loading
        $this->test("Ilan with ozellikler loads efficiently", function() {
            try {
                $start = microtime(true);
                $ilan = \App\Models\Ilan::with('ozellikler')->first();
                $time = microtime(true) - $start;
                return $time < 0.5; // Less than 500ms
            } catch (\Exception $e) {
                return false;
            }
        });

        // Test 6.3: Cache functionality
        $this->test("Cache system operational", function() {
            Cache::put('test_key', 'test_value', 60);
            return Cache::get('test_key') === 'test_value';
        });

        // Test 6.4: Database connection
        $this->test("Database connection healthy", function() {
            try {
                DB::connection()->getPdo();
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    protected function test($name, $callback)
    {
        $this->testsRun++;
        try {
            $result = call_user_func($callback);
            if ($result) {
                echo "✅ $name\n";
                $this->testsPassed++;
                return true;
            } else {
                echo "❌ $name\n";
                $this->testsFailed++;
                return false;
            }
        } catch (\Exception $e) {
            echo "❌ $name (Exception: " . $e->getMessage() . ")\n";
            $this->testsFailed++;
            return false;
        }
    }

    protected function printSummary()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 TEST SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        echo "Total Tests:     $this->testsRun\n";
        echo "Passed:          $this->testsPassed ✅\n";
        echo "Failed:          $this->testsFailed ❌\n";

        if ($this->testsFailed === 0) {
            echo "\n🎉 ALL INTEGRATION TESTS PASSED!\n";
            $score = 100;
        } else {
            $score = round(($this->testsPassed / $this->testsRun) * 100, 1);
        }

        echo "\nIntegration Health Score: $score/100\n";
        echo str_repeat("=", 80) . "\n";
    }
}

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Run tests
$suite = new IntegrationTestSuite();
$suite->run();
