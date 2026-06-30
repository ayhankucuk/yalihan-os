<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Mesaj
 *
 * Mülk sahibi (Owner) ve Danışman (Agent) arasındaki iletişim için model.
 */
class Mesaj extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'mesajlar';

    protected $fillable = [
        'tenant_id',
        'gonderen_id',
        'alici_id',
        'ilan_id',
        'icerik',
        'okundu_mu',
    ];

    protected $casts = [
        'okundu_mu' => 'boolean',
    ];

    /**
     * Mesajı gönderen kullanıcı (Danışman veya Mülk Sahibi)
     */
    public function gonderen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gonderen_id');
    }

    /**
     * Mesajı alan kullanıcı (Danışman veya Mülk Sahibi)
     */
    public function alici(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alici_id');
    }

    /**
     * Mesajın bağlı olduğu ilan (opsiyonel)
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Okunmamış mesajları filtreler
     */
    public function scopeOkunmamis($query)
    {
        return $query->where('okundu_mu', false);
    }
}
