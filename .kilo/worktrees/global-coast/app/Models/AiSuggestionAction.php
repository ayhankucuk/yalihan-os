<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSuggestionAction extends BaseModel
{
    use HasCountryScope;

    public $timestamps = false;

    protected $table = 'ai_suggestion_actions';

    protected $fillable = [
        'suggestion_id',
        'action',
        'user_id',
        'note',
        'snapshot_json',
        'created_at',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(AiFieldSuggestion::class, 'suggestion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
