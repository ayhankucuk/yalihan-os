<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use App\Traits\IncrementsStateVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

use App\Traits\HasCountryScope;

class Ozellik extends BaseModel
{
    use HasFactory;
    use HasActiveScope;
    use HasCountryScope;
    use IncrementsStateVersion;

    protected static $stateCategory = 'ozellik';

    protected $table = 'ozellikler';

    protected $fillable = [
        'name',
        'slug',
        'kategori_id',
        'aciklama',
        'veri_tipi',
        'veri_secenekleri',
        'birim',
        'zorunlu',
        'arama_filtresi',
        'ilan_kartinda_goster',
        'aktiflik_durumu', // ✅ SAB standard active field
        // 'display_order', // ⚠️ DB column exists (ozellikler.display_order) but intentionally excluded from mass-assignment
        // 'aktif_mi', // Legacy
        // 'is_readonly', // Cortex ROI
    ];

    protected $casts = [
        'aktiflik_durumu' => \App\Enums\AktiflikDurumu::class, // ✅ SAB standard
        // 'aktif_mi' => 'boolean',
        // 'is_readonly' => 'boolean',
        // 'display_order' => 'integer', // ⚠️ DB column exists but intentionally excluded from casts
        'veri_secenekleri' => 'array',
        'zorunlu' => 'boolean',
        'arama_filtresi' => 'boolean',
        'ilan_kartinda_goster' => 'boolean',
    ];

    public function kategori()
    {
        return $this->belongsTo(OzellikKategori::class, 'kategori_id');
    }
}
