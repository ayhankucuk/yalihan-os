<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mülk Sahibi Paneli') — Yalıhan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-300"
      x-data="{
          darkMode: localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
          mobileMenu: false
      }"
      x-init="
          $watch('darkMode', val => {
              localStorage.setItem('darkMode', val);
              val ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark');
          });
          darkMode ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark');
      ">

    {{-- ── Üst Bar ── --}}
    <header class="border-b border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">

            {{-- Logo --}}
            <a href="{{ route('owner.dashboard') }}" class="flex items-center gap-2 text-lg font-bold text-blue-700 dark:text-blue-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 9.75L12 3l9 6.75V21a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 21V12h6v9"/>
                </svg>
                Mülk Sahibi Paneli
            </a>

            {{-- Nav --}}
            <nav class="hidden items-center gap-5 text-sm font-medium sm:flex">
                <a href="{{ route('owner.dashboard') }}"
                   class="text-gray-600 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400
                          {{ request()->routeIs('owner.dashboard') ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600' : '' }}">
                    Ana Sayfa
                </a>
                <a href="{{ route('owner.ilanlar.index') }}"
                   class="text-gray-600 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400
                          {{ request()->routeIs('owner.ilanlar.*') ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600' : '' }}">
                    İlanlarım
                </a>
                <a href="{{ route('owner.teklifler.index') }}"
                   class="text-gray-600 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400
                          {{ request()->routeIs('owner.teklifler.*') ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600' : '' }}">
                    Teklifler
                </a>
                <a href="{{ route('owner.mesajlar.index') }}"
                   class="text-gray-600 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400
                          {{ request()->routeIs('owner.mesajlar.*') ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600' : '' }}">
                    Mesajlar
                </a>
                <a href="{{ route('owner.belgeler.index') }}"
                   class="text-gray-600 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400
                          {{ request()->routeIs('owner.belgeler.*') ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600' : '' }}">
                    Belgelerim
                </a>
                <a href="{{ route('owner.reports.index') }}"
                   class="text-gray-600 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400
                          {{ request()->routeIs('owner.reports.*') ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600' : '' }}">
                    Raporlar
                </a>
            </nav>

            {{-- Kullanıcı + Dark Mode + Çıkış + Mobil Menü --}}
            <div class="flex items-center gap-3 text-sm">
                <!-- Dark Mode Toggle -->
                <button @click="darkMode = !darkMode"
                        class="p-2 rounded-full text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-slate-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :title="darkMode ? 'Açık mod' : 'Koyu mod'">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>

                <span class="hidden text-gray-500 dark:text-slate-400 sm:inline font-medium">
                    {{ auth()->user()?->name }}
                </span>

                <form method="POST" action="{{ route('owner.logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-600 hover:border-red-400 hover:text-red-600 hover:bg-red-50 dark:border-slate-600 dark:text-slate-300 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-all">
                        Çıkış
                    </button>
                </form>

                <!-- Mobil Hamburger -->
                <button @click="mobileMenu = !mobileMenu"
                        class="sm:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-slate-700 transition-colors">
                    <svg x-show="!mobileMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenu" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

        </div>

        <!-- Mobil Menü -->
        <div x-show="mobileMenu" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="sm:hidden border-t border-gray-100 dark:border-slate-700 px-4 py-3 space-y-1">
            @foreach([
                ['owner.dashboard',       'Ana Sayfa'],
                ['owner.ilanlar.index',   'İlanlarım'],
                ['owner.teklifler.index', 'Teklifler'],
                ['owner.mesajlar.index',  'Mesajlar'],
                ['owner.belgeler.index',  'Belgelerim'],
                ['owner.reports.index',   'Raporlar'],
            ] as [$route, $label])
            <a href="{{ route($route) }}"
               class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs(str_replace('.index', '.*', $route))
                         ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                         : 'text-gray-600 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                {{ $label }}
            </a>
            @endforeach
            <form method="POST" action="{{ route('owner.logout') }}" class="pt-2 border-t border-gray-100 dark:border-slate-700">
                @csrf
                <button type="submit"
                        class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                    Çıkış Yap
                </button>
            </form>
        </div>
    </header>

    {{-- ── Floating Toast Mesajları (Alpine.js) ── --}}
    <div class="fixed top-5 right-5 z-50 flex flex-col gap-3 pointer-events-none w-80">
        @if(session('basarili'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-x-10"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-10"
                 class="pointer-events-auto bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border-l-4 border-emerald-500 rounded-lg shadow-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-sm text-gray-700 dark:text-gray-200">{{ session('basarili') }}</p>
            </div>
        @endif
        @if(session('bilgi'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-x-10"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-10"
                 class="pointer-events-auto bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border-l-4 border-blue-500 rounded-lg shadow-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-sm text-gray-700 dark:text-gray-200">{{ session('bilgi') }}</p>
            </div>
        @endif
        @if($errors->any())
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-x-10"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-10"
                 class="pointer-events-auto bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border-l-4 border-red-500 rounded-lg shadow-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="flex-1">
                    @foreach($errors->all() as $hata)
                        <p class="text-sm text-gray-700 dark:text-gray-200 mb-1 last:mb-0">{{ $hata }}</p>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ── İçerik — her view kendi max-width'ini yönetir ── --}}
    <main class="w-full">
        @yield('content')
    </main>

    {{-- ── Alt Bilgi ── --}}
    <footer class="mt-auto border-t border-gray-200 bg-white py-4 text-center text-xs text-gray-400 dark:border-slate-700 dark:bg-slate-800">
        &copy; {{ date('Y') }} Yalıhan Emlak — Mülk Sahibi Portalı
    </footer>

    @stack('scripts')
</body>
</html>
