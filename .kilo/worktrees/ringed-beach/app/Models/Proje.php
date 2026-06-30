<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proje extends BaseModel
{
    use HasActiveScope;
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'projeler';

    protected $fillable = [
        'name',
        'slug',
        'aciklama',
        'baslangic_tarihi',
        'bitis_tarihi',
        'proje_durumu',
        'oncelik',
        'takim_lideri_id',
        'butce',
        'tamamlanma_yuzdesi',
        'notlar',
    ];

    protected $casts = [
        'baslangic_tarihi' => 'date',
        'bitis_tarihi' => 'date',
        'butce' => 'decimal:2',
        'tamamlanma_yuzdesi' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // İlişkiler
    public function takimLideri()
    {
        return $this->belongsTo(User::class, 'takim_lideri_id');
    }

    /**
     * @deprecated Use takimLideri() instead
     */
    public function user()
    {
        return $this->takimLideri();
    }

    public function gorevler()
    {
        return $this->hasMany(\App\Modules\TakimYonetimi\Models\Gorev::class);
    }

    public function dosyalar()
    {
        return $this->hasMany(\App\Modules\TakimYonetimi\Models\GorevDosya::class);
    }

    // Accessor'lar
    public function getDurumLabelAttribute()
    {
        $aktiflik_durumular = [
            'planlama' => 'Planlama',
            'devam_ediyor' => 'Devam Ediyor',
            'tamamlandi' => 'Tamamlandı',
            'iptal' => 'İptal',
            'beklemede' => 'Beklemede',
        ];

        return $aktiflik_durumular[$this->proje_durumu] ?? 'Bilinmiyor';
    }

    public function getOncelikLabelAttribute()
    {
        $oncelikler = [
            'dusuk' => 'Düşük',
            'orta' => 'Orta',
            'yuksek' => 'Yüksek',
            'kritik' => 'Kritik',
        ];

        return $oncelikler[$this->oncelik] ?? 'Bilinmiyor';
    }

    public function getKalanGunAttribute()
    {
        if (! $this->bitis_tarihi) {
            return null;
        }

        $now = now();
        $bitis = $this->bitis_tarihi;

        if ($bitis < $now) {
            return 0; // Süresi geçmiş
        }

        return $now->diffInDays($bitis);
    }

    public function getProgressYuzdeAttribute()
    {
        return $this->tamamlanma_yuzdesi.'%';
    }

    // Scope'lar
    // ✅ REFACTORED: scopeActive moved to HasActiveScope trait

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDurum($query, $projeDurumu)
    {
        return $query->where('proje_durumu', $projeDurumu);
    }

    public function scopeByOncelik($query, $oncelik)
    {
        return $query->where('oncelik', $oncelik);
    }

    public function scopeGecmis($query)
    {
        return $query->where('bitis_tarihi', '<', now());
    }

    public function scopeYaklasan($query, $gun = 7)
    {
        return $query->where('bitis_tarihi', '<=', now()->addDays($gun))
            ->where('bitis_tarihi', '>', now());
    }

    // Metodlar
    public function updateProgress()
    {
        $toplamGorev = $this->gorevler()->count();

        if ($toplamGorev == 0) {
            $this->update(['tamamlanma_yuzdesi' => 0]);

            return;
        }

        $tamamlananGorev = $this->gorevler()->where('gorev_durumu', 'tamamlandi')->count();
        $progress = round(($tamamlananGorev / $toplamGorev) * 100);

        $this->update(['tamamlanma_yuzdesi' => $progress]);
    }

    public function isGecmis()
    {
        return $this->bitis_tarihi && $this->bitis_tarihi < now();
    }

    public function isYaklasan($gun = 7)
    {
        return $this->bitis_tarihi &&
               $this->bitis_tarihi <= now()->addDays($gun) &&
               $this->bitis_tarihi > now();
    }
}
