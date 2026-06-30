@extends('admin.layouts.admin')

@section('title', 'Lead Detayı: ' . ($lead->name ?? 'Bilinmeyen Müşteri'))

@section('content')
    <div class="p-6">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.leads.index') }}" class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 text-gray-500 hover:bg-gray-50 transition-colors dark:border-slate-700">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                        {{ $lead->name ?? 'Bilinmeyen Müşteri' }}
                        <span class="px-2 py-1 bg-{{ $score->skor_degeri >= 80 ? 'green' : 'yellow' }}-100 dark:bg-{{ $score->skor_degeri >= 80 ? 'green' : 'yellow' }}-900/30 text-{{ $score->skor_degeri >= 80 ? 'green' : 'yellow' }}-700 dark:text-{{ $score->skor_degeri >= 80 ? 'green' : 'yellow' }}-300 text-xs rounded-full">
                            {{ $score->skor_degeri }}% {{ $score->skor_etiketi }}
                        </span>
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                        <i class="fab fa-{{ $lead->platform }}"></i> {{ ucfirst($lead->platform) }} • Son Aktivite: {{ $lead->updated_at->diffForHumans() }}
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    <i class="fas fa-phone mr-2"></i> Ara
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: AI Card & Lead Info -->
            <div class="space-y-6">
                <!-- AI Insights Card -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-purple-200 dark:border-purple-900/50 shadow-sm overflow-hidden relative dark:shadow-none">
                    <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                        <i class="fas fa-brain text-9xl text-purple-600"></i>
                    </div>
                    <div class="px-6 py-4 border-b border-purple-100 dark:border-purple-900/30 bg-purple-50 dark:bg-purple-900/10 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-purple-900 dark:text-purple-300">
                            <i class="fas fa-magic mr-2"></i> AI İçgörüleri
                        </h3>
                        <span class="text-xs text-purple-700 dark:text-purple-400 font-medium">
                            {{ $score->updated_at->diffForHumans() }} güncellendi
                        </span>
                    </div>
                    <div class="p-6 relative z-10">
                        <!-- Score Bar -->
                        <div class="mb-6 text-center">
                            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full border-4 border-{{ $score->skor_degeri >= 80 ? 'green' : 'yellow' }}-500 mb-2">
                                <span class="text-3xl font-black text-gray-900 dark:text-white dark:text-slate-100">{{ $score->skor_degeri }}</span>
                            </div>
                            <div class="text-sm font-bold uppercase tracking-wider text-gray-500">{{ $score->skor_etiketi }}</div>
                        </div>

                        <!-- Win Probability -->
                        <div class="mb-6 px-4">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-bold text-gray-500 uppercase">Kazanma İhtimali</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $score->win_probability ?? 0 }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $score->win_probability ?? 0 }}%"></div>
                            </div>
                        </div>

                        <!-- Reasoning -->
                        <div class="mb-4">
                            <h4 class="text-sm font-bold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Analiz Özeti</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 italic bg-gray-50 dark:bg-slate-900 p-3 rounded-lg border border-gray-100 dark:border-slate-800">
                                "{{ $score->skor_nedeni ?? 'Analiz için yeterli veri bekleniyor.' }}"
                            </p>
                        </div>

                        <!-- Tags -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Etiketler</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($lead->tags ?? [] as $tag)
                                    <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs font-bold rounded">
                                        #{{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Cost Guard Footer -->
                    <div class="px-6 py-3 bg-gray-50 dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center text-xs text-gray-500">
                        <span>AI Modeli: {{ $score->model_versiyonu }}</span>
                        <span>Maliyet: ~$0.01</span>
                    </div>
                </div>

                <!-- Next Best Action Card -->
                @if(isset($recommendation))
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-{{ $recommendation['color'] }}-200 dark:border-{{ $recommendation['color'] }}-900/50 shadow-sm overflow-hidden mb-6 dark:shadow-none">
                    <div class="px-6 py-4 border-b border-{{ $recommendation['color'] }}-100 dark:border-{{ $recommendation['color'] }}-900/30 bg-{{ $recommendation['color'] }}-50 dark:bg-{{ $recommendation['color'] }}-900/10 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-{{ $recommendation['color'] }}-900 dark:text-{{ $recommendation['color'] }}-300">
                            <i class="fas fa-robot mr-2"></i> Önerilen Aksiyon
                        </h3>
                        @if($recommendation['urgency'] === 'critical' || $recommendation['urgency'] === 'high')
                            <span class="animate-pulse px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">ACİL</span>
                        @endif
                    </div>
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-{{ $recommendation['color'] }}-100 dark:bg-{{ $recommendation['color'] }}-900/30 flex items-center justify-center text-{{ $recommendation['color'] }}-600 dark:text-{{ $recommendation['color'] }}-400 text-xl">
                                <i class="fas {{ $recommendation['icon'] }}"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-lg mb-1 dark:text-slate-100">{{ $recommendation['action'] }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    {{ $recommendation['description'] }}
                                </p>
                                <button class="text-xs bg-{{ $recommendation['color'] }}-600 hover:bg-{{ $recommendation['color'] }}-700 text-white px-3 py-1.5 rounded transition-colors">
                                    Aksiyonu Uygula
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Info Card -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">İletişim Bilgileri</h3>
                    <ul class="space-y-3">
                        <li class="flex items-center text-sm">
                            <div class="w-8 h-8 rounded bg-gray-100 dark:bg-slate-900 flex items-center justify-center mr-3 text-gray-500">
                                <i class="fas fa-phone"></i>
                            </div>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $lead->phone ?? '-' }}</span>
                        </li>
                        <li class="flex items-center text-sm">
                            <div class="w-8 h-8 rounded bg-gray-100 dark:bg-slate-900 flex items-center justify-center mr-3 text-gray-500">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $lead->email ?? '-' }}</span>
                        </li>
                        <li class="flex items-center text-sm">
                            <div class="w-8 h-8 rounded bg-gray-100 dark:bg-slate-900 flex items-center justify-center mr-3 text-gray-500">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $lead->interested_location_id ?? 'Konum Belirsiz' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Activity Feed -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center dark:border-slate-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Aktivite Akışı</h3>
                        <div class="flex gap-2">
                            <button class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Tümünü Gör</button>
                        </div>
                    </div>
                    <div class="p-6">
                        <!-- Timeline -->
                        <div class="relative pl-6 border-l-2 border-gray-200 dark:border-slate-800 space-y-8 dark:border-slate-700">
                            <!-- Activities Loop -->
                            @forelse($lead->activities as $activity)
                            <div class="relative">
                                <!-- Dot -->
                                <div class="absolute -left-[31px] w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 border-2 border-white dark:border-gray-900"></div>

                                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4 border border-gray-100 dark:border-slate-800">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-bold text-gray-900 dark:text-white text-sm dark:text-slate-100">Arama Analizi</h4>
                                        <span class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        Müşteri ile 3dk görüşüldü. Fiyat bilgisi verildi.
                                    </p>

                                    <!-- AI Analysis Snippet (If available) -->
                                    <div class="bg-white dark:bg-slate-900 p-3 rounded border border-blue-100 dark:border-blue-900/30 flex gap-3 items-center">
                                        <div class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xs font-bold">
                                            AI
                                        </div>
                                        <p class="text-xs text-blue-900 dark:text-blue-300 italic flex-1">
                                            "Müşteri olumlu yaklaştı, bütçe konusunda esnek."
                                        </p>
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] rounded font-bold">Duygu: 8/10</span>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <p>Henüz kayıtlı aktivite yok.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
