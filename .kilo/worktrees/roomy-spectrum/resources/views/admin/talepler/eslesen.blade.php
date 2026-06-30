@extends('admin.layouts.admin')

@section('title', 'Eşleşen İlanlar')
@section('page-title', 'Eşleşen İlanlar')

@section('content')
<div class="bg-gray-50 dark:bg-slate-900 shadow overflow-hidden sm:rounded-lg mb-6 dark:shadow-none">
    <div class="px-4 py-5 sm:px-6 flex justify-between">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white dark:text-slate-100">
                {{ $talep->baslik }}
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Talep detayları ve eşleşen ilanlar
            </p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('admin.talepler.show', ['talep' => $talep->id]) }}" class="inline-flex items-center px-4 py-2.5 border border-transparent text-sm leading-4 font-medium rounded-lg shadow-sm text-white justify-center gap-2 transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg focus:outline-none touch-target-optimized">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Talep Detayları
            </a>
            <a href="{{ route('admin.talepler.edit', $talep) }}" class="inline-flex items-center px-4 py-2.5 border border-transparent text-sm leading-4 font-medium rounded-lg shadow-sm text-white justify-center gap-2 transition-all duration-200 focus:ring-2 focus:ring-offset-2-warning focus:outline-none focus:ring-offset-2 focus:ring-yellow-500 touch-target-optimized dark:shadow-none">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
                Talebi Düzenle
            </a>
        </div>
    </div>
    <div class="border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <dl>
            <div class="bg-gray-50 dark:bg-slate-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Müşteri
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2 dark:text-slate-100">
                    @if($talep->kisi)
                        <a href="{{ route('admin.kisiler.show', $talep->kisi_id) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                            {{ $talep->kisi->ad }} {{ $talep->kisi->soyad }}
                        </a>
                    @else
                        <span class="text-red-500 dark:text-red-400">Silinmiş Müşteri</span>
                    @endif
                </dd>
            </div>
            <div class="bg-gray-50 dark:bg-slate-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    İlan Türü
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2 dark:text-slate-100">
                    {{ $talep->ilan_turu }}
                </dd>
            </div>
            <div class="bg-gray-50 dark:bg-slate-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Emlak Türü
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2 dark:text-slate-100">
                    {{ $talep->emlak_turu }}
                </dd>
            </div>
            <div class="bg-gray-50 dark:bg-slate-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Konum
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2 dark:text-slate-100">
                    {{ is_object($talep->il) ? $talep->il->il_adi : ($talep->il ?? '') }}
                    {{ $talep->ilce ? ', '.(is_object($talep->ilce) ? $talep->ilce->ilce_adi : $talep->ilce) : '' }}
                    {{ $talep->mahalle ? ', '.$talep->mahalle : '' }}
                </dd>
            </div>
            <div class="bg-gray-50 dark:bg-slate-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Fiyat Aralığı
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2 dark:text-slate-100">
                    @if($talep->min_fiyat || $talep->max_fiyat)
                        {{ $talep->min_fiyat ?? 0 }} - {{ $talep->max_fiyat ?? '∞' }} {{ $talep->para_birimi ?? 'TL' }}
                    @else
                        Belirtilmemiş
                    @endif
                </dd>
            </div>
            <div class="bg-gray-50 dark:bg-slate-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Metrekare Aralığı
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2 dark:text-slate-100">
                    @if($talep->min_metrekare || $talep->max_metrekare)
                        {{ $talep->min_metrekare ?? 0 }} - {{ $talep->max_metrekare ?? '∞' }} m²
                    @else
                        Belirtilmemiş
                    @endif
                </dd>
            </div>
        </dl>
    </div>
</div>

<!-- Eşleşen İlanlar -->
<div class="bg-gray-50 dark:bg-slate-900 shadow overflow-hidden sm:rounded-lg dark:shadow-none">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white dark:text-slate-100">
            Eşleşen İlanlar ({{ $eslesenIlanlar->count() }})
        </h3>
        <a href="{{ route('admin.talepler.eslesen', $talep) }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Eşleşmeleri Yenile
        </a>
    </div>

    @if($eslesenIlanlar->count() > 0)
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead class="bg-gray-50 dark:bg-slate-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        ID
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Başlık
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Fiyat
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Konum
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Metrekare
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Danışman
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        İşlemler
                    </th>
                </tr>
            </thead>
            <tbody class="bg-gray-50 dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($eslesenIlanlar as $ilan)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $ilan->id }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                        <a href="{{ route('admin.ilanlar.show', $ilan) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                            {{ $ilan->baslik }}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format($ilan->fiyat, 0, ',', '.') }} {{ $ilan->para_birimi ?? 'TL' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ is_object($ilan->il) ? $ilan->il->il_adi : ($ilan->il ?? '') }}
                        {{ $ilan->ilce ? ', '.(is_object($ilan->ilce) ? $ilan->ilce->ilce_adi : $ilan->ilce) : '' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $ilan->metrekare ?? '-' }} m²
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $ilan->danisman ? $ilan->danisman->name : 'Atanmamış' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.ilanlar.show', $ilan) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 mr-3">Görüntüle</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-6 text-center text-gray-500 dark:text-gray-400">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Eşleşen ilan bulunamadı</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bu talebe uygun ilan bulunmuyor.</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Filtreleri değiştirmek için talebi düzenleyebilirsiniz.</p>
    </div>
    @endif
</div>
@endsection
