@extends('admin.layouts.admin')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 py-8">
        <div class="container mx-auto px-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1
                            class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            🤖 AI Danışman Merkezi
                        </h1>
                        <p class="text-gray-600 mt-2">Akıllı analiz ve eşleştirme sisteminiz</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="window.location.href='{{ route('admin.danisman-ai.prompt-interface') }}'"
                            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                            <i class="fas fa-magic mr-2"></i>AI Prompt Arayüzü
                        </button>
                        <button onclick="startBatchAnalysis()"
                            class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                            <i class="fas fa-robot mr-2"></i>Toplu Analiz
                        </button>
                    </div>
                </div>
            </div>

            <!-- Performans Kartları -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Günlük Analiz -->
                <div
                    class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-white/50 dark:bg-slate-900/80">
                    <div class="flex items-center">
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl text-white">
                            <i class="fas fa-chart-line text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-sm font-medium">Bugün Analiz</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-slate-200">{{ $performans['gunluk_analiz'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Başarılı Eşleşme -->
                <div
                    class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-white/50 dark:bg-slate-900/80">
                    <div class="flex items-center">
                        <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-xl text-white">
                            <i class="fas fa-bullseye text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-sm font-medium">Başarılı Eşleşme</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-slate-200">{{ $performans['basarili_eslesme'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- AI Doğruluk -->
                <div
                    class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-white/50 dark:bg-slate-900/80">
                    <div class="flex items-center">
                        <div class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl text-white">
                            <i class="fas fa-brain text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-sm font-medium">AI Doğruluk</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-slate-200">%{{ $performans['ai_dogruluk_orani'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Müşteri Memnuniyeti -->
                <div
                    class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-white/50 dark:bg-slate-900/80">
                    <div class="flex items-center">
                        <div class="p-3 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl text-white">
                            <i class="fas fa-star text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-sm font-medium">Memnuniyet</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-slate-200">
                                {{ number_format($performans['musteri_memnuniyeti'] ?? 0, 1) }}/5</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Sol Kolon: Aktif Talepler -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Aktif Talepler -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-2xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                    <i class="fas fa-tasks text-blue-500 mr-3"></i>
                                    Aktif Taleplerin
                                </h2>
                                <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                                    {{ count($aktiveTalepler) }} talep
                                </span>
                            </div>
                        </div>

                        <div class="p-6">
                            @if (count($aktiveTalepler) > 0)
                                <div class="space-y-4">
                                    @foreach ($aktiveTalepler as $talep)
                                        <div
                                            class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-xl p-4 hover:shadow-md transition-all duration-300 border border-gray-200 dark:border-slate-700">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3 mb-2">
                                                        <h4 class="font-semibold text-gray-800 dark:text-slate-200">
                                                            {{ $talep->kisi->ad ?? 'Müşteri' }}</h4>
                                                        <span
                                                            class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                                                            Aktif
                                                        </span>
                                                    </div>
                                                    <p class="text-gray-600 text-sm mb-2">
                                                        <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                                                        {{ $talep->il->il_adi ?? '' }} / {{ $talep->ilce->ilce_adi ?? '' }}
                                                    </p>
                                                    <p class="text-gray-500 text-xs">
                                                        {{ $talep->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button onclick="analyzeWithAI({{ $talep->id }})"
                                                        class="bg-blue-500 text-white px-4 py-2.5 rounded-lg text-sm hover:bg-blue-600 transition-colors">
                                                        <i class="fas fa-robot mr-1"></i>AI Analiz
                                                    </button>
                                                    <button onclick="findMatches({{ $talep->id }})"
                                                        class="bg-green-500 text-white px-4 py-2.5 rounded-lg text-sm hover:bg-green-600 transition-colors">
                                                        <i class="fas fa-search mr-1"></i>Eşleştir
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">Şu anda aktif talebiniz bulunmuyor</p>
                                    <p class="text-gray-400 text-sm mt-2">Yeni talepler geldiğinde burada görünecek</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Hızlı AI Arama -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <h2 class="text-2xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <i class="fas fa-search text-purple-500 mr-3"></i>
                                Akıllı İlan Arama
                            </h2>
                        </div>
                        <div class="p-6">
                            <form id="aiSearchForm" onsubmit="performAISearch(event)">
                                <div class="flex gap-3">
                                    <input type="text" id="searchQuery"
                                        placeholder="Örnek: Bodrum'da deniz manzaralı 2+1 daire, 2-3 milyon TL arası..."
                                        class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <button type="submit"
                                        class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-6 py-3 rounded-xl hover:from-purple-600 hover:to-purple-700 transition-all duration-300">
                                        <i class="fas fa-magic mr-2"></i>AI Ara
                                    </button>
                                </div>
                            </form>

                            <!-- Arama Sonuçları -->
                            <div id="searchResults" class="mt-6 hidden">
                                <!-- Dinamik içerik -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon: Son Analizler & Hızlı Aksiyonlar -->
                <div class="space-y-6">
                    <!-- Son AI Analizleri -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <i class="fas fa-history text-indigo-500 mr-3"></i>
                                Son Analizler
                            </h3>
                        </div>
                        <div class="p-6">
                            @foreach ($sonAnalizler as $analiz)
                                <div class="mb-4 last:mb-0">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span
                                                    class="text-xs font-medium text-indigo-600">{{ $analiz['tarih'] }}</span>
                                                <span
                                                    class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded dark:bg-slate-900">{{ $analiz['tip'] }}</span>
                                            </div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-slate-200">{{ $analiz['sonuc'] }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ $analiz['aksiyon'] }}</p>
                                        </div>
                                    </div>
                                    @if (!$loop->last)
                                        <hr class="mt-3 border-gray-200 dark:border-slate-700">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Hızlı Aksiyonlar -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <i class="fas fa-bolt text-yellow-500 mr-3"></i>
                                Hızlı Aksiyonlar
                            </h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <button onclick="getAIContext('yeni_talep')"
                                class="w-full text-left bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 p-4 rounded-xl transition-all duration-300 border border-blue-200">
                                <div class="flex items-center">
                                    <i class="fas fa-plus-circle text-blue-500 mr-3"></i>
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-slate-200">Yeni Talep Önerileri</p>
                                        <p class="text-sm text-gray-600">AI destekli işlem önerileri al</p>
                                    </div>
                                </div>
                            </button>

                            <button onclick="getAIContext('ilan_eşleştirme')"
                                class="w-full text-left bg-gradient-to-r from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 p-4 rounded-xl transition-all duration-300 border border-green-200">
                                <div class="flex items-center">
                                    <i class="fas fa-link text-green-500 mr-3"></i>
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-slate-200">Eşleştirme Önerileri</p>
                                        <p class="text-sm text-gray-600">Akıllı eşleştirme stratejileri</p>
                                    </div>
                                </div>
                            </button>

                            <button onclick="getAIContext('müşteri_takip')"
                                class="w-full text-left bg-gradient-to-r from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 p-4 rounded-xl transition-all duration-300 border border-purple-200">
                                <div class="flex items-center">
                                    <i class="fas fa-users text-purple-500 mr-3"></i>
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-slate-200">Müşteri Takip</p>
                                        <p class="text-sm text-gray-600">AI destekli müşteri analizi</p>
                                    </div>
                                </div>
                            </button>

                            <button onclick="getAIContext('performans')"
                                class="w-full text-left bg-gradient-to-r from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 p-4 rounded-xl transition-all duration-300 border border-yellow-200">
                                <div class="flex items-center">
                                    <i class="fas fa-chart-bar text-yellow-500 mr-3"></i>
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-slate-200">Performans Analizi</p>
                                        <p class="text-sm text-gray-600">AI destekli performans değerlendirmesi</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- AI Önerileri Kutusu -->
                    <div id="aiSuggestions"
                        class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 hidden dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <i class="fas fa-lightbulb text-orange-500 mr-3"></i>
                                AI Önerileri
                            </h3>
                        </div>
                        <div class="p-6">
                            <div id="suggestionsContent">
                                <!-- Dinamik içerik -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl p-8 text-center dark:bg-slate-900">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p class="text-lg font-semibold text-gray-800 dark:text-slate-200">AI Analizi Yapılıyor...</p>
            <p class="text-sm text-gray-600 mt-2">Bu işlem birkaç saniye sürebilir</p>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // AI Analiz Fonksiyonu
        async function analyzeWithAI(talepId) {
            showLoading(true);

            try {
                const response = await fetch(`/admin/api/ai/talep-analiz/${talepId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showAnalysisResult(data);
                } else {
                    throw new Error(data.error || 'Analiz başarısız');
                }
            } catch (error) {
                console.error('AI Analiz Hatası:', error);
                alert('AI analizi sırasında bir hata oluştu: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        // İlan Eşleştirme Fonksiyonu
        async function findMatches(talepId) {
            showLoading(true);

            try {
                const response = await fetch(`/admin/api/ai/talep-eslesme/${talepId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showMatchingResults(data);
                } else {
                    throw new Error(data.error || 'Eşleştirme başarısız');
                }
            } catch (error) {
                console.error('Eşleştirme Hatası:', error);
                alert('Eşleştirme sırasında bir hata oluştu: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        // Akıllı Arama Fonksiyonu
        async function performAISearch(event) {
            event.preventDefault();

            const query = document.getElementById('searchQuery').value;
            if (!query) return;

            showLoading(true);

            try {
                const response = await fetch('/admin/api/danisman-ai/smart-search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        search_query: query
                    })
                });

                const data = await response.json();

                if (data.success) {
                    displaySearchResults(data.results);
                } else {
                    throw new Error(data.error || 'Arama başarısız');
                }
            } catch (error) {
                console.error('Arama Hatası:', error);
                alert('Arama sırasında bir hata oluştu: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        // Hızlı AI Önerileri
        async function getAIContext(context) {
            try {
                const response = await fetch('/admin/api/danisman-ai/quick-suggestions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        context: context
                    })
                });

                const data = await response.json();

                if (data.success) {
                    displaySuggestions(data.suggestions, context);
                } else {
                    throw new Error(data.error || 'Öneri alınamadı');
                }
            } catch (error) {
                console.error('Öneri Hatası:', error);
                alert('Öneri alınamadı: ' + error.message);
            }
        }

        // Toplu Analiz
        async function startBatchAnalysis() {
            const confirmed = confirm(
            'Tüm aktif taleplerin AI analizi yapılsın mı? Bu işlem birkaç dakika sürebilir.');
            if (!confirmed) return;

            showLoading(true);

            try {
                const response = await fetch('/admin/api/danisman-ai/batch-analysis', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(`Toplu analiz tamamlandı! ${data.analyzed_count} talep analiz edildi.`);
                    location.reload(); // Sayfayı yenile
                } else {
                    throw new Error(data.error || 'Toplu analiz başarısız');
                }
            } catch (error) {
                console.error('Toplu Analiz Hatası:', error);
                alert('Toplu analiz sırasında bir hata oluştu: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        // Yardımcı Fonksiyonlar
        function showLoading(show) {
            const modal = document.getElementById('loadingModal');
            if (show) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            } else {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function displaySearchResults(results) {
            const container = document.getElementById('searchResults');
            container.classList.remove('hidden');

            if (results.length === 0) {
                container.innerHTML =
                    '<p class="text-gray-500 text-center py-4">Arama kriterlerinize uygun ilan bulunamadı.</p>';
                return;
            }

            let html = '<h4 class="font-semibold text-gray-800 mb-4 dark:text-slate-200">Bulunan İlanlar (' + results.length + ' adet)</h4>';
            html += '<div class="space-y-3">';

            results.forEach(result => {
                html += `
            <div class="bg-gray-50 rounded-xl p-4 border dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="font-medium text-gray-800 dark:text-slate-200">${result.baslik}</h5>
                        <p class="text-sm text-gray-600">${result.lokasyon.il}/${result.lokasyon.ilce}</p>
                        <p class="text-sm font-medium text-green-600">${new Intl.NumberFormat('tr-TR').format(result.fiyat)} TL</p>
                    </div>
                    <div class="text-right">
                        <div class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full mb-2">
                            %${result.relevance_score} uyum
                        </div>
                        <a href="/admin/ilanlar/${result.ilan_id}" target="_blank" class="text-blue-500 hover:text-blue-700 text-sm">
                            <i class="fas fa-external-link-alt mr-1"></i>Görüntüle
                        </a>
                    </div>
                </div>
                ${result.match_reasons.length > 0 ? `
                                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                                    <p class="text-xs text-gray-500">
                                        <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                        ${result.match_reasons.join(', ')}
                                    </p>
                                </div>
                            ` : ''}
            </div>
        `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        function displaySuggestions(suggestions, context) {
            const container = document.getElementById('aiSuggestions');
            const content = document.getElementById('suggestionsContent');

            let html = '<div class="space-y-3">';
            suggestions.forEach(suggestion => {
                html += `
            <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-lightbulb text-orange-500 mr-3 mt-1"></i>
                    <p class="text-gray-800 dark:text-slate-200">${suggestion}</p>
                </div>
            </div>
        `;
            });
            html += '</div>';

            content.innerHTML = html;
            container.classList.remove('hidden');
        }

        function showAnalysisResult(data) {
            // Analiz sonuçlarını göster (detaylı modal veya yeni sayfa)
            console.log('Analiz Sonucu:', data);
            alert('AI analizi tamamlandı! Sonuçlar konsola yazdırıldı.');
        }

        function showMatchingResults(data) {
            // Eşleştirme sonuçlarını göster
            console.log('Eşleştirme Sonucu:', data);
            alert('İlan eşleştirme tamamlandı! Sonuçlar konsola yazdırıldı.');
        }
    </script>
@endpush
