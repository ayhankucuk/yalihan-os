<?php

namespace App\Domain\Ilan\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🔒 WizardSessionManager
 *
 * Sorumluluk: Sihirbaz adımlarının oturum durumunu, tamamlanan aşamaları
 * ve eşzamanlı çakışmaları önleyen iyimser kilit (optimistic locking) mekanizmasını yönetir.
 */
class WizardSessionManager
{
    private const SESSION_TTL_SECONDS = 86400; // 24 saat

    /**
     * Oturum anahtarı oluşturur.
     */
    public function getSessionKey(int $userId, int $ilanId): string
    {
        return "wizard:session:u{$userId}:i{$ilanId}";
    }

    /**
     * Oturum durumunu getirir veya varsayılan başlatır.
     */
    public function getSessionState(int $userId, int $ilanId): array
    {
        $key = $this->getSessionKey($userId, $ilanId);

        return Cache::get($key, [
            'user_id' => $userId,
            'ilan_id' => $ilanId,
            'current_step' => 1,
            'completed_steps' => [],
            'step_data' => [],
            'lock_version' => 1,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Adım ilerlemesini kaydeder (İyimser Kilit Kontrollü).
     *
     * @throws \RuntimeException Eğer lock_version uyuşmazlığı varsa (eşzamanlı değişiklik)
     */
    public function advanceStep(int $userId, int $ilanId, int $completedStep, array $data, ?int $expectedLockVersion = null): array
    {
        $key = $this->getSessionKey($userId, $ilanId);
        $state = $this->getSessionState($userId, $ilanId);

        if ($expectedLockVersion !== null && $state['lock_version'] !== $expectedLockVersion) {
            Log::warning('WizardSessionManager: Concurrent modification detected', [
                'user_id' => $userId,
                'ilan_id' => $ilanId,
                'current_lock' => $state['lock_version'],
                'expected_lock' => $expectedLockVersion,
            ]);

            throw new \RuntimeException('Oturum başka bir sekmede güncellendi. Lütfen sayfayı yenileyin.');
        }

        if (!in_array($completedStep, $state['completed_steps'], true)) {
            $state['completed_steps'][] = $completedStep;
            sort($state['completed_steps']);
        }

        $state['current_step'] = max($state['current_step'], $completedStep + 1);
        $state['step_data']["step_{$completedStep}"] = $data;
        $state['lock_version']++;
        $state['updated_at'] = now()->toISOString();

        Cache::put($key, $state, self::SESSION_TTL_SECONDS);

        return $state;
    }

    /**
     * Oturumu temizler (ilan tamamlandığında veya iptal edildiğinde).
     */
    public function clearSession(int $userId, int $ilanId): bool
    {
        return Cache::forget($this->getSessionKey($userId, $ilanId));
    }
}
