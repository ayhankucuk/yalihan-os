{{-- 🧠 STICKY FLOATING CORTEX NEURAL BAR --}}
<div class="fixed bottom-6 inset-x-0 z-50 max-w-4xl mx-auto px-4"
    x-show="selectedIds.length > 0"
    x-transition:enter="transition ease-out duration-300 transform"
    x-transition:enter-start="opacity-0 translate-y-8 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-200 transform"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-8 scale-95">

    <div class="bg-[#0A1628]/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-2xl border border-[#C9A84C]/30 shadow-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        {{-- Left: Selection Info --}}
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#C9A84C]/20 border border-[#C9A84C]/40 flex items-center justify-center text-[#C9A84C]">
                <x-icon name="robot" class="w-5 h-5 animate-pulse" />
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 bg-[#C9A84C] text-[#0A1628] rounded-full text-[10px] font-black uppercase tracking-wider"
                        x-text="`${selectedIds.length} İLAN SEÇİLDİ`"></span>
                    <span class="text-xs font-black uppercase tracking-tight text-white">Cortex™ Toplu İşlemler</span>
                </div>
                <p class="text-[11px] text-slate-400 font-medium">Seçili ilanlara toplu işlem veya AI analizi uygulayın.</p>
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Activate --}}
            <button type="button" @click="bulkAction('activate')" :disabled="processing"
                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 shadow-sm disabled:opacity-50">
                <x-icon name="onay" class="w-3.5 h-3.5" />
                <span>Aktif</span>
            </button>

            {{-- Deactivate --}}
            <button type="button" @click="bulkAction('deactivate')" :disabled="processing"
                class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 shadow-sm disabled:opacity-50">
                <x-icon name="saat" class="w-3.5 h-3.5" />
                <span>Pasif</span>
            </button>

            {{-- Delete --}}
            <button type="button" @click="confirmBulkDelete()" :disabled="processing"
                class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 shadow-sm disabled:opacity-50">
                <x-icon name="sil" class="w-3.5 h-3.5" />
                <span>Sil</span>
            </button>

            {{-- Clear Selection --}}
            <button type="button" @click="clearSelection()"
                class="px-3 py-2 text-xs text-slate-400 hover:text-white transition-colors">
                Temizle
            </button>
        </div>
    </div>
</div>
