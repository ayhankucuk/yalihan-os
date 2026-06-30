<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class AIConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $fillable = [
        'danisman_id',
        'kisi_id',
        'ilan_id',
        'aktiflik_durumu',
        'metadata',
    ];

    protected $casts = [
        'aktiflik_durumu' => 'boolean',
        'metadata' => 'array',
    ];
}
