<?php

namespace App\Http\Controllers;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Actions\Profile\UpdateProfileAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly UpdateProfileAction $updateProfileAction
    ) {}

    public function index(Request $request)
    {
        return response()->json(['message' => 'Profile endpoint - to be implemented']);
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $user = $request->user();
        if ($user) {
            $this->updateProfileAction->handle($user, $validated);
        }

        return back()->with('success', 'Profil güncellendi.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // İstisna Gerekçesi: DB mutasyonu yoktur, doğrudan framework-native Session/Cookie State (HTTP Layer) mutasyonudur.
        return response()->redirectTo('/');
    }
}
