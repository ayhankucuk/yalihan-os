<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class EtkiAlaniOlayi (Domain Event)
 *
 * SAB Phase 15 Sprint 1: CQRS Event Sourcing Infrastructure
 * Immutable append-only event log for aggregate state reconstruction.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $aggregate_type
 * @property int $aggregate_id
 * @property string $event_type
 * @property int $sequence_number
 * @property array $payload
 * @property string|null $encrypted_payload
 * @property int|null $user_id
 * @property string|null $ip_adresi
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 *
 * @property-read Tenant $tenant
 * @property-read User|null $user
 *
 * @package App\Models
 */
class EtkiAlaniOlayi extends BaseModel
{
    use HasCountryScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'etki_alani_olaylari';

    /**
     * Disable updated_at (immutable event store)
     *
     * @var bool
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'sequence_number',
        'payload',
        'encrypted_payload',
        'user_id',
        'ip_adresi',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'sequence_number' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns this event
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user who triggered this event
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: Get events for specific aggregate
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $aggregateType
     * @param int $aggregateId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAggregate($query, string $aggregateType, int $aggregateId)
    {
        return $query->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->orderBy('sequence_number');
    }

    /**
     * Scope: Get events by type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $eventType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
