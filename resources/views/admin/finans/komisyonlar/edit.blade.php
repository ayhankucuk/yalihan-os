@extends('admin.layouts.admin')

@section('title', 'Komisyon Düzenle')

@section('content')
<div class="max-w-2xl mx-auto" x-data="komisyonEdit()">
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Komisyon Düzenle</h1>

        <div x-show="loading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <form x-show="!loading" @submit.prevent="save()">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                    <textarea x-model="komisyon.notlar" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('admin.finans.komisyonlar.index') }}"
                    class="px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-200 font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">
                    İptal
                </a>
                <button type="submit" class="px-6 py-2 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 transition-colors">
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function komisyonEdit() {
    return {
        komisyon: { notlar: '' },
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
        async save() {
            try {
                const id = this.komisyon.id;
                await fetch(`/api/v1/admin/komisyonlar/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ notlar: this.komisyon.notlar })
                });
                window.toast?.('Komisyon güncellendi', 'success');
                window.location.href = '{{ route("admin.finans.komisyonlar.index") }}';
            } catch (e) {
                window.toast?.('Güncelleme başarısız', 'error');
            }
        }
    };
}
</script>
@endpush
