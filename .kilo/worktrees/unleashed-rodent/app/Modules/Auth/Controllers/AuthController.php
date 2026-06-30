<?php

namespace App\Modules\Auth\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Modules\BaseModule\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    /**
     * Login sayfasını göster
     */
    public function showLoginForm()
    {
        return view('login'); // Modern login page (resources/views/login.blade.php)
    }

    /**
     * Giriş işlemi - Standart Laravel authentication
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        // DEBUG: Log login attempt
        Log::info('AuthController login called', [
            'email' => $request->input('email'),
            'session_keys' => array_keys($request->session()->all()),
            'auth_user_before' => Auth::check() ? Auth::user()->email : 'not authenticated',
        ]);

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Clear any intended URL that might cause POST redirects
            $request->session()->forget('url.intended');

            // DEBUG: Log successful authentication
            Log::info('Authentication successful', [
                'user_id' => Auth::user()->id,
                'email' => Auth::user()->email,
                'session_id' => $request->session()->getId(),
            ]);

            // İstisna Gerekçesi: DB mutasyonu yoktur, doğrudan framework-native Session/Cookie State (HTTP Layer) mutasyonudur.
            return response()->redirectToRoute('admin.dashboard.index');
        }

        // Authentication failed
        Log::info('Authentication failed for email: '.$request->input('email'));

        // İstisna Gerekçesi: Auth Login validation fail durumu, state bozmaz.
        return back()->withErrors(['email' => 'E-posta veya şifre hatalı.']);
    }

    /**
     * Kullanıcı çıkış işlemi
     */
    public function logout()
    {
        Auth::logout();

        // İstisna Gerekçesi: DB mutasyonu yoktur, doğrudan framework-native Session/Cookie State (HTTP Layer) mutasyonudur.
        return response()->redirectToRoute('login');
    }

    /**
     * Kullanıcı listesi (Admin)
     */
    public function index(Request $request)
    {
        // Debug logging
        Log::info('AuthController@index called', [
            'controller' => static::class,
            'method' => __FUNCTION__,
            'view_path' => 'admin.kullanicilar.index',
        ]);

        // Query with search and filters
        $query = User::with('role');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->where('role_id', $request->get('role'));
        }

        // Aktiflik durumu filter (Context7: aktiflik_durumu field)
        if ($request->filled('aktiflik')) {
            $query->where('aktiflik_durumu', $request->get('aktiflik') == '1');
        }

        // Get paginated results
        $users = $query->paginate(10)->withQueryString();
        
        // Try to load roles (may not exist in database)
        try {
            $roles = Role::all();
        } catch (\Exception $e) {
            $roles = collect([]);
            Log::warning('Could not load roles: '.$e->getMessage());
        }

        // Debug variables
        Log::info('Variables prepared for view', [
            'users_count' => $users->count(),
            'roles_count' => $roles->count(),
            'users_class' => get_class($users),
            'roles_class' => get_class($roles),
        ]);

        return view('admin.kullanicilar.index', compact('users', 'roles'));
    }

    /**
     * Yeni kullanıcı oluşturma formu (Admin)
     */
    public function create()
    {
        try {
            $roles = Role::all();
        } catch (\Exception $e) {
            $roles = collect([]);
        }

        return view('auth::users.create', compact('roles'));
    }

    /**
     * Yeni kullanıcı ekleme (Admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'yayin_durumu' => true,
        ]);

        return redirect()->route('admin.kullanicilar.index')->with('success', 'Kullanıcı başarıyla oluşturuldu');
    }

    /**
     * Kullanıcı güncelleme işlemi
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
        ]);

        return response()->json(['success' => true, 'message' => 'Kullanıcı başarıyla güncellendi.']);
    }

    /**
     * Kullanıcı bilgilerini güncelleme
     */
    public function update(Request $request, $id)
    {
        // Yetki kontrolü: Admin/Superadmin herkesi güncelleyebilir, danışman sadece kendi profilini
        $currentUser = Auth::user();
        if (! $this->checkAdminPermission($currentUser) && $currentUser->id != $id) {
            abort(403, 'Bu kullanıcıyı güncelleme yetkiniz yok.');
        }

        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$id,
            'role_id' => 'required|exists:roles,id',
            'yayin_durumu' => 'boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'aktiflik_durumu' => $request->has('aktiflik') ? true : false,
        ];

        // Sadece admin/superadmin rol değiştirebilir
        if ($this->checkAdminPermission($currentUser)) {
            $data['role_id'] = $request->role_id;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->fill($data);
        $user->save();

        return redirect()->route('admin.kullanicilar.index')
            ->with('success', 'Kullanıcı başarıyla güncellendi.');
    }

    /**
     * Kullanıcı detaylarını göster (Admin)
     */
    public function show($id)
    {
        $user = User::with('role')->findOrFail($id);

        return view('auth::users.show', compact('user'));
    }

    /**
     * Kullanıcı düzenleme formu (Admin)
     */
    public function edit($id)
    {
        $currentUser = Auth::user();
        $user = User::findOrFail($id);

        // Yetki kontrolü: Admin/Superadmin herkesi düzenleyebilir, danışman sadece kendi profilini
        if (! $this->checkAdminPermission($currentUser) && $currentUser->id != $id) {
            abort(403, 'Bu kullanıcıyı düzenleme yetkiniz yok.');
        }

        try {
            $roles = Role::all();
        } catch (\Exception $e) {
            $roles = collect([]);
        }

        return view('admin.kullanicilar.edit', compact('user', 'roles'));
    }

    /**
     * Kullanıcı silme işlemi (Admin)
     */
    public function destroy($id)
    {
        // Yetki kontrolü
        $currentUser = Auth::user();
        if (! $this->checkAdminPermission($currentUser)) {
            abort(403, 'Bu işlemi yapmaya yetkiniz yok.');
        }

        $user = User::findOrFail($id);

        // Kendi hesabını silmeyi engelle
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Kendi hesabınızı silemezsiniz.');
        }

        $user->delete();

        return redirect()->route('admin.kullanicilar.index')
            ->with('success', 'Kullanıcı başarıyla silindi.');
    }

    /**
     * Kullanıcının admin yetkisine sahip olup olmadığını kontrol eder
     *
     * @param  \App\Modules\Auth\Models\User  $user
     * @return bool
     */
    private function checkAdminPermission($user)
    {
        // Kullanıcının rolü varsa ve rolü admin veya superadmin ise
        if ($user && $user->role) {
            return in_array($user->role->name, ['admin', 'superadmin']);
        }

        return false;
    }
}
