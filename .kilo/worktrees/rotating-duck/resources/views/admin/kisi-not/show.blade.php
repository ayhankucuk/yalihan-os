@extends('admin.layouts.admin')

@section('title', 'Not Detayları - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-sticky-note text-white text-xl"></i>
                    </div>
                    {{ $not['baslik'] ?? 'Not Detayları' }}
                </h1>
                <p class="text-lg text-gray-600 mt-2">Not detayları ve ilgili bilgiler</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.kisi-not.edit', $not['id']) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none">
                    <i class="fas fa-edit mr-2"></i>
                    Düzenle
                </a>
                <a href="{{ route('admin.kisi-not.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Geri Dön
                </a>
            </div>
        </div>
    </div>

    <div class="px-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Note Content -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-start justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200">Not İçeriği</h2>
                        @if ($not['is_completed'] ?? false)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i>
                                Tamamlandı
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>
                                Devam Ediyor
                            </span>
                        @endif
                    </div>

                    <div class="prose max-w-none">
                        <div class="text-gray-700 whitespace-pre-wrap dark:text-slate-300">{{ $not['icerik'] ?? 'İçerik bulunamadı' }}</div>
                    </div>

                    @if (isset($not['tags']) && is_array($not['tags']))
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-700">
                            <h4 class="text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Etiketler</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($not['tags'] as $tag)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        #{{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Note History -->
                @if (isset($noteHistory) && count($noteHistory) > 0)
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Not Geçmişi</h2>
                        <div class="space-y-4">
                            @foreach ($noteHistory as $history)
                                <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg dark:bg-slate-900">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                            {{ $history['action'] ?? 'Değişiklik' }}</div>
                                        <div class="text-sm text-gray-600">{{ $history['description'] ?? 'Açıklama yok' }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ \Carbon\Carbon::parse($history['created_at'] ?? now())->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Related Notes -->
                @if (isset($relatedNotes) && count($relatedNotes) > 0)
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">İlgili Notlar</h2>
                        <div class="space-y-3">
                            @foreach ($relatedNotes as $relatedNote)
                                <div
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors dark:bg-slate-900">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $relatedNote['baslik'] ?? 'Başlıksız' }}
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            {{ Str::limit($relatedNote['icerik'] ?? '', 100) }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ \Carbon\Carbon::parse($relatedNote['created_at'] ?? now())->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.kisi-not.show', $relatedNote['id']) }}"
                                        class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Note Information -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Not Bilgileri</h3>
                    <div class="space-y-4">
                        <div>
                            <span class="text-sm text-gray-600">Kategori</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($not['kategori'] ?? 'genel') }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="text-sm text-gray-600">Önem Derecesi</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                @php
                                    $importance = $not['onem_derecesi'] ?? 'orta';
                                    $importanceColors = [
                                        'dusuk' => 'bg-gray-100 text-gray-800',
                                        'orta' => 'bg-yellow-100 text-yellow-800',
                                        'yuksek' => 'bg-orange-100 text-orange-800',
                                        'kritik' => 'bg-red-100 text-red-800',
                                    ];
                                    $importanceLabels = [
                                        'dusuk' => 'Düşük',
                                        'orta' => 'Orta',
                                        'yuksek' => 'Yüksek',
                                        'kritik' => 'Kritik',
                                    ];
                                @endphp
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full {{ $importanceColors[$importance] ?? $importanceColors['orta'] }}">
                                    {{ $importanceLabels[$importance] ?? 'Orta' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="text-sm text-gray-600">Durum</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                @if ($not['is_completed'] ?? false)
                                    <span class="text-green-600">Tamamlandı</span>
                                @else
                                    <span class="text-yellow-600">Devam Ediyor</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <span class="text-sm text-gray-600">Oluşturulma</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ \Carbon\Carbon::parse($not['created_at'] ?? now())->format('d.m.Y H:i') }}
                            </div>
                        </div>

                        @if (isset($not['updated_at']))
                            <div>
                                <span class="text-sm text-gray-600">Son Güncelleme</span>
                                <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                    {{ \Carbon\Carbon::parse($not['updated_at'])->format('d.m.Y H:i') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Person Information -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Kişi Bilgileri</h3>
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user text-gray-600 text-lg"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $not['kisi_adi'] ?? 'Bilinmeyen' }}</div>
                            <div class="text-sm text-gray-500">ID: {{ $not['kisi_id'] ?? 'N/A' }}</div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <a href="#"
                            class="block w-full text-center px-4 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-user mr-2"></i>
                            Kişi Detayları
                        </a>
                        <a href="{{ route('admin.kisi-not.create', ['kisi_id' => $not['kisi_id']]) }}"
                            class="block w-full text-center px-4 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Yeni Not Ekle
                        </a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Hızlı İşlemler</h3>
                    <div class="space-y-3">
                        <button onclick="toggleComplete()"
                            class="w-full flex items-center justify-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-check mr-2"></i>
                            @if ($not['is_completed'] ?? false)
                                Tamamlandı İşaretini Kaldır
                            @else
                                Tamamlandı İşaretle
                            @endif
                        </button>

                        <button onclick="copyNoteContent()"
                            class="w-full flex items-center justify-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-copy mr-2"></i>
                            İçeriği Kopyala
                        </button>

                        <button onclick="shareNote()"
                            class="w-full flex items-center justify-center px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
                            <i class="fas fa-share mr-2"></i>
                            Paylaş
                        </button>

                        <button onclick="deleteNote()"
                            class="w-full flex items-center justify-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                            <i class="fas fa-trash mr-2"></i>
                            Notu Sil
                        </button>
                    </div>
                </div>

                <!-- Tags -->
                @if (isset($availableTags) && count($availableTags) > 0)
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Mevcut Etiketler</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($availableTags as $tag)
                                <button onclick="addTag('{{ $tag }}')"
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 hover:bg-blue-100 hover:text-blue-800 transition-colors dark:bg-slate-900 dark:text-slate-200">
                                    #{{ $tag }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleComplete() {
            const isCompleted = {{ $not['is_completed'] ?? false ? 'true' : 'false' }};
            const newStatus = !isCompleted;

            fetch(`{{ route('admin.kisi-not.index') }}/{{ $not['id'] }}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        is_completed: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Durum güncellenirken hata oluştu', 'error');
                });
        }

        function copyNoteContent() {
            const content = `{{ $not['baslik'] ?? '' }}\n\n{{ $not['icerik'] ?? '' }}`;

            navigator.clipboard.writeText(content).then(() => {
                showToast('Not içeriği panoya kopyalandı', 'success');
            }).catch(() => {
                showToast('Kopyalama başarısız', 'error');
            });
        }

        function shareNote() {
            if (navigator.share) {
                navigator.share({
                    title: '{{ $not['baslik'] ?? 'Not' }}',
                    text: '{{ Str::limit($not['icerik'] ?? '', 100) }}',
                    url: window.location.href
                });
            } else {
                copyNoteContent();
                showToast('Not bağlantısı panoya kopyalandı', 'info');
            }
        }

        function deleteNote() {
            if (confirm('Bu notu silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                fetch(`{{ route('admin.kisi-not.index') }}/{{ $not['id'] }}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => {
                                window.location.href = '{{ route('admin.kisi-not.index') }}';
                            }, 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Silme işlemi sırasında hata oluştu', 'error');
                    });
            }
        }

        function addTag(tag) {
            // Tag ekleme işlemi
            showToast(`"${tag}" etiketi eklendi`, 'info');
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
@endpush
