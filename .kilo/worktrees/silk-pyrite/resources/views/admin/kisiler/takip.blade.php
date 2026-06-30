@extends('admin.layouts.admin')

@section('title', 'Kişi Takip - Müşteri Yönetimi')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                        📊 Kişi Takip
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mt-2">
                        Müşteri aktivitelerini takip edin ve analiz edin
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.kisiler.index') }}" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>
                        Kişilere Dön
                    </a>
                </div>
            </div>
        </div>

        <!-- İstatistikler -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <x-context7.card variant="gradient">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Müşteri</p>
                        <p class="stat-card-value dark:text-slate-100">
                            {{ $istatistikler['toplam_kisi'] ?? 0 }}</p>
                    </div>
                </div>
            </x-context7.card>

            <x-context7.card variant="gradient">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Müşteri</p>
                        <p class="stat-card-value dark:text-slate-100">{{ $istatistikler['status_kisi'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </x-context7.card>

            <x-context7.card variant="gradient">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Yeni Müşteri (30 gün)</p>
                        <p class="stat-card-value dark:text-slate-100">{{ $istatistikler['yeni_kisi'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </x-context7.card>

            <x-context7.card variant="gradient">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ortalama Etiket</p>
                        <p class="stat-card-value dark:text-slate-100">
                            {{ number_format($istatistikler['ortalama_etiket'], 1) }}</p>
                    </div>
                </div>
            </x-context7.card>
        </div>

        <!-- Arama ve Filtreler -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-6 dark:shadow-none dark:border-slate-700">
            <div class="p-6">
                <form method="GET" action="{{ route('admin.kisiler.takip') }}" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <x-context7.input name="search" placeholder="Müşteri ara..." value="{{ request('search') }}" />
                    </div>
                    <div class="flex gap-2">
                        <x-context7.button type="submit" variant="primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Ara
                        </x-context7.button>
                        @if (request('search'))
                            <a href="{{ route('admin.kisiler.takip') }}" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Temizle
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Müşteri Listesi -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Müşteri Listesi</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Toplam {{ $kisiler->total() }} müşteri bulundu</p>
            </div>
            <div class="p-0">
                @if ($kisiler->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead class="admin-table-header">
                                <tr>
                                    <th class="admin-table-cell">Müşteri</th>
                                    <th class="admin-table-cell">İletişim</th>
                                    <th class="admin-table-cell">Etiketler</th>
                                    <th class="admin-table-cell">Durum</th>
                                    <th class="admin-table-cell">Kayıt Tarihi</th>
                                    <th class="admin-table-cell">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($kisiler as $kisi)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="admin-table-cell">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div
                                                        class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-white">
                                                            {{ strtoupper(substr($kisi->ad, 0, 1)) }}{{ strtoupper(substr($kisi->soyad, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                        {{ $kisi->ad }} {{ $kisi->soyad }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        ID: {{ $kisi->id }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="admin-table-cell">
                                            <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                                @if ($kisi->telefon)
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                                            </path>
                                                        </svg>
                                                        {{ $kisi->telefon }}
                                                    </div>
                                                @endif
                                                @if ($kisi->email)
                                                    <div class="flex items-center mt-1">
                                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                                            </path>
                                                        </svg>
                                                        {{ $kisi->email }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="admin-table-cell">
                                            <div class="flex flex-wrap gap-1">
                                                @forelse($kisi->etiketler as $etiket)
                                                    <x-context7.badge variant="primary" size="sm">
                                                        {{ $etiket->ad }}
                                                    </x-context7.badge>
                                                @empty
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Etiket
                                                        yok</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="admin-table-cell">
                                            @php
                                                $kisiStatus = $kisi->aktiflik_durumu ?? 'Belirtilmemiş';
                                            @endphp
                                            <x-neo.status-badge :status="$kisiStatus" />
                                        </td>
                                        <td class="admin-table-cell">
                                            <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                                {{ $kisi->created_at->format('d.m.Y') }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $kisi->created_at->format('H:i') }}
                                            </div>
                                        </td>
                                        <td class="admin-table-cell">
                                            <div class="flex items-center space-x-2">
                                                <a href="{{ route('admin.kisiler.show', $kisi->id) }}"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                    title="Görüntüle">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                        </path>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('admin.kisiler.edit', $kisi->id) }}"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                    title="Düzenle">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                        </path>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        {{ $kisiler->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Müşteri bulunamadı</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @if (request('search'))
                                "{{ request('search') }}" araması için sonuç bulunamadı.
                            @else
                                Henüz müşteri eklenmemiş.
                            @endif
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('admin.kisiler.create') }}" class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Yeni Müşteri Ekle
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
