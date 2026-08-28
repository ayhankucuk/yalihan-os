<?php

namespace App\Services\Danisman;

use App\Actions\Admin\Danisman\DeleteDanismanAction;
use App\Actions\Admin\Danisman\StoreDanismanAction;
use App\Actions\Admin\Danisman\ToggleDanismanDurumuAction;
use App\Actions\Admin\Danisman\TouchDanismanOnlineAction;
use App\Actions\Admin\Danisman\UpdateDanismanAction;
use App\Enums\IlanDurumu;
use App\Enums\TalepDurumu;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Talep;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * 🏢 DanismanService
 *
 * Primary domain service for Danışman (Advisor) operations.
 * Thin controller delegation target for DanismanController.
 */
class DanismanService
{
    public function __construct(
        private readonly StoreDanismanAction $storeDanismanAction,
        private readonly UpdateDanismanAction $updateDanismanAction,
        private readonly DeleteDanismanAction $deleteDanismanAction,
        private readonly ToggleDanismanDurumuAction $toggleDanismanDurumuAction,
        private readonly TouchDanismanOnlineAction $touchDanismanOnlineAction
    ) {}

    /**
     * Get paginated danisman list with filters applied.
     */
    public function getDanismanList(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = User::with('roles:id,name');

        $query->whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        });

        // Filter: aktiflik_durumu (Default: 1 if not explicitly provided)
        if (!array_key_exists('aktiflik_durumu', $filters) || $filters['aktiflik_durumu'] === null) {
            $query->where('aktiflik_durumu', 1);
        } else {
            $aktiflik = $filters['aktiflik_durumu'];
            if ($aktiflik === '1' || $aktiflik === 1) {
                $query->where('aktiflik_durumu', 1);
            } elseif ($aktiflik === '0' || $aktiflik === 0) {
                $query->where('aktiflik_durumu', 0);
            }
        }

        // Filter: search
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                if (Schema::hasColumn('users', 'phone_number')) {
                    $q->orWhere('phone_number', 'like', "%{$search}%");
                }
            });
        }

        // Filter: online
        $online = $filters['online'] ?? null;
        if ($online === 'Online') {
            $query->whereNotNull('last_activity_at')->where('last_activity_at', '>', now()->subMinutes(5));
        } elseif ($online === 'Offline') {
            $query->where(function ($q) {
                $q->whereNull('last_activity_at')->orWhere('last_activity_at', '<=', now()->subMinutes(5));
            });
        }

        // Sorting
        $sort = $filters['sort'] ?? null;
        if ($sort === 'name_asc') {
            $query->orderBy('name');
        } elseif ($sort === 'name_desc') {
            $query->orderByDesc('name');
        } elseif ($sort === 'created_asc') {
            $query->orderBy('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get aggregate statistics for danisman management dashboard.
     */
    public function getDanismanStats(): array
    {
        return [
            'toplam_danisman' => User::whereHas('roles', fn ($q) => $q->where('name', 'danisman'))->count(),
            'durum_danisman' => User::whereHas('roles', fn ($q) => $q->where('name', 'danisman'))->where('aktiflik_durumu', 1)->count(),
            'online_danisman' => User::whereHas('roles', fn ($q) => $q->where('name', 'danisman'))
                ->whereNotNull('last_activity_at')
                ->where('last_activity_at', '>', now()->subMinutes(5))
                ->count(),
            'ortalama_performans' => 0,
        ];
    }

    /**
     * Get detailed payload for a single Danisman profile.
     */
    public function getDanismanDetailData(User $danisman, Request $request): array
    {
        $danisman->load([
            'roles:id,name',
            'ilanlar' => function ($q) {
                $q->where('yayin_durumu', IlanDurumu::YAYINDA->value)->latest()->limit(10);
            },
        ]);

        if (Schema::hasTable('danisman_yorumlari')) {
            $danisman->load([
                'onayliDanismanYorumlari' => function ($q) {
                    $q->with('kisi:id,tam_ad,email')->orderBy('created_at', 'desc');
                },
            ]);
        }

        $aktifSekme = $request->get('t', 'hakkimda');
        $danismanId = $danisman->id;

        $toplamIlan = Ilan::where('danisman_id', $danismanId)->count();
        $aktifIlan = Ilan::where('danisman_id', $danismanId)->where('yayin_durumu', IlanDurumu::YAYINDA->value)->count();
        $toplamMusteri = Kisi::where('danisman_id', $danismanId)->count();
        $aktifMusteri = Kisi::where('danisman_id', $danismanId)->where('aktiflik_durumu', 1)->count();
        $basariOrani = $toplamIlan > 0 ? round(($aktifIlan / $toplamIlan) * 100, 1) : 0.0;

        $toplamTalep = Talep::where('danisman_id', $danismanId)->count();
        $aktifTalep = Talep::where('danisman_id', $danismanId)
            ->where('talep_durumu', TalepDurumu::AKTIF->value)
            ->count();

        $toplamYorum = 0;
        $onayliYorum = 0;
        $ortalamaRating = 0;

        if (Schema::hasTable('danisman_yorumlari')) {
            $toplamYorum = $danisman->danismanYorumlari()->count();
            $onayliYorum = $danisman->onayliDanismanYorumlari()->count();
            $ortalamaRating = $danisman->onayliDanismanYorumlari()->avg('rating') ?? 0;
        }

        $performans = [
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

        $portfoy = $danisman->ilanlar()
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->latest()
            ->paginate(12, ['*'], 'portfoy_page');

        if (Schema::hasTable('danisman_yorumlari')) {
            $yorumlar = $danisman->onayliDanismanYorumlari()
                ->with('kisi:id,tam_ad,email')
                ->latest()
                ->paginate(10, ['*'], 'yorum_page');
        } else {
            $yorumlar = new ConcreteLengthAwarePaginator(
                collect([]),
                0,
                10,
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        return [
            'danisman' => $danisman,
            'performans' => $performans,
            'aiOneriler' => [],
            'aktifSekme' => $aktifSekme,
            'portfoy' => $portfoy,
            'yorumlar' => $yorumlar,
        ];
    }

    /**
     * Create a new Danisman user via StoreDanismanAction.
     */
    public function createDanisman(array $input): User
    {
        $userData = [
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'aktiflik_durumu' => !empty($input['aktiflik_durumu']) ? 1 : 0,
            'title' => $input['title'] ?? 'Danışman',
        ];

        if (!empty($input['phone_number'])) {
            $userData['phone_number'] = $input['phone_number'];
        } elseif (!empty($input['telefon'])) {
            $userData['phone_number'] = $input['telefon'];
        }

        if (!empty($input['office_address'])) {
            $userData['office_address'] = $input['office_address'];
        } elseif (!empty($input['adres'])) {
            $userData['office_address'] = $input['adres'];
        }

        if (!empty($input['position']) && in_array($input['position'], ['danisman', 'asistan', 'broker'])) {
            $userData['position'] = $input['position'];
        }

        if (!empty($input['lisans_no'])) {
            $userData['lisans_no'] = $input['lisans_no'];
        }

        if (!empty($input['uzmanlik_alanlari']) && is_array($input['uzmanlik_alanlari'])) {
            $allowedAreas = ['Konut', 'Arsa', 'İşyeri', 'Yazlık', 'Turistik Tesis'];
            $filteredAreas = array_filter($input['uzmanlik_alanlari'], fn ($area) => in_array($area, $allowedAreas));
            if (!empty($filteredAreas)) {
                $userData['uzmanlik_alanlari'] = array_values($filteredAreas);
            }
        }

        if (!empty($input['deneyim_yili'])) {
            $userData['deneyim_yili'] = (int) $input['deneyim_yili'];
        }

        if (!empty($input['aktiflik_durumu'])) {
            $durumValue = $input['aktiflik_durumu'];
            if (in_array($durumValue, ['taslak', 'onay_bekliyor', 'aktif', 'satildi', 'kiralandi', 'pasif', 'arsivlendi'])) {
                $userData['aktiflik_notu'] = $durumValue;
                $userData['aktiflik_durumu'] = in_array($durumValue, ['taslak', 'onay_bekliyor', 'pasif']) ? 0 : 1;
            } elseif (in_array($durumValue, ['aktif', '1', 1, true], true)) {
                $userData['aktiflik_durumu'] = 1;
                $userData['aktiflik_notu'] = 'aktif';
            } elseif (in_array($durumValue, ['pasif', '0', 0, false], true)) {
                $userData['aktiflik_durumu'] = 0;
                $userData['aktiflik_notu'] = 'pasif';
            }
        }

        return $this->storeDanismanAction->handle($userData);
    }

    /**
     * Update an existing Danisman user via UpdateDanismanAction.
     */
    public function updateDanisman(User $danisman, array $input, $profilePhoto = null): bool
    {
        $fullName = $input['name'] ?? null;
        if (!empty($input['ad']) || !empty($input['soyad'])) {
            $ad = trim($input['ad'] ?? '');
            $soyad = trim($input['soyad'] ?? '');
            $fullName = trim($ad . ' ' . $soyad);
        } elseif (empty($fullName) && $danisman->name) {
            $fullName = $danisman->name;
        }

        $userData = [
            'name' => $fullName,
            'email' => $input['email'],
            'title' => $input['title'] ?? $danisman->title,
            'bio' => $input['bio'] ?? null,
            'phone_number' => $input['phone_number'] ?? null,
            'lisans_no' => $input['lisans_no'] ?? null,
            'deneyim_yili' => (int) ($input['deneyim_yili'] ?? 0),
            'office_address' => $input['office_address'] ?? null,
            'office_phone' => $input['office_phone'] ?? null,
            'whatsapp_number' => $input['whatsapp_number'] ?? null,
            'expertise_summary' => $input['expertise_summary'] ?? null,
            'certificates_info' => $input['certificates_info'] ?? null,
            'instagram_profile' => $input['instagram_profile'] ?? null,
            'linkedin_profile' => $input['linkedin_profile'] ?? null,
            'facebook_profile' => $input['facebook_profile'] ?? null,
            'twitter_profile' => $input['twitter_profile'] ?? null,
            'youtube_channel' => $input['youtube_channel'] ?? null,
            'website' => $input['website'] ?? null,
        ];

        if (!empty($input['position']) && in_array($input['position'], ['danisman', 'asistan', 'broker'])) {
            $userData['position'] = $input['position'];
        }

        if (!empty($input['aktiflik_durumu'])) {
            $durumValue = $input['aktiflik_durumu'];
            if (in_array($durumValue, ['taslak', 'onay_bekliyor', 'aktif', 'satildi', 'kiralandi', 'pasif', 'arsivlendi'])) {
                $userData['aktiflik_notu'] = $durumValue;
                $userData['aktiflik_durumu'] = in_array($durumValue, ['taslak', 'onay_bekliyor', 'pasif']) ? 0 : 1;
            } elseif (in_array($durumValue, ['aktif', '1', 1, true], true)) {
                $userData['aktiflik_durumu'] = 1;
                $userData['aktiflik_notu'] = 'aktif';
            } elseif (in_array($durumValue, ['pasif', '0', 0, false], true)) {
                $userData['aktiflik_durumu'] = 0;
                $userData['aktiflik_notu'] = 'pasif';
            }
        }

        if (!empty($input['password'])) {
            $userData['password'] = Hash::make($input['password']);
        }

        if (!empty($input['uzmanlik_alanlari']) && is_array($input['uzmanlik_alanlari'])) {
            $allowedAreas = ['Konut', 'Arsa', 'İşyeri', 'Yazlık', 'Turistik Tesis'];
            $filteredAreas = array_filter($input['uzmanlik_alanlari'], fn ($area) => in_array($area, $allowedAreas));
            if (!empty($filteredAreas)) {
                $userData['uzmanlik_alanlari'] = array_values($filteredAreas);
            }
        }

        if ($profilePhoto) {
            $path = $profilePhoto->store('profile-photos', 'public');
            $userData['profile_photo_path'] = $path;
        }

        return $this->updateDanismanAction->handle($danisman, $userData);
    }

    /**
     * Delete Danisman via DeleteDanismanAction.
     */
    public function deleteDanisman(User $danisman): bool
    {
        return $this->deleteDanismanAction->handle($danisman);
    }

    /**
     * Toggle status via ToggleDanismanDurumuAction.
     */
    public function toggleStatus(User $danisman): bool
    {
        return $this->toggleDanismanDurumuAction->handle($danisman);
    }

    /**
     * Touch online status via TouchDanismanOnlineAction.
     */
    public function touchOnline(User $danisman): void
    {
        $this->touchDanismanOnlineAction->handle($danisman);
    }
}
