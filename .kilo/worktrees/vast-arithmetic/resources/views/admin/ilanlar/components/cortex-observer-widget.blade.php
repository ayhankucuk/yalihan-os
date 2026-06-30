<div x-data="cortexObserver"
     x-init="isMinimized = true"
     class="fixed top-20 right-4 z-30 transition-all duration-500 transform"
     :class="score > 0 ? 'translate-y-0 opacity-100' : 'translate-y-20 opacity-0'">

    {{-- Minimized State (Compact) --}}
    <div x-show="isMinimized"
         @click="isMinimized = false"
         class="bg-white/80 dark:bg-slate-900/90 backdrop-blur-lg rounded-2xl shadow-premium p-3 border border-white/20 dark:border-slate-800/50 cursor-pointer hover:shadow-2xl transition-all duration-300 w-48 group">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-md transform group-hover:scale-110 transition-transform dark:shadow-none">
                    <span class="text-xs">🧠</span>
                </div>
                <div>
                    <h3 class="font-black text-gray-900 dark:text-white text-[10px] uppercase tracking-tighter dark:text-slate-100">Cortex</h3>
                    <p class="text-[8px] text-gray-500 font-bold">LIVE</p>
                </div>
            </div>
            <div class="text-lg font-black font-mono" :class="getColor()" x-text="score + '%'"></div>
        </div>
    </div>

    {{-- Expanded State (Full) --}}
    <div x-show="!isMinimized"
         class="bg-white/90 dark:bg-slate-900/95 backdrop-blur-2xl rounded-[2rem] shadow-premium p-6 border border-white/30 dark:border-slate-800/50 w-80 max-h-[80vh] overflow-y-auto relative dark:bg-slate-900/90">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <span class="text-xl animate-pulse">🧠</span>
                </div>
                <div>
                    <h3 class="font-black text-gray-900 dark:text-white text-sm tracking-tight dark:text-slate-100">Yalıhan Cortex</h3>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Premium Engine</p>
                </div>
            </div>
            <button @click.stop="isMinimized = true" class="w-8 h-8 rounded-full bg-gray-100 dark:bg-slate-900 flex items-center justify-center text-gray-400 hover:text-blue-500 transition-colors shadow-inner">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
            </button>
        </div>

        {{-- Score Visualization (Circular Gauge) --}}
        <div class="flex flex-col items-center mb-6">
            <div class="relative w-32 h-32 flex items-center justify-center">
                <svg class="w-full h-full transform -rotate-90">
                    <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="transparent"
                        class="text-gray-100 dark:text-gray-800" />
                    <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="transparent"
                        stroke-dasharray="351.8"
                        :stroke-dashoffset="351.8 - (351.8 * score / 100)"
                        class="transition-all duration-1000 cubic-bezier(0.4, 0, 0.2, 1)"
                        :class="{
                            'text-red-500': score < 40,
                            'text-yellow-500': score >= 40 && score < 70,
                            'text-green-500': score >= 70
                        }" />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center flex-col">
                    <span class="text-4xl font-black tabular-nums tracking-tighter" :class="getColor()" x-text="score"></span>
                    <span class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-black">Score</span>
                </div>
            </div>

            <div class="mt-4 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest"
                 :class="{
                     'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400': score < 40,
                     'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-400': score >= 40 && score < 70,
                     'bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400': score >= 70
                 }">
                 <span x-text="score < 40 ? 'Zayıf İlan' : (score < 70 ? 'İyileştirilebilir' : 'Mükemmel İlan')"></span>
            </div>
        </div>

        {{-- Loading State --}}
        <template x-if="isLoading">
            <div class="flex items-center gap-2 text-xs text-blue-500 mb-2">
                <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Analiz ediliyor...</span>
            </div>
        </template>

        {{-- Issues List --}}
        <div class="space-y-3 max-h-64 overflow-y-auto pr-1 custom-scrollbar">
            <template x-for="(suggestion, index) in suggestions" :key="index">
                <div class="flex items-start gap-3 p-3 rounded-2xl text-xs transition-all duration-300 border hover:scale-[1.02] cursor-default"
                     :class="suggestion.severity === 'high' ?
                        'bg-red-50/50 dark:bg-red-900/10 border-red-100 dark:border-red-900/30' :
                        'bg-yellow-50/50 dark:bg-yellow-900/10 border-yellow-100 dark:border-yellow-900/30'">

                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                         :class="suggestion.severity === 'high' ? 'bg-red-100 dark:bg-red-900/40 text-red-600' : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600'">
                        <i :class="suggestion.severity === 'high' ? 'fas fa-exclamation-triangle' : 'fas fa-lightbulb'"></i>
                    </div>

                    <div class="flex-1">
                        <p x-text="suggestion.message" class="text-gray-900 dark:text-slate-200 font-bold leading-tight dark:text-white"></p>
                        <p class="text-[9px] text-gray-400 mt-1 uppercase font-bold tracking-widest" x-text="suggestion.severity === 'high' ? 'Kritik Düzeltme' : 'İyileştirme Önerisi'"></p>
                    </div>
                </div>
            </template>

            <template x-if="suggestions.length === 0 && score > 0">
                <div class="text-center py-4 text-green-600 dark:text-green-400">
                    <div class="text-2xl mb-1">🎉</div>
                    <p class="text-xs font-bold">Harika İş!</p>
                    <p class="text-[10px] opacity-75">İlanınız yayına hazır.</p>
                </div>
            </template>
        </div>

        {{-- AI Tip Footer --}}
        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-800">
            <p class="text-[10px] text-gray-400 text-center italic">
                Cortex AI tarafından Context7 kurallarına göre analiz edilmiştir.
            </p>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 20px;
}
</style>
