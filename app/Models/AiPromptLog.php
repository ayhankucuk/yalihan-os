<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiPromptLog extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $fillable = [
        'prompt_hash',
        'template_id',
        'provider',
        'model',
        'governance_score',
        'has_violation',
        'violations',
        'prompt_text',
        'response_text',
        'duration_ms',
        'user_id'
    ];

    protected $casts = [
        'violations' => 'array',
        'governance_score' => 'integer',
        'has_violation' => 'boolean',
        'duration_ms' => 'integer'
    ];

    public function template()
    {
        return $this->belongsTo(UpsTemplate::class, 'template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
