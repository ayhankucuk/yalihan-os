<?php

namespace App\Models;

use App\Models\Ilan;
use App\Models\Talep;
use App\Models\User;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 🎯 MATCHING FEEDBACK MODEL
 *
 * Danışmanların eşleşmeleri değerlendirmesini sağlayan model.
 *
 * Context7: yayin_durumu_log, match_score
 * SAB Rule 11: Ghost Model resolved.
 */
class MatchingFeedback extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'matching_feedbacks';

    protected $fillable = [
        'talep_id',
        'ilan_id',
        'danisman_id',
        'feedback_tipi',
        'match_score',
        'cortex_score_at_time',
        'match_breakdown',
        'yayin_durumu_log',
        'danisman_notu',
        'sonuc_olusturuldu',
        'sonuc_tarihi',
    ];

    protected $casts = [
        'match_breakdown' => 'array',
        'match_score' => 'float',
        'cortex_score_at_time' => 'integer',
        'sonuc_olusturuldu' => 'boolean',
        'sonuc_tarihi' => 'datetime',
    ];

    // --- İLİŞKİLER ---

    public function talep()
    {
        return $this->belongsTo(Talep::class, 'talep_id');
    }

    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function danisman()
    {
        return $this->belongsTo(User::class, 'danisman_id');
    }

    // --- SCOPES ---

    public function scopePending($query)
    {
        return $query->where('yayin_durumu_log', 'pending');
    }

    /**
     * Context7: yayin_durumu_log is the canonical replacement
     */
}
