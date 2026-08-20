@extends('admin.layouts.admin')

@section('title', 'Rezervasyon Operasyonları & İstisna Kontrol Merkezi')

@section('content')
    <div class="min-h-screen bg-gray-50 py-8 dark:bg-slate-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">
                        📅 Rezervasyon Operasyonları
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Uçtan uca operasyonel kontrol ve deterministik istisna tespiti (Wave 7)
                    </p>
                </div>

                <a href="{{ route('admin.yazlik-kiralama.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-4 py-2 text-gray-700 transition-all hover:bg-gray-300 dark:bg-gray-700 dark:text-slate-200 dark:hover:bg-gray-600">
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

            {{-- Wave 6+7: Operational Filter Tabs --}}
            <div class="mb-6 flex flex-wrap gap-2">
                {{-- Priority Exception Filter --}}
                <a href="{{ route('admin.yazlik-kiralama.bookings', ['id' => $id ?? null, 'filter' => 'exceptions']) }}"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all {{ $filter === 'exceptions' || $filter === 'intervention_needed' ? 'bg-red-600 text-white shadow-md ring-2 ring-red-400' : (($counts['exceptions'] ?? 0) > 0 ? 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300 dark:border-red-800' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-200 dark:border-slate-700') }}">
                    <span>⚠️ Müdahale Gerekenler</span>
                    <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $filter === 'exceptions' || $filter === 'intervention_needed' ? 'bg-red-700 text-white' : 'bg-red-200 text-red-900 dark:bg-red-900 dark:text-red-200' }}">
                        {{ $counts['exceptions'] ?? 0 }}
                    </span>
                </a>

                <a href="{{ route('admin.yazlik-kiralama.bookings', ['id' => $id ?? null]) }}"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all {{ empty($filter) ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-200 dark:border-slate-700' }}">
                    <span>Tümü</span>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ empty($filter) ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300' }}">
                        {{ $counts['all'] ?? 0 }}
                    </span>
                </a>

                <a href="{{ route('admin.yazlik-kiralama.bookings', ['id' => $id ?? null, 'filter' => 'arrival_today']) }}"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all {{ $filter === 'arrival_today' ? 'bg-amber-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-200 dark:border-slate-700' }}">
                    <span>📅 Bugün Gelenler</span>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $filter === 'arrival_today' ? 'bg-amber-700 text-white' : 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300' }}">
                        {{ $counts['arrival_today'] ?? 0 }}
                    </span>
                </a>

                <a href="{{ route('admin.yazlik-kiralama.bookings', ['id' => $id ?? null, 'filter' => 'readiness_blocked']) }}"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all {{ $filter === 'readiness_blocked' ? 'bg-rose-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-200 dark:border-slate-700' }}">
                    <span>⚠️ Hazır Olmayanlar</span>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $filter === 'readiness_blocked' ? 'bg-rose-700 text-white' : 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300' }}">
                        {{ $counts['readiness_blocked'] ?? 0 }}
                    </span>
                </a>

                <a href="{{ route('admin.yazlik-kiralama.bookings', ['id' => $id ?? null, 'filter' => 'in_house']) }}"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all {{ $filter === 'in_house' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-200 dark:border-slate-700' }}">
                    <span>🏡 Konaklayanlar</span>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $filter === 'in_house' ? 'bg-emerald-700 text-white' : 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300' }}">
                        {{ $counts['in_house'] ?? 0 }}
                    </span>
                </a>

                <a href="{{ route('admin.yazlik-kiralama.bookings', ['id' => $id ?? null, 'filter' => 'turnover_pending']) }}"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all {{ $filter === 'turnover_pending' ? 'bg-purple-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-200 dark:border-slate-700' }}">
                    <span>🧹 Temizlik Bekleyenler</span>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $filter === 'turnover_pending' ? 'bg-purple-700 text-white' : 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300' }}">
                        {{ $counts['turnover_pending'] ?? 0 }}
                    </span>
                </a>
            </div>

            {{-- Bookings Operations List --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">

                @if ($bookings->isEmpty())
                    <div class="py-16 text-center">
                        <div class="mb-4 text-6xl">📅</div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900 dark:text-slate-100">
                            Rezervasyon Bulunamadı
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Seçilen operasyonel filtreye uygun rezervasyon kaydı bulunmuyor.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-5 py-3.5">ID / İlan</th>
                                    <th class="px-5 py-3.5">Tarihler</th>
                                    <th class="px-5 py-3.5">Misafir</th>
                                    <th class="px-5 py-3.5">Operasyonel Durum Zinciri & İstisnalar</th>
                                    <th class="px-5 py-3.5 text-right">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($bookings as $booking)
                                    @php
                                        $rawState = $booking->reservation_state instanceof \App\Enums\ReservationState
                                            ? $booking->reservation_state->value
                                            : ($booking->reservation_state ?? 'pending');

                                        $isConfirmed = ($rawState === 'confirmed');
                                        $isCancelled = ($rawState === 'cancelled' || $booking->cancelled_at !== null);
                                        $isCheckedIn = ($booking->checked_in_at !== null);
                                        $isCheckedOut = ($booking->checked_out_at !== null || $booking->completed_at !== null || $rawState === 'completed');

                                        // Readiness Dimensions
                                        $readiness = $booking->readiness;
                                        $isReady = $readiness?->is_ready ?? false;
                                        $readinessScore = 0;
                                        if ($readiness) {
                                            if ($readiness->property_clean) $readinessScore++;
                                            if ($readiness->access_credential_ready) $readinessScore++;
                                            if ($readiness->guest_contact_ready) $readinessScore++;
                                            if ($readiness->amenity_check_complete) $readinessScore++;
                                            if ($readiness->welcome_kit_prepared) $readinessScore++;
                                        }

                                        // Tasks
                                        $prepTask = $booking->prepTask;
                                        $prepDone = ($prepTask?->gorev_durumu === 'tamamlandi');

                                        $turnoverTask = $booking->turnoverTask;
                                        $turnoverDone = ($turnoverTask?->gorev_durumu === 'tamamlandi');

                                        // Wave 7: Exceptions for this reservation
                                        $rowExceptions = $exceptionsMap[$booking->id] ?? [];
                                    @endphp

                                    <tr class="transition-colors hover:bg-gray-50/70 dark:hover:bg-slate-800/50 {{ !empty($rowExceptions) ? 'bg-red-50/30 dark:bg-red-950/10' : '' }}">
                                        {{-- ID & Listing --}}
                                        <td class="px-5 py-4 align-top">
                                            <div class="font-bold text-gray-900 dark:text-slate-100">#{{ $booking->id }}</div>
                                            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                {{ $booking->ilan->baslik ?? 'İlan #' . ($booking->property_id ?? $booking->ilan_id) }}
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $booking->nights ?? 1 }} Gece · {{ $booking->guest_count ?? 1 }} Kişi
                                            </div>
                                        </td>

                                        {{-- Dates --}}
                                        <td class="px-5 py-4 align-top whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">
                                            <div class="font-medium text-emerald-700 dark:text-emerald-400">
                                                Giriş: {{ \Carbon\Carbon::parse($booking->start_date)->format('d.m.Y') }}
                                            </div>
                                            <div class="text-rose-700 dark:text-rose-400">
                                                Çıkış: {{ \Carbon\Carbon::parse($booking->end_date)->format('d.m.Y') }}
                                            </div>
                                        </td>

                                        {{-- Guest --}}
                                        <td class="px-5 py-4 align-top text-sm">
                                            <div class="font-semibold text-gray-900 dark:text-slate-100">{{ $booking->guest_name ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $booking->guest_phone ?? $booking->guest_email ?? '—' }}</div>
                                        </td>

                                        {{-- 7-Stage Visual Operational Stepper & Exceptions --}}
                                        <td class="px-5 py-4 align-top">
                                            {{-- Stepper --}}
                                            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                                {{-- 1. Rezervasyon --}}
                                                @if ($isCancelled)
                                                    <span class="inline-flex items-center gap-1 rounded bg-red-100 px-2 py-0.5 font-medium text-red-800 dark:bg-red-900/50 dark:text-red-300" title="İptal Edildi">
                                                        ✕ Rezervasyon
                                                    </span>
                                                @elseif ($isConfirmed || $isCheckedIn || $isCheckedOut)
                                                    <span class="inline-flex items-center gap-1 rounded bg-green-100 px-2 py-0.5 font-medium text-green-800 dark:bg-green-900/50 dark:text-green-300" title="Onaylı Rezervasyon">
                                                        ✓ Rezervasyon
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded bg-yellow-100 px-2 py-0.5 font-medium text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300" title="Onay Bekliyor">
                                                        ⏳ Rezervasyon
                                                    </span>
                                                @endif

                                                <span class="text-gray-300 dark:text-gray-600">→</span>

                                                {{-- 2. Hazırlık Görevi --}}
                                                @if ($prepDone)
                                                    <span class="inline-flex items-center gap-1 rounded bg-green-100 px-2 py-0.5 font-medium text-green-800 dark:bg-green-900/50 dark:text-green-300" title="Hazırlık görevi tamamlandı">
                                                        ✓ Hazırlık
                                                    </span>
                                                @elseif ($prepTask)
                                                    <span class="inline-flex items-center gap-1 rounded bg-blue-100 px-2 py-0.5 font-medium text-blue-800 dark:bg-blue-900/50 dark:text-blue-300" title="Hazırlık görevi devam ediyor">
                                                        ⏳ Hazırlık
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-slate-800 dark:text-gray-400" title="Hazırlık görevi planlanmadı">
                                                        — Hazırlık
                                                    </span>
                                                @endif

                                                <span class="text-gray-300 dark:text-gray-600">→</span>

                                                {{-- 3. Readiness (Hazır) --}}
                                                @if ($isReady)
                                                    <span class="inline-flex items-center gap-1 rounded bg-green-100 px-2 py-0.5 font-bold text-green-800 dark:bg-green-900/50 dark:text-green-300" title="Tüm readiness kriterleri (5/5) tamam">
                                                        ✓ Hazır (5/5)
                                                    </span>
                                                @elseif ($readiness)
                                                    <span class="inline-flex items-center gap-1 rounded bg-rose-100 px-2 py-0.5 font-bold text-rose-800 dark:bg-rose-900/50 dark:text-rose-300" title="Readiness eksikleri var">
                                                        ⚠️ Hazır Değil ({{ $readinessScore }}/5)
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-slate-800 dark:text-gray-400" title="Readiness değerlendirilmedi">
                                                        — Hazırlık (0/5)
                                                    </span>
                                                @endif

                                                <span class="text-gray-300 dark:text-gray-600">→</span>

                                                {{-- 4. Giriş (Check-in) --}}
                                                @if ($isCheckedIn || $isCheckedOut)
                                                    <span class="inline-flex items-center gap-1 rounded bg-green-100 px-2 py-0.5 font-medium text-green-800 dark:bg-green-900/50 dark:text-green-300" title="Giriş yapıldı: {{ $booking->checked_in_at?->format('d.m.Y H:i') }}">
                                                        ✓ Giriş
                                                    </span>
                                                @elseif ($isReady && !$isCancelled)
                                                    <span class="inline-flex items-center gap-1 rounded bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800 ring-1 ring-emerald-500 dark:bg-emerald-900/50 dark:text-emerald-300" title="Giriş için hazır">
                                                        ● Giriş Bekliyor
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-slate-800 dark:text-gray-400">
                                                        — Giriş
                                                    </span>
                                                @endif

                                                <span class="text-gray-300 dark:text-gray-600">→</span>

                                                {{-- 5. Konaklama --}}
                                                @if ($isCheckedOut)
                                                    <span class="inline-flex items-center gap-1 rounded bg-green-100 px-2 py-0.5 font-medium text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                                        ✓ Konaklama
                                                    </span>
                                                @elseif ($isCheckedIn)
                                                    <span class="inline-flex items-center gap-1 rounded bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800 ring-1 ring-emerald-500 dark:bg-emerald-900/50 dark:text-emerald-300">
                                                        🏡 Konaklıyor
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-slate-800 dark:text-gray-400">
                                                        — Konaklama
                                                    </span>
                                                @endif

                                                <span class="text-gray-300 dark:text-gray-600">→</span>

                                                {{-- 6. Çıkış (Check-out) --}}
                                                @if ($isCheckedOut)
                                                    <span class="inline-flex items-center gap-1 rounded bg-green-100 px-2 py-0.5 font-medium text-green-800 dark:bg-green-900/50 dark:text-green-300" title="Çıkış yapıldı: {{ $booking->checked_out_at?->format('d.m.Y H:i') }}">
                                                        ✓ Çıkış
                                                    </span>
                                                @elseif ($isCheckedIn)
                                                    <span class="inline-flex items-center gap-1 rounded bg-blue-100 px-2 py-0.5 font-medium text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                                        ⏳ Çıkış Bekliyor
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-slate-800 dark:text-gray-400">
                                                        — Çıkış
                                                    </span>
                                                @endif

                                                <span class="text-gray-300 dark:text-gray-600">→</span>

                                                {{-- 7. Temizlik (Turnover) --}}
                                                @if ($turnoverDone)
                                                    <span class="inline-flex items-center gap-1 rounded bg-green-100 px-2 py-0.5 font-bold text-green-800 dark:bg-green-900/50 dark:text-green-300" title="Temizlik tamamlandı">
                                                        ✓ Temizlendi
                                                    </span>
                                                @elseif ($turnoverTask)
                                                    <span class="inline-flex items-center gap-1 rounded bg-purple-100 px-2 py-0.5 font-bold text-purple-800 ring-1 ring-purple-500 dark:bg-purple-900/50 dark:text-purple-300" title="Temizlik görevi aktif/bekliyor">
                                                        🧹 Temizlik ({{ $turnoverTask->gorev_durumu }})
                                                    </span>
                                                @elseif ($isCheckedOut)
                                                    <span class="inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 font-bold text-amber-800 dark:bg-amber-900/50 dark:text-amber-300" title="Çıkış sonrası temizlik görevi bekleniyor">
                                                        ⏳ Temizlik Bekliyor
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-slate-800 dark:text-gray-400">
                                                        — Temizlik
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Wave 7: Exception Banners --}}
                                            @if (!empty($rowExceptions))
                                                <div class="mt-2.5 flex flex-col gap-1">
                                                    @foreach ($rowExceptions as $exc)
                                                        <div class="inline-flex items-center gap-1.5 rounded border px-2 py-0.5 text-xs font-semibold {{ $exc->isP0() ? 'border-red-300 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/60 dark:text-red-200' : 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-200' }}" title="{{ $exc->reason }}">
                                                            <span class="font-bold">{{ $exc->isP0() ? '🔴 P0' : '🟡 P1' }} — {{ $exc->title }}:</span>
                                                            <span class="font-normal">{{ $exc->reason }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-5 py-4 align-top text-right whitespace-nowrap">
                                            @if (!$isCancelled && !$isCheckedIn && !$isCheckedOut)
                                                {{-- Check-in Action (Wave 5) --}}
                                                <form action="{{ route('admin.yazlik-kiralama.bookings.check-in', $booking->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        id="btn-checkin-{{ $booking->id }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow transition-all hover:bg-emerald-700 active:scale-95">
                                                        <span>Misafir Geldi</span>
                                                    </button>
                                                </form>
                                            @elseif ($isCheckedIn && !$isCheckedOut)
                                                {{-- Check-out Action (Wave 5) --}}
                                                <form action="{{ route('admin.yazlik-kiralama.bookings.check-out', $booking->id) }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Misafirin çıkış yaptığını onaylıyor musunuz?');">
                                                    @csrf
                                                    <button type="submit"
                                                        id="btn-checkout-{{ $booking->id }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow transition-all hover:bg-blue-700 active:scale-95">
                                                        <span>Misafir Çıktı</span>
                                                    </button>
                                                </form>
                                            @elseif ($isCheckedOut)
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/60 dark:text-blue-200">
                                                    ✓ Tamamlandı (Çıkış Yapıldı)
                                                </span>
                                            @elseif ($isCancelled)
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 dark:bg-red-900/60 dark:text-red-200">
                                                    ✕ İptal (İşlem Yok)
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($bookings->hasPages())
                        <div class="border-t border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-900">
                            {{ $bookings->appends(request()->query())->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
@endsection
