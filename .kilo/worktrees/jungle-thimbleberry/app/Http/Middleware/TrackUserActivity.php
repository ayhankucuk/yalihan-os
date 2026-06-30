<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Logging\LogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Kullanıcı aktivitesini takip eder
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check()) {
            try {
                $user = Auth::user();
                if (! $user || ! $user->id) {
                    return $response;
                }

                $userId = $user->id;

                // ✅ STANDARDIZED: Using Eloquent instead of DB::table()
                User::where('id', $userId)->update([
                    'last_activity_at' => now(),
                ]);

                // Kullanıcının çevrimiçi olduğunu belirt (5 dakika için)
                $expiresAt = now()->addMinutes(5);
                Cache::put('user-online-'.$userId, true, $expiresAt);

            } catch (\Exception $e) {
                // ✅ STANDARDIZED: Using LogService
                LogService::error('Kullanıcı aktivitesi güncellenemedi', [
                    'user_id' => Auth::id(),
                    'url' => $request->url(),
                    'method' => $request->method(),
                ], $e);
            }
        }

        return $response;
    }
}
