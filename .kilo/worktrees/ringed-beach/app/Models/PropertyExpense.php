<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyExpense extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $fillable = [
        'ilan_id',
        'expense_item_id',
        'miktar',
        'para_birimi',
        'fatura_tarihi',
        'donem_tarihi',
        'son_odeme_tarihi',
        'odeme_tarihi',
        'odeme_durumu',
        'belge_url',
        'notlar',
        'user_id',
        'ulke_id',
    ];

    protected $casts = [
        'miktar' => 'decimal:2',
        'fatura_tarihi' => 'date',
        'donem_tarihi' => 'date',
        'son_odeme_tarihi' => 'date',
        'odeme_tarihi' => 'date',
        'odeme_durumu' => 'integer',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
