@extends('admin.layouts.admin')

@section('title', 'Takım Üyesi Detayı')

@section('content')
    <!-- Modern Header -->
    <div class="content-header mb-8">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-200 flex items-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        👤 {{ $takimUyesi->user->name ?? 'Takım Üyesi' }}
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Takım üyesi detayları ve performans analizi
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="button" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none" onclick="uyeDuzenle()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Düzenle
                    </button>
                    <button type="button" class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 hover:scale-105 active:scale-95 transition-all duration-200 touch-target-optimized" onclick="uyeCikar()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path>
                        </svg>
                        Takımdan Çıkar
                    </button>
                    <a href="{{ route('admin.takim-yonetimi.takim.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>
                        Geri Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 shadow-sm p-6 dark:shadow-none">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-medium text-blue-800">Toplam Görev</h4>
                    <p class="text-2xl font-bold text-blue-900">{{ $takimUyesi->toplam_gorev }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200 shadow-sm p-6 dark:shadow-none">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-medium text-green-800">Başarılı Görev</h4>
                    <p class="text-2xl font-bold text-green-900">{{ $takimUyesi->basarili_gorev }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-50 to-violet-50 rounded-xl border border-purple-200 shadow-sm p-6 dark:shadow-none">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-violet-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-medium text-purple-800">Performans</h4>
                    <p class="text-2xl font-bold text-purple-900">{{ $takimUyesi->performans_skoru }}/100</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl border border-orange-200 shadow-sm p-6 dark:shadow-none">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-orange-500 to-amber-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-medium text-orange-800">Başarı Oranı</h4>
                    <p class="text-2xl font-bold text-orange-900">{{ $takimUyesi->basari_orani }}%</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sol Kolon - Üye Bilgileri -->
        <div class="lg:col-span-1">
            <!-- Üye Profil Kartı -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 shadow-sm p-6 mb-6 dark:shadow-none">
                <div class="text-center mb-6">
                    <div
                        class="w-20 h-20 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-blue-800 mb-1">{{ $takimUyesi->user->name ?? 'Bilinmeyen' }}</h3>
                    <p class="text-blue-600 mb-4">{{ $takimUyesi->user->email ?? 'Email yok' }}</p>

                    <div class="flex justify-center gap-2 mb-4">
                        {!! $takimUyesi->rol_etiketi !!}
                        {!! $takimUyesi->status_etiketi !!}
                    </div>

                    @if ($takimUyesi->lokasyon)
                        <div class="flex items-center justify-center text-blue-600 mb-4">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>{{ $takimUyesi->lokasyon }}</span>
                        </div>
                    @endif
                </div>

                <!-- Performans Özeti -->
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-blue-700">Başarı Oranı</span>
                            <span class="text-sm font-semibold text-blue-900">{{ $takimUyesi->basari_orani }}%</span>
                        </div>
                        <div class="w-full bg-blue-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-1000"
                                style="width: {{ $takimUyesi->basari_orani }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-purple-700">Performans Skoru</span>
                            <span
                                class="text-sm font-semibold text-purple-900">{{ $takimUyesi->performans_skoru }}/100</span>
                        </div>
                        <div class="w-full bg-purple-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-purple-500 to-violet-600 h-2 rounded-full transition-all duration-1000"
                                style="width: {{ $takimUyesi->performans_skoru }}%"></div>
                        </div>
                    </div>

                    @if ($takimUyesi->ortalama_tamamlanma_suresi)
                        <div class="text-center">
                            <span class="text-sm text-gray-600">Ortalama Süre:
                                {{ $takimUyesi->ortalama_sure_formatli }}</span>
                        </div>
                    @endif

                    <div class="text-center">
                        {!! $takimUyesi->performans_etiketi !!}
                    </div>
                </div>
            </div>

            <!-- Uzmanlık Alanları -->
            @if ($takimUyesi->uzmanlik_alani && count($takimUyesi->uzmanlik_alani) > 0)
                <div
                    class="bg-gradient-to-r from-yellow-50 to-amber-50 rounded-xl border border-yellow-200 shadow-sm p-6 mb-6 dark:shadow-none">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                            </path>
                        </svg>
                        🎯 Uzmanlık Alanları
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($takimUyesi->uzmanlik_alani as $uzmanlik)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                                {{ $uzmanlik }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Çalışma Saatleri -->
            @if ($takimUyesi->calisma_saati && count($takimUyesi->calisma_saati) > 0)
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200 shadow-sm p-6 dark:shadow-none">
                    <h3 class="text-lg font-semibold text-green-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        🕒 Çalışma Saatleri
                    </h3>
                    <div class="space-y-2">
                        @foreach ($takimUyesi->calisma_saati as $gun => $saat)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-green-700">{{ ucfirst($gun) }}</span>
                                <span class="text-gray-900 dark:text-white">{{ $saat }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sağ Kolon - Detaylar -->
        <div class="lg:col-span-2">
            <!-- Aktif Görevler -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 shadow-sm p-6 mb-6 dark:shadow-none">
                <h2 class="text-xl font-bold text-blue-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    📋 Aktif Görevler
                </h2>

                @if ($activeGorevler && count($activeGorevler) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-blue-200">
                            <thead class="bg-blue-100">
                                <tr>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Görev</th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Öncelik</th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Durum</th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Deadline</th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-blue-200 dark:bg-slate-900">
                                @foreach ($activeGorevler as $gorev)
                                    <tr class="hover:bg-blue-50">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div>
                                                <h6 class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $gorev->baslik }}</h6>
                                                <p class="text-sm text-gray-500">{{ Str::limit($gorev->aciklama, 50) }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">{!! $gorev->oncelik_etiketi !!}</td>
                                        <td class="px-4 py-4 whitespace-nowrap">{!! $gorev->status_etiketi !!}</td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($gorev->deadline)
                                                <span
                                                    class="{{ $gorev->gecikti_mi ? 'text-red-600' : ($gorev->deadline_yaklasiyor_mu ? 'text-yellow-600' : 'text-gray-500') }}">
                                                    {{ $gorev->deadline->format('d.m.Y H:i') }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">Belirtilmemiş</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <a href="{{ route('admin.takim.gorevler.show', $gorev->id) }}"
                                                class="text-blue-600 hover:text-blue-900">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                    </path>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-blue-900">Aktif görev bulunmuyor</h3>
                        <p class="mt-1 text-sm text-blue-500">Bu üyenin status görevi yok</p>
                    </div>
                @endif
            </div>

            <!-- Son Tamamlanan Görevler -->
            <div
                class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200 shadow-sm p-6 mb-6 dark:shadow-none">
                <h2 class="text-xl font-bold text-green-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    ✅ Son Tamamlanan Görevler
                </h2>

                @if ($tamamlananGorevler && count($tamamlananGorevler) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-green-200">
                            <thead class="bg-green-100">
                                <tr>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-green-700 uppercase tracking-wider">
                                        Görev</th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-green-700 uppercase tracking-wider">
                                        Tamamlanma</th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-green-700 uppercase tracking-wider">
                                        Süre</th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-green-700 uppercase tracking-wider">
                                        Performans</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-green-200 dark:bg-slate-900">
                                @foreach ($tamamlananGorevler as $gorev)
                                    <tr class="hover:bg-green-50">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div>
                                                <h6 class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $gorev->baslik }}</h6>
                                                <p class="text-sm text-gray-500">{{ Str::limit($gorev->aciklama, 50) }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $gorev->updated_at->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($gorev->statusTakip && $gorev->statusTakip->harcanan_sure)
                                                <span
                                                    class="text-sm text-gray-900 dark:text-slate-100 dark:text-white">{{ $gorev->statusTakip->harcanan_sure_saat }}</span>
                                            @else
                                                <span class="text-sm text-gray-400">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @php
                                                $performans = 100;
                                                if (
                                                    $gorev->deadline &&
                                                    $gorev->statusTakip &&
                                                    $gorev->statusTakip->harcanan_sure
                                                ) {
                                                    $tahmini = $gorev->tahmini_sure ?? 120;
                                                    $harcanan = $gorev->statusTakip->harcanan_sure;
                                                    $performans = max(
                                                        0,
                                                        min(100, (($tahmini - $harcanan) / $tahmini) * 100 + 50),
                                                    );
                                                }
                                            @endphp
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if ($performans >= 80) bg-green-100 text-green-800 border border-green-200
                                            @elseif($performans >= 60) bg-yellow-100 text-yellow-800 border border-yellow-200
                                            @else bg-red-100 text-red-800 border border-red-200 @endif">
                                                {{ round($performans) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-green-900">Henüz tamamlanan görev bulunmuyor</h3>
                        <p class="mt-1 text-sm text-green-500">Bu üyenin henüz tamamlanmış görevi yok</p>
                    </div>
                @endif
            </div>

            <!-- Görev Takip Geçmişi -->
            <div
                class="bg-gradient-to-r from-purple-50 to-violet-50 rounded-xl border border-purple-200 shadow-sm p-6 mb-6 dark:shadow-none">
                <h2 class="text-xl font-bold text-purple-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    📈 Görev Takip Geçmişi
                </h2>

                @if ($gorevTakipGecmisi && count($gorevTakipGecmisi) > 0)
                    <div class="space-y-4">
                        @foreach ($gorevTakipGecmisi as $takip)
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    {{-- Context7: Accessor üzerinden status erişimi --}}
                                    @php
                                        $takipDurumu = $takip->islem_statusu ?? 'bekliyor';
                                    @endphp
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center
                                    @if ($takipDurumu == 'tamamlandi') bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($takipDurumu == 'devam_ediyor') bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @else bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 @endif">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if ($takipDurumu == 'tamamlandi')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            @elseif($takipDurumu == 'devam_ediyor')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            @endif
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-purple-900">{{ $takip->gorev->baslik }}</h4>
                                        <span
                                            class="text-xs text-purple-500">{{ $takip->created_at->format('d.m.Y H:i') }}</span>
                                    </div>
                                    <p class="text-sm text-purple-700 mt-1">{{ $takip->notlar ?? 'Not eklenmemiş' }}</p>
                                    <div class="flex items-center mt-2">
                                        {!! $takip->status_etiketi !!}
                                        @if ($takip->harcanan_sure)
                                            <span class="ml-2 text-xs text-purple-600 bg-purple-100 px-2 py-1 rounded">
                                                {{ $takip->harcanan_sure_saat }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-purple-900">Henüz görev takip geçmişi bulunmuyor</h3>
                        <p class="mt-1 text-sm text-purple-500">Bu üyenin görev takip geçmişi yok</p>
                    </div>
                @endif
            </div>

            <!-- İstatistikler -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl border border-orange-200 shadow-sm p-6 dark:shadow-none">
                    <div class="text-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-orange-500 to-amber-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-bold text-orange-900">{{ $istatistikler['bu_ay_tamamlanan'] ?? 0 }}</h4>
                        <p class="text-orange-700">Bu Ay Tamamlanan</p>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl border border-indigo-200 shadow-sm p-6 dark:shadow-none">
                    <div class="text-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-bold text-indigo-900">{{ $istatistikler['toplam_calisma_saati'] ?? 0 }}h
                        </h4>
                        <p class="text-indigo-700">Toplam Çalışma Saati</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Üye Düzenleme Modal -->
    <div class="modal fade" id="uyeDuzenleModal" tabindex="-1" aria-labelledby="uyeDuzenleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient-to-r from-purple-500 to-pink-600 text-white">
                    <h5 class="modal-title" id="uyeDuzenleModalLabel">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Takım Üyesi Düzenle
                    </h5>
                    <button type="button" class="btn-close btn-close-white touch-target-optimized" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uyeDuzenleForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-field">
                                <label for="rol" class="admin-label required">Rol</label>
                                <select style="color-scheme: light dark;" id="rol" name="rol" class="admin-input transition-all duration-200" required>
                                    <option value="">Rol Seçin...</option>
                                    <option value="admin" {{ $takimUyesi->rol == 'admin' ? 'selected' : '' }}>Admin
                                    </option>
                                    <option value="danisman" {{ $takimUyesi->rol == 'danisman' ? 'selected' : '' }}>
                                        Danışman</option>
                                    <option value="alt_kullanici"
                                        {{ $takimUyesi->rol == 'alt_kullanici' ? 'selected' : '' }}>Alt Kullanıcı</option>
                                    <option value="musteri_temsilcisi"
                                        {{ $takimUyesi->rol == 'musteri_temsilcisi' ? 'selected' : '' }}>Müşteri Temsilcisi
                                    </option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="aktiflik_durumu" class="admin-label required">Aktiflik Durumu</label>
                                <select style="color-scheme: light dark;" id="aktiflik_durumu" name="aktiflik_durumu" class="admin-input transition-all duration-200" required>
                                    <option value="">Durum Seçin...</option>
                                    <option value="active" {{ ($takimUyesi->aktiflik_durumu ?? 'active') == 'active' ? 'selected' : '' }}>Aktif
                                    </option>
                                    <option value="pasif" {{ ($takimUyesi->aktiflik_durumu ?? 'active') == 'pasif' ? 'selected' : '' }}>Pasif
                                    </option>
                                    <option value="izinli" {{ ($takimUyesi->aktiflik_durumu ?? 'active') == 'izinli' ? 'selected' : '' }}>İzinli
                                    </option>
                                    <option value="tatilde" {{ ($takimUyesi->aktiflik_durumu ?? 'active') == 'tatilde' ? 'selected' : '' }}>
                                        Tatilde</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="lokasyon" class="admin-label">Lokasyon</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" id="lokasyon" name="lokasyon"
                                    value="{{ $takimUyesi->lokasyon }}" class="admin-input pl-10"
                                    placeholder="Şehir, bölge bilgisi">
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="uzmanlik_alani" class="admin-label">Uzmanlık Alanları</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                        </path>
                                    </svg>
                                </div>
                                <input type="text" id="uzmanlik_alani" name="uzmanlik_alani"
                                    value="{{ $takimUyesi->uzmanlik_alani ? implode(', ', $takimUyesi->uzmanlik_alani) : '' }}"
                                    class="admin-input pl-10"
                                    placeholder="Emlak türleri, lokasyonlar, özellikler (virgülle ayırın)">
                            </div>
                            <p class="form-hint">Virgülle ayırarak birden fazla uzmanlık alanı ekleyebilirsiniz</p>
                        </div>

                        <div class="form-field">
                            <label class="admin-label">Çalışma Saatleri</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach (['pazartesi', 'sali', 'carsamba', 'persembe', 'cuma', 'cumartesi', 'pazar'] as $gun)
                                    <div class="flex items-center space-x-3">
                                        <label class="w-20 text-sm font-medium text-gray-700 dark:text-slate-300">{{ ucfirst($gun) }}</label>
                                        <input type="text" name="calisma_saati[{{ $gun }}]"
                                            value="{{ $takimUyesi->calisma_saati[$gun] ?? '' }}"
                                            class="admin-input flex-1" placeholder="09:00-18:00">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-gray-50 dark:bg-slate-900">
                    <button type="button" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none" data-bs-dismiss="modal">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        İptal
                    </button>
                    <button type="button" class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none" onclick="uyeDuzenleGonder()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Güncelle
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')

@endpush

@push('scripts')
    <script>
        // Context7: Takım üyesi detay sayfası için gelişmiş işlevsellik
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Context7: Takım üyesi detay sayfası yüklendi');

            // Progress bar animasyonları
            animateProgressBars();

            // Hover efektleri
            addHoverEffects();

            // Modal geliştirmeleri
            enhanceModal();
        });

        function animateProgressBars() {
            const progressBars = document.querySelectorAll('.bg-gradient-to-r');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        }

        function addHoverEffects() {
            const cards = document.querySelectorAll('.hover-lift');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                    this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });
        }

        // ✅ CONTEXT7: Vanilla JS - Bootstrap modal event'leri kaldırıldı
        function enhanceModal() {
            const modal = document.getElementById('uyeDuzenleModal');
            if (modal) {
                // Focus yönetimi - Modal açıldığında
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            const isVisible = !modal.classList.contains('hidden');
                            if (isVisible) {
                                const firstInput = modal.querySelector('input, select');
                                if (firstInput) {
                                    firstInput.focus();
                                }
                            } else {
                                // Form reset
                                const form = document.getElementById('uyeDuzenleForm');
                                if (form) {
                                    form.reset();
                                }
                            }
                        }
                    });
                });

                observer.observe(modal, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
        }

        // ✅ CONTEXT7: Vanilla JS - jQuery kaldırıldı
        // Üye düzenleme modal'ını aç
        function uyeDuzenle() {
            console.log('Context7: Üye düzenleme modal açılıyor');
            const modal = document.getElementById('uyeDuzenleModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                // Focus yönetimi
                const firstInput = modal.querySelector('input, select');
                if (firstInput) {
                    firstInput.focus();
                }
            }
        }

        // ✅ CONTEXT7: Vanilla JS - jQuery kaldırıldı
        // Üye düzenleme formunu gönder
        function uyeDuzenleGonder() {
            const form = document.getElementById('uyeDuzenleForm');
            if (!form) {
                console.error('Form bulunamadı');
                return;
            }

            const formData = new FormData(form);

            // Uzmanlık alanlarını array'e çevir
            const uzmanlikAlani = formData.get('uzmanlik_alani');
            if (uzmanlikAlani) {
                formData.set('uzmanlik_alani', uzmanlikAlani.split(',').map(item => item.trim()).filter(item => item));
            }

            // Loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (!submitBtn) {
                console.error('Submit button bulunamadı');
                return;
            }

            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = `
                <svg class="w-4 h-4 animate-spin mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Güncelleniyor...
            `;
            submitBtn.disabled = true;

            // ✅ CONTEXT7: Vanilla JS Fetch API
            fetch(`/admin/takim-yonetimi/takim/{{ $takimUyesi->id }}/guncelle`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Context7: Üye güncelleme başarılı', data);
                if (data.success) {
                    window.showToast?.(data.message, 'success') || alert(data.message);
                    // Modal'ı kapat
                    const modal = document.getElementById('uyeDuzenleModal');
                    if (modal) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    window.showToast?.(data.message, 'error') || alert(data.message);
                }
            })
            .catch(error => {
                console.error('Context7: Üye güncelleme hatası', error);
                window.showToast?.('Üye güncellenirken hata oluştu', 'error') || alert('Hata oluştu');
            })
            .finally(() => {
                // Reset loading state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        // ✅ CONTEXT7: Vanilla JS - jQuery kaldırıldı
        // Üyeyi takımdan çıkar
        function uyeCikar() {
            console.log('Context7: Üye çıkarma işlemi başlatıldı');

            if (confirm('Bu üyeyi takımdan çıkarmak istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                // Loading state
                const cikarBtn = event?.target?.closest('button') || document.querySelector('button[onclick*="uyeCikar"]');
                if (!cikarBtn) {
                    console.error('Çıkar butonu bulunamadı');
                    return;
                }

                const originalText = cikarBtn.innerHTML;
                cikarBtn.innerHTML = `
                    <svg class="w-4 h-4 animate-spin mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Çıkarılıyor...
                `;
                cikarBtn.disabled = true;

                // ✅ CONTEXT7: Vanilla JS Fetch API
                fetch('/admin/takim-yonetimi/takim/uye-cikar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        takim_uye_id: {{ $takimUyesi->id }}
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Context7: Üye çıkarma başarılı', data);
                    if (data.success) {
                        window.showToast?.(data.message, 'success') || alert(data.message);
                        setTimeout(() => {
                            window.location.href = '{{ route('admin.takim-yonetimi.takim.index') }}';
                        }, 1000);
                    } else {
                        window.showToast?.(data.message, 'error') || alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Context7: Üye çıkarma hatası', error);
                    window.showToast?.('Üye çıkarılırken hata oluştu', 'error') || alert('Hata oluştu');
                })
                .finally(() => {
                    // Reset loading state
                    cikarBtn.innerHTML = originalText;
                    cikarBtn.disabled = false;
                });
            }
        }

        // Context7: Sayfa performans izleme
        window.addEventListener('load', function() {
            console.log('Context7: Sayfa tamamen yüklendi');
            // Sayfa yüklenme süresi izleme
            if (window.performance) {
                const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
                console.log('Context7: Sayfa yüklenme süresi:', loadTime + 'ms');
            }
        });
    </script>
@endpush
