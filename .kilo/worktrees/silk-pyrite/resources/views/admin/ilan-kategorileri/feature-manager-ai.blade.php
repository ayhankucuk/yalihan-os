{{-- AI Suggestions Panel Component --}}
<div x-show="showAISuggestions" 
     x-cloak
     class="mb-6 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg border border-purple-200 dark:border-purple-800 p-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-3">
            <div class="bg-purple-600 dark:bg-purple-500 rounded-lg p-2">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">🤖 AI Önerileri</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Benzer kategorilere göre akıllı özellik önerileri</p>
            </div>
        </div>
        <button @click="showAISuggestions = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div x-show="loadingAI" class="text-center py-8">
        <svg class="animate-spin h-10 w-10 text-purple-600 dark:text-purple-400 mx-auto" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">AI analiz yapıyor...</p>
    </div>

    <div x-show="!loadingAI && aiSuggestions.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <template x-for="suggestion in aiSuggestions" :key="suggestion.feature_id">
            <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-gray-200 dark:border-slate-800 hover:shadow-md transition-shadow dark:border-slate-700">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="suggestion.name"></h4>
                            <span x-show="suggestion.priority === 'high'" class="px-2 py-0.5 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full">
                                Yüksek
                            </span>
                            <span x-show="suggestion.priority === 'medium'" class="px-2 py-0.5 text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full">
                                Orta
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2" x-text="suggestion.reason"></p>
                        <div class="flex items-center space-x-2 text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Skor:</span>
                            <span class="font-medium text-purple-600 dark:text-purple-400" x-text="suggestion.score + '%'"></span>
                            <span class="text-gray-400">•</span>
                            <span class="text-gray-500 dark:text-gray-400" x-text="suggestion.type"></span>
                        </div>
                    </div>
                    <button @click="quickAddFeature(suggestion.feature_id)" 
                            class="ml-3 px-3 py-1.5 text-xs font-medium bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                        Ekle
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="!loadingAI && aiSuggestions.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <p class="text-sm">Bu kategori için AI önerisi bulunamadı</p>
    </div>
</div>
