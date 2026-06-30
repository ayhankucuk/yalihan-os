<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class SavedSearch extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $fillable = [
        'user_id',
        'name',
        'criteria',
        'notification_frequency',
        'last_run_at',
    ];

    protected $casts = [
        'criteria' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
