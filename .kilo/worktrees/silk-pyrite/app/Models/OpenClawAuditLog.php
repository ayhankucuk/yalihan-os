<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * OpenClawAuditLog — Immutable audit record for agent interactions.
 *
 * Every agent request through the OpenClaw middleware stack gets a record.
 * Write violations from GuardsAgentWrites also get recorded here.
 *
 * Context7: basarili (NOT success), http_durum_kodu (NOT status_code),
 * olusturma_tarihi (NOT created_at)
 *
 * @property int $id
 * @property string $event_type
 * @property string|null $agent_source
 * @property string|null $agent_scope
 * @property string|null $correlation_id
 * @property string|null $token_hash
 * @property string|null $route
 * @property string|null $http_method
 * @property int|null $http_durum_kodu
 * @property string|null $ip_address
 * @property string|null $payload_hash
 * @property int|null $payload_size
 * @property float|null $duration_ms
 * @property bool $basarili
 * @property string|null $rejection_reason
 * @property string|null $service_class
 * @property string|null $service_method
 * @property array|null $metadata
 * @property \Carbon\Carbon $olusturma_tarihi
 */
class OpenClawAuditLog extends BaseModel
{
    use HasCountryScope;

    public $timestamps = false;

    protected $table = 'openclaw_audit_logs';

    protected $fillable = [
        'event_type',
        'agent_source',
        'agent_scope',
        'correlation_id',
        'token_hash',
        'route',
        'http_method',
        'http_durum_kodu',
        'ip_address',
        'payload_hash',
        'payload_size',
        'duration_ms',
        'basarili',
        'rejection_reason',
        'service_class',
        'service_method',
        'metadata',
        'olusturma_tarihi',
    ];

    protected $casts = [
        'basarili' => 'boolean',
        'metadata' => 'array',
        'olusturma_tarihi' => 'datetime',
        'http_durum_kodu' => 'integer',
        'payload_size' => 'integer',
        'duration_ms' => 'float',
    ];

    // =========================================================================
    // Event Type Constants
    // =========================================================================

    public const EVENT_GATEWAY_OPEN = 'gateway_open';
    public const EVENT_GATEWAY_BLOCKED = 'gateway_blocked';
    public const EVENT_SCOPE_REJECTED = 'scope_rejected';
    public const EVENT_TOKEN_INVALID = 'token_invalid';
    public const EVENT_BOUNDARY_REJECTED = 'boundary_rejected';
    public const EVENT_REQUEST_PASSED = 'request_passed';
    public const EVENT_WRITE_VIOLATION = 'write_violation';

    // =========================================================================
    // Query Scopes
    // =========================================================================

    public function scopeViolations(Builder $query): Builder
    {
        return $query->where('event_type', self::EVENT_WRITE_VIOLATION);
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('basarili', false);
    }

    public function scopePassed(Builder $query): Builder
    {
        return $query->where('basarili', true);
    }

    public function scopeByCorrelation(Builder $query, string $correlationId): Builder
    {
        return $query->where('correlation_id', $correlationId);
    }

    public function scopeByAgent(Builder $query, string $agentSource): Builder
    {
        return $query->where('agent_source', $agentSource);
    }

    public function scopeByToken(Builder $query, string $tokenHash): Builder
    {
        return $query->where('token_hash', $tokenHash);
    }

    public function scopeRecent(Builder $query, int $minutes = 10): Builder
    {
        return $query->where('olusturma_tarihi', '>=', now()->subMinutes($minutes));
    }

    public function scopeSince(Builder $query, \Carbon\Carbon $since): Builder
    {
        return $query->where('olusturma_tarihi', '>=', $since);
    }
}
