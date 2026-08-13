<?php

namespace App\Services\Ydl;

use App\DTOs\Ydl\YdlContextOutput;
use App\DTOs\Ydl\YdlPublishRecommendation;
use App\Enums\Governance\GovernanceState;
use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Services\Governance\GovernanceTransitionGuard;
use App\Services\Listing\ListingScoreService;

/**
 * YdlPublishReadinessService — Publish readiness evaluation for YDL Phase 3.
 *
 * PILOT-001 Wave 1 — YDL Context Integration
 *
 * Deterministic. No LLM inference.
 *
 * Evaluation flow:
 *   1. Read YDL authority from context (session-start)
 *   2. Load Ilan + compute scores
 *   3. Run publish gate checks
 *   4. Produce YdlPublishRecommendation DTO for agent consumption
 *
 * This service does NOT trigger state transitions. It only evaluates
 * publish readiness and produces a recommendation. The actual publish
 * transition remains gated by IlanCrudService + YalihanLifecycle.
 */
class YdlPublishReadinessService
{
    private const PILOT = 'PILOT-001';

    public function __construct(
        private readonly ListingScoreService $scoreService,
        private readonly GovernanceTransitionGuard $governanceGuard,
    ) {}

    /**
     * Evaluate publish readiness for an Ilan given YDL authority context.
     *
     * @param Ilan $ilan                   The listing to evaluate
     * @param string $ydlAuthority         Authority level from YdlContextReader (FULL / LIMITED_BY_BLOCKER / STOP)
     * @param GovernanceState|null $governanceState  Override governance state (null = derive from ilan)
     * @return YdlPublishRecommendation
     */
    public function evaluate(
        Ilan $ilan,
        string $ydlAuthority = YdlContextOutput::AUTHORITY_FULL,
        ?GovernanceState $governanceState = null,
    ): YdlPublishRecommendation {
        // ── 1. Score computation ──────────────────────────────────────
        $ilan->completion_score = $this->scoreService->computeCompletionScore($ilan);
        $ilan->quality_score    = $this->scoreService->computeQualityScore($ilan);

        // ── 2. State analysis ─────────────────────────────────────────
        $mevcutRaw = $ilan->getRawOriginal('yayin_durumu') ?? $ilan->yayin_durumu;
        $mevcutStr = $mevcutRaw instanceof IlanDurumu ? $mevcutRaw->value : (string) $mevcutRaw;
        $mevcut    = IlanDurumu::tryFrom($mevcutStr) ?? IlanDurumu::TASLAK;

        // Default governance = PROMOTED for ready listings.
        // For DRAFT-only ilans (no governance workflow yet): pass GovernanceState::DRAFT explicitly.
        $govState = $governanceState ?? GovernanceState::PROMOTED;

        // ── 3. Early-exit cases ───────────────────────────────────────
        // Already published
        if ($mevcut === IlanDurumu::YAYINDA) {
            return $this->recommendAlreadyPublished($ilan, $ydlAuthority, $mevcutStr);
        }

        // Not in TASLAK or BEKLEMEDE
        if (! in_array($mevcut, [IlanDurumu::TASLAK, IlanDurumu::BEKLEMEDE], true)) {
            return $this->recommendNotTaslak($ilan, $ydlAuthority, $mevcutStr, $govState);
        }

        // ── 4. YDL authority gate ────────────────────────────────────
        if ($ydlAuthority === YdlContextOutput::AUTHORITY_STOP) {
            return $this->recommendBlocked(
                $ilan, $ydlAuthority, $mevcutStr, 'YDL authority: STOP — sistem durduruldu'
            );
        }

        // ── 5. Gate evaluation ───────────────────────────────────────
        $canPublish  = $this->runPublishGates($ilan);
        $missing     = $this->scoreService->computeBreakdown($ilan);
        $missingFields = $this->extractMissingFieldLabels($ilan, $missing);
        $blocking    = $this->buildBlockingReasons($ilan);

        // ── 6. Decision ──────────────────────────────────────────────
        if (! $canPublish['ok']) {
            return $this->recommendMissingFields(
                $ilan, $ydlAuthority, $mevcutStr, $missingFields, $blocking
            );
        }

        if (! $this->governanceGuard->canPublish($govState)) {
            return $this->recommendBlocked(
                $ilan, $ydlAuthority, $mevcutStr,
                "Governance state '{$govState->value}' → canPublish=false. PROMOTED required."
            );
        }

        // Ready to publish — human approval required (supervised autonomy)
        return $this->recommendPublishReady(
            $ilan, $ydlAuthority, $mevcutStr, $govState
        );
    }

