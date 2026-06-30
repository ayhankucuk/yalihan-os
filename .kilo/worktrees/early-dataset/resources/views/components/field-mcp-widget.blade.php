{{-- 📊 İlan Detay Sayfasında FieldMCP Widget Component --}}
@props([
    'ilan',
    'measurements' => null,
])

<div class="bg-white dark:bg-slate-900 rounded-lg shadow-md overflow-hidden dark:shadow-none">
    <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-purple-600">
        <h3 class="text-lg font-semibold text-white flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            Hardware Doğrulama
        </h3>
    </div>

    <div class="p-6 space-y-4">
        @if($measurements)
            {{-- Bosch GLM Measurement --}}
            @if(isset($measurements['alan_m2']['verified']) && $measurements['alan_m2']['verified'])
            <div class="flex items-start space-x-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                        Alan Ölçümü (Bosch GLM)
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">
                        {{ number_format($measurements['alan_m2']['value'], 2) }} m²
                    </p>
                    <div class="mt-2 flex items-center space-x-4 text-xs text-gray-600 dark:text-gray-400">
                        <span>📱 {{ $measurements['alan_m2']['device'] ?? 'Bosch GLM' }}</span>
                        <span>⚡ ±{{ $ilan->alan_m2_accuracy_mm ?? 50 }}mm</span>
                        @if(isset($measurements['alan_m2']['measured_at']))
                        <span>🕐 {{ \Carbon\Carbon::parse($measurements['alan_m2']['measured_at'])->diffForHumans() }}</span>
                        @endif
                    </div>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            {{ $measurements['alan_m2']['etiketi'] }}
                        </span>
                    </div>
                </div>
            </div>
            @endif

            {{-- FLIR Thermal Analysis --}}
            @if(isset($measurements['flir_analysis']['findings_count']) && $measurements['flir_analysis']['findings_count'] > 0)
            <div class="flex items-start space-x-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                        Termal Analiz (FLIR ONE)
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">
                        {{ $measurements['flir_analysis']['findings_count'] }} Bulgu
                    </p>
                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                        <span>🌡️ {{ $measurements['flir_analysis']['analysis_type'] ?? 'Thermal Scan' }}</span>
                        @if(isset($measurements['flir_analysis']['analyzed_at']))
                        <span class="ml-3">🕐 {{ \Carbon\Carbon::parse($measurements['flir_analysis']['analyzed_at'])->diffForHumans() }}</span>
                        @endif
                    </div>
                    
                    @if($ilan->flir_image_url)
                    <div class="mt-3">
                        <a href="{{ $ilan->flir_image_url }}" target="_blank" 
                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-orange-700 bg-orange-100 rounded hover:bg-orange-200 dark:bg-orange-900 dark:text-orange-200 dark:hover:bg-orange-800 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Termal Görüntüyü Görüntüle
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- No Hardware Verification --}}
            @if(!isset($measurements['alan_m2']['verified']) && !isset($measurements['flir_analysis']['findings_count']))
            <div class="text-center py-8">
                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    Bu ilan için henüz hardware doğrulaması yapılmadı
                </p>
                <button type="button" 
                        onclick="alert('Hardware cihazı bağlayın ve ölçüm yapın')"
                        class="mt-4 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    🔌 Cihaz Bağla
                </button>
            </div>
            @endif
        @else
            {{-- Loading State --}}
            <div class="animate-pulse space-y-4">
                <div class="h-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
        @endif
    </div>
</div>
