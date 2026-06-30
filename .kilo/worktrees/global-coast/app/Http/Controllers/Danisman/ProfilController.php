<?php

namespace App\Http\Controllers\Danisman;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use App\Actions\Profile\UpdateProfileAction;
use App\Actions\Profile\ChangePasswordAction;

/**
 * Danışman Self-Service Profil Controller
 *
 * Context7 Compliance: Danışmanların kendi profillerini yönetebilmeleri
 * Sadece auth()->user() üzerinde işlem yapılır (güvenli)
 */
class ProfilController extends Controller
{
    /**
     * Profil düzenleme sayfası
     */
    public function edit()
    {
        $danisman = auth()->user();

        // Danışman rolü kontrolü
        if (!$danisman->hasRole('danisman')) {
            abort(403, 'Bu alan sadece danışmanlar içindir.');
        }

        return view('danisman.profil.edit', compact('danisman'));
    }

    /**
     * Profil güncelleme
     * Context7: Sadece izin verilen alanlar güncellenebilir
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        // Danışman rolü kontrolü
        if (!$user->hasRole('danisman')) {
            abort(403, 'Bu alan sadece danışmanlar içindir.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'office_phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'title' => 'nullable|string|max:100',
            'office_address' => 'nullable|string|max:500',
            'expertise_summary' => 'nullable|string|max:500',

            // Sosyal Medya
            'instagram_profile' => 'nullable|url|max:255',
            'linkedin_profile' => 'nullable|url|max:255',
            'facebook_profile' => 'nullable|url|max:255',
            'twitter_profile' => 'nullable|url|max:255',
            'youtube_channel' => 'nullable|url|max:255',
            'website' => 'nullable|url|max:255',

            // Profil Fotoğrafı
            'profile_photo' => 'nullable|image|max:2048|mimes:jpg,jpeg,png,webp',
        ]);

        // Profil fotoğrafı yükle
        if ($request->hasFile('profile_photo')) {
            // Eski fotoğrafı sil
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $validated['profile_photo_path'] = $path;
        }

        // Güncelle
        app(UpdateProfileAction::class)->handle($user, $validated);

        return redirect()->route('danisman.profil.edit')
            ->with('success', '✅ Profil bilgileriniz başarıyla güncellendi!');
    }

    /**
     * Şifre değiştirme
     * Context7: Güvenli şifre değiştirme (mevcut şifre kontrolüyle)
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        // Danışman rolü kontrolü
        if (!$user->hasRole('danisman')) {
            abort(403, 'Bu alan sadece danışmanlar içindir.');
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ], [
            'current_password.required' => 'Mevcut şifrenizi girmelisiniz.',
            'password.required' => 'Yeni şifrenizi girmelisiniz.',
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
        ]);

        // Mevcut şifre kontrolü
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mevcut şifreniz hatalı.']);
        }

        // Yeni şifre eski şifre ile aynı olamaz
        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Yeni şifre eski şifrenizle aynı olamaz.']);
        }

        // Şifreyi güncelle
        app(ChangePasswordAction::class)->handle($user, $request->password);

        return redirect()->route('danisman.profil.edit')
            ->with('success', '🔐 Şifreniz başarıyla değiştirildi!');
    }
}
