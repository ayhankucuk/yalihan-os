@extends('admin.layouts.admin')

@section('title', 'Projeler')

@section('content')
    <div class="container mx-auto px-4 py-6">
        {{-- Başlık ve Aksiyon --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Projeler</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tüm projeleri yönetin</p>
            </div>
            <a href="{{ route('admin.takim.projeler.create') }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 hover:scale-105 active:scale-95">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Yeni Proje
            </a>
        </div>

        {{-- Proje Listesi --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden dark:shadow-none">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 dark:bg-slate-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Proje</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Sorumlu</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Durum</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Öncelik</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tarihler</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($projeler as $proje)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                                            <span
                                                class="text-white font-semibold text-sm">{{ strtoupper(substr($proje->proje_adi ?? 'P', 0, 2)) }}</span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                {{ $proje->proje_adi ?? '—' }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ Str::limit($proje->aciklama ?? '', 40) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $proje->user->name ?? '—' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $proje->takim->takim_adi ?? 'Takım yok' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'planlama' =>
                                                'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
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
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$proje->status] ?? $statusColors['planlama'] }}">
                                        {{ $statusLabels[$proje->status] ?? 'Bilinmiyor' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $oncelikColors = [
                                            'dusuk' => 'text-gray-500 dark:text-gray-400',
                                            'orta' => 'text-blue-500 dark:text-blue-400',
                                            'yuksek' => 'text-orange-500 dark:text-orange-400',
                                            'kritik' => 'text-red-500 dark:text-red-400',
                                        ];
                                        $oncelikLabels = [
                                            'dusuk' => 'Düşük',
                                            'orta' => 'Orta',
                                            'yuksek' => 'Yüksek',
                                            'kritik' => 'Kritik',
                                        ];
                                    @endphp
                                    <span
                                        class="text-sm font-medium {{ $oncelikColors[$proje->oncelik] ?? 'text-gray-500' }}">
                                        {{ $oncelikLabels[$proje->oncelik] ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div>
                                        {{ $proje->baslangic_tarihi ? \Carbon\Carbon::parse($proje->baslangic_tarihi)->format('d.m.Y') : '—' }}
                                    </div>
                                    <div class="text-xs">
                                        {{ $proje->bitis_tarihi ? \Carbon\Carbon::parse($proje->bitis_tarihi)->format('d.m.Y') : 'Belirsiz' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.takim.projeler.show', $proje) }}"
                                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors duration-150"
                                            title="Görüntüle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.takim.projeler.edit', $proje) }}"
                                            class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-150"
                                            title="Düzenle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.takim.projeler.destroy', $proje) }}" method="POST"
                                            class="inline"
                                            onsubmit="return confirm('Bu projeyi silmek istediğinize emin misiniz?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-150"
                                                title="Sil">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-4" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400">Henüz proje bulunmuyor</p>
                                        <a href="{{ route('admin.takim.projeler.create') }}"
                                            class="mt-3 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                            İlk projeyi oluştur →
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($projeler->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    {{ $projeler->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
