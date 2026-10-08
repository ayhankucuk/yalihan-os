@extends('admin.layouts.admin')

@section('title', 'n8n Workflow Yönetimi')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-3 dark:text-slate-100">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        n8n Workflow Yönetimi
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Otomatik iş akışlarını yönetin ve izleyin</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.integrations.index') }}"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-sm hover:shadow-md dark:shadow-none dark:text-slate-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Entegrasyonlar
                    </a>
                </div>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if (session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-lg shadow-sm dark:shadow-none">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-green-800 dark:text-green-200 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        {{-- Workflow Cards --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($workflows as $key => $workflow)
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-gray-700 dark:bg-slate-900 dark:shadow-slate-900/50">
                    <div class="p-6">
                        {{-- Header --}}
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
                                {{ $workflow['name'] }}
                            </h3>
                            <span class="{{ $workflow['aktiflik_durumu'] === 'aktif'
                                ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} rounded-full px-3 py-1 text-xs font-medium">
                                {{ ($workflow['aktiflik_durumu'] ?? '') === 'aktif' ? '✓ Aktif' : '○ Pasif' }}
                            </span>
                        </div>

                        {{-- Description --}}
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $workflow['aciklama'] }}
                        </p>

                        {{-- Stats --}}
                        <div class="mb-4 space-y-2 border-b border-gray-200 pb-4 dark:border-slate-700">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Tetiklenme:</span>
                                <span class="font-semibold text-gray-900 dark:text-slate-100">{{ $workflow['trigger_count'] }}</span>
                            </div>
                            @if (isset($workflow['last_triggered']))
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Son Çalışma:</span>
                                    <span class="text-xs text-gray-900 dark:text-slate-100">{{ $workflow['last_triggered'] }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2">
                            <button class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors duration-200 dark:bg-indigo-700 dark:hover:bg-indigo-600">
                                Detaylar
                            </button>
                            <button class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                Test Et
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Info Box - Standart Alert Yapısı --}}
        <div class="mt-8 rounded-xl border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800/50 p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400 dark:text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200">
                        n8n Webhook Endpoint
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <p class="mb-3">Laravel'den n8n'e webhook göndermek için aşağıdaki endpoint'i n8n workflow'larınızda kullanın:</p>
                        <code class="block rounded-lg bg-white/50 dark:bg-slate-800/50 px-4 py-3 text-sm font-mono text-gray-800 dark:text-slate-200 border border-blue-100 dark:border-blue-800">
                            {{ config('n8n.webhook_url', 'https://n8n.example.com/webhook/yalihan') }}
                        </code>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
