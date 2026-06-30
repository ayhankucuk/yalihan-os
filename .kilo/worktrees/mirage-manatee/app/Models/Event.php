<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'yazlik_rezervasyonlar';

    protected $fillable = [
        'ilan_id',
        'check_in',
        'check_out',
        'musteri_adi',
        'musteri_telefon',
        'musteri_email',
        'misafir_sayisi',
        'toplam_fiyat',
        'rezervasyon_durumu',
        'ozel_istekler',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'misafir_sayisi' => 'integer',
        'toplam_fiyat' => 'decimal:2',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }
}
