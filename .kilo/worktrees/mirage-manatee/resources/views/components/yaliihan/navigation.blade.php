@props([
    'class' => '',
])

<nav
    class="yaliihan-nav bg-white dark:bg-slate-900 shadow-lg fixed w-full z-50 backdrop-filter backdrop-blur-lg bg-opacity-95 dark:bg-opacity-95 border-b border-gray-200 dark:border-slate-800 transition-colors duration-300 {{ $class }} dark:border-slate-700">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-20">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center group">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 dark:from-blue-600 dark:to-purple-700 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-300 shadow-lg hover:shadow-xl">
                        <span class="text-white font-bold text-xl">Y</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-300 dark:text-slate-100">Yalıhan Emlak</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-300">Bodrum'da Güvenilir Emlak</p>
                    </div>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden lg:flex items-center space-x-8">
                <a href="{{ route('home') }}"
                    class="nav-link text-gray-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-colors duration-200 dark:text-slate-300">
                    Ana Sayfa
                </a>
                <div class="relative group">
                    <a href="{{ route('yalihan.properties') }}"
                        class="nav-link text-gray-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-colors duration-200 flex items-center dark:text-slate-300">
                        İlanlar
                        <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </a>
                    <!-- Dropdown Menu -->
                    <div
                        class="absolute top-full left-0 mt-2 w-48 bg-white dark:bg-slate-900 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <a href="{{ route('yalihan.properties') }}"
                            class="block px-4 py-2 text-gray-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 dark:text-slate-300">
                            Tüm İlanlar
                        </a>
                        <a href="{{ route('yalihan.properties', ['ilan_turu' => 'satilik']) }}"
                            class="block px-4 py-2 text-gray-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 dark:text-slate-300">
                            Satılık
                        </a>
                        <a href="{{ route('yalihan.properties', ['ilan_turu' => 'kiralik']) }}"
                            class="block px-4 py-2 text-gray-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 dark:text-slate-300">
                            Kiralık
                        </a>
                    </div>
                </div>
                <a href="{{ route('yalihan.contact') }}"
                    class="nav-link text-gray-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-colors duration-200 dark:text-slate-300">
                    Hakkımızda
                </a>
                <a href="{{ route('yalihan.contact') }}"
                    class="nav-link text-gray-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-colors duration-200 dark:text-slate-300">
                    İletişim
                </a>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-2">
                <!-- Language & Currency Selector -->
                <x-yaliihan.language-currency-selector :current-language="app()->getLocale()" :current-currency="'TRY'" :show-language="true"
                    :show-currency="true" class="hidden sm:flex" />

                <!-- Search Icon -->
                <button class="p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" title="Arama" aria-label="Arama">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>

                <!-- Dark Mode Toggle -->
                <button class="p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" title="Dark Mode" aria-label="Dark mode toggle" onclick="toggleDarkMode()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                        </path>
                    </svg>
                </button>

                <!-- CTA Button -->
                <a href="{{ route('yalihan.contact') }}"
                    class="bg-gradient-to-r from-blue-500 to-purple-600 dark:from-blue-600 dark:to-purple-700 text-white px-6 py-2 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 dark:hover:from-blue-700 dark:hover:to-purple-800 active:scale-95 transition-all duration-200 shadow-lg hover:shadow-xl focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    İlan Ver
                </a>

                <!-- Mobile Menu Button -->
                <button class="lg:hidden p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" x-data
                    @click="$dispatch('toggle-mobile-menu')" aria-label="Menü">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="lg:hidden border-t border-gray-200 dark:border-slate-800 py-4 dark:border-slate-700" x-data="{ open: false }"
            @toggle-mobile-menu.window="open = !open" x-show="open" x-transition>
            <div class="space-y-2">
                <a href="{{ route('home') }}"
                    class="block px-4 py-2 text-gray-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-gray-800 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 rounded-lg dark:text-slate-300">
                    Ana Sayfa
                </a>
                <a href="{{ route('yalihan.properties') }}"
                    class="block px-4 py-2 text-gray-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-gray-800 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 rounded-lg dark:text-slate-300">
                    İlanlar
                </a>
                <a href="{{ route('yalihan.contact') }}"
                    class="block px-4 py-2 text-gray-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-gray-800 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 rounded-lg dark:text-slate-300">
                    Hakkımızda
                </a>
                <a href="{{ route('yalihan.contact') }}"
                    class="block px-4 py-2 text-gray-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-gray-800 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 rounded-lg dark:text-slate-300">
                    İletişim
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Spacer for fixed navigation -->
<div class="h-20"></div>

@push('styles')
<style>
    .yaliihan-nav {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(59, 130, 246, 0.1);
    }

    .dark .yaliihan-nav {
        background: linear-gradient(135deg, rgba(17, 24, 39, 0.95) 0%, rgba(17, 24, 39, 0.9) 100%);
        border-bottom: 1px solid rgba(59, 130, 246, 0.2);
    }

    .nav-link {
        position: relative;
        padding: 0.5rem 0;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        transition: width 0.3s ease;
    }

    .nav-link:hover::after {
        width: 100%;
    }

    /* Mobile menu animation */
    [x-cloak] {
        display: none !important;
    }
</style>
@endpush

@push('scripts')
<script>
    function toggleDarkMode() {
        const html = document.documentElement;
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('dark', isDark);
    }

    // Initialize dark mode from localStorage
    document.addEventListener('DOMContentLoaded', function() {
        const isDark = localStorage.getItem('dark') === 'true';
        if (isDark) {
            document.documentElement.classList.add('dark');
        }
    });
</script>
@endpush
