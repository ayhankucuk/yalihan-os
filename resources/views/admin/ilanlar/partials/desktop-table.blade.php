{{-- 🖥️ DESKTOP QUANTUM TABLE (Mediterranean Luxury) --}}
<div class="hidden md:block w-full overflow-hidden rounded-2xl border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-md">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                <th class="px-5 py-4 w-12 text-center">
                    <input type="checkbox" id="select-all"
                        class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-[#C9A84C] focus:ring-[#C9A84C] bg-transparent cursor-pointer"
                        x-model="selectAll" @change="toggleSelectAll()">
                </th>
                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                    İlan & Varlık
                </th>
                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                    Kategori & Tip
                </th>
                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                    Fiyat
                </th>
                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                    Danışman
                </th>
                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                    Yayın Durumu
                </th>
                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 text-right">
                    İşlemler
                </th>
            </tr>
        </thead>
        <tbody id="ilanlar-tbody" class="divide-y divide-slate-100 dark:divide-slate-800/60">
            @foreach ($ilanlar as $ilan)
                <tr class="group/row hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors duration-200">
                    {{-- Checkbox --}}
                    <td class="px-5 py-4 text-center">
                        <input type="checkbox"
                            class="row-checkbox w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-[#C9A84C] focus:ring-[#C9A84C] bg-transparent cursor-pointer"
                            value="{{ $ilan->id }}" x-model="selectedIds"
                            @change="updateSelectAll()">
                    </td>

                    {{-- İlan & Varlık (Kompakt Thumbnail + Başlık + Konum) --}}
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-4">
                            {{-- Kompakt Görsel (w-24 h-16) --}}
                            <div class="relative flex-shrink-0 w-24 h-16 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 group/img">
                                @php
                                    $firstPhoto = $ilan->fotograflar?->first();
                                    $photoPath = $firstPhoto?->dosya_yolu;
                                @endphp
                                @if ($photoPath && file_exists(storage_path('app/public/' . $photoPath)))
                                    <img class="w-full h-full object-cover group-hover/img:scale-110 transition-transform duration-500"
                                        src="{{ asset('storage/' . $photoPath) }}"
                                        alt="{{ $ilan->baslik ?? 'İlan görseli' }}">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-300 dark:text-slate-600">
                                        <x-icon name="resim" class="w-6 h-6" />
                                    </div>
                                @endif
                                <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                                    class="absolute inset-0 bg-[#0A1628]/70 flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-opacity duration-200">
                                    <span class="text-[#C9A84C] font-black text-[10px] tracking-wider uppercase">İNCELE</span>
                                </a>
                            </div>

                            <div class="flex-1 min-w-0 space-y-1">
                                <div class="flex items-center gap-2">
                                    @include('admin.ilanlar.partials.referans-badge', ['ilan' => $ilan])
                                    <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                                        class="text-sm font-bold text-slate-900 dark:text-white truncate hover:text-[#C9A84C] dark:hover:text-[#C9A84C] transition-colors"
                                        title="{{ $ilan->baslik }}">
                                        {{ $ilan->baslik ?? 'İlan #' . $ilan->id }}
                                    </a>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                    <x-icon name="konum" class="w-3.5 h-3.5 text-[#C9A84C]" />
                                    <span class="truncate">{{ $ilan->il->il_adi ?? '-' }}, {{ $ilan->ilce->ilce_adi ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Kategori & Tip --}}
                    <td class="px-5 py-4">
                        <div class="flex flex-col gap-1">
                            <span class="px-2.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-black uppercase tracking-wider rounded border border-slate-200 dark:border-slate-700 w-fit">
                                {{ $ilan->yayinTipi?->name ?? 'STANDART' }}
                            </span>
                            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                {{ $ilan->anaKategori?->name }}
                                @if ($ilan->altKategori)
                                    <span class="mx-0.5 text-slate-300">/</span> {{ $ilan->altKategori->name }}
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Fiyat --}}
                    <td class="px-5 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-black text-slate-900 dark:text-white tracking-tight">
                                {{ number_format($ilan->fiyat ?? 0, 0, ',', '.') }} {{ $ilan->para_birimi ?? 'TRY' }}
                            </span>
                            @if ($ilan->kiralama_turu)
                                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                                    / {{ $ilan->kiralama_turu }}
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Danışman --}}
                    <td class="px-5 py-4">
                        @if ($ilan->userDanisman)
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-[#0A1628] text-[#C9A84C] flex items-center justify-center font-black text-[10px] border border-slate-700 shadow-sm">
                                    {{ substr($ilan->userDanisman->name, 0, 2) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $ilan->userDanisman->name }}</p>
                                    <p class="text-[9px] font-medium text-slate-400 uppercase tracking-widest">Danışman</p>
                                </div>
                            </div>
                        @else
                            <span class="text-xs text-slate-400 dark:text-slate-600">Atanmadı</span>
                        @endif
                    </td>

                    {{-- Yayın Durumu (Inline Dropdown) --}}
                    <td class="px-5 py-4">
                        <div x-data="yayinDurumuToggle({{ $ilan->id }}, '{{ $ilan->yayin_durumu ?? 'taslak' }}')" @click.outside="open = false" class="relative inline-block">
                            <button @click="open = !open" type="button" :disabled="updating"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-lg border transition-all cursor-pointer disabled:opacity-50"
                                :class="getYayinDurumuClasses()">
                                <span x-text="getYayinDurumuLabel(currentYayinDurumu)"></span>
                                <x-icon name="asagi-chevron" class="w-3 h-3 transition-transform duration-200" x-bind:class="{ 'rotate-180': open }" />
                            </button>

                            <div x-show="open" x-transition
                                class="absolute left-0 z-50 mt-2 w-40 rounded-xl shadow-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 py-1.5 overflow-hidden">
                                <template x-for="dt in durumSecenekleri" :key="dt.value">
                                    <button @click="changeYayinDurumu(dt.value)" type="button"
                                        class="w-full text-left px-4 py-2 text-[10px] font-black uppercase tracking-wider hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2.5 transition-colors"
                                        :class="{ 'text-[#C9A84C] font-bold': currentYayinDurumu === dt.value, 'text-slate-600 dark:text-slate-300': currentYayinDurumu !== dt.value }">
                                        <span class="w-2 h-2 rounded-full"
                                            :class="{
                                                'bg-emerald-500': dt.value === 'yayinda',
                                                'bg-amber-500': dt.value === 'beklemede',
                                                'bg-slate-400': dt.value === 'taslak',
                                                'bg-rose-500': dt.value === 'pasif',
                                                'bg-indigo-500': dt.value === 'arsiv'
                                            }"></span>
                                        <span x-text="dt.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </td>

                    {{-- İşlemler --}}
                    <td class="px-5 py-4 text-right">
                        <div class="inline-flex items-center gap-1">
                            <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                                class="p-2 bg-slate-100 hover:bg-[#0A1628] text-slate-700 hover:text-[#C9A84C] dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-[#0A1628] rounded-lg transition-all"
                                title="İlan Detayı">
                                <x-icon name="goster" class="w-4 h-4" />
                            </a>
                            <a href="{{ route('admin.ilanlar.edit', $ilan->id) }}"
                                class="p-2 bg-slate-100 hover:bg-[#0A1628] text-slate-700 hover:text-[#C9A84C] dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-[#0A1628] rounded-lg transition-all"
                                title="Düzenle">
                                <x-icon name="duzenle" class="w-4 h-4" />
                            </a>
                            <form method="POST" action="{{ route('admin.ilanlar.destroy', $ilan->id) }}" class="inline"
                                @submit.prevent="if(confirm('İlanı silmek istediğinize emin misiniz?')) $el.submit()">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="p-2 bg-slate-100 hover:bg-rose-600 text-slate-700 hover:text-white dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-rose-600 rounded-lg transition-all"
                                    title="Sil">
                                    <x-icon name="sil" class="w-4 h-4" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
