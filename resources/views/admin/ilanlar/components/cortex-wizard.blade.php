{{--
    🚀 CORTEX-ENHANCED DYNAMIC WIZARD
    
    Context7 Standardı: C7-DYNAMIC-WIZARD-2025-12-29
    Versiyon: 1.0.0 (Cortex-Nexus Integration)
    
    Dynamic category-based form with real-time Cortex quality scoring
    
    Usage:
    @include('admin.ilanlar.components.cortex-wizard')
--}}

<div x-data="cortexDynamicWizard()" class="cortex-wizard-container">
    {{-- Quality Score Bar (Real-time Cortex Feedback) - Minimized by default --}}
    <div x-show="kategoriSelected"
        class="fixed top-20 right-4 w-64 bg-white dark:bg-slate-900 rounded-lg shadow-lg border border-blue-500 p-3 z-30 transition-all duration-300"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-8"
        x-transition:enter-end="opacity-100 transform translate-x-0">

        {{-- Quality Score Header --}}
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                <i class="fas fa-brain text-blue-600 dark:text-blue-400 animate-pulse"></i>
                Cortex Kalite Skoru
            </h4>
            <span class="text-2xl font-black"
                :class="{
                    'text-red-600 dark:text-red-400': qualityScore < 40,
                    'text-orange-500 dark:text-orange-400': qualityScore >= 40 && qualityScore < 70,
                    'text-blue-500 dark:text-blue-400': qualityScore >= 70 && qualityScore < 90,
                    'text-green-600 dark:text-green-400': qualityScore >= 90
                }"
                x-text="qualityScore + '%'">
            </span>
        </div>

        {{-- Quality Progress Bar --}}
        <div class="relative w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-3">
            <div class="absolute top-0 left-0 h-full rounded-full transition-all duration-500 ease-out"
                :style="`width: ${qualityScore}%; background: ${getScoreColor()}`"
                :class="{ 'animate-pulse': qualityScore < 40 }">
            </div>
        </div>

        {{-- Missing Critical Fields --}}
        <template x-if="missingCritical.length > 0">
            <div class="mt-3">
                <p class="text-[10px] text-red-500 font-bold mb-1">EKSİK KRİTİK ALANLAR:</p>
                <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                    <template x-for="item in missingCritical" :key="item">
                        <li class="flex items-center">
                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-2"></span>
                            <span x-text="item"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </template>

        {{-- Quality Breakdown --}}
        <div class="space-y-2 text-xs">
            <template x-for="(item, key) in qualityBreakdown" :key="key">
                <div class="flex items-center justify-between">
                    <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300" x-text="item.label"></span>
                    <div class="flex items-center gap-2">
                        <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600 dark:bg-blue-500 rounded-full transition-all duration-300"
                                :style="`width: ${item.score}%`"></div>
                        </div>
                        <span class="text-gray-900 dark:text-white font-semibold w-8 text-right dark:text-slate-100"
                            x-text="item.score + '%'"></span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Cortex Suggestions --}}
        <div x-show="suggestions.length > 0" class="mt-4 pt-3 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <h5 class="text-xs font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-1 dark:text-slate-100">
                <i class="fas fa-lightbulb text-yellow-500 dark:text-yellow-400"></i>
                AI Önerileri
            </h5>
            <ul class="space-y-1">
                <template x-for="suggestion in suggestions" :key="suggestion">
                    <li class="text-xs text-gray-700 dark:text-slate-200 flex items-start gap-1 dark:text-slate-300">
                        <i class="fas fa-arrow-right text-blue-500 dark:text-blue-400 mt-0.5 text-[10px]"></i>
                        <span x-text="suggestion"></span>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    {{-- Dynamic Form Container --}}
    <div x-show="kategoriSelected" x-transition class="mt-6">
        {{-- Category Info Banner --}}
        <div
            class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 
                    border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center gap-3">
                <i class="fas fa-tag text-2xl text-blue-600 dark:text-blue-400"></i>
                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100" x-text="selectedKategoriName"></h4>
                    <p class="text-xs text-gray-700 dark:text-slate-200 mt-1 dark:text-slate-300" x-text="fieldSummary"></p>
                </div>
            </div>
        </div>

        {{-- Zorunlu Alanlar (Required Fields) --}}
        <div x-show="requiredFields.length > 0" x-transition
            class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
            <h4 class="text-sm font-bold text-red-800 dark:text-red-300 mb-4 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                Zorunlu Alanlar
                <span
                    class="ml-auto text-xs bg-red-600 dark:bg-red-700 text-white dark:text-red-100 px-2 py-0.5 rounded-full"
                    x-text="`${completedRequired}/${requiredFields.length}`"></span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-html="requiredFieldsHtml"></div>
        </div>

        {{-- Önerilen Alanlar (Recommended Fields) --}}
        <div x-show="recommendedFields.length > 0" x-transition
            class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <h4 class="text-sm font-bold text-yellow-800 dark:text-yellow-300 mb-4 flex items-center gap-2">
                <i class="fas fa-star text-yellow-600 dark:text-yellow-400"></i>
                Önerilen Alanlar (Kalite İçin)
                <span
                    class="ml-auto text-xs bg-yellow-600 dark:bg-yellow-700 text-white dark:text-yellow-100 px-2 py-0.5 rounded-full"
                    x-text="`${completedRecommended}/${recommendedFields.length}`"></span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-html="recommendedFieldsHtml"></div>
        </div>

        {{-- Opsiyonel Alanlar (Optional Fields) --}}
        <div x-show="optionalFields.length > 0" x-transition
            class="mb-6 bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-slate-800 rounded-lg p-4 dark:border-slate-700 dark:bg-slate-900">
            <h4 class="text-sm font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <i class="fas fa-plus-circle text-gray-600 dark:text-gray-400"></i>
                Opsiyonel Alanlar
                <span
                    class="ml-auto text-xs bg-gray-600 dark:bg-gray-700 text-white dark:text-slate-100 px-2 py-0.5 rounded-full"
                    x-text="`${completedOptional}/${optionalFields.length}`"></span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-html="optionalFieldsHtml"></div>
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="!kategoriSelected"
        class="text-center py-12 px-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-800 dark:bg-slate-900">
        <i class="fas fa-arrow-up text-4xl text-gray-400 dark:text-gray-500 mb-4"></i>
        <p class="text-gray-600 dark:text-gray-400 text-sm">
            Dinamik alanları görmek için yukarıdan kategori seçin
        </p>
    </div>
</div>

<style>
    .cortex-wizard-container .field-group {
        @apply transition-all duration-200 hover:scale-[1.02];
    }

    .cortex-wizard-container input:focus,
    .cortex-wizard-container select:focus,
    .cortex-wizard-container textarea:focus {
        @apply ring-2 ring-blue-500 border-blue-500 shadow-lg;
    }

    .cortex-wizard-container input[type="checkbox"]:checked {
        @apply bg-blue-600 border-blue-600 dark:bg-blue-700 dark:border-blue-700;
    }
</style>
