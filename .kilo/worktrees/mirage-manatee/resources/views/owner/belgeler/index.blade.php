@extends('layouts.owner')

@section('title', 'Belgelerim')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Belgelerim</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">İlanlarınıza ve hesabınıza ait resmi evrakları, sözleşmeleri ve faturaları buradan yönetebilirsiniz.</p>
</div>

@if($belgeler->isEmpty())
    <div class="rounded-xl border border-gray-200 bg-white p-12 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-slate-700">
            <svg class="h-8 w-8 text-gray-400 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        </div>
        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Henüz belge bulunmuyor</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">Hesabınıza atanmış veya ilanlarınızla ilişkili herhangi bir belge henüz yüklenmemiş.</p>
    </div>
@else
    {{-- Tüm Belgeler Izgarası --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach($belgeler as $belge)
            <div class="group relative flex flex-col justify-between rounded-2xl border border-gray-200/60 bg-white/80 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/10 dark:border-slate-700/50 dark:bg-slate-800/80">
                
                {{-- Belge İkonu & Türü --}}
                <div class="flex items-start justify-between p-5 pb-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg 
                        @if($belge->dosya_tipi === 'pdf') bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400
                        @elseif(in_array($belge->dosya_tipi, ['doc', 'docx'])) bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400
                        @elseif(in_array($belge->dosya_tipi, ['jpg', 'jpeg', 'png'])) bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400
                        @else bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 @endif">
                        
                        @if($belge->dosya_tipi === 'pdf')
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 3v5h5M9 13v4m-2-2h4" /></svg>
                        @elseif(in_array($belge->dosya_tipi, ['doc', 'docx']))
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        @elseif(in_array($belge->dosya_tipi, ['jpg', 'jpeg', 'png']))
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        @else
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                        @endif
                    </div>
                    
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                        @if($belge->belge_turu === 'sözleşme') bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                        @elseif($belge->belge_turu === 'tapu') bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                        @elseif($belge->belge_turu === 'fatura') bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400
                        @else bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 @endif">
                        {{ ucfirst($belge->belge_turu) }}
                    </span>
                </div>

                {{-- Belge Detayları --}}
                <div class="px-5 pb-5">
                    <h3 class="line-clamp-2 text-sm font-semibold text-gray-900 dark:text-white" title="{{ $belge->baslik }}">
                        {{ $belge->baslik }}
                    </h3>
                    
                    @if($belge->ilan)
                        <p class="mt-1 line-clamp-1 text-xs text-blue-600 dark:text-blue-400">
                            İlan: {{ $belge->ilan->baslik }}
                        </p>
                    @endif

                    <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 dark:text-slate-400">
                        <span class="flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            {{ $belge->created_at->format('d.m.Y') }}
                        </span>
                        <span>{{ number_format($belge->boyut_kb / 1024, 2) }} MB</span>
                    </div>
                </div>

                {{-- İndir Butonu --}}
                <div class="mt-auto border-t border-gray-100/60 bg-gray-50/50 px-5 py-4 dark:border-slate-700/50 dark:bg-slate-800/50">
                    <a href="{{ route('owner.belgeler.download', $belge->id) }}" 
                       class="flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition-all hover:bg-blue-50 hover:text-blue-700 hover:ring-blue-300 dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-600 dark:hover:bg-slate-600 dark:hover:text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        İndir
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
