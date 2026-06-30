{{-- 🎨 Section 3.5: Arsa Calculation (Tailwind Modernized) --}}
@php
    $anaKategoriSlug = $ilan->anaKategori->slug ?? '';
    $isArsa = $anaKategoriSlug === 'arsa' || str_contains($ilan->altKategori->slug ?? '', 'arsa');
@endphp

@if($isArsa)
<div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-800 p-8 hover:shadow-2xl transition-shadow duration-300 dark:border-slate-700">
    <!-- Section Header -->
    <div class="flex items-center gap-4 mb-8 pb-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/50 font-bold text-lg">
            3.5
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Arsa Hesaplamaları
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">KAKS/TAKS hesaplamaları ve TKGM sorgulama</p>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Hesaplama Sonuçları --}}
        <div id="arsa-calculation-results" class="hidden">
            <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6">
                <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Hesaplama Sonuçları
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="calculation-results-grid">
                    <!-- Results will be populated by JavaScript -->
                </div>
            </div>
        </div>

        {{-- Hesaplama Butonları --}}
        <div class="flex flex-wrap gap-4">
            <button type="button" 
                    id="calculate-arsa-btn"
                    onclick="calculateArsa()"
                    class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold shadow-lg shadow-emerald-500/50 hover:shadow-xl hover:scale-105 active:scale-95 focus:ring-4 focus:ring-emerald-500/20 transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                KAKS/TAKS Hesapla
            </button>

            @if($ilan->ada_no && $ilan->parsel_no)
            <button type="button" 
                    id="tkgm-query-btn"
                    onclick="queryTKGM()"
                    class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-semibold shadow-lg shadow-blue-500/50 hover:shadow-xl hover:scale-105 active:scale-95 focus:ring-4 focus:ring-blue-500/20 transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                TKGM Sorgula
            </button>
            @endif
        </div>

        {{-- Bilgilendirme --}}
        <div class="bg-amber-50 dark:bg-amber-900/20 border-2 border-amber-200 dark:border-amber-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-amber-800 dark:text-amber-200">
                    <p class="font-semibold mb-1">Hesaplama Bilgileri:</p>
                    <ul class="list-disc list-inside space-y-1 text-amber-700 dark:text-amber-300">
                        <li><strong>KAKS:</strong> Kat Alanı Katsayısı (Toplam inşaat alanı / Arsa alanı)</li>
                        <li><strong>TAKS:</strong> Taban Alanı Katsayısı (Taban alanı / Arsa alanı)</li>
                        <li><strong>Maksimum İnşaat Alanı:</strong> Arsa Alanı × KAKS</li>
                        <li><strong>Maksimum Taban Alanı:</strong> Arsa Alanı × TAKS</li>
                        <li><strong>Maksimum Kat Sayısı:</strong> KAKS ÷ TAKS</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function calculateArsa() {
    const alanM2 = parseFloat(document.getElementById('net_m2')?.value || document.getElementById('brut_m2')?.value || 0);
    const kaks = parseFloat(document.getElementById('kaks')?.value || 0);
    const taks = parseFloat(document.getElementById('taks')?.value || 0);

    if (!alanM2 || alanM2 <= 0) {
        alert('Lütfen önce arsa alanını giriniz (m²)');
        return;
    }

    const calculations = {
        alan_m2: alanM2,
        alan_dunum: (alanM2 / 1000).toFixed(3),
        kaks: kaks,
        taks: taks,
        maksimum_insaat_alani: kaks > 0 ? (alanM2 * kaks).toFixed(2) : 0,
        maksimum_taban_alani: taks > 0 ? (alanM2 * taks).toFixed(2) : 0,
        maksimum_kat_sayisi: (kaks > 0 && taks > 0) ? Math.ceil(kaks / taks) : 0
    };

    // Display results
    const resultsDiv = document.getElementById('arsa-calculation-results');
    const resultsGrid = document.getElementById('calculation-results-grid');
    
    resultsGrid.innerHTML = `
        <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-800">
            <div class="text-sm text-gray-600 dark:text-gray-400">Arsa Alanı</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">${calculations.alan_m2} m²</div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">${calculations.alan_dunum} dönüm</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border-2 border-emerald-200 dark:border-emerald-800">
            <div class="text-sm text-gray-600 dark:text-gray-400">Maks. İnşaat Alanı</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">${calculations.maksimum_insaat_alani} m²</div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">KAKS: ${calculations.kaks}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border-2 border-purple-200 dark:border-purple-800">
            <div class="text-sm text-gray-600 dark:text-gray-400">Maks. Taban Alanı</div>
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">${calculations.maksimum_taban_alani} m²</div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">TAKS: ${calculations.taks}</div>
        </div>
        ${calculations.maksimum_kat_sayisi > 0 ? `
        <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border-2 border-orange-200 dark:border-orange-800">
            <div class="text-sm text-gray-600 dark:text-gray-400">Maks. Kat Sayısı</div>
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">${calculations.maksimum_kat_sayisi}</div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">Kat</div>
        </div>
        ` : ''}
    `;
    
    resultsDiv.classList.remove('hidden');
}

function queryTKGM() {
    const adaNo = document.getElementById('ada_no')?.value;
    const parselNo = document.getElementById('parsel_no')?.value;
    
    if (!adaNo || !parselNo) {
        alert('Ada ve Parsel numaralarını giriniz');
        return;
    }

    // TKGM sorgulama için API endpoint'e istek gönder
    fetch('/admin/api/arsa/tkgm-query', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({
            ada: adaNo,
            parsel: parselNo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('TKGM sorgulama başarılı!');
            // TKGM verilerini form alanlarına doldur
            if (data.data) {
                // İmar statusu, KAKS, TAKS gibi bilgileri doldur
                console.log('TKGM Data:', data.data);
            }
        } else {
            alert('TKGM sorgulama hatası: ' + (data.message || 'Bilinmeyen hata'));
        }
    })
    .catch(error => {
        console.error('TKGM Query Error:', error);
        alert('TKGM sorgulama sırasında bir hata oluştu');
    });
}
</script>
@endpush
@endif

