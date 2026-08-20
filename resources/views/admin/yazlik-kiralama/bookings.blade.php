@extends('admin.layouts.admin')

@section('title', 'Rezervasyon Yönetimi')

@section('content')
    <div class="min-h-screen bg-gray-50 py-8 dark:bg-slate-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                        📅 Rezervasyon Yönetimi
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Yazlık kiralama rezervasyonlarını yönetin
                    </p>
                </div>

                <a href="{{ route('admin.yazlik-kiralama.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-4 py-2 text-gray-700 transition-all hover:bg-gray-300 dark:bg-gray-700 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-gray-600">
                    ← Geri Dön
                </a>
            </div>

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
                    <div class="flex items-center gap-2">
                        <span class="font-bold">✓</span>
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                    <div class="flex items-center gap-2">
                        <span class="font-bold">✕</span>
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none"
                x-data="{
                    showFilters: false,
                    rezervasyon_durumu: '{{ request('rezervasyon_durumu', request('reservation_state')) }}',
                    dateRange: '{{ request('date_range') }}'
                }">

                {{-- Filter Toggle --}}
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                        🔍 Filtreler
                    </h2>
                    <button @click="showFilters = !showFilters"
                        class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                        <span x-text="showFilters ? 'Gizle' : 'Göster'"></span>
                    </button>
                </div>

                {{-- Filter Form --}}
                <form method="GET" action="{{ route('admin.yazlik-kiralama.bookings', $id ?? null) }}"
                    x-show="showFilters" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100" class="grid grid-cols-1 gap-4 md:grid-cols-3">

                    {{-- Status Filter --}}
                    <div>
                        <label for="rezervasyon_durumu"
                            class="mb-2 block text-sm font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                            Durum
                        </label>
                        <select name="rezervasyon_durumu" id="rezervasyon_durumu" x-model="rezervasyon_durumu"
                            class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2 font-semibold text-black transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                            <option value="">Tümü</option>
                            <option value="pending">Beklemede</option>
                            <option value="confirmed">Onaylandı</option>
                            <option value="cancelled">İptal Edildi</option>
                            <option value="completed">Tamamlandı</option>
                        </select>
                    </div>

                    {{-- Date Range Filter --}}
                    <div>
                        <label for="date_range"
                            class="mb-2 block text-sm font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                            Tarih Aralığı
                        </label>
                        <input type="text" name="date_range" id="date_range" x-model="dateRange"
                            placeholder="2025-01-01 - 2025-12-31"
                            class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2 font-semibold text-black placeholder-gray-600 transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500">
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex items-end">
                        <button type="submit"
                            class="w-full rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-2 font-semibold text-white shadow-lg transition-all hover:scale-105 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-95">
                            Filtrele
                        </button>
                    </div>
                </form>
            </div>

            {{-- Bookings List --}}
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">

                @if ($bookings->isEmpty())
                    {{-- Empty State --}}
                    <div class="py-16 text-center">
                        <div class="mb-4 text-6xl">📅</div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                            Rezervasyon Bulunamadı
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Henüz hiç rezervasyon yapılmamış veya filtrelere uygun rezervasyon yok.
                        </p>
                    </div>
                @else
                    {{-- Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead
                                class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                        ID
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                        İlan
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                        Check-in
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                        Check-out
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                        Misafir
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                        Durum
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                        İşlemler
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($bookings as $booking)
                                    @php
                                        $rawState = $booking->reservation_state instanceof \App\Enums\ReservationState
                                            ? $booking->reservation_state->value
                                            : ($booking->reservation_state ?? $booking->rezervasyon_durumu ?? 'pending');
                                        $isConfirmed = ($rawState === 'confirmed');
                                        $isCancelled = ($rawState === 'cancelled' || $booking->cancelled_at !== null);
                                        $isCheckedIn = ($booking->checked_in_at !== null);
                                        $isCheckedOut = ($booking->checked_out_at !== null || $booking->completed_at !== null || $rawState === 'completed');
                                    @endphp
                                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        {{-- ID --}}
                                        <td
                                            class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                            #{{ $booking->id }}
                                        </td>

                                        {{-- İlan --}}
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                            <div class="font-semibold">{{ $booking->ilan->baslik ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                İlan ID: {{ $booking->ilan_id ?? $booking->property_id }}
                                            </div>
                                        </td>

                                        {{-- Check-in --}}
                                        <td
                                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                            {{ \Carbon\Carbon::parse($booking->start_date ?? $booking->check_in)->format('d.m.Y') }}
                                        </td>

                                        {{-- Check-out --}}
                                        <td
                                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                            {{ \Carbon\Carbon::parse($booking->end_date ?? $booking->check_out)->format('d.m.Y') }}
                                        </td>

                                        {{-- Misafir --}}
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                            <div class="font-semibold">{{ $booking->guest_name ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $booking->guest_email ?? 'N/A' }}
                                            </div>
                                        </td>

                                        {{-- Status --}}
                                        <td class="whitespace-nowrap px-6 py-4">
                                            @if ($isCancelled)
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    ✕ İptal
                                                </span>
                                            @elseif ($isCheckedOut)
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    ✓ Tamamlandı
                                                </span>
                                            @elseif ($isCheckedIn)
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                                    🏡 Konaklıyor
                                                </span>
                                            @elseif ($isConfirmed)
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    ✓ Onaylandı
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                    ⏳ Beklemede
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            <div class="flex items-center gap-2">
                                                @if ($isConfirmed && !$isCheckedIn && !$isCancelled && !$isCheckedOut)
                                                    {{-- Eligible Arrival: Show Check-in --}}
                                                    <form method="POST" action="{{ route('admin.yazlik-kiralama.bookings.check-in', $booking->id) }}">
                                                        @csrf
                                                        <button type="submit"
                                                            id="btn-checkin-{{ $booking->id }}"
                                                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-emerald-700">
                                                            <span>🚪</span> Misafir Geldi
                                                        </button>
                                                    </form>
                                                @elseif ($isCheckedIn && !$isCheckedOut && !$isCancelled)
                                                    {{-- Checked in / Active Stay: Show Check-out with confirmation --}}
                                                    <form method="POST" action="{{ route('admin.yazlik-kiralama.bookings.check-out', $booking->id) }}"
                                                        onsubmit="return confirm('Misafirin çıkış yaptığını onaylıyor musunuz?\nTemizlik operasyonu başlatılacaktır.');">
                                                        @csrf
                                                        <button type="submit"
                                                            id="btn-checkout-{{ $booking->id }}"
                                                            class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-amber-700">
                                                            <span>🧹</span> Misafir Çıktı
                                                        </button>
                                                    </form>
                                                @elseif ($isCheckedOut)
                                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                        Çıkış Yapıldı
                                                    </span>
                                                @elseif ($isCancelled)
                                                    <span class="text-xs font-medium text-gray-400">
                                                        İşlem Yok
                                                    </span>
                                                @endif

                                                <a href="{{ route('admin.yazlik-kiralama.show', $booking->ilan_id ?? $booking->property_id ?? 1) }}"
                                                    class="rounded-lg bg-gray-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-700">
                                                    Detay
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div
                        class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        {{ $bookings->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection
