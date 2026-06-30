<?php

namespace App\Services\OpenClaw;

use App\Models\OpenClawAuditLog;
use App\Support\AgentContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * OpenClawAuditService — Central audit recorder for agent interactions.
 *
 * Dual-write strategy:
 * 1. DB record (queryable, aggregatable, anomaly-detectable)
 * 2. File log (human-readable backup, disaster recovery)
 *
 * Usage:
 *   app(OpenClawAuditService::class)->recordRequest($request, 'request_passed', 200);
 *   app(OpenClawAuditService::class)->recordWriteViolation('IlanCrudService', 'store');
 */
class OpenClawAuditService
{
    /**
     * Record an agent request event (middleware layer).
     */
    public function recordRequest(
        Request $request,
        string $eventType,
        int $httpDurumKodu,
        bool $basarili = true,
        ?string $rejectionReason = null,
        ?float $durationMs = null,
        array $metadata = []
    ): ?OpenClawAuditLog {
        $data = [
            'event_type' => $eventType,
            'agent_source' => $request->header('X-Agent-Source'),
            'agent_scope' => $request->header('X-Agent-Scope'),
            'correlation_id' => $request->header('X-Correlation-Id'),
            'token_hash' => $this->truncateTokenHash($request->header('X-Agent-Token')),
            'route' => $request->path(),
            'http_method' => $request->method(),
            'http_durum_kodu' => $httpDurumKodu,
            'ip_address' => $request->ip(),
            'payload_hash' => $this->hashPayload($request),
            'payload_size' => (int) $request->header('Content-Length', 0),
            'duration_ms' => $durationMs,
            'basarili' => $basarili,
            'rejection_reason' => $rejectionReason,
            'metadata' => !empty($metadata) ? $metadata : null,
            'olusturma_tarihi' => now(),
        ];

        return $this->persist($data);
    }

    /**
     * Record a write violation event (service layer — GuardsAgentWrites).
     */
    public function recordWriteViolation(
        string $serviceClass,
        string $serviceMethod,
        array $metadata = []
    ): ?OpenClawAuditLog {
        $data = [
            'event_type' => OpenClawAuditLog::EVENT_WRITE_VIOLATION,
            'agent_source' => null,
            'agent_scope' => AgentContext::scope(),
            'correlation_id' => AgentContext::correlationId(),
            'token_hash' => AgentContext::tokenHash(),
            'route' => request()?->path(),
            'http_method' => request()?->method(),
            'http_durum_kodu' => 403,
            'ip_address' => request()?->ip(),
            'payload_hash' => null,
            'payload_size' => null,
            'duration_ms' => null,
            'basarili' => false,
            'rejection_reason' => "Write blocked: {$serviceClass}::{$serviceMethod}",
            'service_class' => $serviceClass,
            'service_method' => $serviceMethod,
            'metadata' => !empty($metadata) ? $metadata : null,
            'olusturma_tarihi' => now(),
        ];

        return $this->persist($data);
    }

    /**
     * Get anomaly indicators for the given time window.
     *
     * @return array{total_requests: int, blocked_count: int, violation_count: int, unique_tokens: int, block_rate: float}
     */
    public function getWindowStats(int $minutes = 10): array
    {
        $since = now()->subMinutes($minutes);

        $total = OpenClawAuditLog::since($since)->count();
        $blocked = OpenClawAuditLog::since($since)->blocked()->count();
        $violations = OpenClawAuditLog::since($since)->violations()->count();
        $uniqueTokens = OpenClawAuditLog::since($since)
            ->whereNotNull('token_hash')
            ->distinct('token_hash')
            ->count('token_hash');

        return [
            'total_requests' => $total,
            'blocked_count' => $blocked,
            'violation_count' => $violations,
            'unique_tokens' => $uniqueTokens,
            'block_rate' => $total > 0 ? round($blocked / $total, 4) : 0.0,
        ];
    }

    /**
     * Get recent violations grouped by service.
     */
    public function getViolationsByService(int $minutes = 60): array
    {
        return OpenClawAuditLog::violations()
            ->recent($minutes)
            ->selectRaw('service_class, service_method, COUNT(*) as violation_count')
            ->groupBy('service_class', 'service_method')
            ->orderByDesc('violation_count')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // Internal
    // =========================================================================

    private function persist(array $data): ?OpenClawAuditLog
    {
        // 🛡️ GOVERNANCE: Pre-persist validation to ensure contract compliance
        // If event_type exceeds 50 chars, it will fail DB constraint.
        if (strlen($data['event_type'] ?? '') > 50) {
            Log::channel(config('openclaw.audit.log_channel', 'security'))
                ->error('openclaw_audit_invalid_data', ['reason' => 'event_type_too_long']);
            return null;
        }

        try {
            return OpenClawAuditLog::create($data);
        } catch (\Throwable $e) {
            // Audit failure must never crash the request.
            // Fallback: file log only.
            Log::channel(config('openclaw.audit.log_channel', 'security'))
                ->error('openclaw_audit_persist_failed', [
                    'error' => $e->getMessage(),
                    'event_type' => $data['event_type'] ?? 'unknown',
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

            return null;
        }
    }

    private function truncateTokenHash(?string $token): ?string
    {
        if (!$token) {
            return null;
        }

        return substr(hash('sha256', $token), 0, 16);
    }

    private function hashPayload(Request $request): ?string
    {
        $content = $request->getContent();

        if (empty($content)) {
            return null;
        }

        return substr(hash('sha256', $content), 0, 16);
    }
}
