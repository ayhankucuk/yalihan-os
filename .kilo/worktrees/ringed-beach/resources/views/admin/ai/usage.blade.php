@extends('admin.layouts.admin')

@section('title', 'AI Kullanım & Faturalandırma')

@section('content')
<div class="space-y-6" x-data="{ showTopUp: false, amount: 1000 }">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI Kullanım & Faturalandırma</h1>
            <p class="text-slate-500 dark:text-slate-400">Kredi bakiyesi ve işlem geçmişini takip edin.</p>
        </div>
        <div class="flex gap-3">
            <button @click="showTopUp = true" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Kredi Yükle
            </button>
        </div>
    </div>

    <!-- Top Up Modal -->
    <div x-show="showTopUp" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showTopUp = false"></div>

            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-8 transform transition-all border border-slate-200 dark:border-slate-700 dark:bg-slate-900">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Kredi Yükle (Simülasyon)</h3>
                <p class="text-slate-500 dark:text-slate-400 mb-6 text-sm">Cortex AI servislerini kullanmaya devam etmek için kredi paketlerinden birini seçin.</p>

                <form action="{{ route('admin.ai.usage.top-up') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <template x-for="p in [500, 1000, 5000, 10000]">
                            <button type="button" @click="amount = p" :class="amount === p ? 'border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/30' : 'border-slate-200 dark:border-slate-700'" class="p-3 border-2 rounded-xl text-center transition-all">
                                <span class="block font-bold text-slate-900 dark:text-white" x-text="p.toLocaleString()"></span>
                                <span class="text-xs text-slate-500">Kredi</span>
                            </button>
                        </template>
                    </div>

                    <input type="hidden" name="amount" :value="amount">

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-lg shadow-blue-500/20">
                            Yüklemeyi Tamamla
                        </button>
                        <button type="button" @click="showTopUp = false" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                            Vazgeç
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Balance Card -->
        <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-2xl p-6 text-white shadow-lg shadow-blue-500/20">
            <div class="flex items-center justify-between mb-4">
                <span class="text-indigo-100 font-medium">Mevcut Bakiye</span>
                <div class="p-2 bg-slate-50/20 dark:bg-slate-800/40 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-4xl font-bold tracking-tight">{{ number_format($balance) }}</span>
                <span class="text-indigo-100 font-medium text-lg">Kredi</span>
            </div>
            <div class="mt-4 pt-4 border-t border-white/10 dark:border-slate-700/40 flex items-center gap-2 text-sm text-indigo-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Hesap Aktif
            </div>
        </div>

        <!-- Feature Distribution -->
        <div class="md:col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700 dark:shadow-none dark:bg-slate-900">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-4">Özellik Bazlı Dağılım</h3>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($stats as $stat)
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wider mb-1">{{ str_replace('_', ' ', $stat->reason) }}</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($stat->total_spend) }} <span class="text-xs font-normal opacity-50">Kredi</span></p>
                    <p class="text-xs text-slate-400 mt-1">{{ $stat->count }} İşlem</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Transaction Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden dark:shadow-none dark:bg-slate-900">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">İşlem Geçmişi</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50">
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tarih</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">İşlem / Sebep</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Miktar</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Yeni Bakiye</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                            {{ $tx->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ str_replace('_', ' ', ucfirst($tx->reason)) }}</span>
                                <span class="text-xs text-slate-500">{{ $tx->reference_type ?? 'Sistem İşlemi' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex items-center font-bold {{ $tx->amount > 0 ? 'text-green-600 dark:text-green-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">
                            {{ number_format($tx->final_balance) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-slate-500 italic">Henüz bir işlem bulunmuyor.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-700">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
