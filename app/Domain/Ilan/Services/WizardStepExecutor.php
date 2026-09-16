<?php

namespace App\Domain\Ilan\Services;

use App\Domain\Ilan\Events\WizardStepCompleted;
use App\Domain\Ilan\Events\WizardSubmitted;
use App\Models\Ilan;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ⚡ WizardStepExecutor
 *
 * Sorumluluk: Sihirbaz adımlarını yürütür, verileri IlanCrudService üzerinden kalıcılaştırır,
 * adım durumunu ilerletir ve domain olaylarını (WizardStepCompleted, WizardSubmitted) fırlatır.
 */
class WizardStepExecutor
{
    public function __construct(
        private readonly IlanCrudService $ilanCrudService,
        private readonly WizardSessionManager $sessionManager
    ) {}

    /**
     * Sihirbaz adımını kaydeder/günceller ve olayları fırlatır.
     */
    public function executeStep(
        int $userId,
        int $stepNumber,
        array $stepData,
        ?int $ilanId = null,
        ?int $expectedLockVersion = null
    ): array {
        return DB::transaction(function () use ($userId, $stepNumber, $stepData, $ilanId, $expectedLockVersion) {
            $ilan = null;

            if ($ilanId) {
                $ilan = Ilan::findOrFail($ilanId);
                $ilan = $this->ilanCrudService->update($ilan, $stepData);
            } else {
                // Yeni ilan başlatma (Adım 1)
                $ilan = $this->ilanCrudService->store($stepData);
            }

            // Oturum durumunu güncelle
            $sessionState = $this->sessionManager->advanceStep(
                $userId,
                $ilan->id,
                $stepNumber,
                $stepData,
                $expectedLockVersion
            );

            // Adım tamamlandı olayını fırlat
            event(new WizardStepCompleted($ilan, $stepNumber, $stepData));

            Log::info('WizardStepExecutor: Step executed successfully', [
                'user_id' => $userId,
                'ilan_id' => $ilan->id,
                'step' => $stepNumber,
                'lock_version' => $sessionState['lock_version'],
            ]);

            return [
                'success' => true,
                'ilan_id' => $ilan->id,
                'ilan' => $ilan,
                'session' => $sessionState,
            ];
        });
    }

    /**
     * Sihirbazı nihai olarak onaylar ve ilanı yayına/incelemeye alır.
     */
    public function submitWizard(int $userId, int $ilanId, array $finalOptions = []): array
    {
        return DB::transaction(function () use ($userId, $ilanId, $finalOptions) {
            $ilan = Ilan::findOrFail($ilanId);

            $updatePayload = [
                'yayin_durumu' => $finalOptions['yayin_durumu'] ?? 'yayinda',
            ];

            $ilan = $this->ilanCrudService->update($ilan, $updatePayload);

            // Oturumu temizle
            $this->sessionManager->clearSession($userId, $ilanId);

            // Nihai submitted olayını fırlat
            event(new WizardSubmitted($ilan, $finalOptions));

            Log::info('WizardStepExecutor: Wizard submitted successfully', [
                'user_id' => $userId,
                'ilan_id' => $ilan->id,
                'yayin_durumu' => $ilan->yayin_durumu,
            ]);

            return [
                'success' => true,
                'message' => 'İlan sihirbazı başarıyla tamamlandı.',
                'ilan_id' => $ilan->id,
                'ilan' => $ilan,
            ];
        });
    }
}
