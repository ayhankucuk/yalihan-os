<?php

namespace App\Models;

use App\Enums\TeklifDurumu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Teklif
 *
 * Mülk sahibi paneli veya genel süreçte ilanlara verilen spesifik fiyat tekliflerini tutar.
 */
class Teklif extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'teklifler';

    protected $fillable = [
        'tenant_id',
        'ilan_id',
        'kisi_id',
        'teklif_tutari',
        'para_birimi',
        'teklif_durumu',
        'mesaj',
        'gecerlilik_tarihi',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'teklif_tutari' => 'decimal:2',
        'teklif_durumu' => TeklifDurumu::class,
        'gecerlilik_tarihi' => 'datetime',
    ];

    /**
     * Teklifin verildiği ilan
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Teklifi veren kişi
     */
    public function teklifVeren(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }
}
