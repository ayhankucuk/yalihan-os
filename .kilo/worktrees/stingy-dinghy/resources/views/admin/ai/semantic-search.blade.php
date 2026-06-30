@extends('admin.layouts.admin')

@section('title', 'AI Semantik Arama')

@section('content')
<div class="container-fluid px-4 py-8 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3 dark:text-slate-200">
                <i class="fas fa-brain text-indigo-500"></i>
                AI Semantik Arama
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2 italic">
                "Anahtar kelimelerle değil, mülkün anlamıyla arayın."
            </p>
        </div>

        <div class="flex items-center gap-4 bg-white dark:bg-slate-900 p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 dark:shadow-none">
            <div class="text-right">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Vektörel Kapsama</div>
                <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $stats['total_embeddings'] }} / {{ $stats['total_ilanlar'] }}
                </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                <i class="fas fa-database text-indigo-500"></i>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="max-w-4xl mx-auto mb-12">
        <form action="{{ route('admin.ai.semantic-search') }}" method="GET" class="relative group">
            <input
                type="text"
                name="q"
                value="{{ $query }}"
                placeholder="Örn: Bodrum'da denize yakın, geniş teraslı modern villalar..."
                class="w-full px-6 py-5 rounded-3xl bg-white dark:bg-slate-900 border-2 border-transparent shadow-xl focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all duration-300 text-lg text-gray-700 dark:text-slate-200 dark:text-slate-300"
            >
            <button type="submit" class="absolute right-3 top-3 bottom-3 px-8 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-2xl transition-all duration-300 shadow-lg flex items-center gap-2">
                <i class="fas fa-search"></i>
                Cortex Arama
            </button>
        </form>
    </div>

    @if($query)
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-slate-200 italic dark:text-slate-300">
                "{{ $query }}" için semantik sonuçlar:
            </h2>
            <span class="text-sm text-gray-500">{{ count($results) }} sonuç bulundu</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($results as $item)
                @php $listing = $item['listing']; @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500 border border-gray-100 dark:border-slate-800 group relative dark:shadow-none">
                    <!-- Similarity Badge -->
                    <div class="absolute top-4 right-4 z-10">
                        <div class="px-3 py-1.5 rounded-full bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm border border-indigo-100 dark:border-indigo-800 flex items-center gap-2 shadow-sm dark:shadow-none dark:bg-slate-900/90">
                            <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-xs font-bold text-gray-800 dark:text-slate-200">
                                %{{ round($item['score'] * 100, 1) }} Alaka
                            </span>
                        </div>
                    </div>

                    <!-- Image Placeholder / Preview -->
                    <div class="relative h-48 overflow-hidden">
                        @if($listing->kapak_fotografi)
                            <img src="{{ $listing->kapak_fotografi }}" alt="" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 flex flex-col items-center justify-center p-6 text-center">
                                <i class="fas fa-home text-4xl text-indigo-200 dark:text-indigo-800 mb-2"></i>
                                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">Görsel Yok</span>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/60 to-transparent"></div>
                        <div class="absolute bottom-4 left-4">
                             <div class="text-xs text-white/80 font-medium uppercase tracking-widest">{{ $listing->anaKategori?->name ?? 'Kategori' }}</div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5">
                        <h3 class="font-bold text-gray-800 dark:text-white line-clamp-2 mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors dark:text-slate-200">
                            {{ $listing->baslik }}
                        </h3>

                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <i class="fas fa-map-marker-alt text-red-400"></i>
                            {{ $listing->il?->il_adi }}, {{ $listing->ilce?->ilce_adi }}
                        </div>

                        <div class="flex items-center justify-between mt-auto">
                            <div class="text-xl font-black text-indigo-600 dark:text-indigo-400">
                                {{ number_format($listing->fiyat, 0, ',', '.') }} <span class="text-sm font-medium">{{ $listing->para_birimi }}</span>
                            </div>
                            <a href="#" class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-700 flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all dark:bg-slate-900">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center">
                    <div class="w-20 h-20 bg-gray-100 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-ghost text-3xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-700 dark:text-slate-200 dark:text-slate-300">Sonuç Bulunamadı</h3>
                    <p class="text-gray-500">Daha farklı anahtar kelimelerle aramayı deneyin.</p>
                </div>
            @endforelse
        </div>
    @else
        <!-- Welcome / Statistics State -->
        <div class="max-w-2xl mx-auto text-center py-20">
            <div class="mb-8 inline-flex p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-3xl">
                <i class="fas fa-rocket text-4xl text-indigo-600 animate-bounce"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 dark:text-slate-200">Vektörel Veri Tabanı Hazır</h2>
            <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-10">
                Sistemdeki tüm ilanlar 768 boyutlu vektörlere dönüştürülerek "nomic-embed-text" modeliyle endekslenmiştir.
                Artık mülklerin lokasyon, özellik ve açıklamalarını harmanlayarak birbiriyle anlamsal olarak en yakın sonuçları bulabilirsiniz.
            </p>

            <div class="grid grid-cols-2 gap-4">
                <div class="p-6 bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm dark:shadow-none">
                    <div class="text-3xl font-black text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">0.6+</div>
                    <div class="text-xs text-gray-500 uppercase tracking-widest font-bold">Similarity Threshold</div>
                </div>
                <div class="p-6 bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm dark:shadow-none">
                    <div class="text-3xl font-black text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Cortex</div>
                    <div class="text-xs text-gray-500 uppercase tracking-widest font-bold">Orchestrator</div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endsection
