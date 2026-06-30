@extends('admin.layouts.admin')

@section('title', 'Arsa Hesaplama')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="arsaCalculator()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Arsa Hesaplama</h1>
        <p class="text-gray-600 dark:text-gray-400">TKGM sorgusu ile alanı doldurup KAKS/TAKS hesaplayın</p>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Ada</label>
                <input type="text" x-model="form.ada" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="Ada">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Parsel</label>
                <input type="text" x-model="form.parsel" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="Parsel">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">İl</label>
                <input type="text" x-model="form.il" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="İl">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">İlçe</label>
                <input type="text" x-model="form.ilce" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="İlçe">
            </div>
        </div>

        <div class="mt-6">
            <button @click="tkgmQuery()" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-green-600 text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500">TKGM Sorgula</button>
            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Alan otomatik doldurulur</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Alan (m²)</label>
                <input type="number" step="0.01" x-model.number="form.alan" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Toplam Fiyat</label>
                <input type="number" step="0.01" x-model.number="form.toplam_fiyat" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">KAKS</label>
                <input type="number" step="0.01" x-model.number="form.kaks" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="Örn: 0.30">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">TAKS</label>
                <input type="number" step="0.01" x-model.number="form.taks" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="Örn: 0.20">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Satılabilir Oran (%)</label>
                <input type="number" step="0.1" x-model.number="form.satilabilir_oran" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="Örn: 80">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">İnşaat Birim Maliyeti (TL/m²)</label>
                <input type="number" step="0.01" x-model.number="form.insaat_birim_maliyet" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="Örn: 17000">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Hedef Satış Fiyatı (TL/m²)</label>
                <input type="number" step="0.01" x-model.number="form.hedef_satis_m2_fiyati" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="Örn: 30000">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Finansman Maliyeti (TL)</label>
                <input type="number" step="0.01" x-model.number="form.finansman_maliyeti" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="Örn: 500000">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Vergi/Harç Oranı (%)</label>
                <input type="number" step="0.1" x-model.number="form.vergi_harc_orani" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:text-slate-100" placeholder="Örn: 2">
            </div>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button @click="hesapla()" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">Hesapla</button>
            <button @click="loadHistory()" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 dark:text-slate-300">Geçmişi Yükle</button>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6" x-show="result">
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Max İnşaat Alanı</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="result.max_insaat_alani"></div>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Max Taban Alanı</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="result.max_taban_alani"></div>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700" x-show="result.metre_fiyati">
                <div class="text-xs text-gray-500 dark:text-gray-400">Metre Fiyatı</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="result.metre_fiyati"></div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6" x-show="result">
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Satılabilir Alan</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="result.satilabilir_alani"></div>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Toplam İnşaat Maliyeti</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="result.toplam_insaat_maliyeti"></div>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Toplam Satış Geliri</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="result.toplam_satis_geliri"></div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6" x-show="result">
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Toplam Maliyet</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="result.toplam_maliyet"></div>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Net Kâr</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="result.net_kar"></div>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">ROI</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="(result.roi !== null) ? (result.roi * 100).toFixed(2) + '%' : '—'"></div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6" x-show="result">
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Break-even Satış (TL/m²)</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="result.break_even_m2_fiyati ?? '—'"></div>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Break-even İnşaat Maliyeti (TL/m²)</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="result.break_even_insaat_birim_maliyet ?? '—'"></div>
            </div>
        </div>

        <div class="mt-6" x-show="result">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">Duyarlılık (±%10)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400">İnşaat Maliyeti +%10 ROI</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="(result.sensitivities.roi_insaat_up_10 * 100).toFixed(2) + '%' "></div>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400">İnşaat Maliyeti −%10 ROI</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="(result.sensitivities.roi_insaat_down_10 * 100).toFixed(2) + '%' "></div>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Satış Fiyatı +%10 ROI</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="(result.sensitivities.roi_satis_up_10 * 100).toFixed(2) + '%' "></div>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Satış Fiyatı −%10 ROI</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="(result.sensitivities.roi_satis_down_10 * 100).toFixed(2) + '%' "></div>
                </div>
            </div>
        </div>

        <div class="mt-6" x-show="history.length">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">Geçmiş</h3>
            <div class="space-y-2">
                <template x-for="h in history" :key="h.timestamp">
                    <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <div class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300" x-text="h.timestamp"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Alan: <span x-text="h.input.alan"></span> • KAKS: <span x-text="h.input.kaks"></span> • TAKS: <span x-text="h.input.taks"></span></div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function arsaCalculator() {
    return {
        form: { ada: '', parsel: '', il: '', ilce: '', alan: '', toplam_fiyat: '', kaks: '', taks: '' },
        result: null,
        history: [],
        async tkgmQuery() {
            const payload = { ada: this.form.ada, parsel: this.form.parsel, il: this.form.il, ilce: this.form.ilce };
            const res = await fetch('{{ route('api.tkgm-parsel.query') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(payload) });
            const data = await res.json();
            if (data && data.success && data.data && data.data.alan) {
                this.form.alan = data.data.alan;
            }
        },
        async hesapla() {
            const payload = { ada: this.form.ada, parsel: this.form.parsel, il: this.form.il, ilce: this.form.ilce, alan: this.form.alan, toplam_fiyat: this.form.toplam_fiyat, kaks: this.form.kaks, taks: this.form.taks, satilabilir_oran: this.form.satilabilir_oran, insaat_birim_maliyet: this.form.insaat_birim_maliyet, hedef_satis_m2_fiyati: this.form.hedef_satis_m2_fiyati, finansman_maliyeti: this.form.finansman_maliyeti, vergi_harc_orani: this.form.vergi_harc_orani };
            const res = await fetch('{{ route('api.arsa.calculate') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(payload) });
            const data = await res.json();
            if (data && data.success) { this.result = data.data; }
        },
        async loadHistory() {
            const res = await fetch('{{ route('api.arsa.history') }}');
            const data = await res.json();
            if (data && data.success) { this.history = data.data || []; }
        }
    };
}
</script>
@endpush