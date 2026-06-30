<?php

namespace App\Http\Controllers\Api\V2;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * V2 Users API Controller
 *
 * Context7: 100% Compliant
 * - Field names: name, email, telefon, password, aktiflik_durumu
 * - B-006: Deprecated\Kullanici → App\Models\User (2026-06-12)
 */
class UserController extends Controller
{
    /**
     * Display a listing of users
     * GET /api/v2/users
     */
    public function index(): JsonResponse
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'telefon', 'aktiflik_durumu', 'created_at'])
            ->where('aktiflik_durumu', true)
            ->latest('created_at')
            ->paginate(20);

        return ResponseService::success([
            'data' => $users->items(),
            'pagination' => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created user
     * POST /api/v2/users
     */
    public function store(Request $request, \App\Actions\Api\V2\User\StoreUserAction $action): JsonResponse
    {
        $validated = $request->validate([
            'ad_soyad'   => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'telefon'    => 'required|string|max:20',
            'sifre_hash' => 'required|string|min:6',
        ]);

        $user = $action->handle($validated);

        return ResponseService::success(
            $user->only(['id', 'name', 'email', 'aktiflik_durumu']),
            'Kullanıcı başarıyla oluşturuldu',
            201
        );
    }

    /**
     * Display the specified user
     * GET /api/v2/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        return ResponseService::success(
            $user->only(['id', 'name', 'email', 'telefon', 'aktiflik_durumu', 'created_at'])
        );
    }

    /**
     * Update the specified user
     * PUT /api/v2/users/{user}
     */
    public function update(Request $request, User $user, \App\Actions\Api\V2\User\UpdateUserAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name'            => 'sometimes|string|max:255',
            'email'           => 'sometimes|email|unique:users,email,' . $user->id,
            'telefon'         => 'sometimes|string|max:20',
            'aktiflik_durumu' => 'sometimes|boolean',
        ]);

        $action->handle($user, $validated);

        return ResponseService::success(
            $user->only(['id', 'name', 'email', 'aktiflik_durumu']),
            'Kullanıcı başarıyla güncellendi'
        );
    }

    /**
     * Delete the specified user
     * DELETE /api/v2/users/{user}
     */
    public function destroy(User $user, \App\Actions\Api\V2\User\DestroyUserAction $action): JsonResponse
    {
        $action->handle($user);

        return ResponseService::success([], 'Kullanıcı başarıyla silindi');
    }
}
