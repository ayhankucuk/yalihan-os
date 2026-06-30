<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanismanChatMessage extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'session_id',
        'role', // user, assistant, system
        'content',
        'metadata',
        'is_error',
        'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_error' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(DanismanChatSession::class, 'session_id');
    }
}
