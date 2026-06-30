<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertySubscription extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $fillable = [
        'ilan_id',
        'expense_item_id',
        'abone_no',
        'sayac_no',
        'servis_saglayici',
        'sozlesme_tarihi',
        'notlar',
        'is_active',
        'ulke_id',
    ];

    protected $casts = [
        'sozlesme_tarihi' => 'date',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'ulke_id' => 'integer',
    ];

    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function expenseItem()
    {
        return $this->belongsTo(ExpenseItem::class);
    }
}
