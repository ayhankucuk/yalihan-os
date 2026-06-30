<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * IlanTaslak Model
 * Context7 Standard Architecture
 * Sprint Plan A2 Implementation
 */
class IlanTaslak extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ilan_taslaklar';

    protected $fillable = [
        'user_id',
        'site_id',
        'ilan_id',
        'category_id',
        'yayin_tipi_id',
        'step',
        'payload',
        'taslak_durumu',
        'version'
    ];

    protected $casts = [
        'payload' => 'array',
        'category_id' => 'integer',
        'yayin_tipi_id' => 'integer',
        'taslak_durumu' => 'integer',
        'step' => 'integer',
        'version' => 'integer'
    ];

    /**
     * Alias for 'payload' column to maintain compatibility with WizardDraftService naming.
     */
    public function getDataAttribute()
    {
        return $this->payload;
    }

    public function setDataAttribute($value)
    {
        $this->attributes['payload'] = $value;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function ilan()
    {
        return $this->belongsTo(Ilan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('taslak_durumu', 1);
    }
}
