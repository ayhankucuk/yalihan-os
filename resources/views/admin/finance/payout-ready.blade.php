@extends('layouts.admin')

@section('title', 'Ödeme Hazır - Finans Yönetimi')

@section('content')
<div class="container-fluid px-4 py-6">

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Ödeme Hazır Rezervasyonlar
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Ev sahibi ödemeleri — C3.3 Payout Readiness
                </p>
            </div>
            <a href="{{ route('admin.finance.dashboard') }}"
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition-colors text-sm">
                &larr; Finans Dashboard
            </a>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Ödenecek</span>
                <span class="text-xl">&#128176;</span>
            </div>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                &#8364;{{ number_format($totalEntitlement ?? 0, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                T&uuml;m kanallar &mdash; {{ $count ?? 0 }} rezervasyon
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Rezervasyon</span>
                <span class="text-xl">&#128203;</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $count ?? 0 }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Aktif &ouml;deme bekleyen
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Kanallar</span>
                <span class="text-xl">&#127760;</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                @if(!empty($reservations))
                    {{ collect($reservations)->pluck('external_channel')->filter()->unique()->count() }}
                @else
                    0
                @endif
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Airbnb / Booking / Manual
            </p>
        </div>
    </div>

    {{-- Payout Readiness Table --}}
    @if(!empty($reservations))
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                &Ouml;deme Haz&#305;r Rezervasyonlar ({{ count($reservations) }})
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">&Icirc;lan</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">Misafir</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">Tarih</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-400">Br&uuml;t</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-400">Komisyon</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-400">Sahip &Ouml;denecek</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">Model</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">Durum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($reservations as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            {{ Str::limit($item['ilan_baslik'] ?? 'Bilinmeyen', 30) }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $item['guest_name'] ?? '&mdash;' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $item['start_date'] }} &mdash; {{ $item['end_date'] }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-gray-700 dark:text-gray-300">
                            {{ number_format($item['gross_amount'], 0, ',', '.') }} {{ $item['currency'] }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-red-600 dark:text-red-400">
                            -{{ number_format($item['commission_amount'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-green-600 dark:text-green-400">
                            {{ number_format($item['owner_entitlement'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                @if(($item['management_model_snapshot'] ?? '') === 'FULL_MANAGEMENT')
                                    bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @elseif(($item['management_model_snapshot'] ?? '') === 'CHECKIN_CHECKOUT')
                                    bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                @elseif(($item['management_model_snapshot'] === 'NONE')
                                    bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                @else
                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @endif
                            ">
                                {{ $item['management_model_label'] ?? $item['management_model_snapshot'] ?? 'Bilinmeyen' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                &Ouml;deme Haz&#305;r
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
        <p class="text-gray-400 dark:text-gray-500 text-lg mb-2">Hen&uuml;z &ouml;deme haz&#305;r rezervasyon yok</p>
        <p class="text-gray-400 dark:text-gray-600 text-sm">
            T&uuml;m rezervasyonlar tamamland&#;&#305;&#287;&#305;nda ve C3.2 accrual tamamland&#;&#305;&#287;&#305;nda burada g&ouml;r&uuml;n&uuml;r.
        </p>
    </div>
    @endif

    {{-- Payout by Owner --}}
    @if(!empty($byOwner))
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                Sahip Baz&#305;nda &Ouml;deme &Ouml;zeti
            </h2>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($byOwner as $owner)
            <div class="px-6 py-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $owner['owner_name'] ?? 'Bilinmeyen Sahip' }}
                            @if(!empty($owner['owner_kisi_id']))
                                <span class="ml-2 text-xs text-gray-400">#{{ $owner['owner_kisi_id'] }}</span>
                            @endif
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ count($owner['reservations']) }} rezervasyon
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">
                            &#8364;{{ number_format($owner['total_entitlement'], 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500">Toplam &Ouml;denecek</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
