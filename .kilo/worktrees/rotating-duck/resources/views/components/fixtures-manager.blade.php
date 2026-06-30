{{-- ========================================
     FIXTURES MANAGER COMPONENT
     Demirbaşlar yönetimi - Revy.com.tr tarzı
     ======================================== --}}

@props([
    'fixtures' => [],
    'ilanId' => null,
])

<div x-data="fixturesManager({{ json_encode([
    'fixtures' => $fixtures,
    'ilanId' => $ilanId,
]) }})"
     class="fixtures-manager">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Demirbaşlar</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Emlak ile birlikte gelen demirbaşları ekleyin</p>
        </div>
        <button type="button"
                @click="openModal()"
                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-md hover:shadow-lg transition-all duration-200 hover:scale-110 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:shadow-none">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </div>

    {{-- Fixtures List --}}
    <div class="space-y-2">
        <template x-for="(fixture, index) in fixtures" :key="index">
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="flex-1">
                    <div class="font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="fixture.name"></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400" x-text="fixture.brand || 'Marka belirtilmemiş'"></div>
                </div>
                <button type="button"
                        @click="removeFixture(index)"
                        class="ml-4 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </template>

        {{-- Empty State --}}
        <div x-show="fixtures.length === 0"
             class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p>Henüz demirbaş eklenmemiş</p>
        </div>
    </div>

    {{-- Add Fixture Modal --}}
    <div x-show="isModalOpen"
         x-cloak
         @click.away="closeModal()"
         @keydown.escape.window="closeModal()"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity duration-300"></div>

        {{-- Modal Container --}}
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="relative inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full"
                 @click.stop>

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">DEMİRBAŞLAR</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Yeni demirbaş ekleyin</p>
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
                <div class="px-6 py-6">
                    <div class="space-y-4">
                        {{-- Demirbaş Adı --}}
                        <div>
                            <label for="fixture-name"
                                   class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Demirbaş Adı <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="fixture-name"
                                   x-model="newFixture.name"
                                   placeholder="Örn: Buzdolabı, Çamaşır Makinesi..."
                                   class="w-full px-4 py-2 border-2 border-yellow-400 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all duration-200 dark:text-slate-100">
                            <p x-show="errors.name"
                               x-text="errors.name"
                               class="mt-1 text-sm text-red-600 dark:text-red-400"></p>
                        </div>

                        {{-- Marka --}}
                        <div>
                            <label for="fixture-brand"
                                   class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Markası
                            </label>
                            <input type="text"
                                   id="fixture-brand"
                                   x-model="newFixture.brand"
                                   placeholder="Örn: Arçelik, Beko..."
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100">
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-gray-900/50 rounded-b-2xl dark:border-slate-700 dark:bg-slate-900">
                    <button type="button"
                            @click="closeModal()"
                            class="px-4 py-2 text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 dark:text-slate-300">
                        İptal
                    </button>
                    <button type="button"
                            @click="addFixture()"
                            class="inline-flex items-center gap-2 px-6 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-yellow-500 focus:outline-none dark:shadow-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ekle
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden Inputs for Form Submission --}}
    <template x-for="(fixture, index) in fixtures" :key="index">
        <input type="hidden"
               :name="`fixtures[${index}][name]`"
               :value="fixture.name">
        <input type="hidden"
               :name="`fixtures[${index}][brand]`"
               :value="fixture.brand || ''">
    </template>
</div>

<script>
function fixturesManager(config) {
    return {
        fixtures: config.fixtures || [],
        ilanId: config.ilanId || null,
        isModalOpen: false,
        newFixture: {
            name: '',
            brand: ''
        },
        errors: {},

        openModal() {
            this.isModalOpen = true;
            this.newFixture = { name: '', brand: '' };
            this.errors = {};
        },

        closeModal() {
            this.isModalOpen = false;
            this.newFixture = { name: '', brand: '' };
            this.errors = {};
        },

        addFixture() {
            // Validation
            this.errors = {};
            if (!this.newFixture.name || this.newFixture.name.trim() === '') {
                this.errors.name = 'Demirbaş adı gereklidir';
                return;
            }

            // Add fixture
            this.fixtures.push({
                name: this.newFixture.name.trim(),
                brand: this.newFixture.brand.trim() || null
            });

            // Dispatch event
            this.$dispatch('fixtures-updated', {
                fixtures: this.fixtures
            });

            this.closeModal();
        },

        removeFixture(index) {
            if (confirm('Bu demirbaşı silmek istediğinize emin misiniz?')) {
                this.fixtures.splice(index, 1);

                // Dispatch event
                this.$dispatch('fixtures-updated', {
                    fixtures: this.fixtures
                });
            }
        }
    }
}
</script>

<style>
[x-cloak] {
    display: none !important;
}
</style>
