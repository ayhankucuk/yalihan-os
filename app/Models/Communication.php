<?php

namespace App\Models;

use App\Models\Tenant;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Communication Model
 *
 * Çok kanallı iletişim kaydı (Telegram, WhatsApp, Instagram, E-posta, Web).
 * Polimorfik: communicable_type/communicable_id → Ilan, Kisi, vb.
 * Önceki Deprecated\Communication ghost'unun kanonik karşılığı.
 *
 * WAVE1 — Email Intelligence: Gmail mesajları için genişletildi.
 * tenant_id ile Gmail mesajları için tenant isolation sağlar.
 *
 * @property int|null          $id
 * @property int|null           $tenant_id
 * @property string|null        $communicable_type
 * @property int|null           $communicable_id
 * @property string|null        $reservation_id
 * @property string             $channel           telegram|whatsapp|instagram|email|web
 * @property string            $message
 * @property string|null        $sender_name
 * @property string|null        $sender_phone
 * @property string|null        $sender_email
 * @property string|null        $sender_instagram
 * @property string|null        $sender_id
 * @property string|null        $external_message_id  Gmail Message-ID (idempotency)
 * @property array|null         $ai_analysis
 * @property string|null         $severity           P0|P1|P2
 * @property array|null         $ai_extracted_data  LLM signal extraction
 * @property string|null        $platform           airbnb|booking.com|direct|unknown
 * @property string             $reply_durumu       bekliyor|cevaplandi|arşivlendi
 * @property \Illuminate\Support\Carbon|null $replied_at
 * @property int|null           $created_by
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property int|null           $resolved_by
 */
class Communication extends BaseModel
{
    use HasCountryScope;

    protected $table = 'communications';

    // ── Severity sabitleri (CommunicationSeverityPolicy ile senkron) ────────────
    public const SEVERITY_P0 = 'P0';
    public const SEVERITY_P1 = 'P1';
    public const SEVERITY_P2 = 'P2';

    // ── Platform sabitleri ─────────────────────────────────────────────────────
    public const PLATFORM_AIRBNB     = 'airbnb';
    public const PLATFORM_BOOKING    = 'booking.com';
    public const PLATFORM_DIRECT     = 'direct';
    public const PLATFORM_UNKNOWN    = 'unknown';

    protected $fillable = [
        'tenant_id',
        'communicable_type',
        'communicable_id',
        'reservation_id',
        'channel',
        'message',
        'subject',
        'sender_name',
        'sender_phone',
        'sender_email',
        'sender_instagram',
        'sender_id',
        'external_message_id',
        'ai_analysis',
        'severity',
        'ai_extracted_data',
        'platform',
        'reply_durumu',
        'replied_at',
        'created_by',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'ai_analysis'       => 'array',
        'ai_extracted_data' => 'array',
        'resolved_at'      => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // İlişkiler
    // -------------------------------------------------------------------------

    /** Tenant */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Polimorfik sahip (Ilan, Kisi, vb.) */
    public function communicable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Bağlı rezervasyon (varsa) */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(PropertyReservation::class);
    }

    /** Oluşturan kullanıcı */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Müdahale sonucu kapatán kullanıcı */
    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // -------------------------------------------------------------------------
    // Scope'lar
    // -------------------------------------------------------------------------

    /** Kanal bazlı filtrele */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /** Cevaplanmamış iletişimler */
    public function scopeBekliyor($query)
    {
        return $query->where('reply_durumu', 'bekliyor');
    }

    /** Cevaplanmış iletişimler */
    public function scopeCevaplandi($query)
    {
        return $query->where('reply_durumu', 'cevaplandi');
    }

    /** Email kanalı */
    public function scopeEmail($query)
    {
        return $query->where('channel', 'email');
    }

    /** Severity bazlı filtre */
    public function scopeForSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /** Cozumlenmemis (Ayhan müdahalesi bekliyor) */
    public function scopeUnresolved($query)
    {
        return $query->where('reply_durumu', 'bekliyor')
                     ->whereNull('resolved_at');
    }

    /** Priority: P0 ve P1 — müdahale gerekli */
    public function scopeRequiresAction($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_P0, self::SEVERITY_P1])
                      ->whereNull('resolved_at');
    }

    // -------------------------------------------------------------------------
    // Yardımcılar
    // -------------------------------------------------------------------------

    /**
     * İletişimi cevaplandı olarak işaretle.
     */
    public function markAsReplied(): bool
    {
        return $this->update([
            'reply_durumu' => 'cevaplandi',
            'replied_at'   => now(),
        ]);
    }

    /**
     * Ayhan müdahalesi sonrası çözüldü olarak işaretle.
     */
    public function markAsResolved(int $userId): bool
    {
        return $this->update([
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    /**
     * Severity P0 mu?
     */
    public function isP0(): bool
    {
        return $this->severity === self::SEVERITY_P0;
    }

    /**
     * Severity P1 mi?
     */
    public function isP1(): bool
    {
        return $this->severity === self::SEVERITY_P1;
    }
}
