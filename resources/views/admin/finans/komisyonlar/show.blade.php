@extends('admin.layouts.admin')

@section('title', 'Komisyon Detayı')

@section('content')
<div class="max-w-4xl mx-auto" x-data="komisyonDetay()">
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700 p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Komisyon Detayı</h1>
            <a href="{{ route('admin.finans.komisyonlar.index') }}"
                class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">
                Geri
            </a>
        </div>

        <div x-show="loading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <div x-show="!loading" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Komisyon Tutarı</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="komisyon ? formatCurrency(komisyon.komisyon_tutari, komisyon.para_birimi) : '-'"></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Durum</h3>
                    <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full"
                        :class="{
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': komisyon?.odeme_statusu === 'hesaplandi',
                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': komisyon?.odeme_statusu === 'onaylandi',
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': komisyon?.odeme_statusu === 'odendi'
                        }"
                        x-text="komisyon ? getStatusLabel(komisyon.odeme_statusu) : '-'">
                    </span>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-slate-700 pt-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">İşlemler</h3>
                <div class="flex gap-3">
                    <template x-if="komisyon?.odeme_statusu === 'hesaplandi'">
                        <button @click="approve()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Onayla
                        </button>
                    </template>
                    <template x-if="komisyon?.odeme_statusu === 'onaylandi'">
                        <button @click="pay()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Öde
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function komisyonDetay() {
    return {
        komisyon: null,
        loading: true,
        async init() {
            await this.loadKomisyon();
        },
        async loadKomisyon() {
            try {
                const id = {{ $id }};
                const res = await fetch(`/api/v1/admin/komisyonlar/${id}`);
                const result = await res.json();
                this.komisyon = result.data || result;
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },
        async approve() {
            if (!confirm('Bu komisyonu onaylamak istediğinizden emin misiniz?')) return;
            try {
                const id = this.komisyon.id;
                await fetch(`/api/v1/admin/komisyonlar/${id}/approve`, { method: 'POST' });
                await this.loadKomisyon();
                window.toast?.('Komisyon onaylandı', 'success');
            } catch (e) {
                window.toast?.('Komisyon onaylanamadı', 'error');
            }
        },
        async pay() {
            if (!confirm('Bu komisyonu ödendi olarak işaretlemek istediğinizden emin misiniz?')) return;
            try {
                const id = this.komisyon.id;
                await fetch(`/api/v1/admin/komisyonlar/${id}/pay`, { method: 'POST' });
                await this.loadKomisyon();
                window.toast?.('Komisyon ödendi olarak işaretlendi', 'success');
            } catch (e) {
                window.toast?.('Komisyon ödenemedi', 'error');
            }
        },
        formatCurrency(amount, currency = 'TRY') {
            return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: currency || 'TRY' }).format(amount || 0);
        },
        getStatusLabel(status) {
            return { hesaplandi: 'Hesaplandı', onaylandi: 'Onaylandı', odendi: 'Ödendi' }[status] || status || '-';
        }
    };
}
</script>
@endpush
