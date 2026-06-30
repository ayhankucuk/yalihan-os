{{-- Video Sekmesi - AraziPro Tasarımı Referanslı --}}
<div class="space-y-6">
    {{-- Ana Grid: Sol Panel (Video Kayıt Kartı) + Sağ Panel (Harita) --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- SOL PANEL: Video Kayıt Kartı (AraziPro Referanslı) --}}
        <div class="lg:col-span-1">
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                {{-- Başlık --}}
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100 dark:text-white">Video Kayıt</h3>
                </div>

                {{-- Çözünürlük Seçenekleri (Statik Bilgi) --}}
                <div class="mb-6">
                    <div class="mb-3 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        Çözünürlük:</div>
                    <div class="space-y-4 dark:text-slate-200">
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <div class="h-2 w-2 rounded-full bg-gray-400"></div>
                            <span>720p (Hızlı)</span>
                        </div>
                        <div
                            class="flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                            <div class="h-2 w-2 rounded-full bg-blue-600"></div>
                            <span>1080p (Kaliteli)</span>
                        </div>
                    </div>
                </div>

                {{-- Video Oluştur Butonu (Büyük Kırmızı) --}}
                <div x-data="videoDurumWidget({{ $ilan->id }}, '{{ route('api.ai.baslat-video-render', ['ilanId' => $ilan->id]) }}', '{{ route('api.ai.video-durum', ['ilanId' => $ilan->id]) }}')" x-init="init()" class="mb-6">
                    <template x-if="video_durumu === 'none'">
                        <button @click="start()" :disabled="loading"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-6 py-4 text-base font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:bg-red-700 hover:shadow-xl focus:ring-4 focus:ring-red-500/50 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg x-show="!loading" class="h-5 w-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg x-show="loading" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="loading ? 'Kuyruğa Alınıyor...' : 'Sesli Video Kaydı Başlat'"></span>
                        </button>
                    </template>

                    <template x-if="video_durumu === 'queued' || video_durumu === 'rendering'">
                        <div class="space-y-3">
                            <div
                                class="h-3 w-full overflow-hidden rounded-full bg-gray-200 shadow-inner dark:bg-gray-700">
                                <div class="h-3 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 shadow-sm transition-all duration-500 dark:from-blue-400 dark:to-blue-500 dark:shadow-none"
                                    :style="`width: ${progress}%`"></div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-600 dark:text-gray-400"
                                    x-text="progress + '%'"></span>
                                <span class="text-gray-500 dark:text-gray-500"
                                    x-text="video_durumu === 'queued' ? 'Kuyrukta bekleniyor...' : 'Video işleniyor...'"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="video_durumu === 'completed' && url">
                        <a :href="url" target="_blank" rel="noopener"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-4 text-base font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:bg-emerald-700 hover:shadow-xl focus:ring-4 focus:ring-emerald-500/50 active:scale-95">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Videoyu Aç / İndir</span>
                        </a>
                    </template>

                    <template x-if="video_durumu === 'failed'">
                        <div class="space-y-3">
                            <div
                                class="rounded-lg bg-red-50 p-3 text-sm text-red-600 dark:bg-red-900/20 dark:text-red-400">
                                Video oluşturma başarısız oldu. Tekrar deneyebilirsiniz.
                            </div>
                            <button @click="start()"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-6 py-4 text-base font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:bg-red-700 hover:shadow-xl focus:ring-4 focus:ring-red-500/50 active:scale-95">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>Yeniden Dene</span>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Özellikler Listesi (AraziPro Referanslı) --}}
                <div class="border-b border-slate-200 bg-slate-50 p-6 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong class="text-gray-900 dark:text-slate-100 dark:text-white">TKGM + POI + Yalihan
                                Cortex</strong> Video
                            Script</span>
                    </div>
                    <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong class="text-gray-900 dark:text-slate-100 dark:text-white">Sesli anlatım</strong> +
                            TTS
                            (ElevenLabs)</span>
                    </div>
                    <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong class="text-gray-900 dark:text-slate-100 dark:text-white">1920×1080</strong> • 60
                            FPS • ~45
                            saniye</span>
                    </div>
                    <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Arsa merkezinde <strong class="text-gray-900 dark:text-slate-100 dark:text-white">3 tur
                                360° dönüş</strong>
                            (gelecek özellik)</span>
                    </div>
                    <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong class="text-gray-900 dark:text-slate-100 dark:text-white">1 saniyelik smooth
                                fade</strong>
                            geçişleri</span>
                    </div>
                    <div class="flex items-start gap-3 text-sm text-amber-600 dark:text-amber-400">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Düşük sistemde capture süresi uzayabilir (video hep ~45s)</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ PANEL: Harita Görünümü (2/3 genişlik) --}}
        <div class="lg:col-span-2">
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"
                style="height: 600px;">
                {{-- Harita Container --}}
                <div class="absolute inset-0">
                    @include('admin.ilanlar.components.location-map', [
                        'ilan' => $ilan,
                        'iller' => $iller ?? collect(),
                        'ilceler' => $ilceler ?? collect(),
                        'mahalleler' => $mahalleler ?? collect(),
                    ])
                </div>

                {{-- Lokasyon Bilgileri Overlay (AraziPro Referanslı) - Üst Sol --}}
                <div class="absolute left-4 top-4 z-10 flex flex-col gap-2">
                    <div
                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 shadow-lg dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-2 text-sm">
                            <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ optional($ilan->il)->il_adi }}{{ optional($ilan->ilce)->ilce_adi ? ' / ' . $ilan->ilce->ilce_adi : '' }}
                            </span>
                        </div>
                    </div>
                    @if ($ilan->mahalle)
                        <div
                            class="rounded-lg border border-gray-200 bg-white px-3 py-2 shadow-lg dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span
                                    class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $ilan->mahalle->name }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Danışman Kartı Overlay (AraziPro Referanslı) - Alt Sol --}}
                @if ($ilan->userDanisman)
                    <div
                        class="absolute bottom-4 left-4 z-10 max-w-xs rounded-lg border border-gray-200 bg-white p-4 shadow-xl dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div
                                    class="truncate text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                    {{ $ilan->userDanisman->name }}
                                </div>
                                @if ($ilan->userDanisman->phone_number)
                                    <a href="tel:{{ $ilan->userDanisman->phone_number }}"
                                        class="mt-1 inline-flex items-center gap-1 text-sm text-blue-600 hover:underline dark:text-blue-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        {{ $ilan->userDanisman->phone_number }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ALT BÖLÜM: Ek Özellikler --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Sosyal Medya Gönderisi Oluştur --}}
        <div
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100 dark:text-white">Sosyal Medya Gönderisi
                </h3>
            </div>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                Bu ilan için Instagram, Facebook ve LinkedIn'e uygun sosyal medya gönderisi oluşturun.
            </p>
            <button onclick="generateSocialPost({{ $ilan->id }})"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:scale-105 hover:bg-purple-700 hover:shadow-lg focus:ring-2 focus:ring-purple-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:shadow-none">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>Sosyal Medya Gönderisi Oluştur</span>
            </button>
        </div>

        {{-- Pazar Analizi Metni Oluştur --}}
        <div
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100 dark:text-white">Pazar Analizi</h3>
            </div>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                TKGM verileri ve bölge analizi kullanarak profesyonel pazar analizi metni oluşturun.
            </p>
            <button onclick="generateMarketAnalysis({{ $ilan->id }})"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:scale-105 hover:bg-indigo-700 hover:shadow-lg focus:ring-2 focus:ring-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:shadow-none">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>Pazar Analizi Metni Oluştur</span>
            </button>
        </div>
    </div>
