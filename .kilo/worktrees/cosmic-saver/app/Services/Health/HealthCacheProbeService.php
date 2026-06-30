<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\Cache;

class HealthCacheProbeService
{
    public function probe(string $key, mixed $value, int $ttlSeconds): bool
    {
        Cache::put($key, $value, $ttlSeconds);

        return Cache::get($key) === $value;
    }
}
