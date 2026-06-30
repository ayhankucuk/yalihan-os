{{-- ========================================
     FEATURE MODAL SELECTOR COMPONENT
     Revy.com.tr tarzı modal tabanlı özellik seçimi
     ======================================== --}}

@props([
    'category' => null,
    'categorySlug' => null,
    'selectedFeatures' => [],
    'ilanId' => null,
])

<div x-data="featureModalSelector({{ json_encode([
    'category' => $category,
    'categorySlug' => $categorySlug ?? ($category ? $category->slug : null),
    'selectedFeatures' => $selectedFeatures,
    'ilanId' => $ilanId,
]) }})"
     x-init="loadFeatures()"
     class="feature-modal-selector">

    {{-- Modal Trigger Button --}}
    <button type="button"
            @click="openModal()"
            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-md hover:shadow-lg transition-all duration-200 hover:scale-110 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:shadow-none">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

    {{-- Modal Overlay --}}
    <div x-show="isOpen"
         x-cloak
         @click.away="closeModal()"
         @keydown.escape.window="closeModal()"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity duration-300"
             :class="isOpen ? 'opacity-100' : 'opacity-0'"></div>

        {{-- Modal Container --}}
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="relative inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full"
                 @click.stop
                 :class="isOpen ? 'scale-100 opacity-100' : 'scale-95 opacity-0'">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                             :style="`background: linear-gradient(135deg, ${categoryColor}, ${categoryColor}dd);`">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100" x-text="categoryName"></h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Çoklu seçim yapabilirsiniz</p>
                        </div>
                    </div>
                    <button type="button"
                            @click="closeModal()"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="px-6 py-6 max-h-[60vh] overflow-y-auto">
                    {{-- Loading State --}}
                    <div x-show="loading" class="text-center py-12">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">Özellikler yükleniyor...</p>
                    </div>

                    {{-- Error State --}}
                    <div x-show="error && !loading" class="text-center py-12">
                        <div class="text-red-500 mb-2">
                            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-red-600 dark:text-red-400" x-text="error"></p>
                    </div>

                    {{-- Features Grid --}}
                    <div x-show="!loading && !error" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <template x-for="feature in features" :key="feature.id">
                            <label class="flex items-center p-4 rounded-lg border-2 cursor-pointer transition-all duration-200 hover:shadow-md"
                                   :class="isSelected(feature.id)
                                       ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                       : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                <input type="checkbox"
                                       :value="feature.id"
                                       :checked="isSelected(feature.id)"
                                       @change="toggleFeature(feature.id)"
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200">
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100"
                                      x-text="feature.name"></span>
                            </label>
                        </template>

                        {{-- Empty State --}}
                        <div x-show="features.length === 0 && !loading"
                             class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                            <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p>Bu kategoride özellik bulunmamaktadır.</p>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-gray-900/50 rounded-b-2xl dark:border-slate-700 dark:bg-slate-900">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold" x-text="selectedCount"></span> özellik seçildi
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button"
                                @click="closeModal()"
                                class="px-4 py-2 text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 dark:text-slate-300">
                            İptal
                        </button>
                        <button type="button"
                                @click="saveFeatures()"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:shadow-none">
                            Kaydet
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Selected Features Display --}}
    <div x-show="displaySelectedFeatures.length > 0"
         class="mt-3 flex flex-wrap gap-2"
         x-cloak>
        <template x-for="feature in displaySelectedFeatures" :key="feature.id">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200">
                <span x-text="feature.name"></span>
                <button type="button"
                        @click="removeFeature(feature.id)"
                        class="ml-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </span>
        </template>
    </div>
</div>

<script>
function featureModalSelector(config) {
    return {
        category: config.category || null,
        categorySlug: config.categorySlug || null,
        selectedFeatures: config.selectedFeatures || [],
        ilanId: config.ilanId || null,
        isOpen: false,
        loading: false,
        error: null,
        features: [],
        selectedIds: new Set(config.selectedFeatures.map(f => f.id || f)),

        get categoryName() {
            return this.category?.name || 'Özellikler';
        },

        get categoryColor() {
            const colors = {
                'ic-ozellikleri': '#3B82F6',
                'dis-ozellikleri': '#10B981',
                'muhit': '#F59E0B',
                'ulasim': '#8B5CF6',
                'cephe': '#EC4899',
                'manzara': '#06B6D4',
            };
            return colors[this.categorySlug] || '#6366F1';
        },

        get selectedCount() {
            return this.selectedIds.size;
        },

        get displaySelectedFeatures() {
            return this.features.filter(f => this.selectedIds.has(f.id));
        },

        async loadFeatures() {
            if (!this.categorySlug) return;

            this.loading = true;
            this.error = null;

            try {
                let anaSlug = '';
                const anaSel = document.getElementById('ana_kategori');
                if (anaSel && anaSel.value) {
                    const opt = anaSel.options[anaSel.selectedIndex];
                    anaSlug = opt?.getAttribute('data-slug') || '';
                }
                const yayinTipi = document.getElementById('yayin_tipi_id')?.value || '';
                const list = await (window.featuresSystem ? window.featuresSystem.loadFeatures(anaSlug, this.categorySlug, yayinTipi) : Promise.resolve([]));
                this.features = list;
                if (this.selectedFeatures.length > 0) {
                    this.selectedIds = new Set(this.selectedFeatures.map(f => f.id || f));
                }
            } catch (error) {
                this.error = 'Özellikler yüklenirken bir hata oluştu.';
            } finally {
                this.loading = false;
            }
        },

        openModal() {
            this.isOpen = true;
            if (this.features.length === 0) {
                this.loadFeatures();
            }
        },

        closeModal() {
            this.isOpen = false;
        },

        isSelected(featureId) {
            return this.selectedIds.has(featureId);
        },

        toggleFeature(featureId) {
            if (this.selectedIds.has(featureId)) {
                this.selectedIds.delete(featureId);
            } else {
                this.selectedIds.add(featureId);
            }
        },

        removeFeature(featureId) {
            this.selectedIds.delete(featureId);
            this.saveFeatures();
        },

        async saveFeatures() {
            const selectedFeaturesData = Array.from(this.selectedIds).map(id => {
                const feature = this.features.find(f => f.id === id);
                return feature ? { id: feature.id, name: feature.name } : { id };
            });

            // Dispatch event to parent
            this.$dispatch('features-selected', {
                categorySlug: this.categorySlug,
                features: selectedFeaturesData
            });

            this.closeModal();
        }
    }
}
</script>

<style>
[x-cloak] {
    display: none !important;
}
</style>
