@extends('admin.layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">🎙️ Sesli Arama Ayarları</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Sesli komut ve arama sistemini buradan
                    yapılandırabilirsiniz.</p>
            </div>
            <div class="flex space-x-3">
                <button type="button"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                    Test Et
                </button>
                <button type="submit" form="voice-settings-form"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                    Değişiklikleri Kaydet
                </button>
            </div>
        </div>

        @if (session('success'))
            <div
                class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/30 rounded-lg text-green-800 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <form id="voice-settings-form" action="{{ route('admin.voice-search.settings.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Sol Kolon: Genel Ayarlar -->
                <div class="md:col-span-2 space-y-6">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                        <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 dark:bg-slate-900 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Entegrasyon Yapılandırması</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Sesli Arama
                                        Aktif</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Sistem genelinde sesli arama
                                        özelliğini açar veya kapatır.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="voice_search_enabled" class="sr-only peer"
                                        {{ $voiceSearchEnabled ? 'checked' : '' }}>
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                    </div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Servis
                                        Sağlayıcı</label>
                                    <select name="voice_provider"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:bg-slate-900 dark:text-slate-100">
                                        <option value="openai_whisper"
                                            {{ $voiceProvider == 'openai_whisper' ? 'selected' : '' }}>
                                            OpenAI Whisper (Önerilen)</option>
                                        <option value="google_speech"
                                            {{ $voiceProvider == 'google_speech' ? 'selected' : '' }}>
                                            Google Speech-to-Text</option>
                                        <option value="browser_native"
                                            {{ $voiceProvider == 'browser_native' ? 'selected' : '' }}>
                                            Tarayıcı Yerel API (Ücretsiz)</option>
                                        <option value="ollama_local"
                                            {{ $voiceProvider == 'ollama_local' ? 'selected' : '' }}>
                                            Ollama Local (Gelişmiş)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Varsayılan
                                        Dil</label>
                                    <select name="voice_language"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:bg-slate-900 dark:text-slate-100">
                                        <option value="tr-TR"
                                            {{ $voiceLanguage == 'tr-TR' ? 'selected' : '' }}>
                                            Türkçe (Türkiye)</option>
                                        <option value="en-US"
                                            {{ $voiceLanguage == 'en-US' ? 'selected' : '' }}>
                                            English (US)</option>
                                        <option value="de-DE"
                                            {{ $voiceLanguage == 'de-DE' ? 'selected' : '' }}>
                                            Deutsch</option>
                                        <option value="auto"
                                            {{ $voiceLanguage == 'auto' ? 'selected' : '' }}>
                                            Otomatik Algıla</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">API
                                    Anahtarı</label>
                                <input type="password" name="voice_api_key"
                                    value="{{ $voiceApiKey ? '••••••••••••••••' : '' }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:bg-slate-900 dark:text-slate-100"
                                    placeholder="sk-...">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Seçilen sağlayıcıya ait API
                                    anahtarı. Boş bırakılırsa sistem varsayılanı kullanılır.</p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                        <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 dark:bg-slate-900 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Gelişmiş Parametreler</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Hassasiyet
                                        (Threshold)</label>
                                    <input type="range" name="sensitivity" min="0" max="100"
                                        value="{{ $voiceSensitivity }}"
                                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>Düşük</span>
                                        <span>Yüksek</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Maksimum
                                        Kayıt Süresi (sn)</label>
                                    <input type="number" name="max_record_time"
                                        value="{{ $voiceMaxRecordTime }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:bg-slate-900 dark:text-slate-100">
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="auto_submit"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 dark:bg-slate-900"
                                    {{ $voiceAutoSubmit ? 'checked' : '' }}>
                                <label class="ml-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Konuşma bitince
                                    otomatik ara</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon: Bilgi ve İstatistik -->
                <div class="space-y-6">
                    <div class="bg-gradient-to-br from-blue-600 to-purple-700 rounded-xl p-6 text-white shadow-lg">
                        <h3 class="text-lg font-bold mb-2">Cortex Voice AI</h3>
                        <p class="text-sm text-blue-100 mb-4">Yalıhan Cortex ile entegre sesli komut sistemi, doğal dil
                            işleme (NLP) kullanarak karmaşık emlak aramalarını saniyeler içinde sonuçlandırır.</p>
                        <div class="flex items-center space-x-2 text-xs bg-white/10 rounded-lg p-3 dark:bg-slate-900/10 dark:bg-slate-800/40">
                            <span class="flex-shrink-0 w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                            <span>Sistem Durumu: Aktif ve Bağlı</span>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 uppercase tracking-wider dark:text-slate-100">
                            Kullanım İstatistikleri</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Bugünkü Aramalar</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">124</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Başarı Oranı</span>
                                <span class="text-sm font-bold text-green-500">%98.2</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Ort. Yanıt Süresi</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">1.2 sn</span>
                            </div>
                        </div>
                        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-slate-800">
                            <button type="button"
                                class="w-full text-center text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                Detaylı Raporu Görüntüle
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testBtn = document.querySelector('button:contains("Test Et")') || document.querySelector(
                'button[type="button"]');

            if (testBtn) {
                testBtn.addEventListener('click', function() {
                    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                        alert('Tarayıcınız ses tanıma özelliğini desteklemiyor.');
                        return;
                    }

                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    const recognition = new SpeechRecognition();

                    recognition.lang = document.querySelector('select[name="voice_language"]').value ||
                        'tr-TR';
                    recognition.interimResults = false;
                    recognition.maxAlternatives = 1;

                    testBtn.innerHTML =
                        '<span class="flex items-center"><span class="w-2 h-2 bg-red-500 rounded-full animate-ping mr-2"></span> Dinleniyor...</span>';
                    testBtn.disabled = true;

                    recognition.start();

                    recognition.onresult = function(event) {
                        const transcript = event.results[0][0].transcript;

                        // Voice-to-Query API çağrısı
                        fetch('{{ route('api.ai.admin.voice-to-query') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    text: transcript
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Sesli geri bildirim varsa oynat
                                    if (data.data.audio_url) {
                                        const audio = new Audio(data.data.audio_url);
                                        audio.play();
                                    }

                                    const intent = data.data.intent;
                                    let message = `Duyulan: "${transcript}"\n\n`;
                                    message += `🔍 Arama Tipi: ${intent.search_type}\n`;
                                    message +=
                                        `📍 Lokasyon: ${intent.location.il || ''} ${intent.location.ilce || ''}\n`;
                                    message +=
                                        `💰 Fiyat: ${intent.price.min || '0'} - ${intent.price.max || '∞'} ${intent.price.currency || 'TL'}\n`;

                                    if (confirm(message + '\nSonuçlara gitmek istiyor musunuz?')) {
                                        window.location.href = data.data.redirect_url;
                                    }
                                } else {
                                    alert('Hata: ' + (data.message ||
                                        'Bilinmeyen bir hata oluştu'));
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('API bağlantı hatası!');
                            })
                            .finally(() => {
                                resetBtn();
                            });
                    };

                    recognition.onspeechend = function() {
                        recognition.stop();
                        resetBtn();
                    };

                    recognition.onerror = function(event) {
                        alert('Hata oluştu: ' + event.error);
                        resetBtn();
                    };

                    function resetBtn() {
                        testBtn.innerHTML = 'Test Et';
                        testBtn.disabled = false;
                    }
                });
            }
        });
    </script>
@endsection
