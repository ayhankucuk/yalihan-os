<?php

namespace App\Services\Listing;

/**
 * YalihanLifecycle — Canonical State Authority
 * Context7 Standard: C7-LIFECYCLE-2026-04-16
 */

use App\Contracts\TemplateResolverInterface;
use App\Enums\IlanDurumu;
use App\Exceptions\TemplateNotFoundException;
use App\Models\Ilan;
use App\Models\ListingStateTransition;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\GuardsAgentWrites;

/**
 * YalihanLifecycle
 *
 * SAB §5 — SINGLE WRITE PATH
 * Phase 8 — STATE AUTHORITY LOCK
 *
 * YAYINDA geçişi için:
 *   1. completion_score >= 100 (zorunlu alanlar tam)
 *   2. Template mapping mevcut (resolveByJunction() başarılı)
 *
 * Her iki koşul da fail-fast DomainException fırlatır.
 */
class YalihanLifecycle
{
    use GuardsAgentWrites;

    /**
     * Transition nesting counter. Set to > 0 by setUp() to authorize all lifecycle transitions.
     * Incremented at the start of each transition() call, decremented in finally block.
     * Using a counter (not bool) handles recursive transition() calls correctly.
     * @internal For testing only.
     */
    public static int $isTransitioningCounter = 0;

    /**
     * When true, guards (completion, quality, template) are skipped during transition.
     * Used for testing the lifecycle without valid business data.
     * @internal For testing only.
     */
    public static bool $skipGuards = false;

    public function __construct(
        private readonly ListingStateMachine $stateMachine,
        private readonly TemplateResolverInterface $templateResolver,
        private readonly ListingScoreService $scoreService,
    ) {}

    /**
     * Listing state geçişini uygula — SINGLE WRITE PATH
     *
     * @throws DomainException Geçersiz geçiş, yetersiz completion, veya eksik template
     */
    public function transition(
        Ilan $ilan,
        IlanDurumu $hedef,
        ?int $aktanId = null,
        array $meta = [],
    ): Ilan {
        $this->blockAgentWrite(__FUNCTION__);

        self::$isTransitioningCounter++;

        try {
            $aktanId   ??= Auth::id();

            // Always read current state from a fresh model instance.
            // This prevents stale in-memory state from breaking transition logic.
            // Production: guards against concurrent modifications.
            // Testing: guards against dirty in-memory model instances.
            $freshModel = Ilan::find($ilan->id);
            if (! $freshModel) {
                throw new \RuntimeException("Listing [{$ilan->id}] not found.");
            }
            $mevcutRaw  = $freshModel->yayin_durumu;
            $mevcutStr  = $mevcutRaw instanceof IlanDurumu ? $mevcutRaw->value : (string) $mevcutRaw;
            $mevcutInt  = $this->stateMachine->normalizeToInt($mevcutStr); // context7-ignore
            $hedefInt   = $this->stateMachine->normalizeToInt($hedef->value); // context7-ignore

            // Idempotent: aynı durum
            if ($mevcutInt === $hedefInt) {
                return $ilan;
            }

            // Auto-chain: Taslak -> Beklemede -> Yayında
            if ($mevcutInt === ListingStateMachine::TASLAK && $hedefInt === ListingStateMachine::YAYINDA) {
                $this->transition($ilan, IlanDurumu::BEKLEMEDE, $aktanId, array_merge($meta, ['chained' => true]));
                // Re-read from fresh model after chained transition
                $freshModel2 = Ilan::find($ilan->id);
                $mevcutRaw  = $freshModel2->yayin_durumu;
                $mevcutStr  = $mevcutRaw instanceof IlanDurumu ? $mevcutRaw->value : (string) $mevcutRaw;
                $mevcutInt  = $this->stateMachine->normalizeToInt($mevcutStr); // context7-ignore
            }

            // 1. StateMachine geçiş kuralı
            $this->stateMachine->gecisYap($mevcutInt, $hedefInt); // context7-ignore

            // 2. YAYINDA hard-guards (Phase 8) — skip in test mode
            if ($hedef === IlanDurumu::YAYINDA && !self::$skipGuards) {
                $ilan->completion_score = $this->scoreService->computeCompletionScore($ilan);
                $ilan->quality_score    = $this->scoreService->computeQualityScore($ilan);
                $ilan->saveQuietly();

                $this->completionGuard($ilan);
                $this->qualityGuard($ilan);
                $this->templateGuard($ilan);
            }

            return DB::transaction(function () use ($ilan, $hedef, $mevcutStr, $aktanId, $meta) {
                // The setter guard (setYayinDurumuAttribute) fires when isAuthorized=false.
                // Since the closure is executed synchronously before the outer finally block
                // resets the flag, using the setter directly is safe.
                $ilan->yayin_durumu = $hedef;
                $ilan->saveQuietly();

                // 🛡️ SAB §17: Force state version increment (since we used saveQuietly)
                if (method_exists($ilan, 'incrementStateVersion')) {
                    $ilan::incrementStateVersion();
                }

                ListingStateTransition::create([
                    'ilan_id'    => $ilan->id,
                    'from_state' => $mevcutStr,
                    'to_state'   => $hedef->value,
                    'aktan_id'   => $aktanId,
                    'meta'       => $meta,
                ]);

                Log::info('listing_state_transition', [
                    'ilan_id'  => $ilan->id,
                    'from'     => $mevcutStr,
                    'to'       => $hedef->value,
                    'aktan_id' => $aktanId,
                    'source'   => $meta['source'] ?? 'unknown',
                ]);

                // Reload the model after commit to guarantee callers get fresh state.
                // Inside the closure, $ilan->fresh() reads from uncommitted changes (same conn).
                // Returning $ilan->fresh() from DB::transaction reads POST-commit state.
                return $ilan->fresh();
            });
        } finally {
            self::$isTransitioningCounter--;
        }
    }

