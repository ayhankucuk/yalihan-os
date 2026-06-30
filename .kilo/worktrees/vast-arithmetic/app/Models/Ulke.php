<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasCountryScope;

class Ulke extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    /**
     * Model için kullanılacak tablo
     */
    protected $table = 'ulkeler';

    /**
     * Toplu atama yapılabilecek özellikler
     */
    protected $fillable = [
        'ulke_adi',
        'ulke_kodu',
    ];

    /**
     * Context7: name accessor for compatibility
     * Component'ler name attribute bekliyor
     */
    public function getNameAttribute()
    {
        return $this->ulke_adi;
    }

    /**
     * Bir ülkenin birden çok ili olabilir
     */
    public function iller()
    {
        return $this->hasMany(Il::class, 'ulke_id');
    }

    /**
     * Bir ülkenin birden çok adresi olabilir - Global Address Support
     */
    public function adresler()
    {
        return $this->hasMany(Adres::class, 'ulke_id');
    }

    /**
     * Türkiye default ülkesi olarak ayarla
     */
    public static function getTurkiye()
    {
        return static::where('ulke_kodu', 'TR')->first();
    }

    /**
     * Aktif ülkeleri getir
     */
    public static function getActiveCountries()
    {
        return static::whereNotNull('ulke_kodu')->orderBy('ulke_adi')->get(); // context7-ignore
    }

    /**
     * Ülke kodu ile ülke bul
     */
    public static function findByCode(string $code)
    {
        return static::where('ulke_kodu', $code)->first();
    }
}
