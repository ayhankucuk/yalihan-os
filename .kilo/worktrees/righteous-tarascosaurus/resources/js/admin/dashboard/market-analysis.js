export function initMarketAnalysis() {
    const provinceSelect = document.getElementById('mi-province');
    const refreshBtn = document.getElementById('mi-refresh');
    const statusEl = document.getElementById('mi-status');
    const hotspotsStatusEl = document.getElementById('mi-hotspots-status');
    const badgeEl = document.getElementById('mi-hotspots-badge');
    const listEl = document.getElementById('mi-hotspots-list');
    const avgPriceEl = document.getElementById('mi-avg-price');
    const medianPriceEl = document.getElementById('mi-median-price');
    const velocityEl = document.getElementById('mi-sales-velocity');
    const conversionEl = document.getElementById('mi-conversion');

    if (!provinceSelect) return; // Guard clause

    const requiredElements = [
        refreshBtn,
        statusEl,
        hotspotsStatusEl,
        badgeEl,
        listEl,
        avgPriceEl,
        medianPriceEl,
        velocityEl,
        conversionEl,
    ];

    if (requiredElements.some((el) => !el)) {
        return;
    }

    const formatCurrency = (value) => {
        if (value === null || value === undefined || isNaN(value)) return '-';
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
            maximumFractionDigits: 0,
        }).format(value);
    };

    const formatPercent = (value) => {
        if (value === null || value === undefined || isNaN(value)) return '-';
        return `${value.toFixed(1)}%`;
    };

    const setLoading = (isLoading) => {
        if (refreshBtn) {
            refreshBtn.disabled = isLoading;
            refreshBtn.classList.toggle('opacity-60', isLoading);
        }
        if (statusEl) {
            statusEl.textContent = isLoading ? 'API çağrısı yapılıyor…' : 'Güncel veri';
        }
        if (hotspotsStatusEl) {
            hotspotsStatusEl.textContent = isLoading ? 'Hotspot bekleniyor…' : 'Güncel veri';
        }
    };

    const renderHotspots = (items = []) => {
        if (!listEl) return;
        listEl.innerHTML = '';
        if (!items.length) {
            listEl.innerHTML =
                '<p class="text-sm text-gray-500 dark:text-slate-500">Hotspot bulunamadı</p>';
            if (badgeEl) badgeEl.textContent = '0';
            return;
        }
        if (badgeEl) badgeEl.textContent = items.length;
        items.slice(0, 5).forEach((item, idx) => {
            const li = document.createElement('div');
            li.className =
                'flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 dark:border-slate-700';
            li.innerHTML = `
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">${idx + 1}. ${item?.mahalle_adi ?? 'Mahalle'}</p>
                <p class="text-xs text-gray-500 dark:text-slate-500">İlçe: ${item?.ilce_adi ?? '-'} • Güven: ${formatPercent(item?.confidence_level ?? 0)}</p>
            </div>
            <span class="text-sm font-semibold text-blue-600 dark:text-blue-300">${formatCurrency(item?.avg_price ?? 0)}</span>
        `;
            listEl.appendChild(li);
        });
    };

    const fetchData = async () => {
        const ilId = provinceSelect.value || 48;
        setLoading(true);
        try {
            // Stats
            const statsUrl = window.APIConfig.getUrl(window.APIConfig.marketAnalysis.stats);
            const statsRes = await fetch(statsUrl);
            const statsJson = await statsRes.json();
            if (statsJson && statsJson.data) {
                if (avgPriceEl) {
                    avgPriceEl.textContent = formatCurrency(
                        statsJson.data.avg_price ?? statsJson.data.average_price ?? null
                    );
                }
                if (medianPriceEl) {
                    medianPriceEl.textContent = formatCurrency(statsJson.data.median_price ?? null);
                }
                if (velocityEl) {
                    velocityEl.textContent = statsJson.data.sales_velocity
                        ? `${statsJson.data.sales_velocity} gün`
                        : '-';
                }
                if (conversionEl) {
                    conversionEl.textContent = statsJson.data.conversion_rate
                        ? formatPercent(statsJson.data.conversion_rate)
                        : '-';
                }
            } else {
                if (avgPriceEl) avgPriceEl.textContent = '-';
                if (medianPriceEl) medianPriceEl.textContent = '-';
                if (velocityEl) velocityEl.textContent = '-';
                if (conversionEl) conversionEl.textContent = '-';
            }

            // Hotspots
            const hotspotsUrl = window.APIConfig.getUrl(
                window.APIConfig.marketAnalysis.hotspots(ilId)
            );
            const hsRes = await fetch(hotspotsUrl);
            const hsJson = await hsRes.json();
            renderHotspots(hsJson?.data ?? []);
        } catch (e) {
            console.error('Market analysis fetch error', e);
            if (statusEl) statusEl.textContent = 'Hata: Veri alınamadı';
            if (hotspotsStatusEl) hotspotsStatusEl.textContent = 'Hata: Hotspot alınamadı';
        } finally {
            setLoading(false);
        }
    };

    refreshBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        fetchData();
    });
    provinceSelect?.addEventListener('change', fetchData);

    fetchData();
}

document.addEventListener('DOMContentLoaded', initMarketAnalysis);
