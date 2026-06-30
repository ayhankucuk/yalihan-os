<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class ControllerCacheMutationService
{
    public function put(string $key, mixed $value, int $ttlSeconds): bool
    {
        Cache::put($key, $value, $ttlSeconds);

        return true;
    }

    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }
}
