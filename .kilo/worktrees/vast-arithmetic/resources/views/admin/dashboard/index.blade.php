@extends('admin.layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sistem genel durumu ve istatistikler</p>
        </div>

        <!-- Stats Grid -->
        <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
            <!-- AI Kredisi -->
            <a href="{{ route('admin.ai.usage') }}"
                class="group overflow-hidden rounded-xl border-none bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg transition-all hover:scale-105 hover:shadow-indigo-500/20">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-50/20 backdrop-blur-sm dark:bg-slate-800/40">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-indigo-100">Cortex AI Kredisi</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-bold text-white">
                                        {{ number_format($quickStats['ai_balance'] ?? 0) }}
                                    </div>
                                    <span class="ml-2 text-xs text-indigo-100/70">Kredi</span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div
                    class="flex items-center justify-between bg-slate-50/10 px-5 py-2 text-xs text-indigo-50 transition-colors group-hover:bg-slate-50/20 dark:bg-slate-800/20 dark:group-hover:bg-slate-800/40">
                    <span>Usage & Billing</span>
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

            <!-- Toplam İlanlar -->
            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900">
                                <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H7a2 2 0 00-2-2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Toplam İlan</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ $quickStats['total_ilanlar'] }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aktif İlanlar -->
            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Aktif İlan</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ $quickStats['active_ilanlar'] }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toplam Kullanıcılar -->
            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Kullanıcı
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ $quickStats['total_kullanicilar'] }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toplam Danışmanlar -->
            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Danışman
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ $quickStats['total_danismanlar'] ?? 0 }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Opportunity Board & Exchange Rate Grid --}}
        <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @include('admin.dashboard.widgets.opportunity-board')
            </div>
            <div class="lg:col-span-1">
                <x-admin.exchange-rate-widget />
            </div>
        </div>

        {{-- 🎯 CORTEX ACTION CENTER - Daily Intelligence --}}
        <div class="mb-8" x-data="actionCenter()">
            <div
                class="overflow-hidden rounded-[2.5rem] bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 shadow-2xl">
                <div class="p-8">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50/20 backdrop-blur-sm dark:bg-slate-800/40">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black tracking-tight text-white">Cortex Günlük İstihbarat</h2>
                                <p class="text-sm font-medium text-white/80">Bugünkü öncelikli aksiyonlarınız</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span x-show="stats.total > 0"
                                class="rounded-xl bg-slate-50/20 px-4 py-2 font-bold text-white dark:bg-slate-800/40">
                                <span x-text="stats.total"></span> Aksiyon
                            </span>
                            <button @click="refreshActions()" :disabled="loading"
                                class="rounded-xl bg-slate-50/20 px-4 py-2 font-bold text-white transition-all hover:bg-slate-50/30 disabled:opacity-50 dark:bg-slate-800/40 dark:hover:bg-slate-800/60">
                                <svg class="h-5 w-5" :class="{ 'animate-spin': loading }" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582M20 20v-5h-.581M5.545 9A7.5 7.5 0 0119.5 12M18.455 15A7.5 7.5 0 014.5 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Stats Pills -->
                    <div class="mb-6 flex items-center gap-3">
                        <div class="rounded-xl bg-red-500/30 px-4 py-2 backdrop-blur-sm">
                            <span class="text-xs text-white/70">Churn Riski:</span>
                            <span class="ml-2 font-black text-white" x-text="stats.by_type?.churn || 0"></span>
                        </div>
                        <div class="rounded-xl bg-amber-500/30 px-4 py-2 backdrop-blur-sm">
                            <span class="text-xs text-white/70">Fiyat Fırsatı:</span>
                            <span class="ml-2 font-black text-white" x-text="stats.by_type?.pricing || 0"></span>
                        </div>
                        <div class="rounded-xl bg-emerald-500/30 px-4 py-2 backdrop-blur-sm">
                            <span class="text-xs text-white/70">Sıcak Eşleşme:</span>
                            <span class="ml-2 font-black text-white" x-text="stats.by_type?.match || 0"></span>
                        </div>
                    </div>

                    <!-- Actions List -->
                    <div class="rounded-2xl bg-slate-50/10 p-6 backdrop-blur-sm dark:bg-slate-800/40">
                        <template x-if="loading">
                            <div class="space-y-3">
                                <div class="h-16 animate-pulse rounded-xl bg-slate-50/10 dark:bg-slate-800/20"></div>
                                <div class="h-16 animate-pulse rounded-xl bg-slate-50/10 dark:bg-slate-800/20"></div>
                                <div class="h-16 animate-pulse rounded-xl bg-slate-50/10 dark:bg-slate-800/20"></div>
                            </div>
                        </template>

                        <template x-if="!loading && actions.length === 0">
                            <div class="py-12 text-center">
                                <svg class="mx-auto mb-4 h-16 w-16 text-white/50" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-xl font-bold text-white">Harika! Bugünlük acil bir durum yok. ☕</p>
                                <p class="mt-2 text-sm text-white/70">Cortex tüm ilanlarınızı ve talepleri izliyor.</p>
                            </div>
                        </template>

                        <template x-if="!loading && actions.length > 0">
                            <div class="space-y-3">
                                <template x-for="action in actions.slice(0, 5)" :key="action.ilan_id + action.type">
                                    <div
                                        class="flex items-center gap-4 rounded-xl bg-white p-4 transition-all hover:shadow-lg dark:bg-slate-900">
                                        <!-- Icon -->
                                        <div class="shrink-0">
                                            <div :class="{
                                                'bg-red-100 dark:bg-red-900/30': action.type === 'churn',
                                                'bg-amber-100 dark:bg-amber-900/30': action.type === 'pricing',
                                                'bg-emerald-100 dark:bg-emerald-900/30': action.type === 'match'
                                            }"
                                                class="flex h-12 w-12 items-center justify-center rounded-xl">
                                                <span
                                                    x-text="action.type === 'churn' ? '⚠️' : (action.type === 'pricing' ? '💰' : '🔥')"
                                                    class="text-2xl"></span>
                                            </div>
                                        </div>

                                        <!-- Content -->
                                        <div class="min-w-0 flex-1">
                                            <h4 class="truncate text-sm font-bold text-slate-800 dark:text-white"
                                                x-text="action.title"></h4>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                                                x-text="action.description"></p>
                                        </div>

                                        <!-- Priority Badge -->
                                        <div class="shrink-0 text-center">
                                            <div class="text-lg font-black"
                                                :class="{
                                                    'text-red-600 dark:text-red-400': action.priority >= 90,
                                                    'text-amber-600 dark:text-amber-400': action.priority >= 80 &&
                                                        action.priority < 90,
                                                    'text-emerald-600 dark:text-emerald-400': action.priority < 80
                                                }"
                                                x-text="Math.round(action.priority)"></div>
                                            <div class="text-[9px] font-bold uppercase text-slate-400">Öncelik</div>
                                        </div>

                                        <!-- Action Button -->
                                        <button @click="executeAction(action)"
                                            class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                            x-text="action.action_label">
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- AI Automation & Integrations Widget --}}
        <div class="mb-8">
            <div
                class="overflow-hidden rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 shadow-lg transition-all duration-200 hover:scale-[1.01] hover:shadow-xl">
                <div class="p-6">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-50/20 backdrop-blur-sm dark:bg-slate-800/40">
                                <svg class="h-6 w-6 text-white dark:text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white dark:text-white">AI Otomasyon Sistemi</h2>
                                <p class="text-sm text-white/80 dark:text-white/80">n8n, Telegram, Voice Search &
                                    Bildirimler</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.integrations.index') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-slate-50/20 px-4 py-2 text-white backdrop-blur-sm transition-all duration-200 hover:scale-105 hover:bg-slate-50/30 active:scale-95 dark:bg-slate-800/20 dark:text-white dark:hover:bg-slate-800/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Ayarlar
                        </a>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        @php
                            $aiWidgets = [
                                [
                                    'title' => 'n8n',
                                    'value' => '10 Workflow',
                                    'route' => route('admin.integrations.n8n-workflows'),
                                    'linkText' => "Workflow'ları Görüntüle →",
                                    'icon' =>
                                        'M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5z',
                                ],
                                [
                                    'title' => 'Telegram',
                                    'value' => '11 Komut',
                                    'route' => route('admin.telegram-bot.index'),
                                    'linkText' => "Bot'u Yönet →",
                                    'icon' => 'M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z',
                                    'iconFill' => true,
                                ],
                                [
                                    'title' => 'Voice Search',
                                    'value' => '4 Sağlayıcı',
                                    'route' => route('admin.voice-search.settings'),
                                    'linkText' => 'Ayarları Görüntüle →',
                                    'icon' =>
                                        'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
                                ],
                                [
                                    'title' => 'Bildirimler',
                                    'value' => '6 Kanal',
                                    'route' => route('admin.notifications.settings'),
                                    'linkText' => 'Kanalları Yönet →',
                                    'icon' =>
                                        'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                                ],
                            ];
                        @endphp

                        @foreach ($aiWidgets as $widget)
                            <div
                                class="rounded-lg border border-slate-50/20 bg-slate-50/10 p-4 backdrop-blur-sm transition-all duration-200 hover:scale-105 hover:bg-slate-50/20 dark:border-slate-700/40 dark:bg-slate-800/40 dark:hover:bg-slate-800/60">
                                <div class="mb-3 flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-50/20 dark:bg-slate-800/40">
                                        <svg class="h-5 w-5 text-white dark:text-white" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            @if (isset($widget['iconFill']))
                                                <path d="{{ $widget['icon'] }}" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="{{ $widget['icon'] }}" />
                                            @endif
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-white/70 dark:text-white/70">{{ $widget['title'] }}</p>
                                        <p class="text-lg font-bold text-white dark:text-white">{{ $widget['value'] }}</p>
                                    </div>
                                </div>
                                <a href="{{ $widget['route'] }}"
                                    class="text-sm text-white/90 transition-colors hover:text-white dark:text-white/90 dark:hover:text-white">
                                    {{ $widget['linkText'] }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- 🧠 TKGM Learning Engine - Market Analysis Widget --}}
        <div class="mb-10">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Market Analysis
                        (TKGM Learning Engine)
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hotspot ve özet istatistikler (dinamik API)</p>
                </div>
                <div class="flex items-center gap-3">
                    <select id="mi-province"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        <option value="48">Muğla (48)</option>
                        <option value="34">İstanbul (34)</option>
                        <option value="06">Ankara (06)</option>
                    </select>
                    <button id="mi-refresh"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-all duration-200 hover:scale-105 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-95 dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus:ring-offset-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582M20 20v-5h-.581M5.545 9A7.5 7.5 0 0119.5 12M18.455 15A7.5 7.5 0 014.5 12" />
                        </svg>
                        Yenile
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Stats --}}
                <div
                    class="rounded-xl border border-gray-200 bg-gray-50 p-6 shadow transition-all duration-200 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:shadow-none lg:col-span-1">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19V6a2 2 0 012-2h6a2 2 0 012 2v13M9 19H5a2 2 0 01-2-2v-5a2 2 0 012-2h4m0 7h6" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Özet İstatistik</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">Market
                                Snapshot</p>
                        </div>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Ortalama Fiyat</dt>
                            <dd id="mi-avg-price" class="font-semibold text-gray-900 dark:text-white">
                                -</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Medyan Fiyat</dt>
                            <dd id="mi-median-price"
                                class="font-semibold text-gray-900 dark:text-white">-</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Satış Hızı</dt>
                            <dd id="mi-sales-velocity"
                                class="font-semibold text-gray-900 dark:text-white">-</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Dönüşüm Oranı</dt>
                            <dd id="mi-conversion"
                                class="font-semibold text-gray-900 dark:text-white">-</dd>
                        </div>
                    </dl>
                    <p id="mi-durum" class="mt-4 text-xs text-gray-500 dark:text-gray-400">API çağrısı bekleniyor…</p>
                </div>

                {{-- Hotspots --}}
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow transition-all duration-200 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:shadow-none lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Yatırım Hotspot</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">En iyi 5
                                mahalle</p>
                        </div>
                        <span id="mi-hotspots-badge"
                            class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">-</span>
                    </div>
                    <div id="mi-hotspots-list" class="space-y-3">
                        <div class="h-10 animate-pulse rounded-lg bg-gray-100 dark:bg-slate-900"></div>
                        <div class="h-10 animate-pulse rounded-lg bg-gray-100 dark:bg-slate-900"></div>
                        <div class="h-10 animate-pulse rounded-lg bg-gray-100 dark:bg-slate-900"></div>
                    </div>
                    <p id="mi-hotspots-durum" class="mt-4 text-xs text-gray-500 dark:text-gray-400">API çağrısı
                        bekleniyor…</p>
                </div>
            </div>
        </div>

        <!-- Tables Grid -->
        <div class="mb-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
            <!-- Son Eklenen İlanlar -->
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 shadow dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-800">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Son Eklenen İlanlar
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    #</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Başlık</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Fiyat</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Durum</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Tarih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-gray-50 dark:divide-gray-700 dark:bg-slate-900">
                            @forelse($quickStats['recent_ilanlar'] ?? [] as $ilan)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ $ilan->id }}</td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ Str::limit($ilan->baslik, 30) }}</td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ number_format($ilan->fiyat) }} {{ $ilan->para_birimi }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @php
                                            $ilanDurumu = $ilan->yayin_durumu ?? 'Taslak';
                                            $ilanAktif =
                                                $ilanDurumu === true || $ilanDurumu === 'Aktif' || $ilanDurumu === 1;
                                        @endphp
                                        <span
                                            class="{{ $ilanAktif ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-gray-400' }} inline-flex rounded-full px-2 text-xs font-semibold leading-5 dark:text-slate-200">
                                            {{ is_bool($ilanDurumu) ? ($ilanDurumu ? 'Aktif' : 'Pasif') : $ilanDurumu }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ optional($ilan->created_at)->format('d.m.Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Henüz ilan eklenmemiş
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Son Kullanıcılar -->
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 shadow dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-800">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Son Kullanıcılar</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    #</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Ad</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Email</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Durum</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Kayıt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-gray-50 dark:divide-gray-700 dark:bg-slate-900">
                            @forelse($quickStats['recent_kullanicilar'] ?? [] as $user)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ $user->id }}</td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $user->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $user->email }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            class="{{ $user->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-gray-400' }} inline-flex rounded-full px-2 text-xs font-semibold leading-5 dark:text-slate-200">
                                            {{ $user->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ optional($user->created_at)->format('d.m.Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Henüz kullanıcı eklenmemiş
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sistem Durumu -->
        <div
            class="rounded-lg border border-gray-200 bg-gray-50 shadow dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-800">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sistem Durumu</h3>
            </div>
            <div class="px-6 py-4">
                <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    @foreach ($quickStats['sistem_durumu'] ?? [] as $service => $serviceState)
                        <div class="rounded-lg bg-gray-50 px-4 py-5 dark:bg-slate-900">
                            <dt class="truncate text-sm font-medium capitalize text-gray-500 dark:text-gray-400">
                                {{ $service }}</dt>
                            <dd class="mt-1 flex items-center">
                                <span
                                    class="{{ $serviceState == 'online' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} flex items-center text-sm font-semibold">
                                    <span
                                        class="{{ $serviceState == 'online' ? 'bg-green-500 dark:bg-green-500' : 'bg-red-500 dark:bg-red-500' }} mr-2 h-2 w-2 rounded-full"></span>
                                    {{ $serviceState == 'online' ? 'Online' : 'Offline' }}
                                </span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/dashboard/opportunity-board.js', 'resources/js/admin/dashboard/market-analysis.js'])

    <script>
        // Cortex Action Center Alpine Component
        function actionCenter() {
            return {
                loading: true,
                actions: [],
                stats: {
                    total: 0,
                    by_type: {
                        churn: 0,
                        pricing: 0,
                        match: 0
                    },
                    high_priority: 0
                },

                async init() {
                    await this.loadActions();
                },

                async loadActions() {
                    this.loading = true;
                    try {
                        const result = await window.safeJsonFetch('/api/v1/admin/dashboard/actions');

                        if (result.authRequired) {
                            // Auth gerekli — sessiz çık, UI default state'de kalır
                            return;
                        }

                        if (result.ok && result.data?.success) {
                            this.actions = result.data.actions || result.data.data?.actions || [];
                            this.stats = result.data.stats || result.data.data?.stats || this.stats;
                        } else if (result.error) {
                            console.warn('Action Center:', result.error);
                        }
                    } catch (error) {
                        console.warn('Action Center fetch error:', error.message);
                    } finally {
                        this.loading = false;
                    }
                },

                async refreshActions() {
                    await this.loadActions();
                    // Optional: Show toast notification
                    if (window.toast) {
                        window.toast('Aksiyonlar yenilendi', 'success');
                    }
                },

                executeAction(action) {
                    // Route to appropriate action handler
                    if (action.action_type === 'call') {
                        // Open phone/WhatsApp
                        if (action.action_data?.phone) {
                            window.open(`https://wa.me/${action.action_data.phone.replace(/[^0-9]/g, '')}`, '_blank');
                        }
                    } else if (action.action_type === 'price_review') {
                        // Navigate to listing edit
                        window.location.href = `/admin/ilanlar/${action.ilan_id}/edit`;
                    } else if (action.action_type === 'send_message') {
                        // Navigate to listing detail to open message modal
                        window.location.href = `/admin/ilanlar/${action.ilan_id}`;
                    }
                }
            };
        }
    </script>
@endpush
