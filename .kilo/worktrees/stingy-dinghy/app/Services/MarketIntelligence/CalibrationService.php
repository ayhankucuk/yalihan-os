<?php

namespace App\Services\MarketIntelligence;

use App\Models\MarketIntelligence\FeedbackResult;
use Illuminate\Support\Facades\Log;

/**
 * Calibration Service — MIE v2.0
 *
 * Feedback verilerinden threshold önerisi üretir.
 * Alpha: sadece logging. Otomatik threshold mutation YASAK.
 *
 * Drift Guards (v2.1):
 *   - Time decay: 90 gün sonra feedback ağırlığı azalır
 *   - Max weight cap: tek feedback %15'ten fazla etkilemez
 *   - Staleness check: 30 gün feedback yoksa uyarı
 *
 * Tamamen deterministik — AI sıfır, rand() sıfır.
 */
class CalibrationService
{
    /** Günlük zaman aşımı — bu günden eski feedback'lerin ağırlığı azalır */
    private const DECAY_THRESHOLD_DAYS = 90;

    /** Tek feedback'in accuracy hesaplamasında maksimum ağırlığı (%15) */
    private const MAX_WEIGHT_CAP = 0.15;

    /** Son feedback'ten bu kadar gün geçtiyse → stale uyarısı */
    private const STALENESS_DAYS = 30;
    /**
     * Mevcut feedback verilerini analiz et, sapmaları logla.
     *
     * @return array{
     *   total_feedback: int,
     *   pricing_accuracy: float|null,
     *   demand_accuracy: float|null,
     *   opportunity_accuracy: float|null,
     *   recommendations: string[],
     *   staleness: array{is_stale: bool, days_since_last: int|null},
     * }
     */
    public function analyze(): array
    {
        $feedbackEval = new FeedbackEvaluationService();
        $accuracy = $feedbackEval->calculateAccuracy();

        $staleness = $this->checkStaleness();
        $recommendations = $this->generateRecommendations($accuracy, $staleness);

        $report = [
            'total_feedback' => $accuracy['total_evaluated'],
            'pricing_accuracy' => $accuracy['pricing_accuracy'],
            'demand_accuracy' => $accuracy['demand_accuracy'],
            'opportunity_accuracy' => $accuracy['opportunity_accuracy'],
            'recommendations' => $recommendations,
            'staleness' => $staleness,
        ];

        Log::channel('daily')->info('mie_calibration_analysis', $report);

        return $report;
    }

    /**
     * Accuracy verilerinden deterministik öneriler oluştur.
     *
     * Bu öneriler sadece LOG'a yazılır, otomatik aksiyon ALMAZ.
     *
     * @return string[]
     */
    private function generateRecommendations(array $accuracy, array $staleness = []): array
    {
        $recs = [];

        // Staleness uyarısı (drift guard)
        if (!empty($staleness['is_stale'])) {
            $recs[] = 'Son feedback ' . $staleness['days_since_last'] . ' gün önce. '
                . 'Kalibrasyon verileri bayatlamış olabilir — yeni feedback toplanmalı.';
        }

        if ($accuracy['total_evaluated'] < 10) {
            $recs[] = 'Yeterli veri yok. En az 10 feedback gerekli.';
            return $recs;
        }

        // Pricing accuracy control
        if ($accuracy['pricing_accuracy'] !== null && $accuracy['pricing_accuracy'] < 60) {
            $recs[] = 'Fiyat pozisyon doğruluğu düşük (%' . $accuracy['pricing_accuracy'] . '). '
                . 'FAIR band ±10% threshold gözden geçirilmeli.';
        }

        // Demand accuracy control
        if ($accuracy['demand_accuracy'] !== null && $accuracy['demand_accuracy'] < 60) {
            $recs[] = 'Talep doğruluğu düşük (%' . $accuracy['demand_accuracy'] . '). '
                . 'Talep threshold değerleri gözden geçirilmeli.';
        }

        // Opportunity accuracy control
        if ($accuracy['opportunity_accuracy'] !== null && $accuracy['opportunity_accuracy'] < 60) {
            $recs[] = 'Fırsat aksiyonu doğruluğu düşük (%' . $accuracy['opportunity_accuracy'] . '). '
                . 'BUY/SELL kuralları gözden geçirilmeli.';
        }

        // High accuracy = stable
        $allHigh = ($accuracy['pricing_accuracy'] ?? 0) >= 80
            && ($accuracy['demand_accuracy'] ?? 0) >= 80
            && ($accuracy['opportunity_accuracy'] ?? 0) >= 80;

        if ($allHigh) {
            $recs[] = 'Tüm modüller yüksek doğrulukta. Mevcut threshold değerleri stabil.';
        }

        if (empty($recs)) {
            $recs[] = 'Genel performans kabul edilebilir düzeyde.';
        }

        return $recs;
    }

