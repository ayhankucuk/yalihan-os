<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $ilan_id
 * @property string $dosya_yolu
 * @property int $display_order
 * @property bool $kapak_fotografi
 * @property string|null $alt_text
 */
class IlanFotografi extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ilan_fotograflari';

    protected $fillable = [
        'ilan_id',
        'dosya_adi',
        'dosya_yolu',
        'display_order',
        'kapak_fotografi',
        'aciklama',
    ];

    protected $casts = [
        'kapak_fotografi' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * İlana erişim için ilişki
     */
    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Accessor: Fotoğraf URL'i
     */
    public function getUrlAttribute()
    {
        return $this->dosya_yolu ? \Illuminate\Support\Facades\Storage::url($this->dosya_yolu) : null;
    }
}
