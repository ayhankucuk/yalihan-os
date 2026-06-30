@extends('layouts.owner')

@section('title', $ilan->baslik . ' — İlan Detayı')

@section('content')
<div class="mb-6 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-2">
            <a href="{{ route('owner.ilanlar.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200">
                &larr; İlanlarım
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-sm text-gray-400">İlan #{{ $ilan->ilan_no ?? $ilan->id }}</span>
        </div>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $ilan->baslik }}</h1>
    </div>
    
    <div class="flex items-center gap-3">
        @if($ilan->yayindami)
            <span class="inline-flex items-center gap-1.5 rounded-md bg-green-50 px-3 py-1.5 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/20">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                Yayında
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">
                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                {{ ucfirst($ilan->yayin_durumu ?? 'Pasif') }}
            </span>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Sol Taraf (Ana Detaylar) --}}
    <div class="lg:col-span-2 space-y-6">
        
        {{-- Fotoğraf Galerisi (Özet) --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            @if($ilan->fotograflar && $ilan->fotograflar->count() > 0)
                @php $kapak = $ilan->fotograflar->where('is_cover', true)->first() ?? $ilan->fotograflar->first(); @endphp
                <div class="relative h-64 sm:h-96 w-full">
                    <img src="{{ Storage::url($kapak->file_path) }}" alt="{{ $ilan->baslik }}" class="h-full w-full object-cover">
                </div>
            @else
                <div class="flex h-64 w-full items-center justify-center bg-gray-50 text-gray-400 dark:bg-slate-800 dark:text-slate-500">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="mt-2 block text-sm">Fotoğraf Yok</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Detaylar Tablosu --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/50">
                <h3 class="font-semibold text-gray-900 dark:text-white">İlan Özeti</h3>
            </div>
            <div class="px-5 py-5">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Kategori</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $ilan->anaKategori->isim ?? '-' }} @if($ilan->altKategori) / {{ $ilan->altKategori->isim }} @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Konum</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $ilan->il->il_adi ?? '-' }}, {{ $ilan->ilce->ilce_adi ?? '-' }}, {{ $ilan->mahalle->mahalle_adi ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Oda Sayısı</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $ilan->oda_sayisi ?? '-' }} @if($ilan->salon_sayisi) + {{ $ilan->salon_sayisi }} @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Metrekare (Brüt / Net)</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $ilan->brut_m2 ?? '-' }} m² / {{ $ilan->net_m2 ?? '-' }} m²
                        </dd>
                    </div>
                </dl>
                
                @if($ilan->aciklama)
                    <div class="mt-6 border-t border-gray-100 pt-6 dark:border-slate-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Açıklama</dt>
                        <dd class="mt-2 text-sm text-gray-900 dark:text-slate-300 prose prose-sm max-w-none dark:prose-invert">
                            {!! nl2br(e($ilan->aciklama)) !!}
                        </dd>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sağ Taraf (Fiyat, İstatistikler, Danışman) --}}
    <div class="space-y-6">
        
        {{-- Fiyat Kartı --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400">Güncel Fiyat</h3>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format((float) $ilan->fiyat, 0, ',', '.') }} {{ $ilan->para_birimi }}
            </div>
        </div>

        {{-- İstatistikler --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-4 text-sm font-medium text-gray-500 dark:text-slate-400">İstatistikler (Yakında)</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-slate-300">Görüntülenme</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $ilan->goruntulenme ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-slate-300">Favoriye Ekleme</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $ilan->favorite_count ?? 0 }}</span>
                </div>
            </div>
        </div>

        {{-- Danışman Kartı --}}
        @if($ilan->danisman && $ilan->danisman->id)
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-4 text-sm font-medium text-gray-500 dark:text-slate-400">Sorumlu Danışman</h3>
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                    {{ strtoupper(substr($ilan->danisman->name, 0, 1)) }}
                </div>
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $ilan->danisman->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-slate-400">{{ $ilan->danisman->email }}</div>
                    @if($ilan->danisman->phone_number)
                        <div class="text-sm text-gray-500 dark:text-slate-400">{{ $ilan->danisman->phone_number }}</div>
                    @endif
                </div>
            </div>
            <div class="mt-4">
                <a href="mailto:{{ $ilan->danisman->email }}" class="block w-full rounded-md bg-blue-50 px-3 py-2 text-center text-sm font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50">
                    Mesaj Gönder
                </a>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
