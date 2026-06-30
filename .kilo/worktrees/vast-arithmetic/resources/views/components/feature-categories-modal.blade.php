{{-- ========================================
     FEATURE CATEGORIES MODAL COMPONENT
     Revy.com.tr tarzı kategori bazlı özellik seçimi
     ======================================== --}}

@props([
    'ilanId' => null,
    'selectedFeatures' => [],
])

<div x-data="featureCategoriesModal({{ json_encode([
    'ilanId' => $ilanId,
    'selectedFeatures' => $selectedFeatures,
]) }})"
     x-init="loadCategories()"
     class="feature-categories-modal">

    {{-- Feature Categories Sections --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {{-- İç Özellikleri --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">İç Özellikleri</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">ADSL, Asansör, Balkon...</p>
                    </div>
                </div>
                <x-feature-modal-selector
                    category-slug="ic-ozellikleri"
                    :selected-features="collect($selectedFeatures)->where('categorySlug', 'ic-ozellikleri')->values()->all()"
                    :ilan-id="$ilanId"
                    @features-selected="handleFeaturesSelected($event.detail)" />
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="feature in getSelectedByCategory('ic-ozellikleri')" :key="feature.id">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200">
                        <span x-text="feature.name"></span>
                    </span>
                </template>
            </div>
        </div>

        {{-- Dış Özellikleri --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">Dış Özellikleri</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Bahçe, Otopark, Güvenlik...</p>
                    </div>
                </div>
                <x-feature-modal-selector
                    category-slug="dis-ozellikleri"
                    :selected-features="collect($selectedFeatures)->where('categorySlug', 'dis-ozellikleri')->values()->all()"
                    :ilan-id="$ilanId"
                    @features-selected="handleFeaturesSelected($event.detail)" />
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="feature in getSelectedByCategory('dis-ozellikleri')" :key="feature.id">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                        <span x-text="feature.name"></span>
                    </span>
                </template>
            </div>
        </div>

        {{-- Muhit --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">Muhit</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Çevre, Sosyal alanlar...</p>
                    </div>
                </div>
                <x-feature-modal-selector
                    category-slug="muhit"
                    :selected-features="collect($selectedFeatures)->where('categorySlug', 'muhit')->values()->all()"
                    :ilan-id="$ilanId"
                    @features-selected="handleFeaturesSelected($event.detail)" />
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="feature in getSelectedByCategory('muhit')" :key="feature.id">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-200">
                        <span x-text="feature.name"></span>
                    </span>
                </template>
            </div>
        </div>

        {{-- Ulaşım --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">Ulaşım</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Metro, Otobüs, İstasyon...</p>
                    </div>
                </div>
                <x-feature-modal-selector
                    category-slug="ulasim"
                    :selected-features="collect($selectedFeatures)->where('categorySlug', 'ulasim')->values()->all()"
                    :ilan-id="$ilanId"
                    @features-selected="handleFeaturesSelected($event.detail)" />
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="feature in getSelectedByCategory('ulasim')" :key="feature.id">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200">
                        <span x-text="feature.name"></span>
                    </span>
                </template>
            </div>
        </div>

        {{-- Cephe --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">Cephe</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Kuzey, Güney, Doğu, Batı...</p>
                    </div>
                </div>
                <x-feature-modal-selector
                    category-slug="cephe"
                    :selected-features="collect($selectedFeatures)->where('categorySlug', 'cephe')->values()->all()"
                    :ilan-id="$ilanId"
                    @features-selected="handleFeaturesSelected($event.detail)" />
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="feature in getSelectedByCategory('cephe')" :key="feature.id">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-pink-100 dark:bg-pink-900/30 text-pink-800 dark:text-pink-200">
                        <span x-text="feature.name"></span>
                    </span>
                </template>
            </div>
        </div>

        {{-- Manzara --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">Manzara</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Deniz, Dağ, Boğaz, Park...</p>
                    </div>
                </div>
                <x-feature-modal-selector
                    category-slug="manzara"
                    :selected-features="collect($selectedFeatures)->where('categorySlug', 'manzara')->values()->all()"
                    :ilan-id="$ilanId"
                    @features-selected="handleFeaturesSelected($event.detail)" />
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="feature in getSelectedByCategory('manzara')" :key="feature.id">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-200">
                        <span x-text="feature.name"></span>
                    </span>
                </template>
            </div>
        </div>
    </div>

    {{-- Demirbaşlar (sadece kiralık ve ilanId mevcutsa) --}}
    @php($ilan = $ilanId ? \App\Models\Ilan::find($ilanId) : null)
    @if($ilan && ($ilan->ilan_turu ?? null) === 'kiralik')
        <div class="mt-6">
            <x-fixtures-manager :fixtures="[]" :ilan-id="$ilanId" />
        </div>
    @endif

    {{-- Hidden Inputs for Form Submission --}}
    <template x-for="(feature, index) in allSelectedFeatures" :key="index">
        <input type="hidden"
               :name="`features[${index}][id]`"
               :value="feature.id">
        <input type="hidden"
               :name="`features[${index}][category_slug]`"
               :value="feature.categorySlug">
    </template>
</div>

<script>
function featureCategoriesModal(config) {
    return {
        ilanId: config.ilanId || null,
        selectedFeatures: config.selectedFeatures || [],
        allSelectedFeatures: [],
        categories: [],

        init() {
            // Initialize selected features by category
            this.allSelectedFeatures = this.selectedFeatures || [];
        },

        async loadCategories() {
            try {
                const response = await fetch('/api/admin/features/categories');
                const data = await response.json();
                if (data.success) {
                    this.categories = data.categories || [];
                }
            } catch (error) {
                console.error('Categories load error:', error);
            }
        },

        getSelectedByCategory(categorySlug) {
            return this.allSelectedFeatures.filter(f => f.categorySlug === categorySlug);
        },

        handleFeaturesSelected(detail) {
            // Remove old features for this category
            this.allSelectedFeatures = this.allSelectedFeatures.filter(
                f => f.categorySlug !== detail.categorySlug
            );

            // Add new features
            const newFeatures = detail.features.map(f => ({
                ...f,
                categorySlug: detail.categorySlug
            }));
            this.allSelectedFeatures = [...this.allSelectedFeatures, ...newFeatures];

            // Dispatch event
            this.$dispatch('all-features-updated', {
                features: this.allSelectedFeatures
            });
        }
    }
}
</script>
