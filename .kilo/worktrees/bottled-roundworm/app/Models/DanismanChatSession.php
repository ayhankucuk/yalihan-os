<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DanismanChatSession extends BaseModel
{
    use SoftDeletes;
    use HasCountryScope;

    protected $fillable = [
        'session_id',
        'user_id',
        'title',
        'is_active',
        'context_data',
        'ai_config_snapshot',
        'last_message_at',
    ];

    protected $casts = [
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'context_data' => 'array',
        'ai_config_snapshot' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DanismanChatMessage::class, 'session_id');
    }
}
