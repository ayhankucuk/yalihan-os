<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class IlanTalepEslesme extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'eslesmeler';

    protected $fillable = [
        'ilan_id',
        'talep_id',
        'notlar',
    ];

    protected $casts = [
        'ilan_id' => 'integer',
        'talep_id' => 'integer',
    ];

    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function talep()
    {
        return $this->belongsTo(Talep::class, 'talep_id');
    }
}
