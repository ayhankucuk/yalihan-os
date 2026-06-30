{{--
    Context7 Component Library ve 03-modul-ozellikler.md referans alınmıştır.
    Bu component, ilan özelliklerini kategoriye göre gruplayarak gösterir ve Context7 badge, kart ve renk standartlarını kullanır.
--}}
@props(['features' => []])
@php
    $groups = collect($features)->groupBy(fn($f) => $f['group'] ?? 'Genel');
@endphp
<div class="space-y-8">
    @foreach ($groups as $group => $items)
        <div class="bg-white rounded-xl shadow p-6 dark:bg-slate-900 dark:shadow-none">
            <div class="mb-3 flex items-center gap-2">
                <span class="text-base font-semibold text-blue-700">{{ $group }}</span>
                <span class="text-xs text-gray-400">({{ count($items) }} özellik)</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($items as $feature)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium font-medium-primary text-sm">
                        {{ $feature['name'] }}
                    </span>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
