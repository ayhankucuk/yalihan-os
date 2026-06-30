<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @sealed 2026-03-04
 */
class TkgmQuery extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'tkgm_queries';

    // ✅ CONTEXT7: FILLABLE FIELDS
    protected $fillable = [
        'ada',
        'parsel',
        'il_id',
        'ilce_id',
        'mahalle_id',
        'alan_m2',
        'kaks',
        'taks',
        'nitelik',
        'gabari',
        'ilan_id',
        'satis_fiyati',
        'satis_tarihi',
        'satis_suresi_gun',
        'query_source',
        'user_id',
        'queried_at',
        'tkgm_raw_data',
        'islem_durumu',
    ];

    // ✅ CONTEXT7: CASTS
    protected $casts = [
        'il_id' => 'integer',
        'ilce_id' => 'integer',
        'mahalle_id' => 'integer',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'alan_m2' => 'decimal:2',
        'kaks' => 'decimal:2',
        'taks' => 'integer',
        'gabari' => 'integer',
        'ilan_id' => 'integer',
        'satis_fiyati' => 'decimal:2',
        'satis_tarihi' => 'date',
        'satis_suresi_gun' => 'integer',
        'user_id' => 'integer',
        'queried_at' => 'datetime',
        'tkgm_raw_data' => 'array',
        'islem_durumu' => 'boolean',
    ];

    public function il()
    {
        return $this->belongsTo(\App\Models\Il::class, 'il_id');
    }

    public function ilce()
    {
        return $this->belongsTo(\App\Models\Ilce::class, 'ilce_id');
    }

    public function mahalle()
    {
        return $this->belongsTo(\App\Models\Mahalle::class, 'mahalle_id');
    }

    public function ilan()
    {
        return $this->belongsTo(\App\Models\Ilan::class, 'ilan_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function scopeSold($query)
    {
        return $query->whereNotNull('satis_fiyati');
    }

    public function scopeLocation($query, $ilId, $ilceId = null, $mahalleId = null)
    {
        $query->where('il_id', $ilId);

        if ($ilceId) {
            $query->where('ilce_id', $ilceId);
        }

        if ($mahalleId) {
            $query->where('mahalle_id', $mahalleId);
        }

        return $query;
    }

    public function scopeDateRange($query, $startDate, $endDate = null)
    {
        $query->where('queried_at', '>=', $startDate);

        if ($endDate) {
            $query->where('queried_at', '<=', $endDate);
        }

        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('islem_durumu', 1);
    }

    public function getBirimFiyatAttribute()
    {
        if ($this->satis_fiyati && $this->alan_m2 > 0) {
            return round($this->satis_fiyati / $this->alan_m2, 2);
        }

        return null;
    }

    public function getInsaatAlaniAttribute()
    {
        if ($this->alan_m2 && $this->kaks) {
            return round($this->alan_m2 * $this->kaks, 2);
        }

        return null;
    }
}
