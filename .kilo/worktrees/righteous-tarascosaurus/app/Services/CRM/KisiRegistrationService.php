<?php

namespace App\Services\CRM;

use App\Models\Kisi;
use App\Repositories\KisiRepository;
use App\Services\Admin\KisiManagerService;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;

/**
 * 🛰️ KisiRegistrationService
 *
 * Part of CRM Authority Hardening.
 * Central authority for customer registration, validation and duplicate detection.
 *
 * @governance PHASE4B_SERVICE_GOVERNANCE
 * @refactored 2026-05-12
 * @reason Migrated from direct model access to Repository Kernel pattern
 */
class KisiRegistrationService
{
    /**
     * @param KisiRepository $kisiRepository
     * @param KisiManagerService $managerService
     * @param \App\Services\CRM\KisiScoringService $scoringService
     * @param \App\Services\AI\YalihanCortex $cortex
     */
    public function __construct(
        protected KisiRepository $kisiRepository,
        protected KisiManagerService $managerService,
        protected \App\Services\CRM\KisiScoringService $scoringService,
        protected \App\Services\AI\YalihanCortex $cortex
    ) {}

    /**
     * Search/Query personas via central authority rules.
     */
    public function search(array $filters): array
    {
        // Future: Central search logic integration
        return [];
    }

    /**
     * 🛰️ Register a new Person (Authority Entrypoint)
     *
     * @param array $data
     * @param int|null $authId
     * @return Kisi
     * @throws \Exception
     */
    public function register(array $data, ?int $authId = null): Kisi
    {
        // 1. Central Pre-Registration Checks
        $duplicateCheck = $this->validateDuplicate($data);
        if ($duplicateCheck['duplicate']) {
            throw new \Exception('DUPLICATE_KISI_DETECTED');
        }

        // 2. Delegate to the underlying manager (Bridge Phase)
        $data['user_id'] = $authId;

        LogService::info('CRM: Registration triggered', ['email' => $data['email'] ?? null]);

        $kisi = $this->managerService->store($data);

        // 3. Post-Registration Intelligence (C4 Alignment)
        $this->scoringService->performAudit($kisi->id);

        // Async: Cortex enrichment could be queued, but for now we trigger it synchronously for immediate value
        $this->cortex->requestCustomerAiEnrichment($kisi->id);

        return $kisi;
    }

    /**
     * 🛰️ Update an existing Person (Authority Entrypoint)
     *
     * @param Kisi $kisi
     * @param array $data
     * @return Kisi
     * @throws \Exception
     */
    public function update(Kisi $kisi, array $data): Kisi
    {
        LogService::info('CRM: Kisi update started', ['kisi_id' => $kisi->id]);

        $updatedKisi = $this->managerService->update($kisi, $data);

        // Post-update Intelligence
        $this->scoringService->performAudit($updatedKisi->id);

        return $updatedKisi;
    }

    /**
     * 🛰️ Sync Tags for a Person
     *
     * @param Kisi $kisi
     * @param array $tagIds
     * @return bool
     */
    public function syncTags(Kisi $kisi, array $tagIds): bool
    {
        // Centralized tagging logic
        $kisi->etiketler()->sync($tagIds);

        LogService::info('CRM: Kisi tags synced', ['kisi_id' => $kisi->id, 'tag_ids' => $tagIds]);

        // Tags might affect scoring
        $this->scoringService->performAudit($kisi->id);

        return true;
    }

    /**
     * 🛰️ Validate duplicates based on CRM Domain Rules
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel instead of direct model access
     *
     * @param array $data
     * @return array
     */
    public function validateDuplicate(array $data): array
    {
        $email = $data['email'] ?? null;
        $telefon = $data['telefon'] ?? null;
        $tc_kimlik = $data['tc_kimlik'] ?? null;

        $duplicates = [];

        // ✅ Email check via Repository
        if ($email) {
            $duplicate = $this->kisiRepository->findByEmail($email, auth()->user());
            if ($duplicate) {
                $duplicates[] = [
                    'tip' => 'email',
                    'kisi' => $duplicate->tam_ad,
                    'value' => $email,
                ];
            }
        }

        // ✅ Phone check via Repository
        if ($telefon) {
            $duplicate = $this->kisiRepository->findByPhone($telefon, auth()->user());
            if ($duplicate) {
                $duplicates[] = [
                    'tip' => 'telefon',
                    'kisi' => $duplicate->tam_ad,
                    'value' => $telefon,
                ];
            }
        }

        // ✅ TC check via Repository
        if ($tc_kimlik) {
            $duplicate = $this->kisiRepository->findByTcKimlik($tc_kimlik, auth()->user());
            if ($duplicate) {
                $duplicates[] = [
                    'tip' => 'tc_kimlik',
                    'kisi' => $duplicate->tam_ad,
                    'value' => $tc_kimlik,
                ];
            }
        }

        return [
            'duplicate' => !empty($duplicates),
            'duplicates' => $duplicates,
        ];
    }
}
