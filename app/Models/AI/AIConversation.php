<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use App\Models\Communication;
use App\Traits\BelongsToTenant;
use App\Traits\HasCountryScope;

class AIConversation extends BaseModel
{
    use BelongsToTenant, HasCountryScope;

    protected $table = 'ai_conversations';

    protected $fillable = [
        'communication_id',
        'channel',
        'tenant_id',
        'ulke_id',
        'aktiflik_durumu',
        'metadata',
    ];

    protected $casts = [
        'aktiflik_durumu' => 'boolean',
        'metadata' => 'array',
    ];

    public function communication(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Communication::class, 'communication_id');
    }
}
