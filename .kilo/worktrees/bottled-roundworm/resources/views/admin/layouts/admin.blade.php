<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') | Yalıhan Emlak</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="@yield('meta_description', 'Yalıhan Emlak yönetim paneli - İlan, kategori, kullanıcı ve sistem yönetimi için gelişmiş admin paneli.')">
    <meta name="keywords" content="@yield('meta_keywords', 'yalıhan emlak, admin panel, ilan yönetimi, emlak yönetimi')">
    <meta name="author" content="Yalıhan Emlak">

    <!-- OpenGraph Tags -->
    <meta property="og:title" content="@yield('title', 'Admin') | Yalıhan Emlak">
    <meta property="og:description" content="@yield('meta_description', 'Yalıhan Emlak yönetim paneli - İlan, kategori, kullanıcı ve sistem yönetimi için gelişmiş admin paneli.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Yalıhan Emlak Admin">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('title', 'Admin') | Yalıhan Emlak">
    <meta name="twitter:description" content="@yield('meta_description', 'Yalıhan Emlak yönetim paneli - İlan, kategori, kullanıcı ve sistem yönetimi için gelişmiş admin paneli.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/js/admin-safe-fetch.js', 'resources/css/app.css', 'resources/css/leaflet.css', 'resources/js/leaflet-loader.js', 'resources/js/app.js', 'resources/js/admin/global.js', 'resources/js/admin/neo.js', 'resources/js/components/UnifiedPersonSelector.js', 'resources/js/admin/helpers/map-helper.js'])

    <!-- ✅ SAB: API Endpoint Management System -->
    <script src="{{ asset('js/api-config.js') }}?v={{ time() }}" defer></script>
    <!-- ✅ API Helper Utility (APIConfig'den sonra yüklenmeli) -->
    <script src="{{ asset('js/utils/api-helper.js') }}?v={{ time() }}" defer></script>
    <!-- ✅ Loading Manager Utility -->
    <script src="{{ asset('js/utils/loading-manager.js') }}?v={{ time() }}" defer></script>

    <!-- Context7 Components CSS -->
    <link href="{{ asset('css/context7-live-search.css') }}" rel="stylesheet">
    <!-- Tailwind CSS utility classes kullanılır (tailwind.config.js) -->

    <!-- Context7 Toast Utility System -->
    <!-- Toast component uses Tailwind CSS classes directly, no external CSS needed -->

    <!-- PHASE 2: AJAX & UI Utilities (Context7 Standards) -->
    <script>
        // Prevent multiple toast-system initialization
        if (typeof window.ToastSystem === 'undefined') {
            // Load toast-system.js inline to prevent multiple loads
            const script = document.createElement('script');
            script.src = '{{ asset('js/admin/toast-system.js') }}';
            script.defer = true;
            document.head.appendChild(script);
        }
    </script>
    <script src="{{ asset('js/admin/ajax-helpers.js') }}" defer></script>
    <script src="{{ asset('js/admin/ui-helpers.js') }}" defer></script>

    {{-- 📊 Context7: ApexCharts Analytics Visualization --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js" defer></script>

    @yield('styles')
    @stack('styles')
</head>

<body x-data="adminApp()" x-init="init()" :class="{ 'dark': dark }"
    class="h-full bg-gray-50 text-gray-900 dark:bg-slate-900 dark:text-white">
    <a href="#main-content"
        class="sr-only rounded border border-gray-200 bg-white px-3 py-1 text-gray-900 shadow-md focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 dark:border-slate-800 dark:bg-slate-900 dark:text-white dark:shadow-none">
        İçeriğe atla
    </a>

    <div class="min-h-screen bg-gray-50 dark:bg-slate-900">
        <div class="flex h-screen">
            <!-- Modern Sidebar -->
            @include('admin.layouts.sidebar')

            <!-- Content -->
            <div class="flex flex-1 flex-col overflow-hidden">
                <!-- Topbar -->
                <header
                    class="sticky top-0 z-30 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-gray-950/80">
                    <div class="flex h-14 items-center gap-3 px-3 sm:px-4">
                        <div class="ml-auto flex items-center gap-2">
                            <div class="hidden md:block">
                                <button @click="$dispatch('open-global-search')"
                                    class="flex w-72 items-center gap-3 rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-400 outline-none transition-all hover:border-blue-400 dark:border-gray-600 dark:bg-slate-900 dark:text-gray-500 dark:hover:border-blue-500 md:w-80">
                                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                    </svg>
                                    <span>Hızlı Arama...</span>
                                    <kbd
                                        class="ml-auto rounded border border-gray-200 bg-white px-1.5 py-0.5 font-sans text-[10px] dark:border-slate-700 dark:bg-slate-900">⌘K</kbd>
                                </button>
                            </div>
                            {{-- Notification Dropdown --}}
                            <x-admin.header.notification-dropdown />

                            <button class="text-gray-400-btn touch-target-optimized" @click="toggleDark()"
                                :aria-pressed="dark.toString()" aria-label="Tema">
                                <svg x-show="!dark" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12 2a1 1 0 011 1v2a1 1 0 11-2 0V3a1 1 0 011-1zm0 16a1 1 0 011 1v2a1 1 0 11-2 0v-2a1 1 0 011-1zM4.222 4.222a1 1 0 011.414 0L7 5.586a1 1 0 11-1.414 1.414L4.222 5.636a1 1 0 010-1.414zM18.414 18.414a1 1 0 010 1.414L17 21.242a1 1 0 11-1.414-1.414l1.414-1.414a1 1 0 011.414 0zM2 13a1 1 0 110-2h2a1 1 0 110 2H2zm18 0a1 1 0 110-2h2a1 1 0 110 2h-2zM4.222 19.778a1 1 0 010-1.414L5.586 17a1 1 0 111.414 1.414l-1.364 1.364a1 1 0 01-1.414 0zM17 7a1 1 0 011.414-1.414L19.778 6.95A1 1 0 1118.364 8.364L17 7zM12 6a6 6 0 100 12A6 6 0 0012 6z" />
                                </svg>
                                <svg x-show="dark" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                                </svg>
                            </button>
                            <div class="relative" x-data="{ o: false }">
                                <button @click="o=!o"
                                    class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-white px-2 py-1 transition-colors hover:bg-gray-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-gray-700">
                                    <div
                                        class="grid h-8 w-8 place-items-center rounded-full bg-gradient-to-r from-primary-500 to-amber-500 text-white">
                                        <span
                                            class="text-sm font-medium text-white">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="o" @click.away="o=false" x-transition
                                    class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-md dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                                    <a href="{{ route('profile.edit') }}"
                                        class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">Profil</a>
                                    <a href="{{ route('admin.ayarlar.index') }}"
                                        class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">Ayarlar</a>
                                    <div
                                        class="my-1 border-t border-gray-200 dark:border-slate-800">
                                    </div>
                                    <form method="POST" action="{{ route('logout') }}">@csrf
                                        <button type="submit"
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-800">Çıkış</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                {{-- SAB5: Global AI Status Bar --}}
                @include('admin.partials.ai-status-bar')

                <main id="main" class="flex-1 overflow-y-auto bg-gray-50 dark:bg-slate-900">
                    <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
                        {{-- Flash Messages --}}
                        {{-- Flash Messages --}}
                        @if (session('success'))
                            <div class="alert-success mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200"
                                x-data="{ show: true }" x-show="show" x-transition>
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ session('success') }}</span>
                                    <button @click="show = false"
                                        class="ml-auto text-green-600 hover:opacity-75 dark:text-green-300"
                                        aria-label="Kapat">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200"
                                x-data="{ show: true }" x-show="show" x-transition>
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ session('error') }}</span>
                                    <button @click="show = false"
                                        class="ml-auto text-red-600 hover:opacity-75 dark:text-red-300"
                                        aria-label="Kapat">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if (session('warning'))
                            <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200"
                                x-data="{ show: true }" x-show="show" x-transition>
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>{{ session('warning') }}</span>
                                    <button @click="show = false"
                                        class="ml-auto text-yellow-600 hover:opacity-75 dark:text-yellow-300"
                                        aria-label="Kapat">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if (session('info'))
                            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200"
                                x-data="{ show: true }" x-show="show" x-transition>
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ session('info') }}</span>
                                    <button @click="show = false"
                                        class="ml-auto text-blue-600 hover:opacity-75 dark:text-blue-300"
                                        aria-label="Kapat">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Context7 Toast & Alpine Stores -->
    <script src="{{ asset('js/admin/toast-utility.js') }}"></script>
    <script src="{{ asset('js/admin/alpine-stores.js') }}"></script>
    <script src="{{ asset('js/admin/progressive-loader.js') }}"></script>

    <!-- ⚠️ GEÇICI: jQuery - Migration tamamlanana kadar (2025-10-21) -->
    <!-- FIXME: 6 dosya hala $.ajax() kullanıyor - Vanilla JS'e migrate edilecek -->
    <!-- Dosyalar: address-select.js, location-helper.js, location-map-helper.js, ilan-form.js -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        console.log('🔍 Context7 Live Search Active - Vanilla JS (35KB, 0 dependencies)');
        console.log('⚠️ jQuery temporarily loaded - Migration in progress...');
    </script>

    @stack('scripts')

    <!-- Alpine.js - Vite app.js içinde yükleniyor (CDN kaldırıldı) -->

    <!-- Context7 Toast Component Include -->
    <x-admin.toast />

    <!-- Event Booking Manager - Must be defined before Alpine.js -->
    <script>
        // Define eventBookingManager globally before Alpine.js initializes
        if (typeof window.eventBookingManager === 'undefined') {
            window.eventBookingManager = function(ilanId = null) {
                return {
                    ilanId: ilanId,
                    events: [],
                    currentMonth: new Date(),
                    selectedDate: null,
                    showCreateModal: false,
                    editingEvent: null,
                    formData: {
                        event_type: 'booking',
                        guest_name: '',
                        guest_phone: '',
                        guest_email: '',
                        guest_count: 2,
                        check_in: '',
                        check_out: '',
                        nights: 0,
                        total_price: 0,
                        booking_status: 'pending',
                        notes: ''
                    },
                    get currentMonthName() {
                        return this.currentMonth.toLocaleDateString('tr-TR', {
                            month: 'long',
                            year: 'numeric'
                        });
                    },
                    get calendarDays() {
                        const year = this.currentMonth.getFullYear();
                        const month = this.currentMonth.getMonth();
                        const firstDay = new Date(year, month, 1);
                        const lastDay = new Date(year, month + 1, 0);
                        const startDay = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
                        const days = [];
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        for (let i = startDay - 1; i >= 0; i--) {
                            const date = new Date(year, month, -i);
                            days.push(this.createDayObject(date, false));
                        }
                        for (let i = 1; i <= lastDay.getDate(); i++) {
                            const date = new Date(year, month, i);
                            days.push(this.createDayObject(date, true));
                        }
                        const remaining = 42 - days.length;
                        for (let i = 1; i <= remaining; i++) {
                            const date = new Date(year, month + 1, i);
                            days.push(this.createDayObject(date, false));
                        }
                        return days;
                    },
                    get upcomingEvents() {
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        return this.events
                            .filter(e => new Date(e.check_out) >= today)
                            .sort((a, b) => new Date(a.check_in) - new Date(b.check_in))
                            .slice(0, 5);
                    },
                    async init() {
                        if (this.ilanId) {
                            await this.loadEvents();
                        }
                    },
                    async loadEvents() {
                        try {
                            const response = await fetch(`/api/admin/ilanlar/${this.ilanId}/events`);
                            if (response.ok) {
                                const data = await response.json();
                                this.events = data.events || [];
                            }
                        } catch (error) {
                            console.error('Events yüklenemedi:', error);
                        }
                    },
                    createDayObject(date, isCurrentMonth) {
                        const dateStr = date.toISOString().split('T')[0];
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        date.setHours(0, 0, 0, 0);
                        const bookedEvent = this.events.find(e =>
                            e.event_type === 'booking' &&
                            e.booking_status !== 'cancelled' &&
                            dateStr >= e.check_in &&
                            dateStr < e.check_out
                        );
                        const blockedEvent = this.events.find(e =>
                            e.event_type === 'blocked' &&
                            dateStr >= e.check_in &&
                            dateStr < e.check_out
                        );
                        return {
                            date: dateStr,
                            dayNumber: date.getDate(),
                            isCurrentMonth: isCurrentMonth,
                            isToday: date.getTime() === today.getTime(),
                            isBooked: !!bookedEvent,
                            isBlocked: !!blockedEvent,
                            isSelected: this.selectedDate === dateStr
                        };
                    },
                    selectDate(day) {
                        if (!day.isCurrentMonth) return;
                        this.selectedDate = day.date;
                        this.formData.check_in = day.date;
                        this.showCreateModal = true;
                    },
                    previousMonth() {
                        this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() - 1,
                            1);
                    },
                    nextMonth() {
                        this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + 1,
                            1);
                    },
                    calculateNights() {
                        if (this.formData.check_in && this.formData.check_out) {
                            const start = new Date(this.formData.check_in);
                            const end = new Date(this.formData.check_out);
                            const diffTime = end - start;
                            this.formData.nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        } else {
                            this.formData.nights = 0;
                        }
                    },
                    async saveEvent() {
                        if (!this.formData.check_in || !this.formData.check_out) {
                            window.toast?.('Giriş ve çıkış tarihleri gerekli', 'error');
                            return;
                        }
                        if (this.formData.event_type === 'booking' && !this.formData.guest_name) {
                            window.toast?.('Misafir adı gerekli', 'error');
                            return;
                        }
                        const url = this.editingEvent ?
                            `/api/admin/events/${this.editingEvent.id}` :
                            '/api/admin/events';
                        const method = this.editingEvent ? 'PATCH' : 'POST';
                        try {
                            const response = await fetch(url, {
                                method: method,
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .content,
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    ...this.formData,
                                    ilan_id: this.ilanId
                                })
                            });
                            if (response.ok) {
                                await this.loadEvents();
                                this.closeModal();
                                window.toast?.('Rezervasyon kaydedildi', 'success');
                            }
                        } catch (error) {
                            console.error('Save error:', error);
                            window.toast?.('Kaydetme hatası', 'error');
                        }
                    },
                    editEvent(event) {
                        this.editingEvent = event;
                        this.formData = {
                            ...event
                        };
                        this.showCreateModal = true;
                    },
                    async deleteEvent(eventId) {
                        if (!confirm('Bu rezervasyonu silmek istediğinize emin misiniz?')) return;
                        try {
                            const response = await fetch(`/api/admin/events/${eventId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .content
                                }
                            });
                            if (response.ok) {
                                await this.loadEvents();
                                window.toast?.('Rezervasyon silindi', 'success');
                            }
                        } catch (error) {
                            console.error('Delete error:', error);
                        }
                    },
                    closeModal() {
                        this.showCreateModal = false;
                        this.editingEvent = null;
                        this.formData = {
                            event_type: 'booking',
                            guest_name: '',
                            guest_phone: '',
                            guest_email: '',
                            guest_count: 2,
                            check_in: '',
                            check_out: '',
                            nights: 0,
                            total_price: 0,
                            booking_status: 'pending',
                            notes: ''
                        };
                    },
                    formatDate(dateStr) {
                        if (!dateStr) return '';
                        return new Date(dateStr).toLocaleDateString('tr-TR', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        });
                    },
                    formatPrice(price) {
                        return new Intl.NumberFormat('tr-TR', {
                            style: 'currency',
                            currency: 'TRY'
                        }).format(price);
                    }
                };
            };
        }
    </script>

    <script>
        // Admin Alpine.js Functions
        function adminApp() {
            return {
                dark: false,
                mobileSidebar: false,

                init() {
                    // Dark mode initialization - Varsayılan olarak light mode
                    // Eğer localStorage'da dark ayarı yoksa false yap
                    if (!localStorage.getItem('dark')) {
                        localStorage.setItem('dark', 'false');
                    }
                    this.dark = localStorage.getItem('dark') === 'true';

                    // Apply dark mode
                    this.updateDarkMode();
                },

                toggleDark() {
                    this.dark = !this.dark;
                    localStorage.setItem('dark', this.dark);
                    this.updateDarkMode();
                },

                updateDarkMode() {
                    if (this.dark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                },

                toggleMobileSidebar() {
                    this.mobileSidebar = !this.mobileSidebar;
                }
            }
        }

        // Global functions for compatibility
        function toggleMobileSidebar() {
            // This will be handled by Alpine.js
        }

        function toggleDark() {
            // This will be handled by Alpine.js
        }

        function init() {
            // This will be handled by Alpine.js
        }

        function mobileSidebar() {
            // This will be handled by Alpine.js
        }

        function dark() {
            // This will be handled by Alpine.js
        }
    </script>

    {{-- 🔔 Toast Notification System --}}
    @vite(['resources/js/components/ToastNotification.js'])
    <div class="fixed bottom-4 right-4 z-50" x-data="toastNotificationComponent()" x-init="init()">
        <div x-show="visible" x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
            :class="classes" @click="hide()" class="max-w-md cursor-pointer">
            <div class="flex-shrink-0" x-html="icon"></div>
            <p class="flex-1 text-sm font-medium" x-text="message"></p>
            <button @click.stop="hide()"
                class="flex-shrink-0 text-gray-400 transition-colors duration-200 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    {{-- modern global search modal --}}
    @include('admin.components.global-search-modal')

    {{-- modern floating action button --}}
    @include('admin.components.floating-action-button')

    {{-- Copilot AI assistant widget --}}
    @include('admin.components.copilot-widget')
</body>

</html>
