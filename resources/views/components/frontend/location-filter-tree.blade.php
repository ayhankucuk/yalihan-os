@props([
    'iller' => [],
    'selectedIlIds' => [],
    'selectedIlceIds' => [],
    'selectedMahIds' => [],
    'expandedIlIds' => [],
    'expandedIlceIds' => [],
])

<div class="mb-5"
     x-data="{
         openIls:   {{ json_encode(array_values(array_unique($expandedIlIds))) }},
         openIlces: {{ json_encode(array_values(array_unique($expandedIlceIds))) }},
         toggleIl(id)   { const i=this.openIls.indexOf(id);   i===-1?this.openIls.push(id):this.openIls.splice(i,1); },
         toggleIlce(id) { const i=this.openIlces.indexOf(id); i===-1?this.openIlces.push(id):this.openIlces.splice(i,1); }
     }">
    <h4 class="text-[11px] font-bold text-[#6B7280] uppercase tracking-wider mb-3">KONUM</h4>

    {{-- Search Input --}}
    <div class="relative mb-3">
        <x-icon name="arama" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#6B7280] pointer-events-none" />
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="İl, İlçe, Mahalle..."
               class="w-full bg-[#F8F6F1] border border-[#E8E2D8] rounded-lg pl-9 pr-3 py-2.5 text-sm font-medium text-[#0A1628] placeholder-[#6B7280] outline-none focus:border-[#C9A84C] focus:ring-2 focus:ring-[#C9A84C]/20 transition-all h-[44px]">
    </div>

    {{-- Location Tree --}}
    <div class="space-y-0.5 max-h-64 overflow-y-auto pr-0.5 custom-scrollbar">
        @forelse($iller as $il)
        <div>
            {{-- İl row --}}
            <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg hover:bg-[#F8F6F1] cursor-pointer select-none"
                 @click="toggleIl({{ (int)$il->id }})">
                <input type="checkbox" name="il[]" value="{{ $il->id }}"
                       {{ in_array($il->id, $selectedIlIds) ? 'checked' : '' }}
                       class="custom-checkbox flex-shrink-0" @click.stop>
                <span class="text-sm font-semibold text-[#0A1628] flex-1">{{ $il->il_adi }}</span>
                <span class="text-[11px] text-[#6B7280] font-medium tabular-nums">{{ $il->ilan_sayisi }}</span>
                @if($il->ilceler->isNotEmpty())
                @php $ilId = (int)$il->id; @endphp
                <x-icon name="asagi-chevron"
                        ::class="openIls.includes({{ $ilId }}) ? 'rotate-180' : ''"
                        class="w-4 h-4 text-[#6B7280] flex-shrink-0 transition-transform duration-150" />
                @endif
            </div>

            {{-- İlçeler --}}
            @if($il->ilceler->isNotEmpty())
            <div x-show="openIls.includes({{ (int)$il->id }})" x-cloak
                 x-transition:enter="transition-all duration-150 ease-out"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pl-5 mt-0.5 space-y-0.5">
                @foreach($il->ilceler as $ilce)
                <div>
                    {{-- İlçe row --}}
                    @php $ilceId = (int)$ilce->id; $ilceHasMah = $ilce->mahalleler->isNotEmpty(); @endphp
                    <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-[#F8F6F1] cursor-pointer select-none"
                         @click="{{ $ilceHasMah ? 'toggleIlce('.$ilceId.')' : '' }}">
                        <input type="checkbox" name="ilce[]" value="{{ $ilceId }}"
                               {{ in_array($ilceId, $selectedIlceIds) ? 'checked' : '' }}
                               class="custom-checkbox flex-shrink-0" @click.stop>
                        <span class="text-sm font-medium text-[#434655] flex-1">{{ $ilce->ilce_adi }}</span>
                        <span class="text-[11px] text-[#6B7280] tabular-nums">{{ $ilce->ilan_sayisi }}</span>
                        @if($ilceHasMah)
                        <x-icon name="asagi-chevron"
                                ::class="openIlces.includes({{ $ilceId }}) ? 'rotate-180' : ''"
                                class="w-3.5 h-3.5 text-[#6B7280] flex-shrink-0 transition-transform duration-150" />
                        @endif
                    </div>

                    {{-- Mahalleler --}}
                    @if($ilce->mahalleler->isNotEmpty())
                    <div x-show="openIlces.includes({{ (int)$ilce->id }})" x-cloak
                         x-transition:enter="transition-all duration-150 ease-out"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="pl-5 mt-0.5 space-y-0.5">
                        @foreach($ilce->mahalleler as $mah)
                        <label class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-[#F8F6F1] cursor-pointer group">
                            <input type="checkbox" name="mahalle[]" value="{{ $mah->id }}"
                                   {{ in_array($mah->id, $selectedMahIds) ? 'checked' : '' }}
                                   class="custom-checkbox flex-shrink-0">
                            <span class="text-[13px] text-[#434655] flex-1 group-hover:text-[#0A1628] transition-colors">{{ $mah->mahalle_adi }}</span>
                            <span class="text-[11px] text-[#6B7280] tabular-nums">{{ $mah->ilan_sayisi }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @empty
            <p class="text-xs text-[#6B7280] px-2 py-3">Konum verisi bulunamadı.</p>
        @endforelse
    </div>
</div>
