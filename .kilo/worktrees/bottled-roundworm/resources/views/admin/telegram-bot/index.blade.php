@extends('admin.layouts.admin')

@section('title', 'Telegram Bot Yönetimi')

@section('content')
    <!-- Header Section -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="stat-card-value flex items-center">
                    <svg class="mr-3 h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                        </path>
                    </svg>
                    Telegram Bot Yönetimi
                </h1>
                <p class="mt-1 text-sm text-gray-600">Bot durumu, webhook ayarları ve takım entegrasyonu</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="durum-badge active">
                    <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    Aktif
                </span>
            </div>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Bot Status Card -->
        <div
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Bot Durumu</h3>
                <div class="rounded-lg bg-blue-100 p-2">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="mb-4 text-sm text-gray-600">Telegram bot bağlantı durumu ve bilgileri</p>
            <div class="space-y-2">
                <a href="{{ route('admin.telegram-bot.get-aktiflik-durumu') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium leading-4 text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-slate-900 dark:text-slate-300 dark:shadow-none">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Durumu Gör
                </a>
                <a href="{{ route('admin.telegram-bot.webhook-info') }}"
                    class="ml-2 inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium leading-4 text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-slate-900 dark:text-slate-300 dark:shadow-none">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                    Webhook Bilgisi
                </a>
            </div>
        </div>

        <!-- Actions Card -->
        <div
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Eylemler</h3>
                <div class="rounded-lg bg-green-100 p-2">
                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4">
                        </path>
                    </svg>
                </div>
            </div>
            <p class="mb-4 text-sm text-gray-600">Webhook ayarlama ve test mesajı gönderme</p>
            <div class="space-y-3">
                <form method="POST" action="{{ route('admin.telegram-bot.set-webhook') }}" class="inline-block">
                    @csrf
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 font-medium text-white shadow-md transition-all duration-200 hover:scale-105 hover:bg-blue-700 hover:shadow-lg focus:ring-2 focus:ring-blue-500 active:scale-95 dark:shadow-none">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                            </path>
                        </svg>
                        Webhook Ayarla
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.telegram-bot.send-test') }}" class="inline-block">
                    @csrf
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 font-medium text-white shadow-md transition-all duration-200 hover:scale-105 hover:bg-green-700 hover:shadow-lg focus:ring-2 focus:ring-green-500 active:scale-95 dark:shadow-none">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Test Mesajı Gönder
                    </button>
                </form>
            </div>
        </div>

        <!-- Team Integration Card -->
        <div
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Takım Entegrasyonu</h3>
                <div class="rounded-lg bg-purple-100 p-2">
                    <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                </div>
            </div>
            <p class="mb-4 text-sm text-gray-600">Takım-Telegram kanal eşlemesi</p>
            <div class="text-sm text-gray-500">
                <p>Takım ID: <span
                        class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $teamId ?? 'Belirtilmemiş' }}</span>
                </p>
                <p>Kanal ID: <span
                        class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $teamChannelId ?? 'Belirtilmemiş' }}</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Team-Telegram Mapping Form -->
    <div
        class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
        <div class="mb-6 flex items-center">
            <div class="mr-3 rounded-lg bg-indigo-100 p-2">
                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                    </path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Takım-Telegram Eşlemesi
                </h2>
                <p class="text-sm text-gray-600">Takım ve Telegram kanal ID'lerini eşleştirin</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.telegram-bot.update-settings') }}" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        <svg class="mr-1 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        Takım ID
                    </label>
                    <input type="number" name="team_id"
                        class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:shadow-none sm:text-sm"
                        placeholder="1" value="{{ $teamId ?? '' }}" required>
                    <p class="mt-1 text-xs text-gray-500">Sistemdeki takım numarası</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        <svg class="mr-1 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                        Telegram Kanal ID
                    </label>
                    <input type="text" name="telegram_channel_id"
                        class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:shadow-none sm:text-sm"
                        placeholder="-1001234567890" value="{{ $teamChannelId ?? '' }}" required>
                    <p class="mt-1 text-xs text-gray-500">Telegram kanal veya grup ID'si</p>
                </div>
            </div>

            <!-- Info Box -->
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Telegram Kanal ID Nasıl Alınır?</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ol class="list-inside list-decimal space-y-1">
                                <li>Telegram'da kanalınızı oluşturun</li>
                                <li>@username ekleyin (örn: @YalihanTeam)</li>
                                <li>Bot'u kanala admin olarak ekleyin</li>
                                <li>Kanal ID'si genellikle -100 ile başlar</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="touch-target-optimized inline-flex items-center justify-center gap-2 rounded-lg border border-transparent bg-blue-600 px-4 px-6 py-2.5 py-3 text-base font-medium text-white shadow-md transition-all duration-200 hover:scale-105 hover:bg-blue-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-95">
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                        </path>
                    </svg>
                    Ayarları Kaydet
                </button>
            </div>
        </form>
    </div>

    <!-- Telegram Eşleştirme Kartı -->
    <div
        class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
        <div class="mb-6 flex items-center">
            <div class="mr-3 rounded-lg bg-green-100 p-2">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                    </path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Telegram Hesap
                    Eşleştirme</h2>
                <p class="text-sm text-gray-600">Telegram botunuza hesabınızı bağlamak için eşleştirme kodu oluşturun</p>
            </div>
        </div>

        <div class="space-y-4">
            @php
                $currentUser = auth()->user();
                $hasPairingCode = $currentUser && $currentUser->telegram_pairing_code;
                $isPaired = $currentUser && $currentUser->telegram_id && $currentUser->telegram_paired_at;
            @endphp

            @if ($isPaired)
                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="flex items-center">
                        <svg class="mr-2 h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-green-800">✅ Telegram hesabınız eşleştirilmiş</p>
                            <p class="mt-1 text-xs text-green-700">Eşleştirme tarihi:
                                {{ $currentUser->telegram_paired_at->format('d.m.Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            @elseif($hasPairingCode)
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-yellow-800">⏳ Eşleştirme kodu oluşturuldu</p>
                            <p class="mt-1 text-xs text-yellow-700">Bu kodu Telegram botuna gönderin</p>
                        </div>
                        <div class="text-right">
                            <div class="font-mono text-2xl font-bold text-yellow-900">
                                {{ $currentUser->telegram_pairing_code }}</div>
                            <p class="mt-1 text-xs text-yellow-600">6 haneli kod</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                    <p class="mb-4 text-sm text-gray-600">Telegram botunuza hesabınızı bağlamak için eşleştirme kodu
                        oluşturun.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.telegram-bot.generate-pairing-code') }}" class="inline-block">
                @csrf
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 font-medium text-white shadow-md transition-all duration-200 hover:scale-105 hover:bg-indigo-700 hover:shadow-lg focus:ring-2 focus:ring-indigo-500 active:scale-95 dark:shadow-none">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6">
                        </path>
                    </svg>
                    {{ $hasPairingCode ? 'Yeni Kod Oluştur' : 'Eşleştirme Kodu Oluştur' }}
                </button>
            </form>

            @if ($hasPairingCode)
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <h3 class="mb-2 text-sm font-medium text-blue-800">📋 Nasıl Kullanılır?</h3>
                    <ol class="list-inside list-decimal space-y-1 text-xs text-blue-700">
                        <li>Telegram'da <strong>@YalihanCortex_Bot</strong> botunu açın</li>
                        <li><strong>/start</strong> komutunu gönderin</li>
                        <li>Bot size eşleştirme kodu isteyecek</li>
                        <li>Yukarıdaki <strong>{{ $currentUser->telegram_pairing_code }}</strong> kodunu gönderin</li>
                        <li>✅ Eşleştirme tamamlandığında bildirim alacaksınız</li>
                    </ol>
                </div>
            @endif
        </div>
    </div>
@endsection
