<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends BaseModel
{
    use HasCountryScope;

    protected $table = 'lead_activities';

    protected $fillable = [
        'lead_id',
        'activity_type',
        'description',
        'performed_by',
        'activity_date',
        'duration_minutes',
    ];

    protected $casts = [
        'activity_date' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Activity belongs to lead
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Activity performed by agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ========== SCOPES ==========

    /**
     * Activities of specific type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Activities by agent
     */
    public function scopeByAgent($query, int $agentId)
    {
        return $query->where('performed_by', $agentId);
    }
}
