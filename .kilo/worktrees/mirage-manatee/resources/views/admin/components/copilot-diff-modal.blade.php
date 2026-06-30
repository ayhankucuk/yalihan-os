{{-- Copilot Diff Preview Modal --}}
{{-- Shows before/after comparison for copilot-generated actions --}}
<div x-show="showDiffModal" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm transition-opacity" @click.self="showDiffModal = false"
    @keydown.escape.window="showDiffModal = false">
    <div class="w-full max-w-2xl scale-100 transform rounded-3xl border border-white/20 bg-white/90 shadow-2xl backdrop-blur-xl transition-all dark:border-slate-700/50 dark:bg-slate-900/90"
        @click.stop>
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-slate-700">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Değişiklik Önizleme</h3>
                <span
                    class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                    x-text="diffItems.length + ' alan'"></span>
            </div>
            <button @click="showDiffModal = false"
                class="rounded p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-700 dark:hover:text-white"
                aria-label="Kapat">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Diff body --}}
        <div class="max-h-[60vh] overflow-y-auto px-5 py-3">
            <template x-for="(item, i) in diffItems" :key="i">
                <div class="mb-3 rounded-lg border border-gray-100 dark:border-slate-700">
                    {{-- Field header --}}
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="item.label"></span>
                            <span class="rounded px-1.5 py-0.5 text-[10px] font-medium"
                                :class="item.confidence >= 0.7 ?
                                    'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' :
                                    (item.confidence >= 0.5 ?
                                        'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400' :
                                        'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400')"
                                x-text="'%' + Math.round(item.confidence * 100)"></span>
                        </div>
                        <span class="text-xs text-gray-400 dark:text-slate-500" x-text="item.source"></span>
                    </div>

                    {{-- Before / After --}}
                    <div
                        class="grid grid-cols-2 gap-px border-t border-gray-100/50 bg-gray-100/50 dark:border-slate-700/50 dark:bg-slate-700/50">
                        {{-- Before --}}
                        <div class="bg-red-50/80 px-4 py-3 dark:bg-red-900/20">
                            <span
                                class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-red-500">Mevcut Durum</span>
                            <p class="text-sm text-gray-700 dark:text-slate-300" x-text="item.current || '(boş)'"></p>
                        </div>
                        {{-- After --}}
                        <div class="bg-green-50/80 px-4 py-3 dark:bg-green-900/20">
                            <span
                                class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-green-500 flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Yapay Zeka Önerisi
                            </span>
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="item.proposed || '(boş)'"></p>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="diffItems.length === 0">
                <div class="py-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-slate-400">Değişiklik önizleme verisi yok</p>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-slate-700">
            <button @click="showDiffModal = false"
                class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
                İptal
            </button>
            <div class="flex gap-3">
                <button @click="rejectAllActions()"
                    class="rounded-xl border border-red-200/60 px-5 py-2.5 text-sm font-semibold text-red-700 transition-all hover:bg-red-50 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/30">
                    Reddet
                </button>
                <button @click="applyAllActions()"
                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition-all hover:-translate-y-0.5 hover:bg-blue-500 hover:shadow-blue-500/50 dark:bg-blue-500 dark:shadow-blue-600/20">
                    Tümünü Uygula
                </button>
            </div>
        </div>
    </div>
</div>