    /**
     * Quick boolean: can this ilan proceed to publish?
     */
    public function canProceed(Ilan $ilan, string $ydlAuthority = YdlContextOutput::AUTHORITY_FULL): bool
    {
        return $this->evaluate($ilan, $ydlAuthority)->canPublish;
    }

    // ─── Recommendation builders ────────────────────────────────────────────

    private function recommendPublishReady(
        Ilan $ilan,
        string $ydlAuthority,
        string $mevcutStr,
        GovernanceState $govState,
    ): YdlPublishRecommendation {
        $targetState = $mevcutStr === IlanDurumu::BEKLEMEDE->value
            ? IlanDurumu::BEKLEMEDE->value
            : IlanDurumu::YAYINDA->value;

        return new YdlPublishRecommendation(
            pilot:                   self::PILOT,
            ilanId:                 $ilan->id,
            ydlAuthority:           $ydlAuthority,
            decision:                YdlPublishRecommendation::DECISION_PUBLISH_READY,
            decisionLabel:           'Yayına Hazır',
            rationale:               "completion_score={$ilan->completion_score}, quality_score={$ilan->quality_score}, governance={$govState->value}. İnsan onayı bekleniyor.",
            confidence:              $this->confidence($ilan->completion_score, $ilan->quality_score),
            currentState:            $mevcutStr,
            targetState:             $targetState,
            governanceState:         $govState->value,
            humanApprovalRequired:   true, // supervised autonomy — always required
            completionScore:        $ilan->completion_score,
            qualityScore:            $ilan->quality_score,
            canPublish:              true,
            missingFields:           [],
            blockingReasons:         [],
            suggestedActions:        [
                'İlan yayınlanmaya hazır — danışmandan onay bekleniyor.',
                'Onay sonrası: IlanCrudService->update([\'yayin_durumu\' => \'yayinda\']) çağrılmalı.',
                'YDL session-summary: php artisan ydl:session-summary --action CERTIFIED --target "PILOT-001: Property Publish" --commit $(git rev-parse HEAD)',
            ],
            evaluatedAt:             now()->toIso8601String(),
        );
    }

    private function recommendMissingFields(
        Ilan $ilan,
        string $ydlAuthority,
        string $mevcutStr,
        array $missingFields,
        array $blockingReasons,
    ): YdlPublishRecommendation {
        $missingLabels = implode(', ', array_column($missingFields, 'label'));

        return new YdlPublishRecommendation(
            pilot:                   self::PILOT,
            ilanId:                 $ilan->id,
            ydlAuthority:           $ydlAuthority,
            decision:                YdlPublishRecommendation::DECISION_MISSING_FIELDS,
            decisionLabel:           'Eksik Alanlar Var',
            rationale:               "{$missingLabels} eksik. completion_score={$ilan->completion_score}, quality_score={$ilan->quality_score}.",
            confidence:              'HIGH', // deterministic gate failure
            currentState:            $mevcutStr,
            targetState:             IlanDurumu::YAYINDA->value,
            governanceState:         GovernanceState::DRAFT->value,
            humanApprovalRequired:   false,
            completionScore:         $ilan->completion_score,
            qualityScore:            $ilan->quality_score,
            canPublish:              false,
            missingFields:           $missingFields,
            blockingReasons:         $blockingReasons,
            suggestedActions:        $this->buildFieldSuggestions($missingFields, $blockingReasons),
            evaluatedAt:             now()->toIso8601String(),
        );
    }

