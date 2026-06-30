@extends('admin.layouts.admin')

@section('title', 'Admin Dashboard - Yalıhan Emlak')

@section('content')
    <div
        class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 mb-8 p-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1
                        class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        👑 Admin Dashboard
                    </h1>
                    <p class="mt-3 text-lg text-gray-600 dark:text-gray-400">
                        Sistem genel statusu ve yönetim paneli
                    </p>
                </div>
                <div class="flex gap-4">
                    <button
                        class="inline-flex items-center px-4 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none"
                        onclick="generatePerformanceReport()">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002 2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        Performans Raporu
                    </button>
                    <button
                        class="inline-flex items-center px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-slate-800 dark:text-slate-300 dark:focus:ring-slate-600"
                        onclick="optimizeSystem()">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Sistemi Optimize Et
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="space-y-8">
            <!-- İstatistik Kartları -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Toplam İlanlar -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam İlanlar</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $quickStats['total_ilanlar'] ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Aktif İlanlar -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif İlanlar</p>
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                                {{ $quickStats['active_ilanlar'] ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Toplam Kullanıcılar -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Kullanıcılar</p>
                            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                {{ $quickStats['total_users'] ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Toplam Danışmanlar -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Danışmanlar</p>
                            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                                {{ $quickStats['total_danismanlar'] ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sistem Durumu -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-8 dark:shadow-none dark:border-slate-700">
                <h2 class="mb-6 flex items-center text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    <svg class="w-6 h-6 mr-3 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                    Sistem Durumu
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($quickStats['system_status'] ?? [] as $service => $serviceState)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg dark:bg-slate-900">
                            <span class="font-medium text-gray-700 dark:text-slate-200 capitalize dark:text-slate-300">{{ $service }}</span>
                            <span
                                class="flex items-center text-sm {{ $serviceState === 'online' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                <div
                                    class="w-2 h-2 rounded-full mr-2 {{ $serviceState === 'online' ? 'bg-green-500' : 'bg-red-500' }}">
                                </div>
                                {{ $serviceState === 'online' ? 'Online' : 'Offline' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Son İlanlar -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-8 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="flex items-center text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        <svg class="w-6 h-6 mr-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                        Son Eklenen İlanlar
                    </h2>
                    <a href="{{ route('admin.ilanlar.index') }}"
                        class="inline-flex items-center px-4 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Tümünü Gör
                    </a>
                </div>
                <div class="space-y-4">
                    @forelse($quickStats['recent_ilanlar'] ?? [] as $ilan)
                        <div
                            class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 dark:bg-slate-900">
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ $ilan->ilan_basligi }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($ilan->fiyat) }} TL
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $ilan->created_at->diffForHumans() }}</p>
                                <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm transition-colors duration-200">
                                    Görüntüle
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                </path>
                            </svg>
                            <p>Henüz ilan eklenmemiş</p>
                            @if (Route::has('admin.ilanlar.create'))
                                <a href="{{ route('admin.ilanlar.create') }}"
                                    class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-slate-800 dark:text-slate-300 dark:focus:ring-slate-600 text-xs">
                                    Yeni İlan Oluştur
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Hızlı İşlemler -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-8 dark:shadow-none dark:border-slate-700">
                <h2 class="mb-6 flex items-center text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    <svg class="w-6 h-6 mr-3 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z">
                        </path>
                    </svg>
                    Hızlı İşlemler
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @if (Route::has('admin.ilanlar.create'))
                        <a href="{{ route('admin.ilanlar.create') }}"
                            class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors duration-200">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 mr-3" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Yeni İlan</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">İlan ekle</p>
                            </div>
                        </a>
                    @endif

                    <a href="{{ route('admin.kullanicilar.index') }}"
                        class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors duration-200">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Kullanıcılar</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Kullanıcı yönetimi</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.danisman.index') }}"
                        class="flex items-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors duration-200">
                        <svg class="w-8 h-8 text-orange-600 dark:text-orange-400 mr-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Danışmanlar</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Danışman yönetimi</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.reports.index') }}"
                        class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors duration-200">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400 mr-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002 2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Raporlar</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Sistem raporları</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.ayarlar.index') }}"
                        class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 dark:bg-slate-900">
                        <svg class="w-8 h-8 text-gray-600 dark:text-gray-400 mr-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                        </svg>
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Ayarlar</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Sistem ayarları</p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-8 dark:shadow-none dark:border-slate-700">
                <h2 class="mb-6 flex items-center text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    <svg class="w-6 h-6 mr-3 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                    İlişki Yönetimi
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @if (Route::has('admin.features-management.categories.index'))
                        <a href="{{ route('admin.features-management.categories.index') }}"
                            class="flex items-center p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg hover:shadow-md transition-colors duration-200">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 mr-3" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z">
                                </path>
                            </svg>
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Özellik Kategorileri</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Liste ve düzenleme</p>
                            </div>
                        </a>
                    @endif
                    @if (Route::has('admin.property_types.show'))
                        <a href="{{ route('admin.property_types.show', 1) }}"
                            class="flex items-center p-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-lg hover:shadow-md transition-colors duration-200">
                            <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400 mr-3" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Property Type Manager</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Kategori 1</p>
                            </div>
                        </a>
                    @endif
                    @if (Route::has('admin.property_types.field_dependencies'))
                        <a href="{{ route('admin.property_types.field_dependencies', 1) }}"
                            class="flex items-center p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg hover:shadow-md transition-colors duration-200">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400 mr-3" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Alan İlişkileri</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Kategori 1</p>
                            </div>
                        </a>
                    @endif
                    @if (Route::has('admin.features-management.categories.show'))
                        <a href="{{ route('admin.features-management.categories.show', 5) }}"
                            class="flex items-center p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg hover:shadow-md transition-colors duration-200">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400 mr-3" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Kategori Detayı</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">ID 5</p>
                            </div>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function generatePerformanceReport() {
                // Performans raporu — Service entegrasyonu Phase 17'de yapılacak
            }

            function optimizeSystem() {
                // Sistem optimizasyonu — Service entegrasyonu Phase 17'de yapılacak
            }
        </script>
    @endpush
@endsection
