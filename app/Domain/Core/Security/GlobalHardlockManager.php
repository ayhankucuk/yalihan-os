<?php

namespace App\Domain\Core\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class GlobalHardlockManager
 * @package App\Domain\Core\Security
 * @description Phase 20: Cache tabanlı küresel ve kiracı seviyesindeki hardlock işlemlerini yöneten sarsılmaz güvenlik sınıfı.
 */
class GlobalHardlockManager implements GlobalHardlockManagerContract
{
    /**
     * Kiracı bazında veya sistem genelinde hardlock aktif mi kontrol eder.
     *
     * @param int|null $tenantId
     * @return bool
     */
    public function isHardlocked(?int $tenantId = null): bool
    {
        // 1. Sistem genelinde hardlock kontrolü
        if (Cache::get('governance.system_compromised') === true) {
            return true;
        }

        // 2. Kiracı bazında hardlock kontrolü
        if ($tenantId !== null) {
            if (Cache::get("governance.compromised.{$tenantId}") === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hardlock korumasını devreye sokar.
     *
     * @param int|null $tenantId
     * @param string $reason
     * @return void
     */
    public function initiateHardlock(?int $tenantId = null, string $reason): void
    {
        if ($tenantId === null || $tenantId === 0) {
            Cache::forever('governance.system_compromised', true);
            Cache::forever('governance.compromised.SYSTEM', true);
            
            // Log to fallback if secure channel doesn't exist
            try {
                Log::channel('governance_security')->emergency("SYSTEM COMPROMISED: Global Hardlock Initiated. Reason: {$reason}");
            } catch (\Exception $e) {
                Log::emergency("SYSTEM COMPROMISED: Global Hardlock Initiated. Reason: {$reason}");
            }
        } else {
            Cache::forever("governance.compromised.{$tenantId}", true);
            
            try {
                Log::channel('governance_security')->emergency("TENANT COMPROMISED: Hard Lock Initiated for [{$tenantId}]. Reason: {$reason}");
            } catch (\Exception $e) {
                Log::emergency("TENANT COMPROMISED: Hard Lock Initiated for [{$tenantId}]. Reason: {$reason}");
            }
        }
    }

    /**
     * Kurtarma anahtarı ile hardlock durumunu çözer.
     *
     * @param int $tenantId
     * @param string $recoveryToken
     * @return bool
     */
    public function releaseHardlock(int $tenantId, string $recoveryToken): bool
    {
        $expectedToken = config('yalihan.fortress_secure_salt.kripto_anahtar', 'fallback_salt_2026');

        if (!hash_equals($expectedToken, $recoveryToken)) {
            Log::warning("🚨 SAB RECOVERY FAILURE: Invalid recovery token supplied for Tenant: {$tenantId}");
            return false;
        }

        if ($tenantId === 0) {
            Cache::forget('governance.system_compromised');
            Cache::forget('governance.compromised.SYSTEM');
            Log::info("✅ SAB RECOVERY SUCCESS: Global Hardlock lifted successfully.");
        } else {
            Cache::forget("governance.compromised.{$tenantId}");
            Log::info("✅ SAB RECOVERY SUCCESS: Hardlock lifted for Tenant: {$tenantId}");
        }

        return true;
    }
}
