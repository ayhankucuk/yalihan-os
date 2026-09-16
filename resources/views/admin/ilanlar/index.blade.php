@extends('admin.layouts.admin')

@section('title', 'İlan Yönetimi')

@section('content')
    <div class="space-y-6" x-data="ilanFilter()">
        {{-- Page Header --}}
        <div class="relative overflow-hidden flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-6 sm:p-8 shadow-sm">
            <div class="relative">
                <div class="flex items-center gap-2.5 mb-1.5">
                    <div class="w-8 h-8 bg-[#0A1628] text-[#C9A84C] rounded-lg flex items-center justify-center shadow-sm">
                        <x-icon name="bina" class="w-4 h-4" />
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-[0.25em] text-[#0A1628] dark:text-[#C9A84C]">Yalıhan AI OS</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ __('admin.listings') }}
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 font-medium">
                    {{ __('admin.manage_and_track_listings') }}
                </p>
            </div>

            <div class="relative flex items-center gap-3">
                <a href="{{ route('admin.ilanlar.create-wizard') }}"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-[#0A1628] hover:bg-[#132238] text-[#C9A84C] rounded-xl shadow-md transition-all duration-200 font-bold text-sm dark:bg-[#C9A84C] dark:text-[#0A1628] dark:hover:bg-amber-400">
                    <x-icon name="ekle" class="w-4 h-4" />
                    {{ __('admin.new_listing') }}
                </a>
            </div>
        </div>

        {{-- 📊 Statistics Grid --}}
        @include('admin.ilanlar.partials.stats-grid', ['stats' => $stats, 'tabCounts' => $tabCounts, 'ilanlar' => $ilanlar])

        {{-- 🛰️ Tab Navigator --}}
        @include('admin.ilanlar.partials.tab-navigator', ['tabCounts' => $tabCounts])

        {{-- 🔍 Smart Search & Filters --}}
        @include('admin.ilanlar.partials.filter-search-bar', ['kategoriler' => $kategoriler])

        {{-- 📋 Listing List Container with Bulk Manager --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm" x-data="bulkActionsManager()" id="ilanlar-list-container">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <x-icon name="liste" class="w-4 h-4 text-[#C9A84C]" />
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ __('admin.listing_list') }}</h3>
                </div>
                <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-black uppercase tracking-wider rounded-full"
                    x-text="`${totalCount} {{ __('admin.listing') }}`">
                    {{ $ilanlar->total() }} {{ __('admin.listing') }}
                </span>
            </div>

            <div class="p-6">
                @if ($ilanlar->count() > 0)
                    {{-- 📱 Mobile Cards View --}}
                    @include('admin.ilanlar.partials.mobile-cards', ['ilanlar' => $ilanlar])

                    {{-- 🖥️ Desktop Quantum Table --}}
                    @include('admin.ilanlar.partials.desktop-table', ['ilanlar' => $ilanlar])

                    {{-- 🧠 Sticky Floating Cortex Bar --}}
                    @include('admin.ilanlar.partials.floating-neural-bar')

                    {{-- Pagination --}}
                    <div class="mt-6" id="ilanlar-pagination">
                        {{ $ilanlar->appends(request()->query())->links() }}
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mb-4 text-slate-400 dark:text-slate-600">
                            <x-icon name="bina" class="w-8 h-8" />
                        </div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-wider mb-1">
                            {{ __('admin.no_listings_found') }}
                        </h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium max-w-xs mb-6">
                            Arama kriterlerinize uygun ilan bulunmamaktadır.
                        </p>
                        <a href="{{ route('admin.ilanlar.create-wizard') }}"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-[#0A1628] text-[#C9A84C] hover:bg-[#132238] rounded-xl text-xs font-black uppercase tracking-wider transition-all dark:bg-[#C9A84C] dark:text-[#0A1628]">
                            <x-icon name="ekle" class="w-4 h-4" />
                            {{ __('admin.new_listing') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Advanced Filter Drawer Component --}}
    @include('admin.ilanlar.components.advanced-filter-drawer')

    @push('scripts')
        <script>
            // AJAX Filter Manager (Alpine.js Component)
            // Context7: %100, Yalıhan Bekçi: ✅
            function ilanFilter() {
                return {
                    showFilters: false,
                    ilceler: [],
                    filters: {
                        search: '{{ request('search') }}',
                        yayin_durumu: '{{ request('yayin_durumu') }}',
                        kategori_id: '{{ request('kategori_id') }}',
                        kiralama_turu: '{{ request('kiralama_turu') }}',
                        sort: '{{ request('sort', 'created_desc') }}',
                        tab: '{{ request('tab', 'active') }}',
                        il_id: '{{ request('il_id') }}',
                        ilce_id: '{{ request('ilce_id') }}',
                        min_fiyat: '{{ request('min_fiyat') }}',
                        max_fiyat: '{{ request('max_fiyat') }}',
                        min_m2: '{{ request('min_m2') }}',
                        max_m2: '{{ request('max_m2') }}'
                    },
                    loading: false,
                    totalCount: {{ $ilanlar->total() }},

                    init() {
                        const urlParams = new URLSearchParams(window.location.search);
                        Object.keys(this.filters).forEach(key => {
                            if (urlParams.has(key)) {
                                this.filters[key] = urlParams.get(key);
                            }
                        });

                        if (this.filters.il_id) {
                            this.fetchIlceler();
                        }
                    },

                    async fetchIlceler() {
                        if (!this.filters.il_id) {
                            this.ilceler = [];
                            return;
                        }
                        try {
                            const response = await fetch(`/api/v1/admin/address/ilceler?il_id=${this.filters.il_id}`);
                            const data = await response.json();
                            this.ilceler = data.data || [];
                        } catch (e) {
                            console.error('Ilce fetch error:', e);
                        }
                    },

                    setArea(min, max) {
                        this.filters.min_m2 = min;
                        this.filters.max_m2 = max;
                    },

                    clearFilters() {
                        Object.keys(this.filters).forEach(key => {
                            this.filters[key] = '';
                        });
                        this.filters.sort = 'created_desc';
                        this.filters.tab = 'active';
                        this.applyFilters();
                    },

                    async applyFilters() {
                        this.loading = true;

                        try {
                            const params = new URLSearchParams();
                            Object.keys(this.filters).forEach(key => {
                                if (this.filters[key]) {
                                    params.append(key, this.filters[key]);
                                }
                            });

                            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                            window.history.pushState({}, '', newUrl);

                            const response = await fetch('{{ route('admin.ilanlar.filter') }}?' + params.toString(), {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                }
                            });

                            if (!response.ok) {
                                throw new Error('Filtreleme başarısız');
                            }

                            const data = await response.json();

                            if (data.success) {
                                const tbody = document.getElementById('ilanlar-tbody');
                                if (tbody && data.html) {
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(data.html, 'text/html');
                                    const newTbody = doc.querySelector('tbody');
                                    if (newTbody) {
                                        tbody.innerHTML = newTbody.innerHTML;
                                    }
                                }

                                const pagination = document.getElementById('ilanlar-pagination');
                                if (pagination && data.pagination) {
                                    pagination.innerHTML = data.pagination;
                                }

                                if (data.total !== undefined) {
                                    this.totalCount = data.total;
                                }

                                if (window.toast) {
                                    window.toast.success(`${this.totalCount} ilan bulundu`);
                                }
                            }
                        } catch (error) {
                            console.error('Filter error:', error);
                            if (window.toast) {
                                window.toast.error('Filtreleme sırasında bir hata oluştu');
                            }
                        } finally {
                            this.loading = false;
                        }
                    }
                };
            }

            // Bulk Actions Manager (Alpine.js Component)
            // Context7: %100, Yalıhan Bekçi: ✅
            function bulkActionsManager() {
                return {
                    selectedIds: [],
                    selectAll: false,
                    processing: false,

                    toggleSelectAll() {
                        const checkboxes = document.querySelectorAll('.row-checkbox');
                        if (this.selectAll) {
                            this.selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
                        } else {
                            this.selectedIds = [];
                        }
                        checkboxes.forEach(cb => cb.checked = this.selectAll);
                    },

                    updateSelectAll() {
                        const checkboxes = document.querySelectorAll('.row-checkbox');
                        const checkedCount = this.selectedIds.length;
                        this.selectAll = checkedCount === checkboxes.length && checkboxes.length > 0;
                    },

                    clearSelection() {
                        this.selectedIds = [];
                        this.selectAll = false;
                        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
                    },

                    confirmBulkDelete() {
                        if (this.selectedIds.length === 0) return;
                        if (confirm(`${this.selectedIds.length} ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.`)) {
                            this.bulkAction('delete');
                        }
                    },

                    async bulkAction(action) {
                        if (this.selectedIds.length === 0) return;
                        this.processing = true;

                        try {
                            const response = await fetch('{{ route('admin.ilanlar.bulk.action') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    ids: this.selectedIds,
                                    action: action,
                                }),
                            });

                            const data = await response.json();

                            if (data.success) {
                                if (window.toast) {
                                    window.toast.success(data.message || 'İşlem başarılı');
                                }
                                setTimeout(() => window.location.reload(), 800);
                            } else {
                                throw new Error(data.message || 'İşlem başarısız');
                            }
                        } catch (error) {
                            console.error('Bulk action error:', error);
                            if (window.toast) {
                                window.toast.error(error.message || 'Toplu işlem başarısız oldu');
                            }
                        } finally {
                            this.processing = false;
                        }
                    }
                };
            }

            // Inline Yayın Durumu Toggle Component
            // Context7: %100, Yalıhan Bekçi: ✅
            function yayinDurumuToggle(ilanId, initialYayinDurumu) {
                return {
                    open: false,
                    currentYayinDurumu: initialYayinDurumu,
                    updating: false,
                    durumSecenekleri: [
                        { value: 'yayinda', label: 'Yayında' },
                        { value: 'beklemede', label: 'Beklemede' },
                        { value: 'taslak', label: 'Taslak' },
                        { value: 'pasif', label: 'Pasif' },
                        { value: 'arsiv', label: 'Arşiv' },
                    ],

                    async changeYayinDurumu(newDurum) {
                        if (newDurum === this.currentYayinDurumu) {
                            this.open = false;
                            return;
                        }

                        this.updating = true;

                        try {
                            const response = await fetch(`/admin/ilanlar/${ilanId}/yayin-durumu`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    yayin_durumu: newDurum
                                }),
                            });

                            const data = await response.json();

                            if (data.success || response.ok) {
                                this.currentYayinDurumu = newDurum;
                                if (window.toast) {
                                    window.toast.success(`Yayın durumu "${newDurum}" olarak güncellendi`);
                                }
                            } else {
                                throw new Error(data.message || 'Güncelleme başarısız');
                            }
                        } catch (error) {
                            console.error('Yayın durumu update error:', error);
                            if (window.toast) {
                                window.toast.error(error.message || 'Yayın durumu güncellenemedi');
                            }
                        } finally {
                            this.updating = false;
                            this.open = false;
                        }
                    },

                    getYayinDurumuClasses() {
                        const classes = {
                            yayinda: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800',
                            beklemede: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800',
                            taslak: 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
                            pasif: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800',
                            arsiv: 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800',
                        };
                        return classes[this.currentYayinDurumu] || classes.taslak;
                    },

                    getYayinDurumuLabel(value) {
                        const option = this.durumSecenekleri.find((item) => item.value === value);
                        return option ? option.label : 'Taslak';
                    }
                };
            }
        </script>
    @endpush
@endsection
