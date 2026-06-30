@extends('admin.layouts.admin')

@section('title', 'Takım Üyesi Düzenle')

@section('content')
    <div class="content-header mb-8">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-200 flex items-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        ✏️ Takım Üyesi Düzenle
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        {{ $takimUyesi->user->name }} kullanıcısının takım üyesi bilgilerini düzenleyin
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.takim-yonetimi.index') }}" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Geri Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow-lg overflow-hidden transition-all duration-200">
                    <div class="p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                        <h3 class="card-title">Takım Üyesi Bilgileri</h3>
                    </div>
                    <div class="p-6">
                        <form id="takimUyesiForm" method="POST"
                            action="{{ route('admin.takim-yonetimi.takim.update', $takimUyesi->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="user_name" class="admin-label">Kullanıcı</label>
                                        <input type="text" id="user_name" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                            value="{{ $takimUyesi->user->name }} ({{ $takimUyesi->user->email }})" readonly>
                                        <small class="text-gray-500">Kullanıcı bilgileri değiştirilemez</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="rol" class="admin-label">Rol <span
                                                class="text-red-500">*</span></label>
                                        <select style="color-scheme: light dark;" name="rol" id="rol" class="admin-input transition-all duration-200" required>
                                            <option value="">Rol Seçin</option>
                                            @foreach ($roller as $key => $value)
                                                <option value="{{ $key }}"
                                                    {{ $takimUyesi->rol == $key ? 'selected' : '' }}>{{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('rol')
                                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="aktiflik_durumu" class="admin-label">Aktiflik Durumu <span
                                                class="text-red-500">*</span></label>
                                        <select style="color-scheme: light dark;" name="aktiflik_durumu" id="aktiflik_durumu" class="admin-input transition-all duration-200" required>
                                            <option value="">Durum Seçin</option>
                                            @foreach ($statuslar as $key => $value)
                                                <option value="{{ $key }}"
                                                {{ ($takimUyesi->aktiflik_durumu ?? 'active') == $key ? 'selected' : '' }}>{{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('aktiflik_durumu')
                                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lokasyon" class="admin-label">Lokasyon</label>
                                        <input type="text" name="lokasyon" id="lokasyon" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                            value="{{ $takimUyesi->lokasyon }}" placeholder="Örn: İstanbul, Ankara">
                                        @error('lokasyon')
                                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="uzmanlik_alani" class="admin-label">Uzmanlık Alanı</label>
                                <textarea name="uzmanlik_alani" id="uzmanlik_alani" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100" rows="3"
                                    placeholder="Uzmanlık alanlarını yazın...">{{ $takimUyesi->uzmanlik_alani }}</textarea>
                                @error('uzmanlik_alani')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="calisma_saati" class="admin-label">Çalışma Saatleri</label>
                                <textarea name="calisma_saati" id="calisma_saati" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100" rows="3"
                                    placeholder="Çalışma saatlerini yazın...">{{ $takimUyesi->calisma_saati }}</textarea>
                                @error('calisma_saati')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="flex justify-end space-x-3 mt-6">
                                <a href="{{ route('admin.takim-yonetimi.index') }}"
                                    class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                                    İptal
                                </a>
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Güncelle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('takimUyesiForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML =
                '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Güncelleniyor...';
            submitBtn.disabled = true;

            fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.message);
                        setTimeout(() => {
                            window.location.href = '{{ route('admin.takim-yonetimi.index') }}';
                        }, 1500);
                    } else {
                        showNotification('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'Bir hata oluştu!');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });

        function showNotification(type, message) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
@endpush
