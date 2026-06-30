@extends('admin.layouts.admin')

@section('title', 'Talep Analiz Detayı')

@section('content')
    <div class="px-4">
        <div class="flex flex-wrap -mx-4">
            <div class="w-full px-4 md:w-1/3">
                <div class="admin-card">
                    <div class="admin-p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                        <h3 class="admin-h3">Müşteri Talebi #{{ $talep->id }}</h3>
                    </div>
                    <div class="admin-card-body">
                        <h6>{{ $talep->kullanici->tam_ad }} - {{ $talep->kullanici->telefon }}</h6>
                        <hr>
                        <p><strong>Talep Türü:</strong> {{ $talep->tip }}</p>
                        <p><strong>Bölge:</strong> {{ $talep->il }} / {{ $talep->ilce }}</p>
                        <p><strong>Fiyat Aralığı:</strong>
                            @if ($talep->min_fiyat && $talep->max_fiyat)
                                {{ number_format($talep->min_fiyat) }} TL - {{ number_format($talep->max_fiyat) }} TL
                            @elseif($talep->max_fiyat)
                                Max: {{ number_format($talep->max_fiyat) }} TL
                            @elseif($talep->min_fiyat)
                                Min: {{ number_format($talep->min_fiyat) }} TL
                            @else
                                Belirtilmemiş
                            @endif
                        </p>
                        <p><strong>Oda Sayısı:</strong> {{ $talep->oda_sayisi ?: 'Belirtilmemiş' }}</p>
                        <p><strong>Metraj:</strong> {{ $talep->metraj ?: 'Belirtilmemiş' }} m²</p>
                        <p><strong>Ek Notlar:</strong> {{ $talep->notlar ?: 'Bulunmuyor' }}</p>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-2/3 px-4">
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h5 class="text-gray-900 dark:text-white font-semibold dark:text-slate-100">AI Eşleşme Analizi</h5>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @forelse($potansiyelEslesmeler as $eslesme)
                                @php
                                    $eslesmeClass = '';
                                    if ($eslesme['eslesme_yuzdesi'] >= 80) {
                                        $eslesmeClass = 'ai-match-high';
                                    } elseif ($eslesme['eslesme_yuzdesi'] >= 50) {
                                        $eslesmeClass = 'ai-match-medium';
                                    } else {
                                        $eslesmeClass = 'ai-match-low';
                                    }
                                @endphp
                                <div>
                                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow border border-gray-200 dark:border-slate-800 {{ $eslesmeClass }} dark:shadow-none dark:border-slate-700">
                                        <div class="p-4">
                                            <h6>{{ $eslesme['emlak']->baslik }}</h6>
                                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mb-2">
                                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $eslesme['eslesme_yuzdesi'] }}%"></div>
                                            </div>
                                            <div class="text-sm text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">{{ number_format($eslesme['eslesme_yuzdesi']) }}%</div>
                                            <p><strong>Fiyat:</strong> {{ number_format($eslesme['emlak']->fiyat) }} TL</p>
                                            <p><strong>Konum:</strong> {{ $eslesme['emlak']->il }} /
                                                {{ $eslesme['emlak']->ilce }}</p>
                                            <p><strong>Özellikler:</strong> {{ $eslesme['emlak']->oda_sayisi }} Oda,
                                                {{ $eslesme['emlak']->metraj }} m²</p>
                                            <a href="{{ route('admin.emlaklar.show', $eslesme['emlak']->id) }}"
                                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none">Detaylar</a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="px-4 py-3 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-800 dark:text-yellow-200">
                                        Bu talep için uygun eşleşme bulunamadı.
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
