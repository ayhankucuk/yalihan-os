@extends('admin.layouts.admin')

@section('title', 'Eşleşme Radarı - Talep #' . $talep->id)

@section('content')
    {{-- 🎯 EŞLEŞME KOKPITI - Matching Engine UI --}}
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-indigo-950 dark:to-purple-950 p-6 transition-all duration-300">
        
        {{-- 📡 TALEP HEADER --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('admin.talepler.index') }}" 
                            class="p-2 bg-white dark:bg-slate-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <h1 class="text-4xl font-black bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                            🎯 Eşleşme Radarı
                        </h1>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-lg ml-14">
                        {{ $talep->baslik ?? 'Talep #' . $talep->id }} için {{ $eslesenIlanlar->count() }} eşleşme bulundu
                    </p>
                </div>
                
                {{-- Talep Özeti --}}
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-700 shadow-lg dark:shadow-none">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Talep Sahibi</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $talep->kisi->tam_ad ?? 'Bilinmiyor' }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-500 mt-1">{{ $talep->kisi->telefon ?? '-' }}</div>
                </div>
            </div>
        </div>

        {{-- 📊 EŞLEŞME İSTATİSTİKLERİ --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            {{-- Toplam Eşleşme --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Toplam Eşleşme</div>
                <div class="text-3xl font-black text-indigo-600 dark:text-indigo-400">{{ $eslesenIlanlar->count() }}</div>
            </div>

            {{-- En Yüksek Skor --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">En Yüksek Skor</div>
                <div class="text-3xl font-black text-green-600 dark:text-green-400">
                    {{ $eslesenIlanlar->first()['skor'] ?? 0 }}
                </div>
            </div>

            {{-- Ortalama Skor --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ortalama Skor</div>
                <div class="text-3xl font-black text-purple-600 dark:text-purple-400">
                    {{ $eslesenIlanlar->isNotEmpty() ? round($eslesenIlanlar->avg('skor'), 1) : 0 }}
                </div>
            </div>

            {{-- Semantik AI Önerileri --}}
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 dark:from-indigo-900 dark:to-purple-900 rounded-2xl p-6 border border-indigo-500 shadow-xl">
                <div class="flex items-center gap-3 mb-2 text-white">
                    <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <div class="text-sm font-medium opacity-80 uppercase tracking-wider">Cortex Semantic AI</div>
                </div>
                <div class="text-3xl font-black text-white">Semantik Güç</div>
                <div class="text-sm text-indigo-100 mt-1">Vektörel benzerlik analizi aktif</div>
            </div>
        </div>

        {{-- 🧠 SEMANTİK AI ÖNERİLERİ (Vector Search) --}}
        @if(isset($semanticMatches) && count($semanticMatches) > 0)
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-indigo-900 dark:text-indigo-300 mb-6 flex items-center gap-3">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white text-sm">🧠</span>
                    Semantik AI Önerileri
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(Vektörel Benzerlik)</span>
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($semanticMatches as $match)
                        @php
                            $ilan = \App\Models\Ilan::find($match['ilan_id']);
                            if (!$ilan) continue;
                        @endphp
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-indigo-200 dark:border-indigo-800 h-full overflow-hidden hover:shadow-lg transition-all border-l-4 border-l-indigo-500">
                            <div class="p-5">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold rounded">
                                        %{{ round($match['score'] * 100) }} Uyum
                                    </div>
                                    <span class="text-xs text-gray-400">Cortex AI</span>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2 line-clamp-1 dark:text-slate-100">{{ $ilan->baslik }}</h3>
                                <div class="text-sm text-indigo-600 dark:text-indigo-400 font-black mb-3">
                                    {{ number_format($ilan->fiyat) }} {{ $ilan->para_birimi }}
                                </div>
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="text-xs text-gray-500">{{ $ilan->ilce->ilce_adi ?? '' }}</span>
                                    <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                    <span class="text-xs text-gray-500">{{ $ilan->metrekare }} m²</span>
                                </div>
                                <a href="{{ route('admin.ilanlar.show', $ilan) }}" class="block w-full py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 text-center text-sm font-bold rounded-lg hover:bg-indigo-600 hover:text-white transition-all">
                                    İlanı İncele
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <div class="h-px bg-gray-200 dark:bg-gray-700 my-8"></div>
        @endif

        {{-- 🎯 GELENEKSEL EŞLEŞEN İLANLAR (Skor Kartları) --}}
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3 dark:text-slate-100">
            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-600 text-white text-sm">🎯</span>
            Parametrik Eşleşmeler
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(Kriter Bazlı)</span>
        </h2>

        <div class="space-y-6">
            @forelse($eslesenIlanlar as $eslesen)
                @php
                    $ilan = $eslesen['ilan'];
                    $skor = $eslesen['skor'];
                    $detay = $eslesen['detay'];
                    
                    // Skor rengini belirle
                    $skorRenk = match(true) {
                        $skor >= 90 => 'green',
                        $skor >= 80 => 'blue',
                        $skor >= 60 => 'yellow',
                        default => 'gray'
                    };
                @endphp

                {{-- SKOR KARTI --}}
                <div class="group relative bg-white dark:bg-slate-900 rounded-2xl border-2 border-{{ $skorRenk }}-200 dark:border-{{ $skorRenk }}-800 hover:shadow-2xl dark:hover:shadow-{{ $skorRenk }}-900/20 transition-all duration-300 overflow-hidden">
                    {{-- Metal Doku Efekti --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-{{ $skorRenk }}-50/50 to-transparent dark:from-{{ $skorRenk }}-900/10 dark:to-transparent pointer-events-none"></div>
                    
                    <div class="relative p-6">
                        <div class="flex items-start gap-6">
                            {{-- SKOR HALKASI (Circular Progress) --}}
                            <div class="flex-shrink-0">
                                <div class="relative w-32 h-32">
                                    {{-- Arka Plan Halka --}}
                                    <svg class="w-32 h-32 transform -rotate-90">
                                        <circle cx="64" cy="64" r="56" stroke="currentColor" 
                                            class="text-gray-200 dark:text-gray-700" 
                                            stroke-width="8" fill="none"/>
                                        {{-- Skor Halkası --}}
                                        <circle cx="64" cy="64" r="56" 
                                            stroke="currentColor" 
                                            class="text-{{ $skorRenk }}-500 dark:text-{{ $skorRenk }}-400" 
                                            stroke-width="8" 
                                            fill="none"
                                            stroke-dasharray="{{ 2 * 3.14159 * 56 }}"
                                            stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - $skor / 100) }}"
                                            stroke-linecap="round"
                                            class="transition-all duration-1000"/>
                                    </svg>
                                    {{-- Skor Değeri --}}
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <div class="text-3xl font-black text-{{ $skorRenk }}-600 dark:text-{{ $skorRenk }}-400">
                                            {{ $skor }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                            {{ $detay['kategori'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- İLAN BİLGİLERİ --}}
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                                            {{ $ilan->baslik }}
                                        </h3>
                                        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                {{ $ilan->sehir->sehir_adi ?? '-' }} / {{ $ilan->ilce->ilce_adi ?? '-' }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                                </svg>
                                                {{ $ilan->metrekare }} m²
                                            </span>
                                            <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                                {{ number_format($ilan->fiyat) }} {{ $ilan->para_birimi }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- TELEMETRİ IŞIKLARI (Uyum Göstergeleri) --}}
                                <div class="grid grid-cols-4 gap-4 mb-4">
                                    {{-- Lokasyon Uyumu --}}
                                    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-2 h-2 rounded-full bg-{{ $detay['lokasyon_uyumu'] >= 80 ? 'green' : ($detay['lokasyon_uyumu'] >= 60 ? 'yellow' : 'red') }}-500 animate-pulse"></div>
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Lokasyon</span>
                                        </div>
                                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $detay['lokasyon_uyumu'] }}</div>
                                    </div>

                                    {{-- Fiyat Uyumu --}}
                                    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-2 h-2 rounded-full bg-{{ $detay['fiyat_uyumu'] >= 80 ? 'green' : ($detay['fiyat_uyumu'] >= 60 ? 'yellow' : 'red') }}-500 animate-pulse"></div>
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Fiyat</span>
                                        </div>
                                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $detay['fiyat_uyumu'] }}</div>
                                    </div>

                                    {{-- Kategori Uyumu --}}
                                    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-2 h-2 rounded-full bg-{{ $detay['kategori_uyumu'] >= 80 ? 'green' : ($detay['kategori_uyumu'] >= 60 ? 'yellow' : 'red') }}-500 animate-pulse"></div>
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Kategori</span>
                                        </div>
                                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $detay['kategori_uyumu'] }}</div>
                                    </div>

                                    {{-- Metrekare Uyumu --}}
                                    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-2 h-2 rounded-full bg-{{ $detay['metrekare_uyumu'] >= 80 ? 'green' : ($detay['metrekare_uyumu'] >= 60 ? 'yellow' : 'red') }}-500 animate-pulse"></div>
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Alan</span>
                                        </div>
                                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $detay['metrekare_uyumu'] }}</div>
                                    </div>
                                </div>

                                {{-- AÇIKLAMA --}}
                                <div class="bg-{{ $skorRenk }}-50 dark:bg-{{ $skorRenk }}-900/20 border border-{{ $skorRenk }}-200 dark:border-{{ $skorRenk }}-800 rounded-lg p-4 mb-4">
                                    <p class="text-sm text-{{ $skorRenk }}-800 dark:text-{{ $skorRenk }}-300">
                                        {{ $detay['aciklama'] }}
                                    </p>
                                </div>

                                {{-- 🎯 PHASE 8 - SPRINT 3: FEEDBACK WIDGET --}}
                                <div class="mb-4 matching-feedback-widget" 
                                     data-talep-id="{{ $talep->id }}" 
                                     data-ilan-id="{{ $ilan->id }}"
                                     data-cortex-score="{{ $skor }}">
                                    <div class="bg-gradient-to-r from-slate-50 to-indigo-50 dark:from-gray-900 dark:to-indigo-950 border-2 border-dashed border-indigo-300 dark:border-indigo-700 rounded-xl p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">
                                                🤖 Bu eşleşmeyi nasıl buldunuz?
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-500 feedback-indicator hidden">
                                                ✓ Kaydedildi
                                            </span>
                                        </div>
                                        <div class="flex gap-2" role="group">
                                            <button class="flex-1 px-4 py-3 text-sm rounded-lg border-2 border-green-500 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 active:scale-95 transition-all feedback-btn" 
                                                    data-feedback="thumbs_up">
                                                <span class="flex items-center justify-center gap-2">
                                                    <span class="text-xl">👍</span>
                                                    <span class="font-bold">Beğendim</span>
                                                </span>
                                            </button>
                                            <button class="flex-1 px-4 py-3 text-sm rounded-lg border-2 border-red-500 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 active:scale-95 transition-all feedback-btn" 
                                                    data-feedback="thumbs_down">
                                                <span class="flex items-center justify-center gap-2">
                                                    <span class="text-xl">👎</span>
                                                    <span class="font-bold">Beğenmedim</span>
                                                </span>
                                            </button>
                                            <button class="flex-1 px-4 py-3 text-sm rounded-lg border-2 border-blue-500 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 active:scale-95 transition-all feedback-btn" 
                                                    data-feedback="perfect_match">
                                                <span class="flex items-center justify-center gap-2">
                                                    <span class="text-xl">🎯</span>
                                                    <span class="font-bold">Mükemmel</span>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- AKSİYON BUTONLARI --}}
                                <div class="flex gap-3">
                                    <a href="{{ route('admin.ilanlar.show', $ilan) }}" 
                                        target="_blank"
                                        class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-500 dark:to-purple-500 text-white dark:text-slate-50 rounded-xl hover:shadow-lg dark:hover:shadow-indigo-500/20 transition-all duration-300 font-bold text-center">
                                        <span class="flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            İlanı Görüntüle
                                        </span>
                                    </a>

                                    <button 
                                        onclick="sendToCustomer({{ $ilan->id }}, {{ $talep->id }})"
                                        class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-500 dark:to-emerald-500 text-white dark:text-slate-50 rounded-xl hover:shadow-lg dark:hover:shadow-green-500/20 transition-all duration-300 font-bold">
                                        <span class="flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                            </svg>
                                            Müşteriye Sun
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Eşleşme Bulunamadı --}}
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-12 text-center border border-gray-200 dark:border-slate-700">
                    <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Eşleşme Bulunamadı</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Bu talep için uygun ilan bulunamadı. Kriterleri genişletmeyi deneyin.
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    @push('scripts')
    <script>
        function sendToCustomer(ilanId, talepId) {
            if (confirm('Bu ilanı müşteriye göndermek istediğinizden emin misiniz?')) {
                // TODO: WhatsApp/Email entegrasyonu
                alert('🎯 İlan #' + ilanId + ' müşteriye gönderildi!');
            }
        }

        // 🎯 PHASE 8 - SPRINT 3: Feedback Widget Handler
        document.addEventListener('DOMContentLoaded', function() {
            const feedbackWidgets = document.querySelectorAll('.matching-feedback-widget');
            
            feedbackWidgets.forEach(widget => {
                const talepId = widget.dataset.talepId;
                const ilanId = widget.dataset.ilanId;
                const cortexScore = widget.dataset.cortexScore;
                const buttons = widget.querySelectorAll('.feedback-btn');
                const indicator = widget.querySelector('.feedback-indicator');
                
                buttons.forEach(btn => {
                    btn.addEventListener('click', async function() {
                        const feedbackType = this.dataset.feedback;
                        
                        // Disable all buttons
                        buttons.forEach(b => b.disabled = true);
                        
                        try {
                            const response = await fetch('/admin/matching/feedback', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    talep_id: talepId,
                                    ilan_id: ilanId,
                                    feedback_tipi: feedbackType,
                                    cortex_score_at_time: parseInt(cortexScore),
                                    match_breakdown: null // TODO: Extract from telemetry
                                })
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                // Success feedback
                                this.classList.add('ring-2', 'ring-offset-2');
                                if (feedbackType === 'thumbs_up' || feedbackType === 'perfect_match') {
                                    this.classList.add('ring-green-500', 'bg-green-50', 'dark:bg-green-900/30');
                                } else {
                                    this.classList.add('ring-red-500', 'bg-red-50', 'dark:bg-red-900/30');
                                }
                                
                                // Show indicator
                                indicator.classList.remove('hidden');
                                indicator.textContent = '✓ Kaydedildi - Sistem öğreniyor!';
                                indicator.classList.add('text-green-600', 'dark:text-green-400');
                                
                                // Toast notification
                                console.log('🎯 Feedback kaydedildi:', data);
                            } else {
                                throw new Error(data.message || 'Kayıt başarısız');
                            }
                        } catch (error) {
                            console.error('Feedback error:', error);
                            indicator.classList.remove('hidden');
                            indicator.textContent = '❌ Hata: ' + error.message;
                            indicator.classList.add('text-red-600', 'dark:text-red-400');
                            
                            // Re-enable buttons
                            buttons.forEach(b => b.disabled = false);
                        }
                    });
                });
            });
            
            console.log('🎯 Matching Feedback Widget initialized for', feedbackWidgets.length, 'matches');
        });
    </script>
    @endpush
@endsection
