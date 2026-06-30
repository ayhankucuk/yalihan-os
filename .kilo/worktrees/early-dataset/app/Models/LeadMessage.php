<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadMessage extends BaseModel
{
    use HasCountryScope;

    protected $table = 'lead_messages';

    protected $fillable = [
        'lead_id',
        'message_text',
        'message_type',
        'platform_message_id',
        'intent',
        'confidence',
        'entities',
        'sentiment',
        'sent_at',
    ];

    protected $casts = [
        'entities' => 'array',
        'confidence' => 'decimal:2',
        'sent_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Message belongs to lead
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // ========== SCOPES ==========

    /**
     * Incoming messages only
     */
    public function scopeIncoming($query)
    {
        return $query->where('message_type', 'incoming');
    }

    /**
     * Outgoing messages only
     */
    public function scopeOutgoing($query)
    {
        return $query->where('message_type', 'outgoing');
    }

    /**
     * High confidence messages (>= 0.70)
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence', '>=', 0.70);
    }

    /**
     * Messages with specific sentiment
     */
    public function scopeBySentiment($query, string $sentiment)
    {
        return $query->where('sentiment', $sentiment);
    }
}
