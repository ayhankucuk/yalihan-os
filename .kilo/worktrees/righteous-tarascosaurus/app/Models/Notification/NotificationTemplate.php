<?php

namespace App\Models\Notification;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends BaseModel
{
    use HasCountryScope;
    use SoftDeletes;

    protected $table = 'notification_templates';

    protected $fillable = [
        'key',
        'channel',
        'subject',
        'content',
        'provider_template_id',
        'language',
        'display_order',
        'aktiflik_durumu',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'aktiflik_durumu' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Scope: Active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('aktiflik_durumu', 1);
    }

    /**
     * Scope: By channel.
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope: By language.
     */
    public function scopeForLanguage($query, string $language = 'tr')
    {
        return $query->where('language', $language);
    }
}
