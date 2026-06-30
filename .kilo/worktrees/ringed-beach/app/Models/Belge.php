<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Belge
 *
 * Mülk sahibi paneli veya genel süreçte kullanıcılara / ilanlara ait evrakları tutar.
 */
class Belge extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'belgeler';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'ilan_id',
        'baslik',
        'dosya_yolu',
        'dosya_tipi',
        'belge_turu',
        'boyut_kb',
    ];

    /**
     * Belgenin sahibi
     */
    public function kullanici(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Belgenin bağlı olduğu ilan (opsiyonel)
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }
}
