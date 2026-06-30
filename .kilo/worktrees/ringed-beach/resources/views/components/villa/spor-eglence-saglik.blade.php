@props(['ilan'])

@php
    $kategori = \App\Models\FeatureCategory::where('slug', 'spor-eglence-saglik')->first();
    $ozellikler = $kategori
        ? \App\Models\Feature::where('feature_category_id', $kategori->id)
            ->where('aktiflik_durumu', true)
            ->orderBy('display_order')
            ->get()
        : collect();
    $ilanOzellikIds = $ilan->features->pluck('id')->toArray();
    $aktifOzellikler = $ozellikler->filter(fn($oz) => in_array($oz->id, $ilanOzellikIds));
@endphp

@if ($aktifOzellikler->isNotEmpty())
    <div class="space-y-4">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Spor, Eğlence, Sağlık
        </h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ($aktifOzellikler as $ozellik)
                <div
                    class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ $ozellik->name }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
