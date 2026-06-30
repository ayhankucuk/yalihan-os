@extends('admin.layouts.admin')

@section('title', 'Bildirim Detayı #' . $log->id)

@section('content')
<div class="container-fluid px-6 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <a href="{{ route('admin.outbound-notifications.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center font-bold mb-4">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Listeye Dön
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Bildirim Kaydı Detayı</h1>
            <p class="text-gray-500 mt-2">ID: #{{ $log->id }} | Kanal: {{ strtoupper($log->channel) }}</p>
        </div>

        @if($log->gonderim_durumu == \App\Models\Notification\OutboundNotification::STATE_FAILED)
        <form action="{{ route('admin.outbound-notifications.retry', $log->id) }}" method="POST">
            @csrf
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-orange-100 dark:shadow-none active:scale-95 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Yeniden Gönder
            </button>
        </form>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Temel Bilgiler -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-8 shadow-sm">
                <h3 class="text-xl font-bold mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Genel Bilgiler
                </h3>
                
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase">Alıcı</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">{{ $log->recipient }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase">Şablon Anahtarı</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1"><code>{{ $log->template_key }}</code></p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase">Durum</p>
                        <div class="mt-2">
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold 
                                @if($log->gonderim_durumu == \App\Models\Notification\OutboundNotification::STATE_SENT) bg-green-500 text-white @endif
                                @if($log->gonderim_durumu == \App\Models\Notification\OutboundNotification::STATE_FAILED) bg-red-500 text-white @endif
                                @if($log->gonderim_durumu == \App\Models\Notification\OutboundNotification::STATE_PENDING) bg-amber-500 text-white @endif
                                @if($log->gonderim_durumu == \App\Models\Notification\OutboundNotification::STATE_QUEUED) bg-blue-500 text-white @endif
                            ">
                                {{ strtoupper($log->gonderim_durumu) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase">Deneme Sayısı</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">{{ $log->retry_count }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-8 shadow-sm">
                <h3 class="text-xl font-bold mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                    Payload Verisi (Maskelenmiş)
                </h3>
                <div class="bg-gray-900 rounded-xl p-6 overflow-x-auto shadow-inner">
                    <pre class="text-green-400 font-mono text-sm"><code>{{ json_encode($maskedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>

            @if($log->provider_response)
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-8 shadow-sm">
                <h3 class="text-xl font-bold mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Sağlayıcı Yanıtı (Provider Response)
                </h3>
                <div>
                    <p class="text-sm font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2">Provider Response (Ham Yanıt)</p>
                    <div class="bg-gray-50 dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700 overflow-x-auto">
                        <pre class="text-xs text-green-600 dark:text-green-400 font-mono">{{ json_encode($maskedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Zaman Çizelgesi & Hata -->
        <div class="space-y-8">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-8 shadow-sm">
                <h3 class="text-xl font-bold mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Zaman Akışı
                </h3>
                <div class="space-y-6">
                    <div class="relative pl-8 border-l-2 border-gray-100 dark:border-slate-800">
                        <div class="absolute -left-2 top-0 w-4 h-4 rounded-full bg-blue-500 border-4 border-white dark:border-slate-900"></div>
                        <p class="text-xs font-bold text-gray-400 uppercase">Oluşturulma</p>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $log->created_at->format('d.m.Y H:i:s') }}</p>
                    </div>
                    @if($log->last_attempt_at)
                    <div class="relative pl-8 border-l-2 border-gray-100 dark:border-slate-800">
                        <div class="absolute -left-2 top-0 w-4 h-4 rounded-full bg-orange-500 border-4 border-white dark:border-slate-900"></div>
                        <p class="text-xs font-bold text-gray-400 uppercase">Son Deneme</p>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $log->last_attempt_at->format('d.m.Y H:i:s') }}</p>
                    </div>
                    @endif
                    @if($log->sent_at)
                    <div class="relative pl-8 border-l-2 border-gray-100 dark:border-slate-800">
                        <div class="absolute -left-2 top-0 w-4 h-4 rounded-full bg-green-500 border-4 border-white dark:border-slate-900"></div>
                        <p class="text-xs font-bold text-gray-400 uppercase">Gönderim Tamamlandı</p>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $log->sent_at->format('d.m.Y H:i:s') }}</p>
                    </div>
                    @endif
                </div>
            </div>

            @if($log->error_message)
            <div class="bg-red-50 dark:bg-red-900/10 rounded-2xl border border-red-100 dark:border-red-900/30 p-8 shadow-sm">
                <h3 class="text-xl font-bold text-red-800 dark:text-red-400 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    Hata Mesajı
                </h3>
                <div class="bg-white/50 dark:bg-red-900/20 rounded-xl p-4 border border-red-100 dark:border-red-900/30">
                    <p class="text-red-700 dark:text-red-300 font-mono text-sm leading-relaxed">{{ $log->error_message }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
