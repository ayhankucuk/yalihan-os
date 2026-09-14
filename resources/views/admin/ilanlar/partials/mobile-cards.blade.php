{{-- 📱 MOBILE ADAPTIVE CARDS --}}
<div class="md:hidden space-y-4">
    @foreach ($ilanlar as $ilan)
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 relative">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex items-center gap-2">
                    @include('admin.ilanlar.partials.referans-badge', ['ilan' => $ilan])
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">#{{ $ilan->id }}</span>
                </div>

                {{-- Status Badge (Inline Toggle) --}}
                <div x-data="yayinDurumuToggle({{ $ilan->id }}, '{{ $ilan->yayin_durumu ?? 'taslak' }}')" class="relative">
                    <button @click="open = !open" type="button" :disabled="updating"
                        class="px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg border"
                        :class="getYayinDurumuClasses()">
                        <span x-text="getYayinDurumuLabel(currentYayinDurumu)"></span>
                    </button>
                </div>
            </div>

            <h3 class="font-bold text-sm text-slate-900 dark:text-white mb-2 line-clamp-1">
                {{ $ilan->baslik ?? 'İlan #' . $ilan->id }}
            </h3>

            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400 mb-4">
                <div class="flex items-center gap-1">
                    <x-icon name="konum" class="w-3.5 h-3.5 text-[#C9A84C]" />
                    <span>{{ $ilan->il->il_adi ?? '-' }}, {{ $ilan->ilce->ilce_adi ?? '-' }}</span>
                </div>
                <div class="text-sm font-black text-[#0A1628] dark:text-[#C9A84C]">
                    {{ number_format($ilan->fiyat ?? 0, 0, ',', '.') }} {{ $ilan->para_birimi ?? 'TRY' }}
                </div>
            </div>

            <div class="flex items-center gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                    class="flex-1 py-2 bg-[#0A1628] text-[#C9A84C] text-center text-xs font-bold rounded-xl uppercase tracking-wider shadow-sm transition-all dark:bg-slate-800 dark:text-[#C9A84C]">
                    Görüntüle
                </a>
                <a href="{{ route('admin.ilanlar.edit', $ilan->id) }}"
                    class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-200 transition-all">
                    <x-icon name="duzenle" class="w-4 h-4" />
                </a>
            </div>
        </div>
    @endforeach
</div>
