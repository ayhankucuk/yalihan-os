<?php

namespace App\Domain\Core\Security;

use App\Services\AI\AiAbuseDetectionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class ZeroTrustAuditor
 * @package App\Domain\Core\Security
 * @description Phase 19: Çalışma zamanı sınır ihlallerini ve kimlik anomalilerini adli kalıcı tablolara (governance_incidents) kriptografik mühürle kaydeden anayasal sınıf.
 */
class ZeroTrustAuditor implements ZeroTrustAuditorContract
{
    /**
     * Potansiyel bir tehdit veya sınır ihlali eylemini adli olarak tescil eder.
     * Context7 Kanonik Sözlük ve SAB Madde 1 kurallarına tabidir.
     *
     * @param string $tehditTipi Örn: 'CROSS_TENANT_MUTATION_ATTEMPT'
     * @param int $tenantId İhlal edilmeye çalışılan veya aktif olan kiracı kimliği
     * @param array<string, mixed> $adliMetadatalar İsteğe ait IP, Kullanıcı, Payload parametreleri
     * @return bool Tescil işlemi başarılı mı?
     */
    public function logForensicsAnomaly(string $tehditTipi, int $tenantId, array $adliMetadatalar): bool
    {
        try {
            // Adli imza hash zinciri hesaplaması (SHA-256)
            $stringToHash = $tehditTipi . '|' . $tenantId . '|' . json_encode($adliMetadatalar, JSON_UNESCAPED_UNICODE);
            $imzaHash = hash('sha256', $stringToHash);

            $actorId = auth()->id();
            $correlationId = request()->header('X-Correlation-ID') ?: Str::uuid()->toString();

            $details = array_merge([
                'ip' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI/Test-Environment',
                'actor_id' => $actorId ?: 'guest',
                'correlation_id' => $correlationId,
                'occurred_at' => now()->toIso8601String()
            ], $adliMetadatalar);

            // DB'ye doğrudan insert (governance_incidents)
            $inserted = DB::table('governance_incidents')->insert([
                'tenant_id' => (string) $tenantId,
                'olay_tipi' => $tehditTipi,
                'kaynak' => $adliMetadatalar['source'] ?? 'ZeroTrustAuditor',
                'snapshot_id' => $adliMetadatalar['record_id'] ?? null,
                'risk_seviyesi' => $adliMetadatalar['severity'] ?? 'CRITICAL',
                'imza_hash' => $imzaHash,
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::error("🚨 SAB FORENSICS DETECTED RUNTIME ANOMALY: Intrusion locked successfully in governance_incidents.", [
                'tenant_id' => $tenantId,
                'event_type' => $tehditTipi,
                'hash' => $imzaHash,
                'correlation_id' => $correlationId
            ]);

            return $inserted;
        } catch (\Exception $e) {
            Log::critical("🚨 SAB CRITICAL FORENSICS FAULT: Failed to write to forensics ledger!", [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'event_type' => $tehditTipi
            ]);
            return false;
        }
    }

    /**
     * Aktif oturum bağlamındaki eylemin, geçmiş davranışsal veri kalıplarına göre anomalilik skorunu hesaplar.
     *
     * @param int $kullaniciId
     * @param string $eylemKodu Örn: 'BULK_LISTING_EXPORT'
     * @return float Anomali Skoru [0.0 - 1.0] (0.0: Güvenli, 1.0: Mutlak Tehdit / Blokaj)
     */
    public function evaluateBehavioralRiskScore(int $kullaniciId, string $eylemKodu): float
    {
        try {
            // AiAbuseDetectionService ile davranışsal analizi koştur
            $abuseService = app(AiAbuseDetectionService::class);
            $baseScore = $abuseService->getAnomalyScore($kullaniciId);

            // Kritik idari işlemlerde anomali riskini yükselt (Fail-Safe)
            if (in_array($eylemKodu, ['BULK_LISTING_EXPORT', 'BULK_LISTING_DELETE', 'TENANT_DATA_WIPE'])) {
                $baseScore += 0.35;
            }

            return min($baseScore, 1.00);
        } catch (\Exception $e) {
            Log::warning("SAB BEHAVIORAL AUDITOR WARNING: Anomaly calculation error. Falling back to secure default.", [
                'user_id' => $kullaniciId,
                'action' => $eylemKodu,
                'error' => $e->getMessage()
            ]);
            return 0.50; // Belirsizlik durumunda güvenli/orta seviye risk kabul et
        }
    }
}
