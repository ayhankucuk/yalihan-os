<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Giriş Yap - Yalıhan Emlak</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            {{-- Logo & Title --}}
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl">
                    <svg class="h-10 w-10 text-white transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white transition-colors duration-200 dark:text-slate-100">
                    Yalıhan Emlak
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 transition-colors duration-200">
                    Yönetim Paneli Girişi
                </p>
            </div>

            {{-- Login Form --}}
            <div class="bg-white dark:bg-slate-900 shadow-2xl rounded-2xl p-8 border border-gray-200 dark:border-slate-800 dark:border-slate-700"
                 x-data="{
                     showPassword: false,
                     loading: false,
                     error: '',
                     success: ''
                 }">

                {{-- Error Alert --}}
                @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-2 border-red-500 rounded-lg transition-all duration-200 animate-in fade-in slide-in-from-top-2">
                    <div class="flex items-center gap-2 text-red-800 dark:text-red-300">
                        <svg class="h-5 w-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">{{ $errors->first() }}</span>
                    </div>
                </div>
                @endif

                @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border-2 border-green-500 rounded-lg transition-all duration-200 animate-in fade-in slide-in-from-top-2">
                    <div class="flex items-center gap-2 text-green-800 dark:text-green-300">
                        <svg class="h-5 w-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">{{ session('success') }}</span>
                    </div>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}"
                      class="space-y-6"
                      @submit="loading = true"
                      x-on:submit="loading = true">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-extrabold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            E-posta Adresi
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </div>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                required
                                :readonly="loading"
                                value="{{ old('email') }}"
                                class="block w-full pl-10 pr-3 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white font-semibold placeholder-gray-600 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                placeholder="ornek@email.com">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-extrabold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Şifre
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                :readonly="loading"
                                class="block w-full pl-10 pr-10 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white font-semibold placeholder-gray-600 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                placeholder="••••••••">
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                :disabled="loading"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
                                <svg x-show="!showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Remember & Forgot --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                id="remember_me"
                                name="remember"
                                type="checkbox"
                                :disabled="loading"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <label for="remember_me" class="ml-2 block text-sm text-gray-700 dark:text-slate-200 cursor-pointer dark:text-slate-300">
                                Beni hatırla
                            </label>
                        </div>

                        {{-- Şifremi unuttum linki (route varsa göster) --}}
                        @php
                            $hasPasswordReset = false;
                            try {
                                $hasPasswordReset = \Illuminate\Support\Facades\Route::has('password.request');
                            } catch (\Exception $e) {
                                // Route bulunamazsa sessizce devam et
                            }
                        @endphp
                        @if ($hasPasswordReset)
                        <div class="text-sm">
                            <a href="{{ route('password.request') }}"
                               :class="loading ? 'pointer-events-none opacity-50' : ''"
                               class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-all duration-200">
                                Şifremi unuttum
                            </a>
                        </div>
                        @endif
                    </div>

                    {{-- Submit Button --}}
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        <svg x-show="loading" x-cloak class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-show="!loading">Giriş Yap</span>
                        <span x-show="loading" x-cloak>Giriş yapılıyor...</span>
                        <svg x-show="!loading" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </form>

                {{-- Demo Credentials --}}
                @if(app()->environment('local'))
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-slate-800 transition-colors duration-200 dark:border-slate-700">
                    <p class="text-xs text-center text-gray-500 dark:text-gray-400 mb-2 transition-colors duration-200">Demo Hesapları:</p>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2 bg-gray-50 dark:bg-slate-900 rounded text-gray-700 dark:text-slate-200 transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800 hover:shadow-md dark:text-slate-300">
                            <strong>Admin:</strong><br>
                            admin@example.com<br>
                            password
                        </div>
                        <div class="p-2 bg-gray-50 dark:bg-slate-900 rounded text-gray-700 dark:text-slate-200 transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800 hover:shadow-md dark:text-slate-300">
                            <strong>User:</strong><br>
                            user@example.com<br>
                            password
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <p class="text-center text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200">
                &copy; {{ date('Y') }} Yalıhan Emlak. Tüm hakları saklıdır.
            </p>
        </div>
    </div>
</body>
</html>
