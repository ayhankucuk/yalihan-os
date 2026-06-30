<?php

namespace App\Modules\Crm\Services;

use App\Models\Kisi;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\Log;

class KisiService
{
    use GuardsAgentWrites;
    /**
     * Yeni bir kişi oluşturur veya email/telefon eşleşiyorsa mevcut olanı döndürür (Idempotency Patch).
     * ✅ SAB: kisi_tipi required field kontrolü
     * ✅ SAB: Duplicate Entry Guard (email veya telefon)
     */
    public function createKisi(array $data): Kisi
    {
        $this->blockAgentWrite('createKisi');

        Log::info('Kişi upsert işlemi başlatılıyor (Duplicate Guard).', ['email' => $data['email'] ?? null, 'telefon' => $data['telefon'] ?? null]);

        // ✅ SAB: kisi_tipi default değer ataması
        if (empty($data['kisi_tipi'])) {
            $data['kisi_tipi'] = 'Müşteri';
            Log::warning('kisi_tipi boş, default değer atandı: Müşteri');
        }

        $query = Kisi::query();
        $matchFound = false;

        if (!empty($data['email'])) {
            $query->orWhere('email', $data['email']);
            $matchFound = true;
        }

        if (!empty($data['telefon'])) {
            $query->orWhere('telefon', $data['telefon']);
            $matchFound = true;
        }

        if ($matchFound) {
            $existing = $query->first();
            if ($existing) {
                Log::info('Duplicate Entry Guard tetiklendi. Varolan kişi döndürülüyor.', ['kisi_id' => $existing->id]);
                // Güncellenmesi gereken alanlar varsa burada güncellenebilir
                return $existing;
            }
        }

        return Kisi::create($data);
    }

    /**
     * Mevcut bir kişiyi günceller.
     */
    public function updateKisi(Kisi $kisi, array $data): Kisi
    {
        $this->blockAgentWrite('updateKisi');

        Log::info("{$kisi->id} ID'li kişi güncelleniyor.", $data);
        $kisi->update($data);

        return $kisi;
    }

    /**
     * Bir kişiyi siler.
     */
    public function deleteKisi(Kisi $kisi): ?bool
    {
        $this->blockAgentWrite('deleteKisi');

        Log::info("{$kisi->id} ID'li kişi siliniyor.");

        return $kisi->delete();
    }

    /**
     * ID ile bir kişiyi bulur.
     */
    public function getKisiById(int $id): ?Kisi
    {
        return Kisi::find($id);
    }

