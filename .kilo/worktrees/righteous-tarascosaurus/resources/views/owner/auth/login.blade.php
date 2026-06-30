<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mülk Sahibi Girişi — Yalıhan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-50 dark:bg-slate-900">

<div class="w-full max-w-md px-4">

    {{-- Kart --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm dark:border-slate-700 dark:bg-slate-800">

        {{-- Logo --}}
        <div class="mb-6 flex flex-col items-center gap-2">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                <svg class="h-7 w-7 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 9.75L12 3l9 6.75V21a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 21V12h6v9"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">Mülk Sahibi Paneli</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">Email adresinize giriş linki göndereceğiz</p>
        </div>

        {{-- Flash --}}
        @if(session('basarili'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ session('basarili') }}
            </div>
        @endif
        @if(session('bilgi'))
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                {{ session('bilgi') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                @foreach($errors->all() as $hata)
                    <p>{{ $hata }}</p>
                @endforeach
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('owner.auth.send') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                    Email Adresi
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="ornek@mail.com"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm
                           focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-slate-400
                           dark:focus:border-blue-400 dark:focus:ring-blue-800
                           @error('email') border-red-400 @enderror"
                >
                @error('email')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                       hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300
                       dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                Giriş Linki Gönder
            </button>
        </form>

        {{-- Alt not --}}
        <p class="mt-5 text-center text-xs text-gray-400 dark:text-slate-500">
            Link 15 dakika geçerlidir. Sorun yaşarsanız danışmanınızla iletişime geçin.
        </p>

    </div>

    {{-- Danışman linki --}}
    <p class="mt-4 text-center text-xs text-gray-400 dark:text-slate-500">
        <a href="/" class="hover:text-blue-600 dark:hover:text-blue-400">← Ana siteye dön</a>
    </p>

</div>
</body>
</html>
