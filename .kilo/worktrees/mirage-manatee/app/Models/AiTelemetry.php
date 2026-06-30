<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\SaaS\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCountryScope;

/**
 * Class AiTelemetry
 *
 * Observability model for AI service performance monitoring.
 *
 * SAB Compliance:
 * - Tenant Isolation: tenant_id required for all queries
 * - Context7 Naming: aktiflik_kodu (not status_code)
 * - Model Hierarchy: Extends BaseModel (Foundation Lock compliance)
 * - Country Scope: Country Isolation compliance
 *
 * Created: 2026-05-21 (Oturum 30 - SEAL BREAK Remediation)
 * Authority: Mimar (SEAL BREAK PROTOCOL - Seçenek 2)
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $provider
 * @property string $model_name
 * @property string $feature
 * @property int $response_time_ms
 * @property int $tokens_used
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property float $cost_usd
 * @property array|null $prompt_metadata
 * @property array|null $response_metadata
 * @property int $aktiflik_kodu
 * @property string|null $hata_mesaji
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AiTelemetry extends BaseModel
{
    use HasCountryScope;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_telemetry';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'provider',
        'model_name',
        'feature',
        'response_time_ms',
        'tokens_used',
        'prompt_tokens',
        'completion_tokens',
        'cost_usd',
        'prompt_metadata',
        'response_metadata',
        'aktiflik_kodu',
        'hata_mesaji',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'response_time_ms' => 'integer',
        'tokens_used' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'cost_usd' => 'decimal:8',
        'prompt_metadata' => 'array',
        'response_metadata' => 'array',
        'aktiflik_kodu' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the telemetry record.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope a query to only include records for a specific tenant.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include records for a specific provider.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope a query to only include records for a specific feature.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $feature
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForFeature($query, string $feature)
    {
        return $query->where('feature', $feature);
    }

    /**
     * Scope a query to only include successful requests (aktiflik_kodu < 400).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('aktiflik_kodu', '<', 400);
    }

    /**
     * Scope a query to only include failed requests (aktiflik_kodu >= 400).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('aktiflik_kodu', '>=', 400);
    }

    /**
     * Scope a query to only include slow requests (response_time_ms > threshold).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $thresholdMs
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSlow($query, int $thresholdMs = 1000)
    {
        return $query->where('response_time_ms', '>', $thresholdMs);
    }

    /**
     * Scope a query to records within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon|string $start
     * @param \Carbon\Carbon|string|null $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenDates($query, $start, $end = null)
    {
        $query->where('created_at', '>=', $start);

        if ($end) {
            $query->where('created_at', '<=', $end);
        }

        return $query;
    }

    /**
     * Calculate p99 latency for a given query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return int|null
     */
    public static function calculateP99Latency($query): ?int
    {
        $count = $query->count();

        if ($count === 0) {
            return null;
        }

        $offset = (int) ceil($count * 0.99);

        return $query->orderBy('response_time_ms', 'desc')
            ->skip($offset - 1)
            ->take(1)
            ->value('response_time_ms');
    }

    /**
     * Calculate average latency for a given query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return float|null
     */
    public static function calculateAvgLatency($query): ?float
    {
        return $query->avg('response_time_ms');
    }

    /**
     * Calculate total cost for a given query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return float
     */
    public static function calculateTotalCost($query): float
    {
        return (float) $query->sum('cost_usd');
    }

    /**
     * Calculate error rate for a given query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return float Percentage (0-100)
     */
    public static function calculateErrorRate($query): float
    {
        $total = $query->count();

        if ($total === 0) {
            return 0.0;
        }

        $failed = (clone $query)->failed()->count();

        return ($failed / $total) * 100;
    }
}
