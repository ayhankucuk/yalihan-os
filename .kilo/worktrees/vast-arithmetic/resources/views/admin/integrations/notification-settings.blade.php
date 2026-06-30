@extends('admin.layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">🔔 Bildirim Ayarları</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Sistem bildirimlerini ve e-posta gönderim
                    tercihlerini buradan yönetin.</p>
            </div>
            <button type="submit" form="notification-settings-form"
                class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                Ayarları Kaydet
            </button>
        </div>

        @if (session('success'))
            <div
                class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/30 rounded-lg text-green-800 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <form id="notification-settings-form" action="{{ route('admin.notifications.settings.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 space-y-6">
                    <!-- E-posta Bildirimleri -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                        <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 dark:bg-slate-900 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">E-posta Bildirimleri</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg dark:bg-slate-900">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Yeni İlan Bildirimleri
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Yeni bir ilan eklendiğinde e-posta
                                        gönder.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="notify_new_listing" class="sr-only peer" checked>
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                    </div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg dark:bg-slate-900">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Talep Eşleşme Bildirimleri
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Bir talep ile ilan eşleştiğinde
                                        danışmana haber ver.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="notify_match" class="sr-only peer" checked>
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                    </div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg dark:bg-slate-900">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Sistem Güncellemeleri</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Önemli sistem duyuruları ve
                                        güncellemeleri.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="notify_system" class="sr-only peer">
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- SMS & WhatsApp Bildirimleri -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                        <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 dark:bg-slate-900 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">SMS & WhatsApp</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">SMS
                                        Sağlayıcı</label>
                                    <select name="sms_provider"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:bg-slate-900 dark:text-slate-100">
                                        <option value="netgsm">NetGSM</option>
                                        <option value="iletimerkezi">İleti Merkezi</option>
                                        <option value="twilio">Twilio</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">WhatsApp
                                        API</label>
                                    <select name="whatsapp_provider"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:bg-slate-900 dark:text-slate-100">
                                        <option value="meta">Meta Business API</option>
                                        <option value="interact">Interact</option>
                                        <option value="disabled">Devre Dışı</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon -->
                <div class="space-y-6">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 uppercase tracking-wider dark:text-slate-100">
                            Bildirim Kanalları</h3>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <div
                                    class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 mr-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">E-posta (Aktif)</span>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400 mr-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">WhatsApp (Bağlı Değil)</span>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-8 h-8 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-yellow-600 dark:text-yellow-400 mr-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                        </path>
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Push Bildirimleri (Aktif)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
