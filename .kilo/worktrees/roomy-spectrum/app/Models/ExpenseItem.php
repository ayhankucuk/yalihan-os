<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseItem extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $fillable = [
        'ad',
        'slug',
        'icon',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'display_order' => 'integer',
    ];

    public function expenses()
    {
        return $this->hasMany(PropertyExpense::class);
    }
}
