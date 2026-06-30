@extends('admin.layouts.admin')

@section('title', 'Karar Geçmişi')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Karar Geçmişi</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tüm onaylanan, reddedilen ve otomatik uygulanan kararlar</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.governance.review-queue') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700">
                İnceleme Kuyruğu
            </a>
            <a href="{{ route('admin.governance.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700">
                Dashboard
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="mb-6 flex gap-3">
        <a href="{{ route('admin.governance.decision-history') }}"
           class="rounded-lg px-3 py-1.5 text-sm font-medium {{ !request('karar_durumu') ? 'bg-indigo-600 text-white dark:bg-indigo-500' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300' }}">
            Tümü
        </a>
        <a href="{{ route('admin.governance.decision-history', ['karar_durumu' => 'approved']) }}"
           class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request('karar_durumu') === 'approved' ? 'bg-green-600 text-white dark:bg-green-500' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300' }}">
            Onaylanan
        </a>
        <a href="{{ route('admin.governance.decision-history', ['karar_durumu' => 'rejected']) }}"
           class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request('karar_durumu') === 'rejected' ? 'bg-red-600 text-white dark:bg-red-500' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300' }}">
            Reddedilen
        </a>
        <a href="{{ route('admin.governance.decision-history', ['karar_durumu' => 'auto_applied']) }}"
           class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request('karar_durumu') === 'auto_applied' ? 'bg-blue-600 text-white dark:bg-blue-500' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300' }}">
            Otomatik
        </a>
        <a href="{{ route('admin.governance.decision-history', ['karar_durumu' => 'failed']) }}"
           class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request('karar_durumu') === 'failed' ? 'bg-red-600 text-white dark:bg-red-500' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300' }}">
            Başarısız
        </a>
        <a href="{{ route('admin.governance.decision-history', ['karar_durumu' => 'rolled_back']) }}"
           class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request('karar_durumu') === 'rolled_back' ? 'bg-purple-600 text-white dark:bg-purple-500' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300' }}">
            Geri Alınan
        </a>
        <a href="{{ route('admin.governance.decision-history', ['karar_durumu' => 'blocked']) }}"
           class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request('karar_durumu') === 'blocked' ? 'bg-orange-600 text-white dark:bg-orange-500' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300' }}">
            Engellenen
        </a>
    </div>

    {{-- Decision Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        @if($decisions->isEmpty())
            <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <p class="text-sm">Henüz karar geçmişi yok.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Bulgu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kaynak</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Durum</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Güven</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Karar Veren</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tarih</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">SAB Proposal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach($decisions as $decision)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.governance.decisions.show', $decision) }}"
                                       class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        {{ Str::limit($decision->title, 50) }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-slate-700">{{ $decision->source }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    @php
                                        $durumBadge = match($decision->karar_durumu) {
                                            'approved' => ['text-green-800 bg-green-100 dark:bg-green-900/30 dark:text-green-400', 'Onaylandı'],
                                            'rejected' => ['text-red-800 bg-red-100 dark:bg-red-900/30 dark:text-red-400', 'Reddedildi'],
                                            'auto_applied' => ['text-blue-800 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400', 'Otomatik'],
                                            'failed' => ['text-red-800 bg-red-100 dark:bg-red-900/30 dark:text-red-400', 'Başarısız'],
                                            'rolled_back' => ['text-purple-800 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400', 'Geri Alındı'],
                                            'blocked' => ['text-orange-800 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400', 'Engellendi'],
                                            default => ['text-gray-800 bg-gray-100 dark:bg-slate-700 dark:text-gray-300', ucfirst($decision->karar_durumu)],
                                        };
                                    @endphp
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $durumBadge[0] }}">{{ $durumBadge[1] }}</span>
                                    @if($decision->isOverridden())
                                        <span class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">Override</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    @if($decision->confidence)
                                        @php $confColor = $decision->confidence >= 0.7 ? 'text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-400' : ($decision->confidence >= 0.5 ? 'text-yellow-700 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400' : 'text-red-700 bg-red-100 dark:bg-red-900/30 dark:text-red-400'); @endphp
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $confColor }}">%{{ number_format($decision->confidence * 100, 0) }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $decision->kararVeren?->name ?? 'Sistem' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $decision->karar_tarihi?->format('d.m.Y H:i') ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    @if($decision->proposal_filename)
                                        <span class="font-mono text-xs">{{ Str::limit($decision->proposal_filename, 30) }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-6 py-3 dark:border-slate-700">
                {{ $decisions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
