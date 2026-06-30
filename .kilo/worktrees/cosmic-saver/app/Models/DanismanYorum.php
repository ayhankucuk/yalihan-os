<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DanismanYorum extends Model
{
    use SoftDeletes;

    protected $table = 'danisman_yorumlar';

    protected $fillable = [
        'danisman_id',
        'musteri_adi',
        'yorum',
        'rating',
        'onay_durumu',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    // Scope: sadece onaylı yorumlar
    public function scopeOnaylı($query)
    {
        return $query->where('onay_durumu', 'approved');
    }

    public function danisman()
    {
        return $this->belongsTo(User::class, 'danisman_id');
    }
}
