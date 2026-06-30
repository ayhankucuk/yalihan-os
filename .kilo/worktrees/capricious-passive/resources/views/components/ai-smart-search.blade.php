{{-- AI Smart Search Component --}}
<section class="ds-section bg-gradient-to-br from-blue-50 via-indigo-50 to-emerald-50">
    <div class="ds-container">
        <div class="max-w-5xl mx-auto">
            {{-- Header --}}
            <div class="text-center mb-16">
                <div class="ds-badge ds-badge-primary mb-6 px-6 py-3 text-base">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                    </svg>
                    AI Destekli Emlak Arama
                </div>

                <h2 class="text-4xl md:text-5xl font-bold mb-6 ds-text-gradient">
                    Akıllı Emlak Arama
                </h2>
                <p class="text-xl md:text-2xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                    <span class="font-semibold text-blue-600">Yapay zeka</span> ile istediğiniz evi doğal dilde
                    tanımlayın,
                    <span class="font-semibold text-emerald-600">biz sizin için bulalım</span>
                </p>
                <p class="text-lg text-gray-500 mt-4 max-w-2xl mx-auto">
                    Bu özellik sürekli geliştirilmekte ve daha da akıllı hale getirilmektedir
                </p>
            </div>

            {{-- Smart Search Box --}}
            <div class="ds-card-glass p-8 mb-12 ds-shadow-glow">
                <div class="flex items-start space-x-4">
                    {{-- AI Avatar --}}
                    <div class="flex-shrink-0">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Search Input --}}
                    <div class="flex-1">
                        <div class="relative">
                            <textarea id="ai-search-input"
                                class="w-full border-2 border-gray-300 rounded-xl px-4 py-3
                                           focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                           focus:outline-none resize-none transition-all duration-300"
                                rows="3"
                                placeholder="Örnek: 'İstanbul Avrupa yakasında, 3+1, bahçeli, 500-800 bin TL arası satılık ev arıyorum'"></textarea>

                            {{-- Character Counter --}}
                            <div class="absolute bottom-2 right-2 text-xs text-gray-400">
                                <span id="char-counter">0</span>/500
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center justify-between mt-4">
                            <div class="flex space-x-2">
                                {{-- Quick Suggestions --}}
                                <button
                                    class="search-suggestion bg-blue-100 text-blue-700 text-sm px-3 py-1 rounded-full hover:bg-blue-200 transition-colors">
                                    Satılık ev
                                </button>
                                <button
                                    class="search-suggestion bg-purple-100 text-purple-700 text-sm px-3 py-1 rounded-full hover:bg-purple-200 transition-colors">
                                    Kiralık daire
                                </button>
                                <button
                                    class="search-suggestion bg-green-100 text-green-700 text-sm px-3 py-1 rounded-full hover:bg-green-200 transition-colors">
                                    Yatırım amaçlı
                                </button>
                            </div>

                            <button id="ai-search-btn"
                                class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-2
                                           rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-300
                                           flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>AI ile Ara</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search Results Loading --}}
            <div id="search-loading" class="hidden">
                <div class="bg-white rounded-xl shadow-lg p-8 text-center dark:bg-slate-900">
                    <div class="inline-flex items-center space-x-3">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="text-lg font-medium text-gray-700 dark:text-slate-300">AI arama sonuçlarını analiz ediyor...</span>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        <div class="flex justify-center space-x-1">
                            <div class="w-2 h-2 bg-blue-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 0.1s;">
                            </div>
                            <div class="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 0.2s;">
                            </div>
                        </div>
                        <p class="mt-2">Emlak veritabanından en uygun seçenekleri buluyoruz</p>
                    </div>
                </div>
            </div>

            {{-- Sample AI Analysis Result --}}
            <div id="ai-analysis" class="hidden">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 dark:bg-slate-900">
                    <div class="flex items-start space-x-4">
                        <div
                            class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-lg text-gray-800 mb-2 dark:text-slate-200">AI Analiz Sonucu</h3>
                            <div id="analysis-content" class="space-y-2 text-gray-600">
                                <!-- Dynamic content will be inserted here -->
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full">Lokasyon:
                                    İstanbul</span>
                                <span class="bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full">Tip: 3+1</span>
                                <span class="bg-purple-100 text-purple-800 text-sm px-3 py-1 rounded-full">Bütçe:
                                    500-800K</span>
                                <span class="bg-orange-100 text-orange-800 text-sm px-3 py-1 rounded-full">Özellik:
                                    Bahçeli</span>
                            </div>
                            <div class="mt-4">
                                <a href="/ilanlar"
                                    class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                                    Uygun ilanları görüntüle
                                    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- AI Features Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                <div class="text-center p-6 bg-white/60 backdrop-blur-sm rounded-xl border border-white/20 dark:bg-slate-900/60">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2 dark:text-slate-200">Doğal Dil İşleme</h3>
                    <p class="text-sm text-gray-600">İsteklerinizi günlük konuşma diliyle ifade edin</p>
                </div>

                <div class="text-center p-6 bg-white/60 backdrop-blur-sm rounded-xl border border-white/20 dark:bg-slate-900/60">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2 dark:text-slate-200">Akıllı Filtreleme</h3>
                    <p class="text-sm text-gray-600">AI, tercihlerinize en uygun sonuçları bulur</p>
                </div>

                <div class="text-center p-6 bg-white/60 backdrop-blur-sm rounded-xl border border-white/20 dark:bg-slate-900/60">
                    <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2 dark:text-slate-200">Anlık Sonuçlar</h3>
                    <p class="text-sm text-gray-600">Saniyeler içinde en iyi eşleşmeleri görün</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('ai-search-input');
        const searchBtn = document.getElementById('ai-search-btn');
        const charCounter = document.getElementById('char-counter');
        const searchLoading = document.getElementById('search-loading');
        const aiAnalysis = document.getElementById('ai-analysis');
        const analysisContent = document.getElementById('analysis-content');

        // Character counter
        searchInput.addEventListener('input', function() {
            const length = this.value.length;
            charCounter.textContent = length;

            if (length > 450) {
                charCounter.classList.add('text-red-500');
            } else {
                charCounter.classList.remove('text-red-500');
            }
        });

        // Search suggestions
        document.querySelectorAll('.search-suggestion').forEach(btn => {
            btn.addEventListener('click', function() {
                const suggestion = this.textContent;
                const currentText = searchInput.value;

                if (currentText.trim() === '') {
                    searchInput.value = suggestion + ' arıyorum';
                } else {
                    searchInput.value = currentText + ', ' + suggestion;
                }

                searchInput.focus();
                searchInput.dispatchEvent(new Event('input'));
            });
        });

        // AI Search function
        function performAISearch() {
            const query = searchInput.value.trim();

            if (query.length < 10) {
                alert('Lütfen daha detaylı bir arama yapın (en az 10 karakter)');
                return;
            }

            // Hide previous results
            aiAnalysis.classList.add('hidden');

            // Show loading
            searchLoading.classList.remove('hidden');

            // Simulate AI processing
            setTimeout(() => {
                searchLoading.classList.add('hidden');

                // Generate AI analysis
                const analysis = generateAIAnalysis(query);
                analysisContent.innerHTML = analysis;

                aiAnalysis.classList.remove('hidden');

                // Scroll to results
                aiAnalysis.scrollIntoView({
                    behavior: 'smooth'
                });
            }, 2000);
        }

        // Generate mock AI analysis
        function generateAIAnalysis(query) {
            const keywords = {
                locations: ['İstanbul', 'Ankara', 'İzmir', 'Antalya', 'Bursa'],
                types: ['3+1', '2+1', '4+1', 'dubleks', 'villa', 'daire'],
                features: ['bahçeli', 'asansörlü', 'kapalı otopark', 'güvenlikli', 'deniz manzaralı'],
                prices: ['500-800', '300-500', '800-1000', '1000+']
            };

            let analysis = `
            <p><strong>Arama sorgunuz analiz edildi:</strong></p>
            <ul class="list-disc list-inside space-y-1 mt-2">
                <li>Toplam 247 ilan bulundu</li>
                <li>Fiyat aralığınıza uygun 89 seçenek</li>
                <li>Konum tercihinize göre filtrelendi</li>
                <li>AI uyumluluk skoru: %94</li>
            </ul>
            <p class="mt-3 font-medium text-green-700">
                ✓ Size özel öneriler hazırlandı
            </p>
        `;

            return analysis;
        }

        // Search button click
        searchBtn.addEventListener('click', performAISearch);

        // Enter key search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                performAISearch();
            }
        });
    });
</script>
