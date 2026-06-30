<?php

namespace App\Services\CRM;

use App\Models\Eslesme;
use App\Models\MatchingFeedback;
use App\Models\BuyerMatchLog;
use App\Models\User;
use App\Traits\GuardsAgentWrites;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🛰️ MatchingAuthorityService
 *
 * Canonical authority for the Matching (Eslesme) domain.
 * Consolidates manual matches, AI-suggested feedback, and auto-matching pipelines.
 * Part of CRM Authority Hardening (T2-B).
 */
class MatchingAuthorityService
{
    use GuardsAgentWrites;

    /**
     * 🛰️ Create a new hard match (Authority Entrypoint)
     *
     * @param array $data
     * @param User|null $actor
     * @return Eslesme
     */
    public function createMatch(array $data, ?User $actor = null): Eslesme
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($data, $actor) {
            $eslesme = Eslesme::create([
                'kisi_id' => $data['kisi_id'],
                'ilan_id' => $data['ilan_id'],
                'talep_id' => $data['talep_id'] ?? null,
                'danisman_id' => $data['danisman_id'] ?? ($actor?->id),
                'eslesme_durumu' => $data['eslesme_durumu'] ?? 'Beklemede',
                'notlar' => $data['notlar'] ?? null,
                'eslesme_tarihi' => $data['eslesme_tarihi'] ?? now(),
                'skor' => $data['skor'] ?? 0,
            ]);

            $this->logActivity('match_created', $eslesme, $actor);

            return $eslesme;
        });
    }

    /**
     * 🛰️ Record feedback for a soft match (Authority Entrypoint)
     *
     * Replaces the fragmented logic in MatchService.
     *
     * @param int $feedbackId
     * @param string $outcome ('rejected', 'accepted', etc.)
     * @param string $reason
     * @param User|null $actor
     * @return MatchingFeedback
     */
    public function recordFeedback(int $feedbackId, string $outcome, string $reason, ?User $actor = null): MatchingFeedback
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($feedbackId, $outcome, $reason, $actor) {
            $feedback = MatchingFeedback::findOrFail($feedbackId);

            // 1. Update outcome (Context7 canonical: yayin_durumu_log)
            $feedback->update([
                'yayin_durumu_log' => $outcome,
                'danisman_notu' => $reason,
                'sonuc_olusturuldu' => true,
                'sonuc_tarihi' => now(),
            ]);

            // 2. Log outcome to BuyerMatchLog (Consolidated Log)
            BuyerMatchLog::create([
                'ilan_id' => $feedback->ilan_id,
                'talep_id' => $feedback->talep_id,
                'buyer_id' => $feedback->talep?->kisi_id ?? $feedback->kisi_id ?? null,
                'reason' => "Outcome: {$outcome}. Reason: {$reason}",
                'match_score' => $feedback->match_score,
                'metadata' => [
                    'actor_id' => $actor?->id,
                    'trigger' => 'manual_feedback',
                ]
            ]);

            $this->logActivity('feedback_recorded', $feedback, $actor, [
                'outcome' => $outcome,
                'reason' => $reason
            ]);

            return $feedback;
        });
    }

    /**
     * 🛰️ Run Auto-Match Pipeline (Authority Entrypoint)
     *
     * Base for future AI automation. Currently generates soft matches (feedback records).
     *
     * @param int|null $talepId
     * @param User|null $actor
     * @return int Number of matches found
     */
    public function runAutoMatchPipeline(?int $talepId = null, ?User $actor = null): int
    {
        $this->blockAgentWrite(__FUNCTION__);

        // Placeholder for real AI logic. For now, we establish the entrypoint.
        Log::info('Matching Authority: Auto-match pipeline triggered', [
            'talep_id' => $talepId,
            'actor_id' => $actor?->id
        ]);

        return 0; // Next phase will connect real scoring
    }

    /**
     * 🛰️ Log a potential match from AI (Authority Entrypoint)
     */
    public function logPotentialMatch(int $ilanId, int $talepId, float $score, ?User $actor = null): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        MatchingFeedback::updateOrCreate(
            ['ilan_id' => $ilanId, 'talep_id' => $talepId],
            [
                'match_score' => $score,
                'yayin_durumu_log' => 'pending',
                'danisman_id' => $actor?->id
            ]
        );
    }

    /**
     * Standardized forensic logging.
     */
    protected function logActivity(string $action, $item, ?User $actor = null, array $metadata = []): void
    {
        $payload = array_merge([
            'resource_id' => $item->id,
            'resource_type' => get_class($item),
            'action' => $action,
            'actor_id' => $actor?->id,
            'timestamp' => now()->toIso8601String(),
        ], $metadata);

        Log::channel('module_changes')->info("Matching Domain: {$action}", $payload);
        
        LogService::info("CRM Authority: Matching {$action}", [
            'id' => $item->id,
            'actor' => $actor?->id
        ]);
    }
}
