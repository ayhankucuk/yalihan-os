@extends('admin.layouts.admin')

@section('title', 'Yeni Komisyon')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Yeni Komisyon</h1>

        <form x-data="{
            ilan_id: '',
            kisi_id: '',
            danisman_id: '',
            komisyon_tipi: 'satis',
            komisyon_orani: '',
            ilan_fiyati: '',
            para_birimi: 'TRY',
            notlar: '',
            ilanlar: [],
            kisiler: [],
            danismanlar: [],
            loading: false,
            async searchIlanlar(term) {
                if (term.length < 2) return;
                const res = await fetch(`/api/admin/list/ilanlar?q=${term}`);
                const data = await res.json();
                this.ilanlar = data.data || [];
            },
            async searchKisiler(term) {
                if (term.length < 2) return;
                const res = await fetch(`/api/admin/kisi/search?q=${term}`);
                const data = await res.json();
                this.kisiler = data.data || [];
            }
        }" @submit="loading = true">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İlan</label>
                    <select x-model="ilan_id" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200">
                        <option value="">İlan seçin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Müşteri</label>
                    <select x-model="kisi_id" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200">
                        <option value="">Müşteri seçin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Danışman</label>
                    <select x-model="danisman_id" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200">
                        <option value="">Danışman seçin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Komisyon Tipi</label>
                    <select x-model="komisyon_tipi" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200">
                        <option value="satis">Satış</option>
                        <option value="kiralama">Kiralama</option>
                        <option value="danismanlik">Danışmanlık</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İlan Fiyatı</label>
                        <input type="number" x-model="ilan_fiyati" required step="0.01" min="0"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Para Birimi</label>
                        <select x-model="para_birimi"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200">
                            <option value="TRY">TRY</option>
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                    <textarea x-model="notlar" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('admin.finans.komisyonlar.index') }}"
                    class="px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-200 font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">
                    İptal
                </a>
                <button type="submit" :disabled="loading"
                    class="px-6 py-2 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 disabled:opacity-50 transition-colors">
                    <span x-show="!loading">Kaydet</span>
                    <span x-show="loading">Kaydediliyor...</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
