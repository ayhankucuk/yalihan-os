<div class="space-y-6">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
            <i class="fas fa-briefcase mr-2"></i>
            Portföy ({{ $performans['ilan_sayisi'] }} Aktif İlan)
        </h3>
    </div>

    @if ($portfoy && $portfoy->count() > 0)
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($portfoy as $ilan)
                <div
                    class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-all duration-200 hover:shadow-md dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    @php
                        $foto = $ilan->ilanFotograflari->first();
                        $fotoUrl = $foto
                            ? asset('storage/' . $foto->dosya_yolu)
                            : asset('images/placeholder-property.jpg');
                    @endphp
                    <div class="relative h-48 bg-gray-200 dark:bg-gray-700">
                        <img src="{{ $fotoUrl }}" alt="{{ $ilan->baslik }}" class="h-full w-full object-cover">
                        <div class="absolute right-2 top-2">
                            <x-neo.status-badge :label="$ilan->yayin_durumu" :type="$ilan->yayin_durumu === 'Yayında' ? 'success' : 'info'" />
                        </div>
                    </div>
                    <div class="p-4">
                        <h4
                            class="mb-2 line-clamp-2 text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                            {{ $ilan->baslik }}
                        </h4>
                        <p class="mb-2 text-lg font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($ilan->fiyat, 0, ',', '.') }} {{ $ilan->para_birimi ?? 'TL' }}
                        </p>
                        <div class="mb-3 flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
                            @if ($ilan->oda_sayisi)
                                <span><i class="fas fa-bed mr-1"></i> {{ $ilan->oda_sayisi }} Oda</span>
                            @endif
                            @if ($ilan->banyo_sayisi)
                                <span><i class="fas fa-bath mr-1"></i> {{ $ilan->banyo_sayisi }} Banyo</span>
                            @endif
                            @if ($ilan->brut_m2)
                                <span><i class="fas fa-ruler-combined mr-1"></i> {{ $ilan->brut_m2 }} m²</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                                class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-center text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700">
                                Detay
                            </a>
                            <a href="{{ route('ilanlar.show', $ilan->id) }}" target="_blank"
                                class="rounded-lg border border-gray-300 px-3 py-2 text-sm transition-colors duration-200 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $portfoy->appends(request()->query())->links() }}
        </div>
    @else
        <div
            class="rounded-lg border border-gray-200 bg-gray-50 py-12 text-center dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
            <i class="fas fa-briefcase mb-4 text-4xl text-gray-400"></i>
            <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-slate-100 dark:text-white">Henüz portföy
                bulunmuyor</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Bu danışmana ait aktif ilan bulunmamaktadır.</p>
        </div>
    @endif
</div>
