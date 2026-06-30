@extends('admin.layouts.admin')

@section('title', 'Acil Satış Fırsatları - Intelligence Dashboard')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">🎯 Acil Satış Fırsatları</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Action Score'a göre sıralanmış en yüksek potansiyelli müşteriler
                </p>
            </div>
            <button type="button" onclick="refreshOpportunities()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200
                   flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Yenile
            </button>
        </div>

        <!-- Opportunities List -->
        <div class="grid grid-cols-1 gap-4" id="opportunities-container">
            @forelse($opportunities as $opp)
                <div
                    class="opportunity-card bg-white dark:bg-gray-800 rounded-lg p-6 border-l-4 shadow-lg
                        transition-all duration-200 hover:shadow-xl
                        {{ match ($opp['priority_level']) {
                            'ACIL' => 'border-red-500 bg-red-50 dark:bg-red-900/20',
                            'YÜKSEK' => 'border-orange-500 bg-orange-50 dark:bg-orange-900/20',
                            'ORTA' => 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20',
                            default => 'border-gray-500',
                        } }}">

                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $opp['kisi_adi'] }}</h3>
                            @if (isset($opp['talep_baslik']))
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $opp['talep_baslik'] }}</p>
                            @endif
                            <div class="mt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Action Score: <span
                                        class="font-bold text-2xl text-gray-900 dark:text-white dark:text-slate-100">{{ $opp['action_score'] }}</span>/100
                                </p>
                            </div>
                        </div>

                        <span
                            class="inline-block px-3 py-1 rounded-full text-sm font-bold {{ match ($opp['priority_level']) {
                                'ACIL' => 'bg-red-500 text-white',
                                'YÜKSEK' => 'bg-orange-500 text-white',
                                'ORTA' => 'bg-yellow-500 text-white',
                                default => 'bg-gray-500 text-white',
                            } }}">
                            {{ $opp['priority_level'] }}
                        </span>
                    </div>

                    <!-- Score Breakdown -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-white dark:bg-gray-700 rounded-lg p-3 dark:bg-slate-900">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Match Score</p>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mb-1">
                                <div class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ min($opp['match_score'], 100) }}%"></div>
                            </div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $opp['match_score'] }}%</p>
                        </div>

                        <div class="bg-white dark:bg-gray-700 rounded-lg p-3 dark:bg-slate-900">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Churn Risk</p>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mb-1">
                                <div class="bg-red-500 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ min($opp['churn_risk'], 100) }}%"></div>
                            </div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $opp['churn_risk'] }}%</p>
                        </div>
                    </div>

                    <!-- Recommendation -->
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-3 mb-4 dark:bg-slate-900">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1 dark:text-slate-100">💡 Tavsiye:</p>
                        <p class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $opp['recommendation'] }}</p>
                    </div>

                    <!-- Top Match Info -->
                    @if (isset($opp['top_match']) && $opp['top_match'])
                        <div
                            class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 mb-4 border border-blue-200 dark:border-blue-800">
                            <p class="text-xs text-blue-700 dark:text-blue-300 font-semibold mb-1">🎯 En İyi Eşleşme:</p>
                            <p class="text-sm text-blue-900 dark:text-blue-100 font-bold">
                                {{ $opp['top_match']['baslik'] ?? 'N/A' }}</p>
                            @if (isset($opp['top_match']['fiyat']))
                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                    ₺{{ number_format($opp['top_match']['fiyat'], 0) }}
                                </p>
                            @endif
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a href="{{ route('admin.kisiler.show', $opp['kisi_id']) }}"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center text-sm
                               transition-all duration-200 hover:scale-105 active:scale-95">
                            Müşteri Sayfası
                        </a>
                        @if (isset($opp['top_match']['ilan_id']))
                            <a href="{{ route('admin.ilanlar.show', $opp['top_match']['ilan_id']) }}"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-center text-sm
                                   transition-all duration-200 hover:scale-105 active:scale-95">
                                İlanı Gör
                            </a>
                        @endif
                        <button type="button" onclick="callCustomer({{ $opp['kisi_id'] }})"
                            class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm
                               transition-all duration-200 hover:scale-105 active:scale-95
                               flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            Telefon Et
                        </button>
                    </div>
                </div>
            @empty
                <div class="bg-gray-100 dark:bg-slate-900 rounded-lg p-8 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-600 dark:text-gray-400 text-lg font-semibold">Şu an fırsat yoktur</p>
                    <p class="text-gray-500 dark:text-gray-500 text-sm mt-2">
                        Yeni ilanlar eklendikçe ve müşteri talepleri oluştukça fırsatlar burada görünecektir.
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                'use strict';

                /**
                 * Fırsatları yenile
                 */
                function refreshOpportunities() {
                    const container = document.getElementById('opportunities-container');
                    if (!container) return;

                    container.innerHTML = `
            <div class="text-center p-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-4 text-gray-600 dark:text-gray-400">Yenileniyor...</p>
            </div>
        `;

                    const url = window.APIConfig?.intelligence?.opportunities ?
                        window.APIConfig.intelligence.opportunities(10) :
                        '/api/intelligence/opportunities?limit=10';

                    fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                throw new Error(data.message || 'Bilinmeyen hata');
                            }
                        })
                        .catch(error => {
                            console.error('Opportunity refresh error:', error);
                            alert('Yenileme hatası: ' + error.message);
                            location.reload();
                        });
                }

                /**
                 * Müşteriyi ara
                 */
                function callCustomer(kisiId) {
                    if (!kisiId) return;
                    window.location.href = `/admin/kisiler/${kisiId}?action=call`;
                }

                // Global functions
                window.refreshOpportunities = refreshOpportunities;
                window.callCustomer = callCustomer;

                // Auto-refresh every 3 hours (10800000 ms)
                setInterval(refreshOpportunities, 10800000);
            })();
        </script>
    @endpush
@endsection
