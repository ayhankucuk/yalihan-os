{{--
    Market Competition Widget
    
    Context7 Standard: C7-WIDGET-MARKET-COMPETITION-2025-12-05
    Yalıhan Bekçi: Temiz, düzenli, karmaşık kod yok
    
    Son aktif ilanlar için rakip analizi gösteren dashboard widget'ı
--}}

<div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 overflow-hidden transition-all duration-200 hover:shadow-xl dark:border-slate-700">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm dark:bg-slate-900/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">📊 Pazar Analizi</h3>
                    <p class="text-sm text-white/80">Rakip analizi ve fiyat önerileri</p>
                </div>
            </div>
            <button 
                type="button"
                id="market-competition-widget-refresh"
                class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-95 backdrop-blur-sm dark:bg-slate-900/20"
                title="Yenile">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Content --}}
    <div class="p-6">
        {{-- Loading State --}}
        <div id="market-competition-widget-loading" class="hidden">
            <div class="flex items-center justify-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-400">Yükleniyor...</span>
            </div>
        </div>

        {{-- Error State --}}
        <div id="market-competition-widget-error" class="hidden">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-center">
                <p class="text-sm text-red-700 dark:text-red-300">Analiz yüklenirken hata oluştu</p>
                <button 
                    type="button"
                    onclick="loadMarketCompetition()"
                    class="mt-2 text-sm text-red-600 dark:text-red-400 hover:underline">
                    Tekrar Dene
                </button>
            </div>
        </div>

        {{-- Analysis List --}}
        <div id="market-competition-widget-list" class="space-y-3">
            {{-- Will be populated by JavaScript --}}
        </div>

        {{-- Empty State --}}
        <div id="market-competition-widget-empty" class="hidden text-center py-8">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="text-gray-600 dark:text-gray-400 font-medium">Analiz için ilan seçin</p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Aktif ilanlarınız için rakip analizi yapılacak</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    'use strict';

    const WIDGET_ID = 'market-competition-widget';
    const CACHE_KEY = 'market_competition_widget_data';
    const CACHE_TTL = 10 * 60 * 1000; // 10 dakika

    /**
     * Cache kontrolü
     */
    function getCachedData() {
        try {
            const cached = localStorage.getItem(CACHE_KEY);
            if (!cached) return null;

            const data = JSON.parse(cached);
            const now = Date.now();

            if (now - data.timestamp < CACHE_TTL) {
                return data.analyses;
            }

            localStorage.removeItem(CACHE_KEY);
            return null;
        } catch (e) {
            return null;
        }
    }

    /**
     * Cache'e kaydet
     */
    function setCachedData(analyses) {
        try {
            localStorage.setItem(CACHE_KEY, JSON.stringify({
                analyses: analyses,
                timestamp: Date.now()
            }));
        } catch (e) {
            // Ignore storage errors
        }
    }

    /**
     * State yönetimi
     */
    function showState(state) {
        const states = ['loading', 'error', 'empty', 'list'];
        states.forEach(s => {
            const el = document.getElementById(`${WIDGET_ID}-${s}`);
            if (el) el.classList.add('hidden');
        });

        const targetEl = document.getElementById(`${WIDGET_ID}-${state}`);
        if (targetEl) targetEl.classList.remove('hidden');
    }

    /**
     * Fiyat farkı rengi
     */
    function getPriceGapColor(percent) {
        if (percent > 10) return { bg: 'bg-red-500', text: 'text-red-700 dark:text-red-300', border: 'border-red-500' };
        if (percent > 5) return { bg: 'bg-orange-500', text: 'text-orange-700 dark:text-orange-300', border: 'border-orange-500' };
        if (percent > 0) return { bg: 'bg-yellow-500', text: 'text-yellow-700 dark:text-yellow-300', border: 'border-yellow-500' };
        return { bg: 'bg-green-500', text: 'text-green-700 dark:text-green-300', border: 'border-green-500' };
    }

    /**
     * Analysis card HTML oluştur
     */
    function createAnalysisCard(analysis) {
        const priceGap = analysis.price_gap_percent || 0;
        const colors = getPriceGapColor(priceGap);
        const competitorCount = analysis.total_competitors || 0;
        const confidence = analysis.confidence || 0;

        return `
            <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border-l-4 ${colors.border} shadow-sm hover:shadow-md transition-all duration-200 dark:shadow-none dark:bg-slate-900">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate dark:text-slate-100">${escapeHtml(analysis.our_listing?.title || 'İlan')}</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            ${escapeHtml(analysis.our_listing?.location || 'Lokasyon bilgisi yok')}
                        </p>
                    </div>
                    <div class="ml-2 text-right">
                        <p class="text-xs font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            ₺${formatNumber(analysis.our_listing?.price || 0)}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Bizim Fiyat</p>
                    </div>
                </div>

                ${competitorCount > 0 ? `
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Rakip Sayısı</span>
                            <span class="text-xs font-bold text-gray-900 dark:text-white dark:text-slate-100">${competitorCount} ilan</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full transition-all duration-300" style="width: ${Math.min(confidence, 100)}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Güven: ${Math.round(confidence)}%</p>
                    </div>
                ` : ''}

                ${analysis.price_gap_percent !== undefined ? `
                    <div class="mb-3 p-2 rounded ${colors.bg}/10 border ${colors.border}/30">
                        <div class="flex items-center justify-between">
                            <span class="text-xs ${colors.text} font-medium">Fiyat Farkı</span>
                            <span class="text-sm font-bold ${colors.text}">
                                ${priceGap > 0 ? '+' : ''}${priceGap.toFixed(1)}%
                            </span>
                        </div>
                        ${analysis.suggested_price ? `
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Öneri: ₺${formatNumber(analysis.suggested_price)}
                            </p>
                        ` : ''}
                    </div>
                ` : ''}

                ${analysis.recommendation ? `
                    <div class="mb-3 p-2 bg-gray-50 dark:bg-slate-900 rounded text-xs text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        ${escapeHtml(analysis.recommendation)}
                    </div>
                ` : ''}

                <div class="flex gap-2">
                    <a 
                        href="/admin/ilanlar/${analysis.our_listing?.id || ''}"
                        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium px-3 py-2 rounded-lg text-center transition-all duration-200 hover:scale-105 active:scale-95">
                        İlanı Gör
                    </a>
                    ${competitorCount > 0 ? `
                        <button 
                            type="button"
                            onclick="showCompetitors(${analysis.our_listing?.id || 0})"
                            class="flex-1 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium px-3 py-2 rounded-lg transition-all duration-200 hover:scale-105 active:scale-95">
                            Rakip Listesi
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }

    /**
     * HTML escape
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Number format
     */
    function formatNumber(num) {
        return new Intl.NumberFormat('tr-TR').format(Math.round(num || 0));
    }

    /**
     * Son aktif ilanları al
     * Context7: Temiz kod, basit yaklaşım
     */
    async function getRecentActiveListings() {
        try {
            // Dashboard'dan gelen recentIlanlar'ı kullan (eğer varsa)
            // Veya basit bir API çağrısı yap
            const response = await fetch('/admin/dashboard/refresh', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                return [];
            }

            const data = await response.json();
            if (data.success && data.data && data.data.recentIlanlar) {
                // Sadece aktif ilanları filtrele ve max 3 al
                return data.data.recentIlanlar
                    .filter(ilan => (ilan.status === 'Aktif' || ilan.status === 1) && ilan.id)
                    .slice(0, 3);
            }
            return [];
        } catch (error) {
            console.error('Get listings error:', error);
            return [];
        }
    }

    /**
     * Rakip analizi yükle
     */
    async function loadMarketCompetition(forceRefresh = false) {
        // Cache kontrolü
        if (!forceRefresh) {
            const cached = getCachedData();
            if (cached && cached.length > 0) {
                renderAnalyses(cached);
                return;
            }
        }

        showState('loading');

        try {
            // Son aktif ilanları al
            const listings = await getRecentActiveListings();
            
            if (!listings || listings.length === 0) {
                showState('empty');
                return;
            }

            // Her ilan için rakip analizi yap (max 3 ilan)
            const analyses = [];
            const topListings = listings.slice(0, 3);

            for (const listing of topListings) {
                if (!listing.id) continue;

                try {
                    const url = window.APIConfig.ai.marketCompetition(listing.id);
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin'
                    });

                    if (!response.ok) continue;

                    const data = await response.json();
                    if (!data.success || !data.data) continue;

                    // CompetitorMapService format'ına çevir
                    const analysis = {
                        our_listing: {
                            id: listing.id,
                            title: listing.baslik || 'İlan',
                            price: parseFloat(listing.fiyat || 0),
                            location: (listing.il?.il_adi || listing.il?.adi || '') + 
                                     (listing.ilce?.ilce_adi || listing.ilce?.adi ? ', ' + (listing.ilce.ilce_adi || listing.ilce.adi) : ''),
                        },
                        total_competitors: data.data.competitor_count || data.data.total_competitors || 0,
                        price_gap_percent: data.data.price_analysis?.price_difference_percent || data.data.price_gap_percent || 0,
                        price_gap: data.data.price_analysis?.price_difference || data.data.price_gap || 0,
                        recommendation: data.data.recommendation || '',
                        suggested_price: data.data.suggested_price || null,
                        confidence: Math.min((data.data.competitor_count || data.data.total_competitors || 0) * 33, 100),
                    };

                    // CompetitorMapService'den gelen veriyi kontrol et
                    if (data.data.top_competitors) {
                        analysis.top_competitors = data.data.top_competitors;
                    }

                    analyses.push(analysis);
                } catch (error) {
                    console.error(`Analysis error for listing ${listing.id}:`, error);
                }
            }

            if (analyses.length === 0) {
                showState('empty');
                return;
            }

            setCachedData(analyses);
            renderAnalyses(analyses);
        } catch (error) {
            console.error('Market competition error:', error);
            showState('error');
        }
    }

    /**
     * Analizleri render et
     */
    function renderAnalyses(analyses) {
        const listEl = document.getElementById(`${WIDGET_ID}-list`);
        if (!listEl) return;

        if (!analyses || analyses.length === 0) {
            showState('empty');
            return;
        }

        listEl.innerHTML = analyses.map(analysis => createAnalysisCard(analysis)).join('');
        showState('list');
    }

    /**
     * Rakip listesini göster
     */
    function showCompetitors(ilanId) {
        window.location.href = `/admin/ilanlar/${ilanId}?tab=competitors`;
    }

    /**
     * Event listeners
     */
    function init() {
        const refreshBtn = document.getElementById(`${WIDGET_ID}-refresh`);
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => loadMarketCompetition(true));
        }

        // İlk yükleme
        loadMarketCompetition();

        // Global function (backward compatibility)
        window.loadMarketCompetition = loadMarketCompetition;
        window.showCompetitors = showCompetitors;
    }

    // DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush

