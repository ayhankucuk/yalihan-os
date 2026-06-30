<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase: 19.5 Hardening
 */
class AiLog extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ai_logs';

    /**
     * Context7: Türkçe Timestamp İsimlendirmeleri
     */
    const CREATED_AT = 'olusturma_tarihi';
    const UPDATED_AT = 'guncelleme_tarihi';

    protected $fillable = [
        'provider',
        'endpoint',
        'request_type',
        'event_type',
        'content_type',
        'content_id',
        'model',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'maliyet_usd',
        'duration_ms',
        'aktiflik_kodu',
        'correlation_id',
        'calisma_durumu',
        'hata_mesaji',
        'user_id',
        'ip_address',
        'request_payload',
        'response_payload',
        'metadata',
        'version',
    ];

    protected $casts = [
        'maliyet_usd' => 'decimal:6',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'metadata' => 'array',
        'olusturma_tarihi' => 'datetime',
        'guncelleme_tarihi' => 'datetime',
    ];

    /**
     * Logu oluşturan kullanıcı (Opsiyonel)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
