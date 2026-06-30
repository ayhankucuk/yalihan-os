@extends('admin.layouts.admin')

@section('title', 'Kanban Board - Görev Yönetimi')

@section('content')
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">Kanban Board</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Görevleri statüsüne ve personele göre görselleştirin</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.takim.gorevler.index') }}"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-all duration-200 hover:bg-gray-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-gray-700">
                        ← Görev Listesi
                    </a>
                </div>
            </div>
        </div>

        <!-- Personel Filtreleme ve Yeni Görev Butonu -->
        <div
            class="mb-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <form method="GET" action="{{ route('admin.takim.gorevler.board') }}"
                    class="flex min-w-[200px] flex-1 items-center gap-3">
                    <label for="user_id"
                        class="whitespace-nowrap text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        Personel Filtresi:
                    </label>
                    <select name="user_id" id="user_id"
                        class="max-w-xs flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                        <option value="">Tüm Personel</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all duration-200 hover:bg-blue-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:shadow-none">
                        Filtrele
                    </button>
                </form>
                <a href="{{ route('admin.takim.gorevler.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-2.5 text-sm font-medium text-white shadow-md transition-all duration-200 hover:from-blue-700 hover:to-purple-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:shadow-none">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Yeni Görev Ekle
                </a>
            </div>
        </div>

        <!-- Kanban Board - 3 Sütunlu Grid -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- 🔴 YAPILACAKLAR Sütunu -->
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between">
                    <h2
                        class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                        <span class="h-3 w-3 rounded-full bg-red-500"></span>
                        Yapılacaklar
                    </h2>
                    <span
                        class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                        {{ $bekleyenler->count() }}
                    </span>
                </div>
                <div class="min-h-[400px] space-y-3">
                    @forelse($bekleyenler as $gorev)
                        @include('admin.takim.gorevler.partials.gorev-card', ['gorev' => $gorev])
                    @empty
                        <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto mb-2 h-12 w-12 opacity-50" fill="none" stroke="currentColor"
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

            <!-- 🟡 İŞLEMDE Sütunu -->
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between">
                    <h2
                        class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                        <span class="h-3 w-3 rounded-full bg-yellow-500"></span>
                        İşlemde
                    </h2>
                    <span
                        class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        {{ $islemdekiler->count() }}
                    </span>
                </div>
                <div class="min-h-[400px] space-y-3">
                    @forelse($islemdekiler as $gorev)
                        @include('admin.takim.gorevler.partials.gorev-card', ['gorev' => $gorev])
                    @empty
                        <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto mb-2 h-12 w-12 opacity-50" fill="none" stroke="currentColor"
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

            <!-- 🟢 TAMAMLANDI Sütunu -->
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between">
                    <h2
                        class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                        <span class="h-3 w-3 rounded-full bg-green-500"></span>
                        Tamamlandı
                    </h2>
                    <span
                        class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                        {{ $tamamlananlar->count() }}
                    </span>
                </div>
                <div class="min-h-[400px] space-y-3">
                    @forelse($tamamlananlar as $gorev)
                        @include('admin.takim.gorevler.partials.gorev-card', ['gorev' => $gorev])
                    @empty
                        <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto mb-2 h-12 w-12 opacity-50" fill="none" stroke="currentColor"
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
            // Status değiştirme fonksiyonu (Dropdown ile)
            function changeStatus(gorevId, newStatus) {
                if (!confirm('Görev durumunu değiştirmek istediğinize emin misiniz?')) {
                    return;
                }

                updateStatus(gorevId, newStatus);
            }

            // Durumu İlerlet fonksiyonu (Hızlı aksiyon butonu)
            function advanceStatus(gorevId, newStatus) {
                const durumEtiketleri = {
                    'devam_ediyor': 'İşleme almak',
                    'tamamlandi': 'Tamamlamak'
                };

                if (!confirm(`Görevi ${durumEtiketleri[newStatus] || 'güncellemek'} istediğinize emin misiniz?`)) {
                    return;
                }

                updateStatus(gorevId, newStatus);
            }

            // Ortak durum güncelleme fonksiyonu
            function updateStatus(gorevId, newStatus) {
                fetch(`{{ url('/admin/takim-yonetimi/gorevler') }}/${gorevId}/durum-guncelle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            durum: newStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Context7: ResponseService format kontrolü
                        if (data.success) {
                            // Sayfayı yenile
                            window.location.reload();
                        } else {
                            alert(data.message || 'Durum güncellenirken bir hata oluştu.');
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
