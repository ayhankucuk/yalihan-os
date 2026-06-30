{{--
    Property Hub Keyboard Shortcuts Component

    Usage: @include('admin.property-hub.partials.keyboard-shortcuts')

    Shortcuts:
    - Ctrl/Cmd + K: Quick search
    - Ctrl/Cmd + N: New feature
    - Ctrl/Cmd + S: Save (in edit forms)
    - Ctrl/Cmd + /: Show shortcuts help
    - Escape: Close modals
    - Arrow keys: Navigate lists
--}}

<div x-data="keyboardShortcuts()" x-init="init()" @keydown.window="handleKeydown($event)">
    {{-- Shortcuts Help Modal --}}
    <div x-show="showHelp" x-cloak x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
        @click.self="showHelp = false" @keydown.escape.window="showHelp = false">

        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-lg w-full p-6"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                    <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Klavye Kısayolları
                </h3>
                <button @click="showHelp = false"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                {{-- Navigation --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Navigasyon</h4>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-1">
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Hızlı Arama</span>
                            <kbd
                                class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-gray-600 dark:bg-slate-900">
                                <span x-text="isMac ? '⌘' : 'Ctrl'"></span> + K
                            </kbd>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Ana Sayfa</span>
                            <kbd
                                class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-gray-600 dark:bg-slate-900">
                                G sonra H
                            </kbd>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Özellikler</span>
                            <kbd
                                class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-gray-600 dark:bg-slate-900">
                                G sonra F
                            </kbd>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Şablonlar</span>
                            <kbd
                                class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-gray-600 dark:bg-slate-900">
                                G sonra T
                            </kbd>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        İşlemler</h4>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-1">
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Yeni Özellik</span>
                            <kbd
                                class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-gray-600 dark:bg-slate-900">
                                <span x-text="isMac ? '⌘' : 'Ctrl'"></span> + N
                            </kbd>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Kaydet</span>
                            <kbd
                                class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-gray-600 dark:bg-slate-900">
                                <span x-text="isMac ? '⌘' : 'Ctrl'"></span> + S
                            </kbd>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Modalı Kapat</span>
                            <kbd
                                class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-gray-600 dark:bg-slate-900">
                                Esc
                            </kbd>
                        </div>
                    </div>
                </div>

                {{-- Help --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Yardım</h4>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-1">
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Kısayolları Göster</span>
                            <kbd
                                class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-gray-600 dark:bg-slate-900">
                                <span x-text="isMac ? '⌘' : 'Ctrl'"></span> + /
                            </kbd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                    💡 Kısayollar sadece input alanları dışında çalışır
                </p>
            </div>
        </div>
    </div>

    {{-- Quick Search Modal (Command Palette) --}}
    <div x-show="showSearch" x-cloak x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-start justify-center pt-[15vh] p-4 bg-gray-900/50 backdrop-blur-sm"
        @click.self="showSearch = false" @keydown.escape.window="showSearch = false">

        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0">

            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" x-ref="searchInput" x-model="searchQuery" @input="search()"
                    placeholder="Özellik, şablon veya sayfa ara..."
                    class="flex-1 border-0 bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 text-lg dark:text-slate-100">
                <kbd
                    class="px-2 py-1 text-xs font-semibold text-gray-400 bg-gray-100 dark:bg-gray-700 rounded dark:bg-slate-900">Esc</kbd>
            </div>

            <div class="max-h-[400px] overflow-y-auto">
                {{-- Quick Actions --}}
                <div x-show="!searchQuery" class="p-2">
                    <div
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 py-2">
                        Hızlı İşlemler</div>
                    <button @click="navigate('/admin/property-hub/features/create'); showSearch = false"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
                        <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Yeni Özellik Oluştur</span>
                    </button>
                    <button @click="navigate('/admin/property-hub/templates'); showSearch = false"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
                        <svg class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                        </svg>
                        <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Şablonları Yönet</span>
                    </button>
                    <button @click="navigate('/admin/property-hub/analytics'); showSearch = false"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
                        <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Analytics Dashboard</span>
                    </button>
                </div>

                {{-- Search Results --}}
                <div x-show="searchQuery && searchResults.length > 0" class="p-2">
                    <div
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 py-2">
                        Sonuçlar (<span x-text="searchResults.length"></span>)
                    </div>
                    <template x-for="(result, index) in searchResults" :key="index">
                        <button @click="navigate(result.url); showSearch = false"
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 text-left">
                            <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg"
                                :class="result.type === 'feature' ? 'bg-blue-100 dark:bg-blue-900/30' :
                                    'bg-purple-100 dark:bg-purple-900/30'">
                                <svg x-show="result.type === 'feature'"
                                    class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <svg x-show="result.type === 'template'"
                                    class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z" />
                                </svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 dark:text-white truncate dark:text-slate-100" x-text="result.name">
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 truncate"
                                    x-text="result.description"></div>
                            </div>
                        </button>
                    </template>
                </div>

                {{-- No Results --}}
                <div x-show="searchQuery && searchResults.length === 0"
                    class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p>Sonuç bulunamadı</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function keyboardShortcuts() {
        return {
            showHelp: false,
            showSearch: false,
            searchQuery: '',
            searchResults: [],
            isMac: false,
            lastKey: null,
            lastKeyTime: 0,

            init() {
                this.isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            },

            handleKeydown(event) {
                // Ignore if typing in input
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) {
                    return;
                }

                const modKey = this.isMac ? event.metaKey : event.ctrlKey;

                // Ctrl/Cmd + K: Quick search
                if (modKey && event.key === 'k') {
                    event.preventDefault();
                    this.showSearch = true;
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                    return;
                }

                // Ctrl/Cmd + N: New feature
                if (modKey && event.key === 'n') {
                    event.preventDefault();
                    this.navigate('/admin/property-hub/features/create');
                    return;
                }

                // Ctrl/Cmd + S: Save (let forms handle this)
                if (modKey && event.key === 's') {
                    // Don't prevent default, let form handle it
                    return;
                }

                // Ctrl/Cmd + /: Show help
                if (modKey && event.key === '/') {
                    event.preventDefault();
                    this.showHelp = true;
                    return;
                }

                // G + key navigation (vim-style)
                const now = Date.now();
                if (this.lastKey === 'g' && (now - this.lastKeyTime) < 500) {
                    switch (event.key) {
                        case 'h':
                            event.preventDefault();
                            this.navigate('/admin/property-hub');
                            break;
                        case 'f':
                            event.preventDefault();
                            this.navigate('/admin/property-hub/features');
                            break;
                        case 't':
                            event.preventDefault();
                            this.navigate('/admin/property-hub/templates');
                            break;
                        case 'a':
                            event.preventDefault();
                            this.navigate('/admin/property-hub/analytics');
                            break;
                    }
                    this.lastKey = null;
                    return;
                }

                this.lastKey = event.key;
                this.lastKeyTime = now;
            },

            async search() {
                if (!this.searchQuery || this.searchQuery.length < 2) {
                    this.searchResults = [];
                    return;
                }

                try {
                    const response = await fetch(
                        `/admin/property-hub/search?q=${encodeURIComponent(this.searchQuery)}`);
                    if (response.ok) {
                        const data = await response.json();
                        this.searchResults = data.results || [];
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    this.searchResults = [];
                }
            },

            navigate(url) {
                window.location.href = url;
            }
        };
    }
</script>
