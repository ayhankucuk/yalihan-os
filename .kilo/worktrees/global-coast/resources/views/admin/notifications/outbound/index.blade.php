@extends('admin.layouts.admin')

@section('title', 'Dışa Giden Bildirim Kayıtları')

@section('content')
<div class="container-fluid px-6 py-8">
    <div class="flex items-center justify-between mb-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dışa Giden Bildirim Kayıtları</h1>
            <p class="text-gray-500 mt-2">Sistem tarafından gönderilen tüm harici iletişimlerin (Email, WhatsApp, vb.) operasyonel izleme günlüğü.</p>
        </div>
        
        <button onclick="document.getElementById('testSendModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-lg shadow-indigo-100 dark:shadow-none active:scale-95 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            Test Gönder
        </button>
    </div>

    <!-- Test Send Modal -->
    <div id="testSendModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-3xl w-full max-w-lg shadow-2xl border border-gray-100 dark:border-slate-800 p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Manuel Test Gönder</h3>
                <button onclick="document.getElementById('testSendModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form action="{{ route('admin.outbound-notifications.test') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Kanal</label>
                    <select name="channel" required class="w-full rounded-xl border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="email">📧 Email</option>
                        <option value="whatsapp">💬 WhatsApp</option>
                        <option value="telegram">✈️ Telegram</option>
                        <option value="instagram">📸 Instagram</option>
                        <option value="webhook">🔗 Webhook</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Alıcı (Email / Telefon / ID)</label>
                    <input type="text" name="recipient" required placeholder="ör: 90555..." class="w-full rounded-xl border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Şablon Anahtarı</label>
                    <input type="text" name="template_key" required value="manual_test" class="w-full rounded-xl border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Mesaj İçeriği</label>
                    <textarea name="message" required rows="3" placeholder="Test mesajı..." class="w-full rounded-xl border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg transition-all active:scale-95">
                    Gönderimi Başlat
                </button>
            </form>
        </div>
    </div>

    <!-- Metrics (P2) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/30 rounded-2xl p-6 shadow-sm">
            <p class="text-blue-600 dark:text-blue-400 text-sm font-bold uppercase tracking-wider mb-1">Bugünkü Toplam</p>
            <h3 class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ $stats['total_today'] }}</h3>
        </div>
        <div class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-2xl p-6 shadow-sm">
            <p class="text-red-600 dark:text-red-400 text-sm font-bold uppercase tracking-wider mb-1">Bugünkü Hata</p>
            <h3 class="text-3xl font-bold text-red-900 dark:text-red-100">{{ $stats['failed_today'] }}</h3>
        </div>
        <div class="bg-green-50 dark:bg-green-900/10 border border-green-100 dark:border-green-900/30 rounded-2xl p-6 shadow-sm">
            <p class="text-green-600 dark:text-green-400 text-sm font-bold uppercase tracking-wider mb-1">Başarı Oranı</p>
            <h3 class="text-3xl font-bold text-green-900 dark:text-green-100">%{{ $stats['success_rate'] }}</h3>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-6 mb-8 shadow-sm">
        <form method="GET" action="{{ route('admin.outbound-notifications.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">Kanal</label>
                <select name="channel" class="w-full rounded-xl border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 transition-all">
                    <option value="">Tümü</option>
                    <option value="email" {{ request('channel') == 'email' ? 'selected' : '' }}>📧 Email</option>
                    <option value="whatsapp" {{ request('channel') == 'whatsapp' ? 'selected' : '' }}>💬 WhatsApp</option>
                    <option value="telegram" {{ request('channel') == 'telegram' ? 'selected' : '' }}>✈️ Telegram</option>
                    <option value="instagram" {{ request('channel') == 'instagram' ? 'selected' : '' }}>📸 Instagram</option>
                    <option value="webhook" {{ request('channel') == 'webhook' ? 'selected' : '' }}>🔗 Webhook</option>
                </select>
            </div>
                <!-- Durum Filtresi -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Durum</label>
                    <select name="durum" class="w-full rounded-xl border-gray-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white focus:ring-blue-500">
                        <option value="">Tümü</option>
                        <option value="pending" @if(($filters['durum'] ?? '') == 'pending') selected @endif>Pending</option>
                        <option value="queued" @if(($filters['durum'] ?? '') == 'queued') selected @endif>Queued</option>
                        <option value="sent" @if(($filters['durum'] ?? '') == 'sent') selected @endif>Sent</option>
                        <option value="failed" @if(($filters['durum'] ?? '') == 'failed') selected @endif>Failed</option>
                    </select>
                </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">Alıcı</label>
                <input type="text" name="recipient" value="{{ request('recipient') }}" placeholder="Email / Tel..." class="w-full rounded-xl border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">Şablon</label>
                <input type="text" name="template_key" value="{{ request('template_key') }}" placeholder="Şablon anahtarı..." class="w-full rounded-xl border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 transition-all">
            </div>
            <div class="flex items-end space-x-3">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-lg shadow-blue-100 dark:shadow-none active:scale-95">
                    Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Tablo -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 overflow-hidden shadow-sm">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800">
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Kanal</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Alıcı</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Şablon</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Deneme</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tarih</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
                @foreach($logs as $log)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide
                            @if($log->channel == 'email') bg-blue-100 text-blue-800 @endif
                            @if($log->channel == 'whatsapp') bg-green-100 text-green-800 @endif
                            @if($log->channel == 'telegram') bg-sky-100 text-sky-800 @endif
                            @if($log->channel == 'webhook') bg-purple-100 text-purple-800 @endif
                        ">
                            @if($log->channel == 'email') 📧 @endif
                            @if($log->channel == 'whatsapp') 💬 @endif
                            @if($log->channel == 'telegram') ✈️ @endif
                            @if($log->channel == 'webhook') 🔗 @endif
                            {{ $log->channel }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white truncate max-w-xs">
                        {{ $log->recipient }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-slate-400">
                        <code>{{ $log->template_key }}</code>
                    </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold 
                                @if($log->gonderim_durumu == \App\Models\Notification\OutboundNotification::STATE_SENT) bg-green-100 text-green-700 @endif
                                @if($log->gonderim_durumu == \App\Models\Notification\OutboundNotification::STATE_FAILED) bg-red-100 text-red-700 @endif
                                @if($log->gonderim_durumu == \App\Models\Notification\OutboundNotification::STATE_PENDING) bg-amber-100 text-amber-700 @endif
                                @if($log->gonderim_durumu == \App\Models\Notification\OutboundNotification::STATE_QUEUED) bg-blue-100 text-blue-700 @endif
                            ">
                                {{ strtoupper($log->gonderim_durumu) }}
                            </span>
                        </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-slate-400 text-center">
                        {{ $log->retry_count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                        {{ $log->created_at->format('d.m.Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('admin.outbound-notifications.show', $log->id) }}" class="text-blue-600 hover:text-blue-900 font-bold">Detay</a>
                            
                            @if($log->gonderim_durumu == \App\Models\Notification\OutboundNotification::STATE_FAILED)
                            <form action="{{ route('admin.outbound-notifications.retry', $log->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-orange-600 hover:text-orange-900 font-bold">Tekrar Dene</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50">
            {{ $logs->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
