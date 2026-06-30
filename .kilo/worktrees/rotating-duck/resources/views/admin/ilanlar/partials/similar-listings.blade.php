@php
    $listing = $listing ?? ($ilan ?? null);
    $similarListings = [];
    $scoresMap = [];

    if ($listing) {
        $semanticService = app(\App\Services\AI\SemanticSearchService::class);
        $similarScores = $semanticService->search($listing->baslik . ' ' . $listing->aciklama, 4);

        if (!empty($similarScores)) {
            $ids = array_filter(array_column($similarScores, 'ilan_id'), fn($id) => $id != $listing->id);
            $scoresMap = array_column($similarScores, 'score', 'ilan_id');

            $similarListings = \App\Models\Ilan::whereIn('id', $ids)
                ->with(['il', 'ilce', 'anaKategori'])
                ->get()
                ->sortByDesc(fn($l) => $scoresMap[$l->id] ?? 0);
        }
    }
@endphp

@if(count($similarListings) > 0)
<div class="mt-12 bg-white dark:bg-slate-900 rounded-3xl p-8 border border-gray-100 dark:border-slate-800 shadow-sm dark:shadow-none">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-3 dark:text-slate-200">
                <i class="fas fa-magic text-purple-500"></i>
                AI Benzer İlanlar
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bu ilana anlamsal olarak en yakın mülkler:</p>
        </div>
        <div class="px-4 py-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-xs font-bold text-indigo-600 dark:text-indigo-400">
            Cortex Similarity Engine
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($similarListings as $similar)
            <div class="group relative bg-gray-50 dark:bg-gray-900/50 rounded-2xl overflow-hidden border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900 transition-all duration-300 dark:bg-slate-900">
                <div class="relative h-32 overflow-hidden">
                    @if($similar->kapak_fotografi)
                         <img src="{{ $similar->kapak_fotografi }}" alt="" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <i class="fas fa-home text-indigo-300"></i>
                        </div>
                    @endif
                    <div class="absolute top-2 right-2 px-2 py-1 rounded-lg bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm text-[10px] font-black text-indigo-600 dark:bg-slate-900/90">
                        %{{ round(($scoresMap[$similar->id] ?? 0) * 100, 1) }}
                    </div>
                </div>
                <div class="p-4">
                    <h4 class="text-sm font-bold text-gray-800 dark:text-slate-200 line-clamp-1 mb-1">
                        {{ $similar->baslik }}
                    </h4>
                    <div class="flex items-center justify-between">
                        @if($similar->ilce?->ilce_adi)
                            <span class="text-xs text-gray-500 dark:text-gray-400 italic">{{ $similar->ilce?->ilce_adi }}</span>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">Görsel Yok</span>
                        @endif
                        <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">
                            {{ number_format($similar->fiyat, 0, ',', '.') }} {{ $similar->para_birimi }}
                        </span>
                    </div>
                    <a href="#" class="mt-3 block w-full py-2 text-center text-xs font-bold text-gray-400 hover:text-indigo-600 bg-white dark:bg-slate-900 rounded-lg border border-gray-100 dark:border-slate-800 transition-colors">
                        İncele
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
