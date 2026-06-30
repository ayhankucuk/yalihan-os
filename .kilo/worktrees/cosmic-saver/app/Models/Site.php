<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends BaseModel
{
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'sites';

    // ✅ SAB uyumlu fillable alanlar (tablo yapısına göre)
    protected $fillable = [
        'name',
        'blok_adi',
        'adres',
        'is_active',
        'il_id',
        'ilce_id',
        'mahalle_id',
        'created_by',
    ];

    // ✅ SAB uyumlu casts
    protected $casts = [
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ✅ SAB uyumlu ilişkiler
    public function il()
    {
        return $this->belongsTo(Il::class, 'il_id');
    }

    public function ilce()
    {
        return $this->belongsTo(Ilce::class, 'ilce_id');
    }

    public function mahalle()
    {
        return $this->belongsTo(Mahalle::class, 'mahalle_id');
    }

    // ✅ SAB uyumlu scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF->value);
    }

    public function scopePasif($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByIl($query, $ilId)
    {
        return $query->where('il_id', $ilId);
    }

    public function scopeByIlce($query, $ilceId)
    {
        return $query->where('ilce_id', $ilceId);
    }

    // ✅ SAB uyumlu accessor
    public function getTamAdAttribute(): string
    {
        return $this->name.($this->adres ? ' - '.$this->adres : '');
    }

    // ✅ SAB uyumlu helper methods
    public function getLocationTextAttribute(): string
    {
        $parts = [];

        if ($this->il) {
            $parts[] = $this->il->name;
        }
        if ($this->ilce) {
            $parts[] = $this->ilce->name;
        }
        if ($this->mahalle) {
            $parts[] = $this->mahalle->name;
        }

        return implode(' / ', $parts);
    }

    public function isActive(): bool
    {
        return (bool) $this->aktiflik_durumu;
    }
}
