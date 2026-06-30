@extends('admin.layouts.admin')

@section('title', 'Müşteri Detayı')

@section('content')
    <div class="content-header mb-8">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-200 flex items-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        {{ $kisi->tam_ad }}
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Müşteri detayları ve talepleri
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <x-neo.button variant="primary" href="{{ route('admin.kisiler.edit', $kisi) }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Düzenle
                    </x-neo.button>

                    <x-neo.dropdown>
                        <x-neo.dropdown-item href="{{ route('admin.talepler.create', ['kisi_id' => $kisi->id]) }}"
                            icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>'>
                            Yeni Talep Oluştur
                        </x-neo.dropdown-item>
                        <x-neo.dropdown-item href="{{ route('admin.ilanlar.create') }}"
                            icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path></svg>'>
                            İlan Ekle
                        </x-neo.dropdown-item>
                        <x-neo.dropdown-item variant="danger"
                            onclick="if(confirm('Bu müşteriyi silmek istediğinizden emin misiniz?')) { document.getElementById('delete-form').submit(); }"
                            icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>'>
                            Müşteriyi Sil
                        </x-neo.dropdown-item>
                    </x-neo.dropdown>

                    <form id="delete-form" action="{{ route('admin.kisiler.destroy', $kisi) }}" method="POST"
                        class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>

                    <x-neo.button variant="secondary" href="{{ route('admin.kisiler.index') }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Geri Dön
                    </x-neo.button>
                </div>
            </div>
        </div>
    </div>

    <!-- Müşteri Bilgileri -->
    <div class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-8 dark:shadow-none dark:border-slate-700">
        <div class="p-6">
            <h2 class="text-xl font-bold text-blue-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                👤 Müşteri Bilgileri
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="flex items-center">
                        <span class="w-24 font-medium text-gray-700 dark:text-slate-300">Ad Soyad:</span>
                        <span class="text-gray-900 font-semibold dark:text-slate-100 dark:text-white">{{ $kisi->ad }} {{ $kisi->soyad }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 font-medium text-gray-700 dark:text-slate-300">Telefon:</span>
                        <span class="text-gray-900 font-semibold dark:text-slate-100 dark:text-white">{{ $kisi->telefon }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 font-medium text-gray-700 dark:text-slate-300">E-posta:</span>
                        <span class="text-gray-900 font-semibold dark:text-slate-100 dark:text-white">{{ $kisi->email ?? '-' }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 font-medium text-gray-700 dark:text-slate-300">Adres:</span>
                        <span class="text-gray-900 font-semibold dark:text-slate-100 dark:text-white">
                            @if ($kisi->il)
                                {{ $kisi->il->il_adi ?? $kisi->il }}
                                @if ($kisi->ilce)
                                    , {{ $kisi->ilce->ilce_adi ?? $kisi->ilce }}
                                @endif
                                @if ($kisi->mahalle)
                                    , {{ $kisi->mahalle->mahalle_adi ?? $kisi->mahalle }}
                                @endif
                            @else
                                {{ $kisi->adres ?? '-' }}
                            @endif
                        </span>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center">
                        <span class="w-24 font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Durum:</span>
                        <span
                            class="px-3 py-1 inline-flex text-sm font-semibold rounded-full
                            {{ $kisi->aktiflik_durumu === 'Aktif'
                                ? 'bg-green-100 text-green-800 border border-green-200 dark:bg-green-900 dark:text-green-200'
                                : ($kisi->aktiflik_durumu === 'Pasif'
                                    ? 'bg-gray-100 text-gray-800 border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-slate-700'
                                    : ($kisi->aktiflik_durumu === 'Potansiyel'
                                        ? 'bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-900 dark:text-blue-200'
                                        : 'bg-yellow-100 text-yellow-800 border border-yellow-200 dark:bg-yellow-900 dark:text-yellow-200')) }}">
                            {{ $kisi->aktiflik_durumu ?? '-' }}
                        </span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 font-medium text-gray-700 dark:text-slate-300">Kaynak:</span>
                        <span class="text-gray-900 font-semibold dark:text-slate-100 dark:text-white">{{ $kisi->kaynak ?? '-' }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 font-medium text-gray-700 dark:text-slate-300">Danışman:</span>
                        <span class="text-gray-900 font-semibold dark:text-slate-100 dark:text-white">
                            @if ($kisi->danisman_verisi)
                                {{ $kisi->danisman_verisi->name }}
                                <span
                                    class="text-xs text-gray-500 ml-1">({{ $kisi->danisman_verisi->source === 'user_model' ? 'User Modeli' : 'Danışman Modeli' }})</span>
                            @else
                                Atanmamış
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 font-medium text-gray-700 dark:text-slate-300">Kayıt Tarihi:</span>
                        <span class="text-gray-900 font-semibold dark:text-slate-100 dark:text-white">{{ $kisi->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Etiketler -->
            @if ($kisi->etiketler && $kisi->etiketler->count() > 0)
                <div class="mt-6 pt-6 border-t border-blue-200">
                    <h3 class="text-lg font-semibold text-blue-700 mb-3">Etiketler</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($kisi->etiketler as $etiket)
                            @php
                                $kisiTagBgColorVal = $etiket->renk ? $etiket->renk . '20' : '#e5e7eb';
                                $kisiTagTextColorVal = $etiket->renk ? $etiket->renk : '#374151';
                                $kisiTagStyles = [
                                    "background-color: {$kisiTagBgColorVal}",
                                    "color: {$kisiTagTextColorVal}",
                                ];
                            @endphp
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full border"
                                @style($kisiTagStyles)>
                                {{ $etiket->ad }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- CORTEX CRM INTELLIGENCE -->
            <div id="cortex-crm-intelligence" class="mt-8 pt-8 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <h3 class="text-xl font-bold text-indigo-800 dark:text-indigo-200 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    🧠 CORTEX CRM INTELLIGENCE
                </h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Priority Score Card -->
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-xl p-6 border border-indigo-200 dark:border-indigo-800 flex flex-col items-center justify-center">
                        <div class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-4">Müşteri Öncelik Skoru</div>
                        <div class="relative w-32 h-32 flex items-center justify-center">
                            <svg class="w-full h-full transform -rotate-90">
                                <circle cx="64" cy="64" r="58" stroke="currentColor" class="text-indigo-200 dark:text-indigo-900/40" stroke-width="12" fill="none"/>
                                <circle cx="64" cy="64" r="58" stroke="currentColor" class="text-indigo-500 dark:text-indigo-400" stroke-width="12" fill="none"
                                    stroke-dasharray="364.4" stroke-dashoffset="{{ 364.4 * (1 - ($priorityScore ?? 0) / 100) }}" stroke-linecap="round" />
                            </svg>
                            <div class="absolute text-4xl font-black text-indigo-700 dark:text-indigo-300">{{ $priorityScore ?? 0 }}</div>
                        </div>
                        <div class="mt-4 text-center text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                            {{ $priorityScore >= 80 ? '🔥 Kritik Öncelikli - Hemen Ara!' : ($priorityScore >= 50 ? '⚡ Orta Öncelikli - Takipte Kal' : '🧊 Düşük Öncelikli - Isıtma Gerekli') }}
                        </div>
                    </div>

                    <!-- Recommended Listings -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                        <h4 class="font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center justify-between">
                            <span>🎯 Semantik Önerilen İlanlar</span>
                            <span class="text-xs font-normal text-gray-400">Vektörel Benzerlik</span>
                        </h4>
                        
                        <div class="space-y-3">
                            @forelse($recommendedListings ?? [] as $rec)
                                @php $ilan = \App\Models\Ilan::find($rec['ilan_id']); @endphp
                                @if($ilan)
                                <div class="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900/30">
                                    <div class="w-16 h-16 rounded bg-gray-100 overflow-hidden flex-shrink-0 dark:bg-slate-900">
                                        @if($ilan->kapak_fotografi_url)
                                            <img src="{{ $ilan->kapak_fotografi_url }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">🏡</div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white truncate dark:text-slate-100">{{ $ilan->baslik }}</div>
                                        <div class="text-xs text-indigo-600 dark:text-indigo-400 font-black mt-1">
                                            {{ number_format($ilan->fiyat) }} {{ $ilan->para_birimi }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[10px] font-bold text-indigo-500 uppercase">Match</div>
                                        <div class="text-sm font-black text-indigo-600 dark:text-indigo-400">%{{ round($rec['score'] * 100) }}</div>
                                    </div>
                                    <a href="{{ route('admin.ilanlar.show', $ilan) }}" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </div>
                                @endif
                            @empty
                                <div class="text-center py-6 text-sm text-gray-400 italic">Eşleşen ilan bulunamadı.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- CORTEX FİNANSAL ANALİZİ -->
            <div id="cortex-finansal-analiz" class="mt-8 pt-8 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <h3 class="text-xl font-bold text-purple-800 dark:text-purple-200 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    🧠 CORTEX FİNANSAL ANALİZİ
                </h3>
                <div id="cortex-strategy-content" class="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg p-6 border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center justify-center py-8">
                        <div class="text-center">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 dark:border-purple-400 mb-4"></div>
                            <p class="text-gray-600 dark:text-gray-400">Pazarlık stratejisi analiz ediliyor...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notlar -->
            @if ($kisi->notlar)
                <div class="mt-6 pt-6 border-t border-blue-200">
                    <h3 class="text-lg font-semibold text-blue-700 mb-3">Notlar</h3>
                    <p class="text-gray-700 leading-relaxed whitespace-pre-line dark:text-slate-300">{{ $kisi->notlar }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Müşteri Talepleri -->
    <div class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-8 dark:shadow-none dark:border-slate-700">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-green-800 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    📋 Müşteri Talepleri
                </h2>
                <x-neo.button variant="primary" href="{{ route('admin.talepler.create', ['kisi_id' => $kisi->id]) }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Yeni Talep
                </x-neo.button>
            </div>

            @if ($kisi->talepler && $kisi->talepler->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-green-200">
                        <thead class="bg-green-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                                    Talep</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                                    Türü</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                                    Konum</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                                    Durum</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                                    Tarih</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-green-800 uppercase tracking-wider">
                                    İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-green-200 dark:divide-gray-700">
                            @foreach ($kisi->talepler as $talep)
                                <tr class="hover:bg-green-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                            {{ $talep->baslik }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                            {{ $talep->ilan_turu }} {{ $talep->emlak_turu }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                            {{ is_object($talep->il) ? $talep->il->il_adi : $talep->il ?? '' }}{{ $talep->ilce ? ', ' . (is_object($talep->ilce) ? $talep->ilce->ilce_adi : $talep->ilce) : '' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                            {{ $talep->talep_durumu == 'Yeni'
                                                ? 'bg-blue-100 text-blue-800 border border-blue-200'
                                                : ($talep->talep_durumu == 'İşleniyor'
                                                    ? 'bg-yellow-100 text-yellow-800 border border-yellow-200'
                                                    : ($talep->talep_durumu == 'Beklemede'
                                                        ? 'bg-purple-100 text-purple-800 border border-purple-200'
                                                        : ($talep->talep_durumu == 'Tamamlandı'
                                                            ? 'bg-green-100 text-green-800 border border-green-200'
                                                            : 'bg-red-100 text-red-800 border border-red-200'))) }}">
                                            {{ $talep->talep_durumu }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                        {{ $talep->created_at->format('d.m.Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <x-neo.button variant="ghost" size="sm"
                                                href="{{ route('admin.talepler.show', $talep) }}">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                    </path>
                                                </svg>
                                                Detay
                                            </x-neo.button>
                                            <x-neo.button variant="ghost" size="sm"
                                                href="{{ route('admin.talepler.edit', $talep) }}">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                                Düzenle
                                            </x-neo.button>
                                            <x-neo.button variant="ghost" size="sm"
                                                href="{{ route('admin.talepler.eslesen', $talep) }}">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                                    </path>
                                                </svg>
                                                Eşleşen
                                            </x-neo.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">Talep bulunamadı</h3>
                    <p class="mt-1 text-sm text-gray-500">Bu müşteri için henüz talep oluşturulmamış.</p>
                    <div class="mt-6">
                        <x-neo.button variant="primary"
                            href="{{ route('admin.talepler.create', ['kisi_id' => $kisi->id]) }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Yeni Talep Oluştur
                        </x-neo.button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Activity Timeline (CRM Memory) -->
    <div class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-8 dark:shadow-none dark:border-slate-700" x-data="activityTimeline({{ $kisi->id }})">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-indigo-800 dark:text-indigo-300 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ⏱️ Aktivite Zaman Çizelgesi
                </h2>
                <button @click="showAddForm = !showAddForm" 
                        class="px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg 
                               transition-all duration-200 hover:scale-105">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Aktivite Ekle
                </button>
            </div>
            
            {{-- Quick Add Form --}}
            <div x-show="showAddForm" 
                 x-transition
                 class="mb-6 bg-white dark:bg-gray-700 rounded-lg p-4 border border-indigo-200 dark:border-indigo-700 dark:bg-slate-900">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">Hızlı Aktivite Ekle</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Aktivite Tipi</label>
                        <select x-model="newActivity.type" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                       bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm
                                       focus:ring-2 focus:ring-indigo-500">
                            <option value="arama">📞 Arama</option>
                            <option value="whatsapp">💬 WhatsApp</option>
                            <option value="email">📧 E-posta</option>
                            <option value="randevu">📅 Randevu</option>
                            <option value="gorusme">🤝 Görüşme</option>
                            <option value="not">📝 Not</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Açıklama</label>
                        <input type="text" x-model="newActivity.description"
                               placeholder="Aktivite detayları..."
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm
                                      focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button @click="addActivity()" 
                            class="px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm
                                   transition-all duration-200">
                        Kaydet
                    </button>
                    <button @click="showAddForm = false" 
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm
                                   transition-all duration-200">
                        İptal
                    </button>
                </div>
            </div>
            
            {{-- Timeline --}}
            <div class="relative">
                {{-- Vertical Line --}}
                <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-indigo-200 dark:bg-indigo-700"></div>
                
                <div class="space-y-6">
                    <template x-for="(activity, index) in activities" :key="activity.id">
                        <div class="relative flex gap-4 pl-2">
                            {{-- Icon --}}
                            <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center z-10
                                        bg-white dark:bg-gray-700 border-2 border-indigo-500 dark:border-indigo-400"
                                 :class="{
                                     'bg-blue-100 dark:bg-blue-900': activity.etkilesim_tipi === 'arama',
                                     'bg-green-100 dark:bg-green-900': activity.etkilesim_tipi === 'whatsapp',
                                     'bg-purple-100 dark:bg-purple-900': activity.etkilesim_tipi === 'randevu',
                                     'bg-yellow-100 dark:bg-yellow-900': activity.etkilesim_tipi === 'gorusme',
                                     'bg-gray-100 dark:bg-gray-800': activity.etkilesim_tipi === 'not'
                                 }">
                                <span x-text="getActivityIcon(activity.etkilesim_tipi)" class="text-xl"></span>
                            </div>
                            
                            {{-- Content Card --}}
                            <div class="flex-1 bg-white dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600
                                        hover:shadow-md transition-all duration-200">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white capitalize dark:text-slate-100"
                                              x-text="getActivityLabel(activity.etkilesim_tipi)"></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2"
                                              x-text="formatDate(activity.tarih)"></span>
                                    </div>
                                    
                                    <span x-show="activity.kullanici" 
                                          class="text-xs text-gray-500 dark:text-gray-400"
                                          x-text="activity.kullanici?.name || ''"></span>
                                </div>
                                
                                <p class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300"
                                   x-text="activity.aciklama"></p>
                            </div>
                        </div>
                    </template>
                    
                    {{-- Loading State --}}
                    <template x-if="loading">
                        <div class="text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Yükleniyor...</p>
                        </div>
                    </template>
                    
                    {{-- Empty State --}}
                    <template x-if="!loading && activities.length === 0">
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Henüz aktivite yok</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">İlk aktiviteyi ekleyerek başlayın.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Sahip Olduğu Gayrimenkuller -->
    <div class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-purple-800 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z" />
                    </svg>
                    🏠 Sahip Olduğu Gayrimenkuller
                </h2>
                <x-neo.button variant="primary" href="{{ route('admin.ilanlar.create') }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Yeni İlan Ekle
                </x-neo.button>
            </div>

            @if (isset($kisiGayrimenkulleri) && $kisiGayrimenkulleri->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-purple-200">
                        <thead class="bg-purple-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    İlan</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    Tür</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    Konum</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    Fiyat</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    Durum</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    Tarih</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-purple-200 dark:divide-gray-700">
                            @foreach ($kisiGayrimenkulleri as $ilan)
                                <tr class="hover:bg-purple-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if ($ilan->kapak_fotografi_url)
                                                <img src="{{ $ilan->kapak_fotografi_url }}" alt="Kapak Fotoğrafı"
                                                    class="w-12 h-12 rounded-lg object-cover mr-3">
                                            @else
                                                <div
                                                    class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z" />
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                                    {{ $ilan->ilan_basligi }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ID: {{ $ilan->id }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <span
                                                class="px-2 py-1 text-xs font-medium rounded-full
                                                {{ $ilan->ilan_turu == 'Satılık' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                                                {{ $ilan->ilan_turu }}
                                            </span>
                                            <span
                                                class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                                {{ $ilan->emlak_turu ?? 'Belirtilmemiş' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                            @if ($ilan->il)
                                                {{ $ilan->il->il_adi ?? $ilan->il }}
                                            @endif
                                            @if ($ilan->ilce)
                                                , {{ $ilan->ilce->name ?? $ilan->ilce }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                            {{ number_format($ilan->fiyat, 0, ',', '.') }} ₺
                                        </div>
                                        @if ($ilan->net_metrekare)
                                            <div class="text-xs text-gray-500">
                                                {{ number_format($ilan->fiyat / $ilan->net_metrekare, 0, ',', '.') }} ₺/m²
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $ilanDurumu = $ilan->yayin_statusu ?? 'Taslak';
                                        @endphp
                                        <span
                                            class="px-2 py-1 text-xs font-medium rounded-full
                                            {{ $ilanDurumu == 'Aktif'
                                                ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-700'
                                                : ($ilanDurumu == 'Pasif'
                                                    ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-700'
                                                    : ($ilanDurumu == 'Taslak'
                                                        ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-700'
                                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600')) }}">
                                            {{ $ilanDurumu }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                            {{ $ilan->created_at->format('d.m.Y') }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $ilan->created_at->diffForHumans() }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <x-neo.button variant="ghost" size="sm"
                                                href="{{ route('admin.ilanlar.show', $ilan) }}">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                    </path>
                                                </svg>
                                                Görüntüle
                                            </x-neo.button>
                                            <x-neo.button variant="ghost" size="sm"
                                                href="{{ route('admin.ilanlar.edit', $ilan) }}">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                                Düzenle
                                            </x-neo.button>
                                            <x-neo.button variant="ghost" size="sm"
                                                href="{{ route('admin.ilanlar.show', $ilan) }}">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                                    </path>
                                                </svg>
                                                Analiz
                                            </x-neo.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">Gayrimenkul bulunamadı</h3>
                    <p class="mt-1 text-sm text-gray-500">Bu kişi henüz gayrimenkul ilanı eklememiş.</p>
                    <div class="mt-6">
                        <x-neo.button variant="primary" href="{{ route('admin.ilanlar.create') }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            İlk İlanı Ekle
                        </x-neo.button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const kisiId = {{ $kisi->id }};
        const strategyContent = document.getElementById('cortex-strategy-content');
        
        // API'den pazarlık stratejisini çek
        fetch(`/api/v1/ai/strategy/${kisiId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.strategy) {
                const strategy = data.data.strategy;
                const customerProfile = data.data.customer_profile || {};
                
                // Widget içeriğini oluştur
                strategyContent.innerHTML = `
                    <div class="space-y-4">
                        <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                            <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pazarlık Önerisi
                            </h4>
                            <p class="text-gray-700 dark:text-slate-200 leading-relaxed dark:text-slate-300">
                                ${strategy.summary || strategy.recommendation || 'Standart pazarlık stratejisi uygulayın.'}
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                                <h5 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Müşteri Profili</h5>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Yatırımcı Profili:</span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">${customerProfile.yatirimci_profili || 'Bilinmiyor'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Satış Potansiyeli:</span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">${customerProfile.satis_potansiyeli || 0}/100</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Gelir Düzeyi:</span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">${customerProfile.gelir_duzeyi || 'Bilinmiyor'}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                                <h5 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Strateji Detayları</h5>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">İndirim Yaklaşımı:</span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 capitalize dark:text-white">${strategy.discount_approach || 'moderate'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Odak Noktası:</span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 capitalize dark:text-white">${strategy.focus || 'balanced'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                strategyContent.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-red-600 dark:text-red-400">Pazarlık stratejisi yüklenemedi.</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">${data.message || 'Bir hata oluştu.'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Cortex strategy error:', error);
            strategyContent.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-red-600 dark:text-red-400">Pazarlık stratejisi yüklenirken bir hata oluştu.</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Lütfen sayfayı yenileyin.</p>
                </div>
            `;
        });
    });
</script>
@endpush

@endsection

@push('styles')
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const kisiId = {{ $kisi->id }};
        const strategyContent = document.getElementById('cortex-strategy-content');
        
        // API'den pazarlık stratejisini çek
        fetch(`/api/v1/ai/strategy/${kisiId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.strategy) {
                const strategy = data.data.strategy;
                const customerProfile = data.data.customer_profile || {};
                
                // Widget içeriğini oluştur
                strategyContent.innerHTML = `
                    <div class="space-y-4">
                        <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                            <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pazarlık Önerisi
                            </h4>
                            <p class="text-gray-700 dark:text-slate-200 leading-relaxed dark:text-slate-300">
                                ${strategy.summary || strategy.recommendation || 'Standart pazarlık stratejisi uygulayın.'}
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                                <h5 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Müşteri Profili</h5>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Yatırımcı Profili:</span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">${customerProfile.yatirimci_profili || 'Bilinmiyor'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Satış Potansiyeli:</span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">${customerProfile.satis_potansiyeli || 0}/100</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Gelir Düzeyi:</span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">${customerProfile.gelir_duzeyi || 'Bilinmiyor'}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                                <h5 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Strateji Detayları</h5>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">İndirim Yaklaşımı:</span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 capitalize dark:text-white">${strategy.discount_approach || 'moderate'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Odak Noktası:</span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 capitalize dark:text-white">${strategy.focus || 'balanced'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                strategyContent.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-red-600 dark:text-red-400">Pazarlık stratejisi yüklenemedi.</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">${data.message || 'Bir hata oluştu.'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Cortex strategy error:', error);
            strategyContent.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-red-600 dark:text-red-400">Pazarlık stratejisi yüklenirken bir hata oluştu.</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Lütfen sayfayı yenileyin.</p>
                </div>
            `;
        });
    });
</script>
@endpush

@push('scripts')
<script>
    // Activity Timeline Component
    function activityTimeline(kisiId) {
        return {
            activities: [],
            loading: true,
            showAddForm: false,
            newActivity: {
                type: 'not',
                description: ''
            },
            
            async init() {
                await this.loadActivities();
            },
            
            async loadActivities() {
                this.loading = true;
                
                try {
                    const response = await fetch(`/admin/crm/people/${kisiId}/activities`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.activities = result.activities;
                    }
                    
                } catch (error) {
                    console.error('❌ Activities load error:', error);
                } finally {
                    this.loading = false;
                }
            },
            
            async addActivity() {
                if (!this.newActivity.description.trim()) {
                    alert('Lütfen açıklama girin');
                    return;
                }
                
                try {
                    const response = await fetch(`/admin/crm/people/${kisiId}/activities`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            etkilesim_tipi: this.newActivity.type,
                            aciklama: this.newActivity.description
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Add to top of list
                        this.activities.unshift(result.activity);
                        
                        // Reset form
                        this.newActivity.description = '';
                        this.showAddForm = false;
                        
                        this.showToast('✅ Aktivite eklendi', 'success');
                    }
                    
                } catch (error) {
                    console.error('❌ Add activity error:', error);
                    this.showToast('❌ Aktivite eklenemedi', 'error');
                }
            },
            
            getActivityIcon(type) {
                const icons = {
                    'arama': '📞',
                    'whatsapp': '💬',
                    'email': '📧',
                    'randevu': '📅',
                    'gorusme': '🤝',
                    'not': '📝',
                    'surec_degisikligi': '🔄'
                };
                return icons[type] || '📋';
            },
            
            getActivityLabel(type) {
                const labels = {
                    'arama': 'Arama',
                    'whatsapp': 'WhatsApp',
                    'email': 'E-posta',
                    'randevu': 'Randevu',
                    'gorusme': 'Görüşme',
                    'not': 'Not',
                    'surec_degisikligi': 'Süreç Değişikliği'
                };
                return labels[type] || type;
            },
            
            formatDate(dateStr) {
                const date = new Date(dateStr);
                const now = new Date();
                const diff = now - date;
                const hours = diff / (1000 * 60 * 60);
                
                if (hours < 1) {
                    return 'Az önce';
                } else if (hours < 24) {
                    return Math.floor(hours) + ' saat önce';
                } else if (hours < 48) {
                    return 'Dün';
                } else {
                    return date.toLocaleDateString('tr-TR', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            },
            
            showToast(message, type) {
                const event = new CustomEvent('show-toast', {
                    detail: { message, type }
                });
                window.dispatchEvent(event);
                console.log(message);
            }
        };
    }
</script>
@endpush
