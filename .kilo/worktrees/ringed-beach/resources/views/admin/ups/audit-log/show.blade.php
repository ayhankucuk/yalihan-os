@extends('admin.layouts.admin')

@section('title', 'Audit Log Detay - ' . ($auditLog->template?->kategori?->name ?? 'Template'))

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-slate-900">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.ups.audit-log') }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                    ← Geri
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Audit Log Detay</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $auditLog->created_at->format('d.m.Y H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Ana Bilgiler -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 mb-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">📋 Değişim Özeti</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Template -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Şablon (Kategori)</label>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                        @if ($auditLog->template)
                            <a href="{{ route('admin.property-hub.templates.edit', ['kategori_id' => $auditLog->template->kategori_id, 'yayin_tipi_id' => $auditLog->template->id]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $auditLog->template->kategori->name ?? 'Bilinmeyen' }} ({{ $auditLog->template->name }})
                            </a>
                        @else
                            <span class="text-gray-500">— Global Değişiklik —</span>
                        @endif
                    </div>
                </div>

                <!-- User -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Değişim Yapan</label>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                         <span class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            {{ $auditLog->user?->name ?? 'Sistem' }}
                        </span>
                    </div>
                </div>

                <!-- Action -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">İşlem</label>
                    <div>
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                            {{ $auditLog->getActionLabel() }}
                        </span>
                    </div>
                </div>

                <!-- Timestamp -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Tarih & Saat</label>
                    <div class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        {{ $auditLog->created_at->format('d.m.Y H:i:s') }}
                    </div>
                </div>
            </div>

            <!-- Description -->
            @if ($auditLog->aciklama)
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Açıklama</label>
                    <div class="text-gray-700 dark:text-slate-200 bg-gray-50 dark:bg-gray-700 rounded p-3 dark:bg-slate-900 dark:text-slate-300">
                        {{ $auditLog->aciklama }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Değişen Veriler -->
        @if ($auditLog->eski_degerler || $auditLog->yeni_degerler)
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 mb-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">🔄 Değişim Detayı</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Eski Değer -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">❌ Eski Durum</h3>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded p-4 border border-red-200 dark:border-red-800">
                            @if ($auditLog->eski_degerler)
                                <pre class="text-xs text-gray-800 dark:text-slate-200 overflow-auto max-h-64"><code>{{ json_encode($auditLog->eski_degerler, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            @else
                                <p class="text-sm text-gray-600 dark:text-gray-400 text-center py-4 italic">Veri yok</p>
                            @endif
                        </div>
                    </div>

                    <!-- Yeni Değer -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">✅ Yeni Durum</h3>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded p-4 border border-green-200 dark:border-green-800">
                            @if ($auditLog->yeni_degerler)
                                <pre class="text-xs text-gray-800 dark:text-slate-200 overflow-auto max-h-64"><code>{{ json_encode($auditLog->yeni_degerler, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            @else
                                <p class="text-sm text-gray-600 dark:text-gray-400 text-center py-4 italic">Veri yok</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- User Agent -->
        @if ($auditLog->user_agent)
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">🌐 Tarayıcı Bilgisi</h2>

                <div class="bg-gray-50 dark:bg-gray-700 rounded p-4 dark:bg-slate-900">
                    <p class="text-xs font-mono text-gray-700 dark:text-slate-200 break-all dark:text-slate-300">
                        {{ $auditLog->user_agent }}
                    </p>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="mt-8 flex gap-4">
            <a href="{{ route('admin.ups.audit-log.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 dark:bg-gray-700 dark:hover:bg-gray-600">
                ← Geri Dön
            </a>
            <form method="POST" action="{{ route('admin.ups.audit-log.destroy', $auditLog->id) }}" style="display: inline;" onsubmit="return confirm('Bu kaydı silmek istediğinize emin misiniz?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200 dark:bg-red-700 dark:hover:bg-red-600">
                    🗑️ Kaydı Sil
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
