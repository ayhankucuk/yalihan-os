<?php

namespace App\Services\CRM;

use App\Models\Kisi;
use Carbon\Carbon;
use App\Traits\GuardsAgentWrites;

class KisiScoringService
{
    use GuardsAgentWrites;
    /**
     * Kişi için lead score hesapla (0-100)
     */
    public function calculateScore(Kisi $kisi): int
    {
        $score = 0;

        // Son etkileşim (0-20 puan)
        $score += $this->sonEtkilesimSkoru($kisi);

        // İlan sayısı (0-20 puan)
        $score += $this->ilanSayisiSkoru($kisi);

        // Talep sayısı (0-20 puan)
        $score += $this->talepSayisiSkoru($kisi);

        // Pipeline stage (0-20 puan)
        $score += $this->pipelineSkoru($kisi);

        // Referans (0-10 puan)
        $score += $this->referansSkoru($kisi);

        // VIP segment bonus (0-10 puan)
        $score += $this->segmentSkoru($kisi);

        return min(100, max(0, $score));
    }

    private function sonEtkilesimSkoru(Kisi $kisi): int
    {
        if (! $kisi->son_etkilesim) {
            return 0;
        }

        $gunFarki = Carbon::now()->diffInDays($kisi->son_etkilesim);

        return match (true) {
            $gunFarki <= 7 => 20,
            $gunFarki <= 14 => 15,
            $gunFarki <= 30 => 10,
            $gunFarki <= 60 => 5,
            default => 0,
        };
    }

    private function ilanSayisiSkoru(Kisi $kisi): int
    {
        $ilanSayisi = $kisi->ilanlar()->count();

        return match (true) {
            $ilanSayisi >= 10 => 20,
            $ilanSayisi >= 5 => 15,
            $ilanSayisi >= 3 => 10,
            $ilanSayisi >= 1 => 5,
            default => 0,
        };
    }

    private function talepSayisiSkoru(Kisi $kisi): int
    {
        $talepSayisi = $kisi->talepler()->count();

        return match (true) {
            $talepSayisi >= 5 => 20,
            $talepSayisi >= 3 => 15,
            $talepSayisi >= 1 => 10,
            default => 0,
        };
    }

    private function pipelineSkoru(Kisi $kisi): int
    {
        return match ($kisi->crm_surec_asamasi) {
            \App\Enums\KisiDurumu::ISLEMYAPMIS->value => 20,
            \App\Enums\KisiDurumu::SICAK->value => 15,
            \App\Enums\KisiDurumu::TAKIPTE->value => 10,
            \App\Enums\KisiDurumu::ILGILI->value => 5,
            default => 0,
        };
    }

    private function referansSkoru(Kisi $kisi): int
    {
        return $kisi->referans_kisi_id ? 10 : 0;
    }

    /**
     * Calculate segment bonus score based on kisi_tipi VIP keyword.
     *
     * @param Kisi|mixed $kisi  Accepts Kisi model or test double.
     *                           Type relaxed from Kisi to mixed to enable unit testing
     *                           with test doubles (avoids enum cast interference in tests).
     */
    private function segmentSkoru(object $kisi): int
    {
        // Context7: segment column is deprecated, using kisi_tipi for potential bonuses
        $kisiTipi = is_object($kisi->kisi_tipi) ? ($kisi->kisi_tipi->value ?? '') : (string) ($kisi->kisi_tipi ?? '');
        // FIX: strtolower+str_contains was locale-dependent (Turkish İ/i) and matched substrings (PROVIP→VIP false-positive).
        // Turkish dotted İ (U+0130) normalizes to I so mb_stripos finds "vip" correctly in "VİP".
        // Boundary guard prevents "provip"/"antevip" false-positives.
        $normalized = mb_strtolower(mb_ereg_replace('İ', 'I', $kisiTipi), 'UTF-8');
        if (mb_stripos($normalized, 'vip') === false) {
            return 0;
        }
        $vipPos  = mb_stripos($normalized, 'vip');
        $after   = mb_substr($normalized, $vipPos + 3, 4, 'UTF-8');
        $before  = mb_substr($normalized, 0, $vipPos, 'UTF-8');
        // Reject if 'vip' is embedded in another word (alphanumeric on either side)
        if (preg_match('/^[a-zA-Z0-9]/', $after) || preg_match('/[a-zA-Z0-9]$/', $before)) {
            return 0;
        }
        return 10;
    }

