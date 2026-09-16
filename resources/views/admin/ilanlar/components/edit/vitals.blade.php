<div class="sticky top-0 z-50 bg-[#0A1628]/95 backdrop-blur-xl border-b border-amber-500/20 shadow-2xl transition-all duration-300">
    <div class="max-w-[1700px] mx-auto flex items-center justify-between h-16 px-4 md:px-6">

        {{-- Left: Identifiers & Back Button --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white rounded-lg transition-colors border border-slate-700 text-xs font-medium"
               title="İlan Kokpitine Dön">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span class="hidden sm:inline">Kokpite Dön</span>
            </a>

            <div class="h-6 w-px bg-slate-700 hidden sm:block"></div>

            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-amber-400 uppercase tracking-widest">İlan Düzenleme</span>
                    <span class="px-1.5 py-0.2 bg-amber-500/10 text-amber-400 text-[10px] font-semibold rounded border border-amber-500/30">
                        {{ $ilan->kategori?->name ?? 'Gayrimenkul' }}
                    </span>
                </div>
                <h1 class="text-white font-bold text-base md:text-lg tracking-tight flex items-center gap-2">
                    <span class="text-amber-400 font-mono text-sm px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">{{ $ilan->referans_no ?: '#'.$ilan->id }}</span>
                    <span class="text-slate-500 font-light">/</span>
                    <span class="text-slate-200 truncate max-w-[200px] md:max-w-md font-normal">{{ $ilan->baslik }}</span>
                </h1>
            </div>
        </div>

        {{-- Center: Status Badge --}}
        <div class="hidden lg:flex items-center gap-6">
            <div class="flex items-center gap-2 px-3 py-1 bg-slate-800/60 rounded-full border border-slate-700">
                <span class="text-[10px] font-medium text-slate-400">Yayın Durumu:</span>
                <div class="w-2 h-2 rounded-full {{ $ilan->yayin_durumu === 'Aktif' ? 'bg-emerald-400 shadow-[0_0_8px_#34d399]' : 'bg-amber-400 shadow-[0_0_8px_#fbbf24]' }}"></div>
                <span class="text-xs font-bold text-white uppercase">{{ $ilan->yayin_durumu }}</span>
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="flex items-center gap-3">
            @if($ilan->yayin_durumu === 'Aktif' && $ilan->slug)
            <a href="{{ url('/ilan/' . $ilan->slug) }}" target="_blank"
               class="p-2 bg-slate-800/80 hover:bg-slate-700 text-emerald-400 hover:text-emerald-300 rounded-xl transition-all border border-slate-700"
               title="Sitede Canlı Gör">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
            @endif

            <button type="submit" form="ilan-create-form"
                    class="px-5 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-amber-500/20 flex items-center gap-2 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Kaydet
            </button>
        </div>
    </div>
</div>

