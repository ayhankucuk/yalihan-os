<?php

namespace Tests\Feature\AI;

use App\Services\AI\AiRolloutService;
use App\Services\AI\VisionAnalysisService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiRuntimeControlTest extends TestCase
{

    /** @test */
    public function kill_switch_blocks_all_ai_calls()
    {
        Config::set('ai-runtime.ai_enabled', false);

        $rolloutService = app(AiRolloutService::class);

        $this->assertFalse($rolloutService->isEnabledForUser('vision'));
        $this->assertFalse($rolloutService->isEnabledForUser('suggestion'));
    }

    /** @test */
    public function vision_disabled_returns_graceful_fallback()
    {
        Config::set('ai-runtime.ai_enabled', true);
        Config::set('ai-runtime.vision_enabled', false);

        $visionService = app(VisionAnalysisService::class);
        $result = $visionService->analyzeImages(['/path/to/image.jpg'], 1, 1);

        $this->assertTrue($result->success);
        $this->assertTrue($result->metadata['ai_disabled'] ?? false);
        $this->assertEmpty($result->suggestions);
    }

    /** @test */
    public function vision_disabled_with_cache_returns_cached_results()
    {
        Config::set('ai-runtime.ai_enabled', true);
        Config::set('ai-runtime.vision_enabled', false);

        // Seed cache
        $images = ['/path/to/image.jpg'];
        $imagesHash = sha1(serialize($images) . '1' . '1');
        $cacheKey = "ai_vision_results_{$imagesHash}";
        
        Cache::put($cacheKey, [
            'suggestions' => ['cached_suggestion'],
            'metadata' => ['cached' => true]
        ], 3600);

        $visionService = app(VisionAnalysisService::class);
        $result = $visionService->analyzeImages($images, 1, 1);

        $this->assertTrue($result->success);
        $this->assertTrue($result->metadata['ai_disabled'] ?? false);
        $this->assertTrue($result->metadata['cache_hit'] ?? false);
        $this->assertEquals(['cached_suggestion'], $result->suggestions);
    }

    /** @test */
    public function rollout_percentage_is_deterministic()
    {
        Config::set('ai-runtime.ai_enabled', true);
        Config::set('ai-runtime.vision_enabled', true);
        Config::set('ai-runtime.rollout.vision_percentage', 50);

        $rolloutService = app(AiRolloutService::class);

        // Same user should always get same result
        $result1 = $rolloutService->isEnabledForUser('vision', 123);
        $result2 = $rolloutService->isEnabledForUser('vision', 123);
        $result3 = $rolloutService->isEnabledForUser('vision', 123);

        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }

    /** @test */
    public function rollout_percentage_distribution_is_uniform()
    {
        Config::set('ai-runtime.ai_enabled', true);
        Config::set('ai-runtime.vision_enabled', true);
        Config::set('ai-runtime.rollout.vision_percentage', 50);

        $rolloutService = app(AiRolloutService::class);

        $enabled = 0;
        $total = 1000;

        for ($i = 1; $i <= $total; $i++) {
            if ($rolloutService->isEnabledForUser('vision', $i)) {
                $enabled++;
            }
        }

        $percentage = ($enabled / $total) * 100;

        // Should be roughly 50% (allow 10% margin)
        $this->assertGreaterThan(40, $percentage);
        $this->assertLessThan(60, $percentage);
    }

    /** @test */
    public function rollout_100_percent_enables_for_all_users()
    {
        Config::set('ai-runtime.ai_enabled', true);
        Config::set('ai-runtime.vision_enabled', true);
        Config::set('ai-runtime.rollout.vision_percentage', 100);

        $rolloutService = app(AiRolloutService::class);

        for ($i = 1; $i <= 100; $i++) {
            $this->assertTrue($rolloutService->isEnabledForUser('vision', $i));
        }
    }

    /** @test */
    public function rollout_0_percent_disables_for_all_users()
    {
        Config::set('ai-runtime.ai_enabled', true);
        Config::set('ai-runtime.vision_enabled', true);
        Config::set('ai-runtime.rollout.vision_percentage', 0);

        $rolloutService = app(AiRolloutService::class);

        for ($i = 1; $i <= 100; $i++) {
            $this->assertFalse($rolloutService->isEnabledForUser('vision', $i));
        }
    }
}
