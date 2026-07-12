<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturePackLog extends Model
{
    use HasFactory;

    protected $table = 'feature_pack_logs';

    protected $fillable = [
        'feature_pack_id',
        'ilan_id',
        'user_id',
        'action',
        'scope',
        'affected_count',
        'snapshot_before',
        'snapshot_after',
        'diff',
        'reason',
        'rolled_back_at',
    ];

    protected $casts = [
        'feature_pack_id'  => 'integer',
        'ilan_id'          => 'integer',
        'user_id'          => 'integer',
        'affected_count'   => 'integer',
        'snapshot_before'  => 'array',
        'snapshot_after'   => 'array',
        'diff'            => 'array',
        'rolled_back_at'  => 'datetime',
    ];

    public function pack(): BelongsTo
    {
        return $this->belongsTo(FeaturePack::class, 'feature_pack_id');
    }

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Scope: Geri alınabilir (henüz rollback yapılmamış) */
    public function scopeUndoable($query)
    {
        return $query->whereNull('rolled_back_at');
    }

    /** Scope: Belirli bir işlem tipi */
    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
