@extends('admin.layouts.admin')

@php
    use Illuminate\Support\Facades\Auth;
@endphp

@section('title', 'Kullanıcı Düzenle')

@section('content')
    {{-- ✅ SAB: Flash Messages --}}
    @if (session('success'))
        <div class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800"
            x-data="{ show: true }" x-show="show" x-transition>
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="currentColor"
                viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            <p class="text-green-800 dark:text-green-200 font-medium">{{ session('success') }}</p>
            <button @click="show = false"
                class="ml-auto text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-2 border-red-200 dark:border-red-800"
            x-data="{ show: true }" x-show="show" x-transition>
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor"
                viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
            <p class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</p>
            <button @click="show = false"
                class="ml-auto text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- ✅ SAB: Validation Errors Summary --}}
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-2 border-red-200 dark:border-red-800"
            x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
                <div class="flex-1">
                    <h3 class="text-red-800 dark:text-red-200 font-semibold mb-2">Lütfen form hatalarını düzeltin:</h3>
                    <ul class="list-disc list-inside space-y-1 text-sm text-red-700 dark:text-red-300">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false"
                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <div class="content-header mb-8">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-200 flex items-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        Kullanıcı Düzenle
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        <strong>{{ $user->name }}</strong> kullanıcısının bilgilerini güncelleyin
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.kullanicilar.show', $user->id) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white font-medium rounded-lg shadow-sm hover:shadow-md focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:shadow-none dark:text-slate-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Görüntüle
                    </a>
                    <a href="{{ route('admin.kullanicilar.index') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white font-medium rounded-lg shadow-sm hover:shadow-md focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:shadow-none dark:text-slate-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kullanıcılara Geri Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.kullanicilar.update', $user->id) }}" class="space-y-8"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Temel Bilgiler -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 shadow-sm dark:shadow-none">
            <div class="p-6">
                <h2 class="text-xl font-bold text-blue-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    👤 Temel Bilgiler
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Ad -->
                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                            Ad Soyad <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                            required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- E-posta -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                            E-posta Adresi <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent invalid:border-red-500 invalid:ring-red-500 transition-colors duration-200"
                            required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Şifre -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                            Şifre (Değiştirmek istemiyorsanız boş bırakın)
                        </label>
                        <input type="password" name="password" id="password" autocomplete="new-password"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                            placeholder="Yeni şifre girin">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Şifre Tekrar -->
                    <div class="space-y-2">
                        <label for="password_confirmation"
                            class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                            Şifre Tekrar
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            autocomplete="new-password"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                            placeholder="Yeni şifreyi tekrar girin">
                    </div>
                </div>
            </div>
        </div>

        <!-- Kullanıcı Rolü ve Durumu -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200 shadow-sm dark:shadow-none">
            <div class="p-6">
                <h2 class="text-xl font-bold text-green-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    🔐 Rol ve Durum
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Rol (Sadece Admin ve SuperAdmin için) -->
                    @if (Auth::user() && Auth::user()->hasRole(['admin', 'superadmin']))
                        <div class="space-y-2">
                            <label for="role" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                Kullanıcı Rolü <span class="text-red-500">*</span>
                            </label>
                            @php
                                $roleBorderClass = $errors->has('role')
                                    ? 'border-2 border-red-500 dark:border-red-500'
                                    : 'border border-gray-300 dark:border-gray-600';
                            @endphp
                            <select style="color-scheme: light dark;" name="role" id="role" required
                                class="w-full px-4 py-2.5 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer transition-colors duration-200 {{ $roleBorderClass }}">
                                <option value="">-- Rol Seçiniz --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}"
                                        {{ old('role', $user->getRoleNames()->first()) == $role->name ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}</option>
                                @endforeach
                            </select>
                            @error('role')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            @if (!$user->getRoleNames()->first())
                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                    ⚠️ Bu kullanıcının henüz rolü yok. Lütfen bir rol seçin.
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                Mevcut Rolünüz
                            </label>
                            <div
                                class="px-4 py-2.5 bg-gray-100 dark:bg-slate-900 rounded-lg text-gray-900 dark:text-white font-medium dark:text-slate-100">
                                {{ $user->getRoleNames()->first() ?? 'Rol atanmamış' }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Rolünüzü değiştirmek için yöneticinize başvurun.</p>
                            <!-- Hidden field to maintain current role -->
                            <input type="hidden" name="role" value="{{ $user->getRoleNames()->first() }}">
                        </div>
                    @endif

                    <!-- Durum -->
                    <div class="space-y-2">
                        <label style="color-scheme: light dark;" for="aktiflik_durumu" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Durum <span class="text-red-500">*</span>
                        </label>
                        @php
                            $userAktif = $user->aktiflik_durumu ?? 1;
                        @endphp
                        <select style="color-scheme: light dark;" name="aktiflik_durumu" id="aktiflik_durumu"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer transition-colors duration-200">
                            <option value="1" {{ $userAktif == 1 ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ $userAktif == 0 ? 'selected' : '' }}>Pasif</option>
                        </select>
                        @error('aktiflik_durumu')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Profil Fotoğrafı -->
        <div class="bg-gradient-to-r from-purple-50 to-violet-50 rounded-xl border border-purple-200 shadow-sm dark:shadow-none">
            <div class="p-6">
                <h2 class="text-xl font-bold text-purple-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    📸 Profil Fotoğrafı
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Mevcut Fotoğraf -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Mevcut Fotoğraf</label>
                        <div class="flex items-center space-x-4">
                            @if ($user->profile_photo_path)
                                <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="{{ $user->name }}"
                                    class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 dark:border-slate-700">
                            @else
                                <div
                                    class="w-20 h-20 bg-gradient-to-r from-indigo-400 to-indigo-600 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-2xl">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                </div>
                            @endif
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                @if ($user->profile_photo_path)
                                    <p>Mevcut fotoğraf yüklü</p>
                                @else
                                    <p>Fotoğraf yüklenmemiş</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Yeni Fotoğraf -->
                    <div class="space-y-2">
                        <label for="profile_photo" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Yeni
                            Fotoğraf</label>
                        <input type="file" name="profile_photo" id="profile_photo"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer transition-colors duration-200"
                            accept="image/*">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PNG, JPG, JPEG (Max: 2MB)</p>
                        @error('profile_photo')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Ek Bilgiler -->
        <div class="bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl border border-orange-200 shadow-sm dark:shadow-none">
            <div class="p-6">
                <h2 class="text-xl font-bold text-orange-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    📋 Ek Bilgiler
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Unvan -->
                    <div class="space-y-2">
                        <label for="title"
                            class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Unvan</label>
                        <input type="text" name="title" id="title"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                            value="{{ old('title', $user->title) }}" placeholder="Örn: Satış Müdürü">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Telefon -->
                    <div class="space-y-2">
                        <label for="phone_number" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Telefon
                            Numarası</label>
                        <input type="tel" name="phone_number" id="phone_number"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                            value="{{ old('phone_number', $user->phone_number) }}" placeholder="0532 123 45 67">
                        @error('phone_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Adres -->
                    <div class="space-y-2 md:col-span-2">
                        <label for="address"
                            class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Adres</label>
                        <textarea name="address" id="address" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none transition-colors duration-200"
                            placeholder="Kullanıcı adresi...">{{ old('address', $user->address) }}</textarea>
                        @error('address')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notlar -->
                    <div class="space-y-2 md:col-span-2">
                        <label for="notes"
                            class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Notlar</label>
                        <textarea name="notes" id="notes" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none transition-colors duration-200"
                            placeholder="Kullanıcı hakkında notlar...">{{ old('notes', $user->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Butonları -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.kullanicilar.index') }}"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-gray-50 dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white font-medium rounded-lg shadow-sm hover:shadow-md focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:shadow-none dark:text-slate-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                İptal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-lg shadow-sm hover:shadow-md focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 dark:shadow-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Kullanıcıyı Güncelle
            </button>
        </div>
    </form>
@endsection

@push('styles')
@endpush

@push('scripts')
    <script>
        // ✅ SAB: Flash message support ve validation error handling
        document.addEventListener('DOMContentLoaded', function() {
            // Flash mesajlarını otomatik kapat (5 saniye sonra)
            setTimeout(function() {
                const successMessages = document.querySelectorAll('[x-data*="show"]');
                successMessages.forEach(function(msg) {
                    if (msg.querySelector('.text-green-800, .text-green-200')) {
                        msg.style.transition = 'opacity 0.5s';
                        msg.style.opacity = '0';
                        setTimeout(function() {
                            msg.remove();
                        }, 500);
                    }
                });
            }, 5000);

            // Validation hatalarını göster
            @if ($errors->any())
                console.log('Validation errors:', @json($errors->all()));
            @endif

            // Flash mesajları kontrol et
            @if (session('success'))
                console.log('Success message:', '{{ session('success') }}');
            @endif

            @if (session('error'))
                console.log('Error message:', '{{ session('error') }}');
            @endif
        });
    </script>
@endpush
