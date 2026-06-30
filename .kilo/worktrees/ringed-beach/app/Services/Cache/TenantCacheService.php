<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TenantCacheService extends CacheService
{
    /**
     * Resolve the current tenant ID
     */
    public function resolveTenantId(): string
    {
        // 1. Check configuration override (e.g. CLI runner or config(['app.tenant_id' => ...]))
        $configId = config('app.tenant_id');
        if ($configId !== null) {
            return (string) $configId;
        }

        // 2. Resolve via authenticated user
        $user = Auth::user();
        if ($user && isset($user->tenant_id)) {
            return (string) $user->tenant_id;
        }

        // 3. Fallback to global or system context
        return 'global';
    }

    /**
     * Generate standardized, tenant-scoped cache key
     *
     * Format: tenant:{tenantId}:{namespace}:{key}:{params?}
     */
    public function key(string $namespace, string $key, array $params = []): string
    {
        $tenantId = $this->resolveTenantId();
        
        // Scope prefix: {tenant:id}:{namespace}:{key}
        // If namespace is 'crm', it naturally becomes {tenant:id}:crm:{key}
        $tenantPrefix = "{tenant:{$tenantId}}";
        
        $parts = [$tenantPrefix, $namespace, $key];

        if (! empty($params)) {
            ksort($params);
            $paramString = implode(':', array_map(function ($k, $v) {
                return $k.'='.(is_array($v) ? md5(json_encode($v)) : $v);
            }, array_keys($params), $params));
            $parts[] = $paramString;
        }

        return implode(':', $parts);
    }

    /**
     * Flush cache by prefix scoped to current tenant
     */
    public function flushByPrefix(string $prefix): int
    {
        $tenantId = $this->resolveTenantId();
        $tenantPrefix = "{tenant:{$tenantId}}";
        
        $count = 0;

        if ($this->localSupportsWildcard()) {
            $pattern = $tenantPrefix . ':' . $prefix . '*';
            $keys = $this->localGetKeysByPattern($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
                $count++;
            }
        } else {
            // Fallback flush using standard tags if supported
            Log::warning('TenantCacheService: Wildcard flush not fully supported, flushing entire prefix.', [
                'prefix' => $prefix,
            ]);
            return parent::flushByPrefix($prefix);
        }

        return $count;
    }

    /**
     * Local helper to check if cache driver supports wildcard pattern matching
     */
    private function localSupportsWildcard(): bool
    {
        try {
            $store = Cache::getStore();
            return method_exists($store, 'getRedis');
        } catch (\Exception $e) {
            Log::debug('TenantCacheService localSupportsWildcard check failed: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }

    /**
     * Local helper to get cache keys by pattern (Redis only)
     */
    private function localGetKeysByPattern(string $pattern): array
    {
        if (!$this->localSupportsWildcard()) {
            return [];
        }

        try {
            $redis = Cache::getStore()->getRedis();
            return $redis->keys($pattern) ?: [];
        } catch (\Exception $e) {
            Log::warning('Wildcard cache key pattern matching failed inside TenantCacheService', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
