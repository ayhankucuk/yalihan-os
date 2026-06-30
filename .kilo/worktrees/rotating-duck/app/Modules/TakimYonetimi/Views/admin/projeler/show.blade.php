@extends('admin.layouts.admin')

@section('title', $proje->proje_adi ?? 'Proje Detayı')

@section('content')
    <div class="container mx-auto px-4 py-6">
        {{-- Başlık --}}
        <div class="mb-6">
            <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('admin.takim.projeler.index') }}"
                    class="hover:text-gray-700 dark:hover:text-gray-200">Projeler</a>
                <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
                <span>{{ $proje->proje_adi ?? 'Proje' }}</span>
            </nav>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $proje->proje_adi ?? '—' }}</h1>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.takim.projeler.edit', $proje) }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Düzenle
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Proje Bilgileri --}}
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 dark:shadow-none">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Proje Bilgileri</h2>

                <div class="space-y-4">
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Açıklama</span>
                        <p class="text-gray-900 dark:text-white mt-1 dark:text-slate-100">{{ $proje->aciklama ?? 'Açıklama eklenmemiş' }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Başlangıç Tarihi</span>
                            <p class="text-gray-900 dark:text-white mt-1 dark:text-slate-100">
                                {{ $proje->baslangic_tarihi ? \Carbon\Carbon::parse($proje->baslangic_tarihi)->format('d.m.Y') : '—' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Bitiş Tarihi</span>
                            <p class="text-gray-900 dark:text-white mt-1 dark:text-slate-100">
                                {{ $proje->bitis_tarihi ? \Carbon\Carbon::parse($proje->bitis_tarihi)->format('d.m.Y') : 'Belirsiz' }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Durum</span>
                            @php
                                $statusColors = [
                                    'planlama' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    'devam_ediyor' =>
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    'tamamlandi' =>
                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    'iptal' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    'beklemede' =>
                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                ];
                                $statusLabels = [
                                    'planlama' => 'Planlama',
                                    'devam_ediyor' => 'Devam Ediyor',
                                    'tamamlandi' => 'Tamamlandı',
                                    'iptal' => 'İptal',
                                    'beklemede' => 'Beklemede',
                                ];
                            @endphp
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1 {{ $statusColors[$proje->status] ?? $statusColors['planlama'] }}">
                                {{ $statusLabels[$proje->status] ?? 'Bilinmiyor' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Öncelik</span>
                            @php
                                $oncelikLabels = [
                                    'dusuk' => 'Düşük',
                                    'orta' => 'Orta',
                                    'yuksek' => 'Yüksek',
                                    'kritik' => 'Kritik',
                                ];
                            @endphp
                            <p class="text-gray-900 dark:text-white mt-1 dark:text-slate-100">{{ $oncelikLabels[$proje->oncelik] ?? '—' }}</p>
                        </div>
                    </div>

                    @if ($proje->budget)
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Bütçe</span>
                            <p class="text-gray-900 dark:text-white mt-1 dark:text-slate-100">₺{{ number_format($proje->budget, 2, ',', '.') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sağ Panel --}}
            <div class="space-y-6">
                {{-- Sorumlu --}}
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 dark:shadow-none">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Proje Sorumlusu</h3>
                    <div class="flex items-center">
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                            <span
                                class="text-white font-semibold text-sm">{{ strtoupper(substr($proje->user->name ?? 'U', 0, 2)) }}</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-gray-900 dark:text-white font-medium dark:text-slate-100">{{ $proje->user->name ?? '—' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $proje->user->email ?? '' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Takım --}}
                @if ($proje->takim)
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 dark:shadow-none">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Takım</h3>
                        <p class="text-gray-900 dark:text-white font-medium dark:text-slate-100">{{ $proje->takim->takim_adi ?? '—' }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Görevler --}}
        @if ($proje->gorevler && $proje->gorevler->count() > 0)
            <div class="mt-6 bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 dark:shadow-none">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Proje Görevleri
                    ({{ $proje->gorevler->count() }})</h2>
                <div class="space-y-3">
                    @foreach ($proje->gorevler as $gorev)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg dark:bg-slate-900">
                            <div>
                                <p class="text-gray-900 dark:text-white font-medium dark:text-slate-100">{{ $gorev->baslik ?? '—' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $gorev->user->name ?? 'Atanmamış' }}
                                </p>
                            </div>
                            <span
                                class="text-sm text-gray-500 dark:text-gray-400">{{ $gorev->status_etiketi ?? ($gorev->islem_statusu ?? '—') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