</div>

{{-- Video Status Widget Script (Mevcut widget'tan kopyalandı) --}}
<script>
    function videoDurumWidget(ilanId, startUrl, durumUrl) {
        return {
            video_durumu: 'none',
            progress: 0,
            url: '',
            loading: false,
            durumEtiketi: '',
            init() {
                this.fetchDurum();
                setInterval(() => this.fetchDurum(), 5000);
            },
            fetchDurum() {
                fetch(durumUrl, {
                    headers: {
                        'Accept': 'application/json'
                    }
                }).then(r => r.json()).then(data => {
                    if (!data || data.success === false) return;
                    const payload = data.data || data;
                    this.video_durumu = payload.video_durumu || 'none';
                    this.progress = payload.video_last_frame || 0;
                    this.url = payload.video_url || '';
                    this.updateLabel();
                }).catch(() => {});
            },
            start() {
                this.loading = true;
                fetch(startUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json'
                    }
                }).then(r => r.json()).then(data => {
                    this.loading = false;
                    if (!data || data.success === false) return;
                    const payload = data.data || data;
                    this.video_durumu = payload.video_status || 'queued';
                    this.progress = 0;
                    this.updateLabel();
                }).catch(() => {
                    this.loading = false;
                });
            },
            updateLabel() {
                if (this.video_durumu === 'none') this.durumEtiketi = 'Video henüz oluşturulmadı.';
                else if (this.video_durumu === 'queued') this.durumEtiketi = 'Video kuyruğa alındı.';
                else if (this.video_durumu === 'rendering') this.durumEtiketi = 'Video işleniyor...';
                else if (this.video_durumu === 'completed') this.durumEtiketi = 'Video hazır.';
                else if (this.video_durumu === 'failed') this.durumEtiketi = 'Video oluşturma başarısız.';
            }
        }
    }

    // Placeholder functions for social media and market analysis
    function generateSocialPost(ilanId) {
        alert('Sosyal medya gönderisi oluşturma özelliği yakında eklenecek. İlan ID: ' + ilanId);
    }

    function generateMarketAnalysis(ilanId) {
        alert('Pazar analizi metni oluşturma özelliği yakında eklenecek. İlan ID: ' + ilanId);
    }
</script>
