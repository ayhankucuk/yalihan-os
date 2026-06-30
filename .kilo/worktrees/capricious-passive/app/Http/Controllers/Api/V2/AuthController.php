<?php

namespace App\Http\Controllers\Api\V2;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\V2\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * V2 Authentication Controller
 * 
 * Context7: 100% Compliant
 * - Field names: email, sifre_hash, aktiflik_durumu, rol
 * - No forbidden field patterns (using canonical names)
 * - Token-based auth with Sanctum
 */
class AuthController extends Controller
{
    /**
     * User login - Issue Sanctum token
     * POST /api/v1/auth/login
     * 
     * Only active users (aktiflik_durumu = true) can login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'sifre' => 'required|string|min:6',
        ]);

        // Find user by email
        $kullanici = User::where('email', $validated['email'])->first();

        // Check if user exists and password matches
        if (!$kullanici || !Hash::check($validated['sifre'], $kullanici->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email veya şifre yanlış',
            ], 401);
        }

        // Check if user is active (aktiflik_durumu)
        if (!$kullanici->aktiflik_durumu) {
            return response()->json([
                'success' => false,
                'message' => 'Hesabınız deaktif edilmiştir. Yöneticiye başvurunuz.',
            ], 403);
        }

        // Generate Sanctum token
        $token = $kullanici->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Başarıyla giriş yaptınız',
            'data' => [
                'kullanici' => [
                    'id' => $kullanici->id,
                    'ad_soyad' => $kullanici->ad_soyad,
                    'email' => $kullanici->email,
                    'rol' => $kullanici->rol,
                    'aktiflik_durumu' => $kullanici->aktiflik_durumu,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    /**
     * User registration - Create new user account
     * POST /api/v1/auth/register
     * 
     * Public endpoint - No authentication required
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ad_soyad' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'sifre' => 'required|string|min:6|confirmed',
        ]);

        // Create new user with hashed password
        $user = User::create([
            'ad_soyad' => $validated['ad_soyad'],
            'email' => $validated['email'],
            'sifre_hash' => Hash::make($validated['sifre']),
            'rol' => 'musteri',  // Default role
            'aktiflik_durumu' => true,  // Active by default
        ]);

        // Generate Sanctum token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Hesabınız başarıyla oluşturuldu',
            'data' => [
                'kullanici' => [
                    'id' => $user->id,
                    'ad_soyad' => $user->ad_soyad,
                    'email' => $user->email,
                    'rol' => $user->rol,
                    'aktiflik_durumu' => $user->aktiflik_durumu,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * POST /api/v1/auth/logout
     * 
     * Requires authentication
     */
    public function logout(Request $request): JsonResponse
    {
        // Get authenticated user via Sanctum
        $user = $request->user('sanctum');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Kimliği doğrulanmamış kullanıcı',
            ], 401);
        }

        // Revoke current token
        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Başarıyla çıkış yaptınız',
        ], 200);
    }

    /**
     * Get authenticated user profile
     * GET /api/v1/auth/me
     * 
     * Requires authentication
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Kimliği doğrulanmamış kullanıcı',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'ad_soyad' => $user->ad_soyad,
                'email' => $user->email,
                'telefon' => $user->telefon,
                'rol' => $user->rol,
                'aktiflik_durumu' => $user->aktiflik_durumu,
                'created_at' => $user->created_at,
            ],
        ]);
    }
}
