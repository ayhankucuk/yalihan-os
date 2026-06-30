<?php

namespace App\Models\Notification;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class OutboundNotification extends BaseModel
{
    use HasCountryScope;

    // Delivery States (Context7 Canonical TR)
    public const STATE_PENDING         = 'bekliyor';
    public const STATE_PROCESSING      = 'isleniyor';
    public const STATE_SENT            = 'gonderildi';
    public const STATE_FAILED          = 'basarisiz';
    public const STATE_RETRY_SCHEDULED = 'tekrar_planlandi';
    public const STATE_CANCELLED       = 'iptal';

    protected $table = 'outbound_notifications';

    /**
     * Transitional Bridge: keep legacy delivery alias for backward compatibility.
     * @sab-ignore Context7 (This is a bridge for stabilization)
     */
    public function getStatusAttribute()
    {
        return $this->gonderim_durumu;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['gonderim_durumu'] = $value;
    }

    protected $fillable = [
        'channel',
        'recipient',
        'template_key',
        'payload_data',
        'gonderim_durumu',
        'deneme_sayisi',
        'hata_mesaji',
        'gonderim_tarihi',
        'son_deneme_tarihi',
        'basarisiz_olma_tarihi',
        'provider_response',
        'display_order',
        'aktiflik_durumu',
    ];

    protected $casts = [
        'payload_data' => 'array',
        'provider_response' => 'array',
        'gonderim_tarihi' => 'datetime',
        'son_deneme_tarihi' => 'datetime',
        'basarisiz_olma_tarihi' => 'datetime',
        'aktiflik_durumu' => 'integer',
    ];

    /**
     * Scope: Processing notifications.
     */
    public function scopeProcessing($query)
    {
        return $query->where('gonderim_durumu', self::STATE_PROCESSING);
    }

    /**
     * Scope: Sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('gonderim_durumu', self::STATE_SENT);
    }

    /**
     * Scope: Failed notifications.
     */
    public function scopeFailed($query)
    {
        return $query->where('gonderim_durumu', self::STATE_FAILED);
    }

    /**
     * Resend this notification (Retry semantics).
     */
    public function resend(): bool
    {
        $dispatcher = app(\App\Services\Notification\NotificationDispatcher::class);
        $notification = \App\DTOs\Notification\GenericNotification::make(
            $this->channel,
            $this->recipient,
            $this->template_key,
            $this->payload_data ?? []
        );

        // N2: Re-route to adapter using existing ID to keep history in one record
        return $dispatcher->routeToAdapter($notification, $this->id);
    }
}
