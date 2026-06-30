@extends('layouts.owner')

@section('title', 'Teklif Detayı — İlan #' . ($teklif->ilan->ilan_no ?? $teklif->ilan->id))

@section('content')
<div class="mb-6 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-2">
            <a href="{{ route('owner.teklifler.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200">
                &larr; Teklifler & Talepler
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-sm text-gray-400">Teklif #{{ $teklif->id }}</span>
        </div>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Teklif Detayı</h1>
    </div>
    
    <div>
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold ring-1 ring-inset shadow-sm
            @if($teklif->teklif_durumu->value === 'kabul_edildi') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/20
            @elseif($teklif->teklif_durumu->value === 'reddedildi') bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-500/20
            @elseif($teklif->teklif_durumu->value === 'beklemede') bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-500/20
            @else bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 @endif">
            {{ $teklif->teklif_durumu->label() }}
        </span>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    
    {{-- Sol Taraf (Teklif Veren ve İlan Özeti) --}}
    <div class="space-y-6 lg:col-span-2">
        
        {{-- Teklif Kartı --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200/60 bg-white/80 shadow-sm backdrop-blur-md transition-all duration-300 hover:shadow-lg dark:border-slate-700/50 dark:bg-slate-800/80">
            <div class="border-b border-gray-200/60 bg-gray-50/50 px-6 py-5 dark:border-slate-700/50 dark:bg-slate-800/50">
                <h3 class="font-semibold text-gray-900 dark:text-white">Teklif Bilgileri</h3>
            </div>
            <div class="p-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Teklif Edilen Fiyat</div>
                        <div class="mt-1 text-4xl font-extrabold text-blue-600 dark:text-blue-400">
                            {{ number_format((float) $teklif->teklif_tutari, 0, ',', '.') }} <span class="text-2xl">{{ $teklif->para_birimi }}</span>
                        </div>
                    </div>
                    
                    @if($teklif->teklif_durumu->value === 'beklemede')
                        <div class="flex gap-3">
                            <button class="rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:bg-green-500 hover:shadow-md hover:shadow-green-500/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                Kabul Et
                            </button>
                            <button class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 transition-all hover:-translate-y-0.5 hover:bg-gray-50 hover:shadow-md dark:bg-slate-700 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-600">
                                Reddet
                            </button>
                        </div>
                    @endif
                </div>

                @if($teklif->mesaj)
                    <div class="mt-6 rounded-lg bg-gray-50 p-4 dark:bg-slate-900/50">
                        <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Alıcının Mesajı</h4>
                        <p class="text-sm text-gray-700 dark:text-slate-300">{{ $teklif->mesaj }}</p>
                    </div>
                @endif
                
                <div class="mt-6 flex flex-wrap gap-4 text-sm text-gray-500 dark:text-slate-400">
                    <div class="flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        Tarih: {{ $teklif->created_at->format('d.m.Y H:i') }}
                    </div>
                    @if($teklif->gecerlilik_tarihi)
                        <div class="flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Geçerlilik: {{ $teklif->gecerlilik_tarihi->format('d.m.Y') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- İlan Özeti --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200/60 bg-white/80 shadow-sm backdrop-blur-md transition-all duration-300 hover:shadow-lg dark:border-slate-700/50 dark:bg-slate-800/80">
            <div class="border-b border-gray-200/60 bg-gray-50/50 px-6 py-5 dark:border-slate-700/50 dark:bg-slate-800/50">
                <h3 class="font-semibold text-gray-900 dark:text-white">İlgili İlan</h3>
            </div>
            <div class="p-5">
                <div class="flex flex-col sm:flex-row gap-4 items-start">
                    <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-slate-700">
                        @if($teklif->ilan->fotograflar && $teklif->ilan->fotograflar->count() > 0)
                            @php $kapak = $teklif->ilan->fotograflar->where('is_cover', true)->first() ?? $teklif->ilan->fotograflar->first(); @endphp
                            <img src="{{ Storage::url($kapak->file_path) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-gray-400">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                            <a href="{{ route('owner.ilanlar.show', $teklif->ilan->id) }}" class="hover:underline">
                                {{ $teklif->ilan->baslik }}
                            </a>
                        </h4>
                        <div class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                            Liste Fiyatı: <span class="font-semibold text-gray-900 dark:text-white">{{ number_format((float) $teklif->ilan->fiyat, 0, ',', '.') }} {{ $teklif->ilan->para_birimi }}</span>
                        </div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                            İlan No: {{ $teklif->ilan->ilan_no ?? $teklif->ilan->id }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Sağ Taraf (Kişiler ve Danışman) --}}
    <div class="space-y-6">
        
        {{-- Teklif Veren --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200/60 bg-white/80 shadow-sm backdrop-blur-md dark:border-slate-700/50 dark:bg-slate-800/80">
            <div class="border-b border-gray-200/60 bg-gray-50/50 px-5 py-4 dark:border-slate-700/50 dark:bg-slate-800/50">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Alıcı Profili</h3>
            </div>
            <div class="p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-lg font-bold text-gray-600 dark:bg-slate-700 dark:text-slate-300">
                        {{ strtoupper(substr($teklif->teklifVeren->ad, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $teklif->teklifVeren->tam_ad }}</div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">Sistem Kayıtlı Alıcı</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sorumlu Danışman --}}
        @if($teklif->ilan->danisman)
        <div class="overflow-hidden rounded-2xl border border-gray-200/60 bg-white/80 shadow-sm backdrop-blur-md dark:border-slate-700/50 dark:bg-slate-800/80">
            <div class="border-b border-gray-200/60 bg-gray-50/50 px-5 py-4 dark:border-slate-700/50 dark:bg-slate-800/50">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">İşlemi Yürüten Danışman</h3>
            </div>
            <div class="p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                        {{ strtoupper(substr($teklif->ilan->danisman->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $teklif->ilan->danisman->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">{{ $teklif->ilan->danisman->phone_number ?? $teklif->ilan->danisman->email }}</div>
                    </div>
                </div>
                <div class="mt-5">
                    <a href="mailto:{{ $teklif->ilan->danisman->email }}" class="block w-full rounded-xl bg-blue-50/50 px-4 py-2.5 text-center text-sm font-semibold text-blue-700 transition-colors hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50">
                        Danışmanla Görüş
                    </a>
                </div>
            </div>
        </div>
        @endif
        
    </div>
</div>
@endsection
