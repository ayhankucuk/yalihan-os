<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class AIIlanTaslagi extends Model
{
    protected $table = 'ai_ilan_taslaklari';

    protected $fillable = [
        'danisman_id',
        'ilan_id',
        'yayin_durumu',
        'taslak_durumu',
        'ai_response',
        'ai_model_used',
        'ai_prompt_version',
        'ai_generated_at',
    ];

    protected $casts = [
        'ai_response' => 'array',
        'ai_generated_at' => 'datetime',
    ];

    /**
     * Taslağı onayla
     */
    public function approve(int $userId): bool
    {
        return $this->update([
            'yayin_durumu' => 'approved',
            'taslak_durumu' => 'approved'
        ]);
    }

    /**
     * Taslak onaylı mı?
     */
    public function isApproved(): bool
    {
        return $this->yayin_durumu === 'approved' || $this->taslak_durumu === 'approved';
    }
}
