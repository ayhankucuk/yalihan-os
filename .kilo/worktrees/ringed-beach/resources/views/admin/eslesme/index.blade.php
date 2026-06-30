@extends('admin.layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center dark:border-slate-700">
                    <h1 class="admin-h1">Eşleşme Yönetimi</h1>
                    <div>
                        <button type="button" id="runMatchingBtn" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg touch-target-optimized dark:shadow-none">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            Eşleşme Algoritmasını Çalıştır
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200 rounded-lg">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-200 rounded-lg">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                            <div class="px-4 py-2.5 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <h4 class="admin-h3">Talep-İlan Eşleşmeleri</h4>
                            </div>
                            <div class="p-4">
                                <div class="mb-4">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Son Eşleşme
                                            Tarihi:</div>
                                        <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ now()->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Toplam Eşleşme
                                            Sayısı:</div>
                                        <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">0</div>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th scope="col"
                                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Talep</th>
                                                <th scope="col"
                                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    İlan</th>
                                                <th scope="col"
                                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Uyum %</th>
                                                <th scope="col"
                                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Tarih</th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-gray-50 dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                                            <tr>
                                                <td colspan="4" class="px-4 py-8 text-center">
                                                    <x-neo.empty-state title="Henüz eşleşme bulunmuyor" description="Yeni eşleşmeler oluştuğunda burada görünecek." />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                            <div class="px-4 py-2.5 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <h4 class="text-base font-medium text-gray-900 dark:text-white dark:text-slate-100">Eşleşme İstatistikleri</h4>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4">
                                        <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Talepler</h5>
                                        <div class="admin-h1">0</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Toplam Talep</div>
                                    </div>

                                    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4">
                                        <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">İlanlar</h5>
                                        <div class="admin-h1">0</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Toplam İlan</div>
                                    </div>

                                    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4">
                                        <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Eşleşme Oranı
                                        </h5>
                                        <div class="admin-h1">0%</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Talep Başına Eşleşme
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4">
                                        <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Başarılı
                                            Eşleşmeler</h5>
                                        <div class="admin-h1">0</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Satış/Kiralama ile
                                            Sonuçlanan</div>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Eşleşme
                                        Algoritması Performansı</h5>
                                    <div class="w-full bg-gray-200 dark:bg-slate-900 rounded-full h-2.5">
                                        <div class="bg-primary-600 h-2.5 rounded-full" style="width: 0%"></div>
                                    </div>
                                    <div class="flex justify-between mt-1">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">0%</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">100%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                        <div class="px-4 py-2.5 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white dark:text-slate-100">Eşleşme Ayarları</h4>
                        </div>
                        <div class="p-4">
                            <form action="{{ route('admin.eslesme.settings') }}" method="POST">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="match_threshold"
                                            class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Minimum
                                            Eşleşme Yüzdesi</label>
                                        <input type="number" name="match_threshold" id="match_threshold" min="0"
                                            max="100" value="70" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Bu değerin altındaki
                                            eşleşmeler gösterilmeyecektir.</p>
                                    </div>

                                    <div>
                                        <label for="auto_match"
                                            class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Otomatik
                                            Eşleştirme</label>
                                        <div class="mt-2">
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="auto_match" id="auto_match"
                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-slate-900 dark:focus:ring-primary-600 dark:focus:ring-opacity-25 dark:shadow-none">
                                                <span class="ml-2 text-sm text-gray-900 dark:text-white dark:text-slate-100">Yeni talep ve
                                                    ilanlar için otomatik eşleştirme yap</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="notification_status"
                                            class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Bildirimler</label>
                                        <div class="mt-2">
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="notification_status"
                                                    id="notification_status"
                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-slate-900 dark:focus:ring-primary-600 dark:focus:ring-opacity-25 dark:shadow-none"
                                                    checked>
                                                <span class="ml-2 text-sm text-gray-900 dark:text-white dark:text-slate-100">Eşleşme
                                                    statusunda danışmanlara bildirim gönder</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="match_frequency"
                                            class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Eşleştirme
                                            Sıklığı</label>
                                        <select style="color-scheme: light dark;" name="match_frequency" id="match_frequency" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                                            <option value="realtime">Gerçek Zamanlı</option>
                                            <option value="hourly">Saatlik</option>
                                            <option value="daily" selected>Günlük</option>
                                            <option value="weekly">Haftalık</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg touch-target-optimized dark:shadow-none">
                                        Ayarları Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const runMatchingBtn = document.getElementById('runMatchingBtn');

            if (runMatchingBtn) {
                runMatchingBtn.addEventListener('click', function() {
                    if (confirm('Eşleşme algoritmasını çalıştırmak istediğinizden emin misiniz?')) {
                        // AJAX isteği ile eşleşme algoritmasını çalıştır
                        fetch('{{ route("admin.eslesme.run") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({})
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                location.reload();
                            } else {
                                alert('Bir hata oluştu: ' + (data.message || 'Bilinmeyen hata'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                        });
                    }
                });
            }

            // Flash mesajları için timeout
            setTimeout(function() {
                const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
                alerts.forEach(function(alert) {
                    alert.classList.add('opacity-0');
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 3000);
        });
    </script>
@endpush
