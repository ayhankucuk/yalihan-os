@extends('layouts.owner')

@section('title', 'Teklifler & Talepler')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Teklifler & Talepler</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">İlanlarınıza gelen fiyat teklifleri ve sistemdeki eşleşen müşteri talepleri.</p>
</div>

{{-- Alpine.js ile Sekme Yapısı --}}
<div x-data="{ tab: 'teklifler' }">
    <div class="border-b border-gray-200 dark:border-slate-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="tab = 'teklifler'"
                :class="tab === 'teklifler' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                Gelen Teklifler
                <span class="ml-2 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                    {{ $teklifler->count() }}
                </span>
            </button>

            <button @click="tab = 'talepler'"
                :class="tab === 'talepler' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                Sistem Eşleşmeleri
                <span class="ml-2 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                    {{ $eslesmeler->count() }}
                </span>
            </button>
        </nav>
    </div>

    {{-- Gelen Teklifler Sekmesi --}}
    <div x-show="tab === 'teklifler'" class="mt-6">
        @if($teklifler->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Henüz teklif yok</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">İlanlarınıza şu an için doğrudan bir fiyat teklifi yapılmamış.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($teklifler as $teklif)
                    <div class="group flex flex-col rounded-2xl border border-gray-200/60 bg-white/80 p-5 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/10 dark:border-slate-700/50 dark:bg-slate-800/80">
                        <div class="mb-4 flex items-start justify-between">
                            <div>
                                <div class="text-xs font-medium text-blue-600 dark:text-blue-400">İlan #{{ $teklif->ilan->ilan_no ?? $teklif->ilan->id }}</div>
                                <h3 class="mt-1 line-clamp-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $teklif->ilan->baslik }}</h3>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset transition-colors
                                @if($teklif->teklif_durumu->value === 'kabul_edildi') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400
                                @elseif($teklif->teklif_durumu->value === 'reddedildi') bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400
                                @elseif($teklif->teklif_durumu->value === 'beklemede') bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400
                                @else bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 @endif">
                                {{ $teklif->teklif_durumu->label() }}
                            </span>
                        </div>
                        
                        <div class="mb-4 mt-auto rounded-lg bg-gray-50 p-3 dark:bg-slate-900/50">
                            <div class="text-xs text-gray-500 dark:text-slate-400">Teklif Edilen Fiyat</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ number_format((float) $teklif->teklif_tutari, 0, ',', '.') }} {{ $teklif->para_birimi }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                                Gönderen: {{ $teklif->teklifVeren->tam_ad }} &bull; {{ $teklif->created_at->format('d.m.Y') }}
                            </div>
                        </div>

                        <a href="{{ route('owner.teklifler.show', $teklif->id) }}" class="flex w-full items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-all hover:bg-blue-50 hover:text-blue-700 hover:border-blue-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 dark:hover:text-white">
                            Detayları İncele
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Eşleşmeler Sekmesi --}}
    <div x-show="tab === 'talepler'" class="mt-6" x-cloak>
        @if($eslesmeler->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Henüz eşleşme yok</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">İlanlarınıza uygun aktif alıcı talebi bulunmuyor.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-800/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">İlan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Müşteri</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Eşleşme Skoru</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-slate-400">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                        @foreach($eslesmeler as $eslesme)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $eslesme->ilan->baslik }}</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ number_format((float) $eslesme->ilan->fiyat, 0, ',', '.') }} {{ $eslesme->ilan->para_birimi }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $eslesme->kisi->tam_ad ?? 'Gizli Müşteri' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400">Danışman: {{ $eslesme->danisman->name ?? '-' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-slate-700">
                                            <div class="h-2 rounded-full @if($eslesme->skor > 75) bg-green-500 @elseif($eslesme->skor > 50) bg-yellow-500 @else bg-red-500 @endif" style="width: {{ $eslesme->skor }}%"></div>
                                        </div>
                                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-slate-300">%{{ $eslesme->skor }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-slate-400">
                                    {{ $eslesme->created_at->format('d.m.Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
