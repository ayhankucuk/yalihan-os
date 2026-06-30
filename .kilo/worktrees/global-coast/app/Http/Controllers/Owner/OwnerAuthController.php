<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerLoginToken;
use App\Models\User;
use App\Services\Logging\LogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OwnerLoginLinkMail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Application\Shared\Services\TenantContextResolver;

/**
 * OwnerAuthController
 *
 * Mülk sahibi portal girişini yönetir.
 * Akış: email gir → OTP linki al → token ile giriş yap → /owner/dashboard
 *
 * Şifre zorunluluğu yoktur; token 15 dakika geçerlidir.
 * Token SHA-256 hash olarak saklanır, plain-text asla.
 *
 * @sab-ignore-thin (Auth controller — tek sorumluluk)
 * SAB v6.1.2 — Owner Portal sprint.
 */
class OwnerAuthController extends Controller
{
    public function __construct(private TenantContextResolver $tenantResolver)
    {
    }

    // OTP geçerlilik süresi (dakika)
    private const TOKEN_TTL_MINUTES = 15;

    // -------------------------------------------------------
    // Login Formu
    // -------------------------------------------------------

    /**
     * Giriş sayfasını göster
     */
    public function showLoginForm(): View
    {
        return view('owner.auth.login');
    }

    /**
     * Magic-link / OTP gönder
     *
     * Kullanıcı email'ini gönderir; 'owner' rolüne sahipse
     * magic-link emaili iletilir.
     */
    public function sendLoginLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])
                    ->whereHas('roles', fn ($q) => $q->where('name', 'owner'))
                    ->first();

        // Kullanıcı yoksa bile aynı mesajı ver (güvenlik: email enumeration engeli)
        if (! $user) {
            LogService::warning('OwnerAuth: Bilinmeyen email ile giriş denemesi', [
                'email' => $validated['email'],
                'ip'    => $request->ip(),
            ]);

            return back()->with(
                'bilgi',
                'Eğer bu email ile kayıtlı bir mülk sahibi hesabı varsa, giriş linki gönderildi.'
            );
        }

        // Eski kullanılmamış token'ları iptal et
        OwnerLoginToken::where('user_id', $user->id)
                        ->where('kullanildi', false)
                        ->update(['kullanildi' => true]);

        // Yeni token üret
        $plainToken = Str::random(64);
        $tokenHash  = hash('sha256', $plainToken);

        OwnerLoginToken::create([
            'tenant_id'        => $this->tenantResolver->resolve()->tenantId,
            'user_id'          => $user->id,
            'token_hash'       => $tokenHash,
            'giris_kanali'     => 'email',
            'gecerlilik_bitis' => now()->addMinutes(self::TOKEN_TTL_MINUTES),
            'kullanildi'       => false,
        ]);

        Mail::to($user)->send(new OwnerLoginLinkMail($plainToken, $user));

        LogService::info('OwnerAuth: Login linki gönderildi', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'kanal'   => 'email',
        ]);

        return back()->with(
            'basarili',
            'Giriş linki email adresinize gönderildi. ' . self::TOKEN_TTL_MINUTES . ' dakika içinde kullanın.'
        );
    }

    // -------------------------------------------------------
    // Token ile Giriş
    // -------------------------------------------------------

    /**
     * Magic-link token'ını doğrula ve oturum aç
     *
     * GET /owner/auth/verify?token=...
     */
    public function verifyToken(Request $request): RedirectResponse
    {
        $plainToken = $request->query('token');

        if (! $plainToken) {
            return redirect()->route('owner.login')
                ->withErrors(['token' => 'Geçersiz giriş linki.']);
        }

        $tokenHash = hash('sha256', $plainToken);

        $record = OwnerLoginToken::gecerli()
                    ->where('token_hash', $tokenHash)
                    ->first();

        if (! $record) {
            LogService::warning('OwnerAuth: Geçersiz veya süresi dolmuş token', [
                'ip' => $request->ip(),
            ]);

            return redirect()->route('owner.login')
                ->withErrors(['token' => 'Giriş linki geçersiz veya süresi dolmuş. Lütfen yeni bir link isteyin.']);
        }

        // Token kullanıldı olarak işaretle
        $record->update([
            'kullanildi'  => true,
            'kullanilan_ip' => $request->ip(),
        ]);

        // Kullanıcıyı oturum aç
        Auth::loginUsingId($record->user_id, remember: false);

        LogService::info('OwnerAuth: Başarılı giriş', [
            'user_id' => $record->user_id,
            'ip'      => $request->ip(),
        ]);

        return redirect()->intended(route('owner.dashboard'))
            ->with('basarili', 'Hoş geldiniz!');
    }

    // -------------------------------------------------------
    // Çıkış
    // -------------------------------------------------------

    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        LogService::info('OwnerAuth: Çıkış yapıldı', ['user_id' => $userId]);

        return redirect()->route('owner.login')
            ->with('bilgi', 'Oturumunuz güvenli şekilde kapatıldı.');
    }
}
