<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class AIMessage extends Model
{
    protected $table = 'ai_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens_used',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
