<?php

namespace App\Modules\Finans\Models;

use App\Models\Kisi;
use App\Modules\BaseModule\Models\BaseModel;
use App\Models\Ilan; // Context7: musteri → kisi
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Komisyon extends BaseModel
{
    use HasFactory, SoftDeletes;

    public const DURUM_HESAPLANDI = 'hesaplandi';
    public const DURUM_ONAYLANDI = 'onaylandi';
    public const DURUM_ODENDI = 'odendi';

    /**
     * İlişkilendirilmiş tablo adı
     *
     * @var string
     */
    protected $table = 'komisyonlar';

    /**
     * Toplu atanabilir alanlar
     *
     * @var array
     */
    protected $fillable = [
        'ilan_id',
        'kisi_id', // Context7: kisi_id → kisi_id
        'danisman_id',
        // Split Commission Fields (Context7: C7-SPLIT-COMMISSION-2025-11-25)
        'satici_danisman_id',
        'alici_danisman_id',
        'komisyon_tipi', // satis, kiralama, danismanlik
        'komisyon_orani',
        'komisyon_tutari',
        // Split Commission Fields
        'satici_komisyon_orani',
        'alici_komisyon_orani',
        'satici_komisyon_tutari',
        'alici_komisyon_tutari',
        'para_birimi',
        'ilan_fiyati',
        'hesaplama_tarihi',
        'odeme_tarihi',
        'notlar',
    ];

    /**
     * Cast edilecek özellikler
     *
     * @var array
     */
    protected $casts = [
        'komisyon_orani' => 'decimal:2',
        'komisyon_tutari' => 'decimal:2',
        'satici_komisyon_orani' => 'decimal:2',
        'alici_komisyon_orani' => 'decimal:2',
        'satici_komisyon_tutari' => 'decimal:2',
        'alici_komisyon_tutari' => 'decimal:2',
        'ilan_fiyati' => 'decimal:2',
        'hesaplama_tarihi' => 'date',
        'odeme_tarihi' => 'date',
    ];

    /**
     * İlan ile ilişki
     */
    public function ilan()
    {
        return $this->belongsTo(Ilan::class);
    }

    /**
     * Kişi ile ilişki (Context7: musteri → kisi)
     */
    public function kisi()
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }


    /**
     * Satıcı danışman ile ilişki (Context7: C7-SPLIT-COMMISSION-2025-11-25)
     */
    public function saticiDanisman()
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'satici_danisman_id');
    }

    /**
     * Alıcı danışman ile ilişki (Context7: C7-SPLIT-COMMISSION-2025-11-25)
     */
    public function aliciDanisman()
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'alici_danisman_id');
    }

    /**
     * Scope: Hesaplanan komisyonlar
     */
    public function scopeHesaplanan($query)
    {
        return $query->where('odeme_statusu', self::DURUM_HESAPLANDI);
    }

    /**
     * Scope: Onaylanan komisyonlar
     */
    public function scopeOnaylanan($query)
    {
        return $query->where('odeme_statusu', self::DURUM_ONAYLANDI);
    }

    /**
     * Scope: Ödenen komisyonlar
     */
    public function scopeOdendi($query)
    {
        return $query->where('odeme_statusu', self::DURUM_ODENDI);
    }

    /**
     * Scope: Komisyon tipine göre filtrele
     */
    public function scopeKomisyonTipi($query, $tip)
    {
        return $query->where('komisyon_tipi', $tip);
    }

    /**
     * Komisyon hesapla
     */
    public function hesaplaKomisyon(): void
    {
        $oran = $this->getKomisyonOrani();
        $this->komisyon_tutari = $this->ilan_fiyati * ($oran / 100);
        $this->hesaplama_tarihi = now();
        $this->odeme_statusu = self::DURUM_HESAPLANDI;
        $this->save();
    }

    /**
     * Komisyon oranını al
     */
    private function getKomisyonOrani(): float
    {
        return match ($this->komisyon_tipi) {
            'satis' => 3.0, // %3
            'kiralama' => 1.0, // %1
            'danismanlik' => 2.0, // %2
            default => 0.0,
        };
    }

    /**
     * Komisyonu onayla
     */
    public function onayla(): bool
    {
        return $this->update([
            'odeme_statusu' => self::DURUM_ONAYLANDI,
            'odeme_tarihi' => now(),
        ]);
    }

    /**
     * Komisyonu öde
     */
    public function ode(): bool
    {
        return $this->update([
            'odeme_statusu' => self::DURUM_ODENDI,
            'odeme_tarihi' => now(),
        ]);
    }

    /**
     * Komisyon durum rengi (Durum color)
     *
     * Context7: Attribute accessor - "durum" is not a database field
     * Returns color code based on odeme_statusu field value
     */
    public function getDurumRengiAttribute(): string
    {
        return match ($this->odeme_statusu) {
            self::DURUM_HESAPLANDI => 'yellow',
            self::DURUM_ONAYLANDI => 'blue',
            self::DURUM_ODENDI => 'green',
            default => 'gray',
        };
    }

    /**
     * Komisyon tipi etiketi
     */
    public function getKomisyonTipiEtiketiAttribute(): string
    {
        return match ($this->komisyon_tipi) {
            'satis' => 'Satış Komisyonu',
            'kiralama' => 'Kiralama Komisyonu',
            'danismanlik' => 'Danışmanlık Komisyonu',
            default => 'Bilinmiyor',
        };
    }
}
