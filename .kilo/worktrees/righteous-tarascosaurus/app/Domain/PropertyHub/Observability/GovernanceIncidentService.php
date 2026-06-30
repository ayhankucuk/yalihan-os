<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Observability;

use App\Models\PropertyConfigVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Governance Incident Service
 *
 * Handles recording and alerting for governance integrity breaches.
 * ✅ SAB: Safe telemetry, idempotent recording.
 */
class GovernanceIncidentService
{
    private const ALERT_COOLDOWN = 300; // 5 minutes

    /**
     * Canonical Incident Recorder
     */
    public function recordIncident(
        string $type,
        array $payload,
        string $risk = 'HIGH',
        string $source = 'Governance'
    ): void {
        $tenantId = $payload['tenant_id'] ?? 'SYSTEM';
        $snapshotId = $payload['snapshot_id'] ?? null;
        $signature = $payload['signature'] ?? null;

        $this->record(
            $type,
            $source,
            $risk,
            $tenantId,
            $snapshotId,
            $signature,
            $payload
        );
    }

    /**
     * Alias: Tamper Event
     */
    public function recordTamper(mixed $payloadOrVersion, ?string $message = null): void
    {
        if ($payloadOrVersion instanceof \App\Models\PropertyConfigVersion) {
            $payload = [
                'version_id' => $payloadOrVersion->id,
                'message' => $message,
                'snapshot_id' => $payloadOrVersion->id,
                'signature' => $payloadOrVersion->signature,
                'tenant_id' => $payloadOrVersion->tenant_id ?? 'SYSTEM',
            ];
        } else {
            $payload = (array) $payloadOrVersion;
        }

        $this->recordIncident('signature_mismatch', $payload, 'CRITICAL', 'IntegrityGuard');
    }

    /**
     * Alias: Security Breach
     */
    public function recordBreach(array $payload): void
    {
        $this->recordIncident('BREACH', $payload, 'CRITICAL', 'SecurityFirewall');
    }

    /**
     * Record a governance incident.
     * ✅ Sprint 15: Tenant-aware incident log.
     */
    public function record(
        string $type,
        string $source,
        string $risk,
        string $tenantId = 'SYSTEM',
        ?int $snapshotId = null,
        ?string $signature = null,
        array $details = []
    ): void {
        \App\Models\GovernanceIncident::create([
            'tenant_id' => $tenantId,
            'olay_tipi' => $type,
            'kaynak' => $source,
            'risk_seviyesi' => $risk,
            'snapshot_id' => $snapshotId,
            'imza_hash' => $signature,
            'details' => array_merge($details, [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]),
        ]);

        // Alert with cooldown if critical or high risk
        if (in_array($risk, ['CRITICAL', 'HIGH'])) {
            $this->alertAdmins($type, $source, $tenantId);

            if ($risk === 'CRITICAL') {
                $this->triggerHardLock($tenantId, $type . ' from ' . $source);
            }
        }
    }

    /**
     * Trigger the tenant-specific Hard Lock.
     */
    public function triggerHardLock(string $tenantId, string $reason): void
    {
        Cache::forever("governance.compromised.{$tenantId}", true);

        if ($tenantId === 'SYSTEM') {
            Cache::forever('governance.system_compromised', true);
        }

        Log::channel('governance_security')->emergency("TENANT COMPROMISED: Hard Lock Initiated for [{$tenantId}]. Reason: {$reason}");
    }

    /**
     * Notify admins about the breach.
     */
    private function alertAdmins(string $type, string $source, string $tenantId = 'SYSTEM'): void
    {
        $lockKey = "governance.alert_lock." . md5($type . $source . $tenantId);

        if (Cache::has($lockKey)) {
            return;
        }

        // Simulating notification (e.g., Slack/Email)
        try {
            $msg = "YALIHAN GOVERNANCE ALERT [Tenant: {$tenantId}]: Critical Incident [{$type}] from [{$source}] detected.";
            $channel = config('logging.channels.emergency') ? 'emergency' : null;
            if ($channel) {
                Log::channel($channel)->critical($msg);
            } else {
                Log::emergency($msg);
            }
        } catch (\Exception $e) {
            // Absolute silence on logging failure
        }

        Cache::put($lockKey, true, self::ALERT_COOLDOWN);
    }
}
