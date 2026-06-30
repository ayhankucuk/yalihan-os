@extends('admin.layouts.admin')

@section('title', 'İlanlarım')

@section('content')
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 p-4 md:p-6 transition-colors duration-300 dark:bg-slate-900" x-data="myListingsManager()">
        <div class="max-w-[1600px] mx-auto space-y-4 animate-fade-in">
            <!-- Header Component -->
            @include('admin.ilanlar.components.header')

            <!-- Statistics Cards Component -->
            @include('admin.ilanlar.components.stats-cards', ['stats' => $stats])

            <!-- Filter Panel Component (Precision) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm transition-all duration-300 dark:shadow-none">
                 @include('admin.ilanlar.components.filter-panel')
            </div>

            <!-- AI Quick Actions Component -->
            @include('admin.ilanlar.components.ai-quick-actions')

            <!-- View Mode & Main Content -->
            <div class="space-y-4">
                @include('admin.ilanlar.components.view-mode-toggle')

                <!-- Listings Table Component -->
                <div class="animate-slide-up bg-transparent">
                    <x-admin.ilanlar.listings-table :listings="$listings ?? []" />
                </div>
            </div>

            <!-- Pagination (Premium Styling) -->
            @if (isset($listings) && $listings->hasPages())
                <div class="mt-8 flex justify-center">
                    <div class="inline-flex items-center gap-1 p-1 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-none">
                        {{ $listings->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/my-listings-search.js'])
@endpush

@push('styles')
    <style>
        /* Modern Dashboard Styles */
        .stat-card {
            @apply text-center p-6 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200;
        }

        .stat-value {
            @apply text-3xl font-bold mb-2;
        }

        .stat-label {
            @apply text-gray-600 dark:text-gray-400 font-medium;
        }
    </style>
@endpush

@push('scripts')
    <script>
        function refreshListings() {
            location.reload();
        }

        // ✅ AJAX Filter Implementation (No Page Reload) - Context7: Loading state eklendi
        async function applyFilters() {
            const yayinDurumu = document.getElementById('aktiflik-durumu-filter').value;
            const category = document.getElementById('category-filter').value;
            const search = document.getElementById('search-input').value;

            const filterButton = document.getElementById('filter-button');
            const filterSpinner = document.getElementById('filter-spinner');
            const filterIcon = document.getElementById('filter-icon');
            const filterText = document.getElementById('filter-text');

            filterButton.disabled = true;
            filterSpinner.classList.remove('hidden');
            filterIcon.classList.add('hidden');
            filterText.textContent = 'Filtreleniyor...';

            try {
                const tbody = document.getElementById('listings-table-body');
                const gbody = document.getElementById('listings-grid-body');

                const loadingHtml = '<tr><td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400"><svg class="animate-spin h-8 w-8 mx-auto mb-2 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Yükleniyor...</td></tr>';
                if(tbody) tbody.innerHTML = loadingHtml;
                if(gbody) gbody.innerHTML = '<div class="col-span-full py-12 text-center text-gray-400 italic">Yükleniyor...</div>';

                const response = await fetch('{{ route('admin.ilanlarim.search') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        yayin_durumu: yayinDurumu,
                        category,
                        search
                    })
                });

                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();

                if (data.success) {
                    updateListingsContent(data.data);
                    window.toast?.success('Filtreleme başarılı');
                } else {
                    throw new Error(data.message || 'Filtreleme başarısız');
                }
            } catch (error) {
                console.error('Filter error:', error);
                window.toast?.error('Filtreleme başarısız');
                location.reload();
            } finally {
                filterButton.disabled = false;
                filterSpinner.classList.add('hidden');
                filterIcon.classList.remove('hidden');
                filterText.textContent = 'Filtrele';
            }
        }

        function updateListingsContent(paginatedData) {
            const tbody = document.getElementById('listings-table-body');
            const gbody = document.getElementById('listings-grid-body');

            if(tbody) tbody.innerHTML = '';
            if(gbody) gbody.innerHTML = '';

            if (!paginatedData.data || paginatedData.data.length === 0) {
                const emptyHtml = `<tr><td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">İlan bulunamadı</td></tr>`;
                if(tbody) tbody.innerHTML = emptyHtml;
                if(gbody) gbody.innerHTML = '<div class="col-span-full py-12 text-center text-gray-500 italic">İlan bulunamadı</div>';
                return;
            }

            paginatedData.data.forEach((listing) => {
                const normalizedListing = normalizeListing(listing);
                if(tbody) tbody.innerHTML += createListingRowHtml(normalizedListing);
                if(gbody) gbody.innerHTML += createListingGridHtml(normalizedListing);
            });

            updatePagination(paginatedData);
        }

        function normalizeListing(listing) {
            return {
                id: listing.id,
                baslik: listing.baslik || '',
                fiyat: listing.fiyat ?? listing.price ?? 0,
                para_birimi: listing.para_birimi ?? listing.currency ?? '',
                yayin_durumu: listing.yayin_durumu ?? listing.aktiflik_durumu ?? '',
                alt_kategori: listing.alt_kategori ?? listing.kategori ?? null,
                fotograflar: Array.isArray(listing.fotograflar) ? listing.fotograflar : [],
                churn_risk: listing.churn_risk ?? null,
            };
        }

        function createListingRowHtml(listing) {
            const price = new Intl.NumberFormat('tr-TR').format(listing.fiyat || 0);
            const categoryName = listing.alt_kategori?.name || '—';
            const yayinDurumu = listing.yayin_durumu || 'Taslak';

            let statusBadge = `<span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase ${yayinDurumu === 'Aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'}"><span class="w-1 h-1 rounded-full bg-current"></span>${yayinDurumu}</span>`;

            const image = listing.fotograflar?.[0]?.dosya_yolu ? `/storage/${listing.fotograflar[0].dosya_yolu}` : '/images/default-property.jpg';

            return `
                <tr class="group hover:bg-slate-100 dark:hover:bg-slate-900 border-b border-slate-50 dark:border-slate-900 transition-all duration-200">
                    <td class="px-4 py-3"><input type="checkbox" class="row-checkbox rounded border-slate-300 dark:border-white/10" value="${listing.id}" x-model="selectedIds" @change="updateSelectAll()"></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img class="h-10 w-10 rounded-lg object-cover border border-slate-200 dark:border-white/10" src="${image}">
                            <div class="space-y-0.5">
                                <div class="text-sm font-semibold text-slate-800 dark:text-white">${listing.baslik?.substring(0, 35)}</div>
                                <div class="text-[10px] text-slate-400">#${listing.id}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-[10px] font-bold text-slate-600 dark:text-gray-400">${categoryName}</td>
                    <td class="px-4 py-3">${statusBadge}</td>
                    <td class="px-4 py-3">-</td>
                    <td class="px-4 py-3 text-sm font-bold">${price} ${listing.para_birimi}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-1.5 opacity-40 group-hover:opacity-100 transition-opacity">
                            <a href="/admin/ilanlar/${listing.id}/edit" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></a>
                        </div>
                    </td>
                </tr>
            `;
        }

        function createListingGridHtml(listing) {
            const price = new Intl.NumberFormat('tr-TR').format(listing.fiyat || 0);
            const image = listing.fotograflar?.[0]?.dosya_yolu ? `/storage/${listing.fotograflar[0].dosya_yolu}` : '/images/default-property.jpg';

            return `
                <div class="group bg-white dark:bg-gray-950 rounded-xl border border-slate-200 dark:border-white/10 overflow-hidden shadow-sm dark:shadow-none hover:shadow-md transition-all duration-300 dark:bg-slate-900">
                    <div class="relative h-40 overflow-hidden">
                        <img class="w-full h-full object-cover group-hover:scale-105 transition-transform" src="${image}">
                        <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-3 left-3 right-3 flex justify-between items-end text-white">
                            <div>
                                <div class="text-sm font-bold">${price} <span class="text-[10px]">${listing.para_birimi}</span></div>
                                <div class="text-[10px] opacity-80">${listing.alt_kategori?.name || ''}</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 space-y-3">
                        <h3 class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-snug line-clamp-2 h-8">${listing.baslik}</h3>
                        <div class="flex items-center gap-2">
                            <a href="/admin/ilanlar/${listing.id}/edit" class="flex-1 py-1.5 bg-indigo-600 dark:bg-indigo-700 hover:bg-indigo-700 dark:hover:bg-indigo-600 text-white text-[10px] font-bold rounded-lg text-center transition-all">DÜZENLE</a>
                            <a href="/admin/ilanlar/${listing.id}" class="flex-1 py-1.5 bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-gray-700 text-slate-700 dark:text-slate-200 text-[10px] font-bold rounded-lg text-center transition-all">DETAY GÖR</a>
                        </div>
                    </div>
                </div>
            `;
        }


        function updatePagination(paginatedData) {
            // TODO: Implement pagination update if needed
            // For now, we'll just log it
            console.log('Pagination:', {
                current_page: paginatedData.current_page,
                last_page: paginatedData.last_page,
                total: paginatedData.total
            });
        }

        // Enter tuşu ile arama
        document.getElementById('search-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        // ✅ Export Excel loading state
        document.getElementById('export-excel-btn')?.addEventListener('click', function(e) {
            const btn = this;
            const icon = document.getElementById('export-excel-icon');
            const spinner = document.getElementById('export-excel-spinner');
            const text = document.getElementById('export-excel-text');

            // Loading state
            btn.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
            btn.style.pointerEvents = 'none';
            icon.classList.add('hidden');
            spinner.classList.remove('hidden');
            text.textContent = 'İndiriliyor...';

            // 10 saniye sonra otomatik olarak geri dön (fallback)
            setTimeout(() => {
                btn.classList.remove('disabled:opacity-50', 'disabled:cursor-not-allowed');
                btn.style.pointerEvents = '';
                icon.classList.remove('hidden');
                spinner.classList.add('hidden');
                text.textContent = 'Excel İndir';
            }, 10000);
        });

        // Kopyalama fonksiyonu
        function copyToClipboard(text, label) {
            navigator.clipboard.writeText(text).then(function() {
                window.toast?.success(`${label} kopyalandı: ${text}`);
            }, function(err) {
                console.error('Kopyalama hatası:', err);
                window.toast?.error('Kopyalama başarısız');
            });
        }

        // Ref numarası oluştur
        async function generateRef(ilanId) {
            try {
                const response = await fetch('/api/reference/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        ilan_id: ilanId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.toast?.success('Ref numarası oluşturuldu');
                    location.reload(); // Sayfayı yenile
                } else {
                    throw new Error(data.message || 'Ref numarası oluşturulamadı');
                }
            } catch (error) {
                console.error('Ref oluşturma hatası:', error);
                window.toast?.error('Ref numarası oluşturulamadı: ' + error.message);
            }
        }

        // My Listings Manager (Alpine.js Component)
        function myListingsManager() {
            return {
                viewMode: 'table', // 'table' or 'grid'
                selectedIds: [],
                selectAll: false,
                processing: false,

                init() {
                    // Load view mode from localStorage
                    const savedViewMode = localStorage.getItem('my-listings-view-mode');
                    if (savedViewMode) {
                        this.viewMode = savedViewMode;
                    }

                    // Watch view mode changes and save to localStorage
                    this.$watch('viewMode', (value) => {
                        localStorage.setItem('my-listings-view-mode', value);
                    });
                },

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

                async bulkAction(action) {
                    if (this.selectedIds.length === 0) {
                        window.toast?.error('Lütfen en az bir ilan seçin');
                        return;
                    }

                    this.processing = true;
                    try {
                        const response = await fetch('{{ route('admin.ilanlarim.bulk.action') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                action: action,
                                ids: this.selectedIds
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            window.toast?.success(data.message);
                            location.reload();
                        } else {
                            throw new Error(data.message || 'İşlem başarısız');
                        }
                    } catch (error) {
                        console.error('Bulk action error:', error);
                        window.toast?.error('İşlem başarısız: ' + error.message);
                    } finally {
                        this.processing = false;
                    }
                },

                confirmBulkDelete() {
                    if (this.selectedIds.length === 0) {
                        window.toast?.error('Lütfen en az bir ilan seçin');
                        return;
                    }

                    if (confirm(
                            `${this.selectedIds.length} ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.`)) {
                        this.bulkAction('delete');
                    }
                },

                quickEdit(ilanId) {
                    window.location.href = `/admin/ilanlar/${ilanId}/edit`;
                },

                async duplicateListing(ilanId) {
                    if (confirm('Bu ilanı kopyalamak istediğinize emin misiniz?')) {
                        try {
                            const response = await fetch(`{{ route('admin.ilanlar.duplicate', ['ilan' => ':id']) }}`
                                .replace(':id', ilanId), {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                        'Accept': 'application/json'
                                    }
                                });

                            const data = await response.json();
                            if (data.success) {
                                window.toast?.success('İlan kopyalandı');
                                if (data.redirect_url) {
                                    window.location.href = data.redirect_url;
                                } else {
                                    location.reload();
                                }
                            } else {
                                throw new Error(data.message || 'Kopyalama başarısız');
                            }
                        } catch (error) {
                            console.error('Duplicate error:', error);
                            window.toast?.error('Kopyalama başarısız: ' + error.message);
                        }
                    }
                },

                quickPreview(ilanId) {
                    window.open(`/admin/ilanlar/${ilanId}`, '_blank');
                }
            };
        }

        // AI Quick Actions for My Listings (Alpine.js Component)
        function aiQuickActionsMyListings() {
            return {
                processing: false,
                selectedIds: [],
                results: null,
                showResults: false,

                get selectedIds() {
                    const manager = Alpine.$data(document.querySelector('[x-data*="myListingsManager"]'));
                    return manager?.selectedIds || [];
                },

                async analyzeListings(type = 'comprehensive') {
                    const ids = this.selectedIds;
                    if (ids.length === 0) {
                        if (window.toast) {
                            window.toast.error('Lütfen en az bir ilan seçin');
                        }
                        return;
                    }

                    this.processing = true;
                    this.showResults = false;

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch('{{ route('admin.ai.bulk-analyze') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ilan_ids: ids,
                                type: type
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Analiz başarısız');
                        }

                        const data = await response.json();
                        this.results = data.results;
                        this.showResults = true;

                        if (window.toast) {
                            window.toast.success(`${data.count} ilan analiz edildi`);
                        }
                    } catch (error) {
                        console.error('AI Analysis error:', error);
                        if (window.toast) {
                            window.toast.error('AI analiz sırasında bir hata oluştu');
                        }
                    } finally {
                        this.processing = false;
                    }
                },

                async suggestPrices() {
                    await this.analyzeListings('price');
                },

                async optimizeTitles() {
                    await this.analyzeListings('title');
                }
            };
        }
    </script>
@endpush
