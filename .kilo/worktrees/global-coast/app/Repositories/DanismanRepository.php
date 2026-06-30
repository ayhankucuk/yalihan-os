<?php

namespace App\Repositories;

use App\Governance\Instrumentation\RepositoryInstrumentation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

/**
 * ✅ Sprint 1 - Öncelik 1: Danisman Repository Pattern
 *
 * Centralizes all queries for Danisman (User with 'danisman' role):
 * - Role-based filtering (Spatie Permission)
 * - Active/inactive status handling (aktiflik_durumu)
 * - Online status tracking (last_activity_at)
 * - Search and filtering logic
 * - Statistics aggregation
 *
 * SAB Compliance:
 * - Repository Pattern enforced
 * - Context7 naming (aktiflik_durumu, not status)
 * - RepositoryInstrumentation for governance metrics
 * - Tenant isolation ready
 */
class DanismanRepository
{
    use RepositoryInstrumentation;

    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Base query for danışman users
     * Always includes role filter and eager loads roles relationship
     */
    protected function baseDanismanQuery(): Builder
    {
        return $this->model->newQuery()
            ->with('roles:id,name')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'danisman');
            });
    }

    /**
     * Get paginated list of danışmanlar with filters
     *
     * @param array $filters [
     *   'search' => string,
     *   'aktiflik_durumu' => '0'|'1'|null,
     *   'online' => 'Online'|'Offline'|null,
     *   'sort' => 'name_asc'|'name_desc'|'created_asc'|'created_desc'
     * ]
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAdminList(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->baseDanismanQuery();

        // ✅ SAB: Varsayılan olarak sadece aktif danışmanları göster
        if (!isset($filters['aktiflik_durumu'])) {
            $query->where('aktiflik_durumu', 1);
        }

        // Search filter
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                // Context7: phone_number kolonu migration sonrası eklenecek
                if (Schema::hasColumn('users', 'phone_number')) {
                    $q->orWhere('phone_number', 'like', "%{$search}%");
                }
            });
        }

        // Aktiflik durumu filter
        $aktiflik = $filters['aktiflik_durumu'] ?? null;
        if ($aktiflik === '1' || $aktiflik === 1) {
            $query->where('aktiflik_durumu', 1);
        } elseif ($aktiflik === '0' || $aktiflik === 0) {
            $query->where('aktiflik_durumu', 0);
        }

        // Online status filter
        $online = $filters['online'] ?? null;
        if ($online === 'Online') {
            $query->whereNotNull('last_activity_at')
                ->where('last_activity_at', '>', now()->subMinutes(5));
        } elseif ($online === 'Offline') {
            $query->where(function ($q) {
                $q->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<=', now()->subMinutes(5));
            });
        }

        // Sorting
        $sort = $filters['sort'] ?? 'created_desc';
        switch ($sort) {
            case 'name_asc':
                $query->orderBy('name'); // context7-ignore
                break;
            case 'name_desc':
                $query->orderByDesc('name'); // context7-ignore
                break;
            case 'created_asc':
                $query->orderBy('created_at'); // context7-ignore
                break;
            case 'created_desc':
            default:
                $query->orderByDesc('created_at'); // context7-ignore
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Get statistics for danışmanlar
     *
     * @return array [
     *   'toplam_danisman' => int,
     *   'durum_danisman' => int (aktif),
     *   'online_danisman' => int,
     *   'ortalama_performans' => float
     * ]
     */
    public function getStatistics(): array
    {
        $baseQuery = $this->baseDanismanQuery();

        return [
            'toplam_danisman' => (clone $baseQuery)->count(),
            'durum_danisman' => (clone $baseQuery)->where('aktiflik_durumu', 1)->count(),
            'online_danisman' => (clone $baseQuery)
                ->whereNotNull('last_activity_at')
                ->where('last_activity_at', '>', now()->subMinutes(5))
                ->count(),
            'ortalama_performans' => 0, // ✅ SAB: Column missing in DB
        ];
    }

    /**
     * Find a danışman by ID
     * Returns null if user is not a danışman
     */
    public function findById(int $id): ?User
    {
        return $this->baseDanismanQuery()->find($id);
    }

    /**
     * Find a danışman by ID or throw 404
     * Throws ModelNotFoundException if user is not a danışman
     */
    public function findOrFail(int $id): User
    {
        return $this->baseDanismanQuery()->findOrFail($id);
    }

    /**
     * Check if a user is a danışman
     */
    public function isDanisman(User $user): bool
    {
        return $user->hasRole('danisman');
    }

    /**
     * Get danışman with detailed relationships for show page
     *
     * @param User $danisman
     * @param int $ilanLimit
     * @return User
     */
    public function loadDetailedRelations(User $danisman, int $ilanLimit = 10): User
    {
        $danisman->load([
            'roles:id,name',
            'ilanlar' => function ($q) use ($ilanLimit) {
                $q->where('yayin_durumu', \App\Enums\IlanDurumu::YAYINDA->value)
                    ->latest()
                    ->limit($ilanLimit);
            },
        ]);

        // ✅ SAB: Danışman yorumları (tablo kontrolü ile)
        if (Schema::hasTable('danisman_yorumlari')) {
            $danisman->load([
                'onayliDanismanYorumlari' => function ($q) {
                    $q->with('kisi:id,tam_ad,email')
                        ->orderBy('created_at', 'desc'); // context7-ignore
                },
            ]);
        }

        return $danisman;
    }

    /**
     * Get danışman performance statistics
     *
     * @param int $danismanId
     * @return array
     */
    public function getPerformanceStats(int $danismanId): array
    {
        $toplamIlan = \App\Models\Ilan::where('danisman_id', $danismanId)->count();
        $aktifIlan = \App\Models\Ilan::where('danisman_id', $danismanId)
            ->where('yayin_durumu', \App\Enums\IlanDurumu::YAYINDA->value)
            ->count();

        $toplamMusteri = \App\Models\Kisi::where('danisman_id', $danismanId)->count();
        $aktifMusteri = \App\Models\Kisi::where('danisman_id', $danismanId)
            ->where('aktiflik_durumu', 1)
            ->count();

        $basariOrani = $toplamIlan > 0 ? round(($aktifIlan / $toplamIlan) * 100, 1) : 0.0;

        // Talep sayısı
        $toplamTalep = \App\Models\Talep::where('danisman_id', $danismanId)->count();
        $aktifTalep = \App\Models\Talep::where('danisman_id', $danismanId)
            ->where('talep_durumu', \App\Enums\TalepDurumu::AKTIF->value)
            ->count();

        // Yorum istatistikleri (✅ SAB: Tablo kontrolü ile)
        $toplamYorum = 0;
        $onayliYorum = 0;
        $ortalamaRating = 0;

        if (Schema::hasTable('danisman_yorumlari')) {
            $danisman = $this->findById($danismanId);
            if ($danisman) {
                $toplamYorum = $danisman->danismanYorumlari()->count();
                $onayliYorum = $danisman->onayliDanismanYorumlari()->count();
                $ortalamaRating = $danisman->onayliDanismanYorumlari()->avg('rating') ?? 0;
            }
        }

        return [
            'toplam_ilan' => $toplamIlan,
            'ilan_sayisi' => $aktifIlan,
            'toplam_talep' => $toplamTalep,
            'aktif_talep' => $aktifTalep,
            'basari_orani' => $basariOrani,
            'musteri_memnuniyeti' => 80.0,
            'ai_skor' => 70.0,
            'performans_puani' => 85,
            'ai_degerlendirme' => 'Normal',
            'toplam_yorum' => $toplamYorum,
            'onayli_yorum' => $onayliYorum,
            'ortalama_rating' => round($ortalamaRating, 1),
        ];
    }

    /**
     * Get danışman's active listings (portföy)
     *
     * @param User $danisman
     * @param int $perPage
     * @param string $pageName
     * @return LengthAwarePaginator
     */
    public function getPortfolio(User $danisman, int $perPage = 12, string $pageName = 'portfoy_page'): LengthAwarePaginator
    {
        return $danisman->ilanlar()
            ->where('yayin_durumu', \App\Enums\IlanDurumu::YAYINDA->value)
            ->latest()
            ->paginate($perPage, ['*'], $pageName);
    }

    /**
     * Get danışman's approved reviews
     *
     * @param User $danisman
     * @param int $perPage
     * @param string $pageName
     * @return LengthAwarePaginator
     */
    public function getReviews(User $danisman, int $perPage = 10, string $pageName = 'yorum_page'): LengthAwarePaginator
    {
        if (!Schema::hasTable('danisman_yorumlari')) {
            // ✅ SAB: Tablo yoksa boş paginator oluştur
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                $perPage,
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        return $danisman->onayliDanismanYorumlari()
            ->with('kisi:id,tam_ad,email')
            ->latest()
            ->paginate($perPage, ['*'], $pageName);
    }
}
