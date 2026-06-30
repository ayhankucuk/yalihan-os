{{--
    Onboarding Wizard Partial for Property Hub

    Context7 Compliant:
    - Uses aktiflik_durumu (NOT status)
    - Uses display_order (NOT order)

    Usage:
    @include('admin.property-hub.partials.onboarding-wizard', ['kategori' => $kategori])
--}}

<div x-data="onboardingWizard()" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-show="isOpen"
    @keydown.escape.window="close()">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" x-show="isOpen"
        x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="close()">
    </div>

    {{-- Modal Panel --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-4xl transform rounded-2xl bg-white dark:bg-slate-900 shadow-2xl transition-all"
            x-show="isOpen" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95" @click.stop>

            {{-- Header --}}
            <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Özellik Yapılandırma
                                Sihirbazı</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="categoryName"></p>
                        </div>
                    </div>
                    <button @click="close()"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700 transition-all duration-200">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                {{-- Progress Steps --}}
                <div class="mt-6">
                    <div class="flex items-center justify-between">
                        <template x-for="(step, index) in steps" :key="index">
                            <div class="flex items-center" :class="index < steps.length - 1 ? 'flex-1' : ''">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium transition-all duration-200"
                                        :class="{
                                            'bg-blue-600 text-white': currentStep === index,
                                            'bg-green-500 text-white': currentStep > index,
                                            'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400': currentStep <
                                                index
                                        }">
                                        <template x-if="currentStep > index">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </template>
                                        <template x-if="currentStep <= index">
                                            <span x-text="index + 1"></span>
                                        </template>
                                    </div>
                                    <span class="text-sm font-medium hidden sm:block"
                                        :class="{
                                            'text-blue-600 dark:text-blue-400': currentStep === index,
                                            'text-green-600 dark:text-green-400': currentStep > index,
                                            'text-gray-500 dark:text-gray-400': currentStep < index
                                        }"
                                        x-text="step.title">
                                    </span>
                                </div>
                                <div x-show="index < steps.length - 1"
                                    class="mx-4 h-0.5 flex-1 transition-all duration-200"
                                    :class="currentStep > index ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Content --}}
            <div class="p-6 min-h-[400px]">
                {{-- Step 1: Template Seçimi --}}
                <div x-show="currentStep === 0" x-transition>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Şablon Seçin</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Hazır bir şablonla hızlıca başlayın veya sıfırdan oluşturun.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {{-- No Template Option --}}
                        <div @click="selectTemplate(null)"
                            class="relative cursor-pointer rounded-xl border-2 p-4 transition-all duration-200 hover:shadow-md"
                            :class="selectedTemplate === null ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' :
                                'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                            <div class="flex items-center gap-3 mb-2">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 dark:bg-slate-900">
                                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Sıfırdan Başla</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tüm özellikleri manuel olarak seçin</p>
                        </div>

                        {{-- Template Options --}}
                        <template x-for="template in templates" :key="template.id">
                            <div @click="selectTemplate(template)"
                                class="relative cursor-pointer rounded-xl border-2 p-4 transition-all duration-200 hover:shadow-md"
                                :class="selectedTemplate?.id === template.id ?
                                    'border-blue-500 bg-blue-50 dark:bg-blue-900/20' :
                                    'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                                <div class="flex items-center gap-3 mb-2">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                                        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                        </svg>
                                    </div>
                                    <span class="font-medium text-gray-900 dark:text-white dark:text-slate-100"
                                        x-text="template.name"></span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="template.description || `${template.feature_count} özellik içerir`"></p>
                                <div class="mt-2 flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-600 dark:text-gray-400 dark:bg-slate-900">
                                        <span x-text="template.feature_count"></span>
                                        <span class="ml-1">özellik</span>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Step 2: AI Önerileri --}}
                <div x-show="currentStep === 1" x-transition>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">AI Önerileri</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Benzer kategorilerden önerilen özellikleri inceleyin ve ekleyin.
                    </p>

                    <div x-show="loadingSuggestions" class="flex items-center justify-center py-12">
                        <svg class="animate-spin h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>

                    <div x-show="!loadingSuggestions" class="space-y-3">
                        <template x-for="suggestion in suggestions" :key="suggestion.id">
                            <div
                                class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-slate-800 p-4 transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-750 dark:border-slate-700">
                                <div class="flex items-center gap-4">
                                    <input type="checkbox" :id="'suggestion-' + suggestion.id"
                                        :checked="selectedFeatures.includes(suggestion.id)"
                                        @change="toggleFeature(suggestion.id)"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <label :for="'suggestion-' + suggestion.id"
                                            class="font-medium text-gray-900 dark:text-white cursor-pointer dark:text-slate-100"
                                            x-text="suggestion.name"></label>
                                        <p class="text-sm text-gray-500 dark:text-gray-400"
                                            x-text="suggestion.reason"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium px-2 py-1 rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': suggestion
                                                .confidence >= 80,
                                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': suggestion
                                                .confidence >= 50 && suggestion.confidence < 80,
                                            'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': suggestion
                                                .confidence < 50
                                        }">
                                        %<span x-text="suggestion.confidence"></span> güven
                                    </span>
                                </div>
                            </div>
                        </template>

                        <div x-show="suggestions.length === 0"
                            class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <p class="mt-2">Öneri bulunamadı</p>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Manuel Seçim --}}
                <div x-show="currentStep === 2" x-transition>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Ek Özellikler</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Listeden eklemek istediğiniz özellikleri seçin.
                    </p>

                    {{-- Search --}}
                    <div class="mb-4">
                        <input type="text" x-model="featureSearch" placeholder="Özellik ara..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                    </div>

                    {{-- Feature Groups --}}
                    <div class="space-y-4 max-h-[300px] overflow-y-auto">
                        <template x-for="group in filteredFeatureGroups" :key="group.id">
                            <div class="rounded-lg border border-gray-200 dark:border-slate-800 overflow-hidden dark:border-slate-700">
                                <button @click="toggleGroup(group.id)"
                                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-750 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 dark:bg-slate-900">
                                    <span class="font-medium text-gray-900 dark:text-white dark:text-slate-100"
                                        x-text="group.name"></span>
                                    <svg class="h-5 w-5 text-gray-500 transition-transform duration-200"
                                        :class="expandedGroups.includes(group.id) ? 'rotate-180' : ''" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="expandedGroups.includes(group.id)" x-collapse>
                                    <div class="p-4 space-y-2">
                                        <template x-for="feature in group.features" :key="feature.id">
                                            <label class="flex items-center gap-3 cursor-pointer py-1">
                                                <input type="checkbox"
                                                    :checked="selectedFeatures.includes(feature.id)"
                                                    @change="toggleFeature(feature.id)"
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300"
                                                    x-text="feature.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Step 4: Özet --}}
                <div x-show="currentStep === 3" x-transition>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Özet</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Seçimlerinizi gözden geçirin ve uygulayın.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Selected Features --}}
                        <div class="rounded-lg border border-gray-200 dark:border-slate-800 p-4 dark:border-slate-700">
                            <div class="flex items-center justify-between mb-4">
                                <h5 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Seçilen Özellikler</h5>
                                <span class="text-sm text-gray-500" x-text="selectedFeatures.length + ' adet'"></span>
                            </div>
                            <div class="space-y-2 max-h-[200px] overflow-y-auto">
                                <template x-for="featureId in selectedFeatures" :key="featureId">
                                    <div class="flex items-center justify-between py-1">
                                        <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300"
                                            x-text="getFeatureName(featureId)"></span>
                                        <button @click="toggleFeature(featureId)"
                                            class="text-red-500 hover:text-red-700 transition-all duration-200">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <div x-show="selectedFeatures.length === 0" class="text-center py-4 text-gray-500">
                                    Henüz özellik seçilmedi
                                </div>
                            </div>
                        </div>

                        {{-- Configuration --}}
                        <div class="rounded-lg border border-gray-200 dark:border-slate-800 p-4 dark:border-slate-700">
                            <h5 class="font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Yapılandırma</h5>
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Uygulama
                                        Modu</label>
                                    <select x-model="applyMode"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-blue-500">
                                        <option value="merge">Birleştir (mevcut özellikleri koru)</option>
                                        <option value="replace">Değiştir (mevcut özellikleri kaldır)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="saveAsTemplate"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Bu seçimi şablon olarak
                                            kaydet</span>
                                    </label>
                                </div>
                                <div x-show="saveAsTemplate" class="pl-6">
                                    <input type="text" x-model="templateName" placeholder="Şablon adı..."
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="border-t border-gray-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between dark:border-slate-700">
                <button @click="prevStep()" x-show="currentStep > 0"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-200 hover:text-gray-900 dark:hover:text-white transition-all duration-200 dark:text-slate-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Geri
                </button>
                <div x-show="currentStep === 0"></div>

                <div class="flex items-center gap-3">
                    <button @click="close()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-200 hover:text-gray-900 dark:hover:text-white transition-all duration-200 dark:text-slate-300">
                        İptal
                    </button>
                    <button @click="nextStep()" x-show="currentStep < steps.length - 1"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200">
                        Devam
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <button @click="apply()" x-show="currentStep === steps.length - 1"
                        :disabled="applying || selectedFeatures.length === 0"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="applying" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span x-text="applying ? 'Uygulanıyor...' : 'Uygula'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function onboardingWizard() {
        return {
            isOpen: false,
            currentStep: 0,
            categoryId: null,
            categoryName: '',

            steps: [{
                    title: 'Şablon'
                },
                {
                    title: 'AI Önerileri'
                },
                {
                    title: 'Manuel Seçim'
                },
                {
                    title: 'Özet'
                }
            ],

            // Step 1: Templates
            templates: [],
            selectedTemplate: null,

            // Step 2: AI Suggestions
            suggestions: [],
            loadingSuggestions: false,

            // Step 3: Manual Selection
            featureGroups: [],
            featureSearch: '',
            expandedGroups: [],

            // Shared
            selectedFeatures: [],
            allFeatures: {},

            // Step 4: Summary
            applyMode: 'merge',
            saveAsTemplate: false,
            templateName: '',
            applying: false,

            get filteredFeatureGroups() {
                if (!this.featureSearch) return this.featureGroups;
                const search = this.featureSearch.toLowerCase();
                return this.featureGroups.map(group => ({
                    ...group,
                    features: group.features.filter(f => f.name.toLowerCase().includes(search))
                })).filter(group => group.features.length > 0);
            },

            open(categoryId, categoryName) {
                this.categoryId = categoryId;
                this.categoryName = categoryName;
                this.currentStep = 0;
                this.selectedTemplate = null;
                this.selectedFeatures = [];
                this.isOpen = true;
                this.loadInitialData();
            },

            close() {
                this.isOpen = false;
            },

            async loadInitialData() {
                try {
                    // Load templates
                    const templatesRes = await fetch('/admin/property-hub/templates/list');
                    const templatesData = await templatesRes.json();
                    this.templates = templatesData.templates || [];

                    // Load all features grouped
                    const featuresRes = await fetch('/admin/property-hub/features/grouped');
                    const featuresData = await featuresRes.json();
                    this.featureGroups = featuresData.groups || [];

                    // Build features map
                    this.featureGroups.forEach(group => {
                        group.features.forEach(f => {
                            this.allFeatures[f.id] = f.name;
                        });
                    });
                } catch (error) {
                    console.error('Error loading wizard data:', error);
                }
            },

            async loadSuggestions() {
                this.loadingSuggestions = true;
                try {
                    const res = await fetch(`/admin/property-hub/suggestions?category_id=${this.categoryId}`);
                    const data = await res.json();
                    this.suggestions = data.suggestions || [];
                } catch (error) {
                    console.error('Error loading suggestions:', error);
                } finally {
                    this.loadingSuggestions = false;
                }
            },

            selectTemplate(template) {
                this.selectedTemplate = template;
                if (template && template.feature_ids) {
                    this.selectedFeatures = [...template.feature_ids];
                } else {
                    this.selectedFeatures = [];
                }
            },

            toggleFeature(featureId) {
                const index = this.selectedFeatures.indexOf(featureId);
                if (index > -1) {
                    this.selectedFeatures.splice(index, 1);
                } else {
                    this.selectedFeatures.push(featureId);
                }
            },

            toggleGroup(groupId) {
                const index = this.expandedGroups.indexOf(groupId);
                if (index > -1) {
                    this.expandedGroups.splice(index, 1);
                } else {
                    this.expandedGroups.push(groupId);
                }
            },

            getFeatureName(featureId) {
                return this.allFeatures[featureId] || `Özellik #${featureId}`;
            },

            prevStep() {
                if (this.currentStep > 0) {
                    this.currentStep--;
                }
            },

            nextStep() {
                if (this.currentStep === 1 && this.suggestions.length === 0) {
                    this.loadSuggestions();
                }
                if (this.currentStep < this.steps.length - 1) {
                    this.currentStep++;
                }
            },

            async apply() {
                if (this.applying || this.selectedFeatures.length === 0) return;

                this.applying = true;

                try {
                    const payload = {
                        category_id: this.categoryId,
                        feature_ids: this.selectedFeatures,
                        mode: this.applyMode,
                        save_as_template: this.saveAsTemplate,
                        template_name: this.templateName
                    };

                    const res = await fetch('/admin/property-hub/templates/apply-wizard', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json();

                    if (data.success) {
                        // Show success message and close
                        window.dispatchEvent(new CustomEvent('wizard-complete', {
                            detail: {
                                categoryId: this.categoryId,
                                featuresAdded: this.selectedFeatures.length
                            }
                        }));
                        this.close();
                    } else {
                        alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
                    }
                } catch (error) {
                    console.error('Error applying wizard:', error);
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                } finally {
                    this.applying = false;
                }
            }
        };
    }
</script>
