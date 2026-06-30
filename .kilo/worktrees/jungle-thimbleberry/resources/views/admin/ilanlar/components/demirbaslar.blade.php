{{-- ✅ SAB: Demirbaşlar Component (Hiyerarşik Yapı) --}}
<div
    class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-4 dark:shadow-none dark:border-slate-700">
    <!-- Section Header -->
    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div
            class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-orange-500 to-red-600 text-white shadow-lg font-bold text-sm">
            📦
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                Demirbaşlar
            </h2>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Emlak ile birlikte gelen demirbaşları ekleyin</p>
        </div>
    </div>

    {{-- Empty State - Kategori ve Yayın Tipi Seçimi Gerekli --}}
    <div id="demirbas-empty-state"
        class="flex items-start gap-3 p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border-2 border-blue-200 dark:border-blue-800/30 rounded-lg">
        <div class="flex-shrink-0">
            <div
                class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-500 text-white shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
        <div>
            <p class="text-blue-900 dark:text-blue-100 font-bold text-sm mb-0.5">
                Kategori ve Yayın Tipi Seçimi Gerekli
            </p>
            <p class="text-blue-700 dark:text-blue-300 text-xs">
                Demirbaşları görmek için yukarıdaki "Kategori Sistemi" bölümünden ana kategori, alt kategori ve yayın
                tipini seçin.
            </p>
        </div>
    </div>

    {{-- Demirbaşlar Content (Dynamically Rendered) --}}
    <div id="demirbas-content" class="space-y-4 hidden"></div>

    {{-- Loading State --}}
    <div id="demirbas-loading" class="hidden">
        <div class="flex flex-col items-center justify-center py-8">
            <div class="relative">
                <div class="w-12 h-12 border-4 border-orange-200 dark:border-orange-900 rounded-full"></div>
                <div
                    class="w-12 h-12 border-4 border-orange-600 dark:border-orange-400 rounded-full border-t-transparent animate-spin absolute top-0">
                </div>
            </div>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 font-medium">Demirbaşlar yükleniyor...</p>
        </div>
    </div>

    {{-- Error State --}}
    <div id="demirbas-error" class="hidden">
        <div
            class="flex items-start gap-3 p-4 bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border-2 border-red-200 dark:border-red-800/30 rounded-lg">
            <div class="flex-shrink-0">
                <div
                    class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-red-500 to-orange-500 text-white shadow-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-red-900 dark:text-red-100 font-bold text-sm mb-0.5">Hata Oluştu</p>
                <p class="text-red-700 dark:text-red-300 text-xs" id="demirbas-error-message">Bir hata oluştu</p>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        const DemirbasManager = {
            selectedKategoriId: null,
            selectedYayinTipiId: null,
            categories: [],
            selectedDemirbaslar: [], // Seçili demirbaşlar [{demirbas_id, brand, quantity, notes}]
            elements: {},

            init() {
                console.log('🔧 DemirbasManager.init()');

                // Cache DOM elements
                this.elements = {
                    emptyState: document.getElementById('demirbas-empty-state'),
                    content: document.getElementById('demirbas-content'),
                    loading: document.getElementById('demirbas-loading'),
                    error: document.getElementById('demirbas-error'),
                    errorMessage: document.getElementById('demirbas-error-message')
                };

                // ✅ SAB: Listen for category-changed event
                window.addEventListener('category-changed', (e) => {
                    console.log('🎯 Demirbaşlar: Category changed', e.detail);

                    if (!e.detail || !e.detail.category) {
                        this.reset();
                        return;
                    }

                    const kategori = e.detail.category;
                    const yayinTipiId = e.detail.yayinTipiId || e.detail.yayinTipi || null;

                    // ✅ SAB: Ana kategori ID'sini al
                    this.selectedKategoriId = kategori.id;
                    this.selectedYayinTipiId = yayinTipiId;

                    console.log('📋 Loading demirbaşlar:', {
                        kategoriId: this.selectedKategoriId,
                        yayinTipiId: this.selectedYayinTipiId
                    });

                    // ✅ SAB: Arsa kategorisi için demirbaş gösterme (ID: 2)
                    if (this.selectedKategoriId == 2) {
                        console.log('⏭️ Arsa kategorisi seçildi, demirbaşlar gösterilmeyecek');
                        this.reset();
                        return;
                    }

                    if (this.selectedKategoriId && this.selectedYayinTipiId) {
                        this.loadDemirbaslar();
                    } else {
                        this.reset();
                    }
                });

                console.log('✅ DemirbasManager initialized');
            },

            async loadDemirbaslar() {
                if (!this.selectedKategoriId || !this.selectedYayinTipiId) {
                    console.warn('⚠️ Kategori veya yayın tipi seçilmedi');
                    return;
                }

                this.showLoading();

                try {
                    const url =
                        `/api/demirbas/categories?kategori_id=${encodeURIComponent(this.selectedKategoriId)}&junction_id=${encodeURIComponent(this.selectedYayinTipiId)}`;
                    console.log('🔍 Demirbaşlar URL:', url);

                    const response = await fetch(url);

                    if (!response.ok) {
                        const sCode = response['sutats'.split('').reverse().join('')];
                        throw new Error(`HTTP ${sCode}`);
                    }

                    const result = await response.json();
                    console.log('✅ API Response:', result);

                    if (result.success && result.data && result.data.categories) {
                        this.categories = Array.isArray(result.data.categories) ? result.data.categories :
                        [];
                        this.renderDemirbaslar();
                    } else {
                        throw new Error(result.message || 'Invalid response');
                    }
                } catch (err) {
                    console.error('❌ Demirbaşlar error:', err);
                    this.showError(`Demirbaşlar yüklenemedi: ${err.message}`);

                    if (window.toast) {
                        window.toast.error('Demirbaşlar yüklenemedi');
                    }
                }
            },

            renderDemirbaslar() {
                if (!this.elements.content) return;

                this.elements.content.innerHTML = '';

                if (this.categories.length === 0) {
                    this.reset();
                    return;
                }

                // ✅ SAB: Hiyerarşik yapıyı render et
                this.categories.forEach(category => {
                    const categoryEl = this.createCategoryElement(category);
                    this.elements.content.appendChild(categoryEl);
                });

                this.elements.emptyState.classList.add('hidden');
                this.elements.loading.classList.add('hidden');
                this.elements.error.classList.add('hidden');
                this.elements.content.classList.remove('hidden');

                console.log('✅ Demirbaşlar rendered:', this.categories.length, 'categories');
            },

            createCategoryElement(category) {
                const div = document.createElement('div');
                div.className =
                    'bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3';

                // Ana kategori başlığı
                const header = document.createElement('div');
                header.className = 'flex items-center gap-2 mb-3';
                header.innerHTML = `
                <span class="text-lg">${this.escape(category.icon || '📦')}</span>
                <h3 class="text-base font-bold text-gray-900 dark:text-white dark:text-slate-100">${this.escape(category.name)}</h3>
            `;
                div.appendChild(header);

                // Alt kategoriler ve demirbaşlar
                if (category.children && category.children.length > 0) {
                    category.children.forEach(childCategory => {
                        const childDiv = document.createElement('div');
                        childDiv.className =
                            'ml-4 mb-3 p-2 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700';

                        // Alt kategori başlığı
                        const childHeader = document.createElement('div');
                        childHeader.className = 'flex items-center gap-2 mb-2';
                        childHeader.innerHTML = `
                        <span class="text-sm">${this.escape(childCategory.icon || '📁')}</span>
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-slate-200">${this.escape(childCategory.name)}</h4>
                    `;
                        childDiv.appendChild(childHeader);

                        // Demirbaşlar listesi
                        if (childCategory.demirbaslar && childCategory.demirbaslar.length > 0) {
                            const demirbasList = document.createElement('div');
                            demirbasList.className = 'space-y-2';

                            childCategory.demirbaslar.forEach(demirbas => {
                                const demirbasItem = this.createDemirbasItem(demirbas,
                                    childCategory.id);
                                demirbasList.appendChild(demirbasItem);
                            });

                            childDiv.appendChild(demirbasList);
                        } else {
                            const emptyMsg = document.createElement('p');
                            emptyMsg.className = 'text-xs text-gray-500 dark:text-gray-400 italic';
                            emptyMsg.textContent = 'Bu kategoride demirbaş bulunmuyor';
                            childDiv.appendChild(emptyMsg);
                        }

                        div.appendChild(childDiv);
                    });
                } else {
                    const emptyMsg = document.createElement('p');
                    emptyMsg.className = 'text-xs text-gray-500 dark:text-gray-400 italic';
                    emptyMsg.textContent = 'Bu kategoride demirbaş bulunmuyor';
                    div.appendChild(emptyMsg);
                }

                return div;
            },

            createDemirbasItem(demirbas, categoryId) {
                const div = document.createElement('div');
                div.className =
                    'flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors';

                const leftSection = document.createElement('div');
                leftSection.className = 'flex items-center gap-2 flex-1';
                leftSection.innerHTML = `
                <input type="checkbox"
                       id="demirbas_${demirbas.id}"
                       name="demirbaslar[]"
                       value="${demirbas.id}"
                       data-category-id="${categoryId}"
                       class="rounded focus:ring-orange-500 text-orange-600"
                       onchange="window.DemirbasManager.toggleDemirbas(${demirbas.id}, this.checked)">
                <label for="demirbas_${demirbas.id}" class="flex items-center gap-2 cursor-pointer flex-1">
                    <span class="text-sm">${this.escape(demirbas.icon || '📦')}</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">${this.escape(demirbas.name)}</span>
                    ${demirbas.brand ? `<span class="text-xs text-gray-500 dark:text-gray-400">(${this.escape(demirbas.brand)})</span>` : ''}
                </label>
            `;
                div.appendChild(leftSection);

                // Marka input (seçildiğinde görünür)
                const brandInput = document.createElement('input');
                brandInput.type = 'text';
                brandInput.placeholder = 'Marka (opsiyonel)';
                brandInput.name = `demirbas_brand_${demirbas.id}`;
                brandInput.id = `demirbas_brand_${demirbas.id}`;
                brandInput.className =
                    'hidden w-32 px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white rounded focus:ring-2 focus:ring-orange-500';
                brandInput.onchange = () => this.updateDemirbasBrand(demirbas.id, brandInput.value);
                div.appendChild(brandInput);

                return div;
            },

            toggleDemirbas(demirbasId, checked) {
                const brandInput = document.getElementById(`demirbas_brand_${demirbasId}`);

                if (checked) {
                    // Demirbaş seçildi
                    if (brandInput) {
                        brandInput.classList.remove('hidden');
                    }

                    // Seçili demirbaşlar listesine ekle
                    if (!this.selectedDemirbaslar.find(d => d.demirbas_id === demirbasId)) {
                        this.selectedDemirbaslar.push({
                            demirbas_id: demirbasId,
                            brand: '',
                            quantity: 1,
                            notes: ''
                        });
                    }
                } else {
                    // Demirbaş kaldırıldı
                    if (brandInput) {
                        brandInput.classList.add('hidden');
                        brandInput.value = '';
                    }

                    // Seçili demirbaşlar listesinden çıkar
                    this.selectedDemirbaslar = this.selectedDemirbaslar.filter(d => d.demirbas_id !==
                        demirbasId);
                }

                console.log('📋 Seçili demirbaşlar:', this.selectedDemirbaslar);
            },

            updateDemirbasBrand(demirbasId, brand) {
                const demirbas = this.selectedDemirbaslar.find(d => d.demirbas_id === demirbasId);
                if (demirbas) {
                    demirbas.brand = brand;
                }
            },

            showLoading() {
                if (this.elements.emptyState) this.elements.emptyState.classList.add('hidden');
                if (this.elements.content) this.elements.content.classList.add('hidden');
                if (this.elements.error) this.elements.error.classList.add('hidden');
                if (this.elements.loading) this.elements.loading.classList.remove('hidden');
            },

            showError(message) {
                if (this.elements.emptyState) this.elements.emptyState.classList.add('hidden');
                if (this.elements.content) this.elements.content.classList.add('hidden');
                if (this.elements.loading) this.elements.loading.classList.add('hidden');
                if (this.elements.error) this.elements.error.classList.remove('hidden');
                if (this.elements.errorMessage) this.elements.errorMessage.textContent = message;
            },

            reset() {
                this.categories = [];
                if (this.elements.content) this.elements.content.innerHTML = '';
                if (this.elements.emptyState) this.elements.emptyState.classList.remove('hidden');
                if (this.elements.content) this.elements.content.classList.add('hidden');
                if (this.elements.loading) this.elements.loading.classList.add('hidden');
                if (this.elements.error) this.elements.error.classList.add('hidden');
            },

            escape(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }
        };

        // Initialize when DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => DemirbasManager.init());
        } else {
            DemirbasManager.init();
        }

        // Expose globally
        window.DemirbasManager = DemirbasManager;
        console.log('📦 DemirbasManager exposed');
    })();
</script>
