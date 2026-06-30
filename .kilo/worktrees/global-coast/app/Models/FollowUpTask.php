<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpTask extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'lead_id',
        'assigned_to',
        'task_type',
        'description',
        'due_date',
        'priority',
        'gorev_durumu',
        'escalated',
        'escalated_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'escalated_at' => 'datetime',
        'completed_at' => 'datetime',
        'escalated' => 'boolean',
        'gorev_durumu' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * Lead relationship
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Agent relationship
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope: Pending tasks
     */
    public function scopePending($query)
    {
        return $query->where('gorev_durumu', \App\Enums\AktiflikDurumu::BEKLEMEDE);
    }
}