    private function recommendBlocked(
        Ilan $ilan,
        string $ydlAuthority,
        string $mevcutStr,
        string $reason,
    ): YdlPublishRecommendation {
        return new YdlPublishRecommendation(
            pilot:                   self::PILOT,
            ilanId:                 $ilan->id,
            ydlAuthority:           $ydlAuthority,
            decision:                YdlPublishRecommendation::DECISION_BLOCKED_GATE,
            decisionLabel:           'Bloke Edildi',
            rationale:               $reason,
            confidence:              'HIGH',
            currentState:            $mevcutStr,
            targetState:             IlanDurumu::YAYINDA->value,
            governanceState:         GovernanceState::DRAFT->value,
            humanApprovalRequired:   false,
            completionScore:        $ilan->completion_score,
            qualityScore:            $ilan->quality_score,
            canPublish:              false,
            missingFields:           [],
            blockingReasons:         [$ydlAuthority => $reason],
            suggestedActions:        [
                'Bloke nedenini incele ve gider.',
                'YDL authority bloke oldğunda yayın yapılamaz.',
            ],
            evaluatedAt:             now()->toIso8601String(),
        );
    }

    private function recommendAlreadyPublished(
        Ilan $ilan,
        string $ydlAuthority,
        string $mevcutStr,
    ): YdlPublishRecommendation {
        return new YdlPublishRecommendation(
            pilot:                   self::PILOT,
            ilanId:                 $ilan->id,
            ydlAuthority:           $ydlAuthority,
            decision:                YdlPublishRecommendation::DECISION_ALREADY_PUBLISHED,
            decisionLabel:           'Zaten Yayında',
            rationale:               "Ilan zaten yayında durumunda (yayin_durumu={$mevcutStr}).",
            confidence:              'HIGH',
            currentState:            $mevcutStr,
            targetState:             $mevcutStr,
            governanceState:         GovernanceState::PUBLISHED->value,
            humanApprovalRequired:   false,
            completionScore:        $ilan->completion_score,
            qualityScore:            $ilan->quality_score,
            canPublish:              false,
            missingFields:           [],
            blockingReasons:         [],
            suggestedActions:         [
                'Bu ilan zaten yayında — işlem gerekmiyor.',
                'Yeni bir ilan yayınlamak için farklı bir Ilan ID kullan.',
            ],
            evaluatedAt:             now()->toIso8601String(),
        );
    }

    private function recommendNotTaslak(
        Ilan $ilan,
        string $ydlAuthority,
        string $mevcutStr,
        GovernanceState $govState,
    ): YdlPublishRecommendation {
        return new YdlPublishRecommendation(
            pilot:                   self::PILOT,
            ilanId:                 $ilan->id,
            ydlAuthority:           $ydlAuthority,
            decision:                YdlPublishRecommendation::DECISION_NOT_TASLAK,
            decisionLabel:           'Yayın Durumu Uygun Değil',
            rationale:               "Ilan TASLAK veya BEKLEMEDE değil — mevcut durum: {$mevcutStr}",
            confidence:              'HIGH',
            currentState:            $mevcutStr,
            targetState:             IlanDurumu::YAYINDA->value,
            governanceState:         $govState->value,
            humanApprovalRequired:   false,
            completionScore:        $ilan->completion_score,
            qualityScore:            $ilan->quality_score,
            canPublish:              false,
            missingFields:           [],
            blockingReasons:         ["yayin_durumu={$mevcutStr} — sadece TASLAK veya BEKLEMEDE yayınlanabilir"],
            suggestedActions:        [
                "Ilan durumu '{$mevcutStr}' — TASLAK'a döndürülmesi gerekiyor.",
                'restore() kullan veya IlanCrudService üzerinden TASLAK transition tetikle.',
            ],
            evaluatedAt:             now()->toIso8601String(),
        );
    }

    // ─── Gate logic ─────────────────────────────────────────────────────────

