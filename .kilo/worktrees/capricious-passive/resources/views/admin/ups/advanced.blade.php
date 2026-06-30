@extends('admin.layouts.admin')

@section('title', 'UPS Gelişmiş Ayarlar')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        {{-- Breadcrumb --}}
        @include('components.neo.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard.index')],
                ['label' => 'Sistem Yönetimi', 'url' => '#'],
                ['label' => 'UPS', 'url' => route('admin.ups.templates.index')],
                ['label' => 'Gelişmiş Ayarlar', 'url' => route('ups.advanced'), 'current' => true],
            ],
        ])

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3 dark:text-slate-100">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    UPS Gelişmiş Ayarlar
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Birleşik Mülk Sistemi (UPS) için global yapılandırma ve denetim araçları
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('ups.health') }}" class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 transition-all font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Sistem Sağlığı
                </a>
            </div>
        </div>

        {{-- Main Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            {{-- Card: Import/Export --}}
            <a href="{{ route('admin.ups.templates.import-export') }}" class="group bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-6 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 dark:shadow-none">
                <div class="w-14 h-14 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">İçe/Dışa Aktar</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                    Template yapılarını JSON formatında yedekleyin veya başka sistemlerden içe aktarın.
                </p>
                <div class="mt-4 flex items-center text-indigo-600 dark:text-indigo-400 font-semibold text-sm">
                    Görüntüle
                    <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="3" /></svg>
                </div>
            </a>

            {{-- Card: Versioning --}}
            <a href="{{ route('ups.versions.index') }}" class="group bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-6 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 dark:shadow-none">
                <div class="w-14 h-14 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Versiyon Kontrolü</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                    Sistemdeki tüm template değişikliklerini izleyin ve ihtiyacınız olduğunda geçmişe dönün.
                </p>
                <div class="mt-4 flex items-center text-emerald-600 dark:text-emerald-400 font-semibold text-sm">
                    Görüntüle
                    <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="3" /></svg>
                </div>
            </a>

            {{-- Card: Dependencies --}}
            <a href="{{ route('ups.features.dependencies') }}" class="group bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-6 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 dark:shadow-none">
                <div class="w-14 h-14 bg-amber-50 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Bağımlılıklar</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                    Özellikler arası koşullu gösterim ve veri bağımlılıklarını global olarak yönetin.
                </p>
                <div class="mt-4 flex items-center text-amber-600 dark:text-amber-400 font-semibold text-sm">
                    Görüntüle
                    <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="3" /></svg>
                </div>
            </a>

            {{-- Card: Audit Log --}}
            <a href="{{ route('ups.audit-log') }}" class="group bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-6 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 dark:shadow-none">
                <div class="w-14 h-14 bg-purple-50 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Denetim Günlüğü</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                    Hangi kullanıcı ne zaman hangi değişikliği yaptı? Tüm kritik kayıtları inceleyin.
                </p>
                <div class="mt-4 flex items-center text-purple-600 dark:text-purple-400 font-semibold text-sm">
                    Görüntüle
                    <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="3" /></svg>
                </div>
            </a>

        </div>

        {{-- Footer Info --}}
        <div class="mt-12 bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-8 border border-gray-100 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col md:flex-row items-center gap-6">
                <div class="flex-shrink-0 w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">UPS Advanced Engine Hakkında</h4>
                    <p class="text-gray-600 dark:text-gray-400 mt-1 max-w-3xl">
                        Bu sayfa UPS modülünün iç yapısını yönetmek içindir. Burada yapılan değişiklikler tüm ilan formlarını ve arama filtrelerini global olarak etkiler. İşlem yapmadan önce Versiyon Kontrolü üzerinden yedek durumlarını kontrol etmeniz önerilir.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
