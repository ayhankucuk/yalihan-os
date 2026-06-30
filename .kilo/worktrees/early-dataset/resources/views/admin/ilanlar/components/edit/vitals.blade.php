<div class="sticky top-0 z-[40] bg-slate-900/80 backdrop-blur-xl border-b border-white/5 shadow-2xl transition-all duration-500">
    <div class="max-w-[1700px] mx-auto flex items-center justify-between h-16 px-4 md:px-6">

        {{-- Left: Identifiers --}}
        <div class="flex items-center gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Operation: Edit</span>
                    <span class="px-1.5 py-0.5 bg-indigo-500/10 text-indigo-400 text-[9px] font-bold rounded border border-indigo-500/20">v2.1</span>
                </div>
                <h1 class="text-white font-black text-lg tracking-tighter uppercase flex items-center gap-2">
                    {{ $ilan->kisa_referans }}
                    <span class="text-slate-500 font-medium">/</span>
                    <span class="text-indigo-400 truncate max-w-[200px] md:max-w-md">{{ $ilan->baslik }}</span>
                </h1>
            </div>
        </div>

        {{-- Center: Data Quality & Status --}}
        <div class="hidden lg:flex items-center gap-8">
            <div class="flex flex-col items-center">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Data Density</span>
                <div class="w-32 h-1 bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full shadow-[0_0_10px_#6366f1]" style="width: 85%"></div>
                </div>
            </div>

            <div class="h-8 w-px bg-slate-400/10 dark:bg-slate-400/20"></div>

            <div class="flex flex-col items-center">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-0.5">Publication Status</span>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full {{ $ilan->yayin_durumu === 'Aktif' ? 'bg-emerald-500 shadow-[0_0_5px_#10b981]' : 'bg-amber-500 shadow-[0_0_5px_#f59e0b]' }}"></div>
                    <span class="text-[11px] font-black text-white uppercase">{{ $ilan->yayin_durumu }}</span>
                </div>
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="flex items-center gap-3">
            {{-- Quick Links --}}
            <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
               class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-xl transition-all border border-slate-700"
               title="Tactical View">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </a>

            @if($ilan->yayin_durumu === 'Aktif')
            <a href="{{ url('/ilan/' . $ilan->slug) }}" target="_blank"
               class="p-2 bg-slate-800 hover:bg-slate-700 text-emerald-400 hover:text-emerald-300 rounded-xl transition-all border border-slate-700"
               title="Public Preview">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
            @endif

            <div class="h-6 w-px bg-slate-400/10 dark:bg-slate-400/20 mx-1"></div>

            <button type="submit" form="ilan-create-form"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2 group">
                <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Sync Changes
            </button>
        </div>
    </div>
</div>