    /**
     * Tüm kişileri listeler.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function getAllKisiler(array $filters = [], int $paginate = 15)
    {
        $query = Kisi::query();

        // Ownership scope: danışman sadece kendi kişilerini görür.
        $currentUser = auth()->user();
        if ($currentUser) {
            $isAdmin = (method_exists($currentUser, 'isAdmin') && $currentUser->isAdmin()) ||
                       (method_exists($currentUser, 'hasRole') && $currentUser->hasRole(['admin', 'super-admin']));

            if (!$isAdmin) {
                // Non-admin: sadece kendi atanmış kişileri
                $query->where('danisman_id', $currentUser->id);
            }
        } else {
            // Unauthenticated: deterministik boş sonuç
            return collect();
        }

        // Search filter
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(ad, ' ', soyad) LIKE ?", ["%{$search}%"])
                    ->orWhere('telefon', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Danisman filtresi YALNIZCA admin kullanıcılara açıktır.
        if (isset($isAdmin) && $isAdmin && ! empty($filters['danisman_id'])) {
            $query->where('danisman_id', $filters['danisman_id']);
        }

        // Status filter
        if (isset($filters['aktiflik_durumu'])) {
            $query->where('aktiflik_durumu', $filters['aktiflik_durumu']);
        }

        // Limit for API responses
        if (! empty($filters['limit'])) {
            return $query->orderBy('created_at', 'desc')
                ->limit($filters['limit'])
                ->get();
        }

        return $query->orderBy('created_at', 'desc')->paginate($paginate);
    }

    /**
     * Kişi arama - API için optimize edilmiş
     * Context7 & Yalıhan Bekçi: Standart kişi arama metodu
     *
     * @return array Array of search results
     */
    public static function search(string $searchTerm, int $limit = 10): array
    {
        // ✅ SAB & Yalıhan Bekçi: Standart kişi sorgusu
        // 1. Aktiflik kontrolü: Sadece aktif kişiler (aktiflik_durumu = 1)
        // 2. Select optimization: Sadece gerekli kolonlar
        // 3. Sıralama: İsim sırasına göre (orderBy tam_ad)
        return Kisi::select(['id', 'ad', 'soyad', 'telefon', 'email', 'kisi_tipi', 'aktiflik_durumu'])
            ->where('aktiflik_durumu', 1) // ✅ SAB: Sadece aktif kişiler (tinyint)
            ->where(function ($query) use ($searchTerm) {
                $query->whereRaw("CONCAT(ad, ' ', soyad) LIKE ?", ["%{$searchTerm}%"])
                    ->orWhere('telefon', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            })
            ->orderByRaw("CONCAT(ad, ' ', soyad)") // ✅ İsim sırasına göre
            ->limit($limit)
            ->get()
            ->map(function ($kisi) {
                // ✅ SAB: Response formatı standartlaştırıldı
                $tamAd = trim($kisi->ad.' '.$kisi->soyad);

                return [
                    'id' => $kisi->id,
                    'ad' => $kisi->ad,
                    'soyad' => $kisi->soyad,
                    'tam_ad' => $tamAd,
                    'telefon' => $kisi->telefon,
                    'email' => $kisi->email,
                    'kisi_tipi' => $kisi->kisi_tipi ?? null,
                    'text' => $tamAd.($kisi->telefon ? ' - '.$kisi->telefon : ''), // Context7 Live Search için
                ];
            })
            ->values() // ✅ Collection index'lerini sıfırla (array uyumluluğu)
            ->toArray(); // ✅ SAB: Array döndür (JavaScript uyumluluğu için)
    }

    /**
     * İlan sahibi olarak uygun kişileri getir
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPotentialOwners(?string $searchTerm = null, int $limit = 10)
    {
        $query = Kisi::where('aktiflik_durumu', 1); // Context7: tinyInteger(1)

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("CONCAT(ad, ' ', soyad) LIKE ?", ["%{$searchTerm}%"])
                    ->orWhere('telefon', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($kisi) {
                // İlan sahibi olarak uygunluk skoru hesapla
                $kisi->owner_score = self::calculateOwnerScore($kisi);

                return $kisi;
            })
            ->sortByDesc('owner_score');
    }

    /**
     * Kişinin ilan sahibi olarak uygunluk skorunu hesapla
     */
    public static function calculateOwnerScore(Kisi $kisi): int
    {
        $score = 0;

        // Temel bilgiler
        if ($kisi->ad && $kisi->soyad) {
            $score += 10;
        }
        if ($kisi->telefon) {
            $score += 5;
        }
        if ($kisi->email) {
            $score += 5;
        }

        // Kişi tipi (Context7: kisi_tipi preferred)
        $kisiTipi = $kisi->kisi_tipi ?? $kisi->musteri_tipi ?? null;
        if ($kisiTipi === 'Ev Sahibi' || $kisiTipi === 'ev_sahibi') {
            $score += 15;
        } elseif ($kisiTipi === 'Satıcı' || $kisiTipi === 'satici') {
            $score += 10;
        } elseif ($kisiTipi === 'Alıcı' || $kisiTipi === 'alici') {
            $score += 5;
        }

        // Aktiflik durumu (Context7: tinyint, 1 = aktif)
        if ($kisi->aktiflik_durumu === 1) {
            $score += 10;
        }

        // İletişim bilgileri
        if ($kisi->telefon && $kisi->email) {
            $score += 5;
        }

        return $score;
    }

    /**
     * Kişiyi ilan sahibi olarak işaretle
     */
    public static function markAsOwner(int $kisiId): bool
    {
        $kisi = Kisi::find($kisiId);
        if (! $kisi) {
            return false;
        }

        $kisi->update([
            'kisi_tipi' => 'Ev Sahibi',
            'aktiflik_durumu' => 1,
        ]);

        return true;
    }

    /**
     * Kişinin ilan sahibi geçmişini getir
     */
    public static function getOwnerHistory(int $kisiId): array
    {
        $kisi = Kisi::find($kisiId);
        if (! $kisi) {
            return [];
        }

        $ilanlar = \App\Models\Ilan::where('owner_id', $kisiId)
            ->with(['kategori', 'danisman'])
            ->get();

        return [
            'kisi' => $kisi,
            'ilanlar' => $ilanlar,
            'statistics' => [
                'total_listings' => $ilanlar->count(),
                'active_listings' => $ilanlar->where('aktiflik_durumu', 1)->count(),
                'sold_listings' => $ilanlar->where('aktiflik_durumu', 0)->count(),
                'total_value' => $ilanlar->sum('fiyat'),
                'average_price' => $ilanlar->avg('fiyat'),
                'categories' => $ilanlar->groupBy('kategori.name')->map->count(),
            ],
        ];
    }
}
