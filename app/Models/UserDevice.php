<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class UserDevice extends BaseModel
{
    use HasCountryScope;
    protected $fillable = [
        'user_id',
        'device_id',
        'fcm_token',
        'platform',
        'last_active_at',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
