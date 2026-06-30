<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\CalendarIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    public function connect()
    {
        $clientId = config('services.google_oauth.client_id');
        $redirect = config('services.google_oauth.redirect_uri');
        $scope = urlencode('https://www.googleapis.com/auth/calendar.events');
        $state = base64_encode(json_encode(['uid' => Auth::id()]));
        $url = "https://accounts.google.com/o/oauth2/v2/auth?client_id={$clientId}&redirect_uri=".urlencode($redirect)."&response_type=code&scope={$scope}&access_type=offline&prompt=consent&state={$state}";

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        try {
            $code = $request->get('code');
            $clientId = config('services.google_oauth.client_id');
            $clientSecret = config('services.google_oauth.client_secret');
            $redirect = config('services.google_oauth.redirect_uri');

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirect,
                'grant_type' => 'authorization_code',
            ]);

            if (! $response->ok()) {
                return redirect()->route('admin.yazlik-kiralama.takvim.index')->with('error', 'Google token alınamadı');
            }

            $data = $response->json();
            $integration = CalendarIntegration::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'provider' => 'google',
                ],
                [
                    'access_token' => $data['access_token'] ?? null,
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires_at' => now()->addSeconds((int) ($data['expires_in'] ?? 3600)),
                    'meta' => ['token_type' => $data['token_type'] ?? 'Bearer'],
                ]
            );

            return redirect()->route('admin.yazlik-kiralama.takvim.index')->with('success', 'Google Calendar bağlandı');
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback error', ['error' => $e->getMessage()]);

            return redirect()->route('admin.yazlik-kiralama.takvim.index')->with('error', 'Google OAuth hatası');
        }
    }
}
