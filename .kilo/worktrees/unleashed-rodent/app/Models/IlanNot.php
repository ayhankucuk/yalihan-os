<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * App\Models\IlanNot
 *
 * Context7 Standard: Ilan Notları ve AI Pitch Kayıtları
 */
class IlanNot extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ilan_notlari';

    protected $fillable = [
        'ilan_id',
        'user_id',
        'not_icerigi',
        'not_tipi',
        'onemli_mi',
        'is_ai_generated',
        'channel',
    ];

    protected $casts = [
        'onemli_mi' => 'boolean',
        'is_ai_generated' => 'boolean',
    ];

    // İlişkiler
    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scope'lar
    public function scopePitch($query)
    {
        return $query->where('not_tipi', 'pitch');
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }
}
