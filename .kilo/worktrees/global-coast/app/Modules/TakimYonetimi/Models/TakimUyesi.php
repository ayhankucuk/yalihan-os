<?php

namespace App\Modules\TakimYonetimi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TakimUyesi extends Model
{
    use HasFactory;

    protected $table = 'takim_uyeleri';

    protected $fillable = [
        'rol',
        'user_id',
        'performans_skoru',
    ];

    protected $casts = [
        'uzmanlik_alani' => 'array',
        'calisma_saati' => 'array',
        'performans_skoru' => 'decimal:2',
        'toplam_gorev' => 'integer',
        'basarili_gorev' => 'integer',
        'ortalama_tamamlanma_suresi' => 'integer',
        'metadata' => 'array',
        'aktiflik_durumu' => 'string',
    ];

    // Enum değerleri
    public static function getRoller(): array
    {
        return ['admin', 'danisman', 'alt_kullanici', 'musteri_temsilcisi'];
    }

    public static function getDurumlar(): array
    {
        return ['aktif', 'pasif', 'izinli', 'tatilde'];
    }

    public static function getEkipler(): array
    {
        return ['yalıhan_emlak', 'diger', 'misafir'];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function gorevler(): HasMany
    {
        return $this->hasMany(Gorev::class, 'danisman_id', 'user_id');
    }

    public function gorevTakip(): HasMany
    {
        return $this->hasMany(GorevTakip::class, 'user_id', 'user_id');
    }

    // Scopes
    public function scopeAktif($query)
    {
        return $query->where('aktiflik_durumu', 'aktif');
    }

    public function scopeRol($query, $rol)
    {
        return $query->where('departman', $rol);
    }

    public function scopeLokasyon($query, $lokasyon)
    {
        return $query->where('lokasyon', $lokasyon);
    }

    public function scopePerformans($query, $minSkor = 0)
    {
        return $query->where('performans_skoru', '>=', $minSkor);
    }

    public function scopeEkip($query, $ekip)
    {
        return $query->where('ekip', $ekip);
    }

    public function scopeYalihanEkip($query)
    {
        return $query->where('ekip', 'yalıhan_emlak');
    }

    // Accessors
    public function getRolEtiketiAttribute(): string
    {
        $etiketler = [
            'admin' => '<span class="badge bg-danger">Admin</span>',
            'danisman' => '<span class="badge bg-primary">Danışman</span>',
            'alt_kullanici' => '<span class="badge bg-info">Alt Kullanıcı</span>',
            'musteri_temsilcisi' => '<span class="badge bg-success">Müşteri Temsilcisi</span>',
        ];

        return $etiketler[$this->rol] ?? $etiketler['danisman'];
    }

    public function getDurumEtiketiAttribute(): string
    {
        $etiketler = [
            'aktif' => '<span class="badge bg-success">Aktif</span>',
            'pasif' => '<span class="badge bg-secondary">Pasif</span>',
            'izinli' => '<span class="badge bg-warning">İzinli</span>',
            'tatilde' => '<span class="badge bg-info">Tatilde</span>',
        ];

        return $etiketler[$this->aktiflik_durumu] ?? $etiketler['aktif'];
    }

    public function getBasariOraniAttribute(): float
    {
        if ($this->toplam_gorev === 0) {
            return 0.0;
        }

        return round(($this->basarili_gorev / $this->toplam_gorev) * 100, 2);
    }

    public function getBasariOraniEtiketiAttribute(): string
    {
        $oran = $this->getBasariOraniAttribute();

        if ($oran >= 90) {
            return '<span class="badge bg-success">%'.$oran.' Mükemmel</span>';
        } elseif ($oran >= 80) {
            return '<span class="badge bg-primary">%'.$oran.' Çok İyi</span>';
        } elseif ($oran >= 70) {
            return '<span class="badge bg-info">%'.$oran.' İyi</span>';
        } elseif ($oran >= 60) {
            return '<span class="badge bg-warning">%'.$oran.' Orta</span>';
        } else {
            return '<span class="badge bg-danger">%'.$oran.' Düşük</span>';
        }
    }

    public function getPerformansEtiketiAttribute(): string
    {
        $skor = $this->performans_skoru;

        if ($skor >= 8.5) {
            return '<span class="badge bg-success">'.$skor.'/10 Mükemmel</span>';
        } elseif ($skor >= 7.0) {
            return '<span class="badge bg-primary">'.$skor.'/10 Çok İyi</span>';
        } elseif ($skor >= 5.5) {
            return '<span class="badge bg-info">'.$skor.'/10 İyi</span>';
        } elseif ($skor >= 4.0) {
            return '<span class="badge bg-warning">'.$skor.'/10 Orta</span>';
        } else {
            return '<span class="badge bg-danger">'.$skor.'/10 Düşük</span>';
        }
    }

    public function getEkipEtiketiAttribute(): string
    {
        $etiketler = [
            'yalıhan_emlak' => '<span class="badge bg-primary">🏢 Yalıhan Emlak</span>',
            'diger' => '<span class="badge bg-secondary">👥 Diğer</span>',
            'misafir' => '<span class="badge bg-info">👤 Misafir</span>',
        ];

        return $etiketler[$this->ekip] ?? $etiketler['diger'];
    }

    protected function rol(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value, array $attributes) {
                return $attributes['departman'] ?? null;
            },
            set: function ($value) {
                return ['departman' => $value];
            }
        );
    }

    // Methods
    public function aktifMi(): bool
    {
        return $this->aktiflik_durumu === 'aktif';
    }

    public function danismanMi(): bool
    {
        return $this->rol === 'danisman';
    }

    public function adminMi(): bool
    {
        return $this->rol === 'admin';
    }

    public function performansGuncelle(): void
    {
        $gorevler = $this->gorevler()->where('yayin_durumu', 'tamamlandi')->get();

        $toplamGorev = $gorevler->count();
        // 🆕 USTA Auto-Fix: deadline → bitis_tarihi (Context7 uyumlu)
        $basariliGorev = $gorevler->where('bitis_tarihi', '>=', now())->count();

        $ortalamaSure = $gorevler->avg('tahmini_sure') ?? 0;

        $performansSkoru = $this->performansSkoruHesapla($basariliGorev, $toplamGorev, $ortalamaSure);

        $this->update([
            'toplam_gorev' => $toplamGorev,
            'basarili_gorev' => $basariliGorev,
            'ortalama_tamamlanma_suresi' => $ortalamaSure,
            'performans_skoru' => $performansSkoru,
        ]);
    }

    private function performansSkoruHesapla(int $basariliGorev, int $toplamGorev, float $ortalamaSure): float
    {
        if ($toplamGorev === 0) {
            return 0.0;
        }

        $basariOrani = ($basariliGorev / $toplamGorev) * 100;
        $sureSkoru = max(0, 10 - ($ortalamaSure / 60)); // Saat cinsinden, maksimum 10 puan

        $performansSkoru = ($basariOrani * 0.7) + ($sureSkoru * 0.3);

        return round(min(10.0, max(0.0, $performansSkoru)), 2);
    }

    public function uzmanlikAlaniVarMi(string $alan): bool
    {
        return in_array($alan, $this->uzmanlik_alani ?? []);
    }

    public function yalihanEkipMi(): bool
    {
        return $this->ekip === 'yalıhan_emlak';
    }

    public function ekipUyesiMi(string $ekip): bool
    {
        return $this->ekip === $ekip;
    }
}
