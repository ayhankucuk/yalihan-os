<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AiSecurityLog
 *
 * SAB Phase 14 Sprint 1: AI Security Forensic Log Model
 * Stores tamper-proof audit trail for AI security events using hash chains.
 *
 * @property int $id
 * @property string $event_type
 * @property int $user_id
 * @property array $context
 * @property string|null $previous_hash
 * @property string $current_hash
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 *
 * @package App\Models
 */
class AiSecurityLog extends BaseModel
{
    use HasCountryScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_security_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'event_type',
        'user_id',
        'context',
        'previous_hash',
        'current_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that triggered this security event
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