    /**
     * False rate hesapla — yanlış BUY ve SELL oranları.
     *
     * @return array{false_buy_rate: float|null, false_sell_rate: float|null}
     */
    public function calculateFalseRates(): array
    {
        $buySnapshots = FeedbackResult::whereHas('snapshot', function ($q) {
            $q->where('opportunity_action', 'BUY');
        })->get();

        $sellSnapshots = FeedbackResult::whereHas('snapshot', function ($q) {
            $q->where('opportunity_action', 'SELL');
        })->get();

        return [
            'false_buy_rate' => $buySnapshots->isNotEmpty()
                ? round($buySnapshots->where('opportunity_correct', false)->count() / $buySnapshots->count() * 100, 1)
                : null,
            'false_sell_rate' => $sellSnapshots->isNotEmpty()
                ? round($sellSnapshots->where('opportunity_correct', false)->count() / $sellSnapshots->count() * 100, 1)
                : null,
        ];
    }

    /**
     * Staleness check — son feedback ne zaman geldi?
     *
     * @return array{is_stale: bool, days_since_last: int|null}
     */
    public function checkStaleness(): array
    {
        $latest = FeedbackResult::orderByDesc('created_at')->first();

        if (!$latest) {
            return ['is_stale' => true, 'days_since_last' => null];
        }

        $daysSince = (int) now()->diffInDays($latest->created_at);

        return [
            'is_stale' => $daysSince > self::STALENESS_DAYS,
            'days_since_last' => $daysSince,
        ];
    }

    /**
     * Time-decay weighted accuracy — eski feedback'lerin etkisini azaltır.
     *
     * Her feedback'e yaşına göre ağırlık verir:
     *   - 0-90 gün: ağırlık = 1.0
     *   - 90+ gün: ağırlık = 90 / yaş_gün (doğrusal azalma)
     *   - Tek feedback'in ağırlığı toplamın %15'ini geçemez (max weight cap)
     *
     * @param string $field 'pricing_correct', 'demand_correct', veya 'opportunity_correct'
     * @return float|null Ağırlıklı doğruluk yüzdesi (0-100) veya veri yoksa null
     */
    public function calculateDecayedAccuracy(string $field): ?float
    {
        $allowedFields = ['pricing_correct', 'demand_correct', 'opportunity_correct'];
        if (!in_array($field, $allowedFields, true)) {
            return null;
        }

        $feedbacks = FeedbackResult::whereNotNull($field)
            ->orderByDesc('created_at')
            ->get();

        if ($feedbacks->isEmpty()) {
            return null;
        }

        // Raw weights based on age
        $rawWeights = [];
        foreach ($feedbacks as $fb) {
            $ageDays = max(1, (int) now()->diffInDays($fb->created_at));
            $rawWeights[] = $ageDays <= self::DECAY_THRESHOLD_DAYS
                ? 1.0
                : self::DECAY_THRESHOLD_DAYS / $ageDays;
        }

        // Apply max weight cap
        $totalRaw = array_sum($rawWeights);
        if ($totalRaw <= 0) {
            return null;
        }

        $maxAllowed = $totalRaw * self::MAX_WEIGHT_CAP;
        $cappedWeights = array_map(
            fn(float $w) => min($w, $maxAllowed),
            $rawWeights
        );

        $totalCapped = array_sum($cappedWeights);
        if ($totalCapped <= 0) {
            return null;
        }

        // Weighted accuracy
        $correctSum = 0.0;
        foreach ($feedbacks->values() as $i => $fb) {
            if ($fb->{$field}) {
                $correctSum += $cappedWeights[$i];
            }
        }

        return round($correctSum / $totalCapped * 100, 1);
    }
}
