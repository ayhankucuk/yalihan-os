@extends('admin.layouts.admin')

@section('page-title', 'CRM - Müşteri Yönetimi')

@section('content')
    <div class="ds-page-header">
        <div class="ds-page-header-content">
            <div class="ds-page-header-left">
                <h1 class="ds-page-title">👥 CRM - Müşteri Yönetimi</h1>
                <p class="ds-page-description">Müşterilerinizi yönetin, segmentasyon yapın ve CRM analizlerini görüntüleyin
                </p>
            </div>
            <div class="ds-page-header-right">
                <a href="{{ route('admin.crm.customers.create') }}" class="ds-btn ds-inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Yeni Müşteri
                </a>
            </div>
        </div>
    </div>

    <div class="ds-content-wrapper">
        <!-- İstatistikler -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="ds-stat-card">
                <div class="ds-stat-icon ds-stat-icon-primary">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                </div>
                <div class="ds-stat-content">
                    <h4 class="ds-stat-title">Toplam Müşteri</h4>
                    <p class="ds-stat-value">{{ $customers->total() }}</p>
                </div>
            </div>

            <div class="ds-stat-card">
                <div class="ds-stat-icon ds-stat-icon-success">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ds-stat-content">
                    <h4 class="ds-stat-title">Aktif Müşteri</h4>
                    <p class="ds-stat-value">{{ $customers->where('aktiflik_durumu', 'Aktif')->count() }}</p>
                </div>
            </div>

            <div class="ds-stat-card">
                <div class="ds-stat-icon ds-stat-icon-info">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div class="ds-stat-content">
                    <h4 class="ds-stat-title">Ortalama Segment</h4>
                    <p class="ds-stat-value">{{ $segments->count() > 0 ? $segments->first() : 'N/A' }}</p>
                </div>
            </div>

            <div class="ds-stat-card">
                <div class="ds-stat-icon ds-stat-icon-warning">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ds-stat-content">
                    <h4 class="ds-stat-title">Takip Bekleyen</h4>
                    <p class="ds-stat-value">{{ $customers->where('aktiflik_durumu', 'Potansiyel')->count() }}</p>
                </div>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="ds-card">
            <div class="ds-p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                <h3 class="ds-card-title">🔍 Filtreler ve Arama</h3>
            </div>
            <div class="ds-card-body">
                <form method="GET" action="{{ route('admin.crm.customers.index') }}" class="ds-form">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="ds-form-group">
                            <label for="search" class="ds-admin-label">Arama</label>
                            <input type="text" id="search" name="search" value="{{ request('search') }}"
                                class="ds-admin-input" placeholder="Ad, soyad, email, telefon...">
                        </div>

                        <div class="ds-form-group">
                            <label for="segment" class="ds-admin-label">Müşteri Segmenti</label>
                            <select style="color-scheme: light dark;" id="segment" name="segment" class="ds-admin-input transition-all duration-200">
                                <option value="">Tümü</option>
                                @foreach ($segments as $segment)
                                    <option value="{{ $segment }}"
                                        {{ request('segment') == $segment ? 'selected' : '' }}>
                                        {{ $segment }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="ds-form-group">
                            <label for="aktiflik_durumu" class="ds-admin-label">Durum</label>
                            <select style="color-scheme: light dark;" id="aktiflik_durumu" name="aktiflik_durumu" class="ds-admin-input transition-all duration-200">
                                <option value="">Tümü</option>
                                <option value="Aktif" {{ request('aktiflik_durumu') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="Pasif" {{ request('aktiflik_durumu') == 'Pasif' ? 'selected' : '' }}>Pasif</option>
                                <option value="Potansiyel" {{ request('aktiflik_durumu') == 'Potansiyel' ? 'selected' : '' }}>
                                    Potansiyel</option>
                            </select>
                        </div>

                        <div class="ds-form-group">
                            <label for="etiket" class="ds-admin-label">Etiket</label>
                            <select style="color-scheme: light dark;" id="etiket" name="etiket" class="ds-admin-input transition-all duration-200">
                                <option value="">Tümü</option>
                                @foreach ($etiketler as $etiket)
                                    <option value="{{ $etiket->id }}"
                                        {{ request('etiket') == $etiket->id ? 'selected' : '' }}>
                                        {{ $etiket->ad }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="ds-form-actions">
                        <button type="submit" class="ds-btn ds-inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Filtrele
                        </button>
                        <a href="{{ route('admin.crm.customers.index') }}" class="ds-btn ds-inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Temizle
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Müşteri Listesi -->
        <div class="ds-card">
            <div class="ds-p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                <h3 class="ds-card-title">📋 Müşteri Listesi</h3>
                <div class="ds-card-actions">
                    <span class="text-sm text-gray-500">{{ $customers->total() }} müşteri bulundu</span>
                </div>
            </div>
            <div class="ds-card-body">
                @if ($customers->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="ds-table min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 dark:bg-slate-900">
                                <tr>
                                    <th
                                        class="admin-table-th">
                                        Müşteri</th>
                                    <th
                                        class="admin-table-th">
                                        İletişim</th>
                                    <th
                                        class="admin-table-th">
                                        Segment</th>
                                    <th
                                        class="admin-table-th">
                                        Durum</th>
                                    <th
                                        class="admin-table-th">
                                        Danışman</th>
                                    <th
                                        class="admin-table-th">
                                        Son Aktivite</th>
                                    <th class="admin-table-th"
                                        width="150">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-slate-900">
                                @foreach ($customers as $customer)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div
                                                        class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                                        {{ strtoupper(substr($customer->ad, 0, 1) . substr($customer->soyad, 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $customer->tam_ad }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ $customer->musteri_tipi ?? 'Müşteri' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="space-y-1">
                                                @if ($customer->telefon)
                                                    <div class="flex items-center text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                        </svg>
                                                        {{ $customer->telefon }}
                                                    </div>
                                                @endif
                                                @if ($customer->email)
                                                    <div class="flex items-center text-sm text-gray-500">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                        </svg>
                                                        {{ $customer->email }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($customer->musteri_segmenti)
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if ($customer->musteri_segmenti == 'Platinum') bg-purple-100 text-purple-800 border border-purple-200
                                                @elseif($customer->musteri_segmenti == 'Gold') bg-yellow-100 text-yellow-800 border border-yellow-200
                                                @elseif($customer->musteri_segmenti == 'Silver') bg-gray-100 text-gray-800 border border-gray-200
                                                @else bg-orange-100 text-orange-800 border border-orange-200 @endif">
                                                    {{ $customer->musteri_segmenti }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{-- Context7: aktiflik_durumu field kullanımı --}}
                                            @php
                                                $customerStatus = $customer->aktiflik_durumu ?? 'Pasif';
                                            @endphp
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if ($customerStatus == 'Aktif') bg-green-100 text-green-800 border border-green-200 dark:bg-green-900/30 dark:text-green-300
                                            @elseif($customerStatus == 'Pasif') bg-red-100 text-red-800 border border-red-200 dark:bg-red-900/30 dark:text-red-300
                                            @else bg-yellow-100 text-yellow-800 border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 @endif">
                                                {{ $customerStatus }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                            @if ($customer->statusMusteriTakip && $customer->statusMusteriTakip->danisman)
                                                {{ $customer->statusMusteriTakip->danisman->ad }}
                                                {{ $customer->statusMusteriTakip->danisman->soyad }}
                                            @else
                                                <span class="text-gray-400">Atanmamış</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if ($customer->son_aktivite)
                                                <span title="{{ $customer->son_aktivite->format('d.m.Y H:i') }}">
                                                    {{ $customer->son_aktivite->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                <a href="{{ route('admin.crm.customers.show', $customer) }}"
                                                    class="text-blue-600 hover:text-blue-900" title="Görüntüle">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                                <a href="{{ route('admin.crm.customers.edit', $customer) }}"
                                                    class="text-yellow-600 hover:text-yellow-900" title="Düzenle">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Sayfalama -->
                    <div class="ds-pagination-wrapper mt-6">
                        {{ $customers->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">Henüz müşteri bulunmuyor</h3>
                        <p class="mt-1 text-sm text-gray-500">İlk müşterinizi ekleyerek başlayın.</p>
                        <div class="mt-6">
                            <a href="{{ route('admin.crm.customers.create') }}" class="ds-btn ds-inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
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
