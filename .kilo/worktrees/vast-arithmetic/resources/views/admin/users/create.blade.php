@extends('admin.layouts.admin')

@section('title', 'Yeni Kullanıcı Ekle')

@section('content')
    {{-- ✅ SAB: Flash Messages --}}
    @if (session('success'))
        <div
            class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="currentColor"
                viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            <p class="text-green-800 dark:text-green-200 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div
            class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-2 border-red-200 dark:border-red-800">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
            <p class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</p>
        </div>
    @endif

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-purple-50 dark:from-gray-900 dark:to-purple-900">
        {{-- Header --}}
        <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 mb-8 p-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                        👥 Yeni Kullanıcı Ekle
                    </h1>
                    <p class="mt-3 text-lg text-gray-600 dark:text-gray-400">
                        Sisteme yeni kullanıcı ekleyin
                    </p>
                </div>
                <a href="{{ route('admin.kullanicilar.index') }}"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                    ← Geri Dön
                </a>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('admin.kullanicilar.store') }}" method="POST" class="space-y-6"
            onsubmit="const btn = document.getElementById('user-submit-btn'); const icon = document.getElementById('user-submit-icon'); const text = document.getElementById('user-submit-text'); const spinner = document.getElementById('user-submit-spinner'); if(btn && icon && text && spinner) { btn.disabled = true; icon.classList.add('hidden'); spinner.classList.remove('hidden'); text.textContent = 'Kaydediliyor...'; }">
            @csrf

            {{-- Temel Bilgiler --}}
            <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 p-8">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200 mb-6 flex items-center">
                    <span
                        class="bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300 rounded-full w-10 h-10 flex items-center justify-center text-lg font-bold mr-3">1</span>
                    👤 Temel Bilgiler
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Ad Soyad --}}
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Ad Soyad *
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white @error('name') border-red-500 @enderror dark:text-slate-100"
                            placeholder="Kullanıcı adı soyadı">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            E-posta *
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white @error('email') border-red-500 @enderror dark:text-slate-100"
                            placeholder="ornek@yalihanemlak.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Telefon --}}
                    <div>
                        <label for="telefon" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Telefon
                        </label>
                        <input type="text" name="telefon" id="telefon" value="{{ old('telefon') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white @error('telefon') border-red-500 @enderror dark:text-slate-100"
                            placeholder="0532 123 45 67">
                        @error('telefon')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Şifre --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Şifre *
                        </label>
                        <input type="password" name="password" id="password" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white @error('password') border-red-500 @enderror dark:text-slate-100"
                            placeholder="En az 8 karakter">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Şifre Tekrar --}}
                    <div>
                        <label for="password_confirmation"
                            class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Şifre Tekrar *
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100"
                            placeholder="Şifreyi tekrar girin">
                    </div>
                </div>
            </div>

            {{-- Rol ve Yetki Bilgileri --}}
            <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 p-8">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200 mb-6 flex items-center">
                    <span
                        class="bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-full w-10 h-10 flex items-center justify-center text-lg font-bold mr-3">2</span>
                    🔐 Rol ve Yetki Bilgileri
                </h2>

                <div class="space-y-6">
                    {{-- Rol Seçimi --}}
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Rol *
                        </label>
                        @php
                            $roles = \Spatie\Permission\Models\Role::all(['id', 'name']);
                            $roleDescriptions = [
                                'superadmin' => [
                                    'icon' => '👑',
                                    'name' => 'Super Admin',
                                    'desc' => 'Tüm yetkilere sahip süper kullanıcı',
                                    'color' => 'purple',
                                ],
                                'admin' => [
                                    'icon' => '👑',
                                    'name' => 'Admin',
                                    'desc' => 'Tüm yetkilere sahip yönetici',
                                    'color' => 'purple',
                                ],
                                'danisman' => [
                                    'icon' => '👤',
                                    'name' => 'Danışman',
                                    'desc' => 'İlan ekleme, düzenleme ve müşteri yönetimi',
                                    'color' => 'blue',
                                ],
                                'editor' => [
                                    'icon' => '✏️',
                                    'name' => 'Editör',
                                    'desc' => 'İçerik düzenleme ve yayınlama',
                                    'color' => 'green',
                                ],
                                'musteri' => [
                                    'icon' => '👁️',
                                    'name' => 'Müşteri',
                                    'desc' => 'Sadece görüntüleme yetkisi',
                                    'color' => 'gray',
                                ],
                            ];
                        @endphp
                        <select style="color-scheme: light dark;" name="role" id="role" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white @error('role') border-red-500 @enderror transition-all duration-200 dark:text-slate-100">
                            <option value="">-- Rol Seçiniz --</option>
                            @foreach ($roles as $role)
                                @php
                                    $roleInfo = $roleDescriptions[$role->name] ?? [
                                        'icon' => '👤',
                                        'name' => ucfirst($role->name),
                                        'desc' => 'Kullanıcı rolü',
                                        'color' => 'gray',
                                    ];
                                @endphp
                                <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                    {{ $roleInfo['icon'] }} {{ $roleInfo['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Rol Açıklamaları --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($roles as $role)
                            @php
                                $roleInfo = $roleDescriptions[$role->name] ?? [
                                    'icon' => '👤',
                                    'name' => ucfirst($role->name),
                                    'desc' => 'Kullanıcı rolü',
                                    'color' => 'gray',
                                ];
                                $colorClasses = [
                                    'purple' =>
                                        'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-700 text-purple-700 dark:text-purple-300 text-purple-600 dark:text-purple-400',
                                    'blue' =>
                                        'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300 text-blue-600 dark:text-blue-400',
                                    'green' =>
                                        'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 text-green-600 dark:text-green-400',
                                    'gray' =>
                                        'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white text-gray-600 dark:text-gray-400',
                                ];
                                $classes = $colorClasses[$roleInfo['color']] ?? $colorClasses['gray'];
                                $parts = explode(' ', $classes);
                            @endphp
                            <div
                                class="{{ $parts[0] }} $parts[1] rounded-lg p-4 border $parts[2] $parts[3]">
                                <h3 class="font-semibold {{ $parts[4] }} $parts[5] mb-2">
                                    {{ $roleInfo['icon'] }} {{ $roleInfo['name'] }}</h3>
                                <p class="text-sm {{ $parts[6] }} $parts[7]">{{ $roleInfo['desc'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Durum ve Ayarlar --}}
            <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 p-8">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200 mb-6 flex items-center">
                    <span
                        class="bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 rounded-full w-10 h-10 flex items-center justify-center text-lg font-bold mr-3">3</span>
                    ⚙️ Durum ve Ayarlar
                </h2>

                <div class="space-y-6">
                    {{-- Aktif Durum --}}
                    <div
                        class="flex items-center justify-between bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                        <div>
                            <label for="aktiflik_durumu" class="font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                Kullanıcı Aktif
                            </label>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Kullanıcı sisteme giriş yapabilir
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="aktiflik_durumu" id="aktiflik_durumu" value="1"
                                {{ old('aktiflik_durumu', true) ? 'checked' : '' }} class="sr-only peer">
                            <div
                                class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-900 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-green-600">
                            </div>
                        </label>
                    </div>

                    {{-- Email Doğrulanmış --}}
                    <div
                        class="flex items-center justify-between bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
                        <div>
                            <label for="email_verified" class="font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                E-posta Doğrulanmış
                            </label>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                E-posta adresi doğrulanmış olarak işaretle
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="email_verified" id="email_verified" value="1"
                                {{ old('email_verified', true) ? 'checked' : '' }} class="sr-only peer">
                            <div
                                class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-slate-900 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div
                class="flex items-center justify-between bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 p-6">
                <a href="{{ route('admin.kullanicilar.index') }}"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                    ← İptal
                </a>
                <button type="submit" id="user-submit-btn"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 dark:shadow-none">
                    <svg id="user-submit-icon" class="fas fa-save mr-2"></svg>
                    <span id="user-submit-text">Kaydet</span>
                    <svg id="user-submit-spinner" class="hidden w-4 h-4 mr-2 animate-spin" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
@endsection
