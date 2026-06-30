<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TakimUyesi extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'takim_uyeleri';

    protected $fillable = [
        'user_id',
        'pozisyon',
        'departman',
        'performans_skoru',
        'ise_baslama_tarihi',
        // 'is_active' removed - column doesn't exist in database
        'notlar',
    ];

    protected $casts = [
        'ise_baslama_tarihi' => 'date',
        'performans_skoru' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
