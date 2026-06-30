<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Context7 Location Defaults -->
    <meta name="location-default-latitude" content="{{ config('location.map.default_latitude') }}">
    <meta name="location-default-longitude" content="{{ config('location.map.default_longitude') }}">
    <meta name="location-default-zoom" content="{{ config('location.map.default_zoom') }}">
    <title>@yield('title', 'Yönetim Paneli')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/advanced-leaflet.css') }}">
    @stack('styles')
</head>

<body class="flex min-h-screen flex-col bg-gray-50 dark:bg-slate-900">
    <header
        class="border-b border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:shadow-none">
        <div class="container mx-auto flex items-center justify-between py-3">
            <a href="/admin" class="text-xl font-bold text-blue-700 dark:text-blue-400">Yönetim Paneli</a>
            <nav class="flex gap-6">
                <a href="/admin/dashboard"
                    class="font-medium text-gray-700 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400">Dashboard</a>
                <a href="/admin/tkgm-parsel"
                    class="font-medium text-gray-700 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400">TKGM
                    Parsel</a>
                <a href="/admin/ai-monitor"
                    class="font-medium text-gray-700 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400">AI
                    Monitoring</a>
                <a href="/admin/ai-settings"
                    class="font-medium text-gray-700 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400">AI
                    Ayarları</a>
                <a href="{{ route('advisor.portfolio-doctor') }}"
                    class="font-bold font-medium text-gray-700 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400">Portföy
                    Doktoru</a>
                <a href="/admin"
                    class="font-medium text-gray-700 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400">Ana
                    Panel</a>
            </nav>
        </div>
    </header>
    <main class="container mx-auto flex-1 py-8">
        @yield('content')
    </main>
    <footer
        class="mt-8 border-t border-gray-200 bg-white py-6 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900">
        <div class="container mx-auto text-center text-sm text-gray-500 dark:text-slate-400">
            © {{ date('Y') }} Yalıhan Emlak Yönetim Paneli
        </div>
    </footer>
    @if (config('location.google_maps.enabled') && config('location.google_maps.api_key'))
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ config('location.google_maps.api_key') }}&libraries={{ config('location.google_maps.libraries', 'places') }}"
            async defer></script>
    @endif
    <script src="{{ asset('js/context7-location-adapter.js') }}" defer></script>
    @stack('scripts')
</body>

</html>
