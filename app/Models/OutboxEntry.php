<?php

namespace App\Models;

use App\Traits\HasCountryScope;

class OutboxEntry extends BaseModel
{
    use HasCountryScope;

    protected $table = 'outbox_entries';

    protected $fillable = [
        'event_key',
        'payload',
        'yayin_durumu',
        'attempts',
        'error_message',
        'idempotency_key',
        'processed_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'processed_at' => 'datetime'
    ];
}
