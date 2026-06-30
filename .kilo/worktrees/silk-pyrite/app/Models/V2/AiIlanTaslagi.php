<?php

namespace App\Models\V2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * AiIlanTaslagi Model - V2 API Wrapper for V1 Schema
 * Context7: AI draft management with approval workflow
 */
class AiIlanTaslagi extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ilan_taslaklar';

    protected $fillable = [
        'user_id',
        'site_id',
        'ilan_id',
        'step',
        'ana_kategori_id',
        'alt_kategori_id',
        'yayin_tipi_id',
        'baslik',
        'payload',
        'taslak_durumu',
        'version',
    ];

    protected $casts = [
        'payload' => 'json',
        'taslak_durumu' => 'integer',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
