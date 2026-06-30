@extends('admin.layouts.admin')

@section('title', 'CRM Radar Ekranı - Kişiler')

@section('content')
    {{-- 🎯 CRM RADAR EKRANI - Modern Tailwind + Dark Mode + Telemetri Işıkları --}}
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-950 p-6 transition-all duration-300">
        
        {{-- 📡 RADAR HEADER --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-black bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-400 dark:via-indigo-400 dark:to-purple-400 bg-clip-text text-transparent mb-2 transition-all">
                        🎯 CRM Radar Ekranı
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 text-lg">
                        Kişi portföyünüzü gerçek zamanlı izleyin ve yönetin
                    </p>
                </div>
                
                {{-- Action Buttons --}}
                <div class="flex gap-3">
                    <a href="{{ route('admin.kisiler.create') }}"
                        class="group relative px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-500 dark:to-indigo-500 text-white dark:text-slate-50 rounded-xl hover:shadow-2xl dark:hover:shadow-blue-500/20 hover:scale-105 dark:hover:scale-105 active:scale-95 dark:active:scale-95 transition-all duration-300 font-bold overflow-hidden">
                        <span class="absolute inset-0 bg-white/20 dark:bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
                        <span class="relative flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Yeni Kişi Ekle
                        </span>
                    </a>
                </div>
            </div>
        </div>

        {{-- 📊 TELEMETRI KARTLARI (Aktif, Potansiyel, Mülk Sahibi) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Toplam Kişi --}}
            <div class="group relative bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-800 hover:shadow-2xl dark:hover:shadow-blue-900/20 transition-all duration-300 hover:scale-105 dark:hover:scale-105 overflow-hidden dark:border-slate-700">
                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-3xl dark:blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-1 dark:text-slate-100">{{ $stats['total'] ?? 0 }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Toplam Kişi</p>
                </div>
            </div>

            {{-- Aktif Kişiler (Yeşil Işık) --}}
            <div class="group relative bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-800 hover:shadow-2xl dark:hover:shadow-green-900/20 transition-all duration-300 hover:scale-105 dark:hover:scale-105 overflow-hidden dark:border-slate-700">
                <div class="absolute top-0 right-0 w-32 h-32 bg-green-500/10 dark:bg-green-500/5 rounded-full blur-3xl dark:blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl relative">
                            {{-- Telemetri Işığı --}}
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 dark:bg-green-400 rounded-full animate-pulse shadow-lg dark:shadow-green-500/50"></div>
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-1 dark:text-slate-100">{{ $stats['active'] ?? 0 }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Aktif Kişiler</p>
                </div>
            </div>

            {{-- Potansiyel Müşteriler (Sarı Işık) --}}
            <div class="group relative bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-800 hover:shadow-2xl dark:hover:shadow-yellow-900/20 transition-all duration-300 hover:scale-105 dark:hover:scale-105 overflow-hidden dark:border-slate-700">
                <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-500/10 dark:bg-yellow-500/5 rounded-full blur-3xl dark:blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl relative">
                            {{-- Telemetri Işığı --}}
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-yellow-500 dark:bg-yellow-400 rounded-full animate-pulse shadow-lg dark:shadow-yellow-500/50"></div>
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-1 dark:text-slate-100">{{ $stats['potential'] ?? 0 }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Potansiyel Müşteri</p>
                </div>
            </div>

            {{-- Mülk Sahipleri (Mor Işık) --}}
            <div class="group relative bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-800 hover:shadow-2xl dark:hover:shadow-purple-900/20 transition-all duration-300 hover:scale-105 dark:hover:scale-105 overflow-hidden dark:border-slate-700">
                <div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 dark:bg-purple-500/5 rounded-full blur-3xl dark:blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl relative">
                            {{-- Telemetri Işığı --}}
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-purple-500 dark:bg-purple-400 rounded-full animate-pulse shadow-lg dark:shadow-purple-500/50"></div>
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-1 dark:text-slate-100">{{ $stats['property_owners'] ?? 0 }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Mülk Sahibi</p>
                </div>
            </div>
        </div>

        {{-- 🔍 ARAMA VE FİLTRELEME --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 mb-8 shadow-lg dark:shadow-none dark:border-slate-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Arama --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Ara</label>
                    <input type="text" 
                        placeholder="İsim, telefon, email..."
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 dark:text-slate-100">
                </div>

                {{-- Durum Filtresi --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Durum</label>
                    <select class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 dark:text-slate-100">
                        <option value="">Tümü</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Pasif">Pasif</option>
                        <option value="Potansiyel">Potansiyel</option>
                    </select>
                </div>

                {{-- Kişi Tipi --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Kişi Tipi</label>
                    <select class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 dark:text-slate-100">
                        <option value="">Tümü</option>
                        <option value="alici">Alıcı</option>
                        <option value="satici">Satıcı</option>
                        <option value="kiralayan">Kiralayan</option>
                        <option value="kiraci">Kiracı</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- 📋 KİŞİ LİSTESİ (Modern Tablo) --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 shadow-lg dark:shadow-none overflow-hidden dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Kişi</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">İletişim</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Durum</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Tip</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Danışman</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($kisiler as $kisi)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-400 dark:to-indigo-500 flex items-center justify-center text-white dark:text-slate-50 font-bold">
                                                {{ strtoupper(substr($kisi->ad, 0, 1)) }}{{ strtoupper(substr($kisi->soyad ?? '', 0, 1)) }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $kisi->tam_ad }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">ID: {{ $kisi->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $kisi->telefon }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $kisi->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($kisi->aktiflik_durumu === 'Aktif')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                            <span class="w-2 h-2 bg-green-500 dark:bg-green-400 rounded-full mr-2 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @elseif($kisi->aktiflik_durumu === 'Potansiyel')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400">
                                            <span class="w-2 h-2 bg-yellow-500 dark:bg-yellow-400 rounded-full mr-2 animate-pulse"></span>
                                            Potansiyel
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400 dark:bg-slate-900 dark:text-slate-200">
                                            Pasif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $kisi->kisi_tipi ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $kisi->danisman->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.kisiler.show', $kisi) }}" 
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3 transition-colors">
                                        Görüntüle
                                    </a>
                                    <a href="{{ route('admin.kisiler.edit', $kisi) }}" 
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">
                                        Düzenle
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-gray-500 dark:text-gray-400">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        <p class="mt-4 text-lg font-medium">Henüz kişi kaydı bulunmuyor</p>
                                        <p class="mt-2">Yeni kişi ekleyerek başlayın</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($kisiler->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    {{ $kisiler->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
