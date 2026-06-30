<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\CacheManager;
use Illuminate\Support\Facades\Redis;

class CacheStatsController extends Controller
{
    public function __construct(
        private CacheManager $cache
    ) {}

    public function index()
    {
        $stats = $this->cache->getStats();
        $redisInfo = Redis::connection()->info();

        // Predis returns nested arrays (e.g., ['Memory']['used_memory'])
        // PhpRedis returns flat arrays (e.g., ['used_memory'])
        // Flatten for view compatibility
        $redis = [];
        if (is_array($redisInfo) && !empty($redisInfo)) {
            foreach ($redisInfo as $value) {
                if (is_array($value)) {
                    $redis = array_merge($redis, $value);
                }
            }
            // If still empty, it was already flat
            if (empty($redis)) {
                $redis = $redisInfo;
            }
        }

        return view('admin.cache-stats', [
            'stats' => $stats,
            'redis' => $redis,
        ]);
    }

    public function api()
    {
        return response()->json([
            'cache' => $this->cache->getStats(),
            'redis' => Redis::connection()->info(),
        ]);
    }
}
