@extends('admin.layouts.admin')

@section('title', 'Template Tarihçesi')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold mb-0">📋 Template Değişim Tarihçesi</h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                    {{ $template->kategori->name ?? 'Template' }} → {{ $template->yayin_tipi ?? '' }}
                </p>
            </div>
            @isset($template)
                <a href="{{ route('admin.ups.templates.edit', ['kategori_id' => $template->kategori_id, 'yayin_tipi_id' => $template->id]) }}"
                   class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-all duration-200">
                    ← Düzenlenmeye Dön
                </a>
            @endisset
        </div>
    </div>

    @if(!isset($template))
        <!-- No Template Selected -->
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm dark:shadow-none dark:border-slate-700">
            <div class="p-8 text-center">
                <div class="text-4xl text-gray-400 dark:text-gray-500 mb-4">📭</div>
                <h5 class="text-gray-500 dark:text-gray-400 font-medium">Template Seçimi Zorunludur</h5>
                <i class="fas fa-inbox text-muted" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                <h5 class="text-muted">Template Seçimi Zorunludur</h5>
                <p class="text-muted small mb-0">
                    <a href="{{ route('admin.ups.templates.index') }}">Edit sayfasına dön</a> ve bir template seçin.
                </p>
            </div>
        </div>
    @else
        <!-- Changelog Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-200 dark:border-slate-800 overflow-hidden dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 dark:bg-slate-900 dark:border-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Tarih</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Aksiyon</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Özellik</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Kullanıcı</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Sürüm</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Detaylar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changelog as $log)
                            <tr class="border-b border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition dark:border-slate-700">
                                <td class="px-4 py-3 text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">
                                        {{ $log->created_at->format('d.m.Y H:i') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @switch($log->aksiyon_tipi)
                                        @case('feature_added')
                                            <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100 rounded-md text-sm font-medium">✅ Eklendi</span>
                                            @break
                                        @case('feature_removed')
                                            <span class="inline-block px-3 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-100 rounded-md text-sm font-medium">❌ Kaldırıldı</span>
                                            @break
                                        @case('feature_reordered')
                                            <span class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-100 rounded-md text-sm font-medium">🔄 Sıralandı</span>
                                            @break
                                        @case('template_exported')
                                            <span class="inline-block px-3 py-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-100 rounded-md text-sm font-medium">📥 İndirme</span>
                                            @break
                                        @case('template_imported')
                                            <span class="inline-block px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-100 rounded-md text-sm font-medium">📤 Yükleme</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="px-4 py-3">
                                    @if($log->feature)
                                        <span class="font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                            {{ $log->feature->name }}
                                        </span>
                                        <br>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $log->feature->slug }}</span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($log->user)
                                        <span class="font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $log->user->name }}</span>
                                        <br>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $log->user->email }}</span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">Sistem</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded text-xs font-medium dark:bg-slate-900 dark:text-slate-100">v{{ $log->versiyon_numarasi }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($log->yeni_degerler || $log->eski_degerler)
                                        <button class="px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-slate-200 rounded text-sm transition-colors" type="button"
                                                onclick="toggleDetails('details-{{ $log->id }}')">
                                            📄 Göster
                                        </button>
                                        <div class="hidden mt-2" id="details-{{ $log->id }}">
                                            <div class="bg-gray-50 dark:bg-slate-900 p-3 rounded border border-gray-200 dark:border-slate-800 text-sm dark:border-slate-700">
                                                @if($log->eski_degerler)
                                                    <div class="mb-2">
                                                        <strong class="text-red-600 dark:text-red-400">Eski Değerler:</strong>
                                                        <pre class="bg-gray-100 dark:bg-slate-900 p-2 rounded text-xs overflow-x-auto mt-1">{{ json_encode($log->eski_degerler, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    </div>
                                                @endif
                                                @if($log->yeni_degerler)
                                                    <div>
                                                        <strong class="text-green-600 dark:text-green-400">Yeni Değerler:</strong>
                                                        <pre class="bg-gray-100 dark:bg-slate-900 p-2 rounded text-xs overflow-x-auto mt-1">{{ json_encode($log->yeni_degerler, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    📭 Henüz hiç değişim kaydı yok
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if(isset($changelog) && $changelog->hasPages())
            <div class="mt-4 flex justify-center">
                {{ $changelog->links() }}
            </div>
        @endif

        <!-- Stats -->
        @if(isset($changelog))
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="text-center">
                        <div class="text-3xl mb-2">✅</div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Eklenen Özellikler</p>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $changelog->where('aksiyon_tipi', 'feature_added')->count() }}
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="text-center">
                        <div class="text-3xl mb-2">❌</div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Kaldırılan Özellikler</p>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $changelog->where('aksiyon_tipi', 'feature_removed')->count() }}
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="text-center">
                        <div class="text-3xl mb-2">📥</div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">İndirmeler</p>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $changelog->where('aksiyon_tipi', 'template_exported')->count() }}
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="text-center">
                        <div class="text-3xl mb-2">📤</div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Yüklemeler</p>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $changelog->where('aksiyon_tipi', 'template_imported')->count() }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

<script>
function toggleDetails(elementId) {
    const element = document.getElementById(elementId);
    element.classList.toggle('hidden');
}
</script>
@endsection
