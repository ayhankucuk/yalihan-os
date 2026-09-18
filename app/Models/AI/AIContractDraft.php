<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class AIContractDraft extends BaseModel
{
    use HasCountryScope;
    protected $table = 'ai_contract_drafts';

    protected $fillable = [
        'ulke_id',
        'contract_type',
        'property_id',
        'ilan_id',
        'kisi_id',
        'danisman_id',
        'content',
        'draft_content',
        'yayin_durumu',
        'ai_model_used',
        'ai_generated_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'ai_generated_at' => 'datetime',
    ];
}
