<div x-data="videoStatusWidget({{ $ilan->id }}, '{{ route('api.ai.start-video-render', ['ilanId' => $ilan->id]) }}', '{{ route('api.ai.video-durumu', ['ilanId' => $ilan->id]) }}')"
    x-init="init()"
    class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600 flex flex-col justify-between dark:bg-slate-900 dark:border-slate-700">
    <div class="flex items-center justify-between mb-2">
        <div>
            <div class="text-sm font-semibold text-gray-800 dark:text-slate-100 dark:text-slate-200">Video Durumu</div>
            <div class="text-xs text-gray-500 dark:text-gray-400" x-text="durum_etiketi"></div>
        </div>
        <span class="px-2 py-1 rounded-full text-xs font-medium"
            :class="{
                'bg-gray-200 text-gray-800 dark:bg-gray-900 dark:text-gray-200': durum === 'none',
                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200': durum === 'queued' || durum === 'rendering',
                'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200': durum === 'completed',
                'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200': durum === 'failed',
            }"
            x-text="durum"></span>
    </div>

    <div class="flex-1 mt-2">
        <template x-if="durum === 'none'">
            <button @click="start()"
                class="w-full inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                <span x-show="!loading">Video Oluştur</span>
                <span x-show="loading">Kuyruğa Alınıyor...</span>
            </button>
        </template>

        <template x-if="durum === 'queued' || durum === 'rendering'">
            <div class="space-y-2">
                <div class="w-full bg-gray-200 dark:bg-slate-900 rounded-full h-2 overflow-hidden">
                    <div class="h-2 bg-blue-500 dark:bg-blue-400 rounded-full transition-all duration-500"
                        :style="`width: ${progress}%`"></div>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span x-text="progress + '%'"></span>
                    <span>Video işleniyor...</span>
                </div>
            </div>
        </template>

        <template x-if="durum === 'completed' && url">
            <a :href="url" target="_blank" rel="noopener"
                class="w-full inline-flex items-center justify-center px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-emerald-500 transition-all duration-200">
                Videoyu Aç / İndir
            </a>
        </template>

        <template x-if="durum === 'failed'">
            <div class="space-y-2">
                <div class="text-xs text-red-600 dark:text-red-400">Video oluşturma başarısız oldu. Tekrar
                    deneyebilirsiniz.</div>
                <button @click="start()"
                    class="w-full inline-flex items-center justify-center px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-red-500 transition-all duration-200">
                    Yeniden Dene
                </button>
            </div>
        </template>
    </div>
</div>

<script>
    function videoStatusWidget(ilanId, startUrl, durumUrl) {
        return {
            durum: 'none',
            progress: 0,
            url: '',
            loading: false,
            durum_etiketi: '',
            init() {
                this.fetchStatus();
                setInterval(() => this.fetchStatus(), 5000);
            },
            fetchStatus() {
                fetch(durumUrl, {
                    headers: {
                        'Accept': 'application/json'
                    }
                }).then(r => r.json()).then(data => {
                    if (!data || data.success === false) return;
                    const payload = data.data || data;
                    this.durum = payload.video_durumu || 'none';
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                }).then(r => r.json()).then(data => {
                    this.loading = false;
                    if (!data || data.success === false) return;
                    const payload = data.data || data;
                    this.durum = payload.video_status || 'queued';
                    this.progress = 0;
                    this.updateLabel();
                }).catch(() => {
                    this.loading = false;
                });
            },
            updateLabel() {
                if (this.durum === 'none') this.durum_etiketi = 'Video henüz oluşturulmadı.';
                else if (this.durum === 'queued') this.durum_etiketi = 'Video kuyruğa alındı.';
                else if (this.durum === 'rendering') this.durum_etiketi = 'Video işleniyor...';
                else if (this.durum === 'completed') this.durum_etiketi = 'Video hazır.';
                else if (this.durum === 'failed') this.durum_etiketi = 'Video oluşturma başarısız.';
            }
        }
    }
</script>
