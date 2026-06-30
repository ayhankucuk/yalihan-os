@extends('admin.layouts.admin')

@section('title', 'Kategorisiz Özellikler Listesi')

@section('content')
    <div
        class="rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-all duration-200 dark:shadow-none dark:border-slate-700">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-slate-200">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Kategorisiz Özellikler
                </h1>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.ozellikler.kategoriler.index') }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-slate-800 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-offset-gray-900 transition-all duration-200 touch-target-optimized dark:text-slate-300">
                        <i class="fas fa-tag mr-2"></i> Kategorilere Dön
                    </a>
                    <a href="{{ route('admin.ozellikler.create') }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-900 transition-all duration-200 shadow-md hover:shadow-lg touch-target-optimized dark:shadow-none">
                        <i class="fas fa-plus mr-2"></i> Yeni Özellik Ekle
                    </a>
                </div>
            </div>
            <!-- Açıklama Paneli -->
            <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Kategorisiz Özellikler</h3>
                        <div class="mt-1 text-sm text-yellow-700 dark:text-yellow-200">
                            <p>Aşağıdaki özellikler henüz bir kategoriye atanmamıştır. Özellikleri düzenleyerek bir
                                kategoriye atayabilirsiniz.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Özellikler Tablosu -->
            @if ($ozellikler->count() > 0)
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th scope="col"
                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    ID</th>
                                <th scope="col"
                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Özellik Grubu</th>
                                <th scope="col"
                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Özellik Adı</th>
                                <th scope="col"
                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Tür</th>
                                <th scope="col"
                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Durum</th>
                                <th scope="col"
                                    class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-50 dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($ozellikler as $ozellik)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $ozellik->id }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <x-neo.aktiflik-durumu-badge :value="$ozellik->category->translations->first()->name ?? 'Belirsiz Grup'" category="category" />
                                    </td>
                                    <td
                                        class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                        {{ $ozellik->translations->first() ? $ozellik->translations->first()->name : 'İsimsiz Özellik' }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @php
                                            $typeLabel = match ($ozellik->type) {
                                                'boolean' => 'Evet/Hayır',
                                                'text' => 'Metin',
                                                'number' => 'Sayı',
                                                'select' => 'Seçim',
                                                default => ucfirst($ozellik->type),
                                            };
                                        @endphp
                                        <x-neo.aktiflik-durumu-badge :value="$typeLabel" category="type" />
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <x-neo.aktiflik-durumu-badge :value="$ozellik->aktiflik_durumu ? 'Yayında' : 'Taslak'" />
                                    </td>
                                    <td
                                        class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                        <a href="{{ route('admin.ozellikler.edit', $ozellik->id) }}"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-2"
                                            title="Düzenle">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-neo.empty-state title="Harika! Tüm özellikler kategorilere atanmış"
                    description="Şu anda kategorisiz özellik bulunmamaktadır" />
            @endif
        </div>
    </div>
@endsection
