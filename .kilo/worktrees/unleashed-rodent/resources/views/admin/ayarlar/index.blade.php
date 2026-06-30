@extends('admin.layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">⚙️ Sistem Ayarları</h1>
            <a href="{{ route('admin.ayarlar.create') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 touch-target-optimized dark:shadow-none">
                Yeni Ayar Ekle
            </a>
        </div>

        @if (session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/30 rounded-lg text-green-800 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        @foreach ($settings as $group => $groupSettings)
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-6 dark:shadow-none dark:border-slate-700">
                <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ ucfirst($group) }}</h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Anahtar</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Değer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tip</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Açıklama</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($groupSettings as $setting)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                        <td class="px-6 py-4"><code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-slate-200 rounded text-sm font-mono dark:bg-slate-900">{{ $setting->key }}</code></td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                            @if (strlen($setting->value ?? '') > 50)
                                                {{ substr($setting->value, 0, 50) }}...
                                            @else
                                                {{ $setting->value ?? '-' }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ $setting->type }}</span></td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $setting->description ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('admin.ayarlar.edit', $setting->id) }}"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm touch-target-optimized dark:shadow-none dark:text-slate-300">
                                                Düzenle
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                            Bu grupta ayar bulunamadı
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
