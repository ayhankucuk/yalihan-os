<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class AIMessage extends BaseModel
{
    use HasCountryScope;
    protected $table = 'ai_messages';

    protected $fillable = [
        'communication_id',
        'conversation_id',
        'channel',
        'role',
        'content',
        'mesaj_durumu',
        'ai_model_used',
        'ai_generated_at',
        'tokens_used',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'ai_generated_at' => 'datetime',
    ];
}
