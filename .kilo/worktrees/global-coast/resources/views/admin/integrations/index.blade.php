@extends('admin.layouts.admin')

@section('title', 'AI Otomasyon & Entegrasyonlar')

@section('content')
    <div class="container-fluid px-4 py-6">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                🤖 AI Otomasyon & Entegrasyonlar
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Sistem entegrasyonlarını ve AI otomasyon araçlarını yönetin
            </p>
        </div>

        {{-- Integration Cards Grid --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($integrations as $key => $integration)
                <div
                    class="rounded-lg border border-gray-200 bg-white shadow-sm transition-all duration-200 ease-in-out hover:scale-105 hover:shadow-lg active:scale-95 dark:border-gray-700 dark:bg-gray-800">
                    <div class="p-6">
                        {{-- Icon & Title --}}
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="text-4xl">{{ $integration['icon'] }}</span>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $integration['name'] }}
                                </h3>
                            </div>

                            <span
                                class="{{ $integration['aktiflik_durumu'] === 'aktif'
                                    ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }} rounded-full px-3 py-1 text-xs font-medium transition-all duration-200">
                                {{ $integration['aktiflik_durumu'] === 'aktif' ? '✓ Aktif' : '○ Pasif' }}
                            </span>
                        </div>

                        {{-- Stats --}}
                        <div class="mb-4 space-y-2">
                            @if (isset($integration['endpoint']))
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Endpoint:</span>
                                    <code
                                        class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-800 dark:bg-slate-900 dark:text-slate-200">
                                        {{ $integration['endpoint'] }}
                                    </code>
                                </div>
                            @endif

                            @if (isset($integration['workflows']))
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Workflow'lar:</span>
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white">{{ $integration['workflows'] }}</span>
                                </div>
                            @endif

                            @if (isset($integration['commands']))
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Komutlar:</span>
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white">{{ $integration['commands'] }}</span>
                                </div>
                            @endif

                            @if (isset($integration['provider']))
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Provider:</span>
                                    <span
                                        class="font-semibold capitalize text-gray-900 dark:text-white">{{ $integration['provider'] }}</span>
                                </div>
                            @endif

                            @if (isset($integration['languages']))
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Diller:</span>
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white">{{ $integration['languages'] }}</span>
                                </div>
                            @endif

                            @if (isset($integration['channels']))
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Kanallar:</span>
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white">{{ $integration['channels'] }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center space-x-2">
                            @if ($key === 'n8n')
                                <a href="{{ route('admin.integrations.n8n-workflows') }}"
                                    class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-medium text-white transition-all duration-200 ease-in-out hover:scale-105 hover:bg-blue-700 active:scale-95">
                                    Workflow'ları Gör
                                </a>
                            @elseif($key === 'telegram')
                                <a href="#"
                                    class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-medium text-white transition-all duration-200 ease-in-out hover:scale-105 hover:bg-blue-700 active:scale-95">
                                    Bot Ayarları
                                </a>
                            @else
                                <button
                                    class="flex-1 rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 ease-in-out hover:scale-105 hover:bg-gray-700 active:scale-95">
                                    Ayarlar
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Quick Actions --}}
        <div
            class="mt-8 rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 transition-all duration-200 dark:border-gray-600 dark:from-gray-800 dark:to-gray-700">
            <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">⚡ Hızlı İşlemler</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <a href="{{ route('admin.integrations.n8n-workflows') }}"
                    class="flex items-center space-x-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 ease-in-out hover:scale-105 hover:shadow-md active:scale-95 dark:border-gray-700 dark:bg-gray-800">
                    <span class="text-2xl">🔄</span>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">n8n Workflow'ları</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Otomasyon kuralları</div>
                    </div>
                </a>

                <a href="#"
                    class="flex items-center space-x-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 ease-in-out hover:scale-105 hover:shadow-md active:scale-95 dark:border-gray-700 dark:bg-gray-800">
                    <span class="text-2xl">🎤</span>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">Voice Search Test</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Ses arama dene</div>
                    </div>
                </a>

                <a href="#"
                    class="flex items-center space-x-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 ease-in-out hover:scale-105 hover:shadow-md active:scale-95 dark:border-gray-700 dark:bg-gray-800">
                    <span class="text-2xl">🔔</span>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">Bildirim Ayarları</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Kanal yönetimi</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Documentation Link --}}
        <div class="mt-6 text-center">
            <a href="#"
                class="text-sm text-blue-600 transition-all duration-200 ease-in-out hover:scale-105 hover:underline dark:text-blue-400">
                📖 Entegrasyon Dokümantasyonu
            </a>
        </div>
    </div>
@endsection
