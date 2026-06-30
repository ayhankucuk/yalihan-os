<?php

namespace App\Governance\Alerting;

use App\Governance\Analytics\GovernanceAnalytics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 4C — Governance Alerting Engine (Week 2)
 *
 * =====================================================================
 * TEMEL PRENSİPLER (Safety Guardrails)
 * =====================================================================
 *
 * #5  Fail-Open         : Hata durumunda sistem durmaz, log yazılır ve devam edilir.
 * #14 Fatigue Prevention: Dedup (tekilleştirme) ve Rate-limit (hız sınırı) zorunludur.
 * #11 Advisory-only     : Alarmlar bilgilendirme amaçlıdır, süreci kesmez.
 *
 * =====================================================================
 * ALARM MANTIĞI
 * =====================================================================
 *
 * 1. Deduplication      : Aynı tip alarm belirli bir süre içinde tekrar yazılmaz.
 * 2. Rate-limiting      : Bir saat içinde toplam alarm sayısı sınırı aşamaz.
 * 3. Global Scope       : Alarmlar tenant-scoped değildir, sistem geneli izlenir.
 */
class GovernanceAlerter
{
    protected GovernanceAnalytics $analytics;

    public function __construct(GovernanceAnalytics $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * Analiz sonuçlarını kontrol eder ve gerekirse alarm oluşturur.
     */
    public function checkAndAlert(): void
    {
        try {
            $report = $this->analytics->generateHealthReport();

            // 1. Drift Kontrolü
            if ($report['drift']['has_drift']) {
                $this->createAlert(
                    'governance_drift',
                    $report['drift'],
                    $report['drift']['severity'] ?? 'medium'
                );
            }

            // 2. Anomali Kontrolü
            foreach ($report['anomalies'] as $anomaly) {
                $this->createAlert(
                    'governance_anomaly',
                    $anomaly,
                    $anomaly['severity'] ?? 'high'
                );
            }

        } catch (\Throwable $e) {
            Log::error('[GovernanceAlerter] Alarm kontrolü başarısız', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Yeni bir alarm kaydı oluşturur (Dedup ve Rate-limit süzgecinden geçirerek).
     */
    public function createAlert(string $type, array $data, string $severity = 'medium'): void
    {
        try {
            // 1. Deduplication (Yineleme Engelleme)
            if ($this->isDuplicate($type)) {
                return;
            }

            // 2. Rate Limiting (Hız Sınırı)
            if ($this->isRateLimited()) {
                Log::warning('[GovernanceAlerter] Alert rate-limit aşıldı, alarm yazılmadı.', ['tip' => $type]);
                return;
            }

            // 3. Kayıt (Eloquent kullanılmaz - SAB Rule)
            DB::table('governance_alerts')->insert([
                'tip'          => $type,
                'data'         => json_encode($data),
                'severity'     => $severity,
                'acknowledged' => false,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Opsiyonel: Dış kanallara (Slack/Email) bildirim burada tetiklenebilir
            // current design focuses on persistence first.

        } catch (\Throwable $e) {
            Log::error('[GovernanceAlerter] Alarm kaydedilemedi', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Aktif (onaylanmamış) alarmları döner.
     */
    public function getActiveAlerts(): array
    {
        try {
            return DB::table('governance_alerts')
                ->where('acknowledged', false)
                ->orderByDesc('created_at')
                ->get()
                ->toArray();
        } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::error($e->getMessage()); return []; }
    }

    /**
     * Alarmı onaylanmış (okundu) olarak işaretler.
     */
    public function acknowledge(int $alertId, int $userId): bool
    {
        try {
            return DB::table('governance_alerts')
                ->where('id', $alertId)
                ->update([
                    'acknowledged'    => true,
                    'acknowledged_at' => now(),
                    'acknowledged_by' => $userId,
                    'updated_at'      => now(),
                ]) > 0;
        } catch (\Throwable $e) {
            Log::error('[GovernanceAlerter] Acknowledge failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    // ---------------------------------------------------------------
    // PRIVATE: Filtreleme Mantığı
    // ---------------------------------------------------------------

    /**
     * Belirli bir süre içinde aynı tip alarmın varlığını kontrol eder.
     */
    private function isDuplicate(string $type): bool
    {
        $minutes = config('governance.telemetry.alerting.dedup_window_minutes', 60);

        return DB::table('governance_alerts')
            ->where('tip', $type)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->exists();
    }

    /**
     * Son bir saat içindeki toplam alarm sayısını kontrol eder.
     */
    private function isRateLimited(): bool
    {
        $limit = config('governance.telemetry.alerting.rate_limit_per_hour', 20);

        return DB::table('governance_alerts')
            ->where('created_at', '>=', now()->subHour())
            ->count() >= $limit;
    }
}
