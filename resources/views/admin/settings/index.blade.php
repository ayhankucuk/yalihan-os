@extends('admin.layouts.admin')

@section('title', 'Ayarlar')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ saving: false }">
        {{-- Page Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="mb-2 flex items-center gap-3 text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Sistem Ayarları
                    </h1>
                    <p class="mt-1 text-gray-600 dark:text-gray-400">Sistem genelinde tüm ayarları yönetin ve yapılandırın</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="resetToDefaults()"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-95 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-200 dark:text-slate-300 dark:shadow-none dark:hover:bg-gray-700">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Varsayılanlara Dön
                    </button>
                </div>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if (session('success'))
            <x-admin.form-alert type="success" title="Kaydedildi" class="mb-6">
                {{ session('success') }}
            </x-admin.form-alert>
        @endif

        @if (session('error'))
            <x-admin.form-alert type="error" title="Hata" class="mb-6">
                {{ session('error') }}
            </x-admin.form-alert>
        @endif

        {{-- Settings Form --}}
        <form method="POST" action="{{ route('admin.ayarlar.bulk-update') }}" id="settingsForm" class="space-y-6" @submit="saving = true">
            @csrf
            @method('POST')

            {{-- Modern Tab Navigation --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-slate-900 dark:shadow-none">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    {{-- Mobile: Dropdown selector --}}
                    <div class="md:hidden p-4 border-b border-gray-200 dark:border-gray-700">
                        <label for="mobile-tab-select" class="sr-only">Sekme Seç</label>
                        <select id="mobile-tab-select"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                            <option value="genel">Genel</option>
                            <option value="bildirim">Bildirimler</option>
                            <option value="portal">Portal Entegrasyonları</option>
                            <option value="fiyat">Fiyatlandırma</option>
                            <option value="qrcode">QR Kod</option>
                            <option value="navigation">Navigasyon</option>
                            <option value="kullanici">Kullanıcı Yönetimi</option>
                            <option value="diller">Diller</option>
                            <option value="paralar">Para Birimleri</option>
                        </select>
                    </div>

                    {{-- Desktop: Horizontal tabs --}}
                    <nav class="hidden md:-mb-px md:flex md:flex-wrap md:px-6" aria-label="Tabs">
                        <button type="button" data-tab="genel"
                            class="tab-button active flex items-center gap-2 border-b-2 border-blue-500 px-6 py-4 text-sm font-medium text-blue-600 transition-all duration-200 dark:text-blue-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Genel
                        </button>
                        <button type="button" data-tab="bildirim"
                            class="tab-button flex items-center gap-2 border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 transition-all duration-200 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Bildirimler
                        </button>
                        <button type="button" data-tab="portal"
                            class="tab-button flex items-center gap-2 border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 transition-all duration-200 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0 3-4.03 3-9s-1.343-9-3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            Portal Entegrasyonları
                        </button>
                        <button type="button" data-tab="fiyat"
                            class="tab-button flex items-center gap-2 border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 transition-all duration-200 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Fiyatlandırma
                        </button>
                        <button type="button" data-tab="qrcode"
                            class="tab-button flex items-center gap-2 border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 transition-all duration-200 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                            QR Kod
                        </button>
                        <button type="button" data-tab="navigation"
                            class="tab-button flex items-center gap-2 border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 transition-all duration-200 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            Navigasyon
                        </button>
                        <button type="button" data-tab="kullanici"
                            class="tab-button flex items-center gap-2 border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 transition-all duration-200 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Kullanıcı Yönetimi
                        </button>
                        <button type="button" data-tab="diller"
                            class="tab-button flex items-center gap-2 border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 transition-all duration-200 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 11.37 9.188 15.287 5.748 18.839"/>
                            </svg>
                            Diller
                        </button>
                        <button type="button" data-tab="paralar"
                            class="tab-button flex items-center gap-2 border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 transition-all duration-200 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Para Birimleri
                        </button>
                    </nav>
                </div>

                {{-- Tab Content Container --}}
                <div class="p-6">
                    {{-- Genel Tab --}}
                    <div id="genel" class="tab-content">
                        @include('admin.settings.sections.general')
                    </div>

                    {{-- Bildirim Tab --}}
                    <div id="bildirim" class="tab-content hidden">
                        @include('admin.settings.sections.notifications')
                    </div>

                    {{-- Portal Tab --}}
                    <div id="portal" class="tab-content hidden">
                        @include('admin.settings.sections.portals')
                    </div>

                    {{-- Fiyat Tab --}}
                    <div id="fiyat" class="tab-content hidden">
                        @include('admin.settings.sections.pricing')
                    </div>

                    {{-- QR Code Tab --}}
                    <div id="qrcode" class="tab-content hidden">
                        @include('admin.settings.sections.qrcode')
                    </div>

                    {{-- Navigation Tab --}}
                    <div id="navigation" class="tab-content hidden">
                        @include('admin.settings.sections.navigation')
                    </div>

                    {{-- Kullanici Tab --}}
                    <div id="kullanici" class="tab-content hidden">
                        @include('admin.settings.sections.users')
                    </div>

                    {{-- Diller Tab --}}
                    <div id="diller" class="tab-content hidden">
                        @include('admin.settings.sections.languages')
                    </div>

                    {{-- Paralar Tab --}}
                    <div id="paralar" class="tab-content hidden">
                        @include('admin.settings.sections.currencies')
                    </div>

                    {{-- Form Actions --}}
                    <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Değişiklikleri kaydetmek için aşağıdaki butona tıklayın
                        </p>
                        <button type="submit" :disabled="saving"
                            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-3 text-sm font-medium text-white shadow-md transition-all duration-200 hover:from-blue-700 hover:to-blue-800 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-95 disabled:opacity-50 dark:shadow-none">
                            <svg x-show="!saving" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg x-show="saving" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            <span x-text="saving ? 'Kaydediliyor...' : 'Tüm Değişiklikleri Kaydet'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Modern Tab Navigation
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            const mobileSelect = document.getElementById('mobile-tab-select');

            // Unified tab switching function
            function switchToTab(targetTab) {
                // Update buttons
                tabButtons.forEach(btn => {
                    if (btn.getAttribute('data-tab') === targetTab) {
                        btn.classList.add('active', 'border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                        btn.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                    } else {
                        btn.classList.remove('active', 'border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                        btn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                    }
                });

                // Update tab contents
                tabContents.forEach(content => {
                    if (content.id === targetTab) {
                        content.classList.remove('hidden');
                    } else {
                        content.classList.add('hidden');
                    }
                });

                // Update mobile select if exists
                if (mobileSelect) {
                    mobileSelect.value = targetTab;
                }
            }

            // Desktop button click handlers
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    switchToTab(targetTab);
                });
            });

            // Mobile dropdown handler
            if (mobileSelect) {
                mobileSelect.addEventListener('change', function() {
                    switchToTab(this.value);
                });
            }

            // Check URL hash on load (support #notifications and #bildirim)
            const hash = window.location.hash.replace('#', '');
            if (hash) {
                const targetTab = (hash === 'notifications') ? 'bildirim' : hash;
                switchToTab(targetTab);
            }
        });

        // Toggle Password Visibility
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;
            
            const button = input.nextElementSibling;
            if (!button) return;
            
            const svg = button.querySelector('svg');
            if (!svg) return;

            if (input.type === 'password') {
                input.type = 'text';
                svg.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                `;
            } else {
                input.type = 'password';
                svg.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                `;
            }
        }

        // Reset to Defaults
        function resetToDefaults() {
            // TODO: Implement reset functionality
        }

        // Form Validation
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            const siteTitle = document.getElementById('site_title');
            if (siteTitle && !siteTitle.value.trim()) {
                e.preventDefault();
                alert('Site başlığı zorunludur.');
                siteTitle.focus();
                // Reset saving state
                this.dispatchEvent(new CustomEvent('reset-saving'));
                return false;
            }
        });
    </script>
@endsection
