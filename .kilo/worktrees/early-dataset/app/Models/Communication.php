<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Communication Model
 *
 * Çok kanallı iletişim kaydı (Telegram, WhatsApp, Instagram, E-posta, Web).
 * Polimorfik: communicable_type/communicable_id → Ilan, Kisi, vb.
 * Önceki Deprecated\Communication ghost'unun kanonik karşılığı.
 *
 * @property int         $id
 * @property string|null $communicable_type
 * @property int|null    $communicable_id
 * @property string      $channel           telegram|whatsapp|instagram|email|web
 * @property string      $message
 * @property string|null $sender_name
 * @property string|null $sender_phone
 * @property string|null $sender_email
 * @property string|null $sender_instagram
 * @property string|null $sender_id
 * @property array|null  $ai_analysis
 * @property string      $reply_durumu      bekliyor|cevaplandi|arşivlendi
 * @property \Illuminate\Support\Carbon|null $replied_at
 * @property int|null    $created_by
 */
class Communication extends BaseModel
{
    use HasCountryScope;

    protected $table = 'communications';

    protected $fillable = [
        'communicable_type',
        'communicable_id',
        'channel',
        'message',
        'sender_name',
        'sender_phone',
        'sender_email',
        'sender_instagram',
        'sender_id',
        'ai_analysis',
        'reply_durumu',
        'replied_at',
        'created_by',
    ];

    protected $casts = [
        'ai_analysis' => 'array',
        'replied_at'  => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // İlişkiler
    // -------------------------------------------------------------------------

    /** Polimorfik sahip (Ilan, Kisi, vb.) */
    public function communicable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Oluşturan kullanıcı */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    // -------------------------------------------------------------------------
    // Yardımcılar
    // -------------------------------------------------------------------------

    /**
     * İletişimi cevaplandı olarak işaretle.
     * AIMessageService::sendMessage() tarafından çağrılır.
     */
    public function markAsReplied(): bool
    {
        return $this->update([
            'reply_durumu' => 'cevaplandi',
            'replied_at'   => now(),
        ]);
    }
}
