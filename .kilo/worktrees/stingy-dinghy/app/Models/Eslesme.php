<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Eslesme extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'eslesmeler';

    protected $fillable = [
        'kisi_id',
        'ilan_id',
        'talep_id',
        'danisman_id',
        'skor',
        'notlar',
        'eslesme_durumu',
        'eslesme_detaylari',
        'eslesme_tarihi',
    ];

    protected $casts = [
        'skor' => 'integer',
        'eslesme_detaylari' => 'array',
        'eslesme_tarihi' => 'datetime',
    ];

    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function danisman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'danisman_id');
    }

    public function etiketler(): BelongsToMany
    {
        return $this->belongsToMany(Etiket::class, 'eslesme_etiket', 'eslesme_id', 'etiket_id');
    }
}
