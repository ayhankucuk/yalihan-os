<?php

namespace App\Http\Controllers\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Actions\Admin\User\StoreUserAction;
use App\Actions\Admin\User\UpdateUserAction;
use App\Actions\Admin\User\ToggleUserAktiflikAction;
use App\Actions\Admin\User\DeleteUserAction;

class UserController extends AdminController
{
    /**
     * Display a listing of users
     * Context7 compliant: uses 'aktiflik_durumu' instead of legacy field
     */
    public function index(Request $request)
    {
        // ✅ N+1 FIX: Eager loading with select optimization
        $query = User::select(['id', 'name', 'email', 'aktiflik_durumu', 'email_verified_at', 'created_at', 'updated_at', 'ulke_id'])
            ->with(['roles:id,name']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter (Spatie Permission)
        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Durum filtresi (Context7 compliance)
        if ($request->has('kullanici_durumu')) {
            $query->where('aktiflik_durumu', $request->boolean('kullanici_durumu'));
        } else {
            // Active users by default (Context7: aktiflik_durumu = true)
            $query->where('aktiflik_durumu', true);
        }

        // Sorting
        $sort = $request->get('sort');
        if ($sort === 'id_asc') {
            $query->orderBy('id', 'asc'); // context7-ignore
        } elseif ($sort === 'id_desc') {
            $query->orderBy('id', 'desc'); // context7-ignore
        } elseif ($sort === 'name_asc') {
            $query->orderBy('name', 'asc'); // context7-ignore
        } elseif ($sort === 'name_desc') {
            $query->orderBy('name', 'desc'); // context7-ignore
        } elseif ($sort === 'date_asc') {
            $query->orderBy('created_at', 'asc'); // context7-ignore
        } elseif ($sort === 'date_desc') {
            $query->orderBy('created_at', 'desc'); // context7-ignore
        } else {
            // Default sorting
            $query->orderBy('created_at', 'desc'); // context7-ignore
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telefon' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:superadmin,admin,danisman,editor,musteri',
            'aktiflik_durumu' => 'nullable|boolean',
            'email_verified' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        app(StoreUserAction::class)->handle($request->validated());

        return redirect()->route('admin.kullanicilar.index')
            ->with('success', 'Kullanıcı başarıyla oluşturuldu.');
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $kullanicilar)
    {
        // ✅ SAB: Load roles for dropdown
        $roles = \Spatie\Permission\Models\Role::all(['id', 'name']);

        return view('admin.users.edit', [
            'user' => $kullanicilar,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified user
     * ✅ SAB: Kullanıcı güncelleme - flash mesaj ve validation düzeltmeleri
     */
    public function update(Request $request, User $kullanicilar)
    {
        // ✅ SAB: Validation - aktiflik_durumu string olarak geliyor (0 veya 1)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$kullanicilar->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:superadmin,admin,danisman,editor,musteri', // ✅ SAB: Rol zorunlu
            'aktiflik_durumu' => 'nullable|in:0,1',
        ], [
            'role.required' => 'Kullanıcı rolü seçilmelidir.',
            'role.in' => 'Geçersiz rol seçildi. Lütfen geçerli bir rol seçin.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Lütfen form hatalarını düzeltin.'); // ✅ SAB: Error flash mesajı
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'aktiflik_durumu' => $request->has('kullanici_durumu') ? (int) $request->kullanici_durumu : ($kullanicilar->aktiflik_durumu ?? 1),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        try {
            $result = app(UpdateUserAction::class)->handle(
                $kullanicilar,
                $updateData,
                $request->input('role')
            );

            $successMessage = 'Kullanıcı başarıyla güncellendi.';
            if ($result['role_changed']) {
                $successMessage .= ' Rol: '.ucfirst($request->role);
            }

            return redirect()->route('admin.kullanicilar.edit', $kullanicilar->id)
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Kullanıcı güncelleme hatası', [
                'user_id' => $kullanicilar->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Kullanıcı güncellenirken bir hata oluştu: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified user from storage
     */
    public function destroy(User $kullanicilar)
    {
        app(DeleteUserAction::class)->handle($kullanicilar);

        return redirect()->route('admin.kullanicilar.index')
            ->with('success', 'Kullanıcı başarıyla silindi.');
    }

    /**
     * Toggle user aktiflik_durumu (Context7 compliant)
     */
    public function toggleAktiflikDurumu(User $kullanicilar)
    {
        app(ToggleUserAktiflikAction::class)->handle($kullanicilar);

        return response()->json([
            'success' => true,
            'aktiflik_durumu' => $kullanicilar->aktiflik_durumu,
            'message' => 'Kullanıcı aktiflik durumu güncellendi.',
        ]);
    }
}
