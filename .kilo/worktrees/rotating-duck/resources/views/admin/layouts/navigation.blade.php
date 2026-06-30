<!-- Admin Navigation -->
<nav class="bg-white dark:bg-slate-900 shadow-sm border-b border-gray-200 dark:border-slate-800 sticky top-0 z-50 dark:shadow-none dark:border-slate-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Brand Section -->
            <div class="flex items-center">
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('admin.dashboard.index') }}"
                        class="flex items-center hover:opacity-80 transition-opacity">
                        <img class="h-8 w-auto" src="{{ asset('images/logo.png') }}"
                            alt="{{ config('app.name', 'Yalıhan Emlak') }}">
                        <span class="ml-3 text-xl font-bold text-gray-900 hidden sm:block dark:text-slate-100 dark:text-white">
                            {{ config('app.name', 'Yalıhan Emlak') }}
                        </span>
                    </a>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="hidden md:flex md:items-center md:space-x-1">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.dashboard.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                    </svg>
                    Dashboard
                </a>

                <!-- İlanlar -->
                <div class="relative group">
                    <button
                        class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                        İlanlar
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div
                        class="absolute top-full left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 dark:bg-slate-900 dark:border-slate-700">
                        <a href="{{ route('admin.ilanlar.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300">Tüm İlanlar</a>
                        <a href="{{ route('admin.ilanlar.create') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300">Yeni İlan</a>
                        <a href="{{ route('admin.ilan-kategorileri.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-md dark:text-slate-300" title="Konut, Arsa, İşyeri gibi ana emlak kategorileri">İlan Kategorileri</a>
                        {{-- Yayın Tipi Yöneticisi sidebar menüsünde mevcut (duplicate önlendi - Context7 Compliance 2025-11-11) --}}
                    </div>
                </div>

                <!-- Adres Yönetimi -->
                <a href="{{ route('admin.adres-yonetimi.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.adres-yonetimi.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Adres Yönetimi
                </a>

                <!-- CRM -->
                <div class="relative group">
                    <button
                        class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        CRM
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div
                        class="absolute top-full left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 dark:bg-slate-900 dark:border-slate-700">
                        <a href="{{ route('admin.kisiler.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300">Kişiler</a>
                        <a href="{{ route('admin.talepler.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300">Talepler</a>
                        <a href="{{ route('admin.danisman.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-md dark:text-slate-300">Danışmanlar</a>
                    </div>
                </div>

                <!-- Blog -->
                <div class="relative group">
                    <button
                        class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z">
                            </path>
                        </svg>
                        Blog
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div
                        class="absolute top-full left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 dark:bg-slate-900 dark:border-slate-700">
                        <a href="{{ route('admin.blog.posts.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300">Yazılar</a>
                        <a href="{{ route('admin.blog.categories.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300" title="Blog içerik kategorileri">Blog Kategorileri</a>
                        <a href="{{ route('admin.blog.comments.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-md dark:text-slate-300">Yorumlar</a>
                    </div>
                </div>

                <!-- Sistem Yönetimi -->
                <div class="relative group">
                    <button
                        class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Sistem Yönetimi
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div
                        class="absolute top-full left-0 mt-1 w-64 bg-white dark:bg-slate-900 rounded-lg shadow-lg border border-gray-200 dark:border-slate-800 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 dark:border-slate-700">
                        <a href="{{ route('admin.ozellikler.kategoriler.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300" title="Genel, Yapı, Konfor gibi özellik kategorileri">Özellik Kategorileri</a>
                        <a href="{{ route('admin.ozellikler.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300" title="Oda sayısı, banyo sayısı gibi emlak özellikleri">Emlak Özellikleri</a>
                        <a href="#" onclick="alert('Kategori-Özellik Eşleştirme sayfası yapım aşamasında')"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-md dark:text-slate-300" title="Kategori ve özellik eşleştirme yönetimi">Kategori-Özellik Eşleştirme</a>
                    </div>
                </div>

                <!-- Telegram Bot -->
                <a href="{{ route('admin.telegram-bot.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.telegram-bot.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                        </path>
                    </svg>
                    Telegram Bot
                </a>

                <!-- Ayarlar -->
                <a href="{{ route('admin.ayarlar.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.ayarlar.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Ayarlar
                </a>
            </div>

            <!-- User Menu -->
            <div class="flex items-center">
                <!-- Dark Mode Toggle -->
                <button id="flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-800"
                    class="mr-3 p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                    aria-label="Tema" title="Tema">
                    <svg id="icon-moon" class="h-5 w-5 hidden" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                    </svg>
                    <svg id="icon-sun" class="h-5 w-5 hidden" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M6.76 4.84l-1.8-1.79-1.41 1.41 1.79 1.8 1.42-1.42zM1 13h3v-2H1v2zm10 10h2v-3h-2v3zm9-10v-2h-3v2h3zm-3.95 7.95l1.41 1.41 1.8-1.79-1.41-1.41-1.8 1.79zM13 1h-2v3h2V1zm-6.24 16.95l-1.8 1.79 1.41 1.41 1.8-1.79-1.41-1.41zM12 6a6 6 0 100 12 6 6 0 000-12z" />
                    </svg>
                </button>
                <!-- Notifications -->
                <button
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-5 5-5-5h5V3h5v14z"></path>
                    </svg>
                </button>

                <!-- User Dropdown -->
                <div class="relative group ml-3">
                    <button
                        class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="sr-only">Kullanıcı menüsünü aç</span>
                        <img class="h-8 w-8 rounded-full" src="{{ asset('images/default-avatar.png') }}"
                            alt="{{ auth()->check() ? auth()->user()->name : 'Kullanıcı' }}">
                        <span
                            class="ml-2 text-gray-700 font-medium hidden md:block dark:text-slate-300">{{ auth()->check() ? auth()->user()->name : 'Kullanıcı' }}</span>
                        <svg class="w-4 h-4 ml-1 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-slate-900 rounded-lg shadow-lg border border-gray-200 dark:border-slate-800 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 dark:border-slate-700">
                        <div class="px-4 py-2.5 border-b border-gray-200 dark:border-slate-700">
                            <p class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ auth()->check() ? auth()->user()->name : 'Kullanıcı' }}</p>
                            <p class="text-sm text-gray-500">
                                {{ auth()->check() ? auth()->user()->email : 'email@example.com' }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300">Profil</a>
                        <a href="{{ route('admin.ayarlar.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300">Ayarlar</a>
                        <div class="border-t border-gray-200 dark:border-slate-700"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-md dark:text-slate-300">
                                Çıkış Yap
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button id="flex md:hidden items-center justify-center p-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-800"
                    class="inline-flex items-center justify-center p-2 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Neo Mobile Menu -->
    <div id="flex flex-col md:hidden bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700" class="hidden md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white/90 backdrop-blur border-t border-gray-200 dark:border-slate-700 dark:bg-slate-900/90">
            <a href="{{ route('admin.dashboard.index') }}"
                class="block px-4 py-2.5 rounded-lg text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100">Dashboard</a>
            <a href="{{ route('admin.ilanlar.index') }}"
                class="block px-4 py-2.5 rounded-lg text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100">İlanlar</a>
            <a href="{{ route('admin.kisiler.index') }}"
                class="block px-4 py-2.5 rounded-lg text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100">CRM</a>
            <a href="{{ route('admin.adres-yonetimi.index') }}"
                class="block px-4 py-2.5 rounded-lg text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100">Adres Yönetimi</a>
            <a href="{{ route('admin.blog.posts.index') }}"
                class="block px-4 py-2.5 rounded-lg text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100">Blog</a>
            <a href="{{ route('admin.ayarlar.index') }}"
                class="block px-4 py-2.5 rounded-lg text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100">Ayarlar</a>
        </div>
    </div>
</nav>

<!-- Neo Navigation Scripts -->
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dark Mode Init & Toggle
            const htmlEl = document.documentElement
            const toggleBtn = document.getElementById('flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-800')
            const iconMoon = document.getElementById('icon-moon')
            const iconSun = document.getElementById('icon-sun')
            const STORAGE_KEY = 'neo:theme'

            function applyTheme(theme) {
                if (theme === 'dark') {
                    htmlEl.classList.add('dark')
                    iconMoon.classList.add('hidden')
                    iconSun.classList.remove('hidden')
                } else {
                    htmlEl.classList.remove('dark')
                    iconSun.classList.add('hidden')
                    iconMoon.classList.remove('hidden')
                }
            }

            const saved = localStorage.getItem(STORAGE_KEY)
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
            applyTheme(saved || (prefersDark ? 'dark' : 'light'))

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const isDark = htmlEl.classList.toggle('dark')
                    localStorage.setItem(STORAGE_KEY, isDark ? 'dark' : 'light')
                    applyTheme(isDark ? 'dark' : 'light')
                })
            }

            // Neo Mobile Menu Toggle
            const mobileToggle = document.getElementById('flex md:hidden items-center justify-center p-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-800')
            const mobileMenu = document.getElementById('flex flex-col md:hidden bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700')

            if (mobileToggle && mobileMenu) {
                mobileToggle.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden')
                })
            }
        })
    </script>
@endpush
