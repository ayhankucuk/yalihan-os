<?php

namespace App\Domain\Core\Security;

/**
 * Interface GlobalHardlockManagerContract
 * @package App\Domain\Core\Security
 * @description Phase 20: Çalışma zamanında kiracı veya sistem seviyesindeki hardlock durumlarını yöneten güvenlik kontratı.
 */
interface GlobalHardlockManagerContract
{
    /**
     * Kiracı bazında veya sistem genelinde hardlock aktif mi kontrol eder.
     *
     * @param int|null $tenantId
     * @return bool
     */
    public function isHardlocked(?int $tenantId = null): bool;

    /**
     * Hardlock korumasını devreye sokar.
     *
     * @param int|null $tenantId
     * @param string $reason
     * @return void
     */
    public function initiateHardlock(?int $tenantId = null, string $reason): void;

    /**
     * Kurtarma anahtarı ile hardlock durumunu çözer.
     *
     * @param int $tenantId
     * @param string $recoveryToken
     * @return bool
     */
    public function releaseHardlock(int $tenantId, string $recoveryToken): bool;
}
