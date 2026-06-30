@extends('admin.layouts.admin')

@section('title', 'Kanban Board - Takım Yönetimi')

@section('content')
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">Kanban Board</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Görevleri statüsüne ve personele göre görselleştirin</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.takim.takimlar.index') }}"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                        ← Geri Dön
                    </a>
                </div>
            </div>
        </div>

        <!-- Danışman Filtresi -->
        <div class="mb-6">
            <form method="GET" action="{{ route('admin.takim.board') }}" class="flex items-center gap-3">
                <label for="danisman_id" class="text-sm font-medium text-gray-700 dark:text-slate-200">
                    Danışman Filtresi:
                </label>
                <select name="danisman_id" id="danisman_id" onchange="this.form.submit()"
                    class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                    <option value="">Tüm Danışmanlar</option>
                    @foreach ($danismanlar as $danisman)
                        <option value="{{ $danisman->id }}" {{ $selectedDanismanId == $danisman->id ? 'selected' : '' }}>
                            {{ $danisman->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <!-- Kanban Board - 3 Sütunlu Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Yapılacaklar Sütunu -->
            <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4 border border-gray-200 dark:border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                        <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                        Yapılacaklar
                    </h2>
                    <span
                        class="px-2 py-1 text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full">
                        {{ $gorevlerByDurum['bekliyor']->count() }}
                    </span>
                </div>
                <div class="space-y-3 min-h-[400px]">
                    @forelse($gorevlerByDurum['bekliyor'] as $gorev)
                        @include('admin.takim-yonetimi.takim.partials.gorev-card', ['gorev' => $gorev])
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <p class="text-sm">Görev bulunmuyor</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- İşlemde Sütunu -->
            <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4 border border-gray-200 dark:border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                        <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                        İşlemde
                    </h2>
                    <span
                        class="px-2 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full">
                        {{ $gorevlerByDurum['devam_ediyor']->count() }}
                    </span>
                </div>
                <div class="space-y-3 min-h-[400px]">
                    @forelse($gorevlerByDurum['devam_ediyor'] as $gorev)
                        @include('admin.takim-yonetimi.takim.partials.gorev-card', ['gorev' => $gorev])
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <p class="text-sm">Görev bulunmuyor</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Tamamlandı Sütunu -->
            <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4 border border-gray-200 dark:border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                        <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                        Tamamlandı
                    </h2>
                    <span
                        class="px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full">
                        {{ $gorevlerByDurum['tamamlandi']->count() }}
                    </span>
                </div>
                <div class="space-y-3 min-h-[400px]">
                    @forelse($gorevlerByDurum['tamamlandi'] as $gorev)
                        @include('admin.takim-yonetimi.takim.partials.gorev-card', ['gorev' => $gorev])
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <p class="text-sm">Görev bulunmuyor</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Status değiştirme fonksiyonu
            function changeStatus(gorevId, newStatus) {
                if (!confirm('Görev statusunu değiştirmek istediğinize emin misiniz?')) {
                    return;
                }

                fetch(`{{ url('/admin/takim-yonetimi/gorevler') }}/${gorevId}/status-guncelle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            status: newStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Sayfayı yenile
                            window.location.reload();
                        } else {
                            alert('Durum güncellenirken bir hata oluştu.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Durum güncellenirken bir hata oluştu.');
                    });
            }
        </script>
    @endpush
@endsection
