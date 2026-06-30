@extends('admin.layouts.admin')

@section('page-title', 'CRM - Müşteri Yönetimi')

@section('content')
    {{-- Sayfa Başlığı --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                <x-icon name="kullanicilar" class="w-7 h-7 text-orange-500" />
                CRM — Müşteri Yönetimi
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Müşterilerinizi yönetin, segmentasyon yapın ve CRM analizlerini görüntüleyin
            </p>
        </div>
        <div class="flex-shrink-0">
            <a href="{{ route('admin.crm.customers.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white font-semibold text-sm rounded-xl shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:outline-none transition-all duration-200 dark:shadow-none dark:focus:ring-offset-slate-900">
                <x-icon name="ekle" class="w-4 h-4" />
                Yeni Müşteri
            </a>
        </div>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Toplam Müşteri --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <x-icon name="kullanicilar" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <span class="text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded-full">Toplam</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $customers->total() }}</p>
            <h4 class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Toplam Müşteri</h4>
        </div>

        {{-- Aktif Müşteri --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <x-icon name="onay-daire" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-1 rounded-full">Aktif</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $customers->where('aktiflik_durumu', 'Aktif')->count() }}</p>
            <h4 class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Aktif Müşteri</h4>
        </div>

        {{-- Ortalama Segment --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <x-icon name="grafik" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 px-2 py-1 rounded-full">Segment</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $segments->count() > 0 ? $segments->first() : 'N/A' }}</p>
            <h4 class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Önde Gelen Segment</h4>
        </div>

        {{-- Takip Bekleyen --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <x-icon name="saat" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <span class="text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-2 py-1 rounded-full">Bekleyen</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $customers->where('aktiflik_durumu', 'Potansiyel')->count() }}</p>
            <h4 class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Takip Bekleyen</h4>
        </div>
    </div>

    {{-- Filtreler --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-700 flex items-center gap-2">
            <x-icon name="filtrele" class="w-4 h-4 text-gray-400 dark:text-slate-500" />
            <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Filtreler ve Arama</h3>
        </div>
        <div class="p-6">
            <form method="GET" action="{{ route('admin.crm.customers.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div>
                        <label for="search" class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Arama</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                               class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg text-gray-900 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200"
                               placeholder="Ad, soyad, telefon...">
                    </div>

                    <div>
                        <label for="segment" class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Müşteri Segmenti</label>
                        <select style="color-scheme: light dark;" id="segment" name="segment"
                                class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200">
                            <option value="">Tümü</option>
                            @foreach ($segments as $segment)
                                <option value="{{ $segment }}" {{ request('segment') == $segment ? 'selected' : '' }}>
                                    {{ $segment }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="aktiflik_durumu" class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Durum</label>
                        <select style="color-scheme: light dark;" id="aktiflik_durumu" name="aktiflik_durumu"
                                class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200">
                            <option value="">Tümü</option>
                            <option value="Aktif"      {{ request('aktiflik_durumu') == 'Aktif'      ? 'selected' : '' }}>Aktif</option>
                            <option value="Pasif"      {{ request('aktiflik_durumu') == 'Pasif'      ? 'selected' : '' }}>Pasif</option>
                            <option value="Potansiyel" {{ request('aktiflik_durumu') == 'Potansiyel' ? 'selected' : '' }}>Potansiyel</option>
                        </select>
                    </div>

                    <div>
                        <label for="etiket" class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Etiket</label>
                        <select style="color-scheme: light dark;" id="etiket" name="etiket"
                                class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200">
                            <option value="">Tümü</option>
                            @foreach ($etiketler as $etiket)
                                <option value="{{ $etiket->id }}" {{ request('etiket') == $etiket->id ? 'selected' : '' }}>
                                    {{ $etiket->ad }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-5 pt-5 border-t border-gray-100 dark:border-slate-700">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white font-semibold text-sm rounded-xl shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:outline-none transition-all duration-200 dark:shadow-none dark:focus:ring-offset-slate-900">
                        <x-icon name="arama" class="w-4 h-4" />
                        Filtrele
                    </button>
                    <a href="{{ route('admin.crm.customers.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-300 font-semibold text-sm rounded-xl border border-gray-300 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:outline-none transition-all duration-200 shadow-sm dark:shadow-none">
                        <x-icon name="kapat" class="w-4 h-4" />
                        Temizle
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Müşteri Listesi --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-icon name="liste" class="w-4 h-4 text-gray-400 dark:text-slate-500" />
                <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Müşteri Listesi</h3>
            </div>
            <span class="text-xs text-gray-400 dark:text-slate-500 bg-gray-100 dark:bg-slate-700 px-2.5 py-1 rounded-full font-medium">
                {{ $customers->total() }} müşteri
            </span>
        </div>
        <div class="p-0">
            @if ($customers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-700">
                        <thead>
                            <tr class="bg-gray-50/80 dark:bg-slate-900/50">
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Müşteri</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">İletişim</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Segment</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Durum</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Danışman</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Son Aktivite</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider w-28">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach ($customers as $customer)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-slate-700/40 transition-colors duration-150">
                                    {{-- Müşteri --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0 shadow-sm">
                                                {{ strtoupper(substr($customer->ad ?? '', 0, 1) . substr($customer->soyad ?? '', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $customer->tam_ad }}</p>
                                                <p class="text-xs text-gray-400 dark:text-slate-500">{{ $customer->musteri_tipi ?? 'Müşteri' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- İletişim --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="space-y-1">
                                            @if ($customer->telefon)
                                                <div class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-slate-300">
                                                    <x-icon name="telefon" class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500 flex-shrink-0" />
                                                    {{ $customer->telefon }}
                                                </div>
                                            @endif
                                            @if ($customer->eposta ?? $customer->email ?? null)
                                                <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-slate-400">
                                                    <x-icon name="eposta" class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500 flex-shrink-0" />
                                                    {{ $customer->eposta ?? $customer->email }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Segment --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($customer->musteri_segmenti)
                                            @php
                                                $segColors = [
                                                    'Platinum' => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800/40',
                                                    'Gold'     => 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-800/40',
                                                    'Silver'   => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600',
                                                ];
                                                $segClass = $segColors[$customer->musteri_segmenti] ?? 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/30 dark:text-orange-300 dark:border-orange-800/40';
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $segClass }}">
                                                {{ $customer->musteri_segmenti }}
                                            </span>
                                        @else
                                            <span class="text-gray-300 dark:text-slate-600">—</span>
                                        @endif
                                    </td>

                                    {{-- Durum --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $customerStatus = $customer->aktiflik_durumu ?? 'Pasif';
                                            $statusColors = [
                                                'Aktif'      => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800/40',
                                                'Pasif'      => 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800/40',
                                                'Potansiyel' => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800/40',
                                            ];
                                            $statusClass = $statusColors[$customerStatus] ?? 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-slate-700 dark:text-slate-400';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusClass }}">
                                            {{ $customerStatus }}
                                        </span>
                                    </td>

                                    {{-- Danışman --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                        @if ($customer->statusMusteriTakip && $customer->statusMusteriTakip->danisman)
                                            {{ $customer->statusMusteriTakip->danisman->ad }}
                                            {{ $customer->statusMusteriTakip->danisman->soyad }}
                                        @else
                                            <span class="text-gray-300 dark:text-slate-600">Atanmamış</span>
                                        @endif
                                    </td>

                                    {{-- Son Aktivite --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-slate-400">
                                        @if ($customer->son_aktivite)
                                            <span title="{{ $customer->son_aktivite->format('d.m.Y H:i') }}">
                                                {{ $customer->son_aktivite->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-gray-300 dark:text-slate-600">—</span>
                                        @endif
                                    </td>

                                    {{-- İşlemler --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ route('admin.crm.customers.show', $customer) }}"
                                               class="w-8 h-8 rounded-lg flex items-center justify-center text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-150"
                                               title="Görüntüle">
                                                <x-icon name="goster" class="w-4 h-4" />
                                            </a>
                                            <a href="{{ route('admin.crm.customers.edit', $customer) }}"
                                               class="w-8 h-8 rounded-lg flex items-center justify-center text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors duration-150"
                                               title="Düzenle">
                                                <x-icon name="duzenle" class="w-4 h-4" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Sayfalama --}}
                <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-700">
                    {{ $customers->appends(request()->query())->links() }}
                </div>
            @else
                {{-- Boş Durum --}}
                <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                        <x-icon name="kullanicilar" class="w-7 h-7 text-gray-300 dark:text-slate-500" />
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-slate-200 mb-1">Henüz müşteri bulunmuyor</h3>
                    <p class="text-sm text-gray-400 dark:text-slate-500 mb-6">İlk müşterinizi ekleyerek başlayın.</p>
                    <a href="{{ route('admin.crm.customers.create') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white font-semibold text-sm rounded-xl shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:outline-none transition-all duration-200 dark:shadow-none dark:focus:ring-offset-slate-900">
                        <x-icon name="ekle" class="w-4 h-4" />
                        Yeni Müşteri Ekle
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
