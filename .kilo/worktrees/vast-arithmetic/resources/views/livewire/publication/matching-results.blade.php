<div wire:init="loadAiAnalysis" class="space-y-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-100 flex items-center">
            Matching Insights
            @if(!$isAiAnalysisComplete)
                <span class="ml-3 flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                </span>
                <span class="ml-2 text-xs text-blue-400 font-mono animate-pulse uppercase">AI Inference in Progress...</span>
            @endif
        </h2>
    </div>

    <!-- Results Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($results as $ilan)
            <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden transition-all hover:border-blue-500/50 group">
                <!-- Image Placeholder with Neural Pulse -->
                <div class="h-48 {{ !$isAiAnalysisComplete ? 'neural-pulse-skeleton' : 'bg-slate-900' }} relative">
                    <div class="absolute top-4 right-4">
                        <x-cortex-precision-seal :verified="$ilan->is_precision_verified" />
                    </div>
                </div>

                <div class="p-4 space-y-2">
                    <div class="flex justify-between items-start">
                        <h3 class="text-slate-100 font-bold truncate">{{ $ilan->baslik ?? 'Luxury Listing' }}</h3>
                        <span class="text-blue-400 font-mono text-sm">{{ number_format($ilan->fiyat, 0) }} TL</span>
                    </div>
                    
                    <div class="text-slate-400 text-xs flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        {{ $ilan->ilce ?? 'Beşiktaş' }}, {{ $ilan->il ?? 'İstanbul' }}
                    </div>

                    @if(!$isAiAnalysisComplete)
                        <!-- Neural Pulse Mini Skeletons -->
                        <div class="pt-4 space-y-2">
                            <div class="h-2 w-full neural-pulse-skeleton rounded"></div>
                            <div class="h-2 w-2/3 neural-pulse-skeleton rounded"></div>
                        </div>
                    @else
                        <div class="pt-4 flex items-center space-x-4 text-[10px] text-slate-500 uppercase font-bold">
                            <span>{{ $ilan->oda_sayisi }} Oda</span>
                            <span>{{ $ilan->m2 }} m²</span>
                            <span class="text-blue-500/70">Score: {{ $ilan->cortex_score ?? '95' }}%</span>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        @if(!$isAiAnalysisComplete && $results->count() < 3)
            <!-- Loading Skeletons -->
            @for($i = 0; $i < (3 - $results->count()); $i++)
                <div class="bg-slate-800 rounded-xl border border-slate-700 h-80 neural-pulse-skeleton opacity-50"></div>
            @endfor
        @endif
    </div>
</div>
