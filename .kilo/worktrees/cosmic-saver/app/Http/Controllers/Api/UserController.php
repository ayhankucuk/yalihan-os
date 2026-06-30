<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use ValidatesApiRequests;

    /**
     * Search users (specifically for danisman role)
     * Context7: C7-USER-SEARCH-2025-10-30
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $limit = min($request->get('limit', 20), 50);
            $role = $request->get('role', 'danisman'); // Default: danisman

            // ✅ SAB & Yalıhan Bekçi: Danışman sorgusu standartı
            // 1. Spatie Permission: whereHas('roles') kullan (roles plural, role singular YASAK)
            // 2. Status kontrolü: where('aktiflik_durumu', 1) - Sadece aktif danışmanlar
            // 3. Select optimization: select(['id', 'name', 'email'])
            // 4. Eager loading: with('roles:id,name') - N+1 query önleme
            $usersQuery = User::with('roles:id,name')
                ->select(['id', 'name', 'email', 'aktiflik_durumu'])
                ->where('aktiflik_durumu', true);

            // ✅ Role filter (Spatie Permission)
            if (! empty($role)) {
                $usersQuery->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            }

            // Search filter
            if (! empty($query)) {
                $usersQuery->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
                });
            }

            // Order by name
            $usersQuery->orderBy('name'); // context7-ignore

            $users = $usersQuery->limit($limit)->get();

            // ✅ SAB: Response formatı standartlaştırıldı
            $formattedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'text' => $user->name.($user->email ? ' - '.$user->email : ''), // Context7 Live Search için
                    'kisi_tipi' => 'Sistem Danışmanı', // For compatibility with kişiler
                ];
            })
                ->values() // ✅ Collection index'lerini sıfırla (array uyumluluğu)
                ->toArray(); // ✅ SAB: Array döndür (JavaScript uyumluluğu için)

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'data' => $formattedUsers,
                'count' => count($formattedUsers),
                'query' => $query,
                'source' => 'users', // To differentiate from kisiler
            ], 'Danışman araması başarıyla tamamlandı');
        } catch (\Exception $e) {
            Log::error('User search error: '.$e->getMessage());

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Danışman araması sırasında hata oluştu', $e);
        }
    }

    /**
     * Get all active danismanlar
     */
    public function danismanlar(Request $request): JsonResponse
    {
        try {
            $danismanlar = User::where('aktiflik_durumu', 1)
                ->orderBy('name') // context7-ignore
                ->select(['id', 'name', 'email'])
                ->get();

            $danismanlar->each(function ($user) {
                $user->text = $user->name;
            });

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'data' => $danismanlar,
                'count' => $danismanlar->count(),
            ], 'Danışmanlar listesi başarıyla alındı');
        } catch (\Exception $e) {
            Log::error('Danismanlar list error: '.$e->getMessage());

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Danışmanlar listesi alınamadı', $e);
        }
    }
}
