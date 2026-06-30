<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
    x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
    :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Context7 Location Defaults --}}
    <meta name="location-default-latitude" content="{{ config('location.map.default_latitude') }}">
    <meta name="location-default-longitude" content="{{ config('location.map.default_longitude') }}">
    <meta name="location-default-zoom" content="{{ config('location.map.default_zoom') }}">
    <title>@yield('title', 'Yönetim Paneli') — Yalıhan Emlak</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/advanced-leaflet.css') }}">
    @stack('styles')
</head>

<body class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">

    {{-- ═══════════════════════════════════════════════════════
         TOPBAR
    ═══════════════════════════════════════════════════════ --}}
    <header class="sticky top-0 z-40 w-full border-b border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl shadow-sm dark:shadow-slate-950/50">
        <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">

                {{-- Brand --}}
                <a href="/admin" class="flex items-center gap-3 shrink-0 group">
                    <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-amber-600 rounded-lg flex items-center justify-center shadow-md shadow-orange-500/25 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <div class="text-sm font-black text-slate-900 dark:text-white tracking-tighter leading-none">Yalıhan</div>
                        <div class="text-[9px] font-black uppercase tracking-[0.2em] text-orange-500 leading-none mt-0.5">AI OS</div>
                    </div>
                </a>

                {{-- Primary Nav --}}
                <nav class="hidden lg:flex items-center gap-1">
                    <a href="{{ route('admin.dashboard') }}"
                        class="px-3.5 py-2 rounded-lg text-xs font-black uppercase tracking-tighter text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 {{ request()->is('admin/dashboard*') ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white' : '' }}">
                        Pano
                    </a>
                    <a href="{{ route('admin.ilanlar.index') }}"
                        class="px-3.5 py-2 rounded-lg text-xs font-black uppercase tracking-tighter text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 {{ request()->is('admin/ilanlar*') ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400' : '' }}">
                        İlanlar
                    </a>
                    <a href="{{ route('admin.crm.dashboard') }}"
                        class="px-3.5 py-2 rounded-lg text-xs font-black uppercase tracking-tighter text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 {{ request()->is('admin/crm*') ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white' : '' }}">
                        CRM
                    </a>
                    <a href="{{ route('admin.danisman.index') }}"
                        class="px-3.5 py-2 rounded-lg text-xs font-black uppercase tracking-tighter text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 {{ request()->is('admin/danisman*') ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white' : '' }}">
                        Danışmanlar
                    </a>
                    <a href="{{ route('admin.analytics.index') }}"
                        class="px-3.5 py-2 rounded-lg text-xs font-black uppercase tracking-tighter text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 {{ request()->is('admin/analytics*') ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white' : '' }}">
                        Analitik
                    </a>
                    <a href="{{ route('admin.ai-monitor.index') }}"
                        class="px-3.5 py-2 rounded-lg text-xs font-black uppercase tracking-tighter text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 {{ request()->is('admin/ai*') ? 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400' : '' }}">
                        AI
                    </a>
                    <a href="{{ route('advisor.portfolio-doctor') }}"
                        class="px-3.5 py-2 rounded-lg text-xs font-black uppercase tracking-tighter text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200">
                        Portföy
                    </a>
                </nav>

                {{-- Right Actions --}}
                <div class="flex items-center gap-2 shrink-0">

                    {{-- Dark Mode Toggle --}}
                    <button @click="darkMode = !darkMode"
                        class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-200"
                        :aria-label="darkMode ? 'Açık moda geç' : 'Koyu moda geç'">
                        <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998z"/>
                        </svg>
                        <svg x-show="darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z"/>
                        </svg>
                    </button>

                    {{-- Notifications --}}
                    <a href="{{ route('admin.admin-notifications.index') }}"
                        class="relative w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                        </svg>
                    </a>

                    {{-- Settings --}}
                    <a href="{{ route('admin.ayarlar.index') }}"
                        class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                        </svg>
                    </a>

                    {{-- Mobile menu button --}}
                    <button x-data="{ open: false }" @click="open = !open"
                        class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                        </svg>
                    </button>

                </div>
            </div>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════════════════════ --}}
    <main class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    {{-- ═══════════════════════════════════════════════════════
         FOOTER
    ═══════════════════════════════════════════════════════ --}}
    <footer class="mt-8 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 bg-gradient-to-br from-orange-500 to-amber-600 rounded-md flex items-center justify-center">
                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                </div>
                <span class="text-xs font-bold text-slate-400 dark:text-slate-500">
                    © {{ date('Y') }} Yalıhan Emlak
                </span>
            </div>
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 dark:text-slate-600">
                AI OS · SAB v6.1.1 · Context7
            </span>
        </div>
    </footer>

    {{-- Google Maps (conditional) --}}
    @if (config('location.google_maps.enabled') && config('location.google_maps.api_key'))
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ config('location.google_maps.api_key') }}&libraries={{ config('location.google_maps.libraries', 'places') }}"
            async defer></script>
    @endif
    <script src="{{ asset('js/context7-location-adapter.js') }}" defer></script>
    @stack('scripts')
</body>

</html>
