{{-- 📦 Cockpit Data Grid (Technical Features Matrix) --}}
@php
    $categories = $ilan->ozellikler->groupBy('category');
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($categories as $categoryName => $features)
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden hover:border-gray-300 dark:hover:border-gray-600 transition-all dark:border-slate-700">
            <div class="px-4 py-3 bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center dark:border-slate-700">
                <h4 class="text-xs font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ $categoryName ?:  'Genel Özellikler' }}</h4>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $features->count() }} Özellik</span>
            </div>

            <div class="p-4 space-y-3">
                @foreach($features as $feature)
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs font-medium text-gray-700 dark:text-slate-200 truncate dark:text-slate-300">{{ $feature->name }}</span>

                        <div class="flex items-center gap-2 shrink-0">
                            @if($feature->pivot->value === '1' || $feature->pivot->value === 'on')
                                <div class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-semibold rounded border border-green-200 dark:border-green-800">
                                    Var
                                </div>
                            @elseif($feature->pivot->value)
                                <span class="text-xs font-semibold text-gray-900 dark:text-white tabular-nums dark:text-slate-100">
                                    {{ $feature->pivot->value }}
                                </span>
                            @else
                                <span class="text-xs font-medium text-gray-400 dark:text-gray-600 italic">Yok</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="col-span-full py-20 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-slate-800 rounded-lg">
            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400 font-medium">Bu ilan için özellik verisi bulunamadı.</p>
        </div>
    @endforelse
</div>