    /**
     * @return array{ok: bool, gate: string, reason: string}
     */
    private function runPublishGates(Ilan $ilan): array
    {
        // Gate 1: Completion (hard gate — must be 100)
        if ($ilan->completion_score < 100) {
            return [
                'ok'     => false,
                'gate'   => YdlPublishRecommendation::GATE_COMPLETION,
                'reason' => "completion_score={$ilan->completion_score} — 100 olmalı",
            ];
        }

        // Gate 2: Quality (hard gate — must be ≥ 40)
        if ($ilan->quality_score < 40) {
            return [
                'ok'     => false,
                'gate'   => YdlPublishRecommendation::GATE_QUALITY,
                'reason' => "quality_score={$ilan->quality_score} — 40+ olmalı",
            ];
        }

        // Gate 3: Template / yayin_tipi_id
        if (! $ilan->yayin_tipi_id) {
            return [
                'ok'     => false,
                'gate'   => YdlPublishRecommendation::GATE_TEMPLATE,
                'reason' => 'yayin_tipi_id seçilmemiş — template doğrulanamaz',
            ];
        }

        return ['ok' => true, 'gate' => '', 'reason' => ''];
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * @return array<int, array{alan: string, label: string}>
     */
    private function extractMissingFieldLabels(Ilan $ilan, array $breakdown): array
    {
        $missing = [];

        $fieldMap = [
            'baslik'          => 'Başlık',
            'aciklama'        => 'Açıklama',
            'fiyat'           => 'Fiyat',
            'il_id'           => 'İl',
            'ilce_id'         => 'İlçe',
            'ana_kategori_id' => 'Kategori',
            'yayin_tipi_id'   => 'Yayın Tipi',
            'ilan_sahibi_id'  => 'İlan Sahibi',
        ];

        // From ZORUNLU breakdown
        if (! ($breakdown['zorunlu_alanlar']['ok'] ?? true)) {
            foreach ($breakdown['zorunlu_alanlar']['missing'] as $label) {
                $alan = array_search($label, $fieldMap, true);
                $missing[] = [
                    'alan'   => $alan ?: $label,
                    'label'  => $label,
                ];
            }
        }

        // Photo check
        if (! ($breakdown['fotograf']['ok'] ?? false)) {
            $missing[] = [
                'alan'  => 'fotograf',
                'label' => 'Fotoğraf (en az 1)',
            ];
        }

        return $missing;
    }

    /**
     * @return array<string, string>
     */
    private function buildBlockingReasons(Ilan $ilan): array
    {
        $reasons = [];

        if ($ilan->completion_score < 100) {
            $reasons[YdlPublishRecommendation::GATE_COMPLETION] =
                "completion_score={$ilan->completion_score}/100";
        }
        if ($ilan->quality_score < 40) {
            $reasons[YdlPublishRecommendation::GATE_QUALITY] =
                "quality_score={$ilan->quality_score}/100";
        }
        if (! $ilan->yayin_tipi_id) {
            $reasons[YdlPublishRecommendation::GATE_TEMPLATE] =
                'yayin_tipi_id eksik';
        }

        return $reasons;
    }

    /**
     * @return list<string>
     */
    private function buildFieldSuggestions(array $missingFields, array $blockingReasons): array
    {
        $suggestions = [];

        foreach ($missingFields as $field) {
            $label = $field['label'];
            $suggestions[] = match ($label) {
                'Başlık'       => "Başlık ekle: en az 10 karakter (ideal: 40-80 karakter)",
                'Açıklama'     => "Açıklama ekle: en az 50 karakter (ideal: 300+ karakter)",
                'Fiyat'        => 'Fiyat gir: pozitif numeric değer',
                'İl'           => 'İl seç',
                'İlçe'         => 'İlçe seç',
                'Kategori'     => 'Ana kategori seç',
                'Yayın Tipi'   => 'Yayın tipi junction seç',
                'İlan Sahibi'  => 'İlan sahibi ataması yap',
                'Fotoğraf (en az 1)' => 'En az 1 fotoğraf yükle',
                default         => "{$label} alanını tamamla",
            };
        }

        if (isset($blockingReasons[YdlPublishRecommendation::GATE_QUALITY])) {
            $suggestions[] = 'Açıklama ve başlık kalitesini artır (daha uzun + detaylı içerik)';
        }

        if (count($suggestions) === 0) {
            $suggestions[] = 'Eksik alanları tamamla ve tekrar değerlendir';
        }

        return $suggestions;
    }

    private function confidence(int $completionScore, float $qualityScore): string
    {
        if ($completionScore === 100 && $qualityScore >= 60) {
            return 'HIGH';
        }
        if ($completionScore >= 80 && $qualityScore >= 40) {
            return 'MEDIUM';
        }
        return 'LOW';
    }
}
