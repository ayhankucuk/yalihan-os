@extends('admin.layouts.admin')

@section('title', 'n8n Workflow Yönetimi')

@section('content')
    <div class="container-fluid px-4 py-6">
        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="mb-2 text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                    🔄 n8n Workflow Yönetimi
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Otomatik iş akışlarını yönetin ve izleyin
                </p>
            </div>
            <a href="{{ route('admin.integrations.index') }}"
                class="rounded-lg bg-gray-600 px-4 py-2 text-white transition-all duration-200 ease-in-out hover:scale-105 hover:bg-gray-700 active:scale-95">
                ← Geri Dön
            </a>
        </div>

        {{-- Workflow Cards --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($workflows as $key => $workflow)
                <div
                    class="rounded-lg border border-gray-200 bg-white shadow-sm transition-all duration-200 ease-in-out hover:scale-105 hover:shadow-lg active:scale-95 dark:border-gray-700 dark:bg-gray-800">
                    <div class="p-6">
                        {{-- Header --}}
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ $workflow['name'] }}
                            </h3>
                            <span
                                class="{{ $workflow['aktiflik_durumu'] === 'aktif'
                                    ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }} rounded-full px-3 py-1 text-xs font-medium transition-all duration-200">
                                {{ ($workflow['aktiflik_durumu'] ?? '') === 'aktif' ? '✓ Aktif' : '○ Pasif' }}
                            </span>
                        </div>

                        {{-- Description --}}
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $workflow['description'] }}
                        </p>

                        {{-- Stats --}}
                        <div
                            class="mb-4 space-y-2 border-b border-gray-200 pb-4 dark:border-slate-700 dark:border-slate-800">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Tetiklenme:</span>
                                <span
                                    class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $workflow['trigger_count'] }}</span>
                            </div>
                            @if (isset($workflow['last_triggered']))
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Son Çalışma:</span>
                                    <span
                                        class="text-xs text-gray-900 dark:text-slate-100 dark:text-white">{{ $workflow['last_triggered'] }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center space-x-2">
                            <button
                                class="flex-1 rounded bg-blue-600 px-3 py-2 text-sm font-medium text-white transition-all duration-200 ease-in-out hover:scale-105 hover:bg-blue-700 active:scale-95">
                                Detaylar
                            </button>
                            <button
                                class="rounded bg-gray-600 px-3 py-2 text-sm font-medium text-white transition-all duration-200 ease-in-out hover:scale-105 hover:bg-gray-700 active:scale-95">
                                Test Et
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Info Box --}}
        <div
            class="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-6 transition-all duration-200 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                ℹ️ n8n Webhook Endpoint
            </h3>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                Laravel'den n8n'e webhook göndermek için aşağıdaki endpoint'i n8n workflow'larınızda kullanın:
            </p>
            <code class="block rounded bg-gray-100 px-4 py-2 text-sm text-gray-800 dark:bg-slate-900 dark:text-slate-200">
                {{ config('n8n.webhook_url', 'https://n8n.example.com/webhook/yalihan') }}
            </code>
        </div>
    </div>
@endsection
