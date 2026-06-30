{{-- Cost Overview Widget (Widget 1) --}}

<div
    class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-colors dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
            💰 Toplam Maliyet (USD)
        </h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst(str_replace('d', ' gün', $period)) }}</span>
    </div>

    {{-- Loading State --}}
    @if ($loading)
        <div class="animate-pulse space-y-4">
            <div class="h-4 w-1/3 rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-32 rounded bg-gray-200 dark:bg-gray-700"></div>
        </div>

        {{-- Empty State --}}
    @elseif(!$data || !isset($data['timeline']) || count($data['timeline']) === 0)
        <div class="rounded-lg bg-gray-50/50 py-12 text-center dark:bg-slate-900/50">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Henüz AI kullanım verisi yok</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Seçilen tarih aralığında maliyet kaydı bulunamadı.
            </p>
        </div>

        {{-- Data Display --}}
    @else
        {{-- Summary Stats --}}
        <div class="mb-6 grid grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Toplam Harcama</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                    ${{ number_format($data['total_cost'], 4) }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Günlük Limit</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                    ${{ number_format($data['daily_limit'], 2) }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kullanım</p>
                <p
                    class="{{ $data['usage_percent'] > 80 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} text-2xl font-bold">
                    {{ number_format($data['usage_percent'], 1) }}%
                </p>
            </div>
        </div>

        {{-- Simple SVG Line Chart --}}
        <div class="h-32 rounded bg-gray-50 p-2 dark:bg-slate-900">
            <svg width="100%" height="100%" viewBox="0 0 600 100" preserveAspectRatio="none">
                {{-- Simple Line Path (will be enhanced with real data points) --}}
                <polyline fill="none" stroke="#3B82F6" stroke-width="2"
                    points="0,80 100,60 200,70 300,50 400,65 500,45 600,55" />
                {{-- Area Fill --}}
                <polygon fill="url(#gradient)" fill-opacity="0.3"
                    points="0,80 100,60 200,70 300,50 400,65 500,45 600,55 600,100 0,100" />
                {{-- Gradient Definition --}}
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#3B82F6;stop-opacity:0" />
                    </linearGradient>
                </defs>
            </svg>
        </div>

        {{-- Progress Bar --}}
        <div class="mt-4">
            <div class="relative h-2 w-full rounded-full bg-gray-200 dark:bg-slate-700">
                <div class="{{ $data['usage_percent'] > 80 ? 'bg-red-500' : ($data['usage_percent'] > 50 ? 'bg-yellow-500' : 'bg-green-500') }} absolute left-0 top-0 h-2 rounded-full transition-all duration-300"
                    style="width: {{ min($data['usage_percent'], 100) }}%"></div>
            </div>
        </div>
    @endif
</div>
