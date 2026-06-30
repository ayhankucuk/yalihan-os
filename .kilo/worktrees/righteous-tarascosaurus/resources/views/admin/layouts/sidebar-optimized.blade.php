<aside class="w-64 h-screen bg-slate-900 text-white flex flex-col flex-shrink-0" x-data="sidebarMenu()"
    x-init="init()">
    <div class="p-6 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded flex items-center justify-center">
                <span class="text-white font-bold">A</span>
            </div>
            <div class="min-w-0">
                <h2 class="font-semibold truncate">Admin Panel</h2>
                <p class="text-sm text-slate-400 truncate">Yalıhan Emlak</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-4 py-6 overflow-y-auto">
        <div class="mb-4">
            <label for="sidebar-search" class="sr-only">Menüde ara</label>
            <input id="sidebar-search" x-model.debounce.200ms="query" type="text" placeholder="Menüde ara..."
                class="w-full h-9 px-3 rounded-lg bg-slate-800 placeholder:text-slate-400 text-slate-100 border border-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200"
                autocomplete="off" maxlength="50" pattern="[a-zA-ZğüşıöçĞÜŞİÖÇ0-9\s\-_]+"
                title="Sadece harf, rakam ve temel karakterler kullanın" />
        </div>

        <!-- Lazy Loading: Menü öğeleri sadece görünür olduğunda yüklenir -->
        <!-- Şimdilik mevcut sidebar içeriği kullanılıyor (fallback) -->
        <div x-show="!loading" x-transition>
            @include('admin.layouts.sidebar-content')
        </div>

        <!-- Loading State (Şimdilik kullanılmıyor - mevcut sidebar kullanılıyor) -->
        <div x-show="loading" class="flex items-center justify-center py-8" style="display: none;">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <span class="ml-3 text-slate-400 text-sm">Menü yükleniyor...</span>
        </div>
    </nav>

    <!-- User Dropdown sidebar-content.blade.php içinde zaten var -->
</aside>

<script>
    function sidebarMenu() {
        return {
            query: '',
            loading: false,
            menuItems: [],
            useLazyLoading: false, // Şimdilik false - mevcut sidebar kullanılıyor

            async init() {
                // Şimdilik lazy loading kapalı - mevcut sidebar kullanılıyor
                // Gelecekte aktifleştirilebilir
                if (this.useLazyLoading) {
                    // Lazy loading: Menü öğeleri sadece görünür olduğunda yüklenir
                    if (this.isVisible()) {
                        await this.loadMenuItems();
                    } else {
                        // Intersection Observer ile görünür olduğunda yükle
                        const observer = new IntersectionObserver((entries) => {
                            if (entries[0].isIntersecting) {
                                this.loadMenuItems();
                                observer.disconnect();
                            }
                        }, {
                            threshold: 0.1
                        });
                        observer.observe(this.$el);
                    }
                } else {
                    // Mevcut sidebar kullanılıyor - loading state'i göster
                    this.loading = false;
                }
            },

            isVisible() {
                // Viewport'ta görünür mü kontrol et
                const rect = this.$el.getBoundingClientRect();
                return rect.top < window.innerHeight && rect.bottom > 0;
            },

            async loadMenuItems() {
                this.loading = true;
                try {
                    // Menü öğelerini API'den yükle
                    const response = await fetch('/api/v1/admin/menu-items', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();

                    // ResponseService formatı: { success: true, data: [...] }
                    if (result.success && result.data) {
                        this.menuItems = result.data;
                    } else {
                        throw new Error('Invalid response format');
                    }
                } catch (error) {
                    console.error('Menü yüklenirken hata:', error);
                    // Fallback: Mevcut sidebar kullanılıyor (include edilmiş)
                    this.menuItems = [];
                } finally {
                    this.loading = false;
                }
            },

            get filteredMenuItems() {
                if (!this.query) return this.menuItems;
                return this.menuItems.filter(item =>
                    item.text?.toLowerCase().includes(this.query.toLowerCase())
                );
            }
        }
    }
</script>