    /**
     * Tüm kişilerin skorlarını yeniden hesapla
     */
    public function recalculateAllScores(): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        Kisi::chunk(100, function ($kisiler) { // governance-bypass: scoring-only bulk read, no Repository equivalent
            foreach ($kisiler as $kisi) {
                $kisi->skor = $this->calculateScore($kisi);
                $kisi->save();
            }
        });
    }

    /**
     * Pipeline stage güncelle ve skoru yeniden hesapla.
     */
    public function updatePipelineStage(Kisi $kisi, $stage): Kisi
    {
        $this->blockAgentWrite(__FUNCTION__);

        // Mapping from int to enum value
        $mapping = [
            1 => \App\Enums\KisiDurumu::POTANSIYEL->value,
            2 => \App\Enums\KisiDurumu::ILGILI->value,
            3 => \App\Enums\KisiDurumu::TAKIPTE->value,
            4 => \App\Enums\KisiDurumu::SICAK->value,
            5 => \App\Enums\KisiDurumu::ISLEMYAPMIS->value,
            0 => \App\Enums\KisiDurumu::PASIF->value,
        ];

        $kisi->crm_surec_asamasi = $mapping[$stage] ?? \App\Enums\KisiDurumu::POTANSIYEL->value;
        $kisi->son_etkilesim = now();
        $kisi->save();

        $kisi->skor = $this->calculateScore($kisi);
        $kisi->save();

        return $kisi->fresh();
    }

    /**
     * Segment güncelle.
     */
    public function updateSegment(Kisi $kisi, string $segment): Kisi
    {
        $this->blockAgentWrite(__FUNCTION__);

        // Context7: segment is now a virtual/logical field mapping to crm_surec_asamasi or kisi_tipi
        // For compatibility with CRMController/Orchestrator, we update kisi_tipi if it's about classification
        $kisi->kisi_tipi = $segment;
        $kisi->save();

        return $kisi;
    }

    /**
     * Perform an audit/analysis of a person's profile for missing info and eligibility.
     * Context7: C7-KISI-AUDIT-2026-04-16
     */
    public function performAudit(int $kisiId): array
    {
        $kisi = Kisi::find($kisiId); // governance-bypass: audit read-only lookup, no ownership scope required

        if (!$kisi) {
            return ['success' => false, 'message' => 'Kişi bulunamadı'];
        }

        $suggestions = [];

        // CRM skoruna göre öneriler
        $score = $this->calculateScore($kisi);
        if ($score < 50) {
            $suggestions[] = [
                'type' => 'crm_score', // context7-ignore
                'priority' => 'high',
                'message' => 'CRM skoru düşük (' . $score . '/100). Eksik bilgileri tamamlayın.',
                'actions' => [
                    'tc_kimlik' => !$kisi->tc_kimlik ? 'TC Kimlik No ekleyin' : null,
                    'telefon' => !$kisi->telefon ? 'Telefon numarası ekleyin' : null,
                    'email' => !$kisi->email ? 'E-posta adresi ekleyin' : null,
                    'adres' => !$kisi->il_id ? 'Adres bilgilerini tamamlayın' : null,
                ],
            ];
        }

        // İlan sahibi uygunluğu
        if (!$kisi->isOwnerEligible()) {
            $suggestions[] = [
                'type' => 'owner_eligibility', // context7-ignore
                'priority' => 'medium',
                'message' => 'Bu kişi ilan sahibi olarak uygun değil.',
                'actions' => [
                    'tc_kimlik' => !$kisi->tc_kimlik ? 'TC Kimlik No gerekli' : null,
                    'telefon' => !$kisi->telefon ? 'Telefon numarası gerekli' : null,
                    'adres' => !$kisi->il_id ? 'Adres bilgileri gerekli' : null,
                ],
            ];
        }

        // Kişi tipi önerileri (Context7: kisi_tipi)
        if (!$kisi->kisi_tipi) {
            $suggestions[] = [
                'type' => 'kisi_tipi', // context7-ignore
                'priority' => 'medium',
                'message' => 'Kişi tipi belirtilmemiş.',
                'actions' => [
                    'kisi_tipi' => 'Kişi tipini seçin',
                ],
            ];
        }

        return [
            'success' => true,
            'suggestions' => array_filter($suggestions),
            'crm_score' => $score,
            'is_owner_eligible' => $kisi->isOwnerEligible(),
        ];
    }
}
