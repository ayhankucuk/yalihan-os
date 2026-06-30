@php
$nf = new \NumberFormatter('tr_TR', \NumberFormatter::decimal);
$fiyat = data_get($ilan, 'fiyat') ?: data_get($ilan, 'satis_fiyati');
$alan = data_get($ilan, 'alan_m2');
$kira = data_get($ilan, 'kira_bedeli') ?: data_get($ilan, 'aylik_kira');
$pm2 = ($fiyat && $alan) ? round($fiyat / max(1, $alan)) : null;
$yield = ($fiyat && $kira) ? round(($kira * 12 / $fiyat) * 100, 1) : null;
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yatırım Raporu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-white text-gray-900 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
    <div class="max-w-5xl mx-auto p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">YalihanAI Yatırım Karnesi</h1>
            <p class="text-sm">{{ data_get($ilan,'baslik') }}</p>
            <p class="text-sm">{{ data_get($ilan,'lokasyon') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="p-4 border rounded-xl">
                <div class="text-xs">Rozet</div>
                <div id="ir_badge" class="text-lg font-semibold">-</div>
            </div>
            <div class="p-4 border rounded-xl">
                <div class="text-xs">Amortisman (yıl)</div>
                <div id="ir_roi" class="text-lg font-semibold">-</div>
                <div id="ir_basis" class="text-xs text-gray-600">-</div>
            </div>
            <div class="p-4 border rounded-xl">
                <div class="text-xs">Yield %</div>
                <div class="text-lg font-semibold">{{ $yield !== null ? $yield.'%' : '-' }}</div>
            </div>
            <div class="p-4 border rounded-xl">
                <div class="text-xs">₺/m²</div>
                <div class="text-lg font-semibold">{{ $pm2 !== null ? $nf->format($pm2) : '-' }}</div>
            </div>
        </div>
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-3">Piyasa Kıyaslama</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 border rounded-xl">
                    <div class="text-xs mb-2">₺/m² Kıyas</div>
                    <div class="flex items-center gap-3">
                        <div>İlan: <span id="ir_pm2_cur">{{ $pm2 !== null ? $nf->format($pm2) : '-' }}</span></div>
                        <div>Bölge: <span id="ir_pm2_avg">-</span></div>
                        <div><span id="ir_pm2_diff">-</span></div>
                    </div>
                </div>
                <div class="p-4 border rounded-xl">
                    <div class="text-xs mb-2">Amortisman Kıyas</div>
                    <div class="flex items-center gap-3">
                        <div>İlan: <span id="ir_roi_cur">-</span></div>
                        <div>Bölge: <span id="ir_roi_avg">-</span></div>
                        <div><span id="ir_roi_diff">-</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <h2 class="text-xl font-semibold mb-3">Gelir Projeksiyonu</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 border rounded-xl">
                    <div class="text-sm font-medium mb-2">%55 Doluluk</div>
                    <div id="ir_roi_bad" class="text-lg font-semibold">-</div>
                </div>
                <div class="p-4 border rounded-xl">
                    <div class="text-sm font-medium mb-2">%70 Doluluk</div>
                    <div id="ir_roi_base" class="text-lg font-semibold">-</div>
                </div>
                <div class="p-4 border rounded-xl">
                    <div class="text-sm font-medium mb-2">%85 Doluluk</div>
                    <div id="ir_roi_good" class="text-lg font-semibold">-</div>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function(){
        const nf = new Intl.NumberFormat('tr-TR');
        const fiyat = {{ $fiyat ?: 'null' }};
        const gunlukFiyat = {{ data_get($ilan,'gunluk_fiyat') ?: 'null' }};
        const haftalikFiyat = {{ data_get($ilan,'haftalik_fiyat') ?: 'null' }};
        const aylikFiyat = {{ data_get($ilan,'aylik_fiyat') ?: 'null' }};
        const sezonlukFiyat = {{ data_get($ilan,'sezonluk_fiyat') ?: 'null' }};
        const occBase = 0.7;
        const seasonDays = 90;
        let annual = null;
        if (sezonlukFiyat) {
            annual = sezonlukFiyat;
        } else if (gunlukFiyat) {
            annual = Math.round(gunlukFiyat * seasonDays * occBase);
        } else if (haftalikFiyat) {
            annual = Math.round(haftalikFiyat * Math.max(1, Math.round(seasonDays/7)) * occBase);
        } else if (aylikFiyat) {
            annual = Math.round(aylikFiyat * Math.max(1, Math.round(seasonDays/30)) * occBase);
        }
        const roiYears = (fiyat && annual) ? (fiyat / annual) : null;
        const badge = (() => {
            if (roiYears !== null && roiYears <= 10) return 'A+';
            if (roiYears !== null && roiYears <= 15) return 'A';
            return 'B';
        })();
        const setText = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val === null ? '-' : (typeof val === 'number' ? nf.format(Math.round(val)) : val);
        };
        setText('ir_roi', roiYears !== null ? roiYears.toFixed(1) + ' yıl' : '-');
        setText('ir_badge', badge);
        let basis = '-';
        if (sezonlukFiyat) basis = 'Sezonluk fiyat üzerinden';
        else if (gunlukFiyat) basis = 'Günlük × gün × doluluk';
        else if (haftalikFiyat) basis = 'Haftalık × hafta × doluluk';
        else if (aylikFiyat) basis = 'Aylık × ay × doluluk';
        setText('ir_basis', basis);
        const pm2Cur = document.getElementById('ir_pm2_cur').textContent.replace(/[^\d]/g,'');
        const roiCurNum = roiYears !== null ? roiYears : null;
        const ilId = {{ data_get($ilan,'il_id') ?: 'null' }};
        const ilceId = {{ data_get($ilan,'ilce_id') ?: 'null' }};
        const kategoriSlug = '{{ data_get($ilan,'kategori_slug') }}';
        const build = (metric) => {
            const qs = [];
            qs.push('metric='+metric);
            if (ilId) qs.push('il_id='+encodeURIComponent(ilId));
            if (ilceId) qs.push('ilce_id='+encodeURIComponent(ilceId));
            if (kategoriSlug) qs.push('kategori_slug='+encodeURIComponent(kategoriSlug));
            return '/api/v1/analytics/benchmark?'+qs.join('&');
        };
        fetch(build('price_m2')).then(r=>r.json()).then(j=>{
            const avg = j && j.data ? j.data.avg : null;
            if (avg) {
                document.getElementById('ir_pm2_avg').textContent = nf.format(Math.round(avg));
                const cur = parseFloat(pm2Cur) || null;
                if (cur) {
                    const diff = ((cur - avg) / avg) * 100;
                    const arrow = diff > 0 ? '↑' : (diff < 0 ? '↓' : '→');
                    document.getElementById('ir_pm2_diff').textContent = arrow+' '+Math.round(diff)+'%';
                }
            }
        }).catch(()=>{});
        fetch(build('amortization')).then(r=>r.json()).then(j=>{
            const avgM = j && j.data ? j.data.avg_months : null;
            if (avgM) {
                document.getElementById('ir_roi_avg').textContent = Math.round(avgM)+' ay';
                const cur = roiCurNum !== null ? roiCurNum*12 : null;
                if (cur) {
                    const diff = Math.round(cur - avgM);
                    const arrow = diff > 0 ? '↑' : (diff < 0 ? '↓' : '→');
                    document.getElementById('ir_roi_diff').textContent = arrow+' '+diff+' ay';
                }
            }
        }).catch(()=>{});
        const calcRoiYears = (occ) => {
            let ann = null;
            if (sezonlukFiyat) {
                ann = sezonlukFiyat;
            } else if (gunlukFiyat) {
                ann = Math.round(gunlukFiyat * seasonDays * occ);
            } else if (haftalikFiyat) {
                ann = Math.round(haftalikFiyat * Math.max(1, Math.round(seasonDays/7)) * occ);
            } else if (aylikFiyat) {
                ann = Math.round(aylikFiyat * Math.max(1, Math.round(seasonDays/30)) * occ);
            }
            return (fiyat && ann) ? (fiyat / ann) : null;
        };
        setText('ir_roi_bad', (()=>{ const v = calcRoiYears(0.55); return v!==null ? v.toFixed(1)+' yıl' : '-'; })());
        setText('ir_roi_base', (()=>{ const v = calcRoiYears(0.70); return v!==null ? v.toFixed(1)+' yıl' : '-'; })());
        setText('ir_roi_good', (()=>{ const v = calcRoiYears(0.85); return v!==null ? v.toFixed(1)+' yıl' : '-'; })());
        setTimeout(()=>{ window.print(); }, 500);
    })();
    </script>
</body>
</html>
