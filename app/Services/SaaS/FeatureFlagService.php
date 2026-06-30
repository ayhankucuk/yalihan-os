<?php

declare(strict_types=1);

namespace App\Services\SaaS;

use App\Models\SaaS\FeatureFlag;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeatureFlagService
{
    /**
     * Check if a feature flag is enabled for the current context.
     *
     * @param string $key
     * @param User|null $user
     * @return bool
     */
    public function isEnabled(string $key, ?User $user = null): bool
    {
        // Cache flag model to avoid query overhead
        $flag = Cache::remember("feature_flag:model:{$key}", 300, function () use ($key) {
            return FeatureFlag::where('key', $key)->first();
        });

        if (!$flag) {
            return false;
        }

        if (!$flag->is_enabled) {
            return false;
        }

        // If no rules are set, it is globally active
        if (empty($flag->rules)) {
            return true;
        }

        // If rules exist but no user is provided, deny access (scoped flags require context)
        if (!$user) {
            return false;
        }

        return $this->evaluateRules($flag->rules, $user);
    }

    /**
     * Enable a feature flag.
     */
    public function enable(string $key): void
    {
        FeatureFlag::updateOrCreate(['key' => $key], ['is_enabled' => true]);
        $this->clearCache($key);
    }

    /**
     * Disable a feature flag.
     */
    public function disable(string $key): void
    {
        FeatureFlag::updateOrCreate(['key' => $key], ['is_enabled' => false]);
        $this->clearCache($key);
    }

    /**
     * Clear cache for a feature flag.
     */
    public function clearCache(string $key): void
    {
        Cache::forget("feature_flag:model:{$key}");
    }

    /**
     * Evaluate rules against the authenticated user.
     */
    protected function evaluateRules(array $rules, User $user): bool
    {
        // 1. Users list rule
        if (isset($rules['users']) && is_array($rules['users'])) {
            if (in_array($user->id, $rules['users'], true) || in_array($user->email, $rules['users'], true)) {
                return true;
            }
        }

        // 2. Tenants list rule
        if (isset($rules['tenants']) && is_array($rules['tenants']) && !empty($user->tenant_id)) {
            if (in_array($user->tenant_id, $rules['tenants'], true)) {
                return true;
            }
        }

        // 3. Rollout percentage rule
        if (isset($rules['percentage']) && is_numeric($rules['percentage'])) {
            $percentage = (int) $rules['percentage'];
            // Deterministic hash based on user ID to keep rollout stable per user
            $hash = crc32($user->id . '_rollout_salt');
            $userScore = abs($hash) % 100;

            if ($userScore < $percentage) {
                return true;
            }
        }

        return false;
    }
}