    /**
     * Restore listing from archive — ARSIV → TASLAK
     *
     * Sprint 4.2: Real CRUD Certification
     * P0 fix: Missing restore endpoint
     */
    public function restore(Ilan $ilan): Ilan
    {
        $this->blockAgentWrite(__FUNCTION__);

        $mevcutRaw = $ilan->getOriginal('yayin_durumu') ?? $ilan->yayin_durumu;
        $mevcutStr = $mevcutRaw instanceof IlanDurumu ? $mevcutRaw->value : (string) $mevcutRaw;

        if ($mevcutStr !== IlanDurumu::ARSIV->value) {
            return $ilan; // Idempotent: only restore from ARSIV
        }

        return $this->transition($ilan, IlanDurumu::TASLAK, null, ['source' => 'restore']);
    }

    /**
     * Toplu geçiş — bireysel hata izolasyonu
     *
     * @return array{basarili: int, hatali: int, hatalar: array}
     */
    public function bulkTransition(
        iterable $ilanlar,
        IlanDurumu $hedef,
        ?int $aktanId = null,
        array $meta = [],
    ): array {
        $this->blockAgentWrite(__FUNCTION__);

        $basarili = 0;
        $hatali   = 0;
        $hatalar  = [];

        foreach ($ilanlar as $ilan) {
            try {
                $this->transition($ilan, $hedef, $aktanId, $meta);
                $basarili++;
            } catch (DomainException $e) {
            \Illuminate\Support\Facades\Log::error("Silent catch: " . $e->getMessage());
                $hatali++;
                $hatalar[] = ['ilan_id' => $ilan->id, 'hata' => $e->getMessage()];
            }
        }

        return ['basarili' => $basarili, 'hatali' => $hatali, 'hatalar' => $hatalar];
    }

    // ── Hard Guards ──────────────────────────────────────────────────────────

    /**
     * Completion guard: completion_score < 100 → DomainException (SAB Phase 17B)
     */
    private function completionGuard(Ilan $ilan): void
    {
        if ($ilan->completion_score < 100) {
            throw new DomainException(
                "Yayınlama yapılamaz (completion_score={$ilan->completion_score}). " .
                'Zorunlu alanların %100 dolu olması ve en az 1 fotoğraf gereklidir.'
            );
        }
    }

    /**
     * Quality guard: quality_score < 40 → DomainException (SAB Phase 17B)
     */
    private function qualityGuard(Ilan $ilan): void
    {
        $this->stateMachine->yayinIcinKontrolEt(
            (int) $ilan->quality_score,
            (int) $ilan->completion_score,
            $ilan->lat !== null ? (float) $ilan->lat : null,
            $ilan->lng !== null ? (float) $ilan->lng : null
        );
    }

    /**
     * Template guard: yayin_tipi_id eksik veya mapping yok → DomainException
     */
    private function templateGuard(Ilan $ilan): void
    {
        if (! $ilan->yayin_tipi_id) {
            throw new DomainException(
                'Yayın tipi (yayin_tipi_id) seçilmemiş — template doğrulanamaz.'
            );
        }

        try {
            $this->templateResolver->resolveByJunction(
                (int) $ilan->yayin_tipi_id,
                $ilan->ana_kategori_id ? (int) $ilan->ana_kategori_id : null,
            );
        } catch (TemplateNotFoundException $e) {
            throw new DomainException(
                "Template mapping bulunamadı (yayin_tipi_id={$ilan->yayin_tipi_id}). Publish bloklandı."
            );
        } catch (\Exception $e) {
            throw new DomainException(
                "Template doğrulama hatası: {$e->getMessage()}"
            );
        }
    }
}
