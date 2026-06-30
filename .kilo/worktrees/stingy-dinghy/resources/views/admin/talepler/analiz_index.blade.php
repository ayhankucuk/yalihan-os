@extends('admin.layouts.admin')

@section('title', 'Talep Analizi')

@section('content_header')
    <div class="flex justify-between items-center">
        <h1 class="admin-h1">AI Talep Analizi</h1>
        <form action="{{ route('admin.talep-analiz.toplu') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg transition-all duration-200 hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-md hover:shadow-lg touch-target-optimized dark:bg-blue-700 dark:hover:bg-blue-800 disabled:opacity-60 disabled:cursor-not-allowed dark:shadow-none"
                :disabled="loading">
                <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>
                <span x-show="!loading">Toplu Analiz</span>
                <span x-show="loading">İşleniyor...</span>
            </button>
        </form>
    </div>
@endsection

@section('content')
    <div class="admin-card">
        <div class="p-6">
            <!-- Başarı Mesajı -->
            @if (session('success'))
                <div class="admin-alert admin-alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Hata Mesajı -->
            @if (session('error'))
                <div class="admin-alert admin-alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Müşteri</th>
                            <th>Talep Tipi</th>
                            <th>Bölge</th>
                            <th>Fiyat Aralığı</th>
                            <th>Tarih</th>
                            <th class="text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($talepler as $talep)
                            <tr>
                                <td>{{ $talep->id }}</td>
                                <td class="font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ $talep->kullanici->tam_ad }}</td>
                                <td>{{ $talep->tip }}</td>
                                <td>{{ $talep->il }} / {{ $talep->ilce }}</td>
                                <td>
                                    @if ($talep->min_fiyat && $talep->max_fiyat)
                                        {{ number_format($talep->min_fiyat) }} TL -
                                        {{ number_format($talep->max_fiyat) }} TL
                                    @elseif($talep->max_fiyat)
                                        Max: {{ number_format($talep->max_fiyat) }} TL
                                    @elseif($talep->min_fiyat)
                                        Min: {{ number_format($talep->min_fiyat) }} TL
                                    @else
                                        Belirtilmemiş
                                    @endif
                                </td>
                                <td>{{ $talep->created_at->format('d.m.Y') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.talepler.analiz.show', $talep->id) }}"
                                        class="admin-action-view">
                                        <i class="fas fa-chart-bar mr-1"></i> Analiz Et
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Henüz analiz edilecek talep bulunmuyor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
