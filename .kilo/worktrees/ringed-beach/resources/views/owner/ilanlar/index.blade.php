@extends('layouts.owner')

@section('title', 'İlanlarım')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">İlanlarım</h1>
</div>

@if($ilanlar->isEmpty())
    <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Henüz portföyünüzde bir ilan bulunmuyor</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">Yeni mülk eklemek veya ilan ataması için danışmanınızla iletişime geçin.</p>
    </div>
@else
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($ilanlar as $ilan)
            <div class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
                <div class="relative h-48 w-full bg-gray-100 dark:bg-slate-700">
                    {{-- Kapak Fotoğrafı --}}
                    @if($ilan->fotograflar && $ilan->fotograflar->count() > 0)
                        @php
                            $kapak = $ilan->fotograflar->where('is_cover', true)->first() ?? $ilan->fotograflar->first();
                        @endphp
                        <img src="{{ Storage::url($kapak->file_path) }}" alt="{{ $ilan->baslik }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center text-gray-400 dark:text-slate-500">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif

                    {{-- Yayında/Pasif Etiketi --}}
                    <div class="absolute right-3 top-3">
                        @if($ilan->yayindami)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                Yayında
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                {{ ucfirst($ilan->yayin_durumu ?? 'Pasif') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-1 flex-col p-4">
                    <div class="mb-1 text-xs font-medium text-blue-600 dark:text-blue-400">
                        {{ $ilan->anaKategori->isim ?? 'Kategori' }} 
                        @if($ilan->altKategori) &rsaquo; {{ $ilan->altKategori->isim }} @endif
                    </div>
                    
                    <h3 class="mb-2 line-clamp-2 text-base font-semibold text-gray-900 dark:text-white">
                        <a href="{{ route('owner.ilanlar.show', $ilan->id) }}" class="hover:underline">
                            {{ $ilan->baslik }}
                        </a>
                    </h3>

                    <div class="mb-4 text-sm text-gray-500 dark:text-slate-400">
                        <svg class="mr-1 inline-block h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ $ilan->il->il_adi ?? '' }} / {{ $ilan->ilce->ilce_adi ?? '' }}
                    </div>

                    <div class="mt-auto pt-4 border-t border-gray-100 dark:border-slate-700 flex items-center justify-between">
                        <div class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format((float) $ilan->fiyat, 0, ',', '.') }} {{ $ilan->para_birimi }}
                        </div>
                        <a href="{{ route('owner.ilanlar.show', $ilan->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            Detaylar &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $ilanlar->links() }}
    </div>
@endif
@endsection
