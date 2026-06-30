@extends('admin.layouts.admin')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 py-8">
        <div class="container mx-auto px-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1
                            class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent flex items-center">
                            <i class="fas fa-magic text-indigo-500 mr-4"></i>
                            AI Prompt Arayüzü
                        </h1>
                        <p class="text-gray-600 mt-2">İstediğiniz soruyu sorun, AI size en iyi yanıtı versin</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="window.location.href='{{ route('admin.danisman-ai.index') }}'"
                            class="bg-gradient-to-r from-gray-500 to-gray-600 text-white px-6 py-3 rounded-xl hover:from-gray-600 hover:to-gray-700 transition-all duration-300">
                            <i class="fas fa-arrow-left mr-2"></i>Geri Dön
                        </button>
                        <button onclick="clearAll()"
                            class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-3 rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-300">
                            <i class="fas fa-trash mr-2"></i>Temizle
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Sol Panel: AI Prompt Arayüzü -->
                <div class="lg:col-span-3">
                    <!-- Ana Prompt Alanı -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 mb-6 dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <h2 class="text-2xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <i class="fas fa-keyboard text-purple-500 mr-3"></i>
                                AI'ya Sorunuzu Yazın
                            </h2>
                            <p class="text-gray-600 text-sm mt-2">Doğal dilde soru sorabilirsiniz. AI size en uygun yanıtı
                                verecektir.</p>
                        </div>

                        <div class="p-6">
                            <!-- Analiz Tipi Seçimi -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3 dark:text-slate-300">Analiz Tipi</label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <label
                                        class="flex items-center p-4 border-2 border-blue-200 rounded-xl cursor-pointer hover:bg-blue-50 transition-colors has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                        <input type="radio" name="analysis_type" value="ilan_bulma" class="sr-only"
                                            checked>
                                        <div class="flex items-center">
                                            <i class="fas fa-search text-blue-500 mr-3 text-lg"></i>
                                            <div>
                                                <p class="font-semibold text-gray-800 dark:text-slate-200">İlan Bulma</p>
                                                <p class="text-xs text-gray-600">Kriterlere uygun ilanları bulur</p>
                                            </div>
                                        </div>
                                    </label>

                                    <label
                                        class="flex items-center p-4 border-2 border-green-200 rounded-xl cursor-pointer hover:bg-green-50 transition-colors has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                                        <input type="radio" name="analysis_type" value="talep_analizi" class="sr-only">
                                        <div class="flex items-center">
                                            <i class="fas fa-chart-line text-green-500 mr-3 text-lg"></i>
                                            <div>
                                                <p class="font-semibold text-gray-800 dark:text-slate-200">Talep Analizi</p>
                                                <p class="text-xs text-gray-600">Müşteri taleplerini analiz eder</p>
                                            </div>
                                        </div>
                                    </label>

                                    <label
                                        class="flex items-center p-4 border-2 border-purple-200 rounded-xl cursor-pointer hover:bg-purple-50 transition-colors has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50">
                                        <input type="radio" name="analysis_type" value="genel_analiz" class="sr-only">
                                        <div class="flex items-center">
                                            <i class="fas fa-brain text-purple-500 mr-3 text-lg"></i>
                                            <div>
                                                <p class="font-semibold text-gray-800 dark:text-slate-200">Genel Analiz</p>
                                                <p class="text-xs text-gray-600">Genel sorular ve danışmanlık</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Talep Seçimi (İsteğe Bağlı) -->
                            <div class="mb-6">
                                <label for="talepSelect" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                    Özel Talep (İsteğe Bağlı)
                                </label>
                                <select style="color-scheme: light dark;" id="talepSelect"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                    <option value="">Genel analiz (hiçbir talep seçilmedi)</option>
                                    <!-- Buraya aktif talepler dinamik olarak eklenecek -->
                                </select>
                            </div>

                            <!-- Ana Prompt Textarea -->
                            <div class="mb-6">
                                <label for="promptText" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                    Sorunuz / Prompt Metniniz
                                </label>
                                <textarea id="promptText" rows="8"
                                    placeholder="Örnek prompts:&#10;&#10;• 'Bodrum'da 2-3 milyon TL arasında deniz manzaralı ilanlar bulabilir misin?'&#10;&#10;• 'Bu müşterinin tercihlerine göre hangi ilanları önerebilirim?'&#10;&#10;• 'Müşteri profili nasıl? Yakın zamanda karar verebilir mi?'&#10;&#10;• 'Bu bölgede fiyat artışı bekleniyor mu?'&#10;&#10;• 'Benzer özellikte alternatif ilanlar var mı?'"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"></textarea>
                                <div class="flex items-center justify-between mt-2">
                                    <p class="text-xs text-gray-500">Minimum 10, maksimum 2000 karakter</p>
                                    <span id="charCount" class="text-xs text-gray-400">0/2000</span>
                                </div>
                            </div>

                            <!-- Submit Butonu -->
                            <div class="flex gap-3">
                                <button onclick="submitPrompt()"
                                    class="flex-1 bg-gradient-to-r from-purple-500 to-purple-600 text-white py-4 px-6 rounded-xl hover:from-purple-600 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
                                    id="submitBtn">
                                    <i class="fas fa-magic mr-2"></i>
                                    <span>AI Analizi Başlat</span>
                                </button>
                                <button onclick="addToFavorites()"
                                    class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white py-4 px-6 rounded-xl hover:from-yellow-600 hover:to-yellow-700 transition-all duration-300"
                                    title="Favorilere ekle">
                                    <i class="fas fa-star"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- AI Yanıt Alanı -->
                    <div id="aiResponseArea"
                        class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 hidden dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                    <i class="fas fa-robot text-green-500 mr-3"></i>
                                    AI Yanıtı
                                </h3>
                                <div class="flex items-center gap-3">
                                    <span id="responseTime" class="text-sm text-gray-500"></span>
                                    <span id="confidenceScore"
                                        class="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full"></span>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div id="aiResponseContent" class="prose max-w-none">
                                <!-- AI yanıtı buraya gelecek -->
                            </div>

                            <!-- Aksiyon Butonları -->
                            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                                <button onclick="copyResponse()"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition-colors">
                                    <i class="fas fa-copy mr-2"></i>Kopyala
                                </button>
                                <button onclick="saveResponse()"
                                    class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600 transition-colors">
                                    <i class="fas fa-save mr-2"></i>Kaydet
                                </button>
                                <button onclick="shareResponse()"
                                    class="bg-purple-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-600 transition-colors">
                                    <i class="fas fa-share mr-2"></i>Paylaş
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Panel: Yardımcı Araçlar -->
                <div class="space-y-6">
                    <!-- Örnek Prompts -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <i class="fas fa-lightbulb text-yellow-500 mr-3"></i>
                                Örnek Prompts
                            </h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div class="prompt-example bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-xl cursor-pointer hover:from-blue-100 hover:to-blue-200 transition-all"
                                onclick="useExamplePrompt(this)">
                                <div class="flex items-start">
                                    <i class="fas fa-search text-blue-500 mr-3 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-slate-200">İlan Arama</p>
                                        <p class="text-xs text-gray-600 mt-1">"Bodrum'da deniz manzaralı, 2-3 milyon TL
                                            arası villa bulabilir misin?"</p>
                                    </div>
                                </div>
                            </div>

                            <div class="prompt-example bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-xl cursor-pointer hover:from-green-100 hover:to-green-200 transition-all"
                                onclick="useExamplePrompt(this)">
                                <div class="flex items-start">
                                    <i class="fas fa-chart-line text-green-500 mr-3 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-slate-200">Pazar Analizi</p>
                                        <p class="text-xs text-gray-600 mt-1">"Çeşme bölgesinde yazlık fiyatları nasıl bir
                                            trend izliyor?"</p>
                                    </div>
                                </div>
                            </div>

                            <div class="prompt-example bg-gradient-to-r from-purple-50 to-purple-100 p-4 rounded-xl cursor-pointer hover:from-purple-100 hover:to-purple-200 transition-all"
                                onclick="useExamplePrompt(this)">
                                <div class="flex items-start">
                                    <i class="fas fa-users text-purple-500 mr-3 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-slate-200">Müşteri Analizi</p>
                                        <p class="text-xs text-gray-600 mt-1">"Bu müşteri profili için hangi pazarlama
                                            stratejisini önerirsin?"</p>
                                    </div>
                                </div>
                            </div>

                            <div class="prompt-example bg-gradient-to-r from-orange-50 to-orange-100 p-4 rounded-xl cursor-pointer hover:from-orange-100 hover:to-orange-200 transition-all"
                                onclick="useExamplePrompt(this)">
                                <div class="flex items-start">
                                    <i class="fas fa-money-bill-wave text-orange-500 mr-3 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-slate-200">Fiyat Değerlendirme</p>
                                        <p class="text-xs text-gray-600 mt-1">"Bu ilanın fiyatı piyasa değerine göre
                                            nasıl?"</p>
                                    </div>
                                </div>
                            </div>

                            <div class="prompt-example bg-gradient-to-r from-pink-50 to-pink-100 p-4 rounded-xl cursor-pointer hover:from-pink-100 hover:to-pink-200 transition-all"
                                onclick="useExamplePrompt(this)">
                                <div class="flex items-start">
                                    <i class="fas fa-handshake text-pink-500 mr-3 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-slate-200">Satış Stratejisi</p>
                                        <p class="text-xs text-gray-600 mt-1">"Müşteri ile nasıl bir görüşme stratejisi
                                            izlemeliyim?"</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Favori Prompts -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <i class="fas fa-star text-yellow-500 mr-3"></i>
                                Favori Prompts
                            </h3>
                        </div>
                        <div class="p-6" id="favoritesContainer">
                            <div class="text-center py-4">
                                <i class="fas fa-star text-gray-300 text-3xl mb-2"></i>
                                <p class="text-gray-500 text-sm">Henüz favori prompt yok</p>
                            </div>
                        </div>
                    </div>

                    <!-- Son Kullanılan Prompts -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <i class="fas fa-history text-indigo-500 mr-3"></i>
                                Son Kullanılan
                            </h3>
                        </div>
                        <div class="p-6" id="historyContainer">
                            <div class="text-center py-4">
                                <i class="fas fa-history text-gray-300 text-3xl mb-2"></i>
                                <p class="text-gray-500 text-sm">Henüz geçmiş yok</p>
                            </div>
                        </div>
                    </div>

                    <!-- AI Durumu -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 dark:bg-slate-900/80">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <i class="fas fa-server text-blue-500 mr-3"></i>
                                AI Sistem Durumu
                            </h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">AI Servisi</span>
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                                    <i class="fas fa-circle mr-1"></i>Aktif
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Yanıt Süresi</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-slate-200">~2.1s</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Doğruluk Oranı</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-slate-200">%94</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Günlük Kullanım</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-slate-200">{{ auth()->user()->name ?? 'Danışman' }}:
                                    12/∞</span>
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
            <div class="flex items-center justify-center mb-4">
                <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500"></div>
                <div class="ml-4">
                    <div class="animate-pulse flex space-x-1">
                        <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce" style="animation-delay: 0.1s">
                        </div>
                        <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce" style="animation-delay: 0.2s">
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-lg font-semibold text-gray-800 dark:text-slate-200">AI Düşünüyor...</p>
            <p class="text-sm text-gray-600 mt-2">En iyi yanıtı hazırlıyor</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Global değişkenler
        let currentResponse = '';
        let promptHistory = JSON.parse(localStorage.getItem('promptHistory') || '[]');
        let favoritePrompts = JSON.parse(localStorage.getItem('favoritePrompts') || '[]');

        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', function() {
            // Karakter sayacını initialize et
            const promptText = document.getElementById('promptText');
            const charCount = document.getElementById('charCount');

            promptText.addEventListener('input', function() {
                const count = this.value.length;
                charCount.textContent = count + '/2000';

                if (count > 2000) {
                    charCount.classList.add('text-red-500');
                } else {
                    charCount.classList.remove('text-red-500');
                }

                // Submit butonunu kontrol et
                updateSubmitButton();
            });

            // Geçmişi ve favorileri yükle
            loadHistory();
            loadFavorites();

            // Aktif talepleri yükle
            loadActiveTalepler();
        });

        // Ana prompt submit fonksiyonu
        async function submitPrompt() {
            const promptText = document.getElementById('promptText').value.trim();
            const analysisType = document.querySelector('input[name="analysis_type"]:checked').value;
            const talepId = document.getElementById('talepSelect').value || null;

            if (!promptText || promptText.length < 10) {
                alert('Lütfen en az 10 karakter yazın.');
                return;
            }

            if (promptText.length > 2000) {
                alert('Prompt metni 2000 karakteri geçemez.');
                return;
            }

            // Loading göster
            showLoading(true);

            try {
                const response = await fetch('/admin/api/danisman-ai/custom-prompt', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        prompt_text: promptText,
                        analysis_type: analysisType,
                        talep_id: talepId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    displayAIResponse(data);
                    saveToHistory(promptText, analysisType, data);
                } else {
                    throw new Error(data.error || 'AI yanıtı alınamadı');
                }

            } catch (error) {
                console.error('Prompt Error:', error);
                alert('AI yanıtı alınamadı: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        // AI yanıtını göster
        function displayAIResponse(data) {
            const responseArea = document.getElementById('aiResponseArea');
            const responseContent = document.getElementById('aiResponseContent');
            const responseTime = document.getElementById('responseTime');
            const confidenceScore = document.getElementById('confidenceScore');

            // Yanıt içeriğini göster
            let htmlContent = '';

            if (data.result.response) {
                htmlContent = `
            <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-xl p-6 mb-4">
                <div class="flex items-start">
                    <i class="fas fa-robot text-green-500 mr-3 mt-1 text-lg"></i>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800 mb-2 dark:text-slate-200">AI Yanıtı</h4>
                        <div class="text-gray-700 whitespace-pre-wrap dark:text-slate-300">${data.result.response}</div>
                    </div>
                </div>
            </div>
        `;
            }

            responseContent.innerHTML = htmlContent;

            // Meta bilgileri göster
            if (data.result.processing_time) {
                responseTime.textContent = `${data.result.processing_time}s`;
            }

            if (data.result.confidence) {
                const confidence = Math.round(data.result.confidence * 100);
                confidenceScore.textContent = `%${confidence} güven`;
            }

            // Yanıt alanını göster
            responseArea.classList.remove('hidden');
            responseArea.scrollIntoView({
                behavior: 'smooth'
            });

            // Global değişkene kaydet (kopyalama için)
            currentResponse = data.result.response || '';
        }

        // Örnek prompt kullan
        function useExamplePrompt(element) {
            const promptText = element.querySelector('.text-xs').textContent.replace(/"/g, '');
            document.getElementById('promptText').value = promptText;
            document.getElementById('promptText').dispatchEvent(new Event('input'));
        }

        // Favorilere ekle
        function addToFavorites() {
            const promptText = document.getElementById('promptText').value.trim();
            const analysisType = document.querySelector('input[name="analysis_type"]:checked').value;

            if (!promptText) {
                alert('Önce bir prompt yazın.');
                return;
            }

            const favorite = {
                id: Date.now(),
                text: promptText,
                type: analysisType,
                date: new Date().toISOString()
            };

            favoritePrompts.unshift(favorite);
            if (favoritePrompts.length > 10) favoritePrompts.pop();

            localStorage.setItem('favoritePrompts', JSON.stringify(favoritePrompts));
            loadFavorites();

            alert('Favori olarak kaydedildi!');
        }

        // Geçmişe kaydet
        function saveToHistory(prompt, type, response) {
            const historyItem = {
                id: Date.now(),
                text: prompt.substring(0, 100) + (prompt.length > 100 ? '...' : ''),
                type: type,
                date: new Date().toISOString(),
                response: response.result.response ? response.result.response.substring(0, 200) + '...' : ''
            };

            promptHistory.unshift(historyItem);
            if (promptHistory.length > 10) promptHistory.pop();

            localStorage.setItem('promptHistory', JSON.stringify(promptHistory));
            loadHistory();
        }

        // Geçmişi yükle
        function loadHistory() {
            const container = document.getElementById('historyContainer');

            if (promptHistory.length === 0) {
                container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-history text-gray-300 text-3xl mb-2"></i>
                <p class="text-gray-500 text-sm">Henüz geçmiş yok</p>
            </div>
        `;
                return;
            }

            let html = '<div class="space-y-3">';
            promptHistory.slice(0, 5).forEach(item => {
                html += `
            <div class="bg-gray-50 rounded-lg p-3 cursor-pointer hover:bg-gray-100 transition-colors dark:bg-slate-900" onclick="useHistoryPrompt('${item.text}')">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-slate-200">${item.text}</p>
                        <p class="text-xs text-gray-500 mt-1">${new Date(item.date).toLocaleDateString('tr-TR')}</p>
                    </div>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">${item.type}</span>
                </div>
            </div>
        `;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        // Favorileri yükle
        function loadFavorites() {
            const container = document.getElementById('favoritesContainer');

            if (favoritePrompts.length === 0) {
                container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-star text-gray-300 text-3xl mb-2"></i>
                <p class="text-gray-500 text-sm">Henüz favori prompt yok</p>
            </div>
        `;
                return;
            }

            let html = '<div class="space-y-3">';
            favoritePrompts.slice(0, 5).forEach(item => {
                html += `
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 cursor-pointer hover:bg-yellow-100 transition-colors" onclick="useFavoritePrompt('${item.text}')">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-slate-200">${item.text}</p>
                        <p class="text-xs text-gray-500 mt-1">${new Date(item.date).toLocaleDateString('tr-TR')}</p>
                    </div>
                    <button onclick="event.stopPropagation(); removeFavorite(${item.id})" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        // Aktif talepleri yükle
        async function loadActiveTalepler() {
            try {
                // Bu endpoint'i backend'de oluşturmanız gerekecek
                const response = await fetch('/admin/api/danisman-ai/active-talepler');
                const data = await response.json();

                if (data.success) {
                    const select = document.getElementById('talepSelect');
                    data.talepler.forEach(talep => {
                        const option = document.createElement('option');
                        option.value = talep.id;
                        option.textContent = `${talep.musteri_adi} - ${talep.lokasyon}`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Talepler yüklenirken hata:', error);
            }
        }

        // Geçmişten prompt kullan
        function useHistoryPrompt(text) {
            document.getElementById('promptText').value = text.replace('...', '');
            document.getElementById('promptText').dispatchEvent(new Event('input'));
        }

        // Favoriden prompt kullan
        function useFavoritePrompt(text) {
            document.getElementById('promptText').value = text;
            document.getElementById('promptText').dispatchEvent(new Event('input'));
        }

        // Favoriyi kaldır
        function removeFavorite(id) {
            favoritePrompts = favoritePrompts.filter(item => item.id !== id);
            localStorage.setItem('favoritePrompts', JSON.stringify(favoritePrompts));
            loadFavorites();
        }

        // Submit butonunu güncelle
        function updateSubmitButton() {
            const promptText = document.getElementById('promptText').value.trim();
            const submitBtn = document.getElementById('submitBtn');

            if (promptText.length >= 10 && promptText.length <= 2000) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        // Yanıt işlemleri
        function copyResponse() {
            if (!currentResponse) {
                alert('Kopyalanacak yanıt yok.');
                return;
            }

            navigator.clipboard.writeText(currentResponse).then(() => {
                alert('Yanıt kopyalandı!');
            });
        }

        function saveResponse() {
            if (!currentResponse) {
                alert('Kaydedilecek yanıt yok.');
                return;
            }

            const blob = new Blob([currentResponse], {
                type: 'text/plain'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `ai-response-${Date.now()}.txt`;
            a.click();
            URL.revokeObjectURL(url);
        }

        function shareResponse() {
            if (!currentResponse) {
                alert('Paylaşılacak yanıt yok.');
                return;
            }

            if (navigator.share) {
                navigator.share({
                    title: 'AI Yanıtı',
                    text: currentResponse
                });
            } else {
                copyResponse();
            }
        }

        // Temizle
        function clearAll() {
            if (confirm('Tüm alanları temizlemek istediğinizden emin misiniz?')) {
                document.getElementById('promptText').value = '';
                document.getElementById('talepSelect').value = '';
                document.getElementById('aiResponseArea').classList.add('hidden');
                currentResponse = '';
                updateSubmitButton();
            }
        }

        // Loading göster/gizle
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
    </script>
@endpush
