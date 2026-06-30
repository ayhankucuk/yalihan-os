<?php

namespace App\Models\Hermes;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * HermesAnalytics Model
 *
 * Event metrik ve analitik veri deposu.
 * Hermes üzerinden akan event'lerin istatistiklerini tutar.
 *
 * @sab-context7-table hermes_analytics
 */
class HermesAnalytics extends BaseModel
{
    use HasFactory;

    protected $table = 'hermes_analytics';

    protected $fillable = [
        'event_name',
        'tenant_id',
        'date',
        'total_count',
        'success_count',
        'failure_count',
        'avg_duration_ms',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'total_count' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'avg_duration_ms' => 'float',
        'metadata' => 'array',
    ];

    /**
     * Scope: tenant isolation
     */
    public function scopeTenant($query, ?int $tenantId)
    {
        if ($tenantId === null) {
            return $query;
        }
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: by date
     */
    public function scopeOnDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope: by event name
     */
    public function scopeForEvent($query, string $eventName)
    {
        return $query->where('event_name', $eventName);
    }

    /**
     * Record an event occurrence
     */
    public static function record(
        string $eventName,
        bool $success,
        float $durationMs,
        ?int $tenantId = null,
        array $metadata = []
    ): void {
        $today = now()->toDateString();

        $record = self::firstOrCreate(
            [
                'event_name' => $eventName,
                'tenant_id' => $tenantId,
                'date' => $today,
            ],
            [
                'total_count' => 0,
                'success_count' => 0,
                'failure_count' => 0,
                'avg_duration_ms' => 0,
                'metadata' => [],
            ]
        );

        // Update counters
        $record->increment('total_count');

        if ($success) {
            $record->increment('success_count');
        } else {
            $record->increment('failure_count');
        }

        // Update rolling average duration
        $total = $record->total_count;
        $currentAvg = $record->avg_duration_ms;
        $record->avg_duration_ms = (($currentAvg * ($total - 1)) + $durationMs) / $total;
        $record->save();
    }
}
