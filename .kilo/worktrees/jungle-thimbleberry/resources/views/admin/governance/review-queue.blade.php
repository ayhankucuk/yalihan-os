@extends('admin.layouts.admin')

@section('title', 'Karar Motoru — İnceleme Kuyruğu')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Karar Motoru — İnceleme Kuyruğu</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Onay bekleyen bulgular ve otomatik karar durumu</p>
        </div>
        <div class="flex gap-3">
            <form method="POST" action="{{ route('admin.governance.scan') }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Tarama Başlat
                </button>
            </form>
            <a href="{{ route('admin.governance.decision-history') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700">
                Karar Geçmişi
            </a>
            <a href="{{ route('admin.governance.suppression-list') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 shadow-sm hover:bg-amber-100 dark:border-amber-600 dark:bg-amber-900/20 dark:text-amber-300 dark:hover:bg-amber-900/30">
                Bastırma Kuralları
            </a>
            <a href="{{ route('admin.governance.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700">
                Dashboard
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Status Cards --}}
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
            <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Bekleyen</p>
            <p class="mt-1 text-2xl font-bold text-yellow-800 dark:text-yellow-300">{{ $counts['pending'] }}</p>
        </div>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
            <p class="text-sm font-medium text-green-600 dark:text-green-400">Onaylanan</p>
            <p class="mt-1 text-2xl font-bold text-green-800 dark:text-green-300">{{ $counts['approved'] }}</p>
        </div>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
            <p class="text-sm font-medium text-red-600 dark:text-red-400">Reddedilen</p>
            <p class="mt-1 text-2xl font-bold text-red-800 dark:text-red-300">{{ $counts['rejected'] }}</p>
        </div>
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Otomatik</p>
            <p class="mt-1 text-2xl font-bold text-blue-800 dark:text-blue-300">{{ $counts['auto_applied'] }}</p>
        </div>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
            <p class="text-sm font-medium text-red-600 dark:text-red-400">Başarısız</p>
            <p class="mt-1 text-2xl font-bold text-red-800 dark:text-red-300">{{ $counts['failed'] }}</p>
        </div>
        <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Geri Alınan</p>
            <p class="mt-1 text-2xl font-bold text-purple-800 dark:text-purple-300">{{ $counts['rolled_back'] }}</p>
        </div>
        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
            <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Override</p>
            <p class="mt-1 text-2xl font-bold text-indigo-800 dark:text-indigo-300">{{ $counts['overridden'] }}</p>
        </div>
    </div>

    {{-- Pending Decisions Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Bekleyen Kararlar</h2>
        </div>

        @if($pending->isEmpty())
            <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <svg class="mx-auto mb-4 h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm">Bekleyen karar yok. Sistem temiz.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Bulgu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kaynak</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ciddiyet</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Güven</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Risk</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Önerilen</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tarih</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach($pending as $decision)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.governance.decisions.show', $decision) }}"
                                       class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        {{ Str::limit($decision->title, 50) }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $decision->finding_id }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-slate-700">{{ $decision->source }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    @php
                                        $severityColor = match($decision->severity?->value ?? $decision->severity) {
                                            'low' => 'green',
                                            'medium' => 'yellow',
                                            'high' => 'orange',
                                            'critical' => 'red',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <span class="rounded-full bg-{{ $severityColor }}-100 px-2 py-0.5 text-xs font-medium text-{{ $severityColor }}-800 dark:bg-{{ $severityColor }}-900/30 dark:text-{{ $severityColor }}-400">
                                        {{ $decision->severity instanceof \App\Enums\FindingSeverity ? $decision->severity->label() : ucfirst($decision->severity) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    @if($decision->confidence !== null)
                                        @php
                                            $cColor = $decision->confidence >= 0.7 ? 'green' : ($decision->confidence >= 0.5 ? 'yellow' : 'red');
                                        @endphp
                                        <span class="rounded-full bg-{{ $cColor }}-100 px-2 py-0.5 text-xs font-medium text-{{ $cColor }}-800 dark:bg-{{ $cColor }}-900/30 dark:text-{{ $cColor }}-400">
                                            %{{ round($decision->confidence * 100) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $decision->risk }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $decision->recommended_action }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $decision->created_at?->diffForHumans() }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.governance.decisions.approve', $decision) }}">
                                            @csrf
                                            <button type="submit"
                                                class="rounded bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600">
                                                Onayla
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.governance.decisions.reject', $decision) }}">
                                            @csrf
                                            <button type="submit"
                                                class="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600">
                                                Reddet
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-6 py-3 dark:border-slate-700">
                {{ $pending->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
