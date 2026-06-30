@extends('admin.layouts.admin')

@section('title', 'AI Önerileri - CRM Yönetimi')

@section('content')
    <div x-data="crmAI()" class="space-y-6">
        <!-- Context7 AI Önerileri Banner -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 mb-6 p-6 bg-gradient-to-r from-indigo-50 to-purple-50 border-indigo-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-indigo-800">🤖 AI CRM Analizi</h3>
                        <p class="text-sm text-indigo-600">Context7 Intelligence ile akıllı CRM yönetimi ve öneriler</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">✓ Context7 Uyumlu</span>
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-800 text-xs font-medium rounded-full">AI Aktif</span>
                </div>
            </div>
        </div>

        <!-- İstatistik Kartları -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-blue-800">{{ $stats['toplam_kisi'] ?? 0 }}</h3>
                        <p class="text-sm text-blue-600 font-medium">Toplam Kişi</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 bg-gradient-to-r from-green-50 to-emerald-50 border-green-200 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293L18.707 8.707A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-green-800">{{ $stats['toplam_talep'] ?? 0 }}</h3>
                        <p class="text-sm text-green-600 font-medium">Toplam Talep</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 bg-gradient-to-r from-purple-50 to-violet-50 border-purple-200 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-violet-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-purple-800">{{ $stats['toplam_eslesme'] ?? 0 }}</h3>
                        <p class="text-sm text-purple-600 font-medium">Toplam Eşleşme</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 bg-gradient-to-r from-orange-50 to-red-50 border-orange-200 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-orange-800">{{ $aiOnerileri['eksik_bilgiler'] ?? 0 }}</h3>
                        <p class="text-sm text-orange-600 font-medium">Eksik Bilgi</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Önerileri -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Mükerrer E-postalar -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                            <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            Mükerrer E-postalar
                        </h3>
                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">
                            {{ $aiOnerileri['mukerrer_kisiler']->count() }} adet
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    @if($aiOnerileri['mukerrer_kisiler']->count() > 0)
                        <div class="space-y-3">
                            @foreach($aiOnerileri['mukerrer_kisiler'] as $mukerrer)
                                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-red-800">{{ $mukerrer['email'] }}</p>
                                            <p class="text-sm text-red-600">{{ $mukerrer['sayi'] }} adet kayıt</p>
                                        </div>
                                        <button @click="fixDuplicate('{{ $mukerrer['email'] }}')"
                                                class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                                            Düzelt
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">Mükerrer kayıt bulunamadı</h3>
                            <p class="mt-1 text-sm text-gray-500">Tüm e-posta adresleri benzersiz</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Eksik Bilgiler -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                            <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Eksik Bilgiler
                        </h3>
                        <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs font-medium rounded-full">
                            {{ $aiOnerileri['eksik_bilgiler'] }} adet
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div class="p-3 bg-orange-50 border border-orange-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-orange-800">Eksik Bilgi Kontrolü</p>
                                    <p class="text-sm text-orange-600">E-posta, telefon veya ad bilgisi eksik kayıtlar</p>
                                </div>
                                <a href="{{ route('admin.kisiler.index') }}"
                                   class="px-3 py-1 bg-orange-600 text-white text-xs rounded hover:bg-orange-700">
                                    Kontrol Et
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Eşleşmeyen Talepler -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                            <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            Eşleşmeyen Talepler
                        </h3>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                            {{ $aiOnerileri['eslesmeyen_talepler'] }} adet
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-yellow-800">Eşleştirme Gerekli</p>
                                    <p class="text-sm text-yellow-600">Henüz eşleştirilmemiş talep kayıtları</p>
                                </div>
                                <a href="{{ route('admin.talepler.index') }}"
                                   class="px-3 py-1 bg-yellow-600 text-white text-xs rounded hover:bg-yellow-700">
                                    İncele
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yüksek Skorlu Eşleşmeler -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Yüksek Skorlu Eşleşmeler
                        </h3>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                            {{ $aiOnerileri['yuksek_skorlu_eslesmeler'] }} adet
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-green-800">Kaliteli Eşleşmeler</p>
                                    <p class="text-sm text-green-600">8+ skor ile eşleştirilmiş kayıtlar</p>
                                </div>
                                <a href="{{ route('admin.eslesmeler.index') }}"
                                   class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                    Görüntüle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Analiz Butonları -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">AI Analiz Araçları</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button @click="runAnalysis('duplicates')"
                        class="p-4 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors text-left">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <span class="font-medium text-red-800">Mükerrer Analizi</span>
                    </div>
                    <p class="text-sm text-red-600">E-posta mükerrerliklerini tespit et</p>
                </button>

                <button @click="runAnalysis('missing')"
                        class="p-4 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition-colors text-left">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium text-orange-800">Eksik Bilgi Analizi</span>
                    </div>
                    <p class="text-sm text-orange-600">Eksik bilgileri tespit et</p>
                </button>

                <button @click="runAnalysis('matching')"
                        class="p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors text-left">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        <span class="font-medium text-blue-800">Eşleştirme Analizi</span>
                    </div>
                    <p class="text-sm text-blue-600">Eşleştirme performansını analiz et</p>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function crmAI() {
    return {
        loading: false,

        async runAnalysis(type) {
            this.loading = true;
            try {
                const response = await fetch(`/admin/crm/ai-analyze?type=${type}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification(`${type} analizi tamamlandı`, 'success');
                    console.log('Analysis result:', data.analysis);
                } else {
                    this.showNotification('Analiz sırasında hata oluştu', 'error');
                }
            } catch (error) {
                console.error('Analysis error:', error);
                this.showNotification('Analiz sırasında hata oluştu', 'error');
            } finally {
                this.loading = false;
            }
        },

        async fixDuplicate(email) {
            if (!confirm(`${email} için mükerrer kayıtları temizlemek istediğinizden emin misiniz?`)) {
                return;
            }

            try {
                const response = await fetch('/admin/crm/fix-duplicates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        email: email,
                        keep_id: 1 // İlk kaydı tut
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showNotification(data.message || 'Hata oluştu', 'error');
                }
            } catch (error) {
                console.error('Fix duplicate error:', error);
                this.showNotification('Hata oluştu', 'error');
            }
        },

        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                type === 'warning' ? 'bg-yellow-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    }
}
</script>
@endpush
