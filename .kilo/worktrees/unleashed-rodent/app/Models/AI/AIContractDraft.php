<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class AIContractDraft extends Model
{
    protected $table = 'ai_contract_drafts';

    protected $fillable = [
        'ilan_id',
        'danisman_id',
        'draft_content',
        'yayin_durumu',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
