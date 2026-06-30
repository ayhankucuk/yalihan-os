<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ListingStateTransition
 *
 * SAB §7: Immutable audit log — update/delete YASAK.
 * Her satır bir listing state geçişini temsil eder.
 */
class ListingStateTransition extends BaseModel
{
    use HasCountryScope;

    /**
     * No updated_at column — records are immutable.
     */
    const UPDATED_AT = null;

    protected $table = 'listing_state_transitions';

    protected $fillable = [
        'ilan_id',
        'from_state',
        'to_state',
        'aktan_id',
        'meta',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    // ---- Relations ----

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function aktan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aktan_id');
    }

    // ---- Guard: Mutation yasak ----

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \LogicException('ListingStateTransition kayıtları değiştirilemez (immutable audit log).');
        }
        return parent::save($options);
    }

    public function delete(): bool|null
    {
        throw new \LogicException('ListingStateTransition kayıtları silinemez (immutable audit log).');
    }
}
