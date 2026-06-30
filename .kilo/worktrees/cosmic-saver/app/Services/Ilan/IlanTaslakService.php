<?php

namespace App\Services\Ilan;

use App\Models\Ilan;
use App\Models\IlanTaslak;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * IlanTaslakService
 * Context7 Standard Architecture
 * Sprint Plan A3 Implementation
 */
class IlanTaslakService
{
    public function __construct(
        private readonly IlanCrudService $ilanCrudService,
    ) {}

    /**
     * Get or create active draft for user
     *
     * @param $user
     * @param int|null $siteId
     * @return IlanTaslak
     */
    public function getOrCreateActiveDraft($user, $siteId = null)
    {
        return IlanTaslak::firstOrCreate(
            [
                'user_id' => $user->id,
                'taslak_durumu' => 1, // Aktif
                'site_id' => $siteId
            ],
            [
                'step' => 1,
                'payload' => [],
                'version' => 1
            ]
        );
    }

    /**
     * Load specific draft with authorization
     *
     * @param int $id
     * @param $user
     * @param int|null $siteId
     * @return IlanTaslak|null
     */
    public function loadDraft($id, $user, $siteId = null)
    {
        return IlanTaslak::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Save draft payload and step
     *
     * @param int $id
     * @param array $payload
     * @param int $step
     * @return bool
     * @throws \Exception
     */
    public function saveDraft($id, array $payload, int $step)
    {
        $draft = IlanTaslak::findOrFail($id);

        // Payload size guard (256KB)
        $payloadJson = json_encode($payload);
        if (strlen($payloadJson) > 256 * 1024) {
            throw new \Exception("Payload exceeds 256KB limit.");
        }

        return $draft->update([
            'payload' => $payload,
            'step' => $step,
            'version' => $draft->version + 1,
            'updated_at' => now()
        ]);
    }

    /**
     * Close draft (mark as passive)
     *
     * @param int $id
     * @return bool
     */
    public function closeDraft($id)
    {
        return IlanTaslak::where('id', $id)->update(['taslak_durumu' => 0]);
    }

    /**
     * Commit draft data to a real listing
     *
     * @param int $draftId
     * @return Ilan
     */
    public function commitDraftToIlan($draftId)
    {
        return DB::transaction(function () use ($draftId) {
            $draft = IlanTaslak::findOrFail($draftId);
            $payload = $draft->payload;

            // Phase3-WA: delegated to IlanCrudService as single write authority
            if ($draft->ilan_id) {
                $ilan = Ilan::findOrFail($draft->ilan_id);
                $ilan = $this->ilanCrudService->update($ilan, $payload);
            } else {
                $ilan = $this->ilanCrudService->store($payload);
                $draft->update(['ilan_id' => $ilan->id]);
            }

            $this->closeDraft($draftId);

            return $ilan;
        });
    }
}
