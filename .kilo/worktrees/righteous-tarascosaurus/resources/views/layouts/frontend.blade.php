<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Context7 Location Defaults -->
    <meta name="location-default-latitude" content="{{ config('location.map.default_latitude') }}">
    <meta name="location-default-longitude" content="{{ config('location.map.default_longitude') }}">
    <meta name="location-default-zoom" content="{{ config('location.map.default_zoom') }}">
    <meta name="description" content="{{ $seo['description'] ?? '' }}">

    {{-- HrefLang --}}
    @foreach ($seo['hreflang'] ?? [] as $lang => $url)
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ $url }}">
    @endforeach
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">

    {{-- Open Graph —————————————————————————————————————————————— --}}
    <meta property="og:title"       content="{{ $seo['title'] ?? 'Yalıhan Emlak — Bodrum Lüks Gayrimenkul' }}">
    <meta property="og:description" content="{{ $seo['description'] ?? 'Bodrum\'da villa, daire ve arsa portföyü. 15 yılı aşkın deneyim.' }}">
    <meta property="og:type"        content="{{ $seo['og_type'] ?? 'website' }}">
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta property="og:locale"      content="{{ $seo['og_locale'] ?? 'tr_TR' }}">
    <meta property="og:locale:alternate" content="en_US">
    <meta property="og:site_name"   content="Yalıhan Emlak">
    <meta property="og:image"       content="{{ $seo['og_image'] ?? asset('images/og-image.jpg') }}">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt"    content="{{ $seo['title'] ?? 'Yalıhan Emlak' }}">

    {{-- Twitter / X Card ————————————————————————————————————————— --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="{{ $seo['title'] ?? 'Yalıhan Emlak' }}">
    <meta name="twitter:description" content="{{ $seo['description'] ?? '' }}">
    <meta name="twitter:image"       content="{{ $seo['og_image'] ?? asset('images/og-image.jpg') }}">

    {{-- Robots & Canonical ——————————————————————————————————————— --}}
    <meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">

    <title>{{ $seo['title'] ?? 'Yalıhan Emlak' }}</title>

    <!-- Propertius Modern Fonts: Manrope (display) + Inter (data/labels) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Vite CSS + Frontend JS (Alpine) -->
    @vite(['resources/css/app.css', 'resources/js/frontend.js'])

    <!-- Tailwind CDN Fallback (if Vite fails in development) -->
    @if(config('app.env') === 'local')
        <script>
            // Check if Vite dev server is running, if not use CDN
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const testEl = document.createElement('div');
                    testEl.className = 'bg-blue-500';
                    testEl.style.position = 'absolute';
                    testEl.style.left = '-9999px';
                    document.body.appendChild(testEl);
                    const bgColor = window.getComputedStyle(testEl).backgroundColor;
                    document.body.removeChild(testEl);

                    // If Tailwind is not working (bg-blue-500 doesn't apply), load CDN
                    if (bgColor === 'rgba(0, 0, 0, 0)' || bgColor === 'transparent' || !bgColor.includes(
                            'rgb')) {
                        console.warn('Tailwind not detected, loading CDN fallback...');
                        if (!window.tailwind) {
                            const script = document.createElement('script');
                            script.src = 'https://cdn.tailwindcss.com';
                            script.onload = function() {
                                if (window.tailwind) {
                                    tailwind.config = {
                                        darkMode: 'class',
                                        theme: {
                                            extend: {
                                                colors: {
                                                    primary: '#3b82f6',
                                                    secondary: '#1e40af',
                                                    accent: '#f59e0b',
                                                }
                                            }
                                        }
                                    };
                                    location.reload();
                                }
                            };
                            document.head.appendChild(script);
                        }
                    }
                }, 500);
            });
        </script>
    @endif


    <!-- Custom Styles -->
    <link rel="stylesheet" href="{{ asset('css/advanced-leaflet.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ai-assistant.css') }}">
    @stack('styles')

    {{-- Tema CSS değişkenleri — ThemeService → config/themes.php → settings.frontend_theme --}}
    <style>
        [x-cloak] { display: none !important; }
        :root {
            color-scheme: light;
{{ app(\App\Services\ThemeService::class)->getCssVars() }}
            /* Statik yardımcı değişkenler (tema bağımsız) */
            --ege:         #0D5FA3;
            --ege-light:   #EFF6FF;
            --ege-dark:    #0A4D87;
            --gri:         #F4F6F8;
            --gri-mid:     #E5E7EB;
            --metin:       #1A1A2E;
            --metin-ikinci:#6B7280;
            --satilik:     #15803D;
            --kiralik:     #B45309;
        }

        body {
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            background: var(--surface, #faf8ff);
            color: var(--on-surface, #191b23);
        }
        /* Veri/etiket alanları Inter kullanır */
        .font-data, .font-meta,
        .badge, .tag, .label,
        input, select, textarea, button {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Nav link hover — Aegean Clean */
        .nav-link {
            color: #374151;
            transition: color 0.2s ease;
            position: relative;
            padding: 0 0.5rem;
        }
        .nav-link:hover {
            color: var(--ege);
        }
        .mobile-nav-link {
            color: #374151;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .mobile-nav-link:hover {
            color: var(--ege);
            background: var(--ege-light);
        }

        /* Footer links */
        .footer-link {
            color: rgba(255, 255, 255, 0.6);
            transition: color 0.2s ease;
        }
        .footer-link:hover {
            color: #ffffff;
        }
        .social-icon {
            color: rgba(255, 255, 255, 0.5);
            transition: color 0.2s ease, transform 0.2s ease;
            display: inline-flex;
        }
        .social-icon:hover {
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Aegean badge system */
        .badge-satilik { background: #DCFCE7; color: #15803D; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-kiralik { background: #FEF3C7; color: #B45309; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-proje   { background: #EFF6FF; color: #1D4ED8; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>

<body
    class="text-gray-900"
    data-locale-endpoint="{{ route('preferences.locale') }}"
    data-currency-endpoint="{{ route('preferences.currency') }}">
    <!-- Global Topbar -->
    <x-frontend.global.topbar />

    <!-- Navigation — Propertius Modern Glass -->
    <nav class="fixed top-0 w-full z-50 bg-white/95 backdrop-blur-md border-b border-slate-100 shadow-sm h-20 font-sans">
        <div class="max-w-7xl mx-auto px-6 md:px-12 flex justify-between items-center h-full">
            <div class="flex items-center gap-12">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-1 shrink-0">
                    <span class="text-xl font-bold tracking-tight text-on-surface">Yalıhan</span>
                    <span class="text-xl font-semibold tracking-[0.12em] text-primary">EMLAK</span>
                </a>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex gap-8">
                    <a class="text-on-surface-variant font-medium hover:text-primary transition-all duration-300" href="{{ route('ilanlar.index', ['kategori_slug' => 'konut']) }}">Konut</a>
                    <a class="text-on-surface-variant font-medium hover:text-primary transition-all duration-300" href="{{ route('ilanlar.index', ['kategori_slug' => 'arsa-arazi']) }}">Arsa</a>
                    <a class="text-on-surface-variant font-medium hover:text-primary transition-all duration-300" href="{{ route('villas.index') }}">Yazlık</a>
                    <a class="text-on-surface-variant font-medium hover:text-primary transition-all duration-300" href="{{ route('ilanlar.international') }}">Uluslararası</a>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="{{ route('contact') }}" class="hidden lg:flex items-center text-on-surface-variant font-medium hover:text-primary transition-all">İletişim</a>
                
                @auth
                    <a href="{{ route('admin.dashboard.index') }}" class="bg-primary text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-blue-700 active:scale-[0.99] transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">person</span> Panel
                    </a>
                @else
                    <a href="{{ route('login') }}" class="bg-primary text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-blue-700 active:scale-[0.99] transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">login</span> Giriş Yap
                    </a>
                @endauth
                
                <!-- Mobile Menu Button -->
                <button onclick="toggleMobileMenu()" class="rounded-lg p-2 transition-colors duration-200 md:hidden text-on-surface-variant" aria-label="Menü">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden py-4 md:hidden bg-white border-t border-slate-100 shadow-lg absolute w-full left-0 top-20">
            <div class="flex flex-col gap-1 px-4">
                <a href="{{ route('home') }}" class="rounded-lg px-4 py-3 text-sm font-medium hover:bg-surface-container-low text-on-surface">Ana Sayfa</a>
                <a href="{{ route('ilanlar.index', ['kategori_slug' => 'konut']) }}" class="rounded-lg px-4 py-3 text-sm font-medium hover:bg-surface-container-low text-on-surface">Konut</a>
                <a href="{{ route('ilanlar.index', ['kategori_slug' => 'arsa-arazi']) }}" class="rounded-lg px-4 py-3 text-sm font-medium hover:bg-surface-container-low text-on-surface">Arsa</a>
                <a href="{{ route('villas.index') }}" class="rounded-lg px-4 py-3 text-sm font-medium hover:bg-surface-container-low text-on-surface">Yazlık Kiralık</a>
                <a href="{{ route('ilanlar.international') }}" class="rounded-lg px-4 py-3 text-sm font-medium hover:bg-surface-container-low text-on-surface">Uluslararası</a>
                <a href="{{ route('frontend.danismanlar.index') }}" class="rounded-lg px-4 py-3 text-sm font-medium hover:bg-surface-container-low text-on-surface">Danışmanlar</a>
                <a href="{{ route('contact') }}" class="rounded-lg px-4 py-3 text-sm font-medium hover:bg-surface-container-low text-on-surface">İletişim</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- Footer — Premium Mediterranean -->
    <footer style="background: #001a6e; border-top: 1px solid rgba(255,255,255,0.08);">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="mb-12 grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">

                <!-- Company -->
                <div>
                    <div class="mb-5 flex items-center gap-1">
                        <span class="text-xl font-bold tracking-tight" style="color: #2563EB;">Yalıhan</span>
                        <span class="text-xl font-light tracking-[0.18em]" style="color: rgba(255,255,255,0.7);">EMLAK</span>
                    </div>
                    <p class="mb-6 text-sm leading-relaxed" style="color: rgba(255,255,255,0.45);">
                        Bodrum'un en prestijli bölgelerinde 20+ yıllık deneyimle güvenilir,
                        profesyonel gayrimenkul danışmanlığı.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="social-icon" aria-label="Facebook">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"/></svg>
                        </a>
                        <a href="#" class="social-icon" aria-label="Instagram">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd"/></svg>
                        </a>
                        <a href="#" class="social-icon" aria-label="LinkedIn">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h6 class="mb-5 text-xs font-semibold uppercase tracking-widest" style="color: #2563EB;">Hızlı Linkler</h6>
                    <ul class="space-y-3">
                        <li><a href="{{ route('home') }}" class="footer-link text-sm">Ana Sayfa</a></li>
                        <li><a href="{{ route('ilanlar.index') }}" class="footer-link text-sm">Tüm İlanlar</a></li>
                        <li><a href="{{ route('frontend.danismanlar.index') }}" class="footer-link text-sm">Danışmanlar</a></li>
                        <li><a href="{{ route('ilanlar.international') }}" class="footer-link text-sm">Uluslararası</a></li>
                        <li><a href="{{ route('contact') }}" class="footer-link text-sm">İletişim</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div>
                    <h6 class="mb-5 text-xs font-semibold uppercase tracking-widest" style="color: #2563EB;">Hizmetler</h6>
                    <ul class="space-y-3">
                        <li><a href="{{ route('ilanlar.index', ['islem_tipi' => 'satis', 'kategori_slug' => 'konut']) }}" class="footer-link text-sm">Satılık Konut</a></li>
                        <li><a href="{{ route('ilanlar.index', ['islem_tipi' => 'kiralama', 'kategori_slug' => 'konut']) }}" class="footer-link text-sm">Kiralık Konut</a></li>
                        <li><a href="{{ route('ilanlar.index', ['kategori_slug' => 'arsa-arazi']) }}" class="footer-link text-sm">Arsa &amp; Arazi</a></li>
                        <li><a href="{{ route('ilanlar.index', ['kategori_slug' => 'isyeri']) }}" class="footer-link text-sm">Ticari Mülkler</a></li>
                        <li><a href="{{ route('contact') }}" class="footer-link text-sm">Yatırım Danışmanlığı</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h6 class="mb-5 text-xs font-semibold uppercase tracking-widest" style="color: #2563EB;">İletişim</h6>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <x-icon name="konum" class="w-4 h-4 mt-0.5 shrink-0" style="color: #2563EB;" />
                            <span class="text-sm" style="color: rgba(255,255,255,0.5);">Yalıkavak, Bodrum, Muğla</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-icon name="telefon" class="w-4 h-4 shrink-0" style="color: #2563EB;" />
                            <span class="text-sm" style="color: rgba(255,255,255,0.5);">+90 252 123 45 67</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-icon name="gonder" class="w-4 h-4 shrink-0" style="color: #2563EB;" />
                            <span class="text-sm" style="color: rgba(255,255,255,0.5);">info@yalihanemlak.com</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Divider + Copyright -->
            <div class="pt-8 flex flex-col items-center justify-between gap-4 md:flex-row"
                 style="border-top: 1px solid rgba(37,99,235,0.10);">
                <p class="text-xs" style="color: rgba(255,255,255,0.3);">
                    &copy; {{ date('Y') }} Yalıhan Emlak. Tüm hakları saklıdır.
                </p>
                <div class="flex gap-6">
                    <a href="#" class="footer-link text-xs">Gizlilik Politikası</a>
                    <a href="#" class="footer-link text-xs">Kullanım Şartları</a>
                </div>
            </div>
        </div>
    </footer>

    @if (config('location.google_maps.enabled') && config('location.google_maps.api_key'))
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ config('location.google_maps.api_key') }}&libraries={{ config('location.google_maps.libraries', 'places') }}"
            async defer></script>
    @endif
    <script src="{{ asset('js/context7-location-adapter.js') }}" defer></script>
    <script src="{{ asset('js/ai-assistant.js') }}" defer></script>

    <!-- Vanilla JS for Mobile Menu & Dark Mode -->
    <script>
        // ✅ CONTEXT7: Vanilla JS - Error handling eklendi
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            try {
                const menu = document.getElementById('mobileMenu');
                if (menu) {
                    menu.classList.toggle('hidden');
                }
            } catch (error) {
                console.error('Context7: Mobile menu toggle error', error);
            }
        }

        // Dark Mode Toggle - FIX: localStorage boolean sorunu düzeltildi
        function toggleDarkMode() {
            try {
                const html = document.documentElement;
                const isDark = html.classList.toggle('dark');
                // ✅ FIX: Boolean değer doğrudan kaydediliyor
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                console.log('Context7: Dark mode toggled', isDark ? 'dark' : 'light');
            } catch (error) {
                console.error('Context7: Dark mode toggle error', error);
            }
        }

        // Aegean Clean: always light mode — dark class removed unconditionally
        (function() {
            document.documentElement.classList.remove('dark');
            try { localStorage.removeItem('theme'); } catch(e) {}
        })();

        // Dark mode system listener removed — Aegean Clean is always light

        document.addEventListener('DOMContentLoaded', () => {
            try {
                const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : null;
                const localeEndpoint = document.body.dataset.localeEndpoint || null;
                const currencyEndpoint = document.body.dataset.currencyEndpoint || null;

                const postPreference = (endpoint, payload) => {
                    if (!endpoint || !csrfToken) {
                        console.warn('Context7: Preference endpoint or CSRF token missing');
                        return;
                    }

                    fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(payload),
                        })
                        .then((response) => {
                            if (!response.ok) {
                                return response.json().then((data) => Promise.reject(data));
                            }
                            return response.json().catch(() => ({}));
                        })
                        .then(() => {
                            window.location.reload();
                        })
                        .catch((error) => {
                            console.error('Context7: Preference update failed', error);
                        });
                };

                document.querySelectorAll('[data-preference-locale]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const locale = button.getAttribute('data-preference-locale');
                        postPreference(localeEndpoint, {
                            locale
                        });
                    });
                });

                document.querySelectorAll('[data-preference-currency]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const currency = button.getAttribute('data-preference-currency');
                        postPreference(currencyEndpoint, {
                            currency
                        });
                    });
                });

                const mobileCurrencySelect = document.querySelector('[data-preference-currency-select]');
                if (mobileCurrencySelect) {
                    mobileCurrencySelect.addEventListener('change', (event) => {
                        postPreference(currencyEndpoint, {
                            currency: event.target.value
                        });
                    });
                }

                // AI Guide Button Handler
                document.querySelectorAll('[data-ai-guide-endpoint]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const endpoint = button.getAttribute('data-ai-guide-endpoint');
                        if (endpoint) {
                            window.location.href = endpoint;
                        }
                    });
                });

                // Property AI Analyze Button Handler
                document.querySelectorAll('[data-ai-analyze]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const propertyId = button.getAttribute('data-ai-analyze');
                        if (propertyId) {
                            // Redirect to AI explore with property context
                            const aiEndpoint = '{{ route('ai.explore') }}';
                            window.location.href = `${aiEndpoint}?property_id=${propertyId}`;
                        }
                    });
                });
            } catch (error) {
                console.error('Context7: Preference initialization error', error);
            }
        });
    </script>

    <!-- Custom Scripts -->
    @stack('scripts')
</body>

</html>
